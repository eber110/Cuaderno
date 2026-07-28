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
  private string $fromEmail;
  private string $userEmail;

  /**
   * Constructor. Configura PHPMailer con las credenciales SMTP.
   * @param string $fromEmail El correo electrónico del remitente (Tú correo).
   * @param string $userEmail El remitente del correo enviado (Tú nombre)
   */
  public function __construct(?string $fromEmail = null, ?string $userEmail = null)
  {

    // Guardamos el correo del remitente en la propiedad de la clase
    $this->fromEmail = $fromEmail ?? $_ENV['SMTP_USER'];
    $this->userEmail = $userEmail ?? $_SERVER['SERVER_NAME'];

    $this->mailer = new PHPMailer(true); // El 'true' activa las excepciones

    // CONFIGURACIÓN DE CARACTERES PARA ESPAÑOL
    $this->mailer->CharSet = PHPMailer::CHARSET_UTF8;

    // Configuración del servidor SMTP
    //$this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
    $this->mailer->isSMTP();
    $this->mailer->Host       = $_ENV['SMTP_HOST'];
    $this->mailer->SMTPAuth   = true;
    $this->mailer->Username   = $_ENV['SMTP_USER'];
    $this->mailer->Password   = $_ENV['SMTP_PASS'];
    $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $this->mailer->Port       = $_ENV['SMTP_PORT'];
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
   * @return bool Devuelve true si el correo se envió, false en caso de error.
   */
  public function sendEmail(
    $to,
    string $subject,
    string $body,
    bool $isHTML = true,
    array $attachments = [],
    array $cc = [],
    array $bcc = []
  ): bool {

    try {

      // Remitente
      // Ahora usamos la propiedad definida en el constructor
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

      // Versión de texto plano
      if ($isHTML) {

        $this->mailer->AltBody = strip_tags($body);
      }

      $this->mailer->send();
      return true;
    } catch (PHPMailerException $e) {

      //error_log("Error al enviar correo: " . $this->mailer->ErrorInfo);
      return false;
    }
  }
}
