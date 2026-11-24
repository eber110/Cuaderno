<?php

namespace Base\Module;

// Importar las clases de PHPMailer al namespace global.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\SMTP;

/**
 * MailService
 * * Un módulo para enviar correos electrónicos utilizando PHPMailer.
 * Encapsula la configuración y el proceso de envío para que sea reutilizable.
 */
class MailServiceModule
{

  private PHPMailer $mailer;
  private string $fromEmail = '';
  private string $userEmail = '';
  private string $lastError = '';
  private bool $isConfigured = false;

  /**
   * Lee una variable de entorno de forma segura.
   *
   * @param string $key Nombre de la variable.
   * @param string $default Valor por defecto si no existe o está vacía.
   * @return string
   */
  private function readEnv(string $key, string $default = ''): string
  {
    $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($val === false || $val === null) {
      return $default;
    }
    $trimmed = trim((string)$val);
    return $trimmed !== '' ? $trimmed : $default;
  }

  /**
   * Constructor. Configura PHPMailer con las credenciales SMTP.
   *
   * @param string|null $fromEmail El correo electrónico del remitente.
   * @param string|null $userEmail El nombre visible del remitente.
   */
  public function __construct(?string $fromEmail = null, ?string $userEmail = null)
  {
    $smtpHost       = $this->readEnv('SMTP_HOST');
    $smtpUser       = $this->readEnv('SMTP_USER');
    $smtpPass       = $this->readEnv('SMTP_PASS');
    $smtpPortRaw    = $this->readEnv('SMTP_PORT', '587');
    $smtpPort       = is_numeric($smtpPortRaw) ? (int)$smtpPortRaw : 587;
    $smtpEncryption = strtolower($this->readEnv('SMTP_ENCRYPTION'));
    $smtpTimeoutRaw = $this->readEnv('SMTP_TIMEOUT', '15');
    $smtpTimeout    = is_numeric($smtpTimeoutRaw) ? max(5, (int)$smtpTimeoutRaw) : 15;

    $defaultFrom = $this->readEnv('SMTP_FROM', $smtpUser);
    $defaultName = $this->readEnv(
      'SMTP_FROM_NAME',
      (defined('NAME_SITE') && NAME_SITE !== '')
        ? (string)NAME_SITE
        : $this->readEnv('APP_NAME', 'Notificaciones')
    );

    $this->fromEmail = trim((string)($fromEmail ?? $defaultFrom));
    $this->userEmail = trim((string)($userEmail ?? $defaultName));

    $this->mailer = new PHPMailer(true);
    $this->mailer->CharSet = PHPMailer::CHARSET_UTF8;
    $this->mailer->isSMTP();
    $this->mailer->Host     = $smtpHost;
    $this->mailer->SMTPAuth = true;
    $this->mailer->Username = $smtpUser;
    $this->mailer->Password = $smtpPass;
    $this->mailer->Port     = $smtpPort;
    $this->mailer->Timeout  = $smtpTimeout;

    if ($smtpEncryption === 'ssl' || $smtpEncryption === 'smtps') {
      $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($smtpEncryption === 'tls' || $smtpEncryption === 'starttls') {
      $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($smtpEncryption === 'none' || $smtpEncryption === 'false') {
      $this->mailer->SMTPSecure = '';
      $this->mailer->SMTPAutoTLS = false;
    } else {
      $this->mailer->SMTPSecure = ($smtpPort === 465)
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    }

    $this->isConfigured = ($smtpHost !== '' && $smtpUser !== '' && $smtpPass !== '' && $this->fromEmail !== '');
  }

  /**
   * Devuelve el último mensaje de error registrado al intentar conectar o enviar.
   *
   * @return string
   */
  public function getLastError(): string
  {
    return $this->lastError;
  }

  /**
   * Verifica la conexión y autenticación con el servidor SMTP sin enviar correos.
   *
   * @return bool True si la conexión SMTP fue exitosa, false en caso contrario.
   */
  public function testConnection(): bool
  {
    $this->lastError = '';

    if (!$this->isConfigured) {
      $this->lastError = 'Configuración SMTP incompleta (SMTP_HOST, SMTP_USER o SMTP_PASS ausentes).';
      error_log('[MailServiceModule] ' . $this->lastError);
      return false;
    }

    try {
      if ($this->mailer->smtpConnect()) {
        $this->mailer->smtpClose();
        return true;
      }
      $this->lastError = $this->mailer->ErrorInfo !== '' ? $this->mailer->ErrorInfo : 'No se pudo establecer conexión SMTP.';
      error_log('[MailServiceModule] ' . $this->lastError);
      return false;
    } catch (PHPMailerException|\Throwable $e) {
      $this->lastError = $this->mailer->ErrorInfo !== '' ? $this->mailer->ErrorInfo : $e->getMessage();
      error_log('[MailServiceModule] Error de conexión SMTP: ' . $this->lastError);
      return false;
    }
  }

  /**
   * Genera texto plano limpio a partir de un cuerpo HTML eliminando bloques <style> y <script>.
   *
   * @param string $html Cuerpo HTML del correo.
   * @return string Texto plano limpio para AltBody.
   */
  private function buildPlainTextFallback(string $html): string
  {
    $clean = preg_replace('#<(style|script)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
    $clean = preg_replace('/<\s*br\s*\/?>/i', "\n", $clean) ?? $clean;
    $clean = preg_replace('/<\/\s*(p|div|li|tr|h[1-6])\s*>/i', "\n", $clean) ?? $clean;
    $plain = strip_tags($clean);
    $plain = html_entity_decode($plain, ENT_QUOTES, 'UTF-8');
    $plain = preg_replace("/[ \t]+/", ' ', $plain) ?? $plain;
    $plain = preg_replace("/\n{3,}/", "\n\n", $plain) ?? $plain;
    return trim($plain);
  }

  /**
   * Envía un correo electrónico.
   *
   * @param string|array $to Dirección o array de direcciones del destinatario.
   * @param string $subject El asunto del correo.
   * @param string $body El cuerpo del correo (puede ser HTML).
   * @param bool $isHTML Define si el cuerpo del correo es HTML.
   * @param array $attachments Array con las rutas a los archivos adjuntos.
   * @param array $cc Array con direcciones para copia (CC).
   * @param array $bcc Array con direcciones para copia oculta (BCC).
   * @param string|null $altBody Texto plano alternativo explícito (opcional).
   * @return bool Devuelve true si el correo se envió, false en caso de error.
   */
  public function sendEmail(
    $to,
    string $subject,
    string $body,
    bool $isHTML = true,
    array $attachments = [],
    array $cc = [],
    array $bcc = [],
    ?string $altBody = null
  ): bool {
    $this->lastError = '';

    if (!$this->isConfigured) {
      $this->lastError = 'Configuración SMTP incompleta (SMTP_HOST, SMTP_USER o SMTP_PASS ausentes).';
      error_log('[MailServiceModule] ' . $this->lastError);
      return false;
    }

    try {
      // Limpiar destinatarios, adjuntos y cabeceras previas para reutilización segura de la instancia
      $this->mailer->clearAllRecipients();
      $this->mailer->clearAttachments();
      $this->mailer->clearReplyTos();
      $this->mailer->clearCustomHeaders();

      // Remitente
      $this->mailer->setFrom($this->fromEmail, $this->userEmail);

      // Destinatarios
      if (is_array($to)) {
        foreach ($to as $address) {
          $this->mailer->addAddress($address);
        }
      } else {
        $this->mailer->addAddress($to);
      }

      // Copias (CC y BCC)
      foreach ($cc as $address) {
        $this->mailer->addCC($address);
      }

      foreach ($bcc as $address) {
        $this->mailer->addBCC($address);
      }

      // Archivos adjuntos
      foreach ($attachments as $path) {
        if (file_exists($path)) {
          $this->mailer->addAttachment($path);
        }
      }

      // Contenido del correo
      $this->mailer->isHTML($isHTML);
      $this->mailer->Subject = $subject;
      $this->mailer->Body    = $body;

      // Versión de texto plano sin residuos de <style>/<script>
      if ($isHTML) {
        $this->mailer->AltBody = ($altBody !== null && trim($altBody) !== '')
          ? $altBody
          : $this->buildPlainTextFallback($body);
      } else {
        $this->mailer->AltBody = '';
      }

      $this->mailer->send();
      return true;
    } catch (PHPMailerException|\Throwable $e) {
      $this->lastError = $this->mailer->ErrorInfo !== '' ? $this->mailer->ErrorInfo : $e->getMessage();
      error_log('[MailServiceModule] Error al enviar correo: ' . $this->lastError);
      return false;
    } finally {
      $this->mailer->clearAllRecipients();
      $this->mailer->clearAttachments();
      $this->mailer->clearReplyTos();
      $this->mailer->clearCustomHeaders();
    }
  }
}
