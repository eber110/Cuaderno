<?php

namespace Base\Module;

/**
 * 🎛️ FormUiModule
 *
 * Motor declarativo y configurador global/local de componentes de formulario e inputs UI
 * del Eber Framework (equivalente a GraphicsModule para formularios interactivos).
 *
 * Filosofía de diseño:
 * - Centraliza el diseño mediante Design Tokens CSS (--ui-*) con soporte nativo
 *   de modo claro/oscuro ([data-theme="dark"]) y adaptación táctil (Pointer Events / Bottom-Sheet).
 * - Soporta configuración global (`FormUiModule::configStyle([...])` y `theme.json -> inputs`),
 *   sobrescritura local vía helper `_input(...)` y retrocompatibilidad total con atributos HTML
 *   clásicos (`box-image`, `box-btn-image`, `style-box`, `style-color`, `data-note`, etc.).
 *
 * @package Base\Module
 */
class FormUiModule
{
  /**
   * Lista canónica de componentes soportados por FormUiModule y UI Studio.
   */
  public const COMPONENT_TYPES = [
    'cropImage',
    'loadImgModern',
    'colorPicker',
    'checkboxSwitch',
    'dropdown',
    'datePicker',
    'timePicker',
    'rangeSlider',
    'passwordInput',
    'otpCodeInput',
    'tagsInput',
    'stepperInput',
    'ratingInput',
    'fileDropzone',
    'repeatCamp',
    'textLimiter',
    'textInput',
    'textareaInput',
    'submitButton',
  ];

  /**
   * Configuración global de estilo por defecto.
   *
   * @var array<string, mixed>
   */
  private static array $globalConfig = [
    'preset'             => 'soft',
    'triggerBg'          => 'back1',
    'triggerText'        => 'texto',
    'triggerBorder'      => 'textc',
    'surfaceBg'          => 'back1',
    'surfaceText'        => 'texto',
    'surfaceMuted'       => 'text-inactive',
    'surfaceBorder'      => 'textc',
    'placeholderColor'   => 'text-inactive',
    'placeholderSize'    => 14,
    'secondaryText'      => 'text-inactive',
    'secondaryFontSize'  => 12,
    'btnPrimaryBg'       => 'back6',
    'btnPrimaryText'     => 'textw',
    'btnPrimaryBorder'   => 'transparent',
    'btnSecondaryBg'     => 'back2',
    'btnSecondaryText'   => 'texto',
    'btnSecondaryBorder' => 'transparent',
    'accentColor'        => 'color2',
    'accentText'         => 'textb',
    'trackBg'            => 'back2',
    'thumbBg'            => 'textw',
    'borderWidth'        => 1,
    'focusRing'          => 'color2',
    'focusOutline'       => false,
    'radius'             => 15,
    'touchSize'          => 44,
    'fontSize'           => 14,
    'width'              => '100%',
    'gap'                => 8,
    'fieldGap'           => 14,
    'boxPadding'         => 12,
    'thumbSize'          => 80,
    'hideScrollbar'      => false,
    'scrollbarColor'     => 'color2',
    'scrollbarWidth'     => 6,
    'shadow'             => 'soft',
    'blur'               => 10,
    'mobileSheet'        => true,
    'transition'         => 250,
  ];

  /** @var bool Indica si ya se cargó la configuración de theme.json en la petición actual */
  private static bool $themeLoaded = false;

  /** @var array<string, array<string, mixed>> Overrides locales por tipo cargados desde theme.json */
  private static array $themeLocalOverrides = [];

  /**
   * Devuelve los valores por defecto de configuración global.
   *
   * @return array<string, mixed>
   */
  public static function getDefaultConfig(): array
  {
    return [
      'preset'             => 'soft',
      'triggerBg'          => 'back1',
      'triggerText'        => 'texto',
      'triggerBorder'      => 'textc',
      'surfaceBg'          => 'back1',
      'surfaceText'        => 'texto',
      'surfaceMuted'       => 'text-inactive',
      'surfaceBorder'      => 'textc',
      'placeholderColor'   => 'text-inactive',
      'placeholderSize'    => 14,
      'secondaryText'      => 'text-inactive',
      'secondaryFontSize'  => 12,
      'btnPrimaryBg'       => 'back6',
      'btnPrimaryText'     => 'textw',
      'btnPrimaryBorder'   => 'transparent',
      'btnSecondaryBg'     => 'back2',
      'btnSecondaryText'   => 'texto',
      'btnSecondaryBorder' => 'transparent',
      'accentColor'        => 'color2',
      'accentText'         => 'textb',
      'trackBg'            => 'back2',
      'thumbBg'            => 'textw',
      'borderWidth'        => 1,
      'focusRing'          => 'color2',
      'focusOutline'       => false,
      'radius'             => 15,
      'touchSize'          => 44,
      'fontSize'           => 14,
      'width'              => '100%',
      'gap'                => 8,
      'fieldGap'           => 14,
      'boxPadding'         => 12,
      'thumbSize'          => 80,
      'hideScrollbar'      => false,
      'scrollbarColor'     => 'color2',
      'scrollbarWidth'     => 6,
      'shadow'             => 'soft',
      'blur'               => 10,
      'mobileSheet'        => true,
      'transition'         => 250,
    ];
  }

  /**
   * Presets visuales preconfigurados para UI Studio y proyectos.
   *
   * @return array<string, array<string, mixed>>
   */
  public static function getPresets(): array
  {
    return [
      'soft' => [
        'label'              => 'Suave (Tema)',
        'preset'             => 'soft',
        'triggerBg'          => 'back1',
        'triggerText'        => 'texto',
        'triggerBorder'      => 'textc',
        'surfaceBg'          => 'back1',
        'surfaceText'        => 'texto',
        'surfaceMuted'       => 'text-inactive',
        'surfaceBorder'      => 'textc',
        'placeholderColor'   => 'text-inactive',
        'placeholderSize'    => 14,
        'secondaryText'      => 'text-inactive',
        'secondaryFontSize'  => 12,
        'btnPrimaryBg'       => 'back6',
        'btnPrimaryText'     => 'textw',
        'btnPrimaryBorder'   => 'transparent',
        'btnSecondaryBg'     => 'back2',
        'btnSecondaryText'   => 'texto',
        'btnSecondaryBorder' => 'transparent',
        'accentColor'        => 'color2',
        'accentText'         => 'textb',
        'trackBg'            => 'back2',
        'thumbBg'            => 'textw',
        'borderWidth'        => 1,
        'focusRing'          => 'color2',
        'focusOutline'       => false,
        'radius'             => 15,
        'touchSize'          => 44,
        'fontSize'           => 14,
        'width'              => '100%',
        'gap'                => 8,
        'fieldGap'           => 14,
        'boxPadding'         => 12,
        'thumbSize'          => 80,
        'hideScrollbar'      => false,
        'scrollbarColor'     => 'color2',
        'scrollbarWidth'     => 6,
        'shadow'             => 'soft',
        'blur'               => 10,
        'mobileSheet'        => true,
      ],
      'minimal' => [
        'label'              => 'Minimalista',
        'preset'             => 'minimal',
        'triggerBg'          => 'body',
        'triggerText'        => 'texto',
        'triggerBorder'      => 'border2',
        'surfaceBg'          => 'body',
        'surfaceText'        => 'texto',
        'surfaceMuted'       => 'text-inactive',
        'surfaceBorder'      => 'border2',
        'placeholderColor'   => 'text-inactive',
        'placeholderSize'    => 14,
        'secondaryText'      => 'text-inactive',
        'secondaryFontSize'  => 12,
        'btnPrimaryBg'       => 'back8',
        'btnPrimaryText'     => 'textw',
        'btnPrimaryBorder'   => 'transparent',
        'btnSecondaryBg'     => 'transparent',
        'btnSecondaryText'   => 'texto',
        'btnSecondaryBorder' => 'border2',
        'accentColor'        => 'color8',
        'accentText'         => 'textw',
        'trackBg'            => 'back1',
        'thumbBg'            => 'textw',
        'borderWidth'        => 1,
        'focusRing'          => 'color8',
        'focusOutline'       => false,
        'radius'             => 10,
        'touchSize'          => 44,
        'fontSize'           => 14,
        'width'              => '100%',
        'gap'                => 6,
        'fieldGap'           => 12,
        'boxPadding'         => 10,
        'thumbSize'          => 72,
        'hideScrollbar'      => false,
        'scrollbarColor'     => 'color8',
        'scrollbarWidth'     => 5,
        'shadow'             => 'none',
        'blur'               => 0,
        'mobileSheet'        => true,
      ],
      'glass' => [
        'label'              => 'Glassmorphism',
        'preset'             => 'glass',
        'triggerBg'          => 'back1',
        'triggerText'        => 'texto',
        'triggerBorder'      => 'border2',
        'surfaceBg'          => 'back1',
        'surfaceText'        => 'texto',
        'surfaceMuted'       => 'text-inactive',
        'surfaceBorder'      => 'border2',
        'placeholderColor'   => 'text-inactive',
        'placeholderSize'    => 14,
        'secondaryText'      => 'text-inactive',
        'secondaryFontSize'  => 12,
        'btnPrimaryBg'       => 'back5',
        'btnPrimaryText'     => 'textw',
        'btnPrimaryBorder'   => 'transparent',
        'btnSecondaryBg'     => 'back2',
        'btnSecondaryText'   => 'texto',
        'btnSecondaryBorder' => 'transparent',
        'accentColor'        => 'color5',
        'accentText'         => 'textw',
        'trackBg'            => 'back2',
        'thumbBg'            => 'textw',
        'borderWidth'        => 1,
        'focusRing'          => 'color5',
        'focusOutline'       => false,
        'radius'             => 18,
        'touchSize'          => 44,
        'fontSize'           => 14,
        'width'              => '100%',
        'gap'                => 8,
        'fieldGap'           => 14,
        'boxPadding'         => 14,
        'thumbSize'          => 84,
        'hideScrollbar'      => false,
        'scrollbarColor'     => 'color5',
        'scrollbarWidth'     => 6,
        'shadow'             => 'lg',
        'blur'               => 16,
        'mobileSheet'        => true,
      ],
      'pill' => [
        'label'              => 'Pill Redondeado',
        'preset'             => 'pill',
        'triggerBg'          => 'back2',
        'triggerText'        => 'texto',
        'triggerBorder'      => 'transparent',
        'surfaceBg'          => 'back1',
        'surfaceText'        => 'texto',
        'surfaceMuted'       => 'text-inactive',
        'surfaceBorder'      => 'textc',
        'placeholderColor'   => 'text-inactive',
        'placeholderSize'    => 15,
        'secondaryText'      => 'text-inactive',
        'secondaryFontSize'  => 12,
        'btnPrimaryBg'       => 'back-success',
        'btnPrimaryText'     => 'textw',
        'btnPrimaryBorder'   => 'transparent',
        'btnSecondaryBg'     => 'back2',
        'btnSecondaryText'   => 'texto',
        'btnSecondaryBorder' => 'transparent',
        'accentColor'        => 'color6',
        'accentText'         => 'textw',
        'trackBg'            => 'back3',
        'thumbBg'            => 'textw',
        'borderWidth'        => 0,
        'focusRing'          => 'color6',
        'focusOutline'       => false,
        'radius'             => 24,
        'touchSize'          => 48,
        'fontSize'           => 15,
        'width'              => '100%',
        'gap'                => 10,
        'fieldGap'           => 16,
        'boxPadding'         => 14,
        'thumbSize'          => 84,
        'hideScrollbar'      => false,
        'scrollbarColor'     => 'color6',
        'scrollbarWidth'     => 6,
        'shadow'             => 'md',
        'blur'               => 8,
        'mobileSheet'        => true,
      ],
      'elevated' => [
        'label'              => 'Contraste Alto',
        'preset'             => 'elevated',
        'triggerBg'          => 'back8',
        'triggerText'        => 'textw',
        'triggerBorder'      => 'border8',
        'surfaceBg'          => '#111827',
        'surfaceText'        => '#f8fafc',
        'surfaceMuted'       => '#94a3b8',
        'surfaceBorder'      => '#334155',
        'placeholderColor'   => '#94a3b8',
        'placeholderSize'    => 15,
        'secondaryText'      => '#94a3b8',
        'secondaryFontSize'  => 12,
        'btnPrimaryBg'       => '#2563eb',
        'btnPrimaryText'     => '#ffffff',
        'btnPrimaryBorder'   => 'transparent',
        'btnSecondaryBg'     => '#1e293b',
        'btnSecondaryText'   => '#e2e8f0',
        'btnSecondaryBorder' => '#475569',
        'accentColor'        => '#38bdf8',
        'accentText'         => '#0f172a',
        'trackBg'            => '#334155',
        'thumbBg'            => '#ffffff',
        'borderWidth'        => 1,
        'focusRing'          => '#38bdf8',
        'focusOutline'       => true,
        'radius'             => 14,
        'touchSize'          => 44,
        'fontSize'           => 15,
        'width'              => '100%',
        'gap'                => 8,
        'fieldGap'           => 16,
        'boxPadding'         => 14,
        'thumbSize'          => 84,
        'hideScrollbar'      => false,
        'scrollbarColor'     => '#38bdf8',
        'scrollbarWidth'     => 6,
        'shadow'             => 'lg',
        'blur'               => 12,
        'mobileSheet'        => true,
      ],
    ];
  }

  /**
   * Carga automáticamente la configuración de inputs (`inputs` y `localOverrides`) del tema activo
   * en `App/Config/theme.json` si no se ha configurado manualmente en esta petición.
   *
   * @return void
   */
  public static function ensureThemeConfigLoaded(): void
  {
    if (self::$themeLoaded) {
      return;
    }
    self::$themeLoaded = true;

    if (!class_exists(ThemeModule::class)) {
      return;
    }

    try {
      $jsonPath = ThemeModule::getThemeJsonPath();
      if (!is_file($jsonPath)) {
        return;
      }
      $themeData = ThemeModule::loadConfig($jsonPath);
      if (isset($themeData['inputs']) && is_array($themeData['inputs'])) {
        $inputsCfg = $themeData['inputs'];
        if (isset($inputsCfg['localOverrides']) && is_array($inputsCfg['localOverrides'])) {
          self::$themeLocalOverrides = $inputsCfg['localOverrides'];
        }
        unset($inputsCfg['localOverrides']);
        self::configStyle($inputsCfg);
      }
    } catch (\Throwable $e) {
      // Silencioso para no interrumpir vistas si theme.json aún no existe o es ilegible
    }
  }

  /**
   * Configura las propiedades de estilo globales para todos los componentes de formulario.
   *
   * @param array<string, mixed> $config Opciones globales de diseño (--ui-*).
   * @return void
   */
  public static function configStyle(array $config = []): void
  {
    self::$themeLoaded = true;

    $stringKeys = [
      'preset',
      'triggerBg',
      'triggerText',
      'triggerBorder',
      'surfaceBg',
      'surfaceText',
      'surfaceMuted',
      'surfaceBorder',
      'placeholderColor',
      'secondaryText',
      'btnPrimaryBg',
      'btnPrimaryText',
      'btnPrimaryBorder',
      'btnSecondaryBg',
      'btnSecondaryText',
      'btnSecondaryBorder',
      'accentColor',
      'accentText',
      'trackBg',
      'thumbBg',
      'scrollbarColor',
      'focusRing',
      'shadow',
    ];

    foreach ($stringKeys as $key) {
      if (isset($config[$key]) && is_string($config[$key]) && trim($config[$key]) !== '') {
        self::$globalConfig[$key] = trim($config[$key]);
      }
    }

    if (isset($config['width'])) {
      self::$globalConfig['width'] = self::sanitizeCssDimension($config['width'], '100%');
    }

    if (isset($config['borderWidth']) && is_numeric($config['borderWidth'])) {
      self::$globalConfig['borderWidth'] = max(0, min(6, (int)$config['borderWidth']));
    }

    if (isset($config['focusOutline'])) {
      $parsedOutline = filter_var($config['focusOutline'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
      self::$globalConfig['focusOutline'] = $parsedOutline ?? (bool)$config['focusOutline'];
    }

    if (isset($config['radius']) && is_numeric($config['radius'])) {
      self::$globalConfig['radius'] = max(0, min(40, (int)$config['radius']));
    }

    if (isset($config['touchSize']) && is_numeric($config['touchSize'])) {
      self::$globalConfig['touchSize'] = max(32, min(64, (int)$config['touchSize']));
    }

    if (isset($config['fontSize']) && is_numeric($config['fontSize'])) {
      self::$globalConfig['fontSize'] = max(10, min(32, (int)$config['fontSize']));
    }

    if (isset($config['placeholderSize']) && is_numeric($config['placeholderSize'])) {
      self::$globalConfig['placeholderSize'] = max(10, min(28, (int)$config['placeholderSize']));
    }

    if (isset($config['secondaryFontSize']) && is_numeric($config['secondaryFontSize'])) {
      self::$globalConfig['secondaryFontSize'] = max(9, min(24, (int)$config['secondaryFontSize']));
    }

    if (isset($config['gap']) && is_numeric($config['gap'])) {
      self::$globalConfig['gap'] = max(0, min(32, (int)$config['gap']));
    }

    if (isset($config['fieldGap']) && is_numeric($config['fieldGap'])) {
      self::$globalConfig['fieldGap'] = max(4, min(40, (int)$config['fieldGap']));
    }

    if (isset($config['boxPadding']) && is_numeric($config['boxPadding'])) {
      self::$globalConfig['boxPadding'] = max(4, min(40, (int)$config['boxPadding']));
    }

    if (isset($config['thumbSize']) && is_numeric($config['thumbSize'])) {
      self::$globalConfig['thumbSize'] = max(48, min(160, (int)$config['thumbSize']));
    }

    if (isset($config['hideScrollbar'])) {
      $parsedHideScroll = filter_var($config['hideScrollbar'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
      self::$globalConfig['hideScrollbar'] = $parsedHideScroll ?? (bool)$config['hideScrollbar'];
    }

    if (isset($config['scrollbarWidth']) && is_numeric($config['scrollbarWidth'])) {
      self::$globalConfig['scrollbarWidth'] = max(2, min(14, (int)$config['scrollbarWidth']));
    }

    if (isset($config['blur']) && is_numeric($config['blur'])) {
      self::$globalConfig['blur'] = max(0, min(32, (int)$config['blur']));
    }

    if (isset($config['transition']) && is_numeric($config['transition'])) {
      self::$globalConfig['transition'] = max(0, min(2000, (int)$config['transition']));
    }

    if (isset($config['mobileSheet'])) {
      $parsed = filter_var($config['mobileSheet'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
      self::$globalConfig['mobileSheet'] = $parsed ?? (bool)$config['mobileSheet'];
    }

    if (isset($config['localOverrides']) && is_array($config['localOverrides'])) {
      self::$themeLocalOverrides = $config['localOverrides'];
    }
  }

  /**
   * Obtiene la configuración global actual.
   *
   * @return array<string, mixed>
   */
  public static function getGlobalConfig(): array
  {
    self::ensureThemeConfigLoaded();
    return self::$globalConfig;
  }

  /**
   * Restablece la configuración global a sus valores de fábrica.
   *
   * @return void
   */
  public static function resetConfig(): void
  {
    self::$globalConfig = self::getDefaultConfig();
    self::$themeLocalOverrides = [];
    self::$themeLoaded = false;
  }

  /**
   * Resuelve un token de color o clase del framework a su representación CSS y clase opcional.
   *
   * Acepta:
   * - Clases del framework: 'back1'..'back20', 'color1'..'color20', 'border1'..'border20',
   *   'texto', 'textc', 'textw', 'textb', 'text-inactive', 'body', 'back-success', 'color-success', etc.
   * - Variables CSS: '--back-color5', 'var(--back-color5)'
   * - Colores directos: '#38bdf8', '38bdf8', 'rgb(...)', 'oklch(...)', 'transparent'
   *
   * @param string $raw Token de color o clase provisto por el usuario.
   * @return array{class: string, cssValue: string}
   */
  public static function resolveColorToken(string $raw): array
  {
    $raw = ltrim(trim($raw), '.');
    if ($raw === '') {
      return ['class' => '', 'cssValue' => 'transparent'];
    }

    // Bloquear secuencias peligrosas en CSS
    if (preg_match('/[\x00-\x1F\x7F{};<>]|javascript\s*:|expression\s*\(|@import|url\s*\(/i', $raw)) {
      return ['class' => '', 'cssValue' => 'transparent'];
    }

    // 1. Clases de fondo/color/borde numeradas (.back1..N, .color1..N, .border1..N)
    if (preg_match('/^(back|color|border)(\d+)(-hover)?$/i', $raw, $m)) {
      $index = (int)$m[2];
      $isHover = !empty($m[3]);
      $varBase = $index <= 1 ? '--back-color' : "--back-color{$index}";
      $varName = $isHover ? "{$varBase}-hover" : $varBase;
      return [
        'class' => $raw,
        'cssValue' => "var({$varName}, #d3d3d3)",
      ];
    }

    // 2. Clases semánticas del tema
    $semanticMap = [
      'body'           => ['class' => 'body-back', 'cssValue' => 'var(--body, #ffffff)'],
      'body-back'      => ['class' => 'body-back', 'cssValue' => 'var(--body, #ffffff)'],
      'texto'          => ['class' => 'texto', 'cssValue' => 'var(--text-primary, #111827)'],
      'textc'          => ['class' => 'textc', 'cssValue' => 'var(--text-secondary, #f8fafc)'],
      'textw'          => ['class' => 'textw', 'cssValue' => '#ffffff'],
      'textb'          => ['class' => 'textb', 'cssValue' => '#000000'],
      'text-inactive'  => ['class' => 'inactive', 'cssValue' => 'var(--text-inactive, #9ca3af)'],
      'inactive'       => ['class' => 'inactive', 'cssValue' => 'var(--text-inactive, #9ca3af)'],
      'success'        => ['class' => 'back-success', 'cssValue' => 'var(--success, #00b900)'],
      'back-success'   => ['class' => 'back-success', 'cssValue' => 'var(--success, #00b900)'],
      'color-success'  => ['class' => 'color-success', 'cssValue' => 'var(--success, #00b900)'],
      'caution'        => ['class' => 'back-caution', 'cssValue' => 'var(--caution, #ff8c00)'],
      'back-caution'   => ['class' => 'back-caution', 'cssValue' => 'var(--caution, #ff8c00)'],
      'color-caution'  => ['class' => 'color-caution', 'cssValue' => 'var(--caution, #ff8c00)'],
      'danger'         => ['class' => 'back-danger', 'cssValue' => 'var(--danger, #ff0000)'],
      'back-danger'    => ['class' => 'back-danger', 'cssValue' => 'var(--danger, #ff0000)'],
      'color-danger'   => ['class' => 'color-danger', 'cssValue' => 'var(--danger, #ff0000)'],
      'back-none'      => ['class' => 'back-none', 'cssValue' => 'transparent'],
      'border-none'    => ['class' => 'border-none', 'cssValue' => 'transparent'],
      'outline-none'   => ['class' => 'outline-none', 'cssValue' => 'transparent'],
      'none'           => ['class' => '', 'cssValue' => 'transparent'],
      '0'              => ['class' => '', 'cssValue' => 'transparent'],
    ];

    $lower = strtolower($raw);
    if (isset($semanticMap[$lower])) {
      return $semanticMap[$lower];
    }

    // 3. Variables CSS explícitas
    if (str_starts_with($raw, '--')) {
      return ['class' => '', 'cssValue' => "var({$raw})"];
    }
    if (str_starts_with($lower, 'var(')) {
      return ['class' => '', 'cssValue' => $raw];
    }

    // 4. Colores hexadecimales
    if (preg_match('/^#?([a-f0-9]{3}|[a-f0-9]{4}|[a-f0-9]{6}|[a-f0-9]{8})$/i', $raw)) {
      $hex = str_starts_with($raw, '#') ? $raw : ('#' . $raw);
      return ['class' => '', 'cssValue' => $hex];
    }

    // 5. Funciones CSS modernas
    if (preg_match('/^(?:rgb|rgba|hsl|hsla|oklch|oklab|color-mix)\(/i', $raw)) {
      return ['class' => '', 'cssValue' => $raw];
    }

    // 6. Palabras clave CSS estándar
    $stdColors = ['transparent', 'currentcolor', 'inherit', 'white', 'black'];
    if (in_array($lower, $stdColors, true)) {
      return ['class' => '', 'cssValue' => $raw];
    }

    // 7. Identificador de clase personalizada del proyecto
    if (preg_match('/^[a-zA-Z0-9_-]+$/', $raw)) {
      return ['class' => $raw, 'cssValue' => 'currentColor'];
    }

    return ['class' => '', 'cssValue' => 'transparent'];
  }

  /**
   * Sanitiza una dimensión CSS segura (`100%`, `320px`, `24rem`, `auto`, `fit-content`).
   *
   * @param mixed $raw Valor crudo recibido.
   * @param string $fallback Valor seguro de respaldo.
   * @return string
   */
  public static function sanitizeCssDimension(mixed $raw, string $fallback = '100%'): string
  {
    $val = trim((string)$raw);
    if ($val === '') {
      return $fallback;
    }
    $lower = strtolower($val);
    if (in_array($lower, ['auto', 'fit-content', 'min-content', 'max-content'], true)) {
      return $lower;
    }
    if (preg_match('/^\d{1,4}(?:\.\d{1,2})?$/', $val)) {
      return $val . 'px';
    }
    if (preg_match('/^\d{1,4}(?:\.\d{1,2})?(?:%|px|rem|em|vw|vh|dvw|dvh|ch)$/i', $val)) {
      return $lower;
    }
    return $fallback;
  }

  /**
   * Construye el mapa de variables CSS `--ui-*` a partir de la configuración provista.
   *
   * @param array<string, mixed> $config Configuración combinada (global + local).
   * @return array<string, string> Mapa [nombreVar => valorCss]
   */
  public static function buildCssVarsMap(array $config): array
  {
    $cfg = array_merge(self::$globalConfig, $config);

    $shadowMap = [
      'none'   => 'none',
      'soft'   => '0 10px 30px rgba(0, 0, 0, 0.14)',
      'md'     => '0 14px 36px rgba(0, 0, 0, 0.20)',
      'medium' => '0 14px 36px rgba(0, 0, 0, 0.20)',
      'lg'     => '0 22px 50px rgba(0, 0, 0, 0.30)',
      'glass'  => '0 8px 32px rgba(0, 0, 0, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.16)',
    ];

    $triggerBg = self::resolveColorToken((string)$cfg['triggerBg'])['cssValue'];
    $triggerText = self::resolveColorToken((string)$cfg['triggerText'])['cssValue'];
    $triggerBorder = self::resolveColorToken((string)$cfg['triggerBorder'])['cssValue'];
    $surfaceBg = self::resolveColorToken((string)$cfg['surfaceBg'])['cssValue'];
    $surfaceText = self::resolveColorToken((string)$cfg['surfaceText'])['cssValue'];
    $surfaceMuted = self::resolveColorToken((string)$cfg['surfaceMuted'])['cssValue'];
    $surfaceBorder = self::resolveColorToken((string)$cfg['surfaceBorder'])['cssValue'];
    $placeholderColor = self::resolveColorToken((string)($cfg['placeholderColor'] ?? $cfg['surfaceMuted'] ?? 'text-inactive'))['cssValue'];
    $secondaryText = self::resolveColorToken((string)($cfg['secondaryText'] ?? $cfg['surfaceMuted'] ?? 'text-inactive'))['cssValue'];
    $btnPrimaryBg = self::resolveColorToken((string)$cfg['btnPrimaryBg'])['cssValue'];
    $btnPrimaryText = self::resolveColorToken((string)$cfg['btnPrimaryText'])['cssValue'];
    $btnPrimaryBorder = self::resolveColorToken((string)($cfg['btnPrimaryBorder'] ?? 'transparent'))['cssValue'];
    $btnSecondaryBg = self::resolveColorToken((string)$cfg['btnSecondaryBg'])['cssValue'];
    $btnSecondaryText = self::resolveColorToken((string)$cfg['btnSecondaryText'])['cssValue'];
    $btnSecondaryBorder = self::resolveColorToken((string)($cfg['btnSecondaryBorder'] ?? 'transparent'))['cssValue'];
    $accentColor = self::resolveColorToken((string)$cfg['accentColor'])['cssValue'];
    $accentText = self::resolveColorToken((string)$cfg['accentText'])['cssValue'];
    $trackBg = self::resolveColorToken((string)$cfg['trackBg'])['cssValue'];
    $thumbBg = self::resolveColorToken((string)($cfg['thumbBg'] ?? 'textw'))['cssValue'];
    $scrollbarColor = self::resolveColorToken((string)($cfg['scrollbarColor'] ?? $cfg['accentColor'] ?? 'color2'))['cssValue'];

    $borderWidth = max(0, min(6, (int)($cfg['borderWidth'] ?? 1)));
    $focusRingRaw = (string)($cfg['focusRing'] ?? 'color2');
    $focusRing = self::resolveColorToken($focusRingRaw)['cssValue'];
    $focusOutlineEnabled = filter_var($cfg['focusOutline'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $focusOutlineEnabled = $focusOutlineEnabled ?? (bool)($cfg['focusOutline'] ?? false);
    $focusOutlineCss = (!$focusOutlineEnabled || $focusRing === 'transparent' || strtolower(trim($focusRingRaw)) === 'none')
      ? 'none'
      : "2px solid {$focusRing}";

    $radius = max(0, min(40, (int)($cfg['radius'] ?? 15)));
    $touchSize = max(32, min(64, (int)($cfg['touchSize'] ?? 44)));
    $fontSize = max(10, min(32, (int)($cfg['fontSize'] ?? 14)));
    $placeholderSize = max(10, min(28, (int)($cfg['placeholderSize'] ?? $fontSize)));
    $secondaryFontSize = max(9, min(24, (int)($cfg['secondaryFontSize'] ?? 12)));
    $widthVal = self::sanitizeCssDimension($cfg['width'] ?? '100%', '100%');
    $gap = max(0, min(32, (int)($cfg['gap'] ?? 8)));
    $fieldGap = max(4, min(40, (int)($cfg['fieldGap'] ?? 14)));
    $boxPadding = max(4, min(40, (int)($cfg['boxPadding'] ?? 12)));
    $thumbSize = max(48, min(160, (int)($cfg['thumbSize'] ?? 80)));
    $hideScrollbar = filter_var($cfg['hideScrollbar'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $hideScrollbar = $hideScrollbar ?? (bool)($cfg['hideScrollbar'] ?? false);
    $scrollbarWidth = max(2, min(14, (int)($cfg['scrollbarWidth'] ?? 6)));
    $blur = max(0, min(32, (int)($cfg['blur'] ?? 10)));
    $transition = max(0, min(2000, (int)($cfg['transition'] ?? 250)));
    $shadowKey = strtolower((string)($cfg['shadow'] ?? 'soft'));
    $shadowVal = $shadowMap[$shadowKey] ?? $shadowMap['soft'];

    return [
      '--ui-trigger-bg'           => $triggerBg,
      '--ui-trigger-text'         => $triggerText,
      '--ui-trigger-border'       => $triggerBorder,
      '--ui-surface-bg'           => $surfaceBg,
      '--ui-surface-text'         => $surfaceText,
      '--ui-surface-muted'        => $surfaceMuted,
      '--ui-surface-border'       => $surfaceBorder,
      '--ui-surface-shadow'       => $shadowVal,
      '--ui-placeholder-color'    => $placeholderColor,
      '--ui-placeholder-size'     => "{$placeholderSize}px",
      '--ui-secondary-text'       => $secondaryText,
      '--ui-secondary-font-size'  => "{$secondaryFontSize}px",
      '--ui-btn-primary-bg'       => $btnPrimaryBg,
      '--ui-btn-primary-text'     => $btnPrimaryText,
      '--ui-btn-primary-border'   => $btnPrimaryBorder,
      '--ui-btn-secondary-bg'     => $btnSecondaryBg,
      '--ui-btn-secondary-text'   => $btnSecondaryText,
      '--ui-btn-secondary-border' => $btnSecondaryBorder,
      '--ui-accent'               => $accentColor,
      '--ui-accent-text'          => $accentText,
      '--ui-track-bg'             => $trackBg,
      '--ui-thumb-bg'             => $thumbBg,
      '--ui-border-width'         => "{$borderWidth}px",
      '--ui-focus-ring'           => $focusRing,
      '--ui-focus-outline'        => $focusOutlineCss,
      '--ui-radius'               => "{$radius}px",
      '--ui-touch-size'           => "{$touchSize}px",
      '--ui-font-size'            => "{$fontSize}px",
      '--ui-width'                => $widthVal,
      '--ui-gap'                  => "{$gap}px",
      '--ui-field-gap'            => "{$fieldGap}px",
      '--ui-box-padding'          => "{$boxPadding}px",
      '--ui-thumb-size'           => "{$thumbSize}px",
      '--ui-scrollbar-display'    => $hideScrollbar ? 'none' : 'block',
      '--ui-scrollbar-color'      => $scrollbarColor,
      '--ui-scrollbar-width'      => "{$scrollbarWidth}px",
      '--ui-overlay-blur'         => "{$blur}px",
      '--ui-transition'           => "{$transition}ms",
    ];
  }

  /**
   * Genera declaraciones CSS para variables `--ui-*` dentro de `_configTheme.css` o `<style>`.
   *
   * @param array<string, mixed> $config Configuración de inputs del tema.
   * @param string $indent Sangría por línea.
   * @return string Bloque de variables CSS sin llaves envolventes.
   */
  public static function compileThemeCssVars(array $config, string $indent = "  "): string
  {
    $vars = self::buildCssVarsMap($config);
    $lines = [];
    foreach ($vars as $varName => $val) {
      $lines[] = "{$indent}{$varName}: {$val};";
    }
    return implode("\n", $lines);
  }

  /**
   * Escapa un valor para atributos HTML seguros.
   *
   * @param mixed $value Valor a escapar.
   * @return string
   */
  private static function esc(mixed $value): string
  {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }

  /**
   * Sanitiza una lista de clases CSS separadas por espacio.
   *
   * @param string $raw Clases crudas.
   * @return string
   */
  private static function sanitizeClasses(string $raw): string
  {
    $tokens = preg_split('/\s+/', trim($raw)) ?: [];
    $clean = [];
    foreach ($tokens as $tok) {
      if ($tok !== '' && preg_match('/^[a-zA-Z0-9_:\[\]\.-]+$/', $tok)) {
        $clean[] = $tok;
      }
    }
    return implode(' ', array_unique($clean));
  }

  /**
   * Construye el atributo `style="--ui-..."` cuando existen sobrescrituras locales
   * o se solicita emisión explícita para HTML autocontenido.
   *
   * @param array<string, mixed> $params Parámetros locales del componente.
   * @param bool $alwaysEmit Si true, emite todas las variables --ui-* resueltas inline.
   * @return string Cadena lista para atributo style (sin `style=""` si está vacía).
   */
  private static function buildInlineStyleVars(array $params, bool $alwaysEmit = false): string
  {
    $keyToCssVar = [
      'triggerBg'          => ['--ui-trigger-bg'],
      'triggerText'        => ['--ui-trigger-text'],
      'triggerBorder'      => ['--ui-trigger-border'],
      'surfaceBg'          => ['--ui-surface-bg'],
      'surfaceText'        => ['--ui-surface-text'],
      'surfaceMuted'       => ['--ui-surface-muted'],
      'surfaceBorder'      => ['--ui-surface-border'],
      'placeholderColor'   => ['--ui-placeholder-color'],
      'placeholderSize'    => ['--ui-placeholder-size'],
      'secondaryText'      => ['--ui-secondary-text'],
      'secondaryFontSize'  => ['--ui-secondary-font-size'],
      'btnPrimaryBg'       => ['--ui-btn-primary-bg'],
      'btnPrimaryText'     => ['--ui-btn-primary-text'],
      'btnPrimaryBorder'   => ['--ui-btn-primary-border'],
      'btnSecondaryBg'     => ['--ui-btn-secondary-bg'],
      'btnSecondaryText'   => ['--ui-btn-secondary-text'],
      'btnSecondaryBorder' => ['--ui-btn-secondary-border'],
      'accentColor'        => ['--ui-accent'],
      'accentText'         => ['--ui-accent-text'],
      'trackBg'            => ['--ui-track-bg'],
      'thumbBg'            => ['--ui-thumb-bg'],
      'borderWidth'        => ['--ui-border-width'],
      'focusRing'          => ['--ui-focus-ring', '--ui-focus-outline'],
      'focusOutline'       => ['--ui-focus-outline'],
      'radius'             => ['--ui-radius'],
      'touchSize'          => ['--ui-touch-size'],
      'fontSize'           => ['--ui-font-size'],
      'width'              => ['--ui-width'],
      'gap'                => ['--ui-gap'],
      'fieldGap'           => ['--ui-field-gap'],
      'boxPadding'         => ['--ui-box-padding'],
      'thumbSize'          => ['--ui-thumb-size'],
      'hideScrollbar'      => ['--ui-scrollbar-display'],
      'scrollbarColor'     => ['--ui-scrollbar-color'],
      'scrollbarWidth'     => ['--ui-scrollbar-width'],
      'shadow'             => ['--ui-surface-shadow'],
      'blur'               => ['--ui-overlay-blur'],
      'transition'         => ['--ui-transition'],
    ];

    $presentVars = [];
    foreach ($keyToCssVar as $paramKey => $cssVars) {
      if (array_key_exists($paramKey, $params) && $params[$paramKey] !== null && $params[$paramKey] !== '') {
        foreach ($cssVars as $cv) {
          $presentVars[$cv] = true;
        }
      }
    }

    $microVars = [];
    if (isset($params['chipRadius']) && is_numeric($params['chipRadius'])) {
      $microVars['--ui-chip-radius'] = max(0, min(999, (int)$params['chipRadius'])) . 'px';
    }
    if (isset($params['starSize']) && is_numeric($params['starSize'])) {
      $microVars['--ui-star-size'] = max(14, min(48, (int)$params['starSize'])) . 'px';
    }
    if (!empty($params['removeBg']) && is_string($params['removeBg'])) {
      $microVars['--ui-remove-bg'] = self::resolveColorToken($params['removeBg'])['cssValue'];
    }
    if (!empty($params['removeColor']) && is_string($params['removeColor'])) {
      $microVars['--ui-remove-color'] = self::resolveColorToken($params['removeColor'])['cssValue'];
    }
    if (!empty($params['removeBorder']) && is_string($params['removeBorder'])) {
      $microVars['--ui-remove-border'] = self::resolveColorToken($params['removeBorder'])['cssValue'];
    }
    if (isset($params['dropzoneMinHeight']) && is_numeric($params['dropzoneMinHeight'])) {
      $microVars['--ui-dropzone-min-height'] = max(80, min(400, (int)$params['dropzoneMinHeight'])) . 'px';
    }
    if (!empty($params['dropzoneBorderStyle']) && is_string($params['dropzoneBorderStyle'])) {
      $bs = strtolower(trim($params['dropzoneBorderStyle']));
      if (in_array($bs, ['dashed', 'solid', 'dotted'], true)) {
        $microVars['--ui-dropzone-border-style'] = $bs;
      }
    }
    if (isset($params['dropzoneIconSize']) && is_numeric($params['dropzoneIconSize'])) {
      $microVars['--ui-dropzone-icon-size'] = max(20, min(64, (int)$params['dropzoneIconSize'])) . 'px';
    }
    if (isset($params['sliderTrackHeight']) && is_numeric($params['sliderTrackHeight'])) {
      $microVars['--ui-slider-track-height'] = max(4, min(24, (int)$params['sliderTrackHeight'])) . 'px';
    }
    if (isset($params['sliderThumbSize']) && is_numeric($params['sliderThumbSize'])) {
      $microVars['--ui-slider-thumb-size'] = max(14, min(40, (int)$params['sliderThumbSize'])) . 'px';
    }
    if (isset($params['otpCellSize']) && is_numeric($params['otpCellSize'])) {
      $microVars['--ui-otp-cell-size'] = max(32, min(72, (int)$params['otpCellSize'])) . 'px';
    }
    if (isset($params['maxHeight']) && is_numeric($params['maxHeight'])) {
      $microVars['--ui-tp-max-height'] = max(100, min(420, (int)$params['maxHeight'])) . 'px';
    }

    if (!$alwaysEmit && empty($presentVars) && empty($microVars)) {
      return '';
    }

    $vars = self::buildCssVarsMap($params);
    $parts = [];
    foreach ($vars as $k => $v) {
      if ($alwaysEmit || isset($presentVars[$k])) {
        $parts[] = "{$k}: {$v}";
      }
    }
    foreach ($microVars as $k => $v) {
      $parts[] = "{$k}: {$v}";
    }
    return implode('; ', $parts) . ';';
  }

  /**
   * Resuelve atributos comunes de estilo por zona (`ui-*`) a partir de parámetros.
   *
   * @param array<string, mixed> $params Parámetros locales.
   * @param bool $inlineVars Emitir variables `--ui-*` inline.
   * @return array<string, string> Mapa de atributos HTML clave => valor.
   */
  private static function buildZoneAttributes(array $params, bool $inlineVars = false): array
  {
    $attrs = [];

    if (!empty($params['uiTrigger']) && is_string($params['uiTrigger'])) {
      $attrs['ui-trigger'] = self::sanitizeClasses($params['uiTrigger']);
    }
    if (!empty($params['uiSurface']) && is_string($params['uiSurface'])) {
      $attrs['ui-surface'] = self::sanitizeClasses($params['uiSurface']);
    }
    if (!empty($params['uiBtnPrimary']) && is_string($params['uiBtnPrimary'])) {
      $attrs['ui-btn-primary'] = self::sanitizeClasses($params['uiBtnPrimary']);
    }
    if (!empty($params['uiBtnSecondary']) && is_string($params['uiBtnSecondary'])) {
      $attrs['ui-btn-secondary'] = self::sanitizeClasses($params['uiBtnSecondary']);
    }
    if (!empty($params['uiAccent']) && is_string($params['uiAccent'])) {
      $attrs['ui-accent'] = self::sanitizeClasses($params['uiAccent']);
    }
    if (!empty($params['uiTrack']) && is_string($params['uiTrack'])) {
      $attrs['ui-track'] = self::sanitizeClasses($params['uiTrack']);
    }

    // Retrocompatibilidad explícita si el usuario pasó atributos clásicos
    if (!empty($params['boxImage']) && is_string($params['boxImage'])) {
      $attrs['box-image'] = self::sanitizeClasses($params['boxImage']);
    }
    if (!empty($params['boxBtnImage']) && is_string($params['boxBtnImage'])) {
      $attrs['box-btn-image'] = self::sanitizeClasses($params['boxBtnImage']);
    }
    if (!empty($params['styleBox']) && is_string($params['styleBox'])) {
      $attrs['style-box'] = self::sanitizeClasses($params['styleBox']);
    }
    if (!empty($params['styleColor']) && is_string($params['styleColor'])) {
      $attrs['style-color'] = self::sanitizeClasses($params['styleColor']);
    }
    if (!empty($params['dataNote']) && is_string($params['dataNote'])) {
      $attrs['data-note'] = self::sanitizeClasses($params['dataNote']);
    }

    if (array_key_exists('mobileSheet', $params)) {
      $ms = filter_var($params['mobileSheet'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool)$params['mobileSheet'];
      $attrs['ui-mobile-sheet'] = $ms ? 'true' : 'false';
    }

    if (array_key_exists('hideScrollbar', $params) && $params['hideScrollbar'] !== null && $params['hideScrollbar'] !== '') {
      $hs = filter_var($params['hideScrollbar'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool)$params['hideScrollbar'];
      $attrs['data-hide-scrollbar'] = $hs ? 'true' : 'false';
    }

    $inline = self::buildInlineStyleVars($params, $inlineVars);
    if ($inline !== '') {
      $attrs['style'] = $inline;
    }

    return $attrs;
  }

  /**
   * Serializa un mapa de atributos HTML con sangría limpia.
   *
   * @param array<string, mixed> $attrs Atributos clave => valor.
   * @param bool $multiline Si true, imprime un atributo por línea.
   * @param string $indent Sangría cuando es multilínea.
   * @return string
   */
  private static function renderAttrsString(array $attrs, bool $multiline = false, string $indent = '  '): string
  {
    $parts = [];
    foreach ($attrs as $key => $val) {
      if ($val === false || $val === null || $val === '') {
        continue;
      }
      $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$key);
      if ($safeKey === '') {
        continue;
      }
      if ($val === true) {
        $parts[] = $safeKey;
      } else {
        $parts[] = $safeKey . '="' . self::esc($val) . '"';
      }
    }

    if (empty($parts)) {
      return '';
    }

    if ($multiline) {
      return "\n{$indent}" . implode("\n{$indent}", $parts);
    }

    return ' ' . implode(' ', $parts);
  }

  /**
   * Renderiza cualquier componente de formulario soportado.
   *
   * @param string $type Nombre del componente (ej: 'cropImage', 'colorPicker', 'datePicker').
   * @param array<string, mixed> $params Parámetros de estilo y comportamiento.
   * @return string HTML listo para imprimir en vista.
   */
  public static function render(string $type, array $params = []): string
  {
    if (!in_array($type, self::COMPONENT_TYPES, true)) {
      return "<!-- FormUiModule: Component '{$type}' not supported -->";
    }

    self::ensureThemeConfigLoaded();

    if (empty($params['ignoreThemeLocal']) && !empty(self::$themeLocalOverrides[$type]) && is_array(self::$themeLocalOverrides[$type])) {
      $params = array_merge(self::$themeLocalOverrides[$type], $params);
    }

    $inlineVars = !empty($params['inlineVars']);
    $multiline = !empty($params['multiline']);
    $wrapContainer = !empty($params['wrapContainer']);
    $wrapperClassCustom = !empty($params['wrapperClass']) && is_string($params['wrapperClass'])
      ? self::sanitizeClasses($params['wrapperClass'])
      : '';
    $wrapperClass = $wrapperClassCustom !== '' ? $wrapperClassCustom : 'br15 p15 ui-field-card';
    $labelText = isset($params['label']) && is_string($params['label']) ? trim($params['label']) : '';
    $labelClassExtra = !empty($params['labelClass']) && is_string($params['labelClass'])
      ? self::sanitizeClasses($params['labelClass'])
      : '';
    $helperText = isset($params['helperText']) && is_string($params['helperText']) ? trim($params['helperText']) : '';
    $helperClassExtra = !empty($params['helperClass']) && is_string($params['helperClass'])
      ? self::sanitizeClasses($params['helperClass'])
      : '';
    $showWhen = isset($params['showWhen']) && is_string($params['showWhen']) ? trim($params['showWhen']) : '';
    $hasWrapper = $wrapContainer || $labelText !== '' || $helperText !== '' || $showWhen !== '';

    $zoneAttrs = self::buildZoneAttributes($params, $inlineVars);
    $name = trim((string)($params['name'] ?? self::defaultNameForType($type)));
    $id = trim((string)($params['previewId'] ?? $params['id'] ?? ''));
    $labelForId = $id;
    $placeholder = (string)($params['placeholder'] ?? self::defaultPlaceholderForType($type));
    $value = (string)($params['value'] ?? self::defaultValueForType($type));
    $extraClass = isset($params['class']) && is_string($params['class']) ? self::sanitizeClasses($params['class']) : '';
    $required = !empty($params['required']);
    $disabled = !empty($params['disabled']);
    $readonly = !empty($params['readonly']);
    $autoSubmit = !empty($params['autoSubmit']);
    $processAutoSubmit = !empty($params['processAutoSubmit']);
    $noAutoSubmit = !empty($params['noAutoSubmit']);
    $indent = $hasWrapper ? '    ' : '  ';

    $transversalAttrs = [
      'data-no-auto-submit' => $noAutoSubmit ? 'true' : null,
      'data-show-when' => (!$hasWrapper && $showWhen !== '') ? $showWhen : null,
    ];

    $html = '';

    switch ($type) {
      case 'cropImage':
        $classes = trim("selectAndCropImage ui-input-component {$extraClass}");
        if (!empty($params['noPreview'])) {
          $classes .= ' no-preview';
        }
        if ($autoSubmit || $processAutoSubmit) {
          $classes .= ' process-auto-submit';
        }
        $attrs = array_merge([
          'type' => 'file',
          'name' => $name,
          'id' => $id ?: null,
          'accept' => (string)($params['accept'] ?? 'image/*'),
          'class' => $classes,
          'placeholder' => $placeholder,
          'cropping-size' => (string)($params['croppingSize'] ?? '500x500'),
          'crop-shape' => (string)($params['cropShape'] ?? 'square'),
          'data-rotate' => !empty($params['allowRotate']) ? 'true' : null,
          'data-title' => !empty($params['modalTitle']) ? (string)$params['modalTitle'] : null,
          'data-save-label' => !empty($params['saveLabel']) ? (string)$params['saveLabel'] : null,
          'data-cancel-label' => !empty($params['cancelLabel']) ? (string)$params['cancelLabel'] : null,
          'required' => $required,
          'disabled' => $disabled,
        ], $transversalAttrs, $zoneAttrs);
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'loadImgModern':
        $classes = trim("loadImgModern ui-input-component {$extraClass}");
        if ($autoSubmit || $processAutoSubmit) {
          $classes .= ' auto-submit';
        }
        $attrs = array_merge([
          'type' => 'file',
          'name' => $name,
          'id' => $id ?: null,
          'accept' => (string)($params['accept'] ?? 'image/*'),
          'multiple' => !isset($params['multiple']) || (bool)$params['multiple'],
          'class' => $classes,
          'placeholder' => $placeholder,
          'data-dropzone' => !empty($params['dropzone']) ? 'true' : null,
          'data-max-files' => !empty($params['maxFiles']) ? (int)$params['maxFiles'] : null,
          'data-max-size' => !empty($params['maxSizeMb']) ? (float)$params['maxSizeMb'] : null,
          'required' => $required,
          'disabled' => $disabled,
        ], $transversalAttrs, $zoneAttrs);
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'colorPicker':
        $classes = trim("color-picker ui-input-component {$extraClass}");
        $attrs = array_merge([
          'type' => 'color',
          'name' => $name,
          'id' => $id ?: null,
          'value' => $value !== '' ? $value : '#3B82F6',
          'class' => $classes,
          'data-alpha' => !empty($params['showAlpha']) ? 'true' : null,
          'data-swatches' => !empty($params['swatches']) && is_string($params['swatches']) ? $params['swatches'] : null,
          'data-default-color' => !empty($params['defaultColor']) && is_string($params['defaultColor']) ? $params['defaultColor'] : null,
          'disabled' => $disabled,
        ], $transversalAttrs, $zoneAttrs);
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'checkboxSwitch':
        $classes = trim("checkbox-switch ui-input-component {$extraClass}");
        $checkedVal = (string)($params['onValue'] ?? 'true');
        $uncheckedVal = (string)($params['offValue'] ?? 'false');
        $isActive = !isset($params['checked']) || (bool)$params['checked'];
        $attrs = array_merge([
          'type' => 'checkbox',
          'name' => $name,
          'id' => $id ?: null,
          'class' => $classes,
          'data-option' => "{$checkedVal},{$uncheckedVal}",
          'active' => $isActive ? '1' : '2',
          'ui-size' => (string)($params['size'] ?? 'md'),
          'data-label-on' => !empty($params['labelOn']) ? (string)$params['labelOn'] : null,
          'data-label-off' => !empty($params['labelOff']) ? (string)$params['labelOff'] : null,
          'disabled' => $disabled,
        ], $transversalAttrs, $zoneAttrs);
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'dropdown':
        $classes = trim("dropdown-select ui-input-component {$extraClass}");
        if (!empty($params['animated'])) {
          $classes .= ' animated';
        }
        $rawOptions = $params['options'] ?? null;
        if (is_string($rawOptions) && trim($rawOptions) !== '') {
          $options = self::parseOptionsString($rawOptions);
        } elseif (is_array($rawOptions) && !empty($rawOptions)) {
          $options = $rawOptions;
        } else {
          $options = [
            ['value' => 'es', 'label' => 'España'],
            ['value' => 'mx', 'label' => 'México'],
            ['value' => 'ar', 'label' => 'Argentina'],
            ['value' => 'cl', 'label' => 'Chile'],
            ['value' => 'co', 'label' => 'Colombia'],
          ];
        }
        $attrs = array_merge([
          'class' => $classes,
          'id' => $id ?: null,
          'data-name' => $name,
          'data-searchable' => (!isset($params['searchable']) || $params['searchable']) ? 'true' : 'false',
          'data-clearable' => !empty($params['clearable']) ? 'true' : null,
          'data-placeholder' => $placeholder,
          'data-max-height' => !empty($params['maxHeight']) ? max(100, min(500, (int)$params['maxHeight'])) : null,
          'data-empty-text' => !empty($params['emptyText']) ? (string)$params['emptyText'] : null,
          'data-required' => $required ? 'true' : null,
        ], $transversalAttrs, $zoneAttrs);

        $optIndent = $hasWrapper ? '      ' : '    ';
        $optLines = [];
        if (!isset($params['searchable']) || $params['searchable']) {
          $searchPh = !empty($params['searchPlaceholder']) ? (string)$params['searchPlaceholder'] : 'Buscar...';
          $optLines[] = "{$optIndent}<input type=\"text\" class=\"dropdown-search\" placeholder=\"" . self::esc($searchPh) . "\">";
        }
        $selectedLabel = '';
        foreach ($options as $opt) {
          $optValRaw = (string)($opt['value'] ?? '');
          $optLblRaw = (string)($opt['label'] ?? $optValRaw);
          $optVal = self::esc($optValRaw);
          $optLbl = self::esc($optLblRaw);
          $isSelected = ($value !== '' && $value === $optValRaw);
          if ($isSelected) {
            $selectedLabel = $optLblRaw;
          }
          $selClass = $isSelected ? ' selected' : '';
          $optLines[] = "{$optIndent}<div class=\"dropdown-option{$selClass}\" data-value=\"{$optVal}\">{$optLbl}</div>";
        }
        $innerIndent = $hasWrapper ? '    ' : '  ';
        $baseIndent = $hasWrapper ? '  ' : '';
        $selectedClass = $selectedLabel !== '' ? 'dropdown-selected' : 'dropdown-selected is-placeholder';
        $selectedDisplay = $selectedLabel !== '' ? $selectedLabel : $placeholder;
        $html = "<div" . self::renderAttrsString($attrs, $multiline, $indent) . ">\n"
          . "{$innerIndent}<div class=\"{$selectedClass}\">" . self::esc($selectedDisplay) . "</div>\n"
          . "{$innerIndent}<div class=\"dropdown-options hidden\">\n"
          . implode("\n", $optLines) . "\n"
          . "{$innerIndent}</div>\n"
          . "{$baseIndent}</div>";
        break;

      case 'datePicker':
        $classes = trim("date-picker ui-input-component {$extraClass}");
        $attrs = array_merge([
          'type' => 'text',
          'name' => $name,
          'id' => $id ?: null,
          'value' => $value,
          'class' => $classes,
          'placeholder' => $placeholder,
          'data-mode' => (string)($params['mode'] ?? 'single'),
          'data-format' => (string)($params['format'] ?? 'YYYY-MM-DD'),
          'data-time' => !empty($params['showTime']) ? 'true' : null,
          'data-presets' => !empty($params['showPresets']) ? 'true' : null,
          'data-week-start' => !empty($params['weekStart']) ? (string)$params['weekStart'] : null,
          'min' => !empty($params['min']) ? (string)$params['min'] : null,
          'max' => !empty($params['max']) ? (string)$params['max'] : null,
          'autocomplete' => 'off',
          'required' => $required,
          'disabled' => $disabled,
          'readonly' => $readonly,
        ], $transversalAttrs, $zoneAttrs);
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'timePicker':
        $classes = trim("time-picker ui-input-component {$extraClass}");
        $attrs = array_merge([
          'type' => 'text',
          'name' => $name,
          'id' => $id ?: null,
          'value' => $value,
          'class' => $classes,
          'placeholder' => $placeholder,
          'data-format' => (string)($params['timeFormat'] ?? '24h'),
          'data-step' => (int)($params['step'] ?? 15),
          'data-max-height' => !empty($params['maxHeight']) ? max(100, min(420, (int)$params['maxHeight'])) : null,
          'min' => !empty($params['min']) ? (string)$params['min'] : null,
          'max' => !empty($params['max']) ? (string)$params['max'] : null,
          'autocomplete' => 'off',
          'required' => $required,
          'disabled' => $disabled,
          'readonly' => $readonly,
        ], $transversalAttrs, $zoneAttrs);
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'rangeSlider':
        $classes = trim("range-slider ui-input-component {$extraClass}");
        $mode = (string)($params['mode'] ?? 'single');
        $minVal = $params['min'] ?? 0;
        $maxVal = $params['max'] ?? 100;
        $stepVal = $params['step'] ?? 1;
        if ($mode === 'range') {
          $attrs = array_merge([
            'type' => 'text',
            'name' => $name,
            'id' => $id ?: null,
            'value' => $value !== '' ? $value : '20,80',
            'class' => $classes,
            'data-mode' => 'range',
            'min' => $minVal,
            'max' => $maxVal,
            'step' => $stepVal,
            'data-suffix' => (string)($params['suffix'] ?? ''),
            'data-tooltip' => (!isset($params['showTooltip']) || $params['showTooltip']) ? 'true' : 'false',
            'disabled' => $disabled,
          ], $transversalAttrs, $zoneAttrs);
        } else {
          $attrs = array_merge([
            'type' => 'range',
            'name' => $name,
            'id' => $id ?: null,
            'value' => $value !== '' ? $value : '50',
            'class' => $classes,
            'min' => $minVal,
            'max' => $maxVal,
            'step' => $stepVal,
            'data-suffix' => (string)($params['suffix'] ?? ''),
            'data-tooltip' => (!isset($params['showTooltip']) || $params['showTooltip']) ? 'true' : 'false',
            'disabled' => $disabled,
          ], $transversalAttrs, $zoneAttrs);
        }
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'passwordInput':
        $classes = trim("password-input ui-input-component {$extraClass}");
        $attrs = array_merge([
          'type' => 'password',
          'name' => $name,
          'id' => $id ?: null,
          'value' => $value,
          'class' => $classes,
          'placeholder' => $placeholder,
          'data-strength' => !empty($params['showStrength']) ? 'true' : null,
          'minlength' => !empty($params['minlength']) ? (int)$params['minlength'] : null,
          'autocomplete' => 'current-password',
          'required' => $required,
          'disabled' => $disabled,
          'readonly' => $readonly,
        ], $transversalAttrs, $zoneAttrs);
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'otpCodeInput':
        $classes = trim("otp-input ui-input-component {$extraClass}");
        $length = max(4, min(8, (int)($params['length'] ?? 6)));
        $attrs = array_merge([
          'type' => 'text',
          'name' => $name,
          'id' => $id ?: null,
          'value' => $value,
          'class' => $classes,
          'data-length' => $length,
          'data-mask' => !empty($params['mask']) ? 'password' : null,
          'inputmode' => (string)($params['inputMode'] ?? 'numeric'),
          'autocomplete' => 'one-time-code',
          'required' => $required,
          'disabled' => $disabled,
          'readonly' => $readonly,
        ], $transversalAttrs, $zoneAttrs);
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'tagsInput':
        $classes = trim("tags-input ui-input-component {$extraClass}");
        $attrs = array_merge([
          'type' => 'text',
          'name' => $name,
          'id' => $id ?: null,
          'value' => $value !== '' ? $value : 'PHP, Vanilla JS',
          'class' => $classes,
          'placeholder' => $placeholder,
          'data-max-tags' => !empty($params['maxTags']) ? (int)$params['maxTags'] : 10,
          'data-separator' => (string)($params['separator'] ?? ','),
          'disabled' => $disabled,
          'readonly' => $readonly,
        ], $transversalAttrs, $zoneAttrs);
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'stepperInput':
        $classes = trim("stepper-input ui-input-component {$extraClass}");
        $attrs = array_merge([
          'type' => 'number',
          'name' => $name,
          'id' => $id ?: null,
          'value' => $value !== '' ? $value : '1',
          'class' => $classes,
          'min' => $params['min'] ?? 0,
          'max' => $params['max'] ?? 99,
          'step' => $params['step'] ?? 1,
          'required' => $required,
          'disabled' => $disabled,
          'readonly' => $readonly,
        ], $transversalAttrs, $zoneAttrs);
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'ratingInput':
        $classes = trim("rating-input ui-input-component {$extraClass}");
        $attrs = array_merge([
          'type' => 'hidden',
          'name' => $name,
          'id' => $id ?: null,
          'value' => $value !== '' ? $value : '4',
          'class' => $classes,
          'data-max' => max(3, min(10, (int)($params['max'] ?? 5))),
          'data-half' => !empty($params['allowHalf']) ? 'true' : null,
          'data-clearable' => !empty($params['clearable']) ? 'true' : null,
          'data-readonly' => $readonly ? 'true' : null,
          'data-icon' => (string)($params['icon'] ?? 'star'),
          'data-show-value' => !empty($params['showValue']) ? 'true' : null,
          'disabled' => $disabled,
        ], $transversalAttrs, $zoneAttrs);
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'fileDropzone':
        $classes = trim("file-dropzone ui-input-component {$extraClass}");
        $attrs = array_merge([
          'type' => 'file',
          'name' => $name,
          'id' => $id ?: null,
          'accept' => (string)($params['accept'] ?? '.pdf,.doc,.docx,.zip,image/*'),
          'multiple' => !isset($params['multiple']) || (bool)$params['multiple'],
          'class' => $classes,
          'placeholder' => $placeholder,
          'data-subtext' => !empty($params['subtext']) ? (string)$params['subtext'] : null,
          'data-border-style' => !empty($params['dropzoneBorderStyle']) ? (string)$params['dropzoneBorderStyle'] : null,
          'data-min-height' => !empty($params['dropzoneMinHeight']) ? (int)$params['dropzoneMinHeight'] : null,
          'data-icon-size' => !empty($params['dropzoneIconSize']) ? (int)$params['dropzoneIconSize'] : null,
          'data-max-files' => !empty($params['maxFiles']) ? (int)$params['maxFiles'] : 5,
          'data-max-size' => !empty($params['maxSizeMb']) ? (float)$params['maxSizeMb'] : 10,
          'required' => $required,
          'disabled' => $disabled,
        ], $transversalAttrs, $zoneAttrs);
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'repeatCamp':
        $classes = trim("repeat-camp ui-input-component {$extraClass}");
        $maxItems = max(2, min(50, (int)($params['max'] ?? 8)));
        $addVariant = (!empty($params['addVariant']) && in_array($params['addVariant'], ['primary', 'secondary'], true))
          ? $params['addVariant']
          : null;
        $attrs = array_merge([
          'type' => 'text',
          'name' => str_ends_with($name, '[]') ? $name : ($name . '[]'),
          'id' => $id ?: null,
          'value' => $value !== '' ? $value : null,
          'class' => $classes,
          'placeholder' => $placeholder,
          'data-max' => $maxItems,
          'data-add-label' => !empty($params['addLabel']) ? (string)$params['addLabel'] : null,
          'data-add-variant' => $addVariant,
          'required' => $required,
          'disabled' => $disabled,
          'readonly' => $readonly,
        ], $transversalAttrs, $zoneAttrs);
        $groupAttrs = ['class' => 'repeat-group'];
        if (!empty($zoneAttrs['style'])) {
          $groupAttrs['style'] = $zoneAttrs['style'];
        }
        $innerIndent = $hasWrapper ? '    ' : '  ';
        $baseIndent = $hasWrapper ? '  ' : '';
        $html = "<div" . self::renderAttrsString($groupAttrs, false) . ">\n"
          . "{$innerIndent}<input" . self::renderAttrsString($attrs, $multiline, $innerIndent . '  ') . ">\n"
          . "{$baseIndent}</div>";
        break;

      case 'textLimiter':
        $classes = trim("text-limiter ui-input-component {$extraClass}");
        $limit = max(10, (int)($params['limit'] ?? 160));
        $limitType = (string)($params['limitType'] ?? 'chars');
        $counterId = $id ? ($id . '-counter') : ('tl-counter-' . substr(md5($name), 0, 6));
        $attrs = array_merge([
          'name' => $name,
          'id' => $id ?: null,
          'class' => $classes,
          'placeholder' => $placeholder,
          'rows' => (int)($params['rows'] ?? 3),
          'data-limit-type' => $limitType,
          'data-limit' => $limit,
          'data-counter-el' => '#' . $counterId,
          'required' => $required,
          'disabled' => $disabled,
          'readonly' => $readonly,
        ], $transversalAttrs, $zoneAttrs);
        $counterAttrs = ['id' => $counterId, 'class' => 'tl-counter x12'];
        if (!empty($zoneAttrs['style'])) {
          $counterAttrs['style'] = $zoneAttrs['style'];
        }
        $baseIndent = $hasWrapper ? '  ' : '';
        $html = "<textarea" . self::renderAttrsString($attrs, $multiline, $indent) . ">" . self::esc($value) . "</textarea>\n"
          . "{$baseIndent}<div" . self::renderAttrsString($counterAttrs, false) . "><span class=\"tl-current\">0</span> / <span class=\"tl-limit\">{$limit}</span></div>";
        break;

      case 'textInput':
        $rawType = strtolower(trim((string)($params['inputType'] ?? 'text')));
        $allowedInputTypes = ['text', 'email', 'tel', 'phone', 'number', 'search', 'url'];
        $inputType = in_array($rawType, $allowedInputTypes, true) ? ($rawType === 'phone' ? 'tel' : $rawType) : 'text';
        $iconName = (!empty($params['icon']) && is_string($params['icon']) && preg_match('/^[a-zA-Z0-9_-]+$/', trim($params['icon'])))
          ? trim($params['icon'])
          : '';
        $iconPos = (($params['iconPosition'] ?? 'left') === 'right') ? 'right' : 'left';
        $iconSvg = ($iconName !== '' && function_exists('svg')) ? (string)svg($iconName, 'x18') : '';
        $iconPadClass = $iconSvg !== '' ? ($iconPos === 'right' ? 'has-icon-right' : 'has-icon-left') : '';

        $classes = trim("ui-text-input ui-input-component {$iconPadClass} {$extraClass}");
        $attrs = array_merge([
          'type' => $inputType,
          'name' => $name,
          'id' => $id ?: null,
          'value' => $value,
          'class' => $classes,
          'placeholder' => $placeholder,
          'minlength' => !empty($params['minlength']) ? (int)$params['minlength'] : null,
          'maxlength' => !empty($params['maxlength']) ? (int)$params['maxlength'] : null,
          'pattern' => !empty($params['pattern']) && is_string($params['pattern']) ? $params['pattern'] : null,
          'min' => (isset($params['min']) && $params['min'] !== '') ? (string)$params['min'] : null,
          'max' => (isset($params['max']) && $params['max'] !== '') ? (string)$params['max'] : null,
          'step' => !empty($params['step']) ? (string)$params['step'] : null,
          'inputmode' => !empty($params['inputmode']) ? (string)$params['inputmode'] : null,
          'autocomplete' => !empty($params['autocomplete']) ? (string)$params['autocomplete'] : null,
          'required' => $required,
          'disabled' => $disabled,
          'readonly' => $readonly,
        ], $transversalAttrs, $zoneAttrs);

        $inputHtml = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        if ($iconSvg !== '') {
          $wrapStyle = !empty($zoneAttrs['style']) ? ['class' => 'ui-text-input-wrap', 'style' => $zoneAttrs['style']] : ['class' => 'ui-text-input-wrap'];
          $html = '<div' . self::renderAttrsString($wrapStyle, false) . ">\n"
            . "{$indent}<span class=\"ui-input-icon is-{$iconPos}\" aria-hidden=\"true\">{$iconSvg}</span>\n"
            . "{$indent}{$inputHtml}\n"
            . ($hasWrapper ? '  ' : '') . '</div>';
        } else {
          $html = $inputHtml;
        }
        break;

      case 'textareaInput':
        $classes = trim("ui-textarea ui-input-component {$extraClass}");
        $resizeMode = (string)($params['resize'] ?? 'vertical');
        $allowedResize = ['vertical', 'none', 'both', 'horizontal'];
        $textareaAttrs = array_merge([
          'name' => $name,
          'id' => $id ?: null,
          'class' => $classes,
          'placeholder' => $placeholder,
          'rows' => max(2, min(20, (int)($params['rows'] ?? 4))),
          'minlength' => !empty($params['minlength']) ? (int)$params['minlength'] : null,
          'maxlength' => !empty($params['maxlength']) ? (int)$params['maxlength'] : null,
          'required' => $required,
          'disabled' => $disabled,
          'readonly' => $readonly,
        ], $transversalAttrs, $zoneAttrs);
        if (in_array($resizeMode, $allowedResize, true) && $resizeMode !== 'vertical') {
          $textareaAttrs['style'] = trim(($textareaAttrs['style'] ?? '') . " resize: {$resizeMode};");
        }
        $html = '<textarea' . self::renderAttrsString($textareaAttrs, $multiline, $indent) . '>' . self::esc($value) . '</textarea>';
        break;

      case 'submitButton':
        $variant = (($params['variant'] ?? 'primary') === 'secondary') ? 'secondary' : 'primary';
        $rawBtnType = (string)($params['buttonType'] ?? 'submit');
        $btnType = in_array($rawBtnType, ['submit', 'button', 'reset'], true) ? $rawBtnType : 'submit';
        $btnText = isset($params['text']) && is_string($params['text']) && trim($params['text']) !== ''
          ? trim($params['text'])
          : 'Enviar';
        $iconName = (!empty($params['icon']) && is_string($params['icon']) && preg_match('/^[a-zA-Z0-9_-]+$/', trim($params['icon'])))
          ? trim($params['icon'])
          : '';
        $iconHtml = ($iconName !== '' && function_exists('svg'))
          ? '<span class="ui-submit-icon" aria-hidden="true">' . (string)svg($iconName, 'x18') . '</span>'
          : '';
        $classes = trim("ui-submit-btn is-{$variant} ui-input-component {$extraClass}");
        $btnAttrs = array_merge([
          'type' => $btnType,
          'name' => !empty($params['name']) ? $name : null,
          'id' => $id ?: null,
          'value' => $value !== '' ? $value : null,
          'class' => $classes,
          'data-variant' => $variant,
          'data-loading-text' => !empty($params['loadingText']) ? (string)$params['loadingText'] : null,
          'disabled' => $disabled,
        ], $transversalAttrs, $zoneAttrs);
        $btnLabelSpan = '<span class="ui-submit-text">' . self::esc($btnText) . '</span>';
        $html = '<button' . self::renderAttrsString($btnAttrs, $multiline, $indent) . '>'
          . ($iconHtml !== '' ? "{$iconHtml} {$btnLabelSpan}" : $btnLabelSpan)
          . '</button>';
        break;
    }

    if ($hasWrapper) {
      $lines = [];
      $outerClass = $wrapContainer
        ? $wrapperClass
        : trim('ui-field-group' . ($wrapperClassCustom !== '' ? ' ' . $wrapperClassCustom : ''));
      $wrapAttrs = [
        'class' => $outerClass,
        'data-show-when' => $showWhen !== '' ? $showWhen : null,
      ];
      if (!empty($zoneAttrs['style'])) {
        $wrapAttrs['style'] = $zoneAttrs['style'];
      }
      $lines[] = '<div' . self::renderAttrsString($wrapAttrs, false) . '>';
      if ($labelText !== '') {
        $forAttr = $labelForId !== '' ? ' for="' . self::esc($labelForId) . '"' : '';
        $lblClasses = trim('ui-field-label' . ($labelClassExtra !== '' ? ' ' . $labelClassExtra : ''));
        $lines[] = "  <label{$forAttr} class=\"" . self::esc($lblClasses) . '">' . self::esc($labelText) . '</label>';
      }
      $indentedHtml = implode("\n", array_map(static fn(string $line): string => '  ' . $line, explode("\n", $html)));
      $lines[] = $indentedHtml;
      if ($helperText !== '') {
        $hlpClasses = trim('ui-field-helper' . ($helperClassExtra !== '' ? ' ' . $helperClassExtra : ''));
        $lines[] = '  <small class="' . self::esc($hlpClasses) . '">' . self::esc($helperText) . '</small>';
      }
      $lines[] = '</div>';
      return implode("\n", $lines);
    }

    return $html;
  }

  /**
   * Parsea una cadena de opciones con formato `"valor:Etiqueta, valor2:Etiqueta 2"` o líneas.
   *
   * @param string $raw Cadena de opciones.
   * @return array<int, array{value: string, label: string}>
   */
  public static function parseOptionsString(string $raw): array
  {
    $items = preg_split('/[\r\n,]+/', $raw) ?: [];
    $options = [];
    foreach ($items as $item) {
      $item = trim($item);
      if ($item === '') {
        continue;
      }
      if (str_contains($item, ':')) {
        [$val, $lbl] = explode(':', $item, 2);
        $val = trim($val);
        $lbl = trim($lbl);
        $options[] = [
          'value' => $val !== '' ? $val : $lbl,
          'label' => $lbl !== '' ? $lbl : $val,
        ];
      } else {
        $options[] = [
          'value' => $item,
          'label' => $item,
        ];
      }
    }
    return $options;
  }

  /**
   * Genera el snippet HTML formateado listo para copiar y pegar en cualquier vista de formulario.
   *
   * @param string $type Tipo de componente.
   * @param array<string, mixed> $params Parámetros del componente.
   * @return string HTML indentado y listo para pegar.
   */
  public static function buildHtmlSnippet(string $type, array $params = []): string
  {
    $params['multiline'] = $params['multiline'] ?? true;
    return self::render($type, $params);
  }

  /**
   * Renderiza un formulario completo (`<form>`) con soporte para auto-submit, CSRF automático,
   * detección de multipart/form-data, grilla CSS JIT y asistente multi-paso.
   *
   * @param array<string, mixed> $formConfig Configuración general del formulario.
   * @param array<int, array<string, mixed>> $stepsOrFields Lista plana de campos o arreglo de pasos con clave `fields`.
   * @return string HTML completo del formulario listo para vista o vista previa.
   */
  public static function renderForm(array $formConfig, array $stepsOrFields = []): string
  {
    self::ensureThemeConfigLoaded();

    $action = trim((string)($formConfig['action'] ?? ''));
    $methodRaw = strtoupper(trim((string)($formConfig['method'] ?? 'POST')));
    $method = in_array($methodRaw, ['GET', 'POST'], true) ? $methodRaw : 'POST';
    $formId = trim((string)($formConfig['id'] ?? ''));
    $autoSubmit = !empty($formConfig['autoSubmit']);
    $fetchPreview = !empty($formConfig['fetchPreview']);
    $studioPreview = !empty($formConfig['studioPreview']);
    $csrf = !array_key_exists('csrf', $formConfig) || !empty($formConfig['csrf']);
    $showSubmitButton = !array_key_exists('showSubmitButton', $formConfig) || !empty($formConfig['showSubmitButton']);
    $stepProgress = !array_key_exists('stepProgress', $formConfig) || !empty($formConfig['stepProgress']);
    $formClassCustom = !empty($formConfig['formClass']) && is_string($formConfig['formClass'])
      ? self::sanitizeClasses($formConfig['formClass'])
      : '';
    $gridClassCustom = !empty($formConfig['gridClass']) && is_string($formConfig['gridClass'])
      ? self::sanitizeClasses($formConfig['gridClass'])
      : '';
    $prevLabel = !empty($formConfig['prevLabel']) ? (string)$formConfig['prevLabel'] : 'Anterior';
    $nextLabel = !empty($formConfig['nextLabel']) ? (string)$formConfig['nextLabel'] : 'Siguiente';
    $submitLabel = !empty($formConfig['submitLabel']) ? (string)$formConfig['submitLabel'] : 'Enviar formulario';
    $submitConfig = is_array($formConfig['submitConfig'] ?? null) ? $formConfig['submitConfig'] : [];
    $inlineVars = !empty($formConfig['inlineVars']);

    $hasNestedSteps = false;
    foreach ($stepsOrFields as $item) {
      if (is_array($item) && isset($item['fields']) && is_array($item['fields'])) {
        $hasNestedSteps = true;
        break;
      }
    }

    $isMultiStep = !empty($formConfig['multiStep']) || ($hasNestedSteps && count($stepsOrFields) > 1);
    $steps = [];

    if ($hasNestedSteps) {
      foreach ($stepsOrFields as $stepItem) {
        if (!is_array($stepItem)) {
          continue;
        }
        $steps[] = [
          'title'       => (string)($stepItem['title'] ?? ''),
          'description' => (string)($stepItem['description'] ?? ''),
          'stepClass'   => !empty($stepItem['stepClass']) && is_string($stepItem['stepClass'])
            ? self::sanitizeClasses($stepItem['stepClass'])
            : '',
          'validateUrl' => trim((string)($stepItem['validateUrl'] ?? '')),
          'fields'      => is_array($stepItem['fields'] ?? null) ? $stepItem['fields'] : [],
        ];
      }
    } elseif ($isMultiStep) {
      $stepsMeta = is_array($formConfig['stepsMeta'] ?? null) ? $formConfig['stepsMeta'] : [];
      $grouped = [];
      $maxIdx = max(0, count($stepsMeta) - 1);
      foreach ($stepsOrFields as $fieldItem) {
        if (!is_array($fieldItem)) {
          continue;
        }
        $sIdx = max(0, (int)($fieldItem['step'] ?? 0));
        $maxIdx = max($maxIdx, $sIdx);
        $grouped[$sIdx][] = $fieldItem;
      }
      for ($s = 0; $s <= $maxIdx; $s++) {
        $meta = is_array($stepsMeta[$s] ?? null) ? $stepsMeta[$s] : [];
        $steps[] = [
          'title'       => (string)($meta['title'] ?? ('Paso ' . ($s + 1))),
          'description' => (string)($meta['description'] ?? ''),
          'stepClass'   => !empty($meta['stepClass']) && is_string($meta['stepClass'])
            ? self::sanitizeClasses($meta['stepClass'])
            : '',
          'validateUrl' => trim((string)($meta['validateUrl'] ?? '')),
          'fields'      => $grouped[$s] ?? [],
        ];
      }
    } else {
      $steps = [[
        'title'       => '',
        'description' => '',
        'stepClass'   => '',
        'validateUrl' => '',
        'fields'      => array_values(array_filter($stepsOrFields, 'is_array')),
      ]];
    }

    if (empty($steps)) {
      $steps = [[
        'title'       => $isMultiStep ? 'Paso 1' : '',
        'description' => '',
        'stepClass'   => '',
        'validateUrl' => '',
        'fields'      => [],
      ]];
    }

    $needsMultipart = (($formConfig['enctype'] ?? '') === 'multipart/form-data');
    if (!$needsMultipart && ($formConfig['enctype'] ?? 'auto') !== false) {
      $uploadTypes = ['cropImage', 'loadImgModern', 'fileDropzone'];
      foreach ($steps as $st) {
        foreach ($st['fields'] as $fld) {
          if (in_array((string)($fld['type'] ?? ''), $uploadTypes, true)) {
            $needsMultipart = true;
            break 2;
          }
        }
      }
    }

    $formClasses = trim('ui-form' . ($autoSubmit ? ' auto-submit' : '') . ($formClassCustom !== '' ? ' ' . $formClassCustom : ''));
    $formInlineStyle = self::buildInlineStyleVars($formConfig, $inlineVars);

    $formAttrs = [
      'id'                  => $formId !== '' ? $formId : null,
      'action'              => $action !== '' ? $action : null,
      'method'              => strtolower($method),
      'enctype'             => $needsMultipart ? 'multipart/form-data' : null,
      'class'               => $formClasses,
      'data-fetch-preview'  => $fetchPreview ? 'true' : null,
      'data-multi-step'     => $isMultiStep ? 'true' : null,
      'data-studio-preview' => $studioPreview ? 'true' : null,
      'novalidate'          => ($isMultiStep || !empty($formConfig['novalidate'])) ? true : null,
      'style'               => $formInlineStyle !== '' ? $formInlineStyle : null,
    ];

    $lines = ['<form' . self::renderAttrsString($formAttrs, false) . '>'];

    if ($method === 'POST' && $csrf && !$studioPreview && function_exists('csrf_field')) {
      $csrfHtml = (string)csrf_field();
      if ($csrfHtml !== '') {
        $lines[] = '  ' . $csrfHtml;
      }
    }

    $totalSteps = count($steps);

    if ($isMultiStep && $stepProgress) {
      $navBtns = [];
      foreach ($steps as $idx => $st) {
        $stTitle = trim((string)($st['title'] ?? ''));
        $dotLabel = $stTitle !== '' ? ($idx + 1) . '. ' . $stTitle : 'Paso ' . ($idx + 1);
        $dotCls = 'ui-step-dot' . ($idx === 0 ? ' is-active' : '');
        $navBtns[] = '    <button type="button" class="' . $dotCls . '" data-step-target="' . $idx . '"'
          . ($idx === 0 ? ' aria-current="step"' : ' aria-current="false"') . '>'
          . self::esc($dotLabel) . '</button>';
      }
      $initPct = (int)round((1 / max(1, $totalSteps)) * 100);
      $lines[] = '  <div class="ui-steps-nav" role="tablist">';
      $lines[] = implode("\n", $navBtns);
      $lines[] = '  </div>';
      $lines[] = '  <div class="ui-step-progress-bar" aria-hidden="true">';
      $lines[] = '    <div class="ui-step-progress-fill" style="width: ' . $initPct . '%;"></div>';
      $lines[] = '  </div>';
    }

    foreach ($steps as $idx => $st) {
      $fields = $st['fields'];
      $hasExplicitSubmit = false;
      $fieldHtmlBlocks = [];

      foreach ($fields as $fld) {
        $fType = (string)($fld['type'] ?? 'textInput');
        if ($fType === 'submitButton') {
          $hasExplicitSubmit = true;
        }
        $fParams = $fld;
        unset($fParams['type'], $fParams['step']);
        if ($inlineVars && !array_key_exists('inlineVars', $fParams)) {
          $fParams['inlineVars'] = true;
        }
        if ($studioPreview) {
          $fParams['ignoreThemeLocal'] = $fParams['ignoreThemeLocal'] ?? false;
        }
        $rendered = self::render($fType, $fParams);
        $indented = implode("\n", array_map(static fn(string $l): string => ($isMultiStep ? '      ' : '    ') . $l, explode("\n", $rendered)));
        $fieldHtmlBlocks[] = $indented;
      }

      $fieldsContainerClass = trim('ui-form-fields' . ($gridClassCustom !== '' ? ' ' . $gridClassCustom : '') . ($st['stepClass'] !== '' ? ' ' . $st['stepClass'] : ''));

      if ($isMultiStep) {
        $stepSectionAttrs = [
          'class'             => 'ui-form-step' . ($idx === 0 ? ' is-active' : ''),
          'data-step-index'   => (string)$idx,
          'data-validate-url' => $st['validateUrl'] !== '' ? $st['validateUrl'] : null,
          'hidden'            => $idx > 0 ? 'hidden' : null,
        ];
        $lines[] = '  <section' . self::renderAttrsString($stepSectionAttrs, false) . '>';
        $stTitle = trim((string)($st['title'] ?? ''));
        $stDesc = trim((string)($st['description'] ?? ''));
        if ($stTitle !== '' || $stDesc !== '') {
          $lines[] = '    <header class="ui-form-step-header">';
          if ($stTitle !== '') {
            $lines[] = '      <h4 class="ui-form-step-title">' . self::esc($stTitle) . '</h4>';
          }
          if ($stDesc !== '') {
            $lines[] = '      <p class="ui-form-step-desc">' . self::esc($stDesc) . '</p>';
          }
          $lines[] = '    </header>';
        }
        $lines[] = '    <div class="' . self::esc($fieldsContainerClass) . '">';
        if (!empty($fieldHtmlBlocks)) {
          $lines[] = implode("\n", $fieldHtmlBlocks);
        }
        $lines[] = '    </div>';

        $actionsJustify = $idx === 0 ? 'center-end' : 'center-between';
        $lines[] = '    <div class="ui-step-actions flex-row ' . $actionsJustify . ' gap10">';
        if ($idx > 0) {
          $lines[] = '      <button type="button" class="ui-submit-btn is-secondary js-step-prev">' . self::esc($prevLabel) . '</button>';
        }
        if ($idx < $totalSteps - 1) {
          $lines[] = '      <button type="button" class="ui-submit-btn is-primary js-step-next">' . self::esc($nextLabel) . '</button>';
        } elseif ($showSubmitButton && !$hasExplicitSubmit) {
          $btnHtml = self::render('submitButton', array_merge([
            'text' => $submitLabel,
            'buttonType' => 'submit',
            'variant' => 'primary',
            'inlineVars' => $inlineVars,
          ], $submitConfig));
          $lines[] = '      ' . $btnHtml;
        }
        $lines[] = '    </div>';
        $lines[] = '  </section>';
      } else {
        $lines[] = '  <div class="' . self::esc($fieldsContainerClass) . '">';
        if (!empty($fieldHtmlBlocks)) {
          $lines[] = implode("\n", $fieldHtmlBlocks);
        }
        $lines[] = '  </div>';
        if ($showSubmitButton && !$hasExplicitSubmit) {
          $btnHtml = self::render('submitButton', array_merge([
            'text' => $submitLabel,
            'buttonType' => 'submit',
            'variant' => 'primary',
            'inlineVars' => $inlineVars,
          ], $submitConfig));
          $lines[] = '  <div class="ui-step-actions flex-row center-end gap10">';
          $lines[] = '    ' . $btnHtml;
          $lines[] = '  </div>';
        }
      }
    }

    $lines[] = '</form>';
    return implode("\n", $lines);
  }

  /**
   * Acceso mágico estático para llamar `FormUiModule::cropImage($params)`, etc.
   *
   * @param string $name Nombre del método (tipo de componente).
   * @param array<int, mixed> $arguments Argumentos ([0] => array $params).
   * @return string
   */
  public static function __callStatic(string $name, array $arguments): string
  {
    $params = is_array($arguments[0] ?? null) ? $arguments[0] : [];
    return self::render($name, $params);
  }

  private static function defaultNameForType(string $type): string
  {
    return match ($type) {
      'cropImage'      => 'avatar',
      'loadImgModern'  => 'gallery[]',
      'colorPicker'    => 'accent_color',
      'checkboxSwitch' => 'is_active',
      'dropdown'       => 'country',
      'datePicker'     => 'event_date',
      'timePicker'     => 'event_time',
      'rangeSlider'    => 'price_range',
      'passwordInput'  => 'password',
      'otpCodeInput'   => 'verification_code',
      'tagsInput'      => 'tags',
      'stepperInput'   => 'quantity',
      'ratingInput'    => 'rating',
      'fileDropzone'   => 'documents[]',
      'repeatCamp'     => 'options[]',
      'textLimiter'    => 'bio',
      'textInput'      => 'full_name',
      'textareaInput'  => 'message',
      'submitButton'   => 'submit_btn',
      default          => 'field',
    };
  }

  private static function defaultPlaceholderForType(string $type): string
  {
    return match ($type) {
      'cropImage'      => 'Elige una imagen',
      'loadImgModern'  => 'Subir imágenes',
      'dropdown'       => 'Seleccionar una opción...',
      'datePicker'     => 'Seleccionar fecha',
      'timePicker'     => 'Seleccionar hora',
      'passwordInput'  => 'Ingresa tu contraseña',
      'tagsInput'      => 'Añadir etiqueta y Enter...',
      'fileDropzone'   => 'Arrastra archivos o toca para explorar',
      'repeatCamp'     => 'Opción 1',
      'textLimiter'    => 'Escribe una descripción breve...',
      'textInput'      => 'Escribe aquí...',
      'textareaInput'  => 'Escribe tu mensaje o comentario...',
      default          => '',
    };
  }

  private static function defaultValueForType(string $type): string
  {
    return match ($type) {
      'colorPicker'  => '#3B82F6',
      'rangeSlider'  => '50',
      'tagsInput'    => 'PHP, Vanilla JS',
      'stepperInput' => '1',
      'ratingInput'  => '4',
      default        => '',
    };
  }
}
