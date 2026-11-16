<?php

namespace Base\Module;

/**
 * Módulo para enviar datos POST a rutas internas sin formulario.
 * 
 * Permite enviar arrays de datos a rutas de la aplicación
 * de forma programática usando cURL.
 * 
 * @example
 * // Uso estático
 * HttpPostModule::postData(['nombre' => 'Juan', 'edad' => 25], '/mi-ruta');
 * 
 * // Uso como instancia
 * $enviar = new HttpPostModule();
 * $enviar->postData(['nombre' => 'Juan'], '/mi-ruta');
 */
class HttpPostModule
{
  /**
   * URL base de la aplicación.
   * @var string
   */
  private static ?string $baseUrl = null;

  /**
   * Obtiene la URL base de la aplicación.
   * 
   * @return string
   */
  private static function getBaseUrl(): string
  {
    if (self::$baseUrl !== null) {
      return rtrim(self::$baseUrl, '/');
    }

    if (defined('FORCE_DOMAIN') && !empty(FORCE_DOMAIN)) {
      $proto = SecurityModule::isHttpsRequest() ? 'https://' : 'http://';
      return $proto . trim((string)FORCE_DOMAIN, '/');
    }

    $appUrl = trim((string)($_ENV['APP_URL'] ?? $_SERVER['APP_URL'] ?? ''));
    if ($appUrl !== '' && preg_match('#^https?://[a-zA-Z0-9.\-:]+#i', $appUrl)) {
      return rtrim($appUrl, '/');
    }

    if (defined('DOMAIN') && !empty(DOMAIN)) {
      return rtrim((string)DOMAIN, '/');
    }

    return 'http://127.0.0.1';
  }

  /**
   * Envía datos POST a una ruta interna segura con TLS estricto y sin redirecciones automáticas.
   * 
   * @param array $data Array de datos a enviar.
   * @param string $route Ruta destino (ej: '/mi-ruta').
   * @param array $options Opciones adicionales ['timeout' => 30, 'headers' => [], 'baseUrl' => ...].
   * @return array ['success' => bool, 'response' => mixed, 'httpCode' => int, 'error' => string|null]
   */
  public static function postData(array $data, string $route, array $options = []): array
  {
    $timeout = (int)($options['timeout'] ?? 30);
    $headers = (array)($options['headers'] ?? []);

    // Validar ruta interna estricta evitando SSRF hacia esquemas/hosts arbitrarios
    $cleanRoute = str_replace(["\r", "\n", "\0"], '', trim($route));
    if (preg_match('#^(https?:)?//#i', $cleanRoute) || str_starts_with($cleanRoute, '/\\') || str_contains($cleanRoute, '\\')) {
      return [
        'success' => false,
        'response' => false,
        'httpCode' => 0,
        'error' => 'Ruta destino inválida para HttpPostModule'
      ];
    }

    $base = !empty($options['baseUrl']) ? rtrim((string)$options['baseUrl'], '/') : self::getBaseUrl();
    $url = $base . '/' . ltrim($cleanRoute, '/');

    // Inicializar cURL con verificación TLS activa
    $ch = curl_init();

    $curlOpts = [
      CURLOPT_URL => $url,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => http_build_query($data),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => $timeout,
      CURLOPT_FOLLOWLOCATION => false,
      CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
      CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
      CURLOPT_HTTPHEADER => array_merge([
        'Content-Type: application/x-www-form-urlencoded'
      ], $headers),
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_SSL_VERIFYHOST => 2,
    ];

    if (class_exists('\\Composer\\CaBundle\\CaBundle')) {
      $caBundle = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
      if ($caBundle && file_exists($caBundle)) {
        $curlOpts[CURLOPT_CAINFO] = $caBundle;
      }
    }

    curl_setopt_array($ch, $curlOpts);

    // Ejecutar petición
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    return [
      'success' => $httpCode >= 200 && $httpCode < 300 && $error === '',
      'response' => $response,
      'httpCode' => $httpCode,
      'error' => $error ?: null
    ];
  }

  /**
   * Envía datos POST y retorna la respuesta decodificada como JSON.
   * 
   * @param array $data Array de datos a enviar.
   * @param string $route Ruta destino.
   * @param array $options Opciones adicionales.
   * @return array ['success' => bool, 'data' => mixed, 'httpCode' => int, 'error' => string|null]
   */
  public static function postDataJson(array $data, string $route, array $options = []): array
  {
    $result = self::postData($data, $route, $options);

    if ($result['success'] && !empty($result['response'])) {
      $decoded = json_decode($result['response'], true);
      $result['data'] = $decoded ?? $result['response'];
    } else {
      $result['data'] = null;
    }

    return $result;
  }

  /**
   * Envío simple - solo retorna true/false.
   * 
   * @param array $data Array de datos a enviar.
   * @param string $route Ruta destino.
   * @return bool
   * 
   * @example
   * if (HttpPostModule::enviar(['id' => 5], '/procesar')) {
   *     echo "Enviado!";
   * }
   */
  public static function enviar(array $data, string $route): bool
  {
    return self::postData($data, $route)['success'];
  }

  /**
   * Permite establecer una URL base personalizada.
   * 
   * @param string $url URL base.
   * @return void
   */
  public static function setBaseUrl(string $url): void
  {
    self::$baseUrl = rtrim($url, '/');
  }

  /**
   * Obtiene todos los datos de $_POST sanitizados (o un campo específico).
   *
   * @param string|null $key Clave opcional de $_POST.
   * @param mixed $default Valor por defecto si no existe la clave.
   * @param bool $stripTags Si es true, elimina etiquetas HTML.
   * @return mixed Array completo sanitizado o valor del campo.
   */
  public static function getPost(?string $key = null, mixed $default = null, bool $stripTags = false): mixed
  {
    if ($key !== null) {
      return SecurityModule::post($key, $default, $stripTags);
    }
    return SecurityModule::sanitizeArray($_POST ?? [], $stripTags);
  }
}
