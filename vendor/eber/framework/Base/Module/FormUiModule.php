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
  ];

  /**
   * Configuración global de estilo por defecto.
   *
   * @var array<string, mixed>
   */
  private static array $globalConfig = [
    'preset'           => 'soft',
    'triggerBg'        => 'back1',
    'triggerText'      => 'texto',
    'triggerBorder'    => 'textc',
    'surfaceBg'        => 'back1',
    'surfaceText'      => 'texto',
    'surfaceMuted'     => 'text-inactive',
    'surfaceBorder'    => 'textc',
    'btnPrimaryBg'     => 'back6',
    'btnPrimaryText'   => 'textw',
    'btnSecondaryBg'   => 'back2',
    'btnSecondaryText' => 'texto',
    'accentColor'      => 'color2',
    'accentText'       => 'textb',
    'trackBg'          => 'back2',
    'radius'           => 15,
    'touchSize'        => 44,
    'shadow'           => 'soft',
    'blur'             => 10,
    'mobileSheet'      => true,
    'transition'       => 250,
  ];

  /**
   * Devuelve los valores por defecto de configuración global.
   *
   * @return array<string, mixed>
   */
  public static function getDefaultConfig(): array
  {
    return [
      'preset'           => 'soft',
      'triggerBg'        => 'back1',
      'triggerText'      => 'texto',
      'triggerBorder'    => 'textc',
      'surfaceBg'        => 'back1',
      'surfaceText'      => 'texto',
      'surfaceMuted'     => 'text-inactive',
      'surfaceBorder'    => 'textc',
      'btnPrimaryBg'     => 'back6',
      'btnPrimaryText'   => 'textw',
      'btnSecondaryBg'   => 'back2',
      'btnSecondaryText' => 'texto',
      'accentColor'      => 'color2',
      'accentText'       => 'textb',
      'trackBg'          => 'back2',
      'radius'           => 15,
      'touchSize'        => 44,
      'shadow'           => 'soft',
      'blur'             => 10,
      'mobileSheet'      => true,
      'transition'       => 250,
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
        'label'            => 'Suave (Tema)',
        'preset'           => 'soft',
        'triggerBg'        => 'back1',
        'triggerText'      => 'texto',
        'triggerBorder'    => 'textc',
        'surfaceBg'        => 'back1',
        'surfaceText'      => 'texto',
        'surfaceMuted'     => 'text-inactive',
        'surfaceBorder'    => 'textc',
        'btnPrimaryBg'     => 'back6',
        'btnPrimaryText'   => 'textw',
        'btnSecondaryBg'   => 'back2',
        'btnSecondaryText' => 'texto',
        'accentColor'      => 'color2',
        'accentText'       => 'textb',
        'trackBg'          => 'back2',
        'radius'           => 15,
        'touchSize'        => 44,
        'shadow'           => 'soft',
        'blur'             => 10,
        'mobileSheet'      => true,
      ],
      'minimal' => [
        'label'            => 'Minimalista',
        'preset'           => 'minimal',
        'triggerBg'        => 'body',
        'triggerText'      => 'texto',
        'triggerBorder'    => 'border2',
        'surfaceBg'        => 'body',
        'surfaceText'      => 'texto',
        'surfaceMuted'     => 'text-inactive',
        'surfaceBorder'    => 'border2',
        'btnPrimaryBg'     => 'back8',
        'btnPrimaryText'   => 'textw',
        'btnSecondaryBg'   => 'transparent',
        'btnSecondaryText' => 'texto',
        'accentColor'      => 'color8',
        'accentText'       => 'textw',
        'trackBg'          => 'back1',
        'radius'           => 10,
        'touchSize'        => 44,
        'shadow'           => 'none',
        'blur'             => 0,
        'mobileSheet'      => true,
      ],
      'glass' => [
        'label'            => 'Glassmorphism',
        'preset'           => 'glass',
        'triggerBg'        => 'back1',
        'triggerText'      => 'texto',
        'triggerBorder'    => 'border2',
        'surfaceBg'        => 'back1',
        'surfaceText'      => 'texto',
        'surfaceMuted'     => 'text-inactive',
        'surfaceBorder'    => 'border2',
        'btnPrimaryBg'     => 'back5',
        'btnPrimaryText'   => 'textw',
        'btnSecondaryBg'   => 'back2',
        'btnSecondaryText' => 'texto',
        'accentColor'      => 'color5',
        'accentText'       => 'textw',
        'trackBg'          => 'back2',
        'radius'           => 18,
        'touchSize'        => 44,
        'shadow'           => 'lg',
        'blur'             => 16,
        'mobileSheet'      => true,
      ],
      'pill' => [
        'label'            => 'Pill Redondeado',
        'preset'           => 'pill',
        'triggerBg'        => 'back2',
        'triggerText'      => 'texto',
        'triggerBorder'    => 'transparent',
        'surfaceBg'        => 'back1',
        'surfaceText'      => 'texto',
        'surfaceMuted'     => 'text-inactive',
        'surfaceBorder'    => 'textc',
        'btnPrimaryBg'     => 'back-success',
        'btnPrimaryText'   => 'textw',
        'btnSecondaryBg'   => 'back2',
        'btnSecondaryText' => 'texto',
        'accentColor'      => 'color6',
        'accentText'       => 'textw',
        'trackBg'          => 'back3',
        'radius'           => 24,
        'touchSize'        => 48,
        'shadow'           => 'md',
        'blur'             => 8,
        'mobileSheet'      => true,
      ],
      'elevated' => [
        'label'            => 'Contraste Alto',
        'preset'           => 'elevated',
        'triggerBg'        => 'back8',
        'triggerText'      => 'textw',
        'triggerBorder'    => 'border8',
        'surfaceBg'        => '#111827',
        'surfaceText'      => '#f8fafc',
        'surfaceMuted'     => '#94a3b8',
        'surfaceBorder'    => '#334155',
        'btnPrimaryBg'     => '#2563eb',
        'btnPrimaryText'   => '#ffffff',
        'btnSecondaryBg'   => '#1e293b',
        'btnSecondaryText' => '#e2e8f0',
        'accentColor'      => '#38bdf8',
        'accentText'       => '#0f172a',
        'trackBg'          => '#334155',
        'radius'           => 14,
        'touchSize'        => 44,
        'shadow'           => 'lg',
        'blur'             => 12,
        'mobileSheet'      => true,
      ],
    ];
  }

  /**
   * Configura las propiedades de estilo globales para todos los componentes de formulario.
   *
   * @param array<string, mixed> $config Opciones globales de diseño (--ui-*).
   * @return void
   */
  public static function configStyle(array $config = []): void
  {
    $stringKeys = [
      'preset',
      'triggerBg',
      'triggerText',
      'triggerBorder',
      'surfaceBg',
      'surfaceText',
      'surfaceMuted',
      'surfaceBorder',
      'btnPrimaryBg',
      'btnPrimaryText',
      'btnSecondaryBg',
      'btnSecondaryText',
      'accentColor',
      'accentText',
      'trackBg',
      'shadow',
    ];

    foreach ($stringKeys as $key) {
      if (isset($config[$key]) && is_string($config[$key]) && trim($config[$key]) !== '') {
        self::$globalConfig[$key] = trim($config[$key]);
      }
    }

    if (isset($config['radius']) && is_numeric($config['radius'])) {
      self::$globalConfig['radius'] = max(0, min(40, (int)$config['radius']));
    }

    if (isset($config['touchSize']) && is_numeric($config['touchSize'])) {
      self::$globalConfig['touchSize'] = max(32, min(64, (int)$config['touchSize']));
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
  }

  /**
   * Obtiene la configuración global actual.
   *
   * @return array<string, mixed>
   */
  public static function getGlobalConfig(): array
  {
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
      'back-success'   => ['class' => 'back-success', 'cssValue' => 'var(--success, #00b900)'],
      'color-success'  => ['class' => 'color-success', 'cssValue' => 'var(--success, #00b900)'],
      'back-caution'   => ['class' => 'back-caution', 'cssValue' => 'var(--caution, #ff8c00)'],
      'color-caution'  => ['class' => 'color-caution', 'cssValue' => 'var(--caution, #ff8c00)'],
      'back-danger'    => ['class' => 'back-danger', 'cssValue' => 'var(--danger, #ff0000)'],
      'color-danger'   => ['class' => 'color-danger', 'cssValue' => 'var(--danger, #ff0000)'],
      'back-none'      => ['class' => 'back-none', 'cssValue' => 'transparent'],
      'border-none'    => ['class' => 'border-none', 'cssValue' => 'transparent'],
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
    $btnPrimaryBg = self::resolveColorToken((string)$cfg['btnPrimaryBg'])['cssValue'];
    $btnPrimaryText = self::resolveColorToken((string)$cfg['btnPrimaryText'])['cssValue'];
    $btnSecondaryBg = self::resolveColorToken((string)$cfg['btnSecondaryBg'])['cssValue'];
    $btnSecondaryText = self::resolveColorToken((string)$cfg['btnSecondaryText'])['cssValue'];
    $accentColor = self::resolveColorToken((string)$cfg['accentColor'])['cssValue'];
    $accentText = self::resolveColorToken((string)$cfg['accentText'])['cssValue'];
    $trackBg = self::resolveColorToken((string)$cfg['trackBg'])['cssValue'];

    $radius = max(0, min(40, (int)($cfg['radius'] ?? 15)));
    $touchSize = max(32, min(64, (int)($cfg['touchSize'] ?? 44)));
    $blur = max(0, min(32, (int)($cfg['blur'] ?? 10)));
    $transition = max(0, min(2000, (int)($cfg['transition'] ?? 250)));
    $shadowKey = strtolower((string)($cfg['shadow'] ?? 'soft'));
    $shadowVal = $shadowMap[$shadowKey] ?? $shadowMap['soft'];

    return [
      '--ui-trigger-bg'          => $triggerBg,
      '--ui-trigger-text'        => $triggerText,
      '--ui-trigger-border'      => $triggerBorder,
      '--ui-surface-bg'          => $surfaceBg,
      '--ui-surface-text'        => $surfaceText,
      '--ui-surface-muted'       => $surfaceMuted,
      '--ui-surface-border'      => $surfaceBorder,
      '--ui-surface-shadow'      => $shadowVal,
      '--ui-btn-primary-bg'      => $btnPrimaryBg,
      '--ui-btn-primary-text'    => $btnPrimaryText,
      '--ui-btn-secondary-bg'    => $btnSecondaryBg,
      '--ui-btn-secondary-text'  => $btnSecondaryText,
      '--ui-accent'              => $accentColor,
      '--ui-accent-text'         => $accentText,
      '--ui-track-bg'            => $trackBg,
      '--ui-radius'              => "{$radius}px",
      '--ui-touch-size'          => "{$touchSize}px",
      '--ui-overlay-blur'        => "{$blur}px",
      '--ui-transition'          => "{$transition}ms",
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
    $styleKeys = [
      'triggerBg',
      'triggerText',
      'triggerBorder',
      'surfaceBg',
      'surfaceText',
      'surfaceMuted',
      'surfaceBorder',
      'btnPrimaryBg',
      'btnPrimaryText',
      'btnSecondaryBg',
      'btnSecondaryText',
      'accentColor',
      'accentText',
      'trackBg',
      'radius',
      'touchSize',
      'shadow',
      'blur',
      'transition',
    ];

    $hasLocalOverride = false;
    foreach ($styleKeys as $k) {
      if (array_key_exists($k, $params) && $params[$k] !== null && $params[$k] !== '') {
        $hasLocalOverride = true;
        break;
      }
    }

    if (!$alwaysEmit && !$hasLocalOverride) {
      return '';
    }

    $vars = self::buildCssVarsMap($params);
    $parts = [];
    foreach ($vars as $k => $v) {
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

    $inlineVars = !empty($params['inlineVars']);
    $multiline = !empty($params['multiline']);
    $wrapContainer = !empty($params['wrapContainer']);
    $wrapperClass = !empty($params['wrapperClass']) && is_string($params['wrapperClass'])
      ? self::sanitizeClasses($params['wrapperClass'])
      : 'br15 p15 ui-field-card';
    $labelText = isset($params['label']) && is_string($params['label']) ? trim($params['label']) : '';

    $zoneAttrs = self::buildZoneAttributes($params, $inlineVars);
    $name = trim((string)($params['name'] ?? self::defaultNameForType($type)));
    $id = trim((string)($params['id'] ?? ''));
    $placeholder = (string)($params['placeholder'] ?? self::defaultPlaceholderForType($type));
    $value = (string)($params['value'] ?? self::defaultValueForType($type));
    $extraClass = isset($params['class']) && is_string($params['class']) ? self::sanitizeClasses($params['class']) : '';
    $required = !empty($params['required']);
    $autoSubmit = !empty($params['autoSubmit']);
    $indent = $wrapContainer ? '    ' : '  ';

    $html = '';

    switch ($type) {
      case 'cropImage':
        $classes = trim("selectAndCropImage ui-input-component {$extraClass}");
        if (!empty($params['noPreview'])) {
          $classes .= ' no-preview';
        }
        if ($autoSubmit) {
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
          'required' => $required,
        ], $zoneAttrs);
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'loadImgModern':
        $classes = trim("loadImgModern ui-input-component {$extraClass}");
        if ($autoSubmit) {
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
        ], $zoneAttrs);
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
        ], $zoneAttrs);
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
        ], $zoneAttrs);
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'dropdown':
        $classes = trim("dropdown-select ui-input-component {$extraClass}");
        if (!empty($params['animated'])) {
          $classes .= ' animated';
        }
        $options = is_array($params['options'] ?? null) ? $params['options'] : [
          ['value' => 'es', 'label' => 'España'],
          ['value' => 'mx', 'label' => 'México'],
          ['value' => 'ar', 'label' => 'Argentina'],
          ['value' => 'cl', 'label' => 'Chile'],
          ['value' => 'co', 'label' => 'Colombia'],
        ];
        $attrs = array_merge([
          'class' => $classes,
          'id' => $id ?: null,
          'data-name' => $name,
          'data-searchable' => (!isset($params['searchable']) || $params['searchable']) ? 'true' : 'false',
          'data-clearable' => !empty($params['clearable']) ? 'true' : null,
          'data-placeholder' => $placeholder,
        ], $zoneAttrs);

        $optIndent = $wrapContainer ? '      ' : '    ';
        $optLines = [];
        if (!isset($params['searchable']) || $params['searchable']) {
          $optLines[] = "{$optIndent}<input type=\"text\" class=\"dropdown-search\" placeholder=\"" . self::esc('Buscar...') . "\">";
        }
        foreach ($options as $opt) {
          $optVal = self::esc($opt['value'] ?? '');
          $optLbl = self::esc($opt['label'] ?? $optVal);
          $selClass = ($value !== '' && $value === (string)($opt['value'] ?? '')) ? ' selected' : '';
          $optLines[] = "{$optIndent}<div class=\"dropdown-option{$selClass}\" data-value=\"{$optVal}\">{$optLbl}</div>";
        }
        $innerIndent = $wrapContainer ? '    ' : '  ';
        $baseIndent = $wrapContainer ? '  ' : '';
        $html = "<div" . self::renderAttrsString($attrs, $multiline, $indent) . ">\n"
          . "{$innerIndent}<div class=\"dropdown-selected\">" . self::esc($placeholder) . "</div>\n"
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
          'min' => !empty($params['min']) ? (string)$params['min'] : null,
          'max' => !empty($params['max']) ? (string)$params['max'] : null,
          'autocomplete' => 'off',
          'required' => $required,
        ], $zoneAttrs);
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
          'autocomplete' => 'off',
          'required' => $required,
        ], $zoneAttrs);
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
          ], $zoneAttrs);
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
          ], $zoneAttrs);
        }
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'passwordInput':
        $classes = trim("password-input ui-input-component {$extraClass}");
        $attrs = array_merge([
          'type' => 'password',
          'name' => $name,
          'id' => $id ?: null,
          'class' => $classes,
          'placeholder' => $placeholder,
          'data-strength' => !empty($params['showStrength']) ? 'true' : null,
          'autocomplete' => 'current-password',
          'required' => $required,
        ], $zoneAttrs);
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'otpCodeInput':
        $classes = trim("otp-input ui-input-component {$extraClass}");
        $length = max(4, min(8, (int)($params['length'] ?? 6)));
        $attrs = array_merge([
          'type' => 'text',
          'name' => $name,
          'id' => $id ?: null,
          'class' => $classes,
          'data-length' => $length,
          'inputmode' => (string)($params['inputMode'] ?? 'numeric'),
          'autocomplete' => 'one-time-code',
          'required' => $required,
        ], $zoneAttrs);
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
        ], $zoneAttrs);
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
        ], $zoneAttrs);
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
          'data-icon' => (string)($params['icon'] ?? 'star'),
        ], $zoneAttrs);
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
          'data-max-files' => !empty($params['maxFiles']) ? (int)$params['maxFiles'] : 5,
          'data-max-size' => !empty($params['maxSizeMb']) ? (float)$params['maxSizeMb'] : 10,
          'required' => $required,
        ], $zoneAttrs);
        $html = '<input' . self::renderAttrsString($attrs, $multiline, $indent) . '>';
        break;

      case 'repeatCamp':
        $classes = trim("repeat-camp ui-input-component {$extraClass}");
        $maxItems = max(2, min(50, (int)($params['max'] ?? 8)));
        $attrs = array_merge([
          'type' => 'text',
          'name' => str_ends_with($name, '[]') ? $name : ($name . '[]'),
          'id' => $id ?: null,
          'class' => $classes,
          'placeholder' => $placeholder,
          'data-max' => $maxItems,
        ], $zoneAttrs);
        $innerIndent = $wrapContainer ? '    ' : '  ';
        $baseIndent = $wrapContainer ? '  ' : '';
        $html = "<div class=\"repeat-group\">\n"
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
        ], $zoneAttrs);
        $baseIndent = $wrapContainer ? '  ' : '';
        $html = "<textarea" . self::renderAttrsString($attrs, $multiline, $indent) . ">" . self::esc($value) . "</textarea>\n"
          . "{$baseIndent}<div id=\"{$counterId}\" class=\"tl-counter x12\"><span class=\"tl-current\">0</span> / <span class=\"tl-limit\">{$limit}</span></div>";
        break;
    }

    if ($labelText !== '' || $wrapContainer) {
      $lines = [];
      if ($wrapContainer) {
        $lines[] = '<div class="' . self::esc($wrapperClass) . '">';
      }
      if ($labelText !== '') {
        $forAttr = $id !== '' ? ' for="' . self::esc($id) . '"' : '';
        $lblIndent = $wrapContainer ? '  ' : '';
        $lines[] = "{$lblIndent}<label{$forAttr} class=\"ui-field-label\">" . self::esc($labelText) . '</label>';
      }
      if ($wrapContainer) {
        $indentedHtml = implode("\n", array_map(static fn(string $line): string => '  ' . $line, explode("\n", $html)));
        $lines[] = $indentedHtml;
        $lines[] = '</div>';
      } else {
        $lines[] = $html;
      }
      return implode("\n", $lines);
    }

    return $html;
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
