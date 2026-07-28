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
    if (self::$baseUrl === null) {
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
      $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
      self::$baseUrl = $protocol . '://' . $host;
    }
    return self::$baseUrl;
  }

  /**
   * Envía datos POST a una ruta interna.
   * 
   * @param array $data Array de datos a enviar.
   * @param string $route Ruta destino (ej: '/mi-ruta').
   * @param array $options Opciones adicionales ['timeout' => 30, 'headers' => []].
   * @return array ['success' => bool, 'response' => mixed, 'httpCode' => int, 'error' => string|null]
   * 
   * @example
   * // Enviar a ruta interna
   * $resultado = HttpPostModule::postData(['user_id' => 1, 'action' => 'like'], '/interaccion');
   * 
   * if ($resultado['success']) {
   *     echo "Datos enviados correctamente";
   * }
   */
  public static function postData(array $data, string $route, array $options = []): array
  {
    $timeout = $options['timeout'] ?? 30;
    $headers = $options['headers'] ?? [];

    // Construir URL completa
    $url = self::getBaseUrl() . '/' . ltrim($route, '/');

    // Inicializar cURL
    $ch = curl_init();

    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => http_build_query($data),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => $timeout,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTPHEADER => array_merge([
        'Content-Type: application/x-www-form-urlencoded'
      ], $headers),
      // Para desarrollo local, permite certificados autofirmados
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
    ]);

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
}
