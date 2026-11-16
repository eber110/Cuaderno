<?php

namespace Base\Control;

use Core\ErrorHandler;
use Core\ConfigLoader\LoadViewStyle;
use Base\Module\SeoModule;
use Base\Module\TextModule;
use Base\Module\DateTimeModule;
use Base\Module\CacheModule;
use Base\Module\EventModule;
use Base\Module\ResponseModule;
use Base\Module\CookieModule;
use Base\Module\ViewOptimizerModule;

/**
 * Controlador base de la aplicación.
 * 
 * Proporciona funcionalidades core para:
 * - Renderizado de vistas
 * - Navegación y menús
 * - Manejo de errores
 * - Subida de archivos
 */
class Control
{
  protected $menu = array();
  protected $description;
  protected $share_conf;
  protected ?bool $isInlineCss = null;
  protected int $viewCacheTtl = 0;

  public function __construct()
  {
    ViewOptimizerModule::init();
  }

  /**
   * Habilita el cacheo de la vista completa que se renderizará a continuación en Cache/Views,
   * incluyendo automáticamente el idioma activo (locale) en la clave de caché.
   *
   * @param int $ttl Segundos de vida del caché (ej: 3600).
   * @return static
   */
  public function cacheView(int $ttl = 3600): static
  {
    $this->viewCacheTtl = max(0, $ttl);
    return $this;
  }

  /**
   * Configura si el CSS minificado debe inyectarse directamente en el <head> (inline <style>)
   * para la vista que se renderizará a continuación.
   * Permite sintaxis fluida: $this->inlineCss()->view('ruta', $data);
   *
   * @param bool $status True para forzar inline, false para forzar <link>
   * @return static
   */
  public function inlineCss(bool $status = true): static
  {
    $this->isInlineCss = $status;
    LoadViewStyle::setInlineCss($status);
    return $this;
  }

  // ============================================
  // MÉTODOS DE RENDERIZADO DE VISTAS
  // ============================================

  /**
   * Renderiza una vista con datos y layout opcional.
   * Optimizado para SEO y TTFB usando ResponseModule::sendContent.
   * 
   * @param string $route Ruta de la vista (sin extensión, ej: "home.index" o "Home/index").
   * @param array $data Datos a pasar a la vista.
   * @param string|null $layout Nombre del layout opcional dentro de App/Views (ej: "layouts.app" o "layouts/admin").
   * @return void
   */
  public function view($route, $data = [], ?string $layout = null)
  {
    $useViewCache = $this->viewCacheTtl > 0 && (!defined('USE_CACHE') || USE_CACHE);
    $cacheFile = null;

    if ($useViewCache) {
      $activeLoc = function_exists('locale') ? locale() : (defined('LANG_DEFAULT') ? LANG_DEFAULT : 'es');
      $reqUri = $_SERVER['REQUEST_URI'] ?? '/';
      $themeName = function_exists('theme_name') ? theme_name() : 'default';
      $inlineFlag = $this->isInlineCss !== null ? ($this->isInlineCss ? '1' : '0') : (LoadViewStyle::isInlineCss() ? '1' : '0');
      $cacheHash = md5($activeLoc . '|' . $route . '|' . ($layout ?? '') . '|' . $themeName . '|' . $inlineFlag . '|' . $reqUri);
      $cacheDir = (defined('ROOT_PATH') ? ROOT_PATH : getcwd()) . '/Cache/Views/';
      if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
      }
      $cacheFile = $cacheDir . $activeLoc . '_' . $cacheHash . '.html';

      if (is_file($cacheFile) && (time() - (int)filemtime($cacheFile)) < $this->viewCacheTtl) {
        $cachedHtml = @file_get_contents($cacheFile);
        if ($cachedHtml !== false && $cachedHtml !== '') {
          $this->viewCacheTtl = 0;
          if ($this->isInlineCss !== null) {
            LoadViewStyle::setInlineCss(null);
          }
          ResponseModule::sendContent($cachedHtml);
          return;
        }
      }
    }

    \ViewData::set($data);
    $datas = $this->viewPart($route, ['data' => $data], $layout);

    // Agregar script de lazy loading si está habilitado
    $datas .= ViewOptimizerModule::getLazyLoadScript();

    if ($useViewCache && $cacheFile !== null && is_string($datas) && $datas !== '') {
      @file_put_contents($cacheFile, $datas, LOCK_EX);
    }
    $this->viewCacheTtl = 0;

    // Restablecer estado local de inlineCss
    if ($this->isInlineCss !== null) {
      LoadViewStyle::setInlineCss(null);
    }

    // Enviar contenido inmediatamente y cerrar conexión (SEO Boost)
    ResponseModule::sendContent($datas);
  }

  /**
   * Renderiza una vista limpia sin HTML/head/estilos.
   * Ideal para componentes dinámicos, fragmentos modales o peticiones HTMX/Fetch/AJAX.
   * 
   * @param string $route Ruta de la vista (sin extensión, con puntos o barras).
   * @param array $data Datos a pasar a la vista.
   * @return string Contenido renderizado.
   */
  /**
   * Incluye un archivo de vista de forma aislada evitando sobrescritura de rutas vía extract().
   */
  private static function requireIsolatedView(string $templatePath, array $viewData = [], ?string $content = null): void
  {
    if (!empty($viewData)) {
      extract($viewData, EXTR_SKIP);
    }
    require func_get_arg(0);
  }

  public function viewClean(string $route, array $data = []): string
  {
    \ViewData::set($data);
    $route = str_replace(['..', '\\', "\0"], '', $route);
    $route = str_replace('.', '/', $route);
    $route = ltrim($route, '/');
    $filePath = ROUTE_VIEW . $route . '.php';

    if (!file_exists($filePath)) {
      ErrorHandler::handleCode(404, 404, function_exists('__') ? __('global.errors.404_view') : 'No existe la vista especificada.');
      return '';
    }

    ob_start();
    try {
      self::requireIsolatedView($filePath, $data);
      return (string)ob_get_clean();
    } catch (\Exception $e) {
      ob_end_clean();
      return "Error: " . $e->getMessage();
    }
  }

  /**
   * Extrae y renderiza una parte de vista con soporte opcional de layout.
   * 
   * @param string $route Ruta de la vista.
   * @param array $data Datos para la vista.
   * @param string|null $layout Layout opcional.
   * @return string|void Contenido renderizado.
   */
  private function viewPart($route, $data = [], ?string $layout = null)
  {
    $route = str_replace(['..', '\\', "\0"], '', (string)$route);
    $route = str_replace('.', '/', $route);
    $route = ltrim($route, '/');
    $filePath = ROUTE_VIEW . $route . '.php';

    if (file_exists($filePath)) {
      ob_start();

      try {
        $viewData = (isset($data['data']) && is_array($data['data'])) ? $data['data'] : [];

        if ($layout !== null) {
          $cleanLayout = str_replace(['..', '\\', "\0"], '', ltrim($layout, '/'));
          $layoutPath = ROUTE_VIEW . str_replace('.', '/', $cleanLayout) . '.php';
          if (file_exists($layoutPath)) {
            // Renderizar la vista interna en la variable $content
            ob_start();
            self::requireIsolatedView($filePath, $viewData);
            $content = ob_get_clean();

            $activeTheme = function_exists('theme_name') ? theme_name() : 'default';
            $this->confView();
            print '<body class="theme-' . htmlspecialchars($activeTheme, ENT_QUOTES, 'UTF-8') . '" data-theme-name="' . htmlspecialchars($activeTheme, ENT_QUOTES, 'UTF-8') . '">';
            self::requireIsolatedView($layoutPath, $viewData, $content);
            print '</body>';
            print '</html>';
            return ob_get_clean();
          }
        }

        $activeTheme = function_exists('theme_name') ? theme_name() : 'default';
        $this->confView();
        print '<body class="theme-' . htmlspecialchars($activeTheme, ENT_QUOTES, 'UTF-8') . '" data-theme-name="' . htmlspecialchars($activeTheme, ENT_QUOTES, 'UTF-8') . '">';
        self::requireIsolatedView($filePath, $viewData);
        print '</body>';
        print '</html>';
        return ob_get_clean();
      } catch (\Exception $e) {
        ob_end_clean();
        print "Error: " . $e->getMessage();
      }
    } else {
      ErrorHandler::handleCode(404, 404, function_exists('__') ? __('global.errors.404_view') : 'No existe la vista especificada. Verifica que has escrito correctamente el nombre de la vista!!');
      return;
    }
  }

  /**
   * Configura la vista HTML con head y estilos.
   * 
   * @return void
   */
  public function confView()
  {
    $activeTheme = function_exists('theme_name') ? theme_name() : 'default';
    if (!defined('THEME_NAME')) {
      define('THEME_NAME', $activeTheme);
    }
    $htmlLang = function_exists('locale') ? locale() : (defined('LANG_DEFAULT') ? LANG_DEFAULT : 'es');
    echo '<!DOCTYPE html>';
    echo '<html lang="' . htmlspecialchars((string)$htmlLang, ENT_QUOTES, 'UTF-8') . '" data-theme-name="' . htmlspecialchars($activeTheme, ENT_QUOTES, 'UTF-8') . '">';
    echo '<head>';
    // Metadatos principales y SEO
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . htmlspecialchars(SeoModule::title(), ENT_QUOTES, 'UTF-8') . '</title>';
    if (class_exists('\Base\Module\SecurityModule')) {
      echo \Base\Module\SecurityModule::csrfMeta();
    }
    echo SeoModule::metaDescription();
    
    // Canónico dinámico libre de parámetros query y escapado contra XSS
    $currentUri = $_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'] ?? '/';
    $currentUri = str_replace(["\r", "\n", "\0"], '', (string)$currentUri);
    if (($qPos = strpos($currentUri, '?')) !== false) {
        $currentUri = substr($currentUri, 0, $qPos);
    }
    $canonicalUrl = rtrim(DOMAIN, '/') . '/' . ltrim($currentUri, '/');
    $canonicalUrl = preg_replace('#(?<!:)//+#', '/', $canonicalUrl);
    echo '<link rel="canonical" href="' . htmlspecialchars((string)$canonicalUrl, ENT_QUOTES, 'UTF-8') . '">';
    if (method_exists(SeoModule::class, 'hreflang')) {
      echo SeoModule::hreflang($currentUri);
    }
    
    echo SeoModule::getRobotsMeta();
    echo SeoModule::openGraph();

    // JSON-LD estructurado de Persona (opcional, solo si está configurado)
    echo SeoModule::personSchema();

    // Diccionario de traducciones JS seguro (<script type="application/json" id="i18n">)
    if (class_exists('\Base\Module\LangModule')) {
      echo \Base\Module\LangModule::renderJsTranslations('js');
    }

    echo ViewOptimizerModule::getPreloads();

    if ($this->isInlineCss !== null) {
      LoadViewStyle::setInlineCss($this->isInlineCss);
    }

    require_once FRAMEWORK_PATH . 'Core/ConfigLoader/LoadStyle.php';

    // Script inline sincrónico: aplica el tema guardado o valor por defecto antes del primer paint (evita FOUC)
    $defaultTheme = (string)($_ENV['DEFAULT_THEME'] ?? 'system');
    $mediaQueries = class_exists('\\Base\\Module\\ThemeModule')
      ? \Base\Module\ThemeModule::getMediaQueries()
      : [
          ['slug' => 'phone', 'jit' => 'sml', 'label' => 'Móvil (Phone)', 'min' => null, 'max' => 576, 'query' => '(max-width: 576px)'],
          ['slug' => 'tablet', 'jit' => 'mid', 'label' => 'Tablet', 'min' => 577, 'max' => 992, 'query' => '(min-width: 577px) and (max-width: 992px)'],
          ['slug' => 'desk', 'jit' => 'desk', 'label' => 'Escritorio (Desktop)', 'min' => 993, 'max' => null, 'query' => '(min-width: 993px)'],
        ];
    echo "<script>\n";
    echo "(function() {\n";
    echo "  document.documentElement.classList.add('js-loading');\n";
    echo "  var defaultTheme = " . json_encode($defaultTheme, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ";\n";
    echo "  var theme = localStorage.getItem('fme-theme');\n";
    echo "  if (!theme) {\n";
    echo "    if (defaultTheme === 'dark') {\n";
    echo "      theme = 'dark';\n";
    echo "    } else if (defaultTheme === 'light') {\n";
    echo "      theme = 'light';\n";
    echo "    } else {\n";
    echo "      theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';\n";
    echo "    }\n";
    echo "  }\n";
    echo "  if (theme === 'dark') {\n";
    echo "    document.documentElement.setAttribute('data-theme', 'dark');\n";
    echo "  } else {\n";
    echo "    document.documentElement.removeAttribute('data-theme');\n";
    echo "  }\n";
    echo "  window.fmeDefaultTheme = defaultTheme;\n";
    echo "  window.fmeMediaQueries = " . json_encode($mediaQueries, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ";\n";
    echo "})();\n";
    echo "</script>\n";
    echo "<style>\n";
    echo "  /* Evitar FOUC en componentes críticos antes de cargar hojas de estilo externas */\n";
    echo "  .carousel:not([data-carousel-ready]),\n";
    echo "  .image-gallery:not([data-gallery-ready]),\n";
    echo "  .js-loading .js-hide-on-load {\n";
    echo "    opacity: 0 !important;\n";
    echo "    visibility: hidden !important;\n";
    echo "    height: 0 !important;\n";
    echo "    overflow: hidden !important;\n";
    echo "  }\n";
    echo "  .cut-phrase:not([data-cut-phrase-ready]),\n";
    echo "  .cut-phrase-wrapper:not([data-cut-phrase-ready]) {\n";
    echo "    opacity: 0 !important;\n";
    echo "  }\n";
    foreach ($mediaQueries as $mq) {
      $q = preg_replace('/[^a-zA-Z0-9\s():.\-_]/', '', (string)($mq['query'] ?? '')) ?? '';
      $s = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($mq['slug'] ?? '')) ?? '';
      if ($q !== '' && $s !== '') {
        echo "  @media screen and {$q} {\n";
        echo "    .no-{$s}:not([data-responsive-processed]), .no-{$s}-css { display: none !important; }\n";
        echo "  }\n";
      }
    }
    echo "</style>\n";

    echo '<link rel="shortcut icon" type="image/x-icon" href="' . htmlspecialchars((string)LOGO, ENT_QUOTES, 'UTF-8') . '">';
    echo '</head>';
  }

  /**
   * Maneja páginas de error.
   * 
   * @param string $require Nombre del archivo de error.
   * @param array $msg Mensajes de error.
   * @return string Contenido HTML.
   */
  public function pag_error($require, $msg = [])
  {
    ob_start();
    $this->confView();
    echo '<body>';
    $msg = $msg ? $msg : '';

    $errorDir = defined('ROUTE_ERROR_VIEW') ? ROUTE_ERROR_VIEW : ROOT_PATH . '/App/errorViews/';
    $errorFile = $errorDir . $require . '.php';

    if (!file_exists($errorFile)) {
      // Fallback para compatibilidad con proyectos legacy que usan App/Views/errors/
      $fallbackFile = ROUTE_VIEW . 'errors/' . $require . '.php';
      if (file_exists($fallbackFile)) {
        $errorFile = $fallbackFile;
      }
    }

    if (file_exists($errorFile)) {
      require_once $errorFile;
    } else {
      echo '<div class="container-xl h-dvh flex column-direction center-center">';
      echo '<div class="card back8 br0 w100 flex column-direction center-center">';
      echo '<p class="x50 bold600 color-caution">Error</p>';
      echo '<p class="x25 color2">' . ($msg[2] ?? 'Página no encontrada') . '</p>';
      echo '</div></div>';
    }

    echo '</body>';
    echo '</html>';
    return ob_get_clean();
  }

  // ============================================
  // MÉTODOS DE NAVEGACIÓN
  // ============================================

  /**
   * Controlador de menú.
   * 
   * @param array $menu Array asociativo [ruta => nombre].
   * @return array Configuración del menú.
   */
  public function menu($menu = array())
  {
    $currentUri = $_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'] ?? '/';
    $cleanUri = preg_replace('#(?<!:)//+#', '/', $currentUri);
    return $this->menu = [
      'rute' => array_keys($menu),
      'link' => array_values($menu),
      'cant_item' => count($menu),
      'current_link' => $cleanUri
    ];
  }

  /**
   * Genera enlace de la aplicación normalizando barras dobles.
   * 
   * @param string $url URL relativa o absoluta.
   * @return string URL completa y limpia.
   */
  public function linkapp($url)
  {
    if (empty($url)) {
      return '';
    }
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
      return preg_replace('#(?<!:)//+#', '/', $url);
    }
    $domain = defined('DOMAIN') ? DOMAIN : '';
    $full = rtrim($domain, '/') . '/' . ltrim($url, '/');
    return preg_replace('#(?<!:)//+#', '/', $full);
  }

  /**
   * Genera URL para botón volver.
   * 
   * @param string $link Enlace específico (opcional).
   * @return string URL de retorno.
   */
  public function goback($link = '')
  {
    if ($link === '') {
      $referer = $_SERVER['HTTP_REFERER'] ?? null;
      $get = $_GET ? true : false;

      if ($get && !is_null($referer)) {
        $back = substr_replace($referer, '', strpos($referer, '?'));
      } else {
        $back = LINK;
      }

      return preg_replace('#(?<!:)//+#', '/', $back);
    } else {
      $full = (defined('LINK') ? LINK : '') . '/' . ltrim($link, '/');
      return preg_replace('#(?<!:)//+#', '/', $full);
    }
  }

  // ============================================
  // MÉTODOS DE ARCHIVOS
  // ============================================

  /**
   * Manejo de subida de archivos.
   * 
   * @param array $file Archivo de $_FILES.
   * @param string $destination Directorio destino.
   * @param array $allowed_types Tipos MIME permitidos.
   * @param int $max_size Tamaño máximo en bytes.
   * @return array Información del archivo o error.
   */
  public function file($file, $destination = 'App/Public/Uploads/', $allowed_types = [], $max_size = 5242880)
  {
    if (!isset($file['error']) || is_array($file['error'])) {
      return ['error' => 'Parámetro de archivo inválido'];
    }

    switch ($file['error']) {
      case UPLOAD_ERR_OK:
        break;
      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        return ['error' => 'El archivo excede el tamaño permitido'];
      case UPLOAD_ERR_PARTIAL:
        return ['error' => 'El archivo se subió parcialmente'];
      case UPLOAD_ERR_NO_FILE:
        return ['error' => 'No se subió ningún archivo'];
      default:
        return ['error' => 'Error desconocido'];
    }

    if ($file['size'] > $max_size) {
      return ['error' => 'El archivo excede el tamaño máximo permitido'];
    }

    if (empty($file['tmp_name']) || (!is_uploaded_file($file['tmp_name']) && php_sapi_name() !== 'cli')) {
      return ['error' => 'Archivo subido no válido'];
    }

    // Detectar MIME real del archivo exclusivamente en el servidor (ignorar cabecera manipulable $file['type'])
    $realMime = '';
    if (function_exists('finfo_open')) {
      $finfo = @finfo_open(FILEINFO_MIME_TYPE);
      if ($finfo) {
        $detected = @finfo_file($finfo, $file['tmp_name']);
        @finfo_close($finfo);
        if (is_string($detected)) {
          $realMime = strtolower(trim($detected));
        }
      }
    }
    if ($realMime === '' && function_exists('mime_content_type')) {
      $detected = @mime_content_type($file['tmp_name']);
      if (is_string($detected)) {
        $realMime = strtolower(trim($detected));
      }
    }

    // Bloquear siempre tipos MIME activos o ejecutables en origen web (SVG, HTML, JS, PHP)
    $forbiddenMimes = [
      'image/svg+xml',
      'text/html',
      'application/xhtml+xml',
      'application/xml',
      'text/xml',
      'application/javascript',
      'text/javascript',
      'application/x-javascript',
      'application/x-httpd-php',
      'text/x-php',
      'application/x-php',
      'application/x-sh',
      'application/x-executable'
    ];
    if ($realMime !== '' && in_array($realMime, $forbiddenMimes, true)) {
      return ['error' => 'Tipo de archivo no permitido por seguridad'];
    }

    if (!empty($allowed_types)) {
      $normalizedAllowed = array_map(static fn($m) => strtolower(trim((string)$m)), $allowed_types);
      if ($realMime === '' || !in_array($realMime, $normalizedAllowed, true)) {
        return ['error' => 'Tipo de archivo no permitido'];
      }
    }

    // Prohibir extensiones ejecutables y de contenido activo en origen (RCE y Stored XSS)
    $origName = basename((string)($file['name'] ?? 'archivo'));
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $baseNameOnly = pathinfo($origName, PATHINFO_FILENAME);

    $forbiddenExtensions = [
      'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'php8',
      'phps', 'pht', 'pgif', 'shtml', 'htaccess', 'htpasswd', 'ini',
      'cgi', 'fcgi', 'pl', 'py', 'rb', 'sh', 'bash', 'exe', 'dll',
      'bat', 'cmd', 'com', 'vbs', 'vbe', 'jsp', 'asp', 'aspx', 'asa', 'asax',
      'svg', 'svgz', 'html', 'htm', 'xhtml', 'xml', 'xsl', 'xslt',
      'js', 'mjs', 'cjs', 'css', 'swf', 'jar', 'inc', 'tpl'
    ];

    if ($ext !== '' && in_array($ext, $forbiddenExtensions, true)) {
      return ['error' => 'Extensión de archivo no permitida por seguridad'];
    }

    // Verificar coherencia entre MIME real y extensión cuando se conoce el tipo
    $mimeExtMap = [
      'image/jpeg'      => ['jpg', 'jpeg'],
      'image/png'       => ['png'],
      'image/gif'       => ['gif'],
      'image/webp'      => ['webp'],
      'image/avif'      => ['avif'],
      'application/pdf' => ['pdf'],
      'text/plain'      => ['txt'],
      'application/json'=> ['json'],
    ];
    if ($realMime !== '' && isset($mimeExtMap[$realMime])) {
      if ($ext === '' || !in_array($ext, $mimeExtMap[$realMime], true)) {
        return ['error' => 'La extensión del archivo no corresponde con su contenido real'];
      }
    }

    // Confinar destino dentro de ROOT_PATH para prevenir path traversal
    $cleanDest = trim(str_replace(['..', '\\', "\0"], ['', '/', ''], (string)$destination), '/');
    if ($cleanDest === '') {
      $cleanDest = 'App/Public/Uploads';
    }
    $upload_path = rtrim(ROOT_PATH, '/') . '/' . $cleanDest . '/';
    if (!is_dir($upload_path)) {
      mkdir($upload_path, 0755, true);
    }

    // Proteger el directorio de subidas contra ejecución de scripts PHP/CGI si no existe .htaccess
    $uploadHtaccess = $upload_path . '.htaccess';
    if (!file_exists($uploadHtaccess)) {
      @file_put_contents($uploadHtaccess, "Options -Indexes -ExecCGI\n<FilesMatch \"\\.ph(p[0-9]?|t|ar|tml)|\\.(svg|svgz|html?|xhtml|js|cgi|pl|py|sh)$\">\n  <IfModule mod_authz_core.c>\n    Require all denied\n  </IfModule>\n  <IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n  </IfModule>\n</FilesMatch>\n");
    }

    $safeBase = TextModule::toSlug($baseNameOnly);
    $randomId = bin2hex(random_bytes(12));
    $safeName = $safeBase !== '' ? ($safeBase . '-' . $randomId) : $randomId;
    $filename = ($ext !== '' && preg_match('/^[a-z0-9]{1,10}$/', $ext))
      ? $safeName . '.' . $ext
      : $safeName;
    $filepath = $upload_path . $filename;

    $moved = (php_sapi_name() === 'cli' && !is_uploaded_file($file['tmp_name']))
      ? @copy($file['tmp_name'], $filepath)
      : move_uploaded_file($file['tmp_name'], $filepath);

    if (!$moved) {
      return ['error' => 'Error al mover el archivo'];
    }

    @chmod($filepath, 0644);

    return [
      'success' => true,
      'filename' => $filename,
      'filepath' => $filepath,
      'filesize' => $file['size'],
      'filetype' => $realMime,
      'url' => $cleanDest . '/' . $filename
    ];
  }

  // ============================================
  // MÉTODOS HTTP DE RESPUESTA
  // ============================================

  /**
   * Envía una respuesta JSON limpia.
   * 
   * @param mixed $data Datos a enviar
   * @param int $status Código HTTP (default: 200)
   * @param array $headers Cabeceras HTTP adicionales
   * @return never
   */
  public function json(mixed $data, int $status = 200, array $headers = []): never
  {
    ResponseModule::json($data, $status, $headers);
  }

  /**
   * Redirecciona a una URL con mensaje opcional.
   * 
   * @param string $route Ruta de destino
   * @param string|null $msg Mensaje opcional
   * @param int|null $type 0=success, 1=warning, 2=danger, null=error
   * @return never
   */
  public function redirect(string $route, ?string $msg = null, ?int $type = null): never
  {
    ResponseModule::redirect($route, $msg, $type);
  }
}
