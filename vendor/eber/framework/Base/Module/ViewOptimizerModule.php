<?php

namespace Base\Module;

/**
 * Módulo de optimización de entrega de imágenes y vistas.
 * 
 * Complementa a imgProcessModule (que maneja subidas).
 * Implementa técnicas para mejorar Core Web Vitals:
 * - Lazy loading nativo de imágenes
 * - Responsive images con srcset/sizes
 * - Minificación de HTML
 * - Preloading de recursos críticos
 * 
 * @see imgProcessModule Para procesamiento de subidas
 */
class ViewOptimizerModule
{
  private static bool $initialized = false;
  private static array $preloads = [];
  private static bool $minifyEnabled = true;

  /**
   * Tamaños estándar para srcset responsive
   */
  private const SRCSET_SIZES = [320, 480, 768, 1024, 1200, 1600];
    
    // =========================================================================
    // INICIALIZACIÓN
    // =========================================================================

  /**
   * Inicializa el módulo de optimización.
   * Debe llamarse una vez en el constructor de Control.
   */
  public static function init(): void
  {
    if (self::$initialized) {
      return;
    }

    self::$initialized = true;

    // Configurar headers de optimización si no estamos en CLI
    if (php_sapi_name() !== 'cli' && !headers_sent()) {
      self::setOptimizationHeaders();
    }
  }

  /**
   * Establece headers HTTP para optimización
   */
  private static function setOptimizationHeaders(): void
  {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');

    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
      header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
  }
    
    // =========================================================================
    // RENDERIZADO DE IMÁGENES
    // =========================================================================

  /**
   * Renderiza imagen optimizada con lazy loading y responsive.
   * 
   * @param string $path Ruta de la imagen
   * @param string $alt Texto alternativo
   * @param array $options Opciones: width, height, class, loading, sizes
   * @return string HTML de la imagen
   */
  public static function image(string $path, string $alt = '', array $options = []): string
  {
    $defaults = [
      'width' => null,
      'height' => null,
      'class' => '',
      'loading' => 'lazy',
      'fetchpriority' => 'auto',
      'sizes' => '100vw',
    ];

    $opts = array_merge($defaults, $options);

    // Obtener dimensiones si no se especifican
    if (!$opts['width'] || !$opts['height']) {
      $dims = self::getImageDimensions($path);
      $opts['width'] = $opts['width'] ?? $dims['width'];
      $opts['height'] = $opts['height'] ?? $dims['height'];
    }

    // Generar srcset para responsive
    $srcset = self::generateSrcset($path);

    $classAttr = $opts['class'] ? ' class="' . htmlspecialchars($opts['class']) . '"' : '';

    return sprintf(
      '<img src="%s" alt="%s" width="%d" height="%d" loading="%s" fetchpriority="%s" sizes="%s" srcset="%s"%s>',
      htmlspecialchars($path),
      htmlspecialchars($alt),
      $opts['width'],
      $opts['height'],
      $opts['loading'],
      $opts['fetchpriority'],
      htmlspecialchars($opts['sizes']),
      $srcset,
      $classAttr
    );
  }

  /**
   * Renderiza imagen con lazy loading (below the fold).
   */
  public static function lazyImage(string $path, string $alt = '', array $options = []): string
  {
    return self::image($path, $alt, array_merge($options, [
      'loading' => 'lazy',
      'fetchpriority' => 'low',
    ]));
  }

  /**
   * Renderiza imagen con carga inmediata (above the fold).
   */
  public static function eagerImage(string $path, string $alt = '', array $options = []): string
  {
    return self::image($path, $alt, array_merge($options, [
      'loading' => 'eager',
      'fetchpriority' => 'high',
    ]));
  }

  /**
   * Genera srcset con múltiples tamaños.
   */
  private static function generateSrcset(string $path): string
  {
    $parts = [];
    $pathInfo = pathinfo($path);

    foreach (self::SRCSET_SIZES as $size) {
      // Por ahora, usar la misma imagen para todos los tamaños
      // En producción, se podrían generar versiones redimensionadas
      $parts[] = htmlspecialchars($path) . " {$size}w";
    }

    return implode(', ', $parts);
  }

  /**
   * Obtiene dimensiones de una imagen.
   */
  private static function getImageDimensions(string $path): array
  {
    if (!file_exists($path)) {
      return ['width' => 0, 'height' => 0];
    }

    $info = @getimagesize($path);
    if ($info === false) {
      return ['width' => 0, 'height' => 0];
    }

    return ['width' => $info[0], 'height' => $info[1]];
  }
    
    // =========================================================================
    // MINIFICACIÓN DE HTML
    // =========================================================================

  /**
   * Habilita o deshabilita la minificación.
   */
  public static function setMinifyEnabled(bool $enabled): void
  {
    self::$minifyEnabled = $enabled;
  }

  /**
   * Minifica HTML eliminando espacios innecesarios.
   */
  public static function minifyHTML(string $html): string
  {
    if (!self::$minifyEnabled) {
      return $html;
    }

    // Proteger pre, script, style, textarea tags
    $protected = [];
    $html = preg_replace_callback(
      '/<(pre|script|style|textarea)[^>]*>.*?<\/\\1>/s',
      function ($matches) use (&$protected) {
        $key = '<!--PROTECTED_' . count($protected) . '-->';
        $protected[$key] = $matches[0];
        return $key;
      },
      $html
    );

    // Minificar HTML
    $html = preg_replace([
      '/<!--(?!\[if).*?-->/s',  // Comentarios HTML (excepto condicionales IE)
      '/>\s+</',                 // Espacios entre tags
      '/\s+/',                   // Múltiples espacios
    ], [
      '',
      '><',
      ' ',
    ], $html);

    // Restaurar contenido protegido
    foreach ($protected as $key => $value) {
      $html = str_replace($key, $value, $html);
    }

    return trim($html);
  }
    
    // =========================================================================
    // PRELOADING
    // =========================================================================

  /**
   * Agrega recurso para preload.
   * 
   * @param string $url URL del recurso
   * @param string $type Tipo: style, script, font, image
   * @param array $attrs Atributos adicionales
   */
  public static function preload(string $url, string $type, array $attrs = []): void
  {
    self::$preloads[] = [
      'url' => $url,
      'type' => $type,
      'attrs' => $attrs,
    ];
  }

  /**
   * Genera HTML de todos los preloads registrados.
   */
  public static function getPreloads(): string
  {
    if (empty(self::$preloads)) {
      return '';
    }

    $html = '';
    $asMap = [
      'style' => 'style',
      'script' => 'script',
      'font' => 'font',
      'image' => 'image',
    ];

    foreach (self::$preloads as $resource) {
      $as = $asMap[$resource['type']] ?? 'fetch';
      $attrs = '';

      foreach ($resource['attrs'] as $key => $value) {
        $attrs .= sprintf(' %s="%s"', $key, htmlspecialchars($value));
      }

      $html .= sprintf(
        '<link rel="preload" href="%s" as="%s"%s>' . "\n",
        htmlspecialchars($resource['url']),
        $as,
        $attrs
      );
    }

    return $html;
  }

  /**
   * Genera DNS prefetch para dominios externos.
   */
  public static function dnsPrefetch(array $domains): string
  {
    $html = '';
    foreach ($domains as $domain) {
      $html .= sprintf('<link rel="dns-prefetch" href="%s">', htmlspecialchars($domain));
    }
    return $html;
  }

  /**
   * Genera preconnect para dominios externos.
   */
  public static function preconnect(array $domains): string
  {
    $html = '';
    foreach ($domains as $domain) {
      $html .= sprintf('<link rel="preconnect" href="%s">', htmlspecialchars($domain));
    }
    return $html;
  }
    
    // =========================================================================
    // SCRIPTS DE OPTIMIZACIÓN
    // =========================================================================

  /**
   * Genera script de lazy loading para imágenes.
   * Aplica efecto blur-up para mejor UX.
   */
  public static function getLazyLoadScript(): string
  {
    if (!defined('LAZY_LOAD_IMAGES') || !LAZY_LOAD_IMAGES) {
      return '';
    }

    return <<<'JS'
<script>
(function() {
    var images = document.querySelectorAll('img[loading="lazy"]');
    
    if ('loading' in HTMLImageElement.prototype) {
        images.forEach(function(img) {
            img.addEventListener('load', function() {
                this.style.opacity = '1';
            });
            img.style.transition = 'opacity 0.3s';
            img.style.opacity = '0';
        });
    } else {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    img.style.opacity = '1';
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '100px' });
        
        images.forEach(function(img) {
            img.style.transition = 'opacity 0.3s';
            img.style.opacity = '0';
            observer.observe(img);
        });
    }
})();
</script>
JS;
  }
}
