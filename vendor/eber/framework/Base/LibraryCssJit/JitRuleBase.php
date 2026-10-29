<?php

namespace Base\LibraryCssJit;

/**
 * Clase base abstracta para todas las reglas del compilador JIT CSS.
 * Lee dinámicamente la configuración de colores y fuentes desde _configTheme.css
 * y provee utilidades para media queries, bloques únicos y colores con fallback.
 */
abstract class JitRuleBase implements JitRuleInterface
{
  /**
   * Color gris claro por defecto cuando se invoca un índice de color no definido en _configTheme.css.
   */
  protected const DEFAULT_COLOR_FALLBACK = "#d3d3d3";

  /**
   * Color gris para estado hover cuando el índice de color no existe en _configTheme.css.
   */
  protected const DEFAULT_HOVER_FALLBACK = "#bfbfbf";

  /**
   * Mapa estático de variables de color detectadas en _configTheme.css [indice => nombreVariable].
   *
   * @var array<int, string>|null
   */
  protected static ?array $themeColors = null;

  /**
   * Mapa estático de variables hover detectadas en _configTheme.css [indice => nombreVariable].
   *
   * @var array<int, string>|null
   */
  protected static ?array $themeHoverColors = null;

  /**
   * Mapa estático de variables de fuente detectadas en _configTheme.css [indice => nombreVariable].
   *
   * @var array<int, string>|null
   */
  protected static ?array $themeFonts = null;

  /**
   * Registro de bloques CSS compartidos ya emitidos para evitar duplicados en jit-compiled.css.
   *
   * @var array<string, bool>
   */
  protected array $emittedBlocks = [];

  /**
   * Carga y analiza las variables definidas en _configTheme.css del proyecto (o del framework como respaldo).
   *
   * @return void
   */
  protected static function loadThemeConfig(): void
  {
    if (self::$themeColors !== null) {
      return;
    }

    self::$themeColors = [];
    self::$themeHoverColors = [];
    self::$themeFonts = [];

    $rootPath = defined("ROOT_PATH") ? ROOT_PATH : str_replace("\\", "/", getcwd());
    $candidates = [
      $rootPath . "/App/Public/Css/_configTheme.css",
      $rootPath . "/Resources/Public/Css/_configTheme.css",
      str_replace("\\", "/", dirname(__DIR__, 2)) . "/Resources/Public/Css/_configTheme.css",
    ];

    $themeContent = "";
    foreach ($candidates as $file) {
      if (is_file($file)) {
        $themeContent = (string)file_get_contents($file);
        break;
      }
    }

    if ($themeContent === "") {
      // Configuración base por defecto si no se encuentra archivo físico
      self::$themeColors[1] = "--back-color";
      self::$themeHoverColors[1] = "--back-color-hover";
      for ($i = 2; $i <= 8; $i++) {
        self::$themeColors[$i] = "--back-color{$i}";
        self::$themeHoverColors[$i] = "--back-color{$i}-hover";
      }
      self::$themeFonts[1] = "--font";
      return;
    }

    // Limpiar comentarios CSS antes de analizar declaraciones
    $cleanCss = (string)preg_replace('/\/\*.*?\*\//s', '', $themeContent);

    // 1. Detectar variables --back-color, --back-color1, --back-color2... --back-colorN
    if (preg_match_all('/(--back-color(\d*))\s*:/', $cleanCss, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $m) {
        $varName = $m[1];
        $index = ($m[2] === "" || $m[2] === "1") ? 1 : (int)$m[2];
        if (!isset(self::$themeColors[$index])) {
          self::$themeColors[$index] = $varName;
        }
      }
    }

    // 2. Detectar variables --back-color-hover, --back-color2-hover... --back-colorN-hover
    if (preg_match_all('/(--back-color(\d*)-hover)\s*:/', $cleanCss, $hoverMatches, PREG_SET_ORDER)) {
      foreach ($hoverMatches as $m) {
        $varName = $m[1];
        $index = ($m[2] === "" || $m[2] === "1") ? 1 : (int)$m[2];
        if (!isset(self::$themeHoverColors[$index])) {
          self::$themeHoverColors[$index] = $varName;
        }
      }
    }

    // 3. Registrar variable de fuente principal --font
    self::$themeFonts[1] = "--font";
  }

  /**
   * Verifica si un índice de color existe en _configTheme.css.
   *
   * @param int $index Índice del color (1..N)
   * @return bool
   */
  public static function hasThemeColor(int $index): bool
  {
    self::loadThemeConfig();
    return isset(self::$themeColors[$index]);
  }

  /**
   * Devuelve la expresión CSS para el color solicitado.
   * Si el color está configurado en _configTheme.css usa su variable limpia var(--back-colorN);
   * si no existe en _configTheme.css, inyecta el color por defecto gris claro (#d3d3d3) como fallback.
   *
   * @param int $index Índice del color (1..N)
   * @param string $fallback Color por defecto si no existe en _configTheme.css
   * @return string
   */
  protected function getColorVar(int $index, string $fallback = self::DEFAULT_COLOR_FALLBACK): string
  {
    self::loadThemeConfig();
    if (isset(self::$themeColors[$index])) {
      $varName = self::$themeColors[$index];
      return "var({$varName})";
    }

    $varName = $index === 1 ? "--back-color" : "--back-color{$index}";
    return "var({$varName}, {$fallback})";
  }

  /**
   * Devuelve la expresión CSS para el color hover solicitado.
   * Si existe en _configTheme.css usa su variable limpia; si el color base existe pero no su hover,
   * calcula un color-mix automático; si el color no existe en _configTheme.css, usa gris claro hover (#bfbfbf).
   *
   * @param int $index Índice del color (1..N)
   * @param string $fallback Color hover por defecto si no existe
   * @return string
   */
  protected function getColorHoverVar(int $index, string $fallback = self::DEFAULT_HOVER_FALLBACK): string
  {
    self::loadThemeConfig();
    if (isset(self::$themeHoverColors[$index])) {
      $varName = self::$themeHoverColors[$index];
      return "var({$varName})";
    }

    $hoverVar = $index === 1 ? "--back-color-hover" : "--back-color{$index}-hover";
    if (isset(self::$themeColors[$index])) {
      $baseColor = $this->getColorVar($index);
      return "var({$hoverVar}, color-mix(in srgb, {$baseColor}, white 20%))";
    }

    return "var({$hoverVar}, {$fallback})";
  }

  /**
   * Devuelve la expresión CSS para la fuente predeterminada del proyecto (--font).
   *
   * @param int $index Índice de la fuente (mantenido por compatibilidad)
   * @return string
   */
  protected function getFontVar(int $index = 1): string
  {
    self::loadThemeConfig();
    return "var(--font)";
  }

  /**
   * Emite un bloque CSS compartido una única vez por compilación.
   *
   * @param string $key Identificador único del bloque compartido
   * @param string $css Contenido CSS a emitir
   * @return string
   */
  protected function emitOnce(string $key, string $css): string
  {
    if (isset($this->emittedBlocks[$key])) {
      return "";
    }
    $this->emittedBlocks[$key] = true;
    return $css;
  }

  /**
   * Envuelve el CSS en media query si tiene sufijo responsive (desk, mid, sml).
   *
   * @param string $suffix Sufijo de breakpoint ('desk', 'mid', 'sml' o '')
   * @param string $rule Regla CSS a envolver
   * @return string
   */
  protected function wrapMediaQuery(string $suffix, string $rule): string
  {
    if ($suffix === "desk") {
      return "@media screen and (min-width: 993px) {\n  $rule\n}\n";
    } elseif ($suffix === "mid") {
      return "@media screen and (min-width: 577px) and (max-width: 992px) {\n  $rule\n}\n";
    } elseif ($suffix === "sml") {
      return "@media screen and (max-width: 576px) {\n  $rule\n}\n";
    }
    return $rule . "\n";
  }
}
