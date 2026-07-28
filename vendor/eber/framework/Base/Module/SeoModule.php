<?php

namespace Base\Module;

use Core\Route;

/**
 * Módulo unificado para gestión de SEO.
 * 
 * Incluye funcionalidades para:
 * - Generación dinámica de sitemap.xml
 * - Generación dinámica de robots.txt
 * - Gestión de cabeceras X-Robots-Tag
 * 
 * @example
 * // Sitemap
 * SeoModule::sitemap($excludeRoutes, $routeOptions);
 * 
 * // Robots.txt
 * SeoModule::robots($disallow, $allow);
 * 
 * // X-Robots-Tag
 * SeoModule::noindex(['/admin', '/test']);
 * SeoModule::applyHeaderIfMatch();
 */
class SeoModule
{
  /**
   * Obtiene la etiqueta meta robots basada en el subdominio.
   * 
   * @return string Etiqueta meta robots.
   */
  /**
   * Obtiene la etiqueta meta robots basada en la configuración de subdominios bloqueados.
   * Lee la constante NO_INDEX_HOSTS definida en config.php (desde .env).
   * 
   * @return string Etiqueta meta robots.
   */
  public static function getRobotsMeta(): string
  {
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // Verificar si el host actual coincide con alguno de los bloqueados
    if (defined('NO_INDEX_HOSTS')) {
      foreach (NO_INDEX_HOSTS as $blockedHost) {
        if (!empty($blockedHost) && strpos($host, $blockedHost) !== false) {
          return '<meta name="robots" content="noindex, nofollow">';
        }
      }
    }

    // Por defecto para el dominio principal
    return '<meta name="robots" content="index, follow">';
  }

  // ============================================
  // PROPIEDADES PARA X-ROBOTS-TAG
  // ============================================

  /**
   * Almacena las rutas que deben tener noindex.
   * @var array
   */
  private static array $noindexRoutes = [];

  /**
   * Valores permitidos para X-Robots-Tag.
   * @var array
   */
  private static array $allowedDirectives = [
    'noindex',
    'nofollow',
    'noindex, nofollow',
    'noarchive',
    'nosnippet',
    'notranslate',
    'noimageindex',
    'none'
  ];

  /**
   * Almacena la descripción configurada.
   * @var string|null
   */
  private static ?string $description = null;

  /**
   * Almacena la configuración de Open Graph.
   * @var string|null
   */
  private static ?string $openGraphTags = null;

  /**
   * Almacena el título personalizado.
   * @var string|null
   */
  private static ?string $customTitle = null;

  // ============================================
  // MÉTODOS PARA TITLE Y META TAGS
  // ============================================

  /**
   * Establece un título personalizado para la página.
   * 
   * @param string $title Título personalizado.
   * @return void
   */
  public static function setTitle(string $title): void
  {
    self::$customTitle = $title;
  }

  /**
   * Obtiene el título de la página.
   * 
   * Si se estableció un título personalizado con setTitle(), lo devuelve.
   * De lo contrario, genera el título basado en la URL.
   * 
   * @param string $override Título a usar directamente (sin guardar).
   * @return string Título formateado.
   */
  public static function title(string $override = ''): string
  {
    // Si hay override directo, usarlo
    if ($override !== '') {
      return ucwords($override);
    }

    // Si hay título personalizado guardado, usarlo
    if (self::$customTitle !== null) {
      return ucwords(self::$customTitle);
    }

    // Generar desde la URL
    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    $titles = trim($uri, '/');
    $titles = explode('/', $titles);
    $title = array_pop($titles) ?: '';
    $title = explode('-', $title);
    $title = implode(' ', $title);

    // Limpiar query strings
    if (strpos($title, '?') !== false) {
      $title = substr($title, 0, strpos($title, '?'));
    }

    // Si está vacío o es la raíz, usar nombre del sitio
    if ($title === '' || $uri === '/') {
      return ucwords(defined('NAME_SITE') ? NAME_SITE : 'Sitio Web');
    }

    return ucwords($title);
  }

  /**
   * Establece una descripción personalizada para la página.
   * 
   * @param string $desc Descripción personalizada.
   * @return void
   */
  public static function setMetaDescription(string $desc): void
  {
    // Limpiar y truncar
    $content = strip_tags($desc);
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $words = explode(' ', $content);
    $content = implode(' ', array_slice($words, 0, 27));

    self::$description = '<meta name="description" content="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '">';
  }

  /**
   * Obtiene la etiqueta meta description.
   * 
   * Si se estableció una descripción con setMetaDescription(), la devuelve.
   * De lo contrario, usa la descripción por defecto de la configuración.
   * 
   * @param string|null $override Descripción a usar directamente (sin guardar).
   * @return string Etiqueta meta o string vacío.
   */
  public static function metaDescription(?string $override = null): string
  {
    // Si hay descripción personalizada guardada, usarla
    if (self::$description !== null && $override === null) {
      return self::$description;
    }

    $content = $override ?? (defined('DESCRIPTION') ? DESCRIPTION : '');

    if (empty($content)) {
      return '';
    }

    // Limpiar y truncar
    $content = strip_tags($content);
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $words = explode(' ', $content);
    $content = implode(' ', array_slice($words, 0, 27));

    $tag = '<meta name="description" content="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '">';

    // Solo guardar si viene del default
    if ($override === null) {
      self::$description = $tag;
    }

    return $tag;
  }

  /**
   * Establece las etiquetas Open Graph personalizadas.
   * 
   * @param array $config Configuración:
   *   - title: string - Título del contenido
   *   - content: string - Descripción
   *   - image: string - URL de la imagen
  /**
   * Convierte una URL relativa o mal construida en una URL absoluta limpia.
   * 
   * @param string $url URL relativa o absoluta.
   * @return string URL absoluta sin barras dobles redundantes.
   */
  public static function makeAbsoluteUrl(string $url): string
  {
    if (empty($url)) {
      return '';
    }

    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
      return preg_replace('#(?<!:)//+#', '/', $url);
    }

    $domain = defined('DOMAIN') ? DOMAIN : (defined('URL') ? URL : '');
    if (empty($domain) && isset($_SERVER['HTTP_HOST'])) {
      $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
      $domain = $scheme . '://' . $_SERVER['HTTP_HOST'];
    }

    $full = rtrim($domain, '/') . '/' . ltrim($url, '/');
    return preg_replace('#(?<!:)//+#', '/', $full);
  }

  /**
   * Establece las etiquetas Open Graph personalizadas.
   * 
   * @param array $config Configuración:
   *   - title: string - Título del contenido
   *   - site_name: string - Nombre del sitio/marca (ej: "Cuaderno")
   *   - content: string - Descripción
   *   - image: string - URL de la imagen
   *   - image_width: int|string - Ancho de la imagen (default: 400)
   *   - image_height: int|string - Alto de la imagen (default: 400)
   *   - link: string - URL del contenido
   *   - type: string - Tipo (article, website). Default: website
   * @return void
   */
  public static function setOpenGraph(array $config): void
  {
    $logo = defined('LOGO') ? LOGO : '';
    $linkOg = defined('LINK_OG') ? LINK_OG : (defined('DOMAIN') ? DOMAIN : '');
    $imgOg = defined('IMG_OG') ? IMG_OG : '';
    $nameSite = defined('NAME_SITE') && !empty(NAME_SITE) ? NAME_SITE : 'Cuaderno';
    $description = defined('DESCRIPTION') ? DESCRIPTION : '';

    $siteNameRaw = $config['site_name'] ?? $config['siteName'] ?? $nameSite;
    $siteName = htmlspecialchars(strip_tags($siteNameRaw), ENT_QUOTES, 'UTF-8');

    $localeRaw = defined('OG_LOCALE') && !empty(OG_LOCALE) ? OG_LOCALE : 'es_CL';
    $locale = htmlspecialchars(strip_tags($config['locale'] ?? $localeRaw), ENT_QUOTES, 'UTF-8');

    $title = htmlspecialchars(strip_tags($config['title'] ?? $nameSite), ENT_QUOTES, 'UTF-8');
    $contentRaw = $config['content'] ?? $config['description'] ?? $description;
    $content = htmlspecialchars(strip_tags($contentRaw), ENT_QUOTES, 'UTF-8');
    
    $rawImage = $config['image'] ?? $imgOg;
    $image = self::makeAbsoluteUrl($rawImage);

    $imageWidth = $config['image_width'] ?? $config['img_width'] ?? (is_array($config['image_size'] ?? null) ? $config['image_size'][0] : ($config['image_size'] ?? '400'));
    $imageHeight = $config['image_height'] ?? $config['img_height'] ?? (is_array($config['image_size'] ?? null) ? ($config['image_size'][1] ?? '400') : '400');

    $rawLink = $config['link'] ?? $config['url'] ?? $linkOg;
    $link = self::makeAbsoluteUrl($rawLink);

    $type = $config['type'] ?? 'website';
    $domainHost = !empty($link) ? parse_url($link, PHP_URL_HOST) : (defined('DOMAIN') ? parse_url(DOMAIN, PHP_URL_HOST) : '');

    self::$openGraphTags = '<meta property="og:site_name" content="' . $siteName . '" />
        <meta property="og:locale" content="' . $locale . '" />
        ' . (!empty($logo) ? '<meta property="og:logo" content="' . self::makeAbsoluteUrl($logo) . '" />' : '') . '
        <meta property="og:title" content="' . $title . '" />
        <meta property="og:description" content="' . $content . '" />
        ' . (!empty($image) ? '<meta property="og:image" content="' . $image . '" />' : '') . '
        ' . (!empty($image) ? '<meta property="og:image:width" content="' . $imageWidth . '" />' : '') . '
        ' . (!empty($image) ? '<meta property="og:image:height" content="' . $imageHeight . '" />' : '') . '
        <meta property="og:url" content="' . $link . '" />
        <meta property="og:type" content="' . $type . '" />
        
        <meta name="twitter:card" content="summary_large_image" />
        ' . (!empty($domainHost) ? '<meta name="twitter:domain" content="' . $domainHost . '" />' : '') . '
        <meta name="twitter:title" content="' . $title . '" />
        <meta name="twitter:description" content="' . $content . '" />
        ' . (!empty($image) ? '<meta name="twitter:image" content="' . $image . '" />' : '') . '
        <meta name="twitter:url" content="' . $link . '" />';
  }

  /**
   * Obtiene las etiquetas Open Graph y Twitter Cards.
   * 
   * Si se establecieron con setOpenGraph(), devuelve esas.
   * De lo contrario, genera con valores por defecto.
   * 
   * @param array|null $override Configuración para uso directo (sin guardar).
   * @return string Etiquetas meta generadas.
   */
  public static function openGraph(?array $override = null): string
  {
    // Si hay tags guardadas y no hay override, usarlas
    if (self::$openGraphTags !== null && $override === null) {
      return self::$openGraphTags;
    }

    $logo = defined('LOGO') ? LOGO : '';
    $linkOg = defined('LINK_OG') ? LINK_OG : (defined('DOMAIN') ? DOMAIN : '');
    $imgOg = defined('IMG_OG') ? IMG_OG : '';
    $nameSite = defined('NAME_SITE') && !empty(NAME_SITE) ? NAME_SITE : 'Cuaderno';
    $description = defined('DESCRIPTION') ? DESCRIPTION : '';

    if ($override !== null) {
      $siteNameRaw = $override['site_name'] ?? $override['siteName'] ?? $nameSite;
      $localeRaw = $override['locale'] ?? (defined('OG_LOCALE') && !empty(OG_LOCALE) ? OG_LOCALE : 'es_CL');
      $title = htmlspecialchars(strip_tags($override['title'] ?? $nameSite), ENT_QUOTES, 'UTF-8');
      $content = htmlspecialchars(strip_tags($override['content'] ?? $override['description'] ?? $description), ENT_QUOTES, 'UTF-8');
      $rawImage = $override['image'] ?? $imgOg;
      $imageWidth = $override['image_width'] ?? $override['img_width'] ?? (is_array($override['image_size'] ?? null) ? $override['image_size'][0] : ($override['image_size'] ?? '400'));
      $imageHeight = $override['image_height'] ?? $override['img_height'] ?? (is_array($override['image_size'] ?? null) ? ($override['image_size'][1] ?? '400') : '400');
      $rawLink = $override['link'] ?? $override['url'] ?? $linkOg;
      $type = $override['type'] ?? 'website';
    } else {
      $siteNameRaw = $nameSite;
      $localeRaw = defined('OG_LOCALE') && !empty(OG_LOCALE) ? OG_LOCALE : 'es_CL';
      $title = $nameSite;
      $content = htmlspecialchars(strip_tags($description), ENT_QUOTES, 'UTF-8');
      $rawImage = $imgOg;
      $imageWidth = '400';
      $imageHeight = '400';
      $rawLink = $linkOg;
      $type = 'website';
    }

    $siteName = htmlspecialchars(strip_tags($siteNameRaw), ENT_QUOTES, 'UTF-8');
    $locale = htmlspecialchars(strip_tags($localeRaw), ENT_QUOTES, 'UTF-8');
    $image = self::makeAbsoluteUrl($rawImage);
    $link = self::makeAbsoluteUrl($rawLink);
    $domainHost = !empty($link) ? parse_url($link, PHP_URL_HOST) : (defined('DOMAIN') ? parse_url(DOMAIN, PHP_URL_HOST) : '');

    $tags = '<meta property="og:site_name" content="' . $siteName . '" />
        <meta property="og:locale" content="' . $locale . '" />
        ' . (!empty($logo) ? '<meta property="og:logo" content="' . self::makeAbsoluteUrl($logo) . '" />' : '') . '
        <meta property="og:title" content="' . $title . '" />
        <meta property="og:description" content="' . $content . '" />
        ' . (!empty($image) ? '<meta property="og:image" content="' . $image . '" />' : '') . '
        ' . (!empty($image) ? '<meta property="og:image:width" content="' . $imageWidth . '" />' : '') . '
        ' . (!empty($image) ? '<meta property="og:image:height" content="' . $imageHeight . '" />' : '') . '
        <meta property="og:url" content="' . $link . '" />
        <meta property="og:type" content="' . $type . '" />
        
        <meta name="twitter:card" content="summary_large_image" />
        ' . (!empty($domainHost) ? '<meta name="twitter:domain" content="' . $domainHost . '" />' : '') . '
        <meta name="twitter:title" content="' . $title . '" />
        <meta name="twitter:description" content="' . $content . '" />
        ' . (!empty($image) ? '<meta name="twitter:image" content="' . $image . '" />' : '') . '
        <meta name="twitter:url" content="' . $link . '" />';

    // Solo guardar si es default
    if ($override === null) {
      self::$openGraphTags = $tags;
    }

    return $tags;
  }

  /**
   * Obtiene las etiquetas Open Graph configuradas.
   * 
   * @return string|null Etiquetas o null si no están configuradas.
   */
  public static function getOpenGraphTags(): ?string
  {
    return self::$openGraphTags;
  }

  /**
   * Verifica si hay descripción configurada.
   * 
   * @return bool True si hay descripción.
   */
  public static function hasDescription(): bool
  {
    return self::$description !== null;
  }

  // ============================================
  // MÉTODOS PARA SITEMAP.XML
  // ============================================

  /**
   * Genera el sitemap.xml automáticamente.
   *
   * @param array $excludeRoutes Rutas a excluir del sitemap.
   * @param array $routeOptions Opciones SEO específicas por ruta (priority, changefreq, lastmod).
   *                            Ej: ['/' => ['priority' => 1.0, 'changefreq' => 'daily']]
   * @return string El contenido XML del sitemap (impreso).
   */
  public static function sitemap(array $excludeRoutes = [], array $routeOptions = []): string
  {
    $routes = Route::getRoutes();
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    $baseUrl = rtrim(defined('URL') ? URL : 'http://localhost', '/');

    // Normalizar rutas a excluir
    $excludeRoutes = array_map(function ($route) {
      return '/' . ltrim($route, '/');
    }, $excludeRoutes);

    // Normalizar claves de routeOptions
    $normalizedRouteOptions = [];
    foreach ($routeOptions as $key => $value) {
      $normalizedRouteOptions['/' . ltrim($key, '/')] = $value;
    }

    $processedRoutes = [];

    // Procesar rutas registradas
    foreach ($routes as $route) {
      if ($route['method'] !== 'GET') {
        continue;
      }

      $uri = $route['uri'];

      // Omitir rutas dinámicas
      if (strpos($uri, ':') !== false) {
        continue;
      }

      $uri = '/' . ltrim($uri, '/');

      // Verificar exclusiones
      $isExcluded = false;
      foreach ($excludeRoutes as $excluded) {
        if ($uri === $excluded || (str_ends_with($excluded, '*') && str_starts_with($uri, rtrim($excluded, '*')))) {
          $isExcluded = true;
          break;
        }
      }

      if ($isExcluded) {
        continue;
      }

      $processedRoutes[$uri] = true;

      $url = $baseUrl . $uri;
      $xml .= '<url>';
      $xml .= '<loc>' . htmlspecialchars($url, ENT_XML1, 'UTF-8') . '</loc>';

      if (isset($normalizedRouteOptions[$uri])) {
        $options = $normalizedRouteOptions[$uri];

        if (isset($options['lastmod'])) {
          $xml .= '<lastmod>' . htmlspecialchars($options['lastmod'], ENT_XML1, 'UTF-8') . '</lastmod>';
        }
        if (isset($options['changefreq'])) {
          $xml .= '<changefreq>' . htmlspecialchars($options['changefreq'], ENT_XML1, 'UTF-8') . '</changefreq>';
        }
        if (isset($options['priority'])) {
          $xml .= '<priority>' . htmlspecialchars((string) $options['priority'], ENT_XML1, 'UTF-8') . '</priority>';
        }
      }

      $xml .= '</url>';
    }

    // Procesar rutas adicionales de routeOptions
    foreach ($normalizedRouteOptions as $uri => $options) {
      if (isset($processedRoutes[$uri])) {
        continue;
      }

      $isExcluded = false;
      foreach ($excludeRoutes as $excluded) {
        if ($uri === $excluded || (str_ends_with($excluded, '*') && str_starts_with($uri, rtrim($excluded, '*')))) {
          $isExcluded = true;
          break;
        }
      }

      if ($isExcluded) {
        continue;
      }

      $url = $baseUrl . $uri;
      $xml .= '<url>';
      $xml .= '<loc>' . htmlspecialchars($url, ENT_XML1, 'UTF-8') . '</loc>';

      if (isset($options['lastmod'])) {
        $xml .= '<lastmod>' . htmlspecialchars($options['lastmod'], ENT_XML1, 'UTF-8') . '</lastmod>';
      }
      if (isset($options['changefreq'])) {
        $xml .= '<changefreq>' . htmlspecialchars($options['changefreq'], ENT_XML1, 'UTF-8') . '</changefreq>';
      }
      if (isset($options['priority'])) {
        $xml .= '<priority>' . htmlspecialchars((string) $options['priority'], ENT_XML1, 'UTF-8') . '</priority>';
      }

      $xml .= '</url>';
    }

    $xml .= '</urlset>';
    header('Content-Type: application/xml');
    return print $xml;
  }

  // ============================================
  // MÉTODOS PARA ROBOTS.TXT
  // ============================================

  /**
   * Genera el contenido de robots.txt dinámicamente.
   *
   * @param array $disallow Array de rutas a bloquear.
   * @param array $allow Array de rutas a permitir explícitamente.
   * @param string $userAgent User-agent a configurar. Por defecto '*'.
   * @param bool $includeSitemap Si se debe incluir la referencia al sitemap.xml.
   * @return void
   */
  public static function robots(
    array $disallow = [],
    array $allow = [],
    string $userAgent = '*',
    bool $includeSitemap = true
  ): void {
    header('Content-Type: text/plain; charset=UTF-8');

    $baseUrl = rtrim(DOMAIN, '/');
    $content = "User-agent: {$userAgent}\n";

    foreach ($allow as $path) {
      $path = '/' . ltrim($path, '/');
      $content .= "Allow: {$path}\n";
    }

    foreach ($disallow as $path) {
      $path = '/' . ltrim($path, '/');
      $content .= "Disallow: {$path}\n";
    }

    $content .= "\n";

    if ($includeSitemap) {
      $content .= "Sitemap: " . $baseUrl . "/sitemap.xml\n";
    }

    echo $content;
    exit;
  }

  /**
   * Genera robots.txt con múltiples configuraciones de user-agent.
   *
   * @param array $rules Array de reglas por user-agent.
   * @param bool $includeSitemap Si se debe incluir la referencia al sitemap.xml.
   * @return void
   */
  public static function robotsAdvanced(
    array $rules,
    bool $includeSitemap = true
  ): void {
    header('Content-Type: text/plain; charset=UTF-8');

    $baseUrl = rtrim(DOMAIN, '/');
    $content = '';

    foreach ($rules as $userAgent => $config) {
      $content .= "User-agent: {$userAgent}\n";

      if (isset($config['allow']) && is_array($config['allow'])) {
        foreach ($config['allow'] as $path) {
          $path = '/' . ltrim($path, '/');
          $content .= "Allow: {$path}\n";
        }
      }

      if (isset($config['disallow']) && is_array($config['disallow'])) {
        foreach ($config['disallow'] as $path) {
          $path = '/' . ltrim($path, '/');
          $content .= "Disallow: {$path}\n";
        }
      }

      $content .= "\n";
    }

    if ($includeSitemap) {
      $content .= "Sitemap: {$baseUrl}/sitemap.xml\n";
    }

    echo $content;
    exit;
  }

  // ============================================
  // MÉTODOS PARA X-ROBOTS-TAG
  // ============================================

  /**
   * Registra rutas que deben tener la cabecera X-Robots-Tag: noindex.
   *
   * @param array $routes Array de rutas a marcar como noindex.
   * @return void
   */
  public static function noindex(array $routes): void
  {
    foreach ($routes as $route) {
      $normalizedRoute = '/' . ltrim($route, '/');
      self::$noindexRoutes[$normalizedRoute] = 'noindex';
    }
  }

  /**
   * Registra rutas con una directiva personalizada de X-Robots-Tag.
   *
   * @param array $routes Array asociativo [ruta => directiva].
   * @return void
   */
  public static function customTag(array $routes): void
  {
    foreach ($routes as $route => $directive) {
      if (!in_array($directive, self::$allowedDirectives, true)) {
        continue;
      }
      $normalizedRoute = '/' . ltrim($route, '/');
      self::$noindexRoutes[$normalizedRoute] = $directive;
    }
  }

  /**
   * Obtiene la directiva X-Robots-Tag para una ruta específica.
   *
   * @param string $uri La URI a verificar.
   * @return string|null La directiva si coincide, null si no.
   */
  public static function getDirective(string $uri): ?string
  {
    $uri = '/' . ltrim(trim($uri, '/'), '/');
    if ($uri === '') {
      $uri = '/';
    }

    foreach (self::$noindexRoutes as $pattern => $directive) {
      if (str_ends_with($pattern, '*')) {
        $prefix = rtrim($pattern, '*');
        if (str_starts_with($uri, $prefix)) {
          return $directive;
        }
      } else {
        if ($uri === $pattern) {
          return $directive;
        }
      }
    }

    return null;
  }

  /**
   * Verifica si la URI actual coincide con alguna ruta registrada.
   *
   * @param string|null $uri Opcional. La URI a verificar.
   * @return bool True si la URI coincide con alguna ruta noindex.
   */
  public static function shouldApplyTag(?string $uri = null): bool
  {
    if ($uri === null) {
      $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    }

    return self::getDirective($uri) !== null;
  }

  /**
   * Aplica la cabecera X-Robots-Tag si la URI actual coincide.
   *
   * @param string|null $uri Opcional. La URI a verificar.
   * @return bool True si se aplicó la cabecera, false si no.
   */
  public static function applyHeaderIfMatch(?string $uri = null): bool
  {
    if ($uri === null) {
      $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    }

    $directive = self::getDirective($uri);

    if ($directive !== null) {
      if (!headers_sent()) {
        header("X-Robots-Tag: {$directive}");
        return true;
      }
    }

    return false;
  }

  /**
   * Aplica la cabecera X-Robots-Tag directamente.
   *
   * @param string $directive La directiva a aplicar. Default: 'noindex'.
   * @return bool True si se aplicó la cabecera.
   */
  public static function applyTag(string $directive = 'noindex'): bool
  {
    if (!in_array($directive, self::$allowedDirectives, true)) {
      $directive = 'noindex';
    }

    if (!headers_sent()) {
      header("X-Robots-Tag: {$directive}");
      return true;
    }

    return false;
  }

  /**
   * Obtiene todas las rutas registradas con sus directivas.
   *
   * @return array Array asociativo [ruta => directiva].
   */
  public static function getRegisteredRoutes(): array
  {
    return self::$noindexRoutes;
  }

  /**
   * Limpia todas las rutas registradas para X-Robots-Tag.
   *
   * @return void
   */
  public static function resetTags(): void
  {
    self::$noindexRoutes = [];
  }

  /**
   * Elimina una ruta específica del registro de tags.
   *
   * @param string $route La ruta a eliminar.
   * @return bool True si se eliminó.
   */
  public static function removeTag(string $route): bool
  {
    $normalizedRoute = '/' . ltrim($route, '/');

    if (isset(self::$noindexRoutes[$normalizedRoute])) {
      unset(self::$noindexRoutes[$normalizedRoute]);
      return true;
    }

    return false;
  }

  // ============================================
  // MÉTODOS PARA LLMS.TXT (AI / LLM OPTIMIZATION)
  // ============================================

  /**
   * Genera el archivo llms.txt en formato Markdown a partir de un arreglo estructurado.
   * Sigue la especificación estándar propuesta para LLMs (AnswerDotAI).
   *
   * @param array $config Arreglo estructurado con las siguientes claves:
   *   - title: string - Título principal del sitio/proyecto (# Título)
   *   - summary: string - Resumen corto (> Resumen)
   *   - details: string (opcional) - Párrafo descriptivo o detalles adicionales
   *   - sections: array (opcional) - Secciones con listas de enlaces:
   *               ['Nombre Sección' => [ ['title' => '', 'url' => '', 'description' => ''] ]]
   *   - full_file: string (opcional) - Ruta o URL hacia el archivo completo llms-full.txt
   * @return void
   */
  public static function llms(array $config): void
  {
    header('Content-Type: text/markdown; charset=UTF-8');

    $baseUrl = rtrim(defined('DOMAIN') ? DOMAIN : '', '/');
    $title = $config['title'] ?? (defined('NAME_SITE') ? NAME_SITE : 'Proyecto');
    $summary = $config['summary'] ?? (defined('DESCRIPTION') ? DESCRIPTION : '');
    $details = $config['details'] ?? '';
    $sections = $config['sections'] ?? [];
    $fullFile = $config['full_file'] ?? null;

    $md = "# {$title}\n\n";

    if (!empty($summary)) {
      $cleanSummary = trim(preg_replace('/\s+/', ' ', strip_tags($summary)));
      $md .= "> {$cleanSummary}\n\n";
    }

    if (!empty($details)) {
      $cleanDetails = trim(strip_tags($details));
      $md .= "{$cleanDetails}\n\n";
    }

    if (!empty($sections) && is_array($sections)) {
      foreach ($sections as $sectionName => $links) {
        $md .= "## {$sectionName}\n\n";
        if (is_array($links)) {
          foreach ($links as $link) {
            $linkTitle = $link['title'] ?? 'Enlace';
            $rawUrl = $link['url'] ?? '/';
            $linkUrl = (str_starts_with($rawUrl, 'http://') || str_starts_with($rawUrl, 'https://'))
              ? $rawUrl
              : $baseUrl . '/' . ltrim($rawUrl, '/');
            $linkDesc = isset($link['description']) && !empty($link['description'])
              ? ': ' . trim($link['description'])
              : '';

            $md .= "- [{$linkTitle}]({$linkUrl}){$linkDesc}\n";
          }
        }
        $md .= "\n";
      }
    }

    if (!empty($fullFile)) {
      $fullFileUrl = (str_starts_with($fullFile, 'http://') || str_starts_with($fullFile, 'https://'))
        ? $fullFile
        : $baseUrl . '/' . ltrim($fullFile, '/');
      $md .= "## Optional\n\n";
      $md .= "- [Full Documentation]({$fullFileUrl}): Archivo de documentación completa para LLMs.\n";
    }

    echo trim($md);
    exit;
  }

  /**
   * Sirve directamente una cadena de texto Markdown cruda para el archivo llms.txt.
   *
   * @param string $markdownContent Contenido en formato Markdown.
   * @return void
   */
  public static function llmsRaw(string $markdownContent): void
  {
    header('Content-Type: text/markdown; charset=UTF-8');
    echo trim($markdownContent);
    exit;
  }
}
