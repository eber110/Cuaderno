<?php

namespace Base\Control;

use Core\ErrorHandler;
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

  public function __construct()
  {
    ViewOptimizerModule::init();
  }

  // ============================================
  // MÉTODOS DE RENDERIZADO DE VISTAS
  // ============================================

  /**
   * Renderiza una vista con datos.
   * Modificado para optimizar SEO y TTFB usando ResponseModule::sendContent.
   * 
   * @param string $route Ruta de la vista (sin extensión).
   * @param array $data Datos a pasar a la vista.
   * @return void
   */
  public function view($route, $data = [])
  {
    \ViewData::set($data);
    $datas = $this->viewPart($route, ['data' => $data]);

    // Agregar script de lazy loading si está habilitado
    $datas .= ViewOptimizerModule::getLazyLoadScript();

    // Enviar contenido inmediatamente y cerrar conexión (SEO Boost)
    ResponseModule::sendContent($datas);
  }

  /**
   * Extrae y renderiza una parte de vista.
   * 
   * @param string $route Ruta de la vista.
   * @param array $data Datos para la vista.
   * @return string|void Contenido renderizado.
   */
  private function viewPart($route, $data = [])
  {
    $route = str_replace('.', '/', $route);
    $route = ltrim($route, '/');

    if (file_exists(ROUTE_VIEW . $route . '.php')) {
      ob_start();

      try {
        if (isset($data['data']) && is_array($data['data'])) {
          extract($data['data']);
        }

        $this->confView();
        print '<body>';
        require_once ROUTE_VIEW . $route . '.php';
        print '</body>';
        print '</html>';
        return ob_get_clean();
      } catch (\Exception $e) {
        ob_end_clean();
        print "Error: " . $e->getMessage();
      }
    } else {
      ErrorHandler::handleCode(404, 404, 'No existe la vista especificada. Verifica que has escrito correctamente el nombre de la vista!!');
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
    echo '<!DOCTYPE html>';
    echo '<html lang="es">';
    echo '<head>';
    // Metadatos principales y SEO
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . SeoModule::title() . '</title>';
    echo SeoModule::metaDescription();
    
    // Canónico dinámico libre de parámetros query
    $currentUri = $_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'] ?? '/';
    if (($qPos = strpos($currentUri, '?')) !== false) {
        $currentUri = substr($currentUri, 0, $qPos);
    }
    $canonicalUrl = rtrim(DOMAIN, '/') . '/' . ltrim($currentUri, '/');
    $canonicalUrl = preg_replace('#(?<!:)//+#', '/', $canonicalUrl);
    echo '<link rel="canonical" href="' . $canonicalUrl . '">';
    
    echo SeoModule::getRobotsMeta();
    echo SeoModule::openGraph();

    // JSON-LD estructurado de Persona de forma dinámica
    $personName = defined('SEO_PERSON_NAME') && !empty(SEO_PERSON_NAME) ? SEO_PERSON_NAME : 'Eber Sánchez';
    $personUrl = defined('SEO_PERSON_URL') && !empty(SEO_PERSON_URL) ? SEO_PERSON_URL : '';
    if (empty($personUrl)) {
        $personUrl = DOMAIN;
    } else {
        $personUrl = rtrim($personUrl, '/') . '/';
    }
    $personJob = defined('SEO_PERSON_JOB') && !empty(SEO_PERSON_JOB) ? SEO_PERSON_JOB : 'Desarrollador Web Full-Stack';
    $personKnowsRaw = defined('SEO_PERSON_KNOWS') && !empty(SEO_PERSON_KNOWS) ? SEO_PERSON_KNOWS : 'PHP, JavaScript Vanilla, CSS, Optimización Web';
    $personKnows = array_map('trim', explode(',', $personKnowsRaw));

    $schemaData = [
        "@context" => "https://schema.org",
        "@type" => "Person",
        "name" => $personName,
        "url" => $personUrl,
        "jobTitle" => $personJob,
        "knowsAbout" => $personKnows
    ];
    echo "\n" . '<script type="application/ld+json">' . "\n" . json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n" . '</script>' . "\n";

    echo ViewOptimizerModule::getPreloads();

    require_once FRAMEWORK_PATH . 'Core/Load/LoadStyle.php';

    // Script inline sincrónico: aplica el tema guardado o valor por defecto antes del primer paint (evita FOUC)
    $defaultTheme = $_ENV['DEFAULT_THEME'] ?? 'system';
    echo "<script>\n";
    echo "(function() {\n";
    echo "  document.documentElement.classList.add('js-loading');\n";
    echo "  var defaultTheme = '" . $defaultTheme . "';\n";
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
    echo "  @media screen and (max-width: 576px) {\n";
    echo "    .no-phone:not([data-responsive-processed]) { display: none !important; }\n";
    echo "  }\n";
    echo "  @media screen and (min-width: 577px) and (max-width: 992px) {\n";
    echo "    .no-tablet:not([data-responsive-processed]) { display: none !important; }\n";
    echo "  }\n";
    echo "  @media screen and (min-width: 993px) {\n";
    echo "    .no-desk:not([data-responsive-processed]) { display: none !important; }\n";
    echo "  }\n";
    echo "</style>\n";

    echo '<link rel="shortcut icon" type="image/x-icon" href="' . LOGO . '">';
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

    if (!empty($allowed_types) && !in_array($file['type'], $allowed_types)) {
      return ['error' => 'Tipo de archivo no permitido'];
    }

    $upload_path = ROOT_PATH . '/' . trim($destination, '/') . '/';
    if (!is_dir($upload_path)) {
      mkdir($upload_path, 0777, true);
    }

    $filename = TextModule::toSlug($file['name']);
    $filepath = $upload_path . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
      return ['error' => 'Error al mover el archivo'];
    }

    return [
      'success' => true,
      'filename' => $filename,
      'filepath' => $filepath,
      'filesize' => $file['size'],
      'filetype' => $file['type'],
      'url' => $destination . $filename
    ];
  }

  // ============================================
  // MÉTODOS DE SEO (Delegados a SeoModule)
  // ============================================

  /**
   * Genera título de página.
   * @deprecated Usar SeoModule::setTitle() directamente.
   */
  public function title($home = '')
  {
    if ($home !== '') {
      SeoModule::setTitle($home);
    }
    return SeoModule::title();
  }

  /**
   * Genera meta description.
   * @deprecated Usar SeoModule::setMetaDescription() directamente.
   */
  public function meta_description($desc = null)
  {
    if ($desc !== null) {
      SeoModule::setMetaDescription($desc);
    }
    $this->description = SeoModule::metaDescription();
    return print $this->description;
  }

  /**
   * Configura Open Graph y Twitter Cards.
   * @deprecated Usar SeoModule::setOpenGraph() directamente.
   */
  public function config_share($param = null)
  {
    if ($param !== null) {
      SeoModule::setOpenGraph($param);
    }
    $this->share_conf = SeoModule::openGraph();
  }

  // ============================================
  // MÉTODOS DE TEXTO (Delegados a TextModule)
  // ============================================

  /**
   * @deprecated Usar TextModule::removeAccents() directamente.
   */
  public function remove_accents($strings)
  {
    return TextModule::removeAccents($strings);
  }

  /**
   * @deprecated Usar TextModule::toSlug() directamente.
   */
  public function uri($uri)
  {
    return TextModule::toSlug($uri);
  }

  /**
   * @deprecated Usar TextModule::clean() directamente.
   */
  public function clean_text($text)
  {
    return TextModule::clean($text);
  }

  /**
   * @deprecated Usar TextModule::process() directamente.
   */
  public function process_text($text, $cant = null, $options = [])
  {
    return TextModule::process($text, $cant, $options);
  }

  /**
   * @deprecated Usar TextModule::truncate() directamente.
   */
  public function text_token($text, $cant = null)
  {
    return TextModule::truncate($text, $cant);
  }

  /**
   * @deprecated Usar TextModule::truncateRaw() directamente.
   */
  public function text_token_unformatted($text, $cant = null)
  {
    return TextModule::truncateRaw($text, $cant);
  }

  /**
   * @deprecated Usar TextModule::formatParagraphs() directamente.
   */
  public function textFormatP($text)
  {
    return TextModule::formatParagraphs($text);
  }

  /**
   * @deprecated Usar TextModule::truncate() directamente.
   */
  public function text_string($text, $cant = null)
  {
    return TextModule::truncate($text, $cant);
  }

  // ============================================
  // MÉTODOS DE FECHA/TIEMPO (Delegados a DateTimeModule)
  // ============================================

  /**
   * @deprecated Usar DateTimeModule::timeAgo() directamente.
   */
  public function time_post($date_reg)
  {
    return DateTimeModule::timeAgo($date_reg);
  }

  /**
   * @deprecated Usar DateTimeModule::countdown() directamente.
   */
  public function countDown($date = null, $time = null)
  {
    return DateTimeModule::countdown($date, $time);
  }

  // ============================================
  // MÉTODOS DE CACHÉ (Delegados a CacheModule)
  // ============================================

  /**
   * @deprecated Usar CacheModule::get() y CacheModule::set() directamente.
   */
  public function cache($key, $value = null, $ttl = 3600)
  {
    if ($value === null) {
      return CacheModule::get($key);
    }
    return CacheModule::set($key, $value, $ttl) ? $value : null;
  }

  // ============================================
  // MÉTODOS DE EVENTOS (Delegados a EventModule)
  // ============================================

  /**
   * @deprecated Usar EventModule::on() directamente.
   */
  public function on($event, callable $callback)
  {
    EventModule::on($event, $callback);
  }

  /**
   * @deprecated Usar EventModule::trigger() directamente.
   */
  public function trigger($event, $data = [])
  {
    return EventModule::trigger($event, $data);
  }

  // ============================================
  // MÉTODOS HTTP (Delegados a ResponseModule)
  // ============================================

  /**
   * @deprecated Usar ResponseModule::json() directamente.
   */
  public function json($data, $status = 200, $headers = [])
  {
    ResponseModule::json($data, $status, $headers);
  }

  /**
   * @deprecated Usar ResponseModule::redirect() directamente.
   */
  public function redirect($route, $msg = null, $type = null)
  {
    $typeMap = [0 => 'success', 1 => 'warning', 2 => 'danger'];
    $typeError = $typeMap[$type] ?? 'error';

    if (strpos($route, '?') !== false) {
      $route = substr_replace($route, '', strpos($route, '?'));
    }

    if ($msg === null) {
      header('Location: ' . $route);
    } else {
      header('Location: ' . $route . '?' . $typeError . '=' . $msg);
    }
  }

  /**
   * @deprecated Usar ResponseModule::showError() directamente.
   */
  public function error($style_class = null)
  {
    ResponseModule::showError($style_class);
  }

  // ============================================
  // MÉTODOS DE COOKIES (Delegados a CookieModule)
  // ============================================

  /**
   * @deprecated Usar CookieModule::set() directamente.
   */
  public function configCookie(string $name, array $options = []): string
  {
    return CookieModule::set($name, $options);
  }
}
