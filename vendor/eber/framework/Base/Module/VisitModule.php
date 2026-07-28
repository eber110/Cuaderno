<?php

namespace Base\Module;

/**
 * Módulo de gestión de visitantes y geolocalización.
 * 
 * Obtiene datos geográficos basados en la IP del usuario,
 * implementa caché para minimizar peticiones a la API externa,
 * y gestiona los datos de sesión del visitante.
 * 
 * @example
 * // Inicializar geolocalización en sesión
 * VisitModule::initSession('my_cookie_name');
 * 
 * // Obtener datos de ubicación
 * $location = VisitModule::getLocation();
 * echo $location['pais']; // "Chile"
 */
class VisitModule
{
  /**
   * TTL del caché en segundos (24 horas).
   */
  private const CACHE_TTL = 86400;

  /**
   * Directorio de caché para geolocalización.
   */
  private static ?string $cacheDir = null;

  /**
   * Obtiene la URL de la API primaria de geolocalización.
   */
  private static function getGeoApiPrimary(): string
  {
    return defined('GEO_API_PRIMARY') ? GEO_API_PRIMARY : 'https://ip.guide/';
  }

  /**
   * Obtiene la URL de la API secundaria/fallback de geolocalización.
   */
  private static function getGeoApiFallback(): string
  {
    return defined('GEO_API_FALLBACK') ? GEO_API_FALLBACK : 'https://api.ipquery.io/';
  }

  /**
   * Obtiene el timeout de conexión en segundos.
   */
  private static function getConnectTimeout(): int
  {
    return defined('GEO_CONNECT_TIMEOUT') ? GEO_CONNECT_TIMEOUT : 1;
  }

  /**
   * Obtiene el timeout total de request en segundos.
   */
  private static function getRequestTimeout(): int
  {
    return defined('GEO_REQUEST_TIMEOUT') ? GEO_REQUEST_TIMEOUT : 1;
  }

  /**
   * IPs consideradas locales.
   */
  private const LOCAL_IPS = ['::1', '127.0.0.1'];

  /**
   * Modo debug (poner en false en producción).
   */
  private const DEBUG = false;

  /**
   * Inicializa la sesión de geolocalización.
   * 
   * Verifica si es necesario actualizar los datos de ubicación
   * y clasifica al visitante como usuario o invitado.
   * 
   * @param string $cookieName Nombre de la cookie identificadora.
   * @return void
   */
  public static function initSession(string $cookieName): void
  {
    $currentIp = self::getClientIp();
    $needsUpdate = self::needsGeoUpdate($currentIp);

    // Calcular clasificación del visitante PRIMERO
    $visitorData = self::getVisitorClassification($cookieName);

    if (self::DEBUG) {
      echo "<pre>DEBUG VisitModule::initSession\n";
      echo "Current IP: $currentIp\n";
      echo "Is Local IP: " . (self::isLocalIp($currentIp) ? 'true' : 'false') . "\n";
      echo "Needs Update: " . ($needsUpdate ? 'true' : 'false') . "\n";
      echo "Is Visitor: " . ($visitorData['is_visitor'] ? 'true' : 'false') . "\n";
      echo "</pre>";
    }

    if ($needsUpdate) {
      $geoData = self::fetchGeoData($currentIp);

      if (self::DEBUG) {
        echo "<pre>DEBUG geoData received:\n";
        var_dump($geoData);
        echo "</pre>";
      }

      if (!empty($geoData)) {
        // Incluir datos del visitante junto con geo data
        $_SESSION['location'] = array_merge($geoData, $visitorData);
      } else {
        // Si no hay geoData pero necesitamos clasificar
        $_SESSION['location'] = array_merge($_SESSION['location'] ?? [], $visitorData);
      }
    } else {
      // Siempre actualizar la clasificación del visitante incluso sin update de geo
      $_SESSION['location'] = array_merge($_SESSION['location'] ?? [], $visitorData);
    }
  }

  /**
   * Obtiene los datos de ubicación de la sesión actual.
   * 
   * @return array|null Datos de ubicación o null si no existen.
   */
  public static function getLocation(): ?array
  {
    return $_SESSION['location'] ?? null;
  }

  /**
   * Obtiene la IP del cliente.
   * 
   * @return string|null IP del cliente.
   */
  public static function getClientIp(): ?string
  {
    // Verificar headers de proxy primero
    $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP'];

    foreach ($headers as $header) {
      if (!empty($_SERVER[$header])) {
        $ips = explode(',', $_SERVER[$header]);
        return trim($ips[0]);
      }
    }

    return $_SERVER['REMOTE_ADDR'] ?? null;
  }

  /**
   * Verifica si la IP es local (desarrollo).
   * 
   * @param string|null $ip IP a verificar.
   * @return bool True si es IP local.
   */
  public static function isLocalIp(?string $ip): bool
  {
    if ($ip === null) {
      return true;
    }

    if (in_array($ip, self::LOCAL_IPS)) {
      return true;
    }

    // IPs de red local
    if (strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
      return true;
    }

    return false;
  }

  /**
   * Verifica si es un usuario registrado o invitado.
   * 
   * @return bool True si es usuario registrado.
   */
  public static function isRegisteredUser(): bool
  {
    return !($_SESSION['location']['is_visitor'] ?? true);
  }

  /**
   * Obtiene el identificador del visitante.
   * 
   * @return string|null Identificador.
   */
  public static function getVisitorId(): ?string
  {
    return $_SESSION['location']['identifier'] ?? null;
  }

  /**
   * Fuerza actualización de datos geográficos.
   * 
   * @return array Nuevos datos de ubicación.
   */
  public static function refreshGeoData(): array
  {
    $ip = self::getClientIp();
    $geoData = self::fetchGeoData($ip, true);

    if (!empty($geoData)) {
      $_SESSION['location'] = array_merge(
        $_SESSION['location'] ?? [],
        $geoData
      );
    }

    return $geoData;
  }

  /**
   * Determina si se necesita actualizar la geolocalización.
   */
  private static function needsGeoUpdate(string $currentIp): bool
  {
    // Sin datos de ubicación
    if (empty($_SESSION['location']['pais'])) {
      return true;
    }

    // IP ha cambiado
    if (($_SESSION['location']['ip'] ?? '') !== $currentIp) {
      return true;
    }

    return false;
  }

  /**
   * Obtiene datos geográficos para una IP.
   */
  private static function fetchGeoData(?string $ip, bool $forceRefresh = false): array
  {
    if (self::DEBUG) {
      error_log("visitModule fetchGeoData: IP=$ip, forceRefresh=" . ($forceRefresh ? 'true' : 'false'));
    }

    // IP local - datos de desarrollo
    if (self::isLocalIp($ip)) {
      if (self::DEBUG) {
        error_log("visitModule fetchGeoData: IP detectada como local");
      }
      return [
        'ip' => $ip ?? 'localhost',
        'pais' => 'Local Development',
        'codigo' => 'DEV',
        'region' => 'Local',
        'ciudad' => 'Localhost'
      ];
    }

    // Intentar caché primero
    if (!$forceRefresh) {
      $cached = self::getFromCache($ip);
      if ($cached !== null) {
        if (self::DEBUG) {
          error_log("visitModule fetchGeoData: Datos obtenidos de caché");
        }
        return $cached;
      }
    }

    // Consultar API primaria (ip.guide)
    if (self::DEBUG) {
      error_log("visitModule fetchGeoData: Consultando API primaria (ip.guide) para IP=$ip");
    }
    $response = self::queryGeoApi($ip, self::getGeoApiPrimary());

    if ($response !== null) {
      if (self::DEBUG) {
        error_log("visitModule fetchGeoData: API primaria respondió correctamente");
      }
      $data = self::normalizeGeoResponse($response, $ip, 'ipguide');
      self::saveToCache($ip, array_merge($response, ['_api_source' => 'ipguide']));
      return $data;
    }

    // Fallback: API secundaria (ipquery.io)
    if (self::DEBUG) {
      error_log("visitModule fetchGeoData: API primaria falló, intentando fallback (ipquery.io)");
    }
    $response = self::queryGeoApi($ip, self::getGeoApiFallback());

    if ($response !== null) {
      if (self::DEBUG) {
        error_log("visitModule fetchGeoData: API fallback respondió correctamente");
      }
      $data = self::normalizeGeoResponse($response, $ip, 'ipquery');
      self::saveToCache($ip, array_merge($response, ['_api_source' => 'ipquery']));
      return $data;
    }

    if (self::DEBUG) {
      error_log("visitModule fetchGeoData: API falló, devolviendo fallback vacío");
    }

    // Fallback vacío
    return [
      'ip' => $ip,
      'pais' => '',
      'codigo' => '',
      'region' => '',
      'ciudad' => ''
    ];
  }

  /**
   * Consulta una API de geolocalización.
   * 
   * @param string $ip IP a consultar.
   * @param string $apiUrl URL base de la API.
   * @return array|null Respuesta decodificada o null si falló.
   */
  private static function queryGeoApi(string $ip, string $apiUrl): ?array
  {
    $ch = curl_init();

    curl_setopt_array($ch, [
      CURLOPT_URL => $apiUrl . $ip,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => self::getConnectTimeout(),
      CURLOPT_TIMEOUT => self::getRequestTimeout(),
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_USERAGENT => 'VisitModule/2.0',
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => 0
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);

    if (self::DEBUG) {
      error_log("visitModule API Request: $apiUrl$ip");
      error_log("visitModule API HTTP Code: $httpCode");
      error_log("visitModule API cURL errno: $errno");
      error_log("visitModule API cURL error: $error");
      error_log("visitModule API Response: " . substr($response ?: '', 0, 500));
    }

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
      return null;
    }

    return json_decode($response, true);
  }

  /**
   * Normaliza la respuesta de la API según su origen.
   * 
   * @param array $response Respuesta de la API.
   * @param string $ip IP consultada.
   * @param string $source 'ipguide' o 'ipquery'.
   * @return array Datos normalizados.
   */
  private static function normalizeGeoResponse(array $response, string $ip, string $source = 'ipguide'): array
  {
    // Auto-detectar el formato si no se especifica o si hay un caché antiguo
    // ip.guide tiene 'network.autonomous_system', ipquery tiene 'location.country_code'
    if (
      isset($response['network']['autonomous_system']) ||
      (isset($response['location']['country']) && !isset($response['location']['country_code']))
    ) {
      $source = 'ipguide';
    } elseif (isset($response['location']['country_code'])) {
      $source = 'ipquery';
    }

    if ($source === 'ipguide') {
      // Formato ip.guide:
      // {"ip": "...", "location": {"city": "Santiago", "country": "Chile", ...}, "network": {...}}
      $codigo = '';
      if (isset($response['network']['autonomous_system']['country'])) {
        $codigo = $response['network']['autonomous_system']['country'];
      }

      return [
        'ip' => $response['ip'] ?? $ip,
        'pais' => $response['location']['country'] ?? '',
        'codigo' => $codigo,
        'region' => $response['location']['timezone'] ?? '',
        'ciudad' => $response['location']['city'] ?? ''
      ];
    }

    // Formato ipquery.io:
    // {"ip": "...", "location": {"country": "Chile", "country_code": "CL", "state": "...", "city": "..."}}
    $state = $response['location']['state'] ?? '';
    $stateShort = !empty($state) ? explode(' ', $state)[0] : '';

    return [
      'ip' => $response['ip'] ?? $ip,
      'pais' => $response['location']['country'] ?? '',
      'codigo' => $response['location']['country_code'] ?? '',
      'region' => $stateShort,
      'ciudad' => $response['location']['city'] ?? ''
    ];
  }

  /**
   * Obtiene datos del caché.
   */
  private static function getFromCache(string $ip): ?array
  {
    $cacheFile = self::getCacheFilePath($ip);

    if (!is_readable($cacheFile)) {
      return null;
    }

    if ((time() - filemtime($cacheFile)) >= self::CACHE_TTL) {
      return null;
    }

    $content = @file_get_contents($cacheFile);
    if ($content === false) {
      return null;
    }

    $data = json_decode($content, true);
    if ($data === null) {
      return null;
    }

    // Usar la fuente guardada en caché para normalizar correctamente
    $source = $data['_api_source'] ?? 'ipguide';
    return self::normalizeGeoResponse($data, $ip, $source);
  }

  /**
   * Guarda datos en caché.
   */
  private static function saveToCache(string $ip, array $data): void
  {
    $cacheDir = self::getCacheDir();

    if (!is_dir($cacheDir)) {
      @mkdir($cacheDir, 0755, true);
    }

    $cacheFile = self::getCacheFilePath($ip);
    @file_put_contents($cacheFile, json_encode($data), LOCK_EX);
  }

  /**
   * Obtiene el directorio de caché.
   */
  private static function getCacheDir(): string
  {
    if (self::$cacheDir === null) {
      self::$cacheDir = ROOT_PATH . '/Logs/VisitLog/';
    }
    return self::$cacheDir;
  }

  /**
   * Obtiene la ruta del archivo de caché para una IP.
   */
  private static function getCacheFilePath(string $ip): string
  {
    $safeIp = str_replace([':', '.'], '-', $ip);
    return self::getCacheDir() . DIRECTORY_SEPARATOR . "ip_{$safeIp}.json";
  }

  /**
   * Obtiene la clasificación del visitante como usuario o invitado.
   * 
   * @param string $cookieName Nombre de la cookie identificadora.
   * @return array Array con 'identifier' e 'is_visitor'.
   */
  private static function getVisitorClassification(string $cookieName): array
  {
    return [
      'identifier' => $_COOKIE[$cookieName] ?? null,
      'is_visitor' => empty($_SESSION['user'])
    ];
  }

  // ============================================
  // MÉTODOS LEGACY (compatibilidad hacia atrás)
  // ============================================

  /**
   * @deprecated Usar initSession() directamente.
   */
  public static function apiGeo_session($cookie): void
  {
    self::initSession($cookie);
  }
}
