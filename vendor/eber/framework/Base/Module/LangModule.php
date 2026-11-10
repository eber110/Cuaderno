<?php

namespace Base\Module;

/**
 * Módulo de internacionalización y localización (i18n).
 *
 * Responsable de cargar diccionarios de forma perezosa por grupo, resolver claves
 * anidadas con interpolación segura, aplicar reglas de pluralización y ejecutar
 * la cascada de detección de idioma cuando el modo multiidioma está habilitado.
 */
class LangModule
{
  /**
   * Idioma activo en la petición actual.
   */
  private static ?string $currentLocale = null;

  /**
   * Indica si la cascada de detección ya se ejecutó en la petición actual.
   */
  private static bool $detected = false;

  /**
   * Caché en memoria estática de los grupos cargados: [locale => [group => array]].
   *
   * @var array<string, array<string, array>>
   */
  private static array $loadedGroups = [];

  /**
   * Configuración cargada desde LangConfiguration.php.
   */
  private static ?array $config = null;

  /**
   * Registro en memoria de claves faltantes ya reportadas en esta petición para evitar duplicados.
   *
   * @var array<string, bool>
   */
  private static array $loggedMissing = [];

  /**
   * Obtiene la configuración de idioma combinando valores por defecto y App/Config/LangConfiguration.php.
   *
   * @return array
   */
  public static function getConfig(): array
  {
    if (self::$config !== null) {
      return self::$config;
    }

    $defaultLocale = defined('LANG_DEFAULT') ? (string)LANG_DEFAULT : 'es';
    $fallbackLocale = defined('LANG_FALLBACK') ? (string)LANG_FALLBACK : $defaultLocale;
    $available = defined('LANG_AVAILABLE') && is_array(LANG_AVAILABLE) ? LANG_AVAILABLE : [$defaultLocale];

    $defaults = [
      'default'             => $defaultLocale,
      'fallback'            => $fallbackLocale,
      'available'           => $available,
      'url_prefix'          => true,
      'hide_default_in_url' => true,
      'cookie'              => [
        'name'     => 'fme_lang',
        'lifetime' => defined('TIME_YEAR') ? TIME_YEAR : 31536000,
        'path'     => '/',
        'samesite' => 'Lax',
      ],
      'locales_meta'        => [
        'es' => ['name' => 'Español', 'native' => 'Español', 'og_locale' => 'es_CL', 'dir' => 'ltr'],
        'en' => ['name' => 'Inglés', 'native' => 'English', 'og_locale' => 'en_US', 'dir' => 'ltr'],
      ],
      'country_to_locale'   => [
        'CL' => 'es', 'AR' => 'es', 'MX' => 'es', 'ES' => 'es', 'CO' => 'es',
        'PE' => 'es', 'VE' => 'es', 'EC' => 'es', 'UY' => 'es', 'PY' => 'es',
        'BO' => 'es', 'CR' => 'es', 'PA' => 'es', 'DO' => 'es', 'GT' => 'es',
        'HN' => 'es', 'SV' => 'es', 'NI' => 'es', 'PR' => 'es', 'CU' => 'es',
        'GQ' => 'es',
        'US' => 'en', 'GB' => 'en', 'CA' => 'en', 'AU' => 'en', 'NZ' => 'en',
        'IE' => 'en', 'ZA' => 'en', 'SG' => 'en', 'IN' => 'en', 'PH' => 'en',
      ],
    ];

    $root = defined('ROOT_PATH') ? ROOT_PATH : str_replace('\\', '/', getcwd());
    $appConfigPath = rtrim($root, '/\\') . '/App/Config/LangConfiguration.php';
    $resConfigPath = self::getFrameworkResourcesPath() . '/Config/LangConfiguration.php';

    $loaded = [];
    if (is_file($appConfigPath)) {
      $loaded = require $appConfigPath;
    } elseif (is_file($resConfigPath)) {
      $loaded = require $resConfigPath;
    }

    if (is_array($loaded)) {
      self::$config = array_replace_recursive($defaults, $loaded);
    } else {
      self::$config = $defaults;
    }

    // Si MULTILANG está desactivado, restringir siempre a [default]
    if (!self::isMultilang()) {
      self::$config['available'] = [self::defaultLocale()];
    }

    return self::$config;
  }

  /**
   * Indica si el modo multiidioma está activo (constante MULTILANG o APP_MULTILANG=true).
   *
   * @return bool
   */
  public static function isMultilang(): bool
  {
    if (defined('MULTILANG')) {
      return (bool)MULTILANG;
    }
    return filter_var($_ENV['APP_MULTILANG'] ?? false, FILTER_VALIDATE_BOOLEAN);
  }

  /**
   * Obtiene el idioma por defecto configurado.
   *
   * @return string
   */
  public static function defaultLocale(): string
  {
    if (defined('LANG_DEFAULT') && self::isValidLocaleSyntax((string)LANG_DEFAULT)) {
      return (string)LANG_DEFAULT;
    }
    $raw = trim((string)($_ENV['APP_LOCALE'] ?? 'es'));
    return self::isValidLocaleSyntax($raw) ? $raw : 'es';
  }

  /**
   * Obtiene el idioma de respaldo (fallback) para claves faltantes.
   *
   * @return string
   */
  public static function fallbackLocale(): string
  {
    if (defined('LANG_FALLBACK') && self::isValidLocaleSyntax((string)LANG_FALLBACK)) {
      return (string)LANG_FALLBACK;
    }
    $raw = trim((string)($_ENV['APP_FALLBACK_LOCALE'] ?? self::defaultLocale()));
    return self::isValidLocaleSyntax($raw) ? $raw : self::defaultLocale();
  }

  /**
   * Devuelve la lista de idiomas habilitados.
   * Si MULTILANG es false, devuelve únicamente el idioma por defecto.
   *
   * @return array<int, string>
   */
  public static function available(): array
  {
    if (!self::isMultilang()) {
      return [self::defaultLocale()];
    }

    $cfg = self::getConfig();
    $list = $cfg['available'] ?? [self::defaultLocale()];
    $valid = [];

    foreach ((array)$list as $loc) {
      $loc = trim((string)$loc);
      if (self::isValidLocaleSyntax($loc) && !in_array($loc, $valid, true)) {
        $valid[] = $loc;
      }
    }

    return !empty($valid) ? $valid : [self::defaultLocale()];
  }

  /**
   * Verifica si un código de idioma cumple la sintaxis segura y está en la whitelist de idiomas soportados.
   *
   * @param string $locale Código de idioma a verificar (ej: 'es', 'en', 'es_CL').
   * @return bool
   */
  public static function isSupported(string $locale): bool
  {
    $locale = trim($locale);
    if (!self::isValidLocaleSyntax($locale)) {
      return false;
    }
    return in_array($locale, self::available(), true);
  }

  /**
   * Fija el idioma activo validándolo contra la sintaxis segura y la whitelist.
   * Si el idioma no es válido o no está soportado, mantiene el actual o asigna el idioma por defecto.
   *
   * @param string $locale Código de idioma (ej: 'es', 'en').
   * @param bool $persist Si es true y MULTILANG está activo, persiste en cookie y sesión.
   * @return void
   */
  public static function setLocale(string $locale, bool $persist = false): void
  {
    $locale = trim($locale);

    // Si no está en la whitelist exacta pero es variante regional (ej. en_US -> en), probar lengua base
    if (!self::isSupported($locale) && str_contains($locale, '_')) {
      $short = explode('_', $locale)[0];
      if (self::isSupported($short)) {
        $locale = $short;
      }
    }

    if (self::isSupported($locale)) {
      self::$currentLocale = $locale;
      if ($persist && self::isMultilang()) {
        self::persistLocale($locale);
      }
      return;
    }

    if (self::$currentLocale === null) {
      self::$currentLocale = self::defaultLocale();
    }
  }

  /**
   * Obtiene el idioma activo actual.
   * Si aún no ha sido inicializado, devuelve el idioma por defecto.
   *
   * @return string
   */
  public static function getLocale(): string
  {
    if (!self::isMultilang()) {
      return self::defaultLocale();
    }

    if (self::$currentLocale === null) {
      self::$currentLocale = self::defaultLocale();
    }

    return self::$currentLocale;
  }

  /**
   * Obtiene el código regional OpenGraph para un idioma (ej: 'es' -> 'es_CL', 'en' -> 'en_US').
   *
   * @param string|null $locale Idioma a consultar (por defecto el idioma activo).
   * @return string
   */
  public static function getOgLocale(?string $locale = null): string
  {
    $loc = $locale ?? self::getLocale();
    $cfg = self::getConfig();
    if (!empty($cfg['locales_meta'][$loc]['og_locale'])) {
      return (string)$cfg['locales_meta'][$loc]['og_locale'];
    }
    if (str_contains($loc, '_')) {
      return $loc;
    }
    return match ($loc) {
      'es' => defined('OG_LOCALE') && str_starts_with((string)OG_LOCALE, 'es') && str_contains((string)OG_LOCALE, '_') ? (string)OG_LOCALE : 'es_CL',
      'en' => 'en_US',
      'pt' => 'pt_BR',
      'fr' => 'fr_FR',
      'de' => 'de_DE',
      'it' => 'it_IT',
      default => $loc . '_' . strtoupper($loc),
    };
  }

  /**
   * Resuelve una clave de traducción en formato 'grupo.clave.subclave' e interpola parámetros ':param'.
   *
   * @param string $key Clave con notación de puntos (ej: 'global.welcome' o 'auth.errors.password').
   * @param array<string, scalar> $params Parámetros a interpolar.
   * @param string|null $locale Idioma específico opcional (por defecto el idioma activo).
   * @param bool $escapeParams Si es true, escapa los valores interpolados con htmlspecialchars (protección XSS).
   * @return string Texto traducido o la propia clave si no existe.
   */
  public static function get(string $key, array $params = [], ?string $locale = null, bool $escapeParams = true): string
  {
    $key = trim($key);
    if ($key === '' || !str_contains($key, '.')) {
      return $key;
    }

    [$group, $itemKey] = explode('.', $key, 2);
    if (!self::isValidGroupSyntax($group) || $itemKey === '') {
      return $key;
    }

    $targetLocale = ($locale !== null && self::isValidLocaleSyntax($locale))
      ? $locale
      : self::getLocale();

    // 1. Buscar en el idioma solicitado/activo
    $value = self::resolveFromGroup($targetLocale, $group, $itemKey);

    // 2. Fallback al idioma de respaldo si no se encontró
    if ($value === null) {
      $fallback = self::fallbackLocale();
      if ($fallback !== $targetLocale) {
        $value = self::resolveFromGroup($fallback, $group, $itemKey);
      }
      // Registrar clave faltante en entorno de desarrollo
      self::logMissingKey($key, $targetLocale);
    }

    // 3. Si tampoco existe en fallback (o no es escalar), devolver la propia clave (nunca romper la vista)
    if (!is_string($value) && !is_numeric($value)) {
      return $key;
    }

    return self::interpolate((string)$value, $params, $escapeParams);
  }

  /**
   * Resuelve una traducción con reglas de pluralización e interpola ':count' y parámetros adicionales.
   *
   * Soporta formatos:
   * - Con rangos explícitos: '{0} Sin ítems|{1} Un ítem|[2,*] :count ítems' o '[2,4] Pocos'
   * - Simple de 2 partes: 'Un ítem|:count ítems'
   * - Simple de 3 partes: 'Sin ítems|Un ítem|:count ítems'
   *
   * @param string $key Clave de traducción (ej: 'global.items_count').
   * @param int|float $count Cantidad numérica para evaluar pluralización.
   * @param array<string, scalar> $params Parámetros adicionales (':count' se inyecta automáticamente).
   * @param string|null $locale Idioma opcional.
   * @param bool $escapeParams Si se escapan los parámetros interpolados.
   * @return string
   */
  public static function choice(string $key, int|float $count, array $params = [], ?string $locale = null, bool $escapeParams = true): string
  {
    if (!array_key_exists('count', $params)) {
      $params['count'] = $count;
    }

    // Obtener la plantilla cruda sin interpolar aún
    $rawLine = self::get($key, [], $locale, false);
    if ($rawLine === $key) {
      return $key;
    }

    $segments = explode('|', $rawLine);
    $selected = null;

    // Evaluar segmentos con condiciones explícitas {n} o [min,max]
    foreach ($segments as $segment) {
      $segmentTrimmed = trim($segment);
      if (preg_match('/^\{(-?\d+(?:\.\d+)?)\}\s*(.*)$/s', $segmentTrimmed, $m)) {
        if ((float)$m[1] == (float)$count) {
          $selected = $m[2];
          break;
        }
      } elseif (preg_match('/^\[(-?\d+|\*)\s*,\s*(-?\d+|\*)\]\s*(.*)$/s', $segmentTrimmed, $m)) {
        $minOk = ($m[1] === '*') || ((float)$count >= (float)$m[1]);
        $maxOk = ($m[2] === '*') || ((float)$count <= (float)$m[2]);
        if ($minOk && $maxOk) {
          $selected = $m[3];
          break;
        }
      }
    }

    // Si ningún segmento tenía condición explícita que coincidiera, usar posición estándar
    if ($selected === null) {
      $cleanSegments = array_map(function ($seg) {
        return preg_replace('/^(?:\{-?\d+(?:\.\d+)?\}|\[(?:-?\d+|\*)\s*,\s*(?:-?\d+|\*)\])\s*/s', '', trim($seg));
      }, $segments);

      if (count($cleanSegments) === 1) {
        $selected = $cleanSegments[0];
      } elseif (count($cleanSegments) === 2) {
        $selected = ((float)$count == 1.0) ? $cleanSegments[0] : $cleanSegments[1];
      } else {
        if ((float)$count == 0.0) {
          $selected = $cleanSegments[0];
        } elseif ((float)$count == 1.0) {
          $selected = $cleanSegments[1];
        } else {
          $selected = $cleanSegments[2];
        }
      }
    }

    return self::interpolate((string)$selected, $params, $escapeParams);
  }

  /**
   * Verifica si una clave existe en el idioma indicado (o activo).
   *
   * @param string $key Clave con notación 'grupo.clave'.
   * @param string|null $locale Idioma opcional.
   * @return bool
   */
  public static function has(string $key, ?string $locale = null): bool
  {
    $key = trim($key);
    if ($key === '' || !str_contains($key, '.')) {
      return false;
    }

    [$group, $itemKey] = explode('.', $key, 2);
    if (!self::isValidGroupSyntax($group) || $itemKey === '') {
      return false;
    }

    $targetLocale = ($locale !== null && self::isValidLocaleSyntax($locale))
      ? $locale
      : self::getLocale();

    return self::resolveFromGroup($targetLocale, $group, $itemKey) !== null;
  }

  /**
   * Devuelve el array completo de un grupo de traducciones (con merge entre fallback y el idioma activo).
   * Ideal para exportar un grupo como 'js' al cliente.
   *
   * @param string $group Nombre del grupo (ej: 'global', 'js', 'validation').
   * @param string|null $locale Idioma opcional (por defecto el idioma activo).
   * @return array
   */
  public static function group(string $group, ?string $locale = null): array
  {
    $group = trim($group);
    if (!self::isValidGroupSyntax($group)) {
      return [];
    }

    $targetLocale = ($locale !== null && self::isValidLocaleSyntax($locale))
      ? $locale
      : self::getLocale();

    $fallback = self::fallbackLocale();
    $activeData = self::loadGroup($targetLocale, $group);

    if ($fallback !== $targetLocale) {
      $fallbackData = self::loadGroup($fallback, $group);
      return array_replace_recursive($fallbackData, $activeData);
    }

    return $activeData;
  }

  /**
   * Genera la etiqueta <script type="application/json" id="i18n"> con las traducciones del grupo 'js'
   * listas para ser consumidas por Resources/Js/lang.js.
   *
   * @param string $group Grupo a exportar (por defecto 'js').
   * @return string Etiqueta HTML segura contra inyección de etiquetas.
   */
  public static function renderJsTranslations(string $group = 'js'): string
  {
    $translations = self::group($group);
    $payload = [
      'locale'   => self::getLocale(),
      'fallback' => self::fallbackLocale(),
      'messages' => $translations,
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    if ($json === false) {
      $json = '{"locale":"es","fallback":"es","messages":{}}';
    }

    return '<script type="application/json" id="i18n">' . $json . '</script>';
  }

  // ============================================
  // DETECCIÓN EN CASCADA Y ENRUTAMIENTO (Etapa 3 y 4)
  // ============================================

  /**
   * Ejecuta la cascada de detección de idioma una sola vez por petición:
   * 1. Parámetro ?lang= o prefijo /{locale}/ en la URL
   * 2. Cookie o Sesión de idioma
   * 3. Cabecera HTTP Accept-Language (respetando pesos q)
   * 4. País por IP usando el driver local MaxMind (GeoIpModule)
   * 5. Idioma por defecto (LANG_DEFAULT)
   *
   * Si MULTILANG es false, retorna inmediatamente LANG_DEFAULT con costo cero.
   *
   * @param bool $force Re-ejecutar detección aunque ya se haya corrido en esta petición.
   * @return string Idioma detectado y activado.
   */
  public static function detect(bool $force = false): string
  {
    if (!self::isMultilang()) {
      self::$currentLocale = self::defaultLocale();
      self::$detected = true;
      return self::$currentLocale;
    }

    if (self::$detected && !$force && self::$currentLocale !== null) {
      return self::$currentLocale;
    }

    $cfg = self::getConfig();
    $cookieName = (string)($cfg['cookie']['name'] ?? 'fme_lang');
    $resolved = null;
    $shouldPersistCookie = false;

    // 1A. Parámetro explícito ?lang=xx en la URL
    if (isset($_GET['lang']) && is_string($_GET['lang'])) {
      $candidate = trim($_GET['lang']);
      if (self::isSupported($candidate)) {
        $resolved = $candidate;
        $shouldPersistCookie = true;
      }
    }

    // 1B. Prefijo /{locale} en la URL (si url_prefix está habilitado)
    if ($resolved === null && !empty($cfg['url_prefix'])) {
      $uriLocale = self::extractLocaleFromUri($_SERVER['REQUEST_URI'] ?? '/');
      if ($uriLocale !== null && self::isSupported($uriLocale)) {
        $resolved = $uriLocale;
        $shouldPersistCookie = true;
      }
    }

    // 2. Cookie o Sesión de idioma existente
    if ($resolved === null) {
      $cookieVal = class_exists(CookieModule::class)
        ? CookieModule::get($cookieName)
        : ($_COOKIE[$cookieName] ?? null);

      if (is_string($cookieVal) && self::isSupported($cookieVal)) {
        $resolved = $cookieVal;
      } elseif (isset($_SESSION[$cookieName]) && is_string($_SESSION[$cookieName]) && self::isSupported($_SESSION[$cookieName])) {
        $resolved = $_SESSION[$cookieName];
      }
    }

    // 3. Cabecera HTTP Accept-Language (con pesos q)
    if ($resolved === null && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
      $fromHeader = self::parseAcceptLanguage((string)$_SERVER['HTTP_ACCEPT_LANGUAGE']);
      if ($fromHeader !== null) {
        $resolved = $fromHeader;
        $shouldPersistCookie = true;
      }
    }

    // 4. GeoIP local vía MaxMind (GeoIpModule) — solo en la primera visita sin cookie/header coincidente
    if ($resolved === null) {
      $fromGeo = self::detectFromGeoIp();
      if ($fromGeo !== null) {
        $resolved = $fromGeo;
      }
      $shouldPersistCookie = true;
    }

    // 5. Fallback al idioma por defecto
    if ($resolved === null || !self::isSupported($resolved)) {
      $resolved = self::defaultLocale();
      $shouldPersistCookie = true;
    }

    self::$currentLocale = $resolved;
    self::$detected = true;

    // Persistir en cookie/sesión si no existía o cambió por URL
    $currentCookie = $_COOKIE[$cookieName] ?? null;
    if ($shouldPersistCookie || $currentCookie !== $resolved) {
      self::persistLocale($resolved);
    }

    return $resolved;
  }

  /**
   * Persiste el idioma activo en cookie (vía CookieModule) y sesión si está activa.
   *
   * @param string $locale
   * @return void
   */
  public static function persistLocale(string $locale): void
  {
    if (!self::isSupported($locale)) {
      return;
    }

    $cfg = self::getConfig();
    $cookieName = (string)($cfg['cookie']['name'] ?? 'fme_lang');
    $lifetime = (int)($cfg['cookie']['lifetime'] ?? (defined('TIME_YEAR') ? TIME_YEAR : 31536000));
    $path = (string)($cfg['cookie']['path'] ?? '/');
    $samesite = (string)($cfg['cookie']['samesite'] ?? 'Lax');

    if (session_status() === PHP_SESSION_ACTIVE) {
      $_SESSION[$cookieName] = $locale;
    }

    if (php_sapi_name() !== 'cli' && !headers_sent() && class_exists(CookieModule::class)) {
      CookieModule::set($cookieName, [
        'value'    => $locale,
        'expired'  => $lifetime,
        'path'     => $path,
        'samesite' => $samesite,
        'httponly' => false,
      ]);
    } else {
      $_COOKIE[$cookieName] = $locale;
    }
  }

  /**
   * Analiza la cabecera HTTP Accept-Language de forma sanitizada respetando los pesos q.
   *
   * Ejemplo: 'es-CL,es;q=0.9,en-US;q=0.8,en;q=0.7'
   *
   * @param string $header Cabecera cruda de $_SERVER['HTTP_ACCEPT_LANGUAGE'].
   * @return string|null Primer idioma soportado por orden de preferencia, o null.
   */
  public static function parseAcceptLanguage(string $header): ?string
  {
    // Sanitizar longitud y caracteres permitidos (letras, números, guiones, comas, punto y coma, igual, punto, espacios)
    $cleanHeader = substr(preg_replace('/[^a-zA-Z0-9\-_,;=.\s]/', '', $header), 0, 256);
    if ($cleanHeader === '') {
      return null;
    }

    $parts = explode(',', $cleanHeader);
    $parsed = [];
    $order = 0;

    foreach ($parts as $part) {
      $part = trim($part);
      if ($part === '') {
        continue;
      }

      $subParts = explode(';', $part);
      $langTag = trim($subParts[0]);
      $q = 1.0;

      if (isset($subParts[1]) && preg_match('/^q\s*=\s*(0(?:\.\d{1,3})?|1(?:\.0{1,3})?)$/i', trim($subParts[1]), $m)) {
        $q = (float)$m[1];
      }

      if ($q <= 0.0) {
        continue;
      }

      // Normalizar guión a guión bajo (es-CL -> es_CL)
      $normalized = str_replace('-', '_', $langTag);
      $parsed[] = [
        'tag'   => $normalized,
        'short' => strtolower(explode('_', $normalized)[0]),
        'q'     => $q,
        'order' => $order++,
      ];
    }

    if (empty($parsed)) {
      return null;
    }

    // Ordenar por peso q descendente, manteniendo el orden de aparición original en caso de empate
    usort($parsed, function (array $a, array $b): int {
      if ($a['q'] === $b['q']) {
        return $a['order'] <=> $b['order'];
      }
      return ($a['q'] > $b['q']) ? -1 : 1;
    });

    foreach ($parsed as $item) {
      // 1. Coincidencia exacta (ej. es_CL si estuviera habilitado)
      if (self::isSupported($item['tag'])) {
        return $item['tag'];
      }
      // 2. Coincidencia por código base de 2 letras (ej. es-CL -> es)
      if (self::isSupported($item['short'])) {
        return $item['short'];
      }
    }

    return null;
  }

  /**
   * Detecta el idioma a partir del país de la IP del visitante utilizando exclusivamente
   * la base de datos local MaxMind (GeoIpModule) sin realizar peticiones HTTP externas.
   *
   * @param string|null $ip Dirección IP opcional (si es null se obtiene de la petición actual).
   * @return string|null Idioma soportado o null si es IP local o no mapeada.
   */
  public static function detectFromGeoIp(?string $ip = null): ?string
  {
    if ($ip === null) {
      $rawIp = $_SERVER['REMOTE_ADDR'] ?? '';
      if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $rawIp = $_SERVER['HTTP_CF_CONNECTING_IP'];
      } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
        $rawIp = trim($parts[0]);
      }
      $ip = filter_var($rawIp, FILTER_VALIDATE_IP) ?: '';
    }

    if ($ip === '' || self::isLocalIp($ip)) {
      return null;
    }

    if (!class_exists(GeoIpModule::class) || GeoIpModule::getDatabasePath() === null) {
      return null;
    }

    $countryCode = GeoIpModule::getCountryCode($ip);
    if (!is_string($countryCode) || $countryCode === '') {
      return null;
    }

    $countryCode = strtoupper(trim($countryCode));
    $cfg = self::getConfig();
    $map = $cfg['country_to_locale'] ?? [];

    if (isset($map[$countryCode]) && self::isSupported((string)$map[$countryCode])) {
      return (string)$map[$countryCode];
    }

    return null;
  }

  /**
   * Extrae el código de idioma si el primer segmento de la URI corresponde a un idioma soportado.
   *
   * @param string $uri URI o ruta (ej: '/en/contacto' o '/en').
   * @return string|null Código de idioma o null si no tiene prefijo de idioma válido.
   */
  public static function extractLocaleFromUri(string $uri): ?string
  {
    $path = (string)parse_url($uri, PHP_URL_PATH);
    $trimmed = trim($path, '/');
    if ($trimmed === '') {
      return null;
    }

    $segments = explode('/', $trimmed, 2);
    $first = $segments[0];

    // No confundir la ruta del selector '/lang/{locale}' ni rutas internas '/_frame/*'
    if ($first === 'lang' || str_starts_with($first, '_')) {
      return null;
    }

    if (self::isSupported($first)) {
      return $first;
    }

    return null;
  }

  /**
   * Elimina el prefijo de idioma de una URI si está presente y devuelve la URI limpia para el Router.
   * Ejemplo: '/en/contacto' -> '/contacto', '/en' -> '/'.
   *
   * @param string $uri URI original.
   * @param string|null &$extractedLocale Variable por referencia donde se deposita el idioma extraído.
   * @return string URI sin el prefijo de idioma.
   */
  public static function stripLocalePrefix(string $uri, ?string &$extractedLocale = null): string
  {
    $extractedLocale = null;
    if (!self::isMultilang()) {
      return $uri;
    }

    $cfg = self::getConfig();
    if (empty($cfg['url_prefix'])) {
      return $uri;
    }

    $path = (string)parse_url($uri, PHP_URL_PATH);
    $trimmed = trim($path, '/');
    if ($trimmed === '') {
      return '/';
    }

    $segments = explode('/', $trimmed, 2);
    $first = $segments[0];

    if ($first !== 'lang' && !str_starts_with($first, '_') && self::isSupported($first)) {
      $extractedLocale = $first;
      return isset($segments[1]) && $segments[1] !== '' ? '/' . ltrim($segments[1], '/') : '/';
    }

    return '/' . $trimmed;
  }

  /**
   * Genera una URL localizada para una ruta interna dada y un idioma específico.
   *
   * - Si MULTILANG es false: retorna la URL normal sin prefijo de idioma.
   * - Si url_prefix es true: antepone '/{locale}' (u omite el prefijo si es el idioma por defecto y hide_default_in_url es true).
   * - Si url_prefix es false: agrega '?lang={locale}' cuando se solicita un idioma distinto al actual.
   *
   * @param string $path Ruta relativa o absoluta del sitio (ej: '/contacto' o 'contacto').
   * @param string|null $locale Idioma destino (por defecto el idioma activo).
   * @param bool $absolute Si es true, antepone DOMAIN para obtener URL absoluta.
   * @return string
   */
  public static function localizedUrl(string $path = '/', ?string $locale = null, bool $absolute = false): string
  {
    $targetLocale = ($locale !== null && self::isSupported($locale)) ? $locale : self::getLocale();

    // Separar query string y fragmento si existen
    $parsedUrl = parse_url($path);
    $rawPath = $parsedUrl['path'] ?? '/';
    $query = isset($parsedUrl['query']) && $parsedUrl['query'] !== '' ? '?' . $parsedUrl['query'] : '';
    $fragment = isset($parsedUrl['fragment']) && $parsedUrl['fragment'] !== '' ? '#' . $parsedUrl['fragment'] : '';

    // Limpiar cualquier prefijo de idioma previo en el path
    $cleanPath = self::stripLocalePrefix($rawPath);
    $cleanPath = '/' . ltrim($cleanPath, '/');

    if (!self::isMultilang()) {
      $finalPath = $cleanPath;
    } else {
      $cfg = self::getConfig();
      $usePrefix = !empty($cfg['url_prefix']);
      $hideDefault = (bool)($cfg['hide_default_in_url'] ?? true);

      if ($usePrefix) {
        if ($hideDefault && $targetLocale === self::defaultLocale()) {
          $finalPath = $cleanPath;
        } else {
          $finalPath = '/' . $targetLocale . ($cleanPath === '/' ? '' : $cleanPath);
        }
      } else {
        $finalPath = $cleanPath;
        if ($locale !== null) {
          $queryParams = [];
          if (!empty($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
          }
          $queryParams['lang'] = $targetLocale;
          $query = '?' . http_build_query($queryParams);
        }
      }
    }

    if ($absolute) {
      $base = defined('DOMAIN') ? rtrim((string)DOMAIN, '/') : '';
      return $base . $finalPath . $query . $fragment;
    }

    return $finalPath . $query . $fragment;
  }

  /**
   * Procesa el cambio manual de idioma desde la ruta '/lang/:locale',
   * valida la petición contra Open Redirect y redirige a la página de origen.
   *
   * @param string $locale Idioma solicitado en la ruta.
   * @return void
   */
  public static function handleSwitchRoute(string $locale): void
  {
    if (!self::isMultilang()) {
      \Core\ErrorHandler::handle404();
      return;
    }

    $locale = trim($locale);
    if (!self::isSupported($locale)) {
      $locale = self::defaultLocale();
    }

    self::setLocale($locale, true);

    // Determinar URL de retorno segura (mismo host o ruta relativa interna)
    $targetPath = '/';
    $redirectParam = $_GET['redirect'] ?? $_GET['return'] ?? null;
    $referer = $_SERVER['HTTP_REFERER'] ?? null;

    $candidate = is_string($redirectParam) && $redirectParam !== '' ? $redirectParam : (is_string($referer) ? $referer : '');

    if ($candidate !== '') {
      // Limpiar CRLF
      $candidate = str_replace(["\r", "\n", "%0d", "%0a", "%0D", "%0A"], '', $candidate);
      $parsed = parse_url($candidate);

      $isSafeHost = true;
      if (!empty($parsed['host'])) {
        $currentHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? (defined('HOST') ? HOST : 'localhost')));
        $currentHostClean = explode(':', $currentHost)[0];
        $candidateHostClean = strtolower((string)$parsed['host']);
        $isSafeHost = ($candidateHostClean === $currentHostClean);
      }

      // Rechazar URLs relativas al protocolo (//evil.com) o esquemas peligrosos
      if ($isSafeHost && !str_starts_with($candidate, '//') && empty($parsed['scheme']) || ($isSafeHost && in_array(strtolower($parsed['scheme'] ?? ''), ['http', 'https'], true))) {
        $candPath = $parsed['path'] ?? '/';
        if (!str_starts_with($candPath, '/lang/')) {
          $targetPath = $candPath;
          if (!empty($parsed['query'])) {
            $qParams = [];
            parse_str($parsed['query'], $qParams);
            unset($qParams['lang']);
            if (!empty($qParams)) {
              $targetPath .= '?' . http_build_query($qParams);
            }
          }
        }
      }
    }

    $destination = self::localizedUrl($targetPath, $locale, false);

    if (php_sapi_name() !== 'cli' && !headers_sent()) {
      header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
      header('Location: ' . $destination, true, 302);
      exit;
    }
  }

  /**
   * Limpia el estado en memoria de LangModule (útil para pruebas automatizadas).
   *
   * @return void
   */
  public static function reset(): void
  {
    self::$currentLocale = null;
    self::$detected = false;
    self::$loadedGroups = [];
    self::$config = null;
    self::$loggedMissing = [];
  }

  // ============================================
  // MÉTODOS PRIVADOS INTERNOS
  // ============================================

  /**
   * Valida que el código de idioma tenga sintaxis estricta (ej. 'es', 'en', 'es_CL', 'pt_BR')
   * previniendo cualquier intento de Path Traversal.
   */
  public static function isValidLocaleSyntax(string $locale): bool
  {
    return (bool)preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $locale);
  }

  /**
   * Valida que el nombre de un grupo de traducción contenga solo caracteres alfanuméricos, guión o guión bajo.
   */
  public static function isValidGroupSyntax(string $group): bool
  {
    return (bool)preg_match('/^[a-zA-Z0-9_-]+$/', $group);
  }

  /**
   * Verifica si una IP es local o privada (entorno de desarrollo).
   */
  private static function isLocalIp(string $ip): bool
  {
    if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0', 'localhost'], true)) {
      return true;
    }
    return filter_var(
      $ip,
      FILTER_VALIDATE_IP,
      FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) === false;
  }

  /**
   * Carga un archivo de grupo de idioma de forma perezosa y lo almacena en caché estática.
   * Combina la base de Resources/Lang/{locale}/{group}.php con App/Lang/{locale}/{group}.php
   * prevaleciendo siempre las claves personalizadas del proyecto en App/Lang/.
   *
   * @param string $locale
   * @param string $group
   * @return array
   */
  private static function loadGroup(string $locale, string $group): array
  {
    if (!self::isValidLocaleSyntax($locale) || !self::isValidGroupSyntax($group)) {
      return [];
    }

    if (isset(self::$loadedGroups[$locale][$group])) {
      return self::$loadedGroups[$locale][$group];
    }

    $merged = [];

    // 1. Cargar diccionario base del framework (Resources/Lang/{locale}/{group}.php)
    $resFile = self::getFrameworkResourcesPath() . '/Lang/' . $locale . '/' . $group . '.php';
    if (is_file($resFile)) {
      $data = include $resFile;
      if (is_array($data)) {
        $merged = $data;
      }
    }

    // 2. Cargar diccionario del proyecto (App/Lang/{locale}/{group}.php) y sobrescribir
    $appLangDir = defined('ROUTE_LANG')
      ? rtrim((string)ROUTE_LANG, '/\\')
      : (defined('ROOT_PATH') ? rtrim((string)ROOT_PATH, '/\\') . '/App/Lang' : str_replace('\\', '/', getcwd()) . '/App/Lang');

    $appFile = $appLangDir . '/' . $locale . '/' . $group . '.php';
    if (is_file($appFile)) {
      $appData = include $appFile;
      if (is_array($appData)) {
        $merged = array_replace_recursive($merged, $appData);
      }
    }

    self::$loadedGroups[$locale][$group] = $merged;
    return $merged;
  }

  /**
   * Resuelve una clave anidada con notación de puntos dentro de un grupo.
   *
   * @param string $locale
   * @param string $group
   * @param string $itemKey
   * @return mixed
   */
  private static function resolveFromGroup(string $locale, string $group, string $itemKey): mixed
  {
    $dictionary = self::loadGroup($locale, $group);
    if (empty($dictionary)) {
      return null;
    }

    // Acceso directo si la clave completa existe como llave plana
    if (array_key_exists($itemKey, $dictionary)) {
      return $dictionary[$itemKey];
    }

    // Navegación por notación de puntos (ej: 'errors.404_title')
    $segments = explode('.', $itemKey);
    $current = $dictionary;

    foreach ($segments as $segment) {
      if (!is_array($current) || !array_key_exists($segment, $current)) {
        return null;
      }
      $current = $current[$segment];
    }

    return $current;
  }

  /**
   * Interpola parámetros ':param' dentro de la cadena de texto.
   *
   * @param string $line
   * @param array<string, scalar> $params
   * @param bool $escapeParams
   * @return string
   */
  private static function interpolate(string $line, array $params, bool $escapeParams): string
  {
    if (empty($params) || !str_contains($line, ':')) {
      return $line;
    }

    $replacements = [];
    foreach ($params as $k => $v) {
      $cleanKey = ltrim((string)$k, ':');
      $strVal = (string)$v;
      if ($escapeParams) {
        $strVal = htmlspecialchars($strVal, ENT_QUOTES, 'UTF-8');
      }
      $replacements[':' . $cleanKey] = $strVal;
    }

    // Ordenar claves de mayor a menor longitud para evitar que ':name' reemplace parcialmente a ':name_full'
    uksort($replacements, fn(string $a, string $b): int => strlen($b) <=> strlen($a));

    return strtr($line, $replacements);
  }

  /**
   * Registra en Logs/lang_missing.json las claves de traducción faltantes cuando el entorno es de desarrollo.
   */
  private static function logMissingKey(string $key, string $locale): void
  {
    $isDev = !defined('ENVIRONMENT') || in_array(strtolower((string)ENVIRONMENT), ['dev', 'development', 'local'], true);
    if (!$isDev) {
      return;
    }

    $cacheKey = $locale . ':' . $key;
    if (isset(self::$loggedMissing[$cacheKey])) {
      return;
    }
    self::$loggedMissing[$cacheKey] = true;

    if (class_exists(LogModule::class)) {
      LogModule::simpleLog([
        'dir'     => 'Logs',
        'name'    => 'lang_missing',
        'content' => [
          'time'   => date('Y-m-d H:i:s'),
          'locale' => $locale,
          'key'    => $key,
          'uri'    => $_SERVER['REQUEST_URI'] ?? 'cli',
        ],
      ]);
    }
  }

  /**
   * Resuelve la ruta absoluta al directorio Resources/ del framework.
   */
  private static function getFrameworkResourcesPath(): string
  {
    $candidates = [
      dirname(__DIR__, 2) . '/Resources',
    ];
    if (defined('FRAMEWORK_PATH')) {
      $candidates[] = rtrim((string)FRAMEWORK_PATH, '/\\') . '/Resources';
    }
    if (defined('ROOT_PATH')) {
      $candidates[] = rtrim((string)ROOT_PATH, '/\\') . '/vendor/eber/framework/Resources';
      $candidates[] = rtrim((string)ROOT_PATH, '/\\') . '/Resources';
    }

    foreach ($candidates as $path) {
      if (is_dir($path)) {
        return str_replace('\\', '/', $path);
      }
    }

    return str_replace('\\', '/', dirname(__DIR__, 2) . '/Resources');
  }
}
