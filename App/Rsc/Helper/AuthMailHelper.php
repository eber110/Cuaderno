<?php

namespace App\Rsc\Helper;

use Base\Module\MailServiceModule;

/**
 * Helper AuthMailHelper
 *
 * Construye y envía los correos transaccionales de autenticación
 * (verificación de correo electrónico y recuperación de contraseña).
 */
class AuthMailHelper
{
  /**
   * Obtiene el nombre visible de la aplicación.
   *
   * @return string
   */
  private static function getSiteName(): string
  {
    if (defined("NAME_SITE") && trim((string)NAME_SITE) !== "") {
      return trim((string)NAME_SITE);
    }

    $appName = $_ENV["APP_NAME"] ?? getenv("APP_NAME") ?: "Clikhub";
    return trim((string)$appName) !== "" ? trim((string)$appName) : "Clikhub";
  }

  /**
   * Envía el correo con el enlace de verificación de cuenta (expira en 1 hora).
   *
   * @param string $toEmail Correo del destinatario.
   * @param string $username Nombre de usuario registrado.
   * @param string $verifyUrl URL absoluta de verificación con token.
   * @return array{success: bool, error: string} Resultado del envío.
   */
  public static function sendVerificationEmail(string $toEmail, string $username, string $verifyUrl): array
  {
    $siteName    = self::getSiteName();
    $safeSite    = htmlspecialchars($siteName, ENT_QUOTES, "UTF-8");
    $safeUser    = htmlspecialchars($username, ENT_QUOTES, "UTF-8");
    $safeUrl     = htmlspecialchars($verifyUrl, ENT_QUOTES, "UTF-8");
    $subject     = "Confirma tu correo en {$siteName}";

    $body = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>{$subject}</title>
</head>
<body style="margin:0;padding:0;background-color:#ede9fe;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:32px 16px;background-color:#ede9fe;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 25px rgba(11,16,30,0.08);">
          <tr>
            <td style="background-color:#c33e5a;padding:24px 28px;text-align:center;">
              <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">{$safeSite}</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:28px;">
              <h2 style="margin:0 0 12px 0;color:#0b101e;font-size:20px;">¡Hola, @{$safeUser}!</h2>
              <p style="margin:0 0 16px 0;font-size:15px;line-height:1.6;color:#334155;">
                Gracias por crear tu cuenta en <strong>{$safeSite}</strong>. Para activar tu perfil y confirmar que este correo es real, haz clic en el siguiente botón:
              </p>
              <table role="presentation" cellspacing="0" cellpadding="0" style="margin:24px auto;">
                <tr>
                  <td align="center" style="border-radius:12px;background-color:#0b101e;">
                    <a href="{$safeUrl}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:14px 28px;font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:12px;">
                      Verificar mi correo
                    </a>
                  </td>
                </tr>
              </table>
              <p style="margin:0 0 12px 0;font-size:13px;line-height:1.5;color:#64748b;">
                Este enlace es válido por <strong>1 hora</strong> y solo se puede usar una vez. Si el botón no funciona, copia y pega esta dirección en tu navegador:
              </p>
              <p style="margin:0 0 18px 0;font-size:12px;line-height:1.5;word-break:break-all;color:#c33e5a;">
                {$safeUrl}
              </p>
              <p style="margin:0;font-size:13px;line-height:1.5;color:#64748b;">
                Si no creaste esta cuenta, puedes ignorar este mensaje con tranquilidad.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    $altBody = "¡Hola, @{$username}!\n\n"
      . "Gracias por registrarte en {$siteName}. Para activar tu cuenta, abre el siguiente enlace (válido por 1 hora y de un solo uso):\n"
      . "{$verifyUrl}\n\n"
      . "Si no solicitaste esta cuenta, ignora este mensaje.";

    $mailer = new MailServiceModule(null, $siteName);
    $sent   = $mailer->sendEmail($toEmail, $subject, $body, true, [], [], [], $altBody);

    return [
      "success" => $sent,
      "error"   => $sent ? "" : $mailer->getLastError()
    ];
  }

  /**
   * Envía el correo con el enlace para restablecer la contraseña (expira en 30 minutos).
   *
   * @param string $toEmail Correo del destinatario.
   * @param string $username Nombre de usuario asociado.
   * @param string $resetUrl URL absoluta de restablecimiento con token.
   * @return array{success: bool, error: string} Resultado del envío.
   */
  public static function sendPasswordResetEmail(string $toEmail, string $username, string $resetUrl): array
  {
    $siteName    = self::getSiteName();
    $safeSite    = htmlspecialchars($siteName, ENT_QUOTES, "UTF-8");
    $safeUser    = htmlspecialchars($username, ENT_QUOTES, "UTF-8");
    $safeUrl     = htmlspecialchars($resetUrl, ENT_QUOTES, "UTF-8");
    $subject     = "Recupera tu contraseña en {$siteName}";

    $body = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>{$subject}</title>
</head>
<body style="margin:0;padding:0;background-color:#ede9fe;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:32px 16px;background-color:#ede9fe;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 25px rgba(11,16,30,0.08);">
          <tr>
            <td style="background-color:#c33e5a;padding:24px 28px;text-align:center;">
              <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">{$safeSite}</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:28px;">
              <h2 style="margin:0 0 12px 0;color:#0b101e;font-size:20px;">Restablece tu contraseña</h2>
              <p style="margin:0 0 16px 0;font-size:15px;line-height:1.6;color:#334155;">
                Recibimos una solicitud para cambiar la contraseña de tu cuenta <strong>@{$safeUser}</strong> en {$safeSite}. Haz clic en el botón para elegir una nueva:
              </p>
              <table role="presentation" cellspacing="0" cellpadding="0" style="margin:24px auto;">
                <tr>
                  <td align="center" style="border-radius:12px;background-color:#0b101e;">
                    <a href="{$safeUrl}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:14px 28px;font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:12px;">
                      Cambiar mi contraseña
                    </a>
                  </td>
                </tr>
              </table>
              <p style="margin:0 0 12px 0;font-size:13px;line-height:1.5;color:#64748b;">
                Este enlace caduca en <strong>30 minutos</strong> y solo funciona una vez. Si el botón no abre, copia y pega esta dirección en tu navegador:
              </p>
              <p style="margin:0 0 18px 0;font-size:12px;line-height:1.5;word-break:break-all;color:#c33e5a;">
                {$safeUrl}
              </p>
              <p style="margin:0;font-size:13px;line-height:1.5;color:#64748b;">
                Si no pediste cambiar tu contraseña, no realices ninguna acción; tu clave actual seguirá protegida.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    $altBody = "Hola, @{$username}.\n\n"
      . "Recibimos una solicitud para restablecer tu contraseña en {$siteName}. Abre este enlace en los próximos 30 minutos:\n"
      . "{$resetUrl}\n\n"
      . "Si no solicitaste este cambio, ignora este correo.";

    $mailer = new MailServiceModule(null, $siteName);
    $sent   = $mailer->sendEmail($toEmail, $subject, $body, true, [], [], [], $altBody);

    return [
      "success" => $sent,
      "error"   => $sent ? "" : $mailer->getLastError()
    ];
  }
}
