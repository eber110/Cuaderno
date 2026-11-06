<?php

namespace Base\Module;

/**
 * Motor central de temas (Theme Engine) del Eber Framework.
 * 
 * Gestiona la configuración declarativa (theme.json), calcula paletas cromáticas,
 * genera el modo oscuro y hovers automáticamente, y compila a _configTheme.css
 * preservando animaciones y variables personalizadas del usuario.
 */
class ThemeModule
{
  /**
   * Variables de animación y efectos hover por defecto del framework.
   */
  private const DEFAULT_ANIMATION_VARS = [
    "--sepia" => "sepia(40%)",
    "--transition" => "all 0.15s ease-in-out",
    "--transition-slow" => "all 0.25s ease-in-out",
    "--animate-duration" => "400ms",
    "--animate-delay" => "0ms",
    "--animate-ease" => "cubic-bezier(0.25, 1, 0.5, 1)",
    "--animate-ease-elastic" => "cubic-bezier(0.34, 1.56, 0.64, 1)",
    "--animate-ease-in" => "cubic-bezier(0.55, 0, 1, 0.45)",
    "--hover-duration-in" => "250ms",
    "--hover-duration-out" => "350ms",
    "--hover-duration-soft" => "350ms",
    "--hover-ease-in" => "cubic-bezier(0.25, 1, 0.5, 1)",
    "--hover-ease-out" => "cubic-bezier(0.25, 1, 0.5, 1)",
    "--hover-ease-elastic" => "cubic-bezier(0.34, 1.56, 0.64, 1)",
    "--hover-scale" => "1.20",
    "--hover-scale-soft" => "1.02",
    "--hover-scale-shrink" => "0.95",
    "--hover-y" => "-6px",
    "--hover-x" => "6px",
    "--hover-rotate" => "2deg",
    "--hover-rotate-tilt" => "-3deg",
    "--hover-glow-color" => "rgba(255, 255, 255, 0.4)",
    "--hover-shadow" => "0 10px 20px rgba(0, 0, 0, 0.15)",
    "--hover-shadow-active" => "0 4px 8px rgba(0, 0, 0, 0.1)",
    "--hover-shadow-soft" => "0 6px 12px rgba(0, 0, 0, 0.12)",
  ];

  /**
   * Obtiene la estructura y valores predeterminados para un tema individual en theme.json.
   *
   * @param string $slug Identificador del tema
   * @param string $name Nombre legible
   * @return array Configuración por defecto de un tema
   */
  public static function getDefaultConfig(string $slug = "default", string $name = "Predeterminado"): array
  {
    return [
      "name" => $name,
      "slug" => $slug,
      "schedule" => null,
      "layout" => [
        "font" => "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif",
        "fontSize" => "20px",
        "widthWindow" => "1500px",
        "scrollbarWidth" => "thin",
        "scrollbarHide" => "none",
      ],
      "light" => [
        "body" => "#FFF9E5",
        "text" => [
          "primary" => "#00124D",
          "secondary" => "#FFF9E5",
          "inactive" => "#bdbdbd",
        ],
        "colors" => [
          "#FFC7B3",
          "#B3FFED",
          "#B3C4FF",
        ],
      ],
      "dark" => [
        "auto" => true,
        "body" => "#092743",
        "text" => [
          "primary" => "#e6f3ff",
          "secondary" => "#051c31",
          "inactive" => "#bdbdbd",
        ],
        "colors" => [],
      ],
      "status" => [
        "success" => "#00b900",
        "hoverSuccess" => "#04ea04",
        "caution" => "#ff8c00",
        "hoverCaution" => "#fead4b",
        "danger" => "#ff0000",
        "hoverDanger" => "#fc6161",
      ],
    ];
  }

  /**
   * Obtiene la estructura de configuración completa para theme.json con múltiples temas.
   *
   * @return array Estructura con activeTheme y mapa de themes
   */
  public static function getDefaultFullConfig(): array
  {
    return [
      "activeTheme" => "default",
      "themes" => [
        "default" => self::getDefaultConfig("default", "Predeterminado"),
      ],
    ];
  }

  /**
   * Resuelve el directorio raíz del proyecto.
   *
   * @param string|null $basePath Ruta base personalizada
   * @return string Ruta base normalizada sin barra final
   */
  public static function resolveBasePath(?string $basePath = null): string
  {
    if ($basePath !== null && trim($basePath) !== "") {
      return rtrim(str_replace("\\", "/", $basePath), "/");
    }

    if (defined("ROOT_PATH") && ROOT_PATH !== "") {
      return rtrim(str_replace("\\", "/", (string)ROOT_PATH), "/");
    }

    return rtrim(str_replace("\\", "/", (string)getcwd()), "/");
  }

  /**
   * Obtiene la ruta del archivo theme.json del proyecto.
   *
   * @param string|null $basePath Directorio base del proyecto
   * @return string Ruta absoluta a theme.json
   */
  public static function getThemeJsonPath(?string $basePath = null): string
  {
    return self::resolveBasePath($basePath) . "/App/Config/theme.json";
  }

  /**
   * Obtiene la ruta del archivo template Resources/Config/theme.json.
   *
   * @param string|null $basePath Directorio base del proyecto
   * @return string Ruta absoluta al template
   */
  public static function getTemplateThemeJsonPath(?string $basePath = null): string
  {
    $base = self::resolveBasePath($basePath);
    $vendorPath = $base . "/vendor/eber/framework/Resources/Config/theme.json";
    if (is_file($vendorPath)) {
      return $vendorPath;
    }
    return $base . "/Resources/Config/theme.json";
  }

  /**
   * Obtiene la ruta del archivo _configTheme.css del proyecto.
   *
   * @param string|null $basePath Directorio base del proyecto
   * @return string Ruta absoluta a _configTheme.css
   */
  public static function getThemeCssPath(?string $basePath = null): string
  {
    return self::resolveBasePath($basePath) . "/App/Public/Css/_configTheme.css";
  }

  /**
   * Carga la configuración completa con todos los temas y el tema activo.
   *
   * @param string|null $jsonPath Ruta al archivo JSON (opcional)
   * @return array Configuración completa normalizada con activeTheme y themes
   */
  public static function loadFullConfig(?string $jsonPath = null): array
  {
    $defaults = self::getDefaultFullConfig();

    if ($jsonPath === null) {
      $jsonPath = self::getThemeJsonPath();
    }

    if (!is_file($jsonPath)) {
      $templatePath = self::getTemplateThemeJsonPath();
      if (is_file($templatePath)) {
        $jsonPath = $templatePath;
      } else {
        return $defaults;
      }
    }

    $raw = @file_get_contents($jsonPath);
    if ($raw === false || trim($raw) === "") {
      return $defaults;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
      return $defaults;
    }

    return self::normalizeFullConfig($data);
  }

  /**
   * Carga y normaliza la configuración desde theme.json.
   * Por defecto resuelve y devuelve el tema activo preservando retrocompatibilidad total.
   *
   * @param string|null $jsonPath Ruta al archivo JSON (opcional)
   * @param string|null $themeSlug Slug del tema específico a cargar (opcional)
   * @return array Configuración completa y validada del tema seleccionado/activo
   */
  public static function loadConfig(?string $jsonPath = null, ?string $themeSlug = null): array
  {
    $full = self::loadFullConfig($jsonPath);
    $activeSlug = self::resolveActiveThemeSlug($full, $themeSlug);
    $theme = $full["themes"][$activeSlug] ?? $full["themes"]["default"] ?? self::getDefaultConfig();

    $theme["activeTheme"] = $full["activeTheme"] ?? "default";
    $theme["themeSlug"] = $activeSlug;

    return $theme;
  }

  /**
   * Extrae la configuración del tema a partir del CSS existente en _configTheme.css.
   * Si el archivo no existe o está vacío, devuelve la configuración base con exactamente 3 colores.
   *
   * @param string|null $cssContent Contenido CSS directo (opcional)
   * @param string|null $basePath Directorio base del proyecto
   * @return array Configuración extraída normalizada
   */
  public static function extractConfigFromCss(?string $cssContent = null, ?string $basePath = null): array
  {
    $defaults = self::getDefaultConfig();

    if ($cssContent === null) {
      $cssPath = self::getThemeCssPath($basePath);
      if (is_file($cssPath)) {
        $cssContent = (string)file_get_contents($cssPath);
      }
    }

    if ($cssContent === null || trim($cssContent) === "") {
      return $defaults;
    }

    $extracted = $defaults;

    // 1. Extraer variables del bloque :root
    if (preg_match('/:root\s*\{([^}]*)\}/s', $cssContent, $rootMatch)) {
      $rootBody = $rootMatch[1];

      // Layout
      if (preg_match('/--font\s*:\s*([^;]+);/', $rootBody, $m)) $extracted["layout"]["font"] = trim($m[1]);
      if (preg_match('/--font-size\s*:\s*([^;]+);/', $rootBody, $m)) $extracted["layout"]["fontSize"] = trim($m[1]);
      if (preg_match('/--width-window\s*:\s*([^;]+);/', $rootBody, $m)) $extracted["layout"]["widthWindow"] = trim($m[1]);
      if (preg_match('/--scrollbar-width\s*:\s*([^;]+);/', $rootBody, $m)) $extracted["layout"]["scrollbarWidth"] = trim($m[1]);
      if (preg_match('/--scrollbar-hide\s*:\s*([^;]+);/', $rootBody, $m)) $extracted["layout"]["scrollbarHide"] = trim($m[1]);

      // Body & Text
      if (preg_match('/--body\s*:\s*([^;]+);/', $rootBody, $m)) $extracted["light"]["body"] = strtoupper(trim($m[1]));
      if (preg_match('/--text-primary\s*:\s*([^;]+);/', $rootBody, $m)) $extracted["light"]["text"]["primary"] = strtoupper(trim($m[1]));
      if (preg_match('/--text-secondary\s*:\s*([^;]+);/', $rootBody, $m)) $extracted["light"]["text"]["secondary"] = strtoupper(trim($m[1]));
      if (preg_match('/--text-inactive\s*:\s*([^;]+);/', $rootBody, $m)) $extracted["light"]["text"]["inactive"] = strtoupper(trim($m[1]));

      // Status
      if (preg_match('/--success\s*:\s*([^;]+);/', $rootBody, $m)) $extracted["status"]["success"] = strtoupper(trim($m[1]));
      if (preg_match('/--hover-success\s*:\s*([^;]+);/', $rootBody, $m)) $extracted["status"]["hoverSuccess"] = strtoupper(trim($m[1]));
      if (preg_match('/--caution\s*:\s*([^;]+);/', $rootBody, $m)) $extracted["status"]["caution"] = strtoupper(trim($m[1]));
      if (preg_match('/--hover-caution\s*:\s*([^;]+);/', $rootBody, $m)) $extracted["status"]["hoverCaution"] = strtoupper(trim($m[1]));
      if (preg_match('/--danger\s*:\s*([^;]+);/', $rootBody, $m)) $extracted["status"]["danger"] = strtoupper(trim($m[1]));
      if (preg_match('/--hover-danger\s*:\s*([^;]+);/', $rootBody, $m)) $extracted["status"]["hoverDanger"] = strtoupper(trim($m[1]));

      // Colores 1..20
      $colors = [];
      if (preg_match('/^[ \t]*--back-color\s*:\s*([^;]+);/m', $rootBody, $m)) {
        $colors[0] = strtoupper(trim($m[1]));
      }
      for ($i = 2; $i <= 20; $i++) {
        if (preg_match('/^[ \t]*--back-color' . $i . '\s*:\s*([^;]+);/m', $rootBody, $m)) {
          $colors[$i - 1] = strtoupper(trim($m[1]));
        }
      }

      if (!empty($colors)) {
        ksort($colors);
        $extracted["light"]["colors"] = array_values($colors);
      } else {
        // Fallback: verificar si vienen variables de Coloristia (--color-step-X)
        $coloristiaColors = self::parseColorsFromInput($rootBody);
        if (!empty($coloristiaColors)) {
          $extracted["light"]["colors"] = $coloristiaColors;
        }
      }
    }

    // 2. Extraer variables del bloque [data-theme="dark"]
    if (preg_match('/\[data-theme=["\']?dark["\']?\]\s*\{([^}]*)\}/s', $cssContent, $darkMatch)) {
      $darkBody = $darkMatch[1];

      if (preg_match('/--body\s*:\s*([^;]+);/', $darkBody, $m)) $extracted["dark"]["body"] = strtoupper(trim($m[1]));
      if (preg_match('/--text-primary\s*:\s*([^;]+);/', $darkBody, $m)) $extracted["dark"]["text"]["primary"] = strtoupper(trim($m[1]));
      if (preg_match('/--text-secondary\s*:\s*([^;]+);/', $darkBody, $m)) $extracted["dark"]["text"]["secondary"] = strtoupper(trim($m[1]));
      if (preg_match('/--text-inactive\s*:\s*([^;]+);/', $darkBody, $m)) $extracted["dark"]["text"]["inactive"] = strtoupper(trim($m[1]));

      $darkColors = [];
      if (preg_match('/^[ \t]*--back-color\s*:\s*([^;]+);/m', $darkBody, $m)) {
        $darkColors[0] = strtoupper(trim($m[1]));
      }
      for ($i = 2; $i <= 20; $i++) {
        if (preg_match('/^[ \t]*--back-color' . $i . '\s*:\s*([^;]+);/m', $darkBody, $m)) {
          $darkColors[$i - 1] = strtoupper(trim($m[1]));
        }
      }

      if (!empty($darkColors)) {
        ksort($darkColors);
        $extracted["dark"]["colors"] = array_values($darkColors);
        $extracted["dark"]["auto"] = false;
      } else {
        $coloristiaDark = self::parseColorsFromInput($darkBody);
        if (!empty($coloristiaDark)) {
          $extracted["dark"]["colors"] = $coloristiaDark;
          $extracted["dark"]["auto"] = false;
        }
      }
    }

    return self::normalizeConfig($extracted);
  }

  /**
   * Parsea y extrae una paleta de colores desde texto libre, CSS de Coloristia (:root { --color-step-X: #HEX })
   * o listas de códigos HEX.
   *
   * @param string $text Texto CSS o lista de colores a parsear
   * @return array Array de colores HEX ordenados (mínimo 3, hasta 20)
   */
  public static function parseColorsFromInput(string $text): array
  {
    if (trim($text) === "") {
      return [];
    }

    // 1. Detectar si contiene steps de Coloristia (--color-step-X: #HEX)
    if (preg_match_all('/--color-step-(\d+)\s*:\s*(#[0-9A-Fa-f]{3,8})/i', $text, $matches, PREG_SET_ORDER)) {
      $steps = [];
      foreach ($matches as $m) {
        $idx = (int)$m[1];
        $hex = strtoupper($m[2]);
        if (strlen($hex) === 4) {
          $hex = "#" . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
        } elseif (strlen($hex) > 7) {
          $hex = substr($hex, 0, 7);
        }
        $steps[$idx] = $hex;
      }
      ksort($steps);
      $sorted = array_values($steps);
      if (count($sorted) >= 3) {
        return array_slice($sorted, 0, 20);
      }
    }

    // 2. Limpiar comentarios CSS (/* ... */)
    $clean = (string)preg_replace('/\/\*[\s\S]*?\*\//', '', $text);

    // 3. Buscar cualquier variable CSS que asigne un HEX (--variable: #HEX)
    if (preg_match_all('/--[\w-]+\s*:\s*(#[0-9A-Fa-f]{3,8})/i', $clean, $matches)) {
      $varColors = [];
      foreach ($matches[1] as $rawHex) {
        $hex = strtoupper($rawHex);
        if (strlen($hex) === 4) {
          $hex = "#" . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
        } elseif (strlen($hex) > 7) {
          $hex = substr($hex, 0, 7);
        }
        $varColors[] = $hex;
      }
      if (count($varColors) >= 3) {
        return array_slice($varColors, 0, 20);
      }
    }

    // 4. Buscar códigos HEX directos en el texto limpio
    if (preg_match_all('/#[0-9A-Fa-f]{6}|#[0-9A-Fa-f]{3}/', $clean, $matches)) {
      $hexList = [];
      foreach ($matches[0] as $rawHex) {
        $hex = strtoupper($rawHex);
        if (strlen($hex) === 4) {
          $hex = "#" . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
        }
        $hexList[] = $hex;
      }
      if (count($hexList) >= 3) {
        return array_slice($hexList, 0, 20);
      }
    }

    return [];
  }

  /**
   * Normaliza la estructura de un tema individual.
   *
   * @param array $data Datos del tema
   * @param string $slug Slug del tema
   * @param string $name Nombre legible
   * @return array Tema individual normalizado
   */
  public static function normalizeSingleTheme(array $data, string $slug = "default", string $name = "Predeterminado"): array
  {
    $defaults = self::getDefaultConfig($slug, $name);

    $themeName = !empty($data["name"]) && is_string($data["name"]) ? trim($data["name"]) : $name;
    $themeSlug = !empty($data["slug"]) && is_string($data["slug"]) ? self::sanitizeSlug($data["slug"]) : $slug;

    $schedule = null;
    if (isset($data["schedule"]) && is_array($data["schedule"])) {
      $schedule = [
        "enabled" => !empty($data["schedule"]["enabled"]),
        "recurring" => !empty($data["schedule"]["recurring"]),
        "start" => trim((string)($data["schedule"]["start"] ?? "")),
        "end" => trim((string)($data["schedule"]["end"] ?? "")),
      ];
    }

    $layout = array_merge($defaults["layout"], (array)($data["layout"] ?? []));

    $lightData = (array)($data["light"] ?? []);
    $lightText = array_merge($defaults["light"]["text"], (array)($lightData["text"] ?? []));
    $lightColors = !empty($lightData["colors"]) && is_array($lightData["colors"])
      ? array_values(array_filter($lightData["colors"], "is_string"))
      : $defaults["light"]["colors"];

    if (empty($lightColors)) {
      $lightColors = $defaults["light"]["colors"];
    }

    // Asegurar rango de 3 a 20 colores
    if (count($lightColors) < 3) {
      $fallback = ["#FFC7B3", "#B3FFED", "#B3C4FF"];
      while (count($lightColors) < 3) {
        $lightColors[] = $fallback[count($lightColors)] ?? "#FFFFFF";
      }
    } elseif (count($lightColors) > 20) {
      $lightColors = array_slice($lightColors, 0, 20);
    }

    $darkData = (array)($data["dark"] ?? []);
    $darkText = array_merge($defaults["dark"]["text"], (array)($darkData["text"] ?? []));
    $darkColors = !empty($darkData["colors"]) && is_array($darkData["colors"])
      ? array_values(array_filter($darkData["colors"], "is_string"))
      : [];

    if (!empty($darkColors) && count($darkColors) > 20) {
      $darkColors = array_slice($darkColors, 0, 20);
    }

    $status = array_merge($defaults["status"], (array)($data["status"] ?? []));

    if (empty($status["hoverSuccess"])) {
      $status["hoverSuccess"] = self::calculateHoverHex((string)$status["success"]);
    }
    if (empty($status["hoverCaution"])) {
      $status["hoverCaution"] = self::calculateHoverHex((string)$status["caution"]);
    }
    if (empty($status["hoverDanger"])) {
      $status["hoverDanger"] = self::calculateHoverHex((string)$status["danger"]);
    }

    return [
      "name" => $themeName,
      "slug" => $themeSlug,
      "schedule" => $schedule,
      "layout" => $layout,
      "light" => [
        "body" => (string)($lightData["body"] ?? $defaults["light"]["body"]),
        "text" => $lightText,
        "colors" => $lightColors,
      ],
      "dark" => [
        "auto" => isset($darkData["auto"]) ? (bool)$darkData["auto"] : true,
        "body" => (string)($darkData["body"] ?? $defaults["dark"]["body"]),
        "text" => $darkText,
        "colors" => $darkColors,
      ],
      "status" => $status,
    ];
  }

  /**
   * Normaliza una configuración completa multi-tema para theme.json.
   *
   * @param array $data Datos completos o formato plano legacy
   * @return array Configuración con activeTheme y array themes
   */
  public static function normalizeFullConfig(array $data): array
  {
    // Si no contiene la clave 'themes', normalizar como estructura legacy envolviéndola en 'default'
    if (!isset($data["themes"]) || !is_array($data["themes"])) {
      $defaultTheme = self::normalizeSingleTheme($data, "default", "Predeterminado");
      return [
        "activeTheme" => "default",
        "themes" => [
          "default" => $defaultTheme,
        ],
      ];
    }

    $themes = [];
    foreach ($data["themes"] as $rawSlug => $rawTheme) {
      if (!is_array($rawTheme)) continue;
      $slug = self::sanitizeSlug((string)($rawTheme["slug"] ?? $rawSlug));
      if ($slug === "") $slug = "default";
      $name = !empty($rawTheme["name"]) && is_string($rawTheme["name"]) ? trim($rawTheme["name"]) : ucfirst($slug);
      $themes[$slug] = self::normalizeSingleTheme($rawTheme, $slug, $name);
    }

    // Asegurar que siempre exista el tema 'default'
    if (!isset($themes["default"])) {
      $themes["default"] = self::getDefaultConfig("default", "Predeterminado");
    }

    $activeTheme = (string)($data["activeTheme"] ?? "default");
    if ($activeTheme !== "auto" && !isset($themes[$activeTheme])) {
      $activeTheme = "default";
    }

    return [
      "activeTheme" => $activeTheme,
      "themes" => $themes,
    ];
  }

  /**
   * Normaliza una estructura de tema completando claves faltantes.
   * Si recibe un formato multi-tema, lo normaliza completamente. Si recibe un tema individual,
   * normaliza ese tema en particular.
   *
   * @param array $data Datos a validar
   * @return array Configuración normalizada
   */
  public static function normalizeConfig(array $data): array
  {
    if (isset($data["themes"]) && is_array($data["themes"])) {
      return self::normalizeFullConfig($data);
    }

    return self::normalizeSingleTheme($data);
  }

  /**
   * Sanitiza un string para ser usado como slug de tema seguro.
   *
   * @param string $slug Slug a sanitizar
   * @return string Slug limpio (letras minúsculas, números y guiones)
   */
  public static function sanitizeSlug(string $slug): string
  {
    $slug = strtolower(trim($slug));
    $slug = (string)preg_replace('/[^a-z0-9_-]+/', '-', $slug);
    return trim($slug, "-_") ?: "tema";
  }

  /**
   * Determina si una regla de programación de calendario está actualmente activa.
   *
   * @param array|null $schedule Regla de programación con enabled, start, end y recurring
   * @param \DateTimeInterface|null $now Fecha y hora a evaluar (por defecto fecha actual)
   * @return bool True si la fecha actual cae dentro del rango
   */
  public static function isScheduleActive(?array $schedule, ?\DateTimeInterface $now = null): bool
  {
    if ($schedule === null || empty($schedule["enabled"])) {
      return false;
    }

    $start = trim((string)($schedule["start"] ?? ""));
    $end = trim((string)($schedule["end"] ?? ""));
    if ($start === "" || $end === "") {
      return false;
    }

    $now = $now ?? new \DateTimeImmutable("now");
    $isRecurring = !empty($schedule["recurring"]);

    if ($isRecurring) {
      $startMd = substr($start, -5);
      $endMd = substr($end, -5);
      $todayMd = $now->format("m-d");

      if ($startMd <= $endMd) {
        return $todayMd >= $startMd && $todayMd <= $endMd;
      } else {
        // Rango que cruza el año nuevo (ej. 12-24 a 01-06)
        return $todayMd >= $startMd || $todayMd <= $endMd;
      }
    } else {
      $todayYmd = $now->format("Y-m-d");
      return $todayYmd >= $start && $todayYmd <= $end;
    }
  }

  /**
   * Resuelve el slug del tema activo aplicando la jerarquía:
   * 1. Parámetro override explícito
   * 2. Query string de preview (?theme=slug)
   * 3. Programación de calendario activa (schedule)
   * 4. Selección manual activa (activeTheme en theme.json)
   * 5. Fallback a 'default'
   *
   * @param array $fullConfig Configuración completa con activeTheme y themes
   * @param string|null $overrideSlug Slug para forzar tema
   * @param \DateTimeInterface|null $now Fecha de evaluación
   * @return string Slug del tema resuelto
   */
  public static function resolveActiveThemeSlug(array $fullConfig, ?string $overrideSlug = null, ?\DateTimeInterface $now = null): string
  {
    $themes = $fullConfig["themes"] ?? [];
    if (empty($themes)) {
      return "default";
    }

    // 1. Override explícito
    if ($overrideSlug !== null && isset($themes[$overrideSlug])) {
      return $overrideSlug;
    }

    // 2. Override por Query String en preview (?theme=slug)
    if (isset($_GET["theme"]) && is_string($_GET["theme"])) {
      $qTheme = trim($_GET["theme"]);
      if ($qTheme !== "" && isset($themes[$qTheme])) {
        return $qTheme;
      }
    }

    // 3. Comprobar programación por calendario de los temas guardados
    foreach ($themes as $slug => $theme) {
      if ($slug === "default") continue;
      if (!empty($theme["schedule"]) && self::isScheduleActive((array)$theme["schedule"], $now)) {
        return (string)$slug;
      }
    }

    // 4. Selección manual activa
    $manual = (string)($fullConfig["activeTheme"] ?? "default");
    if ($manual !== "" && $manual !== "auto" && isset($themes[$manual])) {
      return $manual;
    }

    // 5. Fallback a 'default'
    return isset($themes["default"]) ? "default" : (string)array_key_first($themes);
  }

  /**
   * Obtiene el slug del tema activo para el proyecto actual.
   *
   * @param string|null $basePath Directorio base del proyecto
   * @return string Slug del tema activo
   */
  public static function getActiveThemeSlug(?string $basePath = null): string
  {
    $jsonPath = self::getThemeJsonPath($basePath);
    $full = self::loadFullConfig($jsonPath);
    return self::resolveActiveThemeSlug($full);
  }

  /**
   * Obtiene los datos del tema activo para el proyecto actual.
   *
   * @param string|null $basePath Directorio base del proyecto
   * @return array Datos completos del tema activo
   */
  public static function getActiveThemeData(?string $basePath = null): array
  {
    $jsonPath = self::getThemeJsonPath($basePath);
    return self::loadConfig($jsonPath);
  }

  /**
   * Obtiene todos los temas guardados en el proyecto.
   *
   * @param string|null $basePath Directorio base del proyecto
   * @return array Mapa de temas [slug => tema]
   */
  public static function getAllThemes(?string $basePath = null): array
  {
    $jsonPath = self::getThemeJsonPath($basePath);
    $full = self::loadFullConfig($jsonPath);
    return $full["themes"] ?? [];
  }

  /**
   * Guarda o actualiza un tema con nombre en theme.json.
   *
   * @param string $slug Slug identificador
   * @param array $themeData Datos de configuración del tema
   * @param bool $setActive Si debe establecerse como tema activo inmediatamente
   * @param string|null $basePath Directorio base del proyecto
   * @return bool True si se guardó con éxito
   */
  public static function saveTheme(string $slug, array $themeData, bool $setActive = false, ?string $basePath = null): bool
  {
    $jsonPath = self::getThemeJsonPath($basePath);
    $full = self::loadFullConfig($jsonPath);

    $slug = self::sanitizeSlug($slug);
    $name = !empty($themeData["name"]) ? trim($themeData["name"]) : ucfirst($slug);
    $themeData["slug"] = $slug;
    $themeData["name"] = $name;

    $full["themes"][$slug] = self::normalizeSingleTheme($themeData, $slug, $name);

    if ($setActive) {
      $full["activeTheme"] = $slug;
    }

    return self::saveFullConfig($full, $jsonPath);
  }

  /**
   * Elimina un tema guardado por su slug (el tema 'default' no puede ser eliminado).
   *
   * @param string $slug Slug del tema a eliminar
   * @param string|null $basePath Directorio base del proyecto
   * @return bool True si se eliminó correctamente
   */
  public static function deleteTheme(string $slug, ?string $basePath = null): bool
  {
    $slug = self::sanitizeSlug($slug);
    if ($slug === "default") {
      return false; // Prohibido eliminar el tema predeterminado
    }

    $jsonPath = self::getThemeJsonPath($basePath);
    $full = self::loadFullConfig($jsonPath);

    if (!isset($full["themes"][$slug])) {
      return false;
    }

    unset($full["themes"][$slug]);

    if (($full["activeTheme"] ?? "") === $slug) {
      $full["activeTheme"] = "default";
    }

    return self::saveFullConfig($full, $jsonPath);
  }

  /**
   * Establece el tema activo (o 'auto' para programación de calendario).
   *
   * @param string $slug Slug del tema o 'auto'
   * @param string|null $basePath Directorio base del proyecto
   * @return bool True si se actualizó
   */
  public static function setActiveTheme(string $slug, ?string $basePath = null): bool
  {
    $jsonPath = self::getThemeJsonPath($basePath);
    $full = self::loadFullConfig($jsonPath);

    if ($slug !== "auto" && !isset($full["themes"][$slug])) {
      return false;
    }

    $full["activeTheme"] = $slug;
    return self::saveFullConfig($full, $jsonPath);
  }

  /**
   * Guarda la configuración completa multi-tema en theme.json.
   *
   * @param array $fullConfig Configuración completa con activeTheme y themes
   * @param string|null $jsonPath Ruta destino
   * @return bool True si se escribió correctamente
   */
  public static function saveFullConfig(array $fullConfig, ?string $jsonPath = null): bool
  {
    if ($jsonPath === null) {
      $jsonPath = self::getThemeJsonPath();
    }

    $dir = dirname($jsonPath);
    if (!is_dir($dir)) {
      @mkdir($dir, 0755, true);
    }

    $normalized = self::normalizeFullConfig($fullConfig);
    $json = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    return file_put_contents($jsonPath, $json . PHP_EOL) !== false;
  }

  /**
   * Guarda la configuración en theme.json soportando formatos completos o individuales.
   *
   * @param array $config Configuración a guardar
   * @param string|null $jsonPath Ruta destino
   * @return bool True si se escribió correctamente
   */
  public static function saveConfig(array $config, ?string $jsonPath = null): bool
  {
    if (isset($config["themes"]) && is_array($config["themes"])) {
      return self::saveFullConfig($config, $jsonPath);
    }

    // Si es un tema individual, actualizarlo dentro de la colección existente
    $full = self::loadFullConfig($jsonPath);
    $slug = !empty($config["slug"]) ? self::sanitizeSlug($config["slug"]) : "default";
    $name = !empty($config["name"]) ? trim($config["name"]) : ($full["themes"][$slug]["name"] ?? "Predeterminado");

    $full["themes"][$slug] = self::normalizeSingleTheme($config, $slug, $name);
    return self::saveFullConfig($full, $jsonPath);
  }

  /**
   * Convierte un código HEX (#RGB o #RRGGBB) a un array RGB [r, g, b].
   *
   * @param string $hex Código hexadecimal
   * @return array Array numérico [r, g, b]
   */
  public static function hexToRgb(string $hex): array
  {
    $hex = ltrim($hex, "#");
    if (strlen($hex) === 3) {
      $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
      return [0, 0, 0];
    }

    return [
      (int)hexdec(substr($hex, 0, 2)),
      (int)hexdec(substr($hex, 2, 2)),
      (int)hexdec(substr($hex, 4, 2))
    ];
  }

  /**
   * Convierte valores RGB a código hexadecimal #RRGGBB.
   *
   * @param int $r Canal rojo (0-255)
   * @param int $g Canal verde (0-255)
   * @param int $b Canal azul (0-255)
   * @return string Código hexadecimal en mayúsculas
   */
  public static function rgbToHex(int $r, int $g, int $b): string
  {
    $r = max(0, min(255, $r));
    $g = max(0, min(255, $g));
    $b = max(0, min(255, $b));
    return sprintf("#%02X%02X%02X", $r, $g, $b);
  }

  /**
   * Convierte valores RGB a HSL [h (0..360), s (0..1), l (0..1)].
   *
   * @param int $r Canal rojo
   * @param int $g Canal verde
   * @param int $b Canal azul
   * @return array [h, s, l]
   */
  public static function rgbToHsl(int $r, int $g, int $b): array
  {
    $rNorm = $r / 255.0;
    $gNorm = $g / 255.0;
    $bNorm = $b / 255.0;

    $max = max($rNorm, $gNorm, $bNorm);
    $min = min($rNorm, $gNorm, $bNorm);
    $l = ($max + $min) / 2.0;

    if ($max === $min) {
      $h = 0.0;
      $s = 0.0;
    } else {
      $d = $max - $min;
      $s = $l > 0.5 ? $d / (2.0 - $max - $min) : $d / ($max + $min);
      switch ($max) {
        case $rNorm:
          $h = ($gNorm - $bNorm) / $d + ($gNorm < $bNorm ? 6.0 : 0.0);
          break;
        case $gNorm:
          $h = ($bNorm - $rNorm) / $d + 2.0;
          break;
        case $bNorm:
          $h = ($rNorm - $gNorm) / $d + 4.0;
          break;
        default:
          $h = 0.0;
      }
      $h *= 60.0;
    }

    return [$h, $s, $l];
  }

  /**
   * Convierte valores HSL a RGB [r, g, b].
   *
   * @param float $h Tono (0..360)
   * @param float $s Saturación (0..1)
   * @param float $l Luminosidad (0..1)
   * @return array [r, g, b]
   */
  public static function hslToRgb(float $h, float $s, float $l): array
  {
    $h = fmod($h, 360.0);
    if ($h < 0) {
      $h += 360.0;
    }
    $h /= 360.0;

    if ($s <= 0.0001) {
      $val = (int)round($l * 255);
      return [$val, $val, $val];
    }

    $q = $l < 0.5 ? $l * (1.0 + $s) : $l + $s - $l * $s;
    $p = 2.0 * $l - $q;

    $hue2rgb = function (float $p, float $q, float $t): float {
      if ($t < 0.0) $t += 1.0;
      if ($t > 1.0) $t -= 1.0;
      if ($t < 1.0 / 6.0) return $p + ($q - $p) * 6.0 * $t;
      if ($t < 1.0 / 2.0) return $q;
      if ($t < 2.0 / 3.0) return $p + ($q - $p) * (2.0 / 3.0 - $t) * 6.0;
      return $p;
    };

    $r = (int)round($hue2rgb($p, $q, $h + 1.0 / 3.0) * 255);
    $g = (int)round($hue2rgb($p, $q, $h) * 255);
    $b = (int)round($hue2rgb($p, $q, $h - 1.0 / 3.0) * 255);

    return [$r, $g, $b];
  }

  /**
   * Calcula un color hover en HEX a partir de un color HEX base.
   *
   * @param string $hex Código hexadecimal
   * @param float $factor Factor de luminosidad
   * @return string Color hover en formato HEX
   */
  public static function calculateHoverHex(string $hex, float $factor = 1.15): string
  {
    [$r, $g, $b] = self::hexToRgb($hex);
    [$h, $s, $l] = self::rgbToHsl($r, $g, $b);

    if ($l < 0.75) {
      $newL = min(0.95, $l * $factor);
    } else {
      $newL = max(0.1, $l / $factor);
    }

    [$nr, $ng, $nb] = self::hslToRgb($h, $s, $newL);
    return self::rgbToHex($nr, $ng, $nb);
  }

  /**
   * Genera automáticamente una paleta de colores para modo oscuro a partir de colores claros.
   * Invierte inteligentemente la luminosidad conservando tono y saturación,
   * aplicando desaturación ergonómica y asegurando contrastes adecuados para WCAG.
   *
   * @param array $lightColors Array de colores HEX en modo claro
   * @param array $options Opciones de configuración (minL, maxL, desaturate)
   * @return array Array de colores HEX calculados para modo oscuro
   */
  public static function generateDarkPalette(array $lightColors, array $options = []): array
  {
    $minL = (float)($options["minL"] ?? 0.18);
    $maxL = (float)($options["maxL"] ?? 0.82);
    $desaturate = (float)($options["desaturate"] ?? 0.88);

    $darkColors = [];
    foreach ($lightColors as $hex) {
      [$r, $g, $b] = self::hexToRgb($hex);
      [$h, $s, $l] = self::rgbToHsl($r, $g, $b);

      // Inversión de luminosidad
      $newL = max($minL, min($maxL, 1.0 - $l));
      // Desaturación ergonómica en modo oscuro
      $newS = max(0.0, min(1.0, $s * $desaturate));

      [$nr, $ng, $nb] = self::hslToRgb($h, $newS, $newL);
      $darkColors[] = self::rgbToHex($nr, $ng, $nb);
    }

    return $darkColors;
  }

  /**
   * Compila la configuración a sintaxis CSS completa para _configTheme.css.
   * Preserva animaciones, transiciones, genera la variable global --theme-active
   * y emite las utilidades condicionales de visibilidad (.only-theme-*, .no-theme-*)
   * para todos los temas registrados.
   *
   * @param array $config Configuración del tema (normalizada, cruda o multi-tema)
   * @param string|null $existingCss CSS previo para sincronización segura
   * @param array|null $allThemes Colección de todos los temas registrados para generar utilidades
   * @return string Contenido CSS compilado
   */
  public static function compileToCss(array $config, ?string $existingCss = null, ?array $allThemes = null): string
  {
    $activeSlug = "default";
    if (isset($config["themes"]) && is_array($config["themes"])) {
      $allThemes = $config["themes"];
      $activeSlug = self::resolveActiveThemeSlug($config);
      $norm = self::normalizeSingleTheme($allThemes[$activeSlug] ?? $allThemes["default"] ?? self::getDefaultConfig(), $activeSlug);
    } else {
      $norm = self::normalizeConfig($config);
      $activeSlug = !empty($norm["slug"]) ? (string)$norm["slug"] : "default";
      if ($allThemes === null) {
        $allThemes = self::getAllThemes();
      }
    }

    if (empty($allThemes)) {
      $allThemes = [$activeSlug => $norm];
    }

    // Determinar paleta oscura: si está vacía o auto=true con colores vacíos, autogenerar
    $darkColors = $norm["dark"]["colors"];
    if (empty($darkColors)) {
      $darkColors = self::generateDarkPalette($norm["light"]["colors"]);
    }

    // Extraer variables existentes no controladas por la paleta principal para preservarlas
    $customRootVars = self::extractCustomRootVars($existingCss);
    $customDarkVars = self::extractCustomDarkVars($existingCss);
    $trailingCss = self::extractTrailingCss($existingCss);

    $layout = $norm["layout"];
    $light = $norm["light"];
    $dark = $norm["dark"];
    $status = $norm["status"];

    $lines = [];
    $lines[] = "/***VAR***/";
    $lines[] = "";
    $lines[] = ":root {";
    $lines[] = "  --theme-active: \"{$activeSlug}\";";
    $lines[] = "  --font: " . $layout["font"] . ";";
    $lines[] = "  --font-size: " . $layout["fontSize"] . ";";
    $lines[] = "  --width-window: " . $layout["widthWindow"] . ";";
    $lines[] = "  --scrollbar-width: " . $layout["scrollbarWidth"] . ";";
    $lines[] = "  --scrollbar-hide: " . $layout["scrollbarHide"] . ";";
    $lines[] = "";
    $lines[] = "  --body: " . $light["body"] . ";";
    $lines[] = "";
    $lines[] = "  --text-primary:   " . $light["text"]["primary"] . ";";
    $lines[] = "  --text-secondary: " . $light["text"]["secondary"] . ";";
    $lines[] = "  --text-inactive:   " . $light["text"]["inactive"] . ";";
    $lines[] = "";
    $lines[] = "  /*Config color site*/";

    // Colores base modo claro
    foreach ($light["colors"] as $index => $color) {
      $varName = ($index === 0) ? "--back-color" : "--back-color" . ($index + 1);
      $lines[] = "  {$varName}: {$color};";
    }
    $lines[] = "";

    // Hovers modo claro con sintaxis relativa oklch
    foreach ($light["colors"] as $index => $color) {
      $varName = ($index === 0) ? "--back-color" : "--back-color" . ($index + 1);
      $hoverName = ($index === 0) ? "--back-color-hover" : "--back-color" . ($index + 1) . "-hover";
      $lines[] = "  {$hoverName}: oklch(from var({$varName}) calc(l * 1.2) calc(c - 0.02) h);";
    }
    $lines[] = "";

    // Estados
    $lines[] = "  --success: " . $status["success"] . ";";
    $lines[] = "  --hover-success: " . $status["hoverSuccess"] . ";";
    $lines[] = "";
    $lines[] = "  --caution: " . $status["caution"] . ";";
    $lines[] = "  --hover-caution: " . $status["hoverCaution"] . ";";
    $lines[] = "";
    $lines[] = "  --danger: " . $status["danger"] . ";";
    $lines[] = "  --hover-danger: " . $status["hoverDanger"] . ";";
    $lines[] = "";

    // Variables personalizadas o animaciones preservadas
    if (!empty($customRootVars)) {
      $hasAnimationHeader = false;

      foreach ($customRootVars as $name => $value) {
        if (str_starts_with($name, "--animate-") || str_starts_with($name, "--hover-")) {
          if (!$hasAnimationHeader) {
            $lines[] = "  /* Variables de animación y efectos hover */";
            $hasAnimationHeader = true;
          }
        }
        $lines[] = "  {$name}: {$value};";
      }
    }

    $lines[] = "}";
    $lines[] = "";
    $lines[] = "/* Modo oscuro - sobrescribe la paleta de colores */";
    $lines[] = '[data-theme="dark"] {';
    $lines[] = "";
    $lines[] = "  --body: " . $dark["body"] . ";";
    $lines[] = "";
    $lines[] = "  --text-primary:   " . $dark["text"]["primary"] . ";";
    $lines[] = "  --text-secondary: " . $dark["text"]["secondary"] . ";";
    $lines[] = "  --text-inactive:   " . $dark["text"]["inactive"] . ";";
    $lines[] = "";

    // Colores base modo oscuro
    foreach ($darkColors as $index => $color) {
      $varName = ($index === 0) ? "--back-color" : "--back-color" . ($index + 1);
      $padding = ($index === 0) ? "  " : " ";
      $lines[] = "  {$varName}:{$padding}{$color};";
    }
    $lines[] = "";

    // Hovers modo oscuro
    foreach ($darkColors as $index => $color) {
      $varName = ($index === 0) ? "--back-color" : "--back-color" . ($index + 1);
      $hoverName = ($index === 0) ? "--back-color-hover" : "--back-color" . ($index + 1) . "-hover";
      $padding = ($index === 0) ? "  " : " ";
      $lines[] = "  {$hoverName}:{$padding}oklch(from var({$varName}) calc(l * 1.2) calc(c - 0.02) h);";
    }

    if (!empty($customDarkVars)) {
      $lines[] = "";
      $lines[] = "  /* Variables adicionales en modo oscuro */";
      foreach ($customDarkVars as $name => $value) {
        $lines[] = "  {$name}: {$value};";
      }
    }

    $lines[] = "";
    $lines[] = "}";

    // Generar bloque de utilidades condicionales para cada tema registrado
    if (!empty($allThemes)) {
      $lines[] = "";
      $lines[] = "/* ==============================================================";
      $lines[] = "   UTILIDADES CONDICIONALES POR TEMA (VISIBILIDAD ESTACIONAL)";
      $lines[] = "   ============================================================== */";

      foreach ($allThemes as $tSlug => $tData) {
        $cleanSlug = self::sanitizeSlug((string)($tData["slug"] ?? $tSlug));
        $cleanName = !empty($tData["name"]) ? $tData["name"] : ucfirst($cleanSlug);

        $lines[] = "";
        $lines[] = "/* Tema: {$cleanName} ({$cleanSlug}) */";
        $lines[] = ".only-theme-{$cleanSlug} {";
        $lines[] = "  display: none !important;";
        $lines[] = "}";
        $lines[] = "[data-theme-name=\"{$cleanSlug}\"] .only-theme-{$cleanSlug},";
        $lines[] = "body.theme-{$cleanSlug} .only-theme-{$cleanSlug} {";
        $lines[] = "  display: revert !important;";
        $lines[] = "}";
        $lines[] = "[data-theme-name=\"{$cleanSlug}\"] .no-theme-{$cleanSlug},";
        $lines[] = "body.theme-{$cleanSlug} .no-theme-{$cleanSlug} {";
        $lines[] = "  display: none !important;";
        $lines[] = "}";
      }
    }

    if ($trailingCss !== "") {
      $lines[] = "";
      $lines[] = trim($trailingCss);
    }

    return implode("\n", $lines) . "\n";
  }

  /**
   * Extrae variables en :root que no corresponden a las controladas directamente por el tema.
   *
   * @param string|null $css Contenido CSS previo
   * @return array Mapa de variables [nombre => valor]
   */
  private static function extractCustomRootVars(?string $css): array
  {
    $controlled = [
      "--font",
      "--font-size",
      "--width-window",
      "--scrollbar-width",
      "--scrollbar-hide",
      "--body",
      "--text-primary",
      "--text-secondary",
      "--text-inactive",
      "--success",
      "--hover-success",
      "--caution",
      "--hover-caution",
      "--danger",
      "--hover-danger",
    ];

    $vars = [];

    if ($css !== null && preg_match('/:root\s*\{([^}]*)\}/s', $css, $matches)) {
      if (preg_match_all('/(--[a-zA-Z0-9_-]+)\s*:\s*([^;]+);/', $matches[1], $propMatches, PREG_SET_ORDER)) {
        foreach ($propMatches as $prop) {
          $name = trim($prop[1]);
          $value = trim($prop[2]);

          if (in_array($name, $controlled, true)) {
            continue;
          }
          if (preg_match('/^--back-color\d*(-hover)?$/', $name)) {
            continue;
          }

          $vars[$name] = $value;
        }
      }
    }

    // Combinar con valores por defecto del framework para que nunca falte ninguna variable esencial
    foreach (self::DEFAULT_ANIMATION_VARS as $defName => $defVal) {
      if (!isset($vars[$defName])) {
        $vars[$defName] = $defVal;
      }
    }

    return $vars;
  }

  /**
   * Extrae variables adicionales en [data-theme="dark"].
   *
   * @param string|null $css Contenido CSS previo
   * @return array Mapa de variables [nombre => valor]
   */
  private static function extractCustomDarkVars(?string $css): array
  {
    $controlled = [
      "--body",
      "--text-primary",
      "--text-secondary",
      "--text-inactive",
    ];

    $vars = [];

    if ($css !== null && preg_match('/\[data-theme=["\']?dark["\']?\]\s*\{([^}]*)\}/s', $css, $matches)) {
      if (preg_match_all('/(--[a-zA-Z0-9_-]+)\s*:\s*([^;]+);/', $matches[1], $propMatches, PREG_SET_ORDER)) {
        foreach ($propMatches as $prop) {
          $name = trim($prop[1]);
          $value = trim($prop[2]);

          if (in_array($name, $controlled, true)) {
            continue;
          }
          if (preg_match('/^--back-color\d*(-hover)?$/', $name)) {
            continue;
          }

          $vars[$name] = $value;
        }
      }
    }

    return $vars;
  }

  /**
   * Extrae reglas o bloques CSS existentes posteriores a [data-theme="dark"],
   * descartando bloques utilitarios generados automáticamente en compilaciones previas.
   *
   * @param string|null $css Contenido CSS previo
   * @return string CSS adicional
   */
  private static function extractTrailingCss(?string $css): string
  {
    if ($css === null) {
      return "";
    }

    if (preg_match('/\[data-theme=["\']?dark["\']?\]\s*\{[^}]*\}\s*(.*)$/s', $css, $matches)) {
      $trailing = trim($matches[1]);
      $clean = preg_replace('/\/\*\s*=+\s*UTILIDADES CONDICIONALES POR TEMA[\s\S]*$/i', '', $trailing);
      return trim((string)$clean);
    }

    return "";
  }

  /**
   * Determina si _configTheme.css necesita ser recompilado respecto a theme.json.
   *
   * @param string|null $basePath Directorio base del proyecto
   * @return bool True si debe recompilarse
   */
  public static function needsRebuild(?string $basePath = null): bool
  {
    $jsonPath = self::getThemeJsonPath($basePath);
    $cssPath = self::getThemeCssPath($basePath);

    if (!is_file($jsonPath)) {
      return false;
    }

    if (!is_file($cssPath)) {
      return true;
    }

    return filemtime($jsonPath) > filemtime($cssPath);
  }

  /**
   * Compila theme.json y genera _configTheme.css.
   *
   * @param string|null $basePath Directorio base del proyecto
   * @param bool $force Forzar compilación omitiendo verificación de timestamps
   * @return array [success => bool, updated => bool, message => string]
   */
  public static function build(?string $basePath = null, bool $force = false): array
  {
    $base = self::resolveBasePath($basePath);
    $jsonPath = self::getThemeJsonPath($base);
    $cssPath = self::getThemeCssPath($base);

    // Si no existe theme.json en App/Config, inicializarlo desde Resources/Config
    if (!is_file($jsonPath)) {
      $templatePath = self::getTemplateThemeJsonPath($base);
      $jsonDir = dirname($jsonPath);
      if (!is_dir($jsonDir)) {
        @mkdir($jsonDir, 0755, true);
      }

      if (is_file($templatePath)) {
        @copy($templatePath, $jsonPath);
      } else {
        self::saveConfig(self::getDefaultConfig(), $jsonPath);
      }
    }

    // Verificar si _configTheme.css ya está al día
    if (!$force && is_file($cssPath) && filemtime($jsonPath) <= filemtime($cssPath)) {
      return [
        "success" => true,
        "updated" => false,
        "message" => "El archivo _configTheme.css ya está actualizado respecto a theme.json.",
      ];
    }

    $fullConfig = self::loadFullConfig($jsonPath);
    $existingCss = is_file($cssPath) ? (string)file_get_contents($cssPath) : null;
    $compiledCss = self::compileToCss($fullConfig, $existingCss, $fullConfig["themes"] ?? null);

    $cssDir = dirname($cssPath);
    if (!is_dir($cssDir)) {
      @mkdir($cssDir, 0755, true);
    }

    $written = file_put_contents($cssPath, $compiledCss);
    if ($written === false) {
      return [
        "success" => false,
        "updated" => false,
        "message" => "Error: No se pudo escribir en {$cssPath}.",
      ];
    }

    return [
      "success" => true,
      "updated" => true,
      "message" => "Tema compilado con éxito en {$cssPath}.",
    ];
  }

  /**
   * Alias de compilación forzada.
   *
   * @param string|null $basePath Directorio base del proyecto
   * @return array Resultado de la compilación
   */
  public static function compile(?string $basePath = null): array
  {
    return self::build($basePath, true);
  }
}
