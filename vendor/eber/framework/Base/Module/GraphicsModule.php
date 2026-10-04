<?php

namespace Base\Module;

/**
 * 📊 GraphicsModule
 * 
 * Motor de generación de gráficos SVG vectoriales minimalistas y monocromáticos nativos.
 * Cero librerías externas (sin Chart.js ni D3).
 * 
 * Filosofía de diseño:
 * - Genera exclusivamente el SVG vectorial puro (el usuario proporciona el contenedor, fondo y layout).
 * - Monocromático inteligente vía OKLCH / CSS Relative Colors: a partir de un único color base
 *   (clase CSS como `.texto`, variable `--color`, hex o `currentColor`), se calculan automáticamente
 *   todas las derivaciones tonales (pistas, capas apiladas, ejes, cuadrículas y etiquetas).
 * - Animación simultánea estricta de 400 ms normalizada con curva easeInOutCubic.
 * 
 * @package Base\Module
 * @example
 * // Configuración global
 * GraphicsModule::configStyle(["color" => ".texto", "transition" => 400]);
 * 
 * // Generación de gráficos (retornan SVG puro como string)
 * echo GraphicsModule::arcMeter(["value" => 90, "label" => "Optimal Load"]);
 * echo GraphicsModule::stackedTones(["color" => "var(--back-color5)"]);
 * echo GraphicsModule::tileTreemap();
 * echo GraphicsModule::hybridSpline();
 * echo GraphicsModule::pillPillars();
 */
class GraphicsModule
{
  /**
   * Configuración de estilo global por defecto.
   *
   * @var array
   */
  private static array $globalConfig = [
    'color'      => 'texto',
    'colorLabel' => 'textw',
    'axisLabel'  => 'color1',
    'transition' => 400,
    'tooltip'    => true,
  ];

  /**
   * Contador para IDs únicos de elementos SVG (clipPaths, gradientes).
   *
   * @var int
   */
  private static int $instanceCounter = 0;

  /**
   * Configura las propiedades de estilo globales para todos los gráficos.
   *
   * @param array $config Opciones de estilo:
   *                      - 'color': Selector o clase CSS para el color base (ej. 'texto', '.texto', 'color', 'var(--back-color5)'), hex o 'currentColor'.
   *                      - 'colorLabel': Selector o clase CSS para etiquetas de datos y lecturas centrales (ej. 'textw', '.textw', '#ffffff').
   *                      - 'axisLabel': Selector o clase CSS para la numeración y etiquetas de ejes X e Y (ej. 'color1', '.color1', 'textc', '#a1a1aa').
   *                      - 'transition': Tiempo en milisegundos de la animación (por defecto 400).
   *                      - 'tooltip': Habilitar o deshabilitar tooltips globalmente (bool, por defecto true).
   * @return void
   */
  public static function configStyle(array $config = []): void
  {
    if (isset($config['color']) && is_string($config['color'])) {
      self::$globalConfig['color'] = trim($config['color']);
    }

    if (isset($config['colorLabel']) && is_string($config['colorLabel'])) {
      self::$globalConfig['colorLabel'] = trim($config['colorLabel']);
    }

    if (isset($config['axisLabel']) && is_string($config['axisLabel'])) {
      self::$globalConfig['axisLabel'] = trim($config['axisLabel']);
    }

    if (isset($config['transition']) && is_numeric($config['transition'])) {
      self::$globalConfig['transition'] = (int) $config['transition'];
    }

    if (isset($config['tooltip'])) {
      self::$globalConfig['tooltip'] = filter_var($config['tooltip'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $config['tooltip'];
    }
  }

  /**
   * Obtiene la configuración de estilo global actual.
   *
   * @return array
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
    self::$globalConfig = [
      'color'      => 'texto',
      'colorLabel' => 'textw',
      'axisLabel'  => 'color1',
      'transition' => 400,
      'tooltip'    => true,
    ];
  }

  /**
   * Resuelve un token de color o clase CSS a su par (clase, valorCss).
   *
   * Soporta tanto identificadores de clase con o sin punto inicial (ej. 'texto', '.texto', 'textw', '.textw'),
   * variables CSS ('--back-color5', 'var(--back-color5)'), colores hexadecimales con o sin almohadilla
   * ('#3b82f6', '3b82f6', '#fff', 'fff'), funciones CSS ('rgb(...)', 'oklch(...)')
   * y palabras clave estándar ('currentColor', 'white', etc.).
   *
   * @param string $raw Token de color o clase provisto por el usuario.
   * @return array ['class' => string, 'cssValue' => string]
   */
  private static function resolveColorToken(string $raw): array
  {
    $raw = trim($raw);
    if ($raw === '') {
      return ['class' => '', 'cssValue' => 'currentColor'];
    }

    // 1. Si empieza con un punto explícito (ej: '.texto', '.textw', '.color', '.color1')
    if (str_starts_with($raw, '.')) {
      return ['class' => ltrim($raw, '.'), 'cssValue' => 'currentColor'];
    }

    // 2. Si es variable CSS directa (ej: '--back-color5')
    if (str_starts_with($raw, '--')) {
      return ['class' => '', 'cssValue' => 'var(' . $raw . ')'];
    }

    // 3. Si es un color hexadecimal (con o sin '#', ej. '#3b82f6', '3b82f6', '#fff', 'fff', '#1a1a1a80')
    if (preg_match('/^#?([a-f0-9]{3}|[a-f0-9]{4}|[a-f0-9]{6}|[a-f0-9]{8})$/i', $raw)) {
      $hex = str_starts_with($raw, '#') ? $raw : ('#' . $raw);
      return ['class' => '', 'cssValue' => $hex];
    }

    // 4. Si es una función o valor de color CSS directo (var(...), rgb(...), rgba(...), hsl(...), hsla(...), oklch(...))
    if (
      str_starts_with($raw, 'var(') ||
      str_starts_with($raw, 'rgb(') ||
      str_starts_with($raw, 'rgba(') ||
      str_starts_with($raw, 'hsl(') ||
      str_starts_with($raw, 'hsla(') ||
      str_starts_with($raw, 'oklch(')
    ) {
      return ['class' => '', 'cssValue' => $raw];
    }

    // 5. Palabras clave de color CSS estándar
    $standardCssColors = [
      'currentcolor', 'transparent', 'inherit', 'initial', 'unset',
      'black', 'white', 'red', 'green', 'blue', 'yellow', 'orange', 'purple', 'cyan', 'magenta', 'gray', 'grey'
    ];
    if (in_array(strtolower($raw), $standardCssColors, true)) {
      return ['class' => '', 'cssValue' => $raw];
    }

    // 6. Cualquier otro identificador alfanumérico (ej: 'texto', 'textw', 'textc', 'textb', 'color', 'color1', 'color5', etc.)
    // se trata como nombre de clase CSS de utilidad
    return ['class' => $raw, 'cssValue' => 'currentColor'];
  }

  /**
   * Resuelve el estilo completo combinando color base, color de etiquetas, color de ejes y duración.
   *
   * @param array $params Parámetros locales pasados al gráfico.
   * @return array ['svgClass' => string, 'labelClass' => string, 'axisClass' => string, 'styleAttr' => string, 'transition' => int, 'colorRaw' => string, 'labelRaw' => string, 'axisRaw' => string]
   */
  private static function resolveStyle(array $params): array
  {
    $colorRaw = $params['color'] ?? self::$globalConfig['color'];
    $labelRaw = $params['colorLabel'] ?? self::$globalConfig['colorLabel'] ?? 'textw';
    $axisRaw = $params['axisLabel'] ?? self::$globalConfig['axisLabel'] ?? 'color1';
    $transition = isset($params['transition']) && is_numeric($params['transition'])
      ? (int) $params['transition']
      : self::$globalConfig['transition'];

    $base = self::resolveColorToken((string) $colorRaw);
    $label = self::resolveColorToken((string) $labelRaw);
    $axis = self::resolveColorToken((string) $axisRaw);

    $svgClasses = [];
    if (!empty($base['class'])) {
      $svgClasses[] = $base['class'];
    }

    $labelClass = $label['class'];
    $axisClass = $axis['class'];

    // Variables CSS para inline style
    $styleParts = [
      "--chart-base: {$base['cssValue']};",
      "--chart-label: {$label['cssValue']};",
      "--chart-axis: {$axis['cssValue']};",
      "--chart-base-color: {$base['cssValue']};",
      "--chart-label-color: {$label['cssValue']};",
      "--chart-axis-color: {$axis['cssValue']};",
      "--chart-duration: {$transition}ms;",
    ];

    // Si el color base es un valor CSS directo (HEX, variable o función), inyectar 'color' inline
    // para garantizar que currentColor dentro del SVG herede fielmente dicho valor
    if (empty($base['class']) && $base['cssValue'] !== 'currentColor') {
      $styleParts[] = "color: {$base['cssValue']};";
    }

    $tooltip = isset($params['tooltip'])
      ? (filter_var($params['tooltip'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $params['tooltip'])
      : (self::$globalConfig['tooltip'] ?? true);

    return [
      'svgClass'    => implode(' ', $svgClasses),
      'labelClass'  => $labelClass,
      'axisClass'   => $axisClass,
      'styleAttr'   => implode(' ', $styleParts),
      'transition'  => $transition,
      'tooltip'     => $tooltip,
      'tooltipAttr' => 'data-tooltip="' . ($tooltip ? 'true' : 'false') . '"',
      'colorRaw'    => $colorRaw,
      'labelRaw'    => $labelRaw,
      'axisRaw'     => $axisRaw,
    ];
  }

  /**
   * Genera un identificador único seguro para defs/clipPaths.
   *
   * @param string $prefix Prefijo descriptivo.
   * @return string
   */
  private static function generateId(string $prefix = 'chart'): string
  {
    self::$instanceCounter++;
    return $prefix . '-' . self::$instanceCounter . '-' . bin2hex(random_bytes(3));
  }

  /**
   * Divide un texto en múltiples líneas si excede una cantidad máxima de caracteres.
   *
   * @param string $text Texto original.
   * @param int $maxChars Caracteres máximos por línea sugeridos.
   * @param int $maxLines Cantidad máxima de líneas resultantes.
   * @return array Lista de líneas.
   */
  public static function wrapTextLines(string $text, int $maxChars = 14, int $maxLines = 2): array
  {
    $text = trim($text);
    if ($text === '') return [];
    if (mb_strlen($text, 'UTF-8') <= $maxChars) return [$text];

    $words = preg_split('/\s+/', $text);
    if (count($words) <= 1) {
      return [$text];
    }

    $lines = [];
    $currentLine = '';

    foreach ($words as $w) {
      if ($currentLine === '') {
        $currentLine = $w;
      } elseif (mb_strlen($currentLine . ' ' . $w, 'UTF-8') <= $maxChars) {
        $currentLine .= ' ' . $w;
      } else {
        $lines[] = $currentLine;
        $currentLine = $w;
        if (count($lines) >= $maxLines - 1) {
          break;
        }
      }
    }

    if ($currentLine !== '') {
      $lines[] = $currentLine;
    }

    $consumedWords = 0;
    foreach ($lines as $l) {
      $consumedWords += count(preg_split('/\s+/', $l));
    }
    if ($consumedWords < count($words) && !empty($lines)) {
      $remaining = array_slice($words, $consumedWords);
      $lines[count($lines) - 1] .= ' ' . implode(' ', $remaining);
    }

    return $lines;
  }

  /**
   * 1. ARC METER (Velocímetro / Indicador de Arco Semicircular de 180°)
   * 
   * Geometría matemática: Arco con radio 80 centrado en (120, 125).
   * Perímetro semicircular = π * 80 ≈ 251.33 px.
   *
   * @param array $params Opciones de configuración:
   *                      - 'value': Porcentaje objetivo (0 a 100, ej. 90).
   *                      - 'label': Subtítulo inferior (ej. 'Optimal Load').
   *                      - 'color': Selector CSS (ej. '.texto'), variable o HEX.
   *                      - 'transition': Duración de animación en ms (default 400).
   *                      - 'showNumber': Si muestra el porcentaje central (default true).
   *                      - 'showLabel': Si muestra la etiqueta inferior (default true).
   * @return string Código SVG puro.
   */
  public static function arcMeter(array $params = []): string
  {
    $style = self::resolveStyle($params);

    // =========================================================================
    // DATOS DE VISTA PREVIA (FALLBACK):
    // Se utilizan exclusivamente como preview/demostración si el usuario no
    // suministra datos dinámicos a través de $params['value'] o $params['label'].
    // =========================================================================
    $value = isset($params['value']) ? max(0, min(100, (float) $params['value'])) : 90;
    $label = isset($params['label']) ? htmlspecialchars((string) $params['label'], ENT_QUOTES, 'UTF-8') : 'Optimal Load';
    $showNumber = $params['showNumber'] ?? true;
    $showLabel = $params['showLabel'] ?? true;
    $unit = !empty($params['unit']) ? htmlspecialchars((string) $params['unit'], ENT_QUOTES, 'UTF-8') : '';
    $displayValue = isset($params['displayValue']) 
      ? htmlspecialchars((string) $params['displayValue'], ENT_QUOTES, 'UTF-8') 
      : $value . '%';

    $classNames = 'mono-chart-svg mono-arc-svg';
    if (!empty($style['svgClass'])) {
      $classNames .= ' ' . htmlspecialchars($style['svgClass'], ENT_QUOTES, 'UTF-8');
    }

    $labelClass = !empty($style['labelClass']) ? ' ' . htmlspecialchars($style['labelClass'], ENT_QUOTES, 'UTF-8') : '';

    $perimeter = 251.33;
    $targetOffset = $perimeter * (1 - ($value / 100));

    ob_start();
    ?>
    <svg viewBox="0 0 240 145" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>" data-chart="arc-meter" <?= $style['tooltipAttr'] ?> data-target-pct="<?= $value ?>" data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
      <!-- Pista de fondo (variación OKLCH suave) -->
      <path d="M 30,125 A 80,80 0 0,1 210,125" class="mono-arc-track" fill="none" stroke-width="16" stroke-linecap="round" />
      
      <!-- Arco dinámico activo con datos para tooltip -->
      <path d="M 30,125 A 80,80 0 0,1 210,125" class="mono-arc-meter-val" data-label="<?= $label ?>" data-val="<?= $displayValue ?>"<?= !empty($unit) ? ' data-unit="' . $unit . '"' : '' ?> fill="none" stroke-width="16" stroke-linecap="round" stroke-dasharray="<?= $perimeter ?> <?= $perimeter ?>" stroke-dashoffset="<?= $perimeter ?>" data-target-offset="<?= number_format($targetOffset, 2, '.', '') ?>" />
      
      <!-- Lecturas centrales con clase de color para etiquetas (colorLabel) -->
      <?php if ($showNumber): ?>
        <text x="120" y="98" text-anchor="middle" class="mono-arc-center-number<?= $labelClass ?>">0%</text>
      <?php endif; ?>
      <?php if ($showLabel): 
        $labelLines = self::wrapTextLines($label, 14, 2);
        if (count($labelLines) > 1):
      ?>
        <!-- Desplazamiento a dos líneas cuando el texto es largo para evitar desbordar el arco -->
        <text x="120" y="117" text-anchor="middle" class="mono-arc-center-label<?= $labelClass ?>" style="font-size: 9px; letter-spacing: 0.04em;">
          <tspan x="120" dy="0"><?= htmlspecialchars($labelLines[0], ENT_QUOTES, 'UTF-8') ?></tspan>
          <tspan x="120" dy="12"><?= htmlspecialchars($labelLines[1], ENT_QUOTES, 'UTF-8') ?></tspan>
        </text>
      <?php else: ?>
        <text x="120" y="125" text-anchor="middle" class="mono-arc-center-label<?= $labelClass ?>"><?= $label ?></text>
      <?php endif; endif; ?>
    </svg>
    <?php
    return trim(ob_get_clean());
  }

  /**
   * 2. STACKED TONES (Barras Apiladas Monocromáticas con Máscara de Cápsula Dinámica)
   * 
   * Divide cada columna en 3 capas apiladas (base principal, cuerpo intermedio, cima oscura)
   * y las enmarca en un clipPath redondeado. Distribuye dinámicamente cualquier cantidad
   * de barras (3, 4, 5, 7, etc.) adaptando anchos y espaciados sin desbordar el contenedor.
   *
   * @param array $params Opciones de configuración:
   *                      - 'quarters' / 'bars': Lista de columnas con claves ['label', 'white', 'mid', 'dark', 'total'].
   *                      - 'color': Selector o clase CSS (ej. 'texto', '.texto', 'color', 'var(--back-color5)').
   *                      - 'colorLabel': Selector o clase CSS para etiquetas (ej. 'textw', '.textw').
   *                      - 'axisLabel': Selector o clase CSS para etiquetas de ejes (ej. 'color1', '.color1').
   *                      - 'transition': Duración de animación en ms (default 400).
   *                      - 'maxVal': Valor máximo del eje Y (opcional; se auto-calcula si no se define).
   * @return string Código SVG puro.
   */
  public static function stackedTones(array $params = []): string
  {
    $style = self::resolveStyle($params);

    // =========================================================================
    // DATOS DE VISTA PREVIA (FALLBACK):
    // Se utilizan exclusivamente como preview/demostración si el usuario no
    // suministra datos dinámicos a través de $params['quarters'] o $params['bars'].
    // =========================================================================
    $previewQuarters = [
      ['label' => 'Q1', 'white' => 45, 'mid' => 30, 'dark' => 20, 'total' => 95],
      ['label' => 'Q2', 'white' => 50, 'mid' => 40, 'dark' => 30, 'total' => 120],
      ['label' => 'Q3', 'white' => 70, 'mid' => 50, 'dark' => 35, 'total' => 155],
      ['label' => 'Q4', 'white' => 55, 'mid' => 35, 'dark' => 25, 'total' => 115],
    ];

    $quarters = !empty($params['quarters']) ? $params['quarters'] : (!empty($params['bars']) ? $params['bars'] : $previewQuarters);
    $numBars = count($quarters);
    if ($numBars === 0) {
      $quarters = $previewQuarters;
      $numBars = count($quarters);
    }

    // Cálculo dinámico de escala del eje Y
    $maxTotalInData = 0;
    foreach ($quarters as $q) {
      $hDark = (float) ($q['dark'] ?? ($q['v3'] ?? 0));
      $hMid = (float) ($q['mid'] ?? ($q['v2'] ?? 0));
      $hWhite = (float) ($q['white'] ?? ($q['v1'] ?? 0));
      $tot = isset($q['total']) ? (float) $q['total'] : ($hDark + $hMid + $hWhite);
      if ($tot > $maxTotalInData) {
        $maxTotalInData = $tot;
      }
    }

    if (isset($params['maxVal']) && (float) $params['maxVal'] > 0) {
      $maxVal = (float) $params['maxVal'];
    } else {
      $maxVal = $maxTotalInData > 0 ? (float) ceil(($maxTotalInData * 1.05) / 20) * 20 : 160.0;
      if ($maxVal < 40) $maxVal = 40.0;
    }

    $scale = 135.0 / $maxVal;
    $uid = self::generateId('stacked');

    // Distribución geométrica dinámica en el área de trazado (x: 44 a 304, total 260px)
    $plotX1 = 44.0;
    $plotX2 = 304.0;
    $availableWidth = $plotX2 - $plotX1;
    $slotWidth = $availableWidth / $numBars;
    $barWidth = min(36.0, max(12.0, $slotWidth * 0.58));
    $rx = min(16.0, $barWidth / 2);

    $gridX1 = 36;
    $gridX2 = 306;

    $classNames = 'mono-chart-svg mono-stacked-svg';
    if (!empty($style['svgClass'])) {
      $classNames .= ' ' . htmlspecialchars($style['svgClass'], ENT_QUOTES, 'UTF-8');
    }

    $axisClass = !empty($style['axisClass']) ? ' ' . htmlspecialchars($style['axisClass'], ENT_QUOTES, 'UTF-8') : '';

    $yTicks = [
      ['y' => 20,  'val' => $maxVal],
      ['y' => 54,  'val' => $maxVal * 0.75],
      ['y' => 88,  'val' => $maxVal * 0.50],
      ['y' => 122, 'val' => $maxVal * 0.25],
      ['y' => 155, 'val' => 0.0],
    ];

    ob_start();
    ?>
    <svg viewBox="0 0 320 190" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>" data-chart="stacked-tones" <?= $style['tooltipAttr'] ?> data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
      <defs>
        <?php foreach ($quarters as $idx => $q): 
          $cx = $plotX1 + ($idx + 0.5) * $slotWidth;
          $qx = $cx - ($barWidth / 2);
          $hDarkDef = (float) ($q['dark'] ?? ($q['v3'] ?? 20)) * $scale;
          $hMidDef = (float) ($q['mid'] ?? ($q['v2'] ?? 30)) * $scale;
          $hWhiteDef = (float) ($q['white'] ?? ($q['v1'] ?? 45)) * $scale;
          $totalH = isset($q['total']) ? ((float) $q['total'] * $scale) : ($hDarkDef + $hMidDef + $hWhiteDef);
          $yBase = 155 - $totalH;
        ?>
        <clipPath id="<?= $uid ?>-clip-<?= $idx ?>">
          <rect class="mono-stacked-clip-rect" x="<?= number_format($qx, 2, '.', '') ?>" y="<?= number_format($yBase, 2, '.', '') ?>" width="<?= number_format($barWidth, 2, '.', '') ?>" height="<?= number_format($totalH, 2, '.', '') ?>" rx="<?= number_format($rx, 2, '.', '') ?>" ry="<?= number_format($rx, 2, '.', '') ?>" data-target-y="<?= number_format($yBase, 2, '.', '') ?>" data-target-h="<?= number_format($totalH, 2, '.', '') ?>" />
        </clipPath>
        <?php endforeach; ?>
      </defs>

      <!-- Rejilla punteada horizontal Y con escala dinámica -->
      <?php foreach ($yTicks as $tick): ?>
      <line x1="<?= $gridX1 ?>" y1="<?= $tick['y'] ?>" x2="<?= $gridX2 ?>" y2="<?= $tick['y'] ?>" class="mono-svg-grid-line" />
      <text x="28" y="<?= $tick['y'] ?>" class="mono-svg-axis-text mono-svg-axis-text-y<?= $axisClass ?>"><?= round($tick['val']) ?></text>
      <?php endforeach; ?>

      <!-- Columnas multicapa apiladas en cápsula distribuidas dinámicamente -->
      <?php foreach ($quarters as $idx => $q): 
        $cx = $plotX1 + ($idx + 0.5) * $slotWidth;
        $qx = $cx - ($barWidth / 2);
        $rawDark = (float) ($q['dark'] ?? ($q['v3'] ?? 20));
        $rawMid = (float) ($q['mid'] ?? ($q['v2'] ?? 30));
        $rawWhite = (float) ($q['white'] ?? ($q['v1'] ?? 45));
        $hDark = $rawDark * $scale;
        $hMid = $rawMid * $scale;
        $hWhite = $rawWhite * $scale;
        $totalH = $hDark + $hMid + $hWhite;
        $yDark = 155 - $totalH;
        $yMid = $yDark + $hDark;
        $yWhite = $yMid + $hMid;
        $totVal = isset($q['total']) ? $q['total'] : round($rawDark + $rawMid + $rawWhite);
      ?>
      <g class="mono-stacked-bar-item" clip-path="url(#<?= $uid ?>-clip-<?= $idx ?>)" data-label="<?= htmlspecialchars((string) $q['label'], ENT_QUOTES, 'UTF-8') ?>" data-total="<?= htmlspecialchars((string) $totVal, ENT_QUOTES, 'UTF-8') ?>" data-white="<?= round($rawWhite) ?>" data-mid="<?= round($rawMid) ?>" data-dark="<?= round($rawDark) ?>">
        <rect x="<?= number_format($qx, 2, '.', '') ?>" y="<?= number_format($yDark, 2, '.', '') ?>" width="<?= number_format($barWidth, 2, '.', '') ?>" height="<?= number_format($hDark + 1, 2, '.', '') ?>" class="mono-layer-top" />
        <rect x="<?= number_format($qx, 2, '.', '') ?>" y="<?= number_format($yMid, 2, '.', '') ?>" width="<?= number_format($barWidth, 2, '.', '') ?>" height="<?= number_format($hMid + 1, 2, '.', '') ?>" class="mono-layer-mid" />
        <rect x="<?= number_format($qx, 2, '.', '') ?>" y="<?= number_format($yWhite, 2, '.', '') ?>" width="<?= number_format($barWidth, 2, '.', '') ?>" height="<?= number_format($hWhite + 2, 2, '.', '') ?>" class="mono-layer-base" />
      </g>
      <?php 
        $axisLines = self::wrapTextLines((string)$q['label'], max(6, (int)($slotWidth / 7)), 2);
        if (count($axisLines) > 1):
      ?>
      <text x="<?= number_format($cx, 2, '.', '') ?>" y="168" class="mono-svg-axis-text mono-svg-axis-text-x<?= $axisClass ?>">
        <tspan x="<?= number_format($cx, 2, '.', '') ?>" dy="0"><?= htmlspecialchars($axisLines[0], ENT_QUOTES, 'UTF-8') ?></tspan>
        <tspan x="<?= number_format($cx, 2, '.', '') ?>" dy="11"><?= htmlspecialchars($axisLines[1], ENT_QUOTES, 'UTF-8') ?></tspan>
      </text>
      <?php else: ?>
      <text x="<?= number_format($cx, 2, '.', '') ?>" y="174" class="mono-svg-axis-text mono-svg-axis-text-x<?= $axisClass ?>"><?= htmlspecialchars((string) $q['label'], ENT_QUOTES, 'UTF-8') ?></text>
      <?php endif; ?>
      <?php endforeach; ?>
    </svg>
    <?php
    return trim(ob_get_clean());
  }

  /**
   * 3. TILE TREEMAP (Partición Dinámica de Mosaicos Vectoriales)
   * 
   * Cuadrícula de partición de mosaicos con esquinas redondeadas, porcentajes y etiquetas.
   * Calcula dinámicamente el layout para cualquier número de bloques (1, 2, 3, 4, 5, 6, etc.)
   * adaptando ancho, alto y distribución al marco sin desbordamientos.
   *
   * @param array $params Opciones de configuración:
   *                      - 'tiles': Lista de bloques con ['label'/'name', 'pct'/'value', 'tag'/'info'].
   *                      - 'color': Selector o clase CSS (ej. 'texto', '.texto', 'color', 'var(--back-color5)').
   *                      - 'colorLabel': Selector o clase CSS para etiquetas (ej. 'textw', '.textw').
   *                      - 'transition': Duración en ms (default 400).
   * @return string Código SVG puro.
   */
  public static function tileTreemap(array $params = []): string
  {
    $style = self::resolveStyle($params);

    // =========================================================================
    // DATOS DE VISTA PREVIA (FALLBACK):
    // Se utilizan exclusivamente como preview/demostración si el usuario no
    // suministra datos dinámicos a través de $params['tiles'].
    // =========================================================================
    $previewTiles = [
      ['label' => 'Storage', 'pct' => 45, 'tag' => 'Primary Vol'],
      ['label' => 'Compute', 'pct' => 30, 'tag' => 'vCPU Units'],
      ['label' => 'Network', 'pct' => 15, 'tag' => 'Egress GB'],
      ['label' => 'Cache',   'pct' => 10, 'tag' => 'In-Memory'],
    ];

    $tiles = !empty($params['tiles']) ? $params['tiles'] : $previewTiles;

    $classNames = 'mono-chart-svg mono-treemap-svg';
    if (!empty($style['svgClass'])) {
      $classNames .= ' ' . htmlspecialchars($style['svgClass'], ENT_QUOTES, 'UTF-8');
    }

    $labelClass = !empty($style['labelClass']) ? ' ' . htmlspecialchars($style['labelClass'], ENT_QUOTES, 'UTF-8') : '';

    // Cálculo dinámico de rectángulos para el Treemap
    $rects = self::computeTreemapLayout($tiles, 4.0, 4.0, 312.0, 182.0, 8.0);

    ob_start();
    ?>
    <svg viewBox="0 0 320 190" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>" data-chart="tile-treemap" <?= $style['tooltipAttr'] ?> data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
      <?php foreach ($rects as $r): 
        $t = $r['tile'];
        $label = htmlspecialchars((string) ($t['label'] ?? ($t['name'] ?? '')), ENT_QUOTES, 'UTF-8');
        $rawPct = $t['pct'] ?? ($t['value'] ?? '');
        $pct = htmlspecialchars(rtrim((string) $rawPct, '%'), ENT_QUOTES, 'UTF-8');
        $tag = htmlspecialchars((string) ($t['tag'] ?? ($t['info'] ?? ($t['desc'] ?? ''))), ENT_QUOTES, 'UTF-8');
        $isCompact = $r['h'] < 52.0;
        $showTag = !empty($tag) && $r['h'] >= 72.0 && $r['w'] >= 75.0;
      ?>
      <g class="mono-treemap-tile-group <?= $r['cls'] ?>" data-label="<?= $label ?>" data-pct="<?= $pct ?>%" data-tag="<?= $tag ?>">
        <rect x="<?= number_format($r['x'], 1, '.', '') ?>" y="<?= number_format($r['y'], 1, '.', '') ?>" width="<?= number_format($r['w'], 1, '.', '') ?>" height="<?= number_format($r['h'], 1, '.', '') ?>" rx="12" class="mono-treemap-tile-rect" />
        
        <?php if ($isCompact): ?>
          <?php if ($r['h'] >= 36.0): ?>
            <!-- Disposición compacta en 2 líneas: Título arriba y porcentaje abajo manteniendo padding estricto sin desbordar -->
            <text x="<?= number_format($r['x'] + 12, 1, '.', '') ?>" y="<?= number_format($r['y'] + 17, 1, '.', '') ?>" class="mono-treemap-title<?= $labelClass ?>" style="font-size: 11px;"><?= $label ?></text>
            <text x="<?= number_format($r['x'] + 12, 1, '.', '') ?>" y="<?= number_format($r['y'] + 33, 1, '.', '') ?>" class="mono-treemap-pct<?= $labelClass ?>" style="font-size: 14px;"><?= $pct ?>%</text>
          <?php else: ?>
            <!-- Disposición compacta en línea para alturas muy bajas con escala protectora -->
            <text x="<?= number_format($r['x'] + 10, 1, '.', '') ?>" y="<?= number_format($r['y'] + ($r['h'] / 2) + 4, 1, '.', '') ?>" class="mono-treemap-title<?= $labelClass ?>" style="font-size: 10px;"><?= $label ?> <tspan class="mono-treemap-pct<?= $labelClass ?>" font-size="11" font-weight="700" dx="4"><?= $pct ?>%</tspan></text>
          <?php endif; ?>
        <?php else: ?>
          <!-- Disposición estándar en bloque con soporte de salto de línea si el título es largo -->
          <?php
            $titleLines = self::wrapTextLines($label, 14, 2);
            if (count($titleLines) > 1):
          ?>
            <text x="<?= number_format($r['x'] + 14, 1, '.', '') ?>" y="<?= number_format($r['y'] + 18, 1, '.', '') ?>" class="mono-treemap-title<?= $labelClass ?>" style="font-size: 11px;">
              <tspan x="<?= number_format($r['x'] + 14, 1, '.', '') ?>" dy="0"><?= htmlspecialchars($titleLines[0], ENT_QUOTES, 'UTF-8') ?></tspan>
              <tspan x="<?= number_format($r['x'] + 14, 1, '.', '') ?>" dy="13"><?= htmlspecialchars($titleLines[1], ENT_QUOTES, 'UTF-8') ?></tspan>
            </text>
            <text x="<?= number_format($r['x'] + 14, 1, '.', '') ?>" y="<?= number_format($r['y'] + 48, 1, '.', '') ?>" class="mono-treemap-pct<?= $labelClass ?>"><?= $pct ?>%</text>
            <?php if ($showTag && $r['h'] >= 84.0): ?>
              <text x="<?= number_format($r['x'] + 14, 1, '.', '') ?>" y="<?= number_format($r['y'] + 68, 1, '.', '') ?>" class="mono-treemap-tag<?= $labelClass ?>"><?= $tag ?></text>
            <?php endif; ?>
          <?php else: ?>
            <text x="<?= number_format($r['x'] + 14, 1, '.', '') ?>" y="<?= number_format($r['y'] + 22, 1, '.', '') ?>" class="mono-treemap-title<?= $labelClass ?>"><?= $label ?></text>
            <text x="<?= number_format($r['x'] + 14, 1, '.', '') ?>" y="<?= number_format($r['y'] + 48, 1, '.', '') ?>" class="mono-treemap-pct<?= $labelClass ?>"><?= $pct ?>%</text>
            <?php if ($showTag): ?>
              <text x="<?= number_format($r['x'] + 14, 1, '.', '') ?>" y="<?= number_format($r['y'] + 68, 1, '.', '') ?>" class="mono-treemap-tag<?= $labelClass ?>"><?= $tag ?></text>
            <?php endif; ?>
          <?php endif; ?>
        <?php endif; ?>
      </g>
      <?php endforeach; ?>
    </svg>
    <?php
    return trim(ob_get_clean());
  }

  /**
   * Particiona dinámicamente un área rectangular para cualquier cantidad de mosaicos (Treemap).
   *
   * @param array $tiles Lista de elementos.
   * @param float $boxX Coordenada X inicial.
   * @param float $boxY Coordenada Y inicial.
   * @param float $boxW Ancho total disponible.
   * @param float $boxH Alto total disponible.
   * @param float $gap Espacio entre mosaicos.
   * @return array Array de rectángulos calculados con coordenadas y dimensiones exactas.
   */
  private static function computeTreemapLayout(
    array $tiles,
    float $boxX = 4.0,
    float $boxY = 4.0,
    float $boxW = 312.0,
    float $boxH = 182.0,
    float $gap = 8.0
  ): array {
    $count = count($tiles);
    if ($count === 0) {
      return [];
    }

    $items = [];
    foreach ($tiles as $idx => $t) {
      $rawPct = $t['pct'] ?? ($t['value'] ?? ($t['total'] ?? 10));
      $num = (float) rtrim((string) $rawPct, '%');
      $items[] = [
        'idx'    => $idx,
        'tile'   => $t,
        'weight' => $num > 0 ? $num : 10.0,
      ];
    }

    // Determinar cantidad óptima de columnas según la cantidad de mosaicos
    if ($count <= 1) {
      $numCols = 1;
    } elseif ($count <= 5) {
      $numCols = 2;
    } elseif ($count <= 8) {
      $numCols = 3;
    } else {
      $numCols = 4;
    }

    // Distribuir elementos entre las columnas buscando equilibrio de pesos
    $cols = array_fill(0, $numCols, []);
    $colWeights = array_fill(0, $numCols, 0.0);

    if ($count === 4) {
      // Para el caso canónico de 4 mosaicos (2x2 asimétrico)
      $cols[0] = [$items[0], $items[2]];
      $cols[1] = [$items[1], $items[3]];
      $colWeights[0] = $items[0]['weight'] + $items[2]['weight'];
      $colWeights[1] = $items[1]['weight'] + $items[3]['weight'];
    } else {
      $sorted = $items;
      usort($sorted, fn($a, $b) => $b['weight'] <=> $a['weight']);

      foreach ($sorted as $it) {
        $minCol = 0;
        $minVal = $colWeights[0];
        for ($c = 1; $c < $numCols; $c++) {
          if ($colWeights[$c] < $minVal) {
            $minVal = $colWeights[$c];
            $minCol = $c;
          }
        }
        $cols[$minCol][] = $it;
        $colWeights[$minCol] += $it['weight'];
      }

      for ($c = 0; $c < $numCols; $c++) {
        usort($cols[$c], fn($a, $b) => $b['weight'] <=> $a['weight']);
      }
    }

    $totalWeight = array_sum($colWeights);
    if ($totalWeight <= 0) $totalWeight = 1.0;

    // Ancho proporcional de columnas
    $usableW = $boxW - ($numCols - 1) * $gap;
    $colWidths = [];
    $allocatedW = 0.0;

    foreach ($cols as $cIdx => $cItems) {
      if ($cIdx === $numCols - 1) {
        $colWidths[$cIdx] = max(40.0, $usableW - $allocatedW);
      } else {
        $rawRatio = $colWeights[$cIdx] / $totalWeight;
        $clampedRatio = max(0.28, min(0.72, $rawRatio));
        $cw = round($usableW * $clampedRatio, 1);
        $colWidths[$cIdx] = $cw;
        $allocatedW += $cw;
      }
    }

    // Asignar rectángulos para cada mosaico
    $rects = [];
    $currentX = $boxX;

    foreach ($cols as $cIdx => $cItems) {
      $cw = $colWidths[$cIdx];
      $numInCol = count($cItems);
      $usableH = $boxH - ($numInCol - 1) * $gap;
      $colSum = $colWeights[$cIdx];

      $currentY = $boxY;
      $allocatedH = 0.0;

      foreach ($cItems as $iInCol => $it) {
        if ($iInCol === $numInCol - 1) {
          $th = max(24.0, $usableH - $allocatedH);
        } else {
          $hRatio = $colSum > 0 ? ($it['weight'] / $colSum) : (1.0 / $numInCol);
          $clampedHRatio = max(0.20, min(0.80, $hRatio));
          $th = round($usableH * $clampedHRatio, 1);
          $allocatedH += $th;
        }

        $clsNum = (($it['idx']) % 4) + 1;
        $rects[$it['idx']] = [
          'x'    => $currentX,
          'y'    => $currentY,
          'w'    => $cw,
          'h'    => $th,
          'cls'  => "mono-tile-{$clsNum}",
          'tile' => $it['tile']
        ];

        $currentY += $th + $gap;
      }

      $currentX += $cw + $gap;
    }

    ksort($rects);
    return $rects;
  }

  /**
   * 4. HYBRID SPLINE (Barras Translúcidas con Curva Spline Continua Dinámica)
   * 
   * Geometría matemática pura sin librerías externas.
   * Distribuye dinámicamente los puntos y las barras a lo largo del eje X
   * para cualquier cantidad de datos sin desbordar el lienzo.
   *
   * @param array $params Opciones de configuración:
   *                      - 'values': Lista de valores numéricos (0 a 100).
   *                      - 'labels': Lista de etiquetas del eje X.
   *                      - 'color': Selector o clase CSS (ej. 'texto', '.texto', 'color', 'var(--back-color5)').
   *                      - 'colorLabel': Selector o clase CSS para etiquetas (ej. 'textw', '.textw').
   *                      - 'axisLabel': Selector o clase CSS para etiquetas de eje (ej. 'color1', '.color1').
   *                      - 'transition': Duración en ms (default 400).
   * @return string Código SVG puro.
   */
  public static function hybridSpline(array $params = []): string
  {
    $style = self::resolveStyle($params);

    // =========================================================================
    // DATOS DE VISTA PREVIA (FALLBACK):
    // Se utilizan exclusivamente como preview/demostración si el usuario no
    // suministra datos dinámicos a través de $params['values'] / $params['labels'].
    // =========================================================================
    $previewValues = [42, 68, 55, 92, 74];
    $previewLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May'];

    $values = !empty($params['values']) ? $params['values'] : $previewValues;
    $labels = !empty($params['labels']) ? $params['labels'] : $previewLabels;
    $displayLabels = !empty($params['displayLabels']) && is_array($params['displayLabels']) ? $params['displayLabels'] : [];
    $displayValues = !empty($params['displayValues']) && is_array($params['displayValues']) ? $params['displayValues'] : [];
    $unit = !empty($params['unit']) ? htmlspecialchars((string) $params['unit'], ENT_QUOTES, 'UTF-8') : '';

    $count = count($values);
    if ($count === 0) {
      $values = $previewValues;
      $labels = $previewLabels;
      $count = count($values);
    }

    $uid = self::generateId('spline');

    $classNames = 'mono-chart-svg mono-hybrid-svg';
    if (!empty($style['svgClass'])) {
      $classNames .= ' ' . htmlspecialchars($style['svgClass'], ENT_QUOTES, 'UTF-8');
    }

    $axisClass = !empty($style['axisClass']) ? ' ' . htmlspecialchars($style['axisClass'], ENT_QUOTES, 'UTF-8') : '';

    // Escala del eje Y y límites máximos
    $maxInData = 0.0;
    foreach ($values as $v) {
      if ((float)$v > $maxInData) $maxInData = (float)$v;
    }

    if (isset($params['maxVal']) && (float) $params['maxVal'] > 0) {
      $maxVal = (float) $params['maxVal'];
    } elseif ($maxInData > 100.0) {
      $maxVal = ceil($maxInData * 1.15);
    } elseif (!empty($params['autoScale']) && $maxInData > 0) {
      $maxVal = ceil($maxInData * 1.15);
    } else {
      $maxVal = 100.0;
    }

    // Distribución geométrica dinámica en el área de trazado
    $plotX1 = 44.0;
    $plotX2 = 280.0;
    $availableWidth = $plotX2 - $plotX1;
    $step = $count > 1 ? ($availableWidth / ($count - 1)) : 0.0;
    $barWidth = min(28.0, max(8.0, ($availableWidth / $count) * 0.52));
    $baselineY = 155.0;
    $maxH = 120.0;

    $points = [];
    $xCoords = [];
    foreach ($values as $idx => $val) {
      $x = $count > 1 ? ($plotX1 + $idx * $step) : ($plotX1 + $availableWidth / 2);
      $xCoords[] = $x;
      $ratio = $maxVal > 0 ? max(0.0, min(1.0, (float) $val / $maxVal)) : 0.0;
      $h = $ratio * $maxH;
      $y = $baselineY - $h;
      $points[] = [
        'x'   => $x,
        'y'   => $y,
        'val' => $val,
        'h'   => $h
      ];
    }

    // Cálculo matemático de Spline Bézier Catmull-Rom a Cúbica
    $pathD = self::computeSplinePath($points, 0.25);
    $areaD = $pathD . ' L ' . end($points)['x'] . ',' . $baselineY . ' L ' . $points[0]['x'] . ',' . $baselineY . ' Z';

    ob_start();
    ?>
    <svg viewBox="0 0 320 180" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>" data-chart="hybrid-spline" <?= $style['tooltipAttr'] ?> data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
      <defs>
        <linearGradient id="<?= $uid ?>-grad" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="var(--chart-base)" stop-opacity="0.35" />
          <stop offset="100%" stop-color="var(--chart-base)" stop-opacity="0.0" />
        </linearGradient>
      </defs>

      <!-- Rejilla punteada horizontal adaptada al ancho disponible -->
      <line x1="28" y1="35" x2="304" y2="35" class="mono-svg-grid-line" />
      <line x1="28" y1="75" x2="304" y2="75" class="mono-svg-grid-line" />
      <line x1="28" y1="115" x2="304" y2="115" class="mono-svg-grid-line" />
      <line x1="28" y1="155" x2="304" y2="155" class="mono-svg-grid-line" />

      <!-- Barras translúcidas de fondo en cápsula distribuidas dinámicamente -->
      <?php foreach ($points as $idx => $pt): 
        $barX = $pt['x'] - ($barWidth / 2);
        $barH = $pt['h'];
        $barY = $baselineY - $barH;
        $rx = min(8.0, $barWidth / 2);
        $barLabel = htmlspecialchars((string) ($displayLabels[$idx] ?? $labels[$idx] ?? ''), ENT_QUOTES, 'UTF-8');
        $dataVal = htmlspecialchars((string) ($displayValues[$idx] ?? (isset($params['valueSuffix']) ? $pt['val'] . ' ' . $params['valueSuffix'] : $pt['val'])), ENT_QUOTES, 'UTF-8');
      ?>
      <rect x="<?= number_format($barX, 2, '.', '') ?>" y="<?= number_format($barY, 2, '.', '') ?>" width="<?= number_format($barWidth, 2, '.', '') ?>" height="<?= number_format($barH, 2, '.', '') ?>" rx="<?= number_format($rx, 2, '.', '') ?>" class="mono-spline-bar-rect" data-label="<?= $barLabel ?>" data-val="<?= $dataVal ?>"<?= !empty($unit) ? ' data-unit="' . $unit . '"' : '' ?> data-target-h="<?= number_format($barH, 2, '.', '') ?>" data-target-y="<?= number_format($barY, 2, '.', '') ?>" />
      <?php endforeach; ?>

      <!-- Área degradada de la curva -->
      <path d="<?= $areaD ?>" fill="url(#<?= $uid ?>-grad)" class="mono-spline-area" />

      <!-- Curva continua de spline principal -->
      <path d="<?= $pathD ?>" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="mono-spline-path" />

      <!-- Vértices / Puntos circulares -->
      <?php foreach ($points as $idx => $pt): 
        $dotLabel = htmlspecialchars((string) ($displayLabels[$idx] ?? $labels[$idx] ?? ''), ENT_QUOTES, 'UTF-8');
        $dataVal = htmlspecialchars((string) ($displayValues[$idx] ?? (isset($params['valueSuffix']) ? $pt['val'] . ' ' . $params['valueSuffix'] : $pt['val'])), ENT_QUOTES, 'UTF-8');
      ?>
        <circle cx="<?= number_format($pt['x'], 2, '.', '') ?>" cy="<?= number_format($pt['y'], 2, '.', '') ?>" r="5" class="mono-spline-dot" data-label="<?= $dotLabel ?>" data-val="<?= $dataVal ?>"<?= !empty($unit) ? ' data-unit="' . $unit . '"' : '' ?> />
      <?php endforeach; ?>

      <!-- Etiquetas del eje X con axisLabel distribuidas dinámicamente -->
      <?php foreach ($labels as $idx => $lbl): 
        $x = $xCoords[$idx] ?? ($plotX1 + $idx * $step);
        $axisLines = self::wrapTextLines((string)$lbl, max(6, (int)(($step > 0 ? $step : $availableWidth) / 7)), 2);
        if (count($axisLines) > 1):
      ?>
      <text x="<?= number_format($x, 2, '.', '') ?>" y="168" class="mono-svg-axis-text mono-svg-axis-text-x<?= $axisClass ?>">
        <tspan x="<?= number_format($x, 2, '.', '') ?>" dy="0"><?= htmlspecialchars($axisLines[0], ENT_QUOTES, 'UTF-8') ?></tspan>
        <tspan x="<?= number_format($x, 2, '.', '') ?>" dy="11"><?= htmlspecialchars($axisLines[1], ENT_QUOTES, 'UTF-8') ?></tspan>
      </text>
      <?php else: ?>
      <text x="<?= number_format($x, 2, '.', '') ?>" y="172" class="mono-svg-axis-text mono-svg-axis-text-x<?= $axisClass ?>"><?= htmlspecialchars((string) $lbl, ENT_QUOTES, 'UTF-8') ?></text>
      <?php endif; ?>
      <?php endforeach; ?>
    </svg>
    <?php
    return trim(ob_get_clean());
  }

  /**
   * 5. PILL PILLARS (Columnas Emparejadas en Cápsula Redondeada Dinámica)
   *
   * @param array $params Opciones de configuración:
   *                      - 'pairs': Lista de parejas [['label' => 'W1', 'v1' => 85, 'v2' => 45], ...]
   *                      - 'color': Selector o clase CSS (ej. 'texto', '.texto', 'color', 'var(--back-color5)').
   *                      - 'colorLabel': Selector o clase CSS para etiquetas (ej. 'textw', '.textw').
   *                      - 'axisLabel': Selector o clase CSS para etiquetas de eje (ej. 'color1', '.color1').
   *                      - 'transition': Duración en ms (default 400).
   * @return string Código SVG puro.
   */
  public static function pillPillars(array $params = []): string
  {
    $style = self::resolveStyle($params);

    // =========================================================================
    // DATOS DE VISTA PREVIA (FALLBACK):
    // Se utilizan exclusivamente como preview/demostración si el usuario no
    // suministra datos dinámicos a través de $params['pairs'].
    // =========================================================================
    $previewPairs = [
      ['label' => 'W1', 'v1' => 75, 'v2' => 40],
      ['label' => 'W2', 'v1' => 95, 'v2' => 60],
      ['label' => 'W3', 'v1' => 60, 'v2' => 30],
      ['label' => 'W4', 'v1' => 88, 'v2' => 52],
    ];

    $pairs = !empty($params['pairs']) ? $params['pairs'] : $previewPairs;
    $count = count($pairs);
    if ($count === 0) {
      $pairs = $previewPairs;
      $count = count($pairs);
    }

    $classNames = 'mono-chart-svg mono-pillars-svg';
    if (!empty($style['svgClass'])) {
      $classNames .= ' ' . htmlspecialchars($style['svgClass'], ENT_QUOTES, 'UTF-8');
    }

    $axisClass = !empty($style['axisClass']) ? ' ' . htmlspecialchars($style['axisClass'], ENT_QUOTES, 'UTF-8') : '';

    $baselineY = 155.0;
    $maxH = 125.0;

    // Distribución geométrica dinámica en el área de trazado
    $plotX1 = 36.0;
    $plotX2 = 304.0;
    $availableWidth = $plotX2 - $plotX1;
    $slotWidth = $availableWidth / $count;
    $pillarWidth = min(16.0, max(6.0, $slotWidth * 0.22));
    $pillarGap = min(4.0, max(2.0, $pillarWidth * 0.25));
    $rx = min(7.0, $pillarWidth / 2);

    ob_start();
    ?>
    <svg viewBox="0 0 320 180" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>" data-chart="pill-pillars" <?= $style['tooltipAttr'] ?> data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
      <!-- Rejilla punteada horizontal -->
      <line x1="28" y1="30" x2="306" y2="30" class="mono-svg-grid-line" />
      <line x1="28" y1="72" x2="306" y2="72" class="mono-svg-grid-line" />
      <line x1="28" y1="114" x2="306" y2="114" class="mono-svg-grid-line" />
      <line x1="28" y1="155" x2="306" y2="155" class="mono-svg-grid-line" />

      <?php foreach ($pairs as $idx => $p): 
        $cx = $plotX1 + ($idx + 0.5) * $slotWidth;
        $x1 = $cx - $pillarWidth - ($pillarGap / 2);
        $x2 = $cx + ($pillarGap / 2);

        $v1 = (float) ($p['v1'] ?? 50);
        $v2 = (float) ($p['v2'] ?? 30);

        $h1 = ($v1 / 100) * $maxH;
        $y1 = $baselineY - $h1;

        $h2 = ($v2 / 100) * $maxH;
        $y2 = $baselineY - $h2;
        $pLabel = htmlspecialchars((string) ($p['label'] ?? ''), ENT_QUOTES, 'UTF-8');
      ?>
      <g class="mono-pillar-pair-group" data-label="<?= $pLabel ?>" data-v1="<?= $v1 ?>" data-v2="<?= $v2 ?>">
        <!-- Pilar primario (color base) -->
        <rect x="<?= number_format($x1, 2, '.', '') ?>" y="<?= number_format($y1, 2, '.', '') ?>" width="<?= number_format($pillarWidth, 2, '.', '') ?>" height="<?= number_format($h1, 2, '.', '') ?>" rx="<?= number_format($rx, 2, '.', '') ?>" class="mono-pillar-primary" data-label="<?= $pLabel ?>" data-val="<?= $v1 ?>" data-series="1" data-target-h="<?= number_format($h1, 2, '.', '') ?>" data-target-y="<?= number_format($y1, 2, '.', '') ?>" />
        <!-- Pilar secundario (segunda variación OKLCH) -->
        <rect x="<?= number_format($x2, 2, '.', '') ?>" y="<?= number_format($y2, 2, '.', '') ?>" width="<?= number_format($pillarWidth, 2, '.', '') ?>" height="<?= number_format($h2, 2, '.', '') ?>" rx="<?= number_format($rx, 2, '.', '') ?>" class="mono-pillar-secondary" data-label="<?= $pLabel ?>" data-val="<?= $v2 ?>" data-series="2" data-target-h="<?= number_format($h2, 2, '.', '') ?>" data-target-y="<?= number_format($y2, 2, '.', '') ?>" />
        <!-- Etiqueta eje X con axisLabel -->
        <?php 
          $axisLines = self::wrapTextLines((string)($p['label'] ?? ''), max(6, (int)($slotWidth / 7)), 2);
          if (count($axisLines) > 1):
        ?>
        <text x="<?= number_format($cx, 2, '.', '') ?>" y="168" class="mono-svg-axis-text mono-svg-axis-text-x<?= $axisClass ?>">
          <tspan x="<?= number_format($cx, 2, '.', '') ?>" dy="0"><?= htmlspecialchars($axisLines[0], ENT_QUOTES, 'UTF-8') ?></tspan>
          <tspan x="<?= number_format($cx, 2, '.', '') ?>" dy="11"><?= htmlspecialchars($axisLines[1], ENT_QUOTES, 'UTF-8') ?></tspan>
        </text>
        <?php else: ?>
        <text x="<?= number_format($cx, 2, '.', '') ?>" y="172" class="mono-svg-axis-text mono-svg-axis-text-x<?= $axisClass ?>"><?= $pLabel ?></text>
        <?php endif; ?>
      </g>
      <?php endforeach; ?>
    </svg>
    <?php
    return trim(ob_get_clean());
  }

  /**
   * 6. SIMPLE BARS (Barras Verticales en Cápsula Dinámica)
   * 
   * Gráfico de barras verticales simple monocromático con soporte para fechas y horarios.
   * Permite configurar etiquetas de eje X (ej. días de la semana, fechas)
   * y eje Y (ej. horarios del día, cantidades o porcentajes), con opción de mostrar
   * únicamente las etiquetas del eje X ('showAxisY' => false).
   *
   * @param array $params Opciones de configuración:
   *                      - 'values' / 'bars' / 'data': Lista de valores o ítems [['label' => 'Lun 12', 'value' => 14.5, 'display' => '14:30'], ...]
   *                      - 'labels': Lista de etiquetas del eje X.
   *                      - 'yLabels': Lista personalizada de etiquetas del eje Y (ej. ['24:00', '18:00', '12:00', '06:00', '00:00']).
   *                      - 'showAxisY': bool (default true). Si es false, oculta las etiquetas del eje Y y aprovecha todo el ancho.
   *                      - 'showAxisX': bool (default true). Si es false, oculta las etiquetas del eje X.
   *                      - 'minVal': float (default 0.0).
   *                      - 'maxVal': float (opcional, auto-calculado de los valores o 24.0 / 100.0).
   *                      - 'valueSuffix': string (ej. 'h', '%', etc.).
   *                      - 'color': Selector o clase CSS (ej. 'texto', '.texto', 'color', 'var(--back-color5)').
   *                      - 'colorLabel': Selector o clase CSS para etiquetas.
   *                      - 'axisLabel': Selector o clase CSS para etiquetas de ejes.
   *                      - 'transition': Duración en ms (default 400).
   *                      - 'tooltip': bool (default true).
   *                      - 'barWidth': float (ancho fijo de barra, opcional).
   * @return string Código SVG puro.
   */
  public static function simpleBars(array $params = []): string
  {
    $style = self::resolveStyle($params);

    // =========================================================================
    // DATOS DE VISTA PREVIA (FALLBACK):
    // Se utilizan exclusivamente como preview/demostración si el usuario no
    // suministra datos dinámicos a través de $params['bars'] / $params['values'].
    // =========================================================================
    $previewBars = [
      ['label' => 'Lun 12', 'value' => 8.5,  'display' => '08:30'],
      ['label' => 'Mar 13', 'value' => 14.0, 'display' => '14:00'],
      ['label' => 'Mié 14', 'value' => 19.5, 'display' => '19:30'],
      ['label' => 'Jue 15', 'value' => 11.0, 'display' => '11:00'],
      ['label' => 'Vie 16', 'value' => 21.0, 'display' => '21:00'],
      ['label' => 'Sáb 17', 'value' => 16.5, 'display' => '16:30'],
      ['label' => 'Dom 18', 'value' => 13.0, 'display' => '13:00'],
    ];

    $rawItems = $params['bars'] ?? ($params['data'] ?? []);
    $items = [];

    if (!empty($rawItems)) {
      foreach ($rawItems as $idx => $it) {
        if (is_array($it)) {
          $val = (float) ($it['value'] ?? ($it['val'] ?? ($it['v'] ?? 0)));
          $lbl = (string) ($it['label'] ?? ($it['name'] ?? ($it['date'] ?? ($it['day'] ?? ''))));
          $disp = isset($it['display']) ? (string) $it['display'] : (isset($it['formatted']) ? (string) $it['formatted'] : null);
          $items[] = ['val' => $val, 'label' => $lbl, 'display' => $disp];
        } else {
          $val = (float) $it;
          $lbl = (string) ($params['labels'][$idx] ?? '');
          $disp = isset($params['displays'][$idx]) ? (string) $params['displays'][$idx] : null;
          $items[] = ['val' => $val, 'label' => $lbl, 'display' => $disp];
        }
      }
    } elseif (!empty($params['values'])) {
      foreach ($params['values'] as $idx => $val) {
        $lbl = (string) ($params['labels'][$idx] ?? '');
        $disp = isset($params['displays'][$idx]) ? (string) $params['displays'][$idx] : null;
        $items[] = ['val' => (float) $val, 'label' => $lbl, 'display' => $disp];
      }
    } else {
      $items = $previewBars;
    }

    $count = count($items);
    if ($count === 0) {
      $items = $previewBars;
      $count = count($items);
    }

    $showAxisY = isset($params['showAxisY']) ? (bool) $params['showAxisY'] : (isset($params['showY']) ? (bool) $params['showY'] : true);
    $showAxisX = isset($params['showAxisX']) ? (bool) $params['showAxisX'] : (isset($params['showX']) ? (bool) $params['showX'] : true);
    $valueSuffix = (string) ($params['valueSuffix'] ?? '');

    $classNames = 'mono-chart-svg mono-simple-bars-svg';
    if (!empty($style['svgClass'])) {
      $classNames .= ' ' . htmlspecialchars($style['svgClass'], ENT_QUOTES, 'UTF-8');
    }
    $axisClass = !empty($style['axisClass']) ? ' ' . htmlspecialchars($style['axisClass'], ENT_QUOTES, 'UTF-8') : '';

    // Geometría y límites del área de trazado
    $topY = 32.0;
    $baselineY = 155.0;
    $maxH = $baselineY - $topY;

    if ($showAxisY) {
      $plotX1 = 44.0;
      $plotX2 = 304.0;
      $gridX1 = 36.0;
      $gridX2 = 306.0;
    } else {
      $plotX1 = 16.0;
      $plotX2 = 304.0;
      $gridX1 = 16.0;
      $gridX2 = 304.0;
    }

    $availableWidth = $plotX2 - $plotX1;
    $slotWidth = $availableWidth / $count;
    $barWidth = isset($params['barWidth']) && (float) $params['barWidth'] > 0
      ? (float) $params['barWidth']
      : min(32.0, max(8.0, $slotWidth * 0.54));
    $rx = min(8.0, $barWidth / 2);

    // Escala del eje Y
    $maxInData = 0.0;
    foreach ($items as $it) {
      if ($it['val'] > $maxInData) $maxInData = $it['val'];
    }

    if (isset($params['maxVal']) && (float) $params['maxVal'] > 0) {
      $maxVal = (float) $params['maxVal'];
    } elseif (!empty($params['yLabels']) && is_array($params['yLabels'])) {
      $firstLabel = reset($params['yLabels']);
      if (preg_match('/^(\d+)(?::\d+)?/', (string) $firstLabel, $m) && (float) $m[1] > 0) {
        $maxVal = (float) $m[1];
      } else {
        $maxVal = $maxInData > 0 ? (float) ceil(($maxInData * 1.1) / 5) * 5 : 24.0;
      }
    } elseif ($maxInData <= 24.0 && !empty($params['isTime'])) {
      $maxVal = 24.0;
    } else {
      $maxVal = $maxInData > 0 ? (float) ceil(($maxInData * 1.1) / 5) * 5 : 24.0;
      if ($maxVal <= 0) $maxVal = 24.0;
    }

    // Ticks para el eje Y
    $yTicks = [];
    if (!empty($params['yLabels']) && is_array($params['yLabels'])) {
      $customTicks = array_values($params['yLabels']);
      $nTicks = count($customTicks);
      foreach ($customTicks as $tIdx => $tLbl) {
        $ty = $nTicks > 1 ? $topY + ($tIdx * $maxH / ($nTicks - 1)) : $baselineY;
        $yTicks[] = ['y' => $ty, 'label' => (string) $tLbl];
      }
    } else {
      // 5 ticks por defecto
      for ($t = 0; $t <= 4; $t++) {
        $ratio = $t / 4.0;
        $ty = $topY + ($ratio * $maxH);
        $valAtTick = $maxVal * (1.0 - $ratio);
        if ($maxVal == 24.0) {
          $hours = (int) round($valAtTick);
          $lblTick = sprintf('%02d:00', $hours);
        } else {
          $lblTick = round($valAtTick) . $valueSuffix;
        }
        $yTicks[] = ['y' => $ty, 'label' => $lblTick];
      }
    }

    ob_start();
    ?>
    <svg viewBox="0 0 320 180" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>" data-chart="simple-bars" <?= $style['tooltipAttr'] ?> data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
      <!-- Rejilla punteada horizontal -->
      <?php foreach ($yTicks as $tick): ?>
      <line x1="<?= $gridX1 ?>" y1="<?= number_format($tick['y'], 2, '.', '') ?>" x2="<?= $gridX2 ?>" y2="<?= number_format($tick['y'], 2, '.', '') ?>" class="mono-svg-grid-line" />
      <?php if ($showAxisY): ?>
      <text x="28" y="<?= number_format($tick['y'], 2, '.', '') ?>" class="mono-svg-axis-text mono-svg-axis-text-y<?= $axisClass ?>"><?= htmlspecialchars($tick['label'], ENT_QUOTES, 'UTF-8') ?></text>
      <?php endif; ?>
      <?php endforeach; ?>

      <!-- Barras de cápsula verticales (pista de fondo + barra animada) -->
      <?php foreach ($items as $idx => $it): 
        $cx = $plotX1 + ($idx + 0.5) * $slotWidth;
        $barX = $cx - ($barWidth / 2);
        $barH = $maxVal > 0 ? (min($it['val'], $maxVal) / $maxVal) * $maxH : 0.0;
        $barY = $baselineY - $barH;
        $lbl = htmlspecialchars((string) ($it['label'] ?? ''), ENT_QUOTES, 'UTF-8');
        $displayVal = htmlspecialchars((string) ($it['display'] ?? ($it['val'] . $valueSuffix)), ENT_QUOTES, 'UTF-8');
      ?>
      <g class="mono-simple-bar-item" data-label="<?= $lbl ?>" data-val="<?= $displayVal ?>">
        <!-- Pista translúcida suave de fondo en cápsula -->
        <rect x="<?= number_format($barX, 2, '.', '') ?>" y="<?= number_format($topY, 2, '.', '') ?>" width="<?= number_format($barWidth, 2, '.', '') ?>" height="<?= number_format($maxH, 2, '.', '') ?>" rx="<?= number_format($rx, 2, '.', '') ?>" class="mono-simple-bar-track" />
        <!-- Barra dinámica activa -->
        <rect x="<?= number_format($barX, 2, '.', '') ?>" y="<?= number_format($barY, 2, '.', '') ?>" width="<?= number_format($barWidth, 2, '.', '') ?>" height="<?= number_format($barH, 2, '.', '') ?>" rx="<?= number_format($rx, 2, '.', '') ?>" class="mono-simple-bar-rect" data-target-h="<?= number_format($barH, 2, '.', '') ?>" data-target-y="<?= number_format($barY, 2, '.', '') ?>" />
        <!-- Etiqueta eje X con axisLabel y salto de línea protector -->
        <?php if ($showAxisX): 
          $axisLines = self::wrapTextLines((string)($it['label'] ?? ''), max(6, (int)($slotWidth / 7)), 2);
          if (count($axisLines) > 1):
        ?>
        <text x="<?= number_format($cx, 2, '.', '') ?>" y="168" class="mono-svg-axis-text mono-svg-axis-text-x<?= $axisClass ?>">
          <tspan x="<?= number_format($cx, 2, '.', '') ?>" dy="0"><?= htmlspecialchars($axisLines[0], ENT_QUOTES, 'UTF-8') ?></tspan>
          <tspan x="<?= number_format($cx, 2, '.', '') ?>" dy="11"><?= htmlspecialchars($axisLines[1], ENT_QUOTES, 'UTF-8') ?></tspan>
        </text>
        <?php else: ?>
        <text x="<?= number_format($cx, 2, '.', '') ?>" y="172" class="mono-svg-axis-text mono-svg-axis-text-x<?= $axisClass ?>"><?= $lbl ?></text>
        <?php endif; endif; ?>
      </g>
      <?php endforeach; ?>
    </svg>
    <?php
    return trim(ob_get_clean());
  }

  /**
   * 7. HORIZONTAL BARS (Barras Horizontales en Cápsula Dinámica)
   * 
   * Gráfico de barras horizontal monocromático con soporte para fechas y horarios.
   * Permite configurar etiquetas de eje Y a la izquierda (ej. días o franjas horarias)
   * y eje X en la base (ej. horas, duración o valores), con opción de mostrar
   * únicamente las etiquetas del eje X ('showAxisY' => false).
   *
   * @param array $params Opciones de configuración:
   *                      - 'values' / 'bars' / 'data': Lista de valores o ítems [['label' => '08:00', 'value' => 6.5, 'display' => '6.5 hrs'], ...]
   *                      - 'labels' / 'yLabels': Lista de etiquetas del eje Y (a la izquierda de cada barra).
   *                      - 'xLabels': Lista personalizada de etiquetas del eje X (en la base del gráfico).
   *                      - 'showAxisY': bool (default true). Si es false, oculta las etiquetas de la izquierda y aprovecha todo el ancho.
   *                      - 'showAxisX': bool (default true). Si es false, oculta las etiquetas del eje X.
   *                      - 'minVal': float (default 0.0).
   *                      - 'maxVal': float (opcional, auto-calculado).
   *                      - 'valueSuffix': string (ej. 'h', '%', etc.).
   *                      - 'color': Selector o clase CSS (ej. 'texto', '.texto', 'color', 'var(--back-color5)').
   *                      - 'colorLabel': Selector o clase CSS para etiquetas.
   *                      - 'axisLabel': Selector o clase CSS para etiquetas de ejes.
   *                      - 'transition': Duración de animación en ms (default 400).
   *                      - 'tooltip': bool (default true).
   *                      - 'barHeight': float (alto fijo de barra, opcional).
   * @return string Código SVG puro.
   */
  public static function horizontalBars(array $params = []): string
  {
    $style = self::resolveStyle($params);

    // =========================================================================
    // DATOS DE VISTA PREVIA (FALLBACK):
    // Se utilizan exclusivamente como preview/demostración si el usuario no
    // suministra datos dinámicos a través de $params['bars'] / $params['values'].
    // =========================================================================
    $previewBars = [
      ['label' => '08:00', 'value' => 4.5, 'display' => '4.5 hrs'],
      ['label' => '11:00', 'value' => 7.2, 'display' => '7.2 hrs'],
      ['label' => '14:00', 'value' => 8.0, 'display' => '8.0 hrs'],
      ['label' => '17:00', 'value' => 6.0, 'display' => '6.0 hrs'],
      ['label' => '20:00', 'value' => 3.5, 'display' => '3.5 hrs'],
    ];

    $rawItems = $params['bars'] ?? ($params['data'] ?? []);
    $items = [];

    if (!empty($rawItems)) {
      foreach ($rawItems as $idx => $it) {
        if (is_array($it)) {
          $val = (float) ($it['value'] ?? ($it['val'] ?? ($it['v'] ?? 0)));
          $lbl = (string) ($it['label'] ?? ($it['name'] ?? ($it['date'] ?? ($it['day'] ?? ''))));
          $disp = isset($it['display']) ? (string) $it['display'] : (isset($it['formatted']) ? (string) $it['formatted'] : null);
          $items[] = ['val' => $val, 'label' => $lbl, 'display' => $disp];
        } else {
          $val = (float) $it;
          $lbl = (string) ($params['labels'][$idx] ?? ($params['yLabels'][$idx] ?? ''));
          $disp = isset($params['displays'][$idx]) ? (string) $params['displays'][$idx] : null;
          $items[] = ['val' => $val, 'label' => $lbl, 'display' => $disp];
        }
      }
    } elseif (!empty($params['values'])) {
      foreach ($params['values'] as $idx => $val) {
        $lbl = (string) ($params['labels'][$idx] ?? ($params['yLabels'][$idx] ?? ''));
        $disp = isset($params['displays'][$idx]) ? (string) $params['displays'][$idx] : null;
        $items[] = ['val' => (float) $val, 'label' => $lbl, 'display' => $disp];
      }
    } else {
      $items = $previewBars;
    }

    $count = count($items);
    if ($count === 0) {
      $items = $previewBars;
      $count = count($items);
    }

    $showAxisY = isset($params['showAxisY']) ? (bool) $params['showAxisY'] : (isset($params['showY']) ? (bool) $params['showY'] : true);
    $showAxisX = isset($params['showAxisX']) ? (bool) $params['showAxisX'] : (isset($params['showX']) ? (bool) $params['showX'] : true);
    $valueSuffix = (string) ($params['valueSuffix'] ?? '');

    $classNames = 'mono-chart-svg mono-hbar-svg';
    if (!empty($style['svgClass'])) {
      $classNames .= ' ' . htmlspecialchars($style['svgClass'], ENT_QUOTES, 'UTF-8');
    }
    $axisClass = !empty($style['axisClass']) ? ' ' . htmlspecialchars($style['axisClass'], ENT_QUOTES, 'UTF-8') : '';

    // Geometría del área horizontal
    $topY = 20.0;
    $bottomY = 155.0;
    $availableH = $bottomY - $topY;

    if ($showAxisY) {
      $plotX1 = 64.0;
      $plotX2 = 304.0;
    } else {
      $plotX1 = 16.0;
      $plotX2 = 304.0;
    }

    $availableW = $plotX2 - $plotX1;
    $slotH = $availableH / $count;
    $barHeight = isset($params['barHeight']) && (float) $params['barHeight'] > 0
      ? (float) $params['barHeight']
      : min(22.0, max(8.0, $slotH * 0.54));
    $ry = min(8.0, $barHeight / 2);

    // Escala del eje X
    $maxInData = 0.0;
    foreach ($items as $it) {
      if ($it['val'] > $maxInData) $maxInData = $it['val'];
    }

    if (isset($params['maxVal']) && (float) $params['maxVal'] > 0) {
      $maxVal = (float) $params['maxVal'];
    } elseif (!empty($params['xLabels']) && is_array($params['xLabels'])) {
      $lastLabel = end($params['xLabels']);
      if (preg_match('/^(\d+(?:\.\d+)?)/', (string) $lastLabel, $m) && (float) $m[1] > 0) {
        $maxVal = (float) $m[1];
      } else {
        $maxVal = $maxInData > 0 ? (float) ceil(($maxInData * 1.1) / 2) * 2 : 10.0;
      }
    } else {
      $maxVal = $maxInData > 0 ? (float) ceil(($maxInData * 1.1) / 2) * 2 : 10.0;
      if ($maxVal <= 0) $maxVal = 10.0;
    }

    // Ticks para el eje X
    $xTicks = [];
    if (!empty($params['xLabels']) && is_array($params['xLabels'])) {
      $customTicks = array_values($params['xLabels']);
      $nTicks = count($customTicks);
      foreach ($customTicks as $tIdx => $tLbl) {
        $tx = $nTicks > 1 ? $plotX1 + ($tIdx * $availableW / ($nTicks - 1)) : $plotX2;
        $xTicks[] = ['x' => $tx, 'label' => (string) $tLbl];
      }
    } else {
      for ($t = 0; $t <= 4; $t++) {
        $ratio = $t / 4.0;
        $tx = $plotX1 + ($ratio * $availableW);
        $valAtTick = $maxVal * $ratio;
        $xTicks[] = ['x' => $tx, 'label' => round($valAtTick, 1) . $valueSuffix];
      }
    }

    ob_start();
    ?>
    <svg viewBox="0 0 320 180" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>" data-chart="horizontal-bars" <?= $style['tooltipAttr'] ?> data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
      <!-- Rejilla vertical punteada -->
      <?php foreach ($xTicks as $tick): ?>
      <line x1="<?= number_format($tick['x'], 2, '.', '') ?>" y1="<?= number_format($topY - 4, 2, '.', '') ?>" x2="<?= number_format($tick['x'], 2, '.', '') ?>" y2="<?= number_format($bottomY, 2, '.', '') ?>" class="mono-svg-grid-line" />
      <?php if ($showAxisX): ?>
      <text x="<?= number_format($tick['x'], 2, '.', '') ?>" y="172" class="mono-svg-axis-text mono-svg-axis-text-x<?= $axisClass ?>"><?= htmlspecialchars($tick['label'], ENT_QUOTES, 'UTF-8') ?></text>
      <?php endif; ?>
      <?php endforeach; ?>

      <!-- Barras horizontales en cápsula (pista + barra activa) -->
      <?php foreach ($items as $idx => $it): 
        $cy = $topY + ($idx + 0.5) * $slotH;
        $barY = $cy - ($barHeight / 2);
        $barW = $maxVal > 0 ? (min($it['val'], $maxVal) / $maxVal) * $availableW : 0.0;
        $lbl = htmlspecialchars((string) ($it['label'] ?? ''), ENT_QUOTES, 'UTF-8');
        $displayVal = htmlspecialchars((string) ($it['display'] ?? ($it['val'] . $valueSuffix)), ENT_QUOTES, 'UTF-8');
      ?>
      <g class="mono-hbar-item" data-label="<?= $lbl ?>" data-val="<?= $displayVal ?>">
        <!-- Pista horizontal translúcida de fondo -->
        <rect x="<?= number_format($plotX1, 2, '.', '') ?>" y="<?= number_format($barY, 2, '.', '') ?>" width="<?= number_format($availableW, 2, '.', '') ?>" height="<?= number_format($barHeight, 2, '.', '') ?>" rx="<?= number_format($ry, 2, '.', '') ?>" class="mono-hbar-track" />
        <!-- Barra dinámica activa -->
        <rect x="<?= number_format($plotX1, 2, '.', '') ?>" y="<?= number_format($barY, 2, '.', '') ?>" width="<?= number_format($barW, 2, '.', '') ?>" height="<?= number_format($barHeight, 2, '.', '') ?>" rx="<?= number_format($ry, 2, '.', '') ?>" class="mono-hbar-rect" data-target-w="<?= number_format($barW, 2, '.', '') ?>" />
        <!-- Etiqueta eje Y a la izquierda de la barra -->
        <?php if ($showAxisY): ?>
        <text x="<?= number_format($plotX1 - 8, 2, '.', '') ?>" y="<?= number_format($cy + 3.5, 2, '.', '') ?>" class="mono-svg-axis-text mono-svg-axis-text-y<?= $axisClass ?>" text-anchor="end"><?= $lbl ?></text>
        <?php endif; ?>
      </g>
      <?php endforeach; ?>
    </svg>
    <?php
    return trim(ob_get_clean());
  }

  /**
   * 8. BULLET TARGET (Barras de Comparación con Marcador de Benchmark)
   * 
   * Gráfico de barras horizontales con indicador de meta o benchmark (Bullet Chart).
   * Muestra el progreso del valor actual frente al valor objetivo para múltiples métricas.
   *
   * @param array $params Opciones de configuración:
   *                      - 'targets' / 'items' / 'bars': Lista de objetivos [['label' => 'Throughput', 'value' => 82, 'target' => 75], ...]
   *                      - 'targetColor': Color o clase CSS para el marcador de benchmark (default '#10b981').
   *                      - 'color': Selector o clase CSS para las barras (ej. 'texto', 'textw', 'color', etc.).
   *                      - 'colorLabel': Selector o clase CSS para etiquetas de texto.
   *                      - 'transition': Duración de animación en ms (default 400).
   *                      - 'tooltip': bool (default true).
   * @return string Código SVG puro.
   */
  public static function bulletTarget(array $params = []): string
  {
    $style = self::resolveStyle($params);

    // =========================================================================
    // DATOS DE VISTA PREVIA (FALLBACK):
    // Se utilizan exclusivamente como preview si no se suministran datos.
    // Idénticos a la especificación canónica: Throughput, Latency, Uptime.
    // =========================================================================
    $previewTargets = [
      ['label' => 'Throughput', 'value' => 82, 'target' => 75, 'suffix' => '%'],
      ['label' => 'Latency',    'value' => 65, 'target' => 80, 'suffix' => '%'],
      ['label' => 'Uptime',     'value' => 95, 'target' => 90, 'suffix' => '%'],
    ];

    $rawItems = $params['targets'] ?? ($params['items'] ?? ($params['bars'] ?? $previewTargets));
    if (empty($rawItems)) {
      $rawItems = $previewTargets;
    }

    $items = [];
    foreach ($rawItems as $it) {
      if (is_array($it)) {
        $val = (float) ($it['value'] ?? ($it['val'] ?? 0));
        $tgt = (float) ($it['target'] ?? ($it['goal'] ?? ($it['benchmark'] ?? 0)));
        $lbl = (string) ($it['label'] ?? ($it['title'] ?? ($it['name'] ?? '')));
        $suf = (string) ($it['suffix'] ?? '%');
        $max = isset($it['max']) && (float) $it['max'] > 0 ? (float) $it['max'] : 100.0;
        $items[] = [
          'label'  => $lbl,
          'val'    => $val,
          'target' => $tgt,
          'suffix' => $suf,
          'max'    => $max,
        ];
      }
    }

    $count = count($items);
    if ($count === 0) {
      $items = [
        ['label' => 'Throughput', 'val' => 82.0, 'target' => 75.0, 'suffix' => '%', 'max' => 100.0],
        ['label' => 'Latency',    'val' => 65.0, 'target' => 80.0, 'suffix' => '%', 'max' => 100.0],
        ['label' => 'Uptime',     'val' => 95.0, 'target' => 90.0, 'suffix' => '%', 'max' => 100.0],
      ];
      $count = 3;
    }

    $classNames = 'mono-chart-svg mono-bullet-svg';
    if (!empty($style['svgClass'])) {
      $classNames .= ' ' . htmlspecialchars($style['svgClass'], ENT_QUOTES, 'UTF-8');
    }
    $labelClass = !empty($style['labelClass']) ? ' ' . htmlspecialchars($style['labelClass'], ENT_QUOTES, 'UTF-8') : '';

    // Color del marcador de benchmark (default esmeralda #10b981)
    $targetColorRaw = (string) ($params['targetColor'] ?? '#10b981');
    $targetToken = self::resolveColorToken($targetColorRaw);
    $targetColorCss = $targetToken['cssValue'] !== 'currentColor' ? $targetToken['cssValue'] : '#10b981';

    // Dimensiones y distribución de las filas
    $trackX = 14.0;
    $trackW = 292.0;
    $barH = 14.0;
    $rx = 7.0;

    $totalH = 180.0;
    $paddingTop = 14.0;
    $paddingBottom = 16.0;
    $availableH = $totalH - $paddingTop - $paddingBottom;
    $slotH = $availableH / $count;

    ob_start();
    ?>
    <svg viewBox="0 0 320 180" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>; --chart-bullet-target: <?= $targetColorCss ?>;" data-chart="bullet-target" <?= $style['tooltipAttr'] ?> data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
      <?php foreach ($items as $idx => $it): 
        $slotY = $paddingTop + ($idx * $slotH);
        $textY = $slotY + 12.0;
        $barY = $slotY + 20.0;

        $maxVal = $it['max'] > 0 ? $it['max'] : 100.0;
        $ratioVal = max(0.0, min(1.0, $it['val'] / $maxVal));
        $ratioTgt = max(0.0, min(1.0, $it['target'] / $maxVal));

        $valW = round($ratioVal * $trackW, 2);
        $tgtX = round($trackX + ($ratioTgt * $trackW), 2);

        $valStr = round($it['val']) . $it['suffix'];
        $tgtStr = round($it['target']) . $it['suffix'];
        $diff = round($it['val'] - $it['target']);
        $diffStr = ($diff >= 0 ? '+' : '') . $diff . $it['suffix'];

        $lbl = htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8');
      ?>
      <g class="mono-bullet-row" data-label="<?= $lbl ?>" data-val="<?= $valStr ?>" data-target="<?= $tgtStr ?>" data-diff="<?= $diffStr ?>">
        <!-- Textos superiores: Etiqueta a la izquierda y Valor/Benchmark a la derecha -->
        <text x="<?= number_format($trackX, 1, '.', '') ?>" y="<?= number_format($textY, 1, '.', '') ?>" class="mono-bullet-label<?= $labelClass ?>"><?= $lbl ?></text>
        <text x="<?= number_format($trackX + $trackW, 1, '.', '') ?>" y="<?= number_format($textY, 1, '.', '') ?>" text-anchor="end" class="mono-bullet-val<?= $labelClass ?>">
          <tspan font-weight="700"><?= $valStr ?></tspan>
          <tspan font-weight="400" opacity="0.65"> / <?= $tgtStr ?></tspan>
        </text>

        <!-- Pista de fondo horizontal en cápsula (dark charcoal) -->
        <rect x="<?= number_format($trackX, 1, '.', '') ?>" y="<?= number_format($barY, 1, '.', '') ?>" width="<?= number_format($trackW, 1, '.', '') ?>" height="<?= number_format($barH, 1, '.', '') ?>" rx="<?= number_format($rx, 1, '.', '') ?>" class="mono-bullet-track" />

        <!-- Barra activa en cápsula (color base o tono principal) -->
        <rect x="<?= number_format($trackX, 1, '.', '') ?>" y="<?= number_format($barY, 1, '.', '') ?>" width="<?= number_format($valW, 1, '.', '') ?>" height="<?= number_format($barH, 1, '.', '') ?>" rx="<?= number_format($rx, 1, '.', '') ?>" class="mono-bullet-bar" data-target-w="<?= number_format($valW, 1, '.', '') ?>" />

        <!-- Marcador vertical de benchmark objetivo -->
        <rect x="<?= number_format($tgtX - 1.5, 1, '.', '') ?>" y="<?= number_format($barY - 1.0, 1, '.', '') ?>" width="3" height="<?= number_format($barH + 2.0, 1, '.', '') ?>" rx="1.5" class="mono-bullet-marker" />
      </g>
      <?php endforeach; ?>
    </svg>
    <?php
    return trim(ob_get_clean());
  }

  /**
   * 9. ROUNDED DONUT (Rosca Monocromática con Extremos de Arco Suaves - Soft Arc Caps)
   * 
   * Gráfico de donut con segmentos circulares de extremos redondeados y separaciones definidas.
   *
   * @param array $params Opciones de configuración:
   *                      - 'items' / 'segments' / 'data': Lista de segmentos:
   *                        [['label' => 'Core Engine', 'value' => 45, 'color' => '...'], ...]
   *                      - 'centerValue': Texto del centro (ej. '100%'). Si no se define, suma los valores.
   *                      - 'centerLabel': Subtítulo central (default 'Mono Arc').
   *                      - 'showCenter': Mostrar lecturas en el centro del donut (default true).
   *                      - 'showLegend': Mostrar leyenda inferior en el SVG (default true).
   *                      - 'startAngle': Ángulo inicial en grados (default -135.0).
   *                      - 'color': Selector o clase CSS para el color base.
   *                      - 'colorLabel': Selector o clase CSS para etiquetas de texto.
   *                      - 'axisLabel': Selector o clase CSS para subtítulos y leyenda.
   *                      - 'transition': Duración de animación en ms (default 400).
   *                      - 'tooltip': bool (default true).
   * @return string Código SVG puro.
   */
  public static function roundedDonut(array $params = []): string
  {
    $style = self::resolveStyle($params);

    // =========================================================================
    // DATOS DE VISTA PREVIA (FALLBACK):
    // Se utilizan exclusivamente como preview si no se suministran datos.
    // Idénticos a la especificación canónica: Core Engine, UI Layer, Assets, Other.
    // =========================================================================
    $previewItems = [
      ['label' => 'Core Engine', 'value' => 45.0, 'suffix' => '%'],
      ['label' => 'UI Layer',    'value' => 28.0, 'suffix' => '%'],
      ['label' => 'Assets',      'value' => 17.0, 'suffix' => '%'],
      ['label' => 'Other',       'value' => 10.0, 'suffix' => '%'],
    ];

    $rawItems = $params['items'] ?? ($params['segments'] ?? ($params['data'] ?? $previewItems));
    if (empty($rawItems)) {
      $rawItems = $previewItems;
    }

    $items = [];
    $totalVal = 0.0;
    foreach ($rawItems as $it) {
      if (is_array($it)) {
        $val = (float) ($it['value'] ?? ($it['val'] ?? 0.0));
        $lbl = (string) ($it['label'] ?? ($it['name'] ?? ($it['title'] ?? '')));
        $suf = (string) ($it['suffix'] ?? '%');
        $col = isset($it['color']) ? (string) $it['color'] : null;
        $totalVal += $val;
        $items[] = [
          'label'  => $lbl,
          'value'  => $val,
          'suffix' => $suf,
          'color'  => $col,
        ];
      }
    }

    if ($totalVal <= 0.0) {
      $totalVal = 100.0;
    }
    $count = count($items);
    if ($count === 0) {
      $items = [
        ['label' => 'Core Engine', 'value' => 45.0, 'suffix' => '%', 'color' => null],
        ['label' => 'UI Layer',    'value' => 28.0, 'suffix' => '%', 'color' => null],
        ['label' => 'Assets',      'value' => 17.0, 'suffix' => '%', 'color' => null],
        ['label' => 'Other',       'value' => 10.0, 'suffix' => '%', 'color' => null],
      ];
      $count = 4;
      $totalVal = 100.0;
    }

    $classNames = 'mono-chart-svg mono-donut-svg';
    if (!empty($style['svgClass'])) {
      $classNames .= ' ' . htmlspecialchars($style['svgClass'], ENT_QUOTES, 'UTF-8');
    }
    $labelClass = !empty($style['labelClass']) ? ' ' . htmlspecialchars($style['labelClass'], ENT_QUOTES, 'UTF-8') : '';
    $axisClass = !empty($style['axisClass']) ? ' ' . htmlspecialchars($style['axisClass'], ENT_QUOTES, 'UTF-8') : '';

    $centerValue = isset($params['centerValue']) ? (string) $params['centerValue'] : (round($totalVal) . '%');
    $centerLabel = isset($params['centerLabel']) ? (string) $params['centerLabel'] : 'Mono Arc';
    $showCenter = $params['showCenter'] ?? true;
    $showLegend = $params['showLegend'] ?? true;

    // Dimensiones geométricas del donut
    $cx = 160.0;
    $cy = 92.0;
    $r = 58.0;
    $strokeWidth = 20.0;
    $startAngle = isset($params['startAngle']) ? (float) $params['startAngle'] : -135.0;

    // Cálculo dinámico de separaciones angulares (gaps) para Soft Arc Caps
    $gapPerSegment = $count > 1 ? min(28.0, max(14.0, 96.0 / $count)) : 0.0;
    $totalGapDeg = $count * $gapPerSegment;
    $availableDeg = 360.0 - $totalGapDeg;

    // Generación de segmentos
    $currentAngle = $startAngle;
    $renderedSegments = [];

    foreach ($items as $idx => $it) {
      $ratio = max(0.01, $it['value'] / $totalVal);
      $arcDeg = max(1.0, $ratio * $availableDeg);

      $startDeg = $currentAngle;
      $endDeg = $currentAngle + $arcDeg;

      $startRad = deg2rad($startDeg);
      $endRad = deg2rad($endDeg);

      $x1 = $cx + ($r * cos($startRad));
      $y1 = $cy + ($r * sin($startRad));
      $x2 = $cx + ($r * cos($endRad));
      $y2 = $cy + ($r * sin($endRad));

      $largeArc = ($arcDeg > 180.0) ? 1 : 0;
      $d = sprintf("M %.2f %.2f A %.2f %.2f 0 %d 1 %.2f %.2f", $x1, $y1, $r, $r, $largeArc, $x2, $y2);
      $arcLength = round(deg2rad($arcDeg) * $r, 2);

      // Color monocromático con gradación tonal decreciente idéntica a la imagen
      if (!empty($it['color'])) {
        $token = self::resolveColorToken($it['color']);
        $segColor = $token['cssValue'] !== 'currentColor' ? $token['cssValue'] : 'var(--chart-base-color, currentColor)';
      } else {
        if ($count === 1) {
          $segColor = "var(--chart-base-color, #ffffff)";
        } elseif ($idx === 0) {
          $segColor = "var(--chart-base-color, #ffffff)";
        } else {
          $lightness = round(0.76 - (($idx - 1) * (0.48 / max(1, $count - 2))), 2);
          $lightness = max(0.25, min(0.90, $lightness));
          $hexFallback = match ($idx) {
            1 => '#b8b8be',
            2 => '#76767c',
            3 => '#404046',
            default => sprintf('#%02x%02x%02x', (int)($lightness * 255), (int)($lightness * 255), (int)($lightness * 255)),
          };
          $segColor = "oklch(from var(--chart-base-color, {$hexFallback}) {$lightness} c h)";
        }
      }

      $pctStr = round(($it['value'] / $totalVal) * 100) . '%';
      $valStr = round($it['value']) . $it['suffix'];

      $renderedSegments[] = [
        'd'         => $d,
        'length'    => $arcLength,
        'color'     => $segColor,
        'label'     => $it['label'],
        'valStr'    => $valStr,
        'pctStr'    => $pctStr,
      ];

      $currentAngle += $arcDeg + $gapPerSegment;
    }

    ob_start();
    ?>
    <svg viewBox="0 0 320 210" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>" data-chart="rounded-donut" <?= $style['tooltipAttr'] ?> data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
      <!-- Segmentos de Donut con Soft Arc Caps -->
      <g class="mono-donut-ring">
        <?php foreach ($renderedSegments as $seg): 
          $lbl = htmlspecialchars($seg['label'], ENT_QUOTES, 'UTF-8');
        ?>
        <path d="<?= $seg['d'] ?>" class="mono-donut-segment" fill="none" stroke="<?= $seg['color'] ?>" stroke-width="<?= $strokeWidth ?>" stroke-linecap="round" stroke-dasharray="<?= $seg['length'] ?> <?= $seg['length'] ?>" stroke-dashoffset="<?= $seg['length'] ?>" data-length="<?= $seg['length'] ?>" data-label="<?= $lbl ?>" data-val="<?= $seg['valStr'] ?>" data-pct="<?= $seg['pctStr'] ?>" style="--seg-color: <?= $seg['color'] ?>;" />
        <?php endforeach; ?>
      </g>

      <!-- Lectura Central -->
      <?php if ($showCenter): ?>
      <g class="mono-donut-center">
        <text x="160.0" y="89.0" text-anchor="middle" class="mono-donut-center-val<?= $labelClass ?>"><?= htmlspecialchars($centerValue, ENT_QUOTES, 'UTF-8') ?></text>
        <text x="160.0" y="105.0" text-anchor="middle" class="mono-donut-center-lbl<?= $axisClass ?>"><?= htmlspecialchars($centerLabel, ENT_QUOTES, 'UTF-8') ?></text>
      </g>
      <?php endif; ?>

      <!-- Leyenda Horizontal Inferior -->
      <?php if ($showLegend && $count > 0): 
        $legendPaddingX = 20.0;
        $legendW = 320.0 - ($legendPaddingX * 2);
        $blockW = $legendW / $count;
      ?>
      <g class="mono-donut-legend">
        <?php foreach ($renderedSegments as $idx => $seg): 
          $itemX = $legendPaddingX + ($idx * $blockW) + 4.0;
          $lbl = htmlspecialchars($seg['label'], ENT_QUOTES, 'UTF-8');
        ?>
        <circle cx="<?= number_format($itemX, 1, '.', '') ?>" cy="191.0" r="3.5" fill="<?= $seg['color'] ?>" />
        <text x="<?= number_format($itemX + 8.0, 1, '.', '') ?>" y="194.0" class="mono-donut-legend-text<?= $axisClass ?>"><?= $lbl ?></text>
        <?php endforeach; ?>
      </g>
      <?php endif; ?>
    </svg>
    <?php
    return trim(ob_get_clean());
  }

  /**
   * 10. PYRAMID STACK (Pila Jerárquica Piramidal con Cápsulas Redondeadas)
   * 
   * Muestra niveles jerárquicos o arquitectónicos organizados en forma piramidal
   * con cápsulas redondeadas de anchura progresiva y gradación tonal monocromática.
   *
   * @param array $params Opciones de configuración:
   *                      - 'items' / 'tiers' / 'layers': Lista de capas de arriba hacia abajo:
   *                        [['label' => 'Executive', 'value' => ...], ['label' => 'Management'], ...]
   *                      - 'minWidth': Ancho de la cápsula superior (default 84.0).
   *                      - 'maxWidth': Ancho de la cápsula base inferior (default 254.0).
   *                      - 'barHeight': Altura de cada cápsula (default 26.0).
   *                      - 'gapY': Espacio vertical entre cápsulas (default 9.0).
   *                      - 'color': Selector o clase CSS para el color base.
   *                      - 'transition': Duración de animación en ms (default 400).
   *                      - 'tooltip': bool (default true).
   * @return string Código SVG puro.
   */
  public static function pyramidStack(array $params = []): string
  {
    $style = self::resolveStyle($params);

    // =========================================================================
    // DATOS DE VISTA PREVIA (FALLBACK):
    // Idénticos a la especificación canónica: Executive, Management, Senior Staff, Core Team.
    // =========================================================================
    $previewTiers = [
      ['label' => 'Executive'],
      ['label' => 'Management'],
      ['label' => 'Senior Staff'],
      ['label' => 'Core Team'],
    ];

    $rawItems = $params['items'] ?? ($params['tiers'] ?? ($params['layers'] ?? $previewTiers));
    if (empty($rawItems)) {
      $rawItems = $previewTiers;
    }

    $items = [];
    foreach ($rawItems as $it) {
      if (is_array($it)) {
        $lbl = (string) ($it['label'] ?? ($it['name'] ?? ($it['title'] ?? '')));
        $val = isset($it['value']) ? (string) $it['value'] : null;
        $desc = isset($it['desc']) ? (string) $it['desc'] : null;
        $col = isset($it['color']) ? (string) $it['color'] : null;
        $textCol = isset($it['textColor']) ? (string) $it['textColor'] : null;
        $items[] = [
          'label'     => $lbl,
          'value'     => $val,
          'desc'      => $desc,
          'color'     => $col,
          'textColor' => $textCol,
        ];
      }
    }

    $count = count($items);
    if ($count === 0) {
      $items = $previewTiers;
      $count = 4;
    }

    $classNames = 'mono-chart-svg mono-pyramid-svg';
    if (!empty($style['svgClass'])) {
      $classNames .= ' ' . htmlspecialchars($style['svgClass'], ENT_QUOTES, 'UTF-8');
    }
    $labelClass = !empty($style['labelClass']) ? ' ' . htmlspecialchars($style['labelClass'], ENT_QUOTES, 'UTF-8') : '';

    $cx = 160.0;
    $barH = isset($params['barHeight']) && (float) $params['barHeight'] > 0 ? (float) $params['barHeight'] : 26.0;
    $gapY = isset($params['gapY']) && (float) $params['gapY'] >= 0 ? (float) $params['gapY'] : 9.0;
    $rx = $barH / 2.0;

    $totalStackH = ($count * $barH) + (($count - 1) * $gapY);
    $startY = round((180.0 - $totalStackH) / 2.0, 1);

    $minW = isset($params['minWidth']) && (float) $params['minWidth'] > 0 ? (float) $params['minWidth'] : 84.0;
    $maxW = isset($params['maxWidth']) && (float) $params['maxWidth'] > 0 ? (float) $params['maxWidth'] : 254.0;

    ob_start();
    ?>
    <svg viewBox="0 0 320 180" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>" data-chart="pyramid-stack" <?= $style['tooltipAttr'] ?> data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
      <?php foreach ($items as $idx => $it): 
        $ratio = $count > 1 ? ($idx / ($count - 1)) : 1.0;
        $w = round($minW + ($ratio * ($maxW - $minW)), 1);
        $x = round($cx - ($w / 2.0), 1);
        $y = round($startY + ($idx * ($barH + $gapY)), 1);
        $textY = round($y + ($barH / 2.0) + 4.5, 1);

        // Determinación de color del fondo de la cápsula
        if (!empty($it['color'])) {
          $token = self::resolveColorToken($it['color']);
          $tierBg = $token['cssValue'] !== 'currentColor' ? $token['cssValue'] : 'var(--chart-base-color, currentColor)';
          $isLight = false;
        } else {
          if ($count === 1 || $idx === 0) {
            $tierBg = 'var(--chart-base-color, #ffffff)';
            $isLight = true;
          } elseif ($idx === 1 && $count === 4) {
            $tierBg = 'oklch(from var(--chart-base-color, #b5b5ba) 0.74 c h)';
            $isLight = true;
          } elseif ($idx === 2 && $count === 4) {
            $tierBg = 'oklch(from var(--chart-base-color, #737378) 0.50 c h)';
            $isLight = false;
          } elseif ($idx === 3 && $count === 4) {
            $tierBg = 'oklch(from var(--chart-base-color, #424246) 0.30 c h)';
            $isLight = false;
          } else {
            $lightness = round(0.74 - (($idx - 1) * (0.46 / max(1, $count - 2))), 2);
            $lightness = max(0.24, min(0.92, $lightness));
            $isLight = $lightness >= 0.65;
            $hexFallback = sprintf('#%02x%02x%02x', (int)($lightness * 255), (int)($lightness * 255), (int)($lightness * 255));
            $tierBg = "oklch(from var(--chart-base-color, {$hexFallback}) {$lightness} c h)";
          }
        }

        // Determinación del color del texto respetando colorLabel y textColor
        if (!empty($it['textColor'])) {
          $tierTextAttr = ' fill="' . htmlspecialchars($it['textColor'], ENT_QUOTES, 'UTF-8') . '"';
          $tierTextClass = '';
        } else {
          $isPureWhiteMono = in_array(strtolower($style['colorRaw'] ?? ''), ['texto', 'textw', '#fff', '#ffffff', 'fff', 'white'], true);
          if ($isPureWhiteMono && $idx < 2 && ($style['labelRaw'] ?? '') === 'textw') {
            $tierTextAttr = ' fill="#121214"';
            $tierTextClass = '';
          } else {
            $tierTextAttr = ' fill="var(--chart-label-color, currentColor)"';
            $tierTextClass = $labelClass;
          }
        }

        $fontWeight = ($idx === 0) ? '700' : '600';
        $fontSize = ($idx === 0) ? '12px' : '11.5px';
        $lbl = htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8');
        $tierName = 'Nivel ' . ($idx + 1) . ' de ' . $count;
        $valAttr = !empty($it['value']) ? ' data-val="' . htmlspecialchars($it['value'], ENT_QUOTES, 'UTF-8') . '"' : '';
      ?>
      <g class="mono-pyramid-tier" data-label="<?= $lbl ?>" data-tier="<?= $tierName ?>"<?= $valAttr ?>>
        <!-- Cápsula redondeada de nivel jerárquico -->
        <rect x="<?= number_format($x, 1, '.', '') ?>" y="<?= number_format($y, 1, '.', '') ?>" width="<?= number_format($w, 1, '.', '') ?>" height="<?= number_format($barH, 1, '.', '') ?>" rx="<?= number_format($rx, 1, '.', '') ?>" class="mono-pyramid-rect" data-target-w="<?= number_format($w, 1, '.', '') ?>" data-target-x="<?= number_format($x, 1, '.', '') ?>" fill="<?= $tierBg ?>" style="--tier-color: <?= $tierBg ?>;" />

        <!-- Texto centrado dentro de la cápsula -->
        <text x="<?= number_format($cx, 1, '.', '') ?>" y="<?= number_format($textY, 1, '.', '') ?>" text-anchor="middle" class="mono-pyramid-text<?= $tierTextClass ?>"<?= $tierTextAttr ?> style="font-size: <?= $fontSize ?>; font-weight: <?= $fontWeight ?>;"><?= $lbl ?></text>
      </g>
      <?php endforeach; ?>
    </svg>
    <?php
    return trim(ob_get_clean());
  }

  /**
   * 11. SPLINE DYNAMICS (Curva Dinámica Dual / Single con Extremos Redondeados)
   * 
   * Gráfico de línea Bézier cúbica continua (Catmull-Rom) con soporte dual
   * (línea primaria continua con nodos circulares y línea secundaria de referencia punteada).
   *
   * @param array $params Opciones de configuración:
   *                      - 'primary' / 'series1' / 'data': Puntos principales [['label' => 'Jan', 'value' => 25], ...]
   *                      - 'secondary' / 'series2': Puntos secundarios punteados de referencia.
   *                      - 'showSecondary': Mostrar u ocultar la serie secundaria (default true).
   *                      - 'showPoints': Mostrar círculos de nodo en serie principal (default true).
   *                      - 'yLabels': Etiquetas del eje Y (default ['100', '75', '50', '25', '0']).
   *                      - 'maxVal': Valor máximo del eje Y (default 100.0).
   *                      - 'tension': Tensión de curvatura Catmull-Rom (default 0.25).
   *                      - 'color': Selector o clase CSS para la línea principal.
   *                      - 'colorLabel': Selector o clase CSS para etiquetas de ejes.
   *                      - 'axisLabel': Selector o clase CSS para cuadrícula y números.
   *                      - 'transition': Duración de animación en ms (default 400).
   *                      - 'tooltip': bool (default true).
   * @return string Código SVG puro.
   */
  public static function splineDynamics(array $params = []): string
  {
    $style = self::resolveStyle($params);

    // =========================================================================
    // DATOS DE VISTA PREVIA (FALLBACK):
    // Idénticos a la especificación canónica: Jan (25/18), Feb (45/32), Mar (38/30),
    // Apr (65/48), May (52/41), Jun (84/60).
    // =========================================================================
    $previewPrimary = [
      ['label' => 'Jan', 'value' => 25.0],
      ['label' => 'Feb', 'value' => 45.0],
      ['label' => 'Mar', 'value' => 38.0],
      ['label' => 'Apr', 'value' => 65.0],
      ['label' => 'May', 'value' => 52.0],
      ['label' => 'Jun', 'value' => 84.0],
    ];

    $previewSecondary = [
      ['label' => 'Jan', 'value' => 18.0],
      ['label' => 'Feb', 'value' => 32.0],
      ['label' => 'Mar', 'value' => 30.0],
      ['label' => 'Apr', 'value' => 48.0],
      ['label' => 'May', 'value' => 41.0],
      ['label' => 'Jun', 'value' => 60.0],
    ];

    $rawPrimary = $params['primary'] ?? ($params['series1'] ?? ($params['data'] ?? $previewPrimary));
    if (empty($rawPrimary)) {
      $rawPrimary = $previewPrimary;
    }

    $rawSecondary = $params['secondary'] ?? ($params['series2'] ?? $previewSecondary);
    if (empty($rawSecondary)) {
      $rawSecondary = $previewSecondary;
    }

    $primaryItems = [];
    foreach ($rawPrimary as $it) {
      if (is_array($it)) {
        $primaryItems[] = [
          'label' => (string) ($it['label'] ?? ($it['name'] ?? '')),
          'value' => (float) ($it['value'] ?? ($it['val'] ?? 0.0)),
        ];
      }
    }

    $secondaryItems = [];
    foreach ($rawSecondary as $it) {
      if (is_array($it)) {
        $secondaryItems[] = [
          'label' => (string) ($it['label'] ?? ($it['name'] ?? '')),
          'value' => (float) ($it['value'] ?? ($it['val'] ?? 0.0)),
        ];
      }
    }

    $count = count($primaryItems);
    if ($count === 0) {
      $primaryItems = $previewPrimary;
      $secondaryItems = $previewSecondary;
      $count = 6;
    }

    $classNames = 'mono-chart-svg mono-spline-dyn-svg';
    if (!empty($style['svgClass'])) {
      $classNames .= ' ' . htmlspecialchars($style['svgClass'], ENT_QUOTES, 'UTF-8');
    }
    $labelClass = !empty($style['labelClass']) ? ' ' . htmlspecialchars($style['labelClass'], ENT_QUOTES, 'UTF-8') : '';
    $axisClass = !empty($style['axisClass']) ? ' ' . htmlspecialchars($style['axisClass'], ENT_QUOTES, 'UTF-8') : '';

    $showSecondary = $params['showSecondary'] ?? true;
    $showPoints = $params['showPoints'] ?? true;
    $tension = isset($params['tension']) && is_numeric($params['tension']) ? (float) $params['tension'] : 0.25;
    $maxVal = isset($params['maxVal']) && (float) $params['maxVal'] > 0 ? (float) $params['maxVal'] : 100.0;
    $yLabels = $params['yLabels'] ?? ['100', '75', '50', '25', '0'];

    // Dimensiones y área de trazado
    $padL = 36.0;
    $padR = 18.0;
    $padT = 20.0;
    $padB = 25.0;
    $plotW = 320.0 - $padL - $padR;
    $plotH = 180.0 - $padT - $padB;

    // Puntos geométricos para ambas curvas
    $primPoints = [];
    $secPoints = [];
    $stepX = $count > 1 ? ($plotW / ($count - 1)) : $plotW;

    $totalPrimLen = 0.0;
    for ($i = 0; $i < $count; $i++) {
      $px = round($padL + ($i * $stepX), 1);
      
      $pVal = $primaryItems[$i]['value'];
      $pyPrim = round($padT + ($plotH * (1.0 - max(0.0, min(1.0, $pVal / $maxVal)))), 1);
      $primPoints[] = [
        'x'     => $px,
        'y'     => $pyPrim,
        'label' => $primaryItems[$i]['label'],
        'val'   => $pVal,
      ];

      $sVal = isset($secondaryItems[$i]['value']) ? $secondaryItems[$i]['value'] : 0.0;
      $pySec = round($padT + ($plotH * (1.0 - max(0.0, min(1.0, $sVal / $maxVal)))), 1);
      $secPoints[] = [
        'x'     => $px,
        'y'     => $pySec,
        'label' => isset($secondaryItems[$i]['label']) ? $secondaryItems[$i]['label'] : $primaryItems[$i]['label'],
        'val'   => $sVal,
      ];

      if ($i > 0) {
        $dx = $px - $primPoints[$i - 1]['x'];
        $dy = $pyPrim - $primPoints[$i - 1]['y'];
        $totalPrimLen += sqrt(($dx * $dx) + ($dy * $dy));
      }
    }

    $primCurveD = self::computeSplinePath($primPoints, $tension);
    $secCurveD = self::computeSplinePath($secPoints, $tension);
    $approxLength = round(max(300.0, $totalPrimLen * 1.08), 1);

    // Líneas del eje Y
    $yCount = count($yLabels);
    $yStepH = $yCount > 1 ? ($plotH / ($yCount - 1)) : $plotH;

    ob_start();
    ?>
    <svg viewBox="0 0 320 180" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>" data-chart="spline-dynamics" <?= $style['tooltipAttr'] ?> data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
      <!-- Rejilla Horizontal Punteada y Marcas Eje Y -->
      <g class="mono-spline-dyn-grid">
        <?php for ($j = 0; $j < $yCount; $j++): 
          $gridY = round($padT + ($j * $yStepH), 1);
        ?>
        <line x1="<?= number_format($padL, 1, '.', '') ?>" y1="<?= number_format($gridY, 1, '.', '') ?>" x2="<?= number_format($padL + $plotW, 1, '.', '') ?>" y2="<?= number_format($gridY, 1, '.', '') ?>" stroke="rgba(255,255,255,0.08)" stroke-dasharray="2,3" stroke-width="1" />
        <text x="<?= number_format($padL - 8.0, 1, '.', '') ?>" y="<?= number_format($gridY + 3.5, 1, '.', '') ?>" text-anchor="end" class="mono-spline-dyn-axis-text<?= $axisClass ?>"><?= htmlspecialchars($yLabels[$j], ENT_QUOTES, 'UTF-8') ?></text>
        <?php endfor; ?>
      </g>

      <!-- Curva Secundaria Punteada (Referencia / Dual Mode) -->
      <?php if ($showSecondary && !empty($secCurveD)): ?>
      <path d="<?= $secCurveD ?>" class="mono-spline-secondary-path" fill="none" stroke="currentColor" opacity="0.40" stroke-width="2" stroke-dasharray="4,4" stroke-linecap="round" />
      <?php endif; ?>

      <!-- Curva Primaria Continua Dinámica -->
      <path d="<?= $primCurveD ?>" class="mono-spline-primary-path" fill="none" stroke="var(--chart-tone-1, #ffffff)" stroke-width="3.5" stroke-linecap="round" stroke-dasharray="<?= $approxLength ?> <?= $approxLength ?>" stroke-dashoffset="<?= $approxLength ?>" data-length="<?= $approxLength ?>" />

      <!-- Puntos / Nodos Circulares y Áreas de Tooltip -->
      <?php if ($showPoints): ?>
      <g class="mono-spline-dyn-nodes">
        <?php foreach ($primPoints as $k => $pt): 
          $lbl = htmlspecialchars($pt['label'], ENT_QUOTES, 'UTF-8');
          $val = $pt['val'] . 'k';
          $secVal = isset($secPoints[$k]['val']) ? ($secPoints[$k]['val'] . 'k') : '';
        ?>
        <g class="mono-spline-dyn-node" data-label="<?= $lbl ?>" data-val="<?= $val ?>" data-ref="<?= $secVal ?>">
          <circle cx="<?= number_format($pt['x'], 1, '.', '') ?>" cy="<?= number_format($pt['y'], 1, '.', '') ?>" r="4.5" fill="var(--chart-tone-1, #ffffff)" stroke="#0c0c0e" stroke-width="2" class="mono-spline-dyn-dot" />
          <!-- Zona invisible de activación de tooltip -->
          <circle cx="<?= number_format($pt['x'], 1, '.', '') ?>" cy="<?= number_format($pt['y'], 1, '.', '') ?>" r="14.0" fill="transparent" class="mono-spline-dyn-hit" />
        </g>
        <?php endforeach; ?>
      </g>
      <?php endif; ?>

      <!-- Etiquetas Eje X (Meses) -->
      <g class="mono-spline-dyn-x-axis">
        <?php foreach ($primPoints as $pt): 
          $lbl = htmlspecialchars($pt['label'], ENT_QUOTES, 'UTF-8');
        ?>
        <text x="<?= number_format($pt['x'], 1, '.', '') ?>" y="<?= number_format(180.0 - 7.0, 1, '.', '') ?>" text-anchor="middle" class="mono-spline-dyn-x-label<?= $labelClass ?>"><?= $lbl ?></text>
        <?php endforeach; ?>
      </g>
    </svg>
    <?php
    return trim(ob_get_clean());
  }

  /**
   * Calcula matemáticamente los comandos SVG de una curva Spline Bézier cúbica continua (Catmull-Rom).
   *
   * @param array $points Array de puntos [['x' => float, 'y' => float], ...].
   * @param float $tension Tensión de curvatura (default 0.25).
   * @return string Comando de trazado SVG "M ... C ...".
   */
  private static function computeSplinePath(array $points, float $tension = 0.25): string
  {
    $count = count($points);
    if ($count === 0) return '';
    if ($count === 1) return 'M ' . $points[0]['x'] . ',' . $points[0]['y'];

    $d = 'M ' . number_format($points[0]['x'], 1, '.', '') . ',' . number_format($points[0]['y'], 1, '.', '');

    for ($i = 0; $i < $count - 1; $i++) {
      $p0 = $i > 0 ? $points[$i - 1] : $points[$i];
      $p1 = $points[$i];
      $p2 = $points[$i + 1];
      $p3 = $i < $count - 2 ? $points[$i + 2] : $p2;

      $cp1x = $p1['x'] + ($p2['x'] - $p0['x']) * $tension;
      $cp1y = $p1['y'] + ($p2['y'] - $p0['y']) * $tension;
      $cp2x = $p2['x'] - ($p3['x'] - $p1['x']) * $tension;
      $cp2y = $p2['y'] - ($p3['y'] - $p1['y']) * $tension;

      $d .= ' C ' . number_format($cp1x, 1, '.', '') . ',' . number_format($cp1y, 1, '.', '')
        . ' ' . number_format($cp2x, 1, '.', '') . ',' . number_format($cp2y, 1, '.', '')
        . ' ' . number_format($p2['x'], 1, '.', '') . ',' . number_format($p2['y'], 1, '.', '');
    }

    return $d;
  }

  /**
   * Genera el gráfico MATRIX HEATMAP (Densidad de Actividad Monocromática en Rejilla 7x5 con Nodos Redondeados).
   *
   * @param array $params Parámetros del gráfico:
   *   - matrix: array 2D de filas con valores o intensidades (0.0..1.0).
   *   - rows: array de etiquetas de filas (default: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri']).
   *   - cols: array de etiquetas de columnas o nombres de slots (default: ['1', '2', '3', '4', '5', '6', '7']).
   *   - rx: radio de curvatura de esquinas de cada nodo (default: 7.0).
   *   - color: token de color base ('texto', 'color3', '#38bdf8', etc.).
   *   - axisLabel: color de los textos del eje Y.
   *   - transition: duración de la animación en ms (default: 700).
   * @return string SVG renderizado.
   */
  public static function matrixHeatmap(array $params = []): string
  {
    $style = self::resolveStyle($params);

    $rowLabels = isset($params['rows']) && is_array($params['rows']) && !empty($params['rows'])
      ? array_values($params['rows'])
      : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];

    $colLabels = isset($params['cols']) && is_array($params['cols']) && !empty($params['cols'])
      ? array_values($params['cols'])
      : ['Col 1', 'Col 2', 'Col 3', 'Col 4', 'Col 5', 'Col 6', 'Col 7'];

    $rowCount = count($rowLabels);
    $colCount = count($colLabels);

    // Matriz de intensidades predeterminada exactamente idéntica a la imagen
    $defaultMatrix = [
      [0.15, 0.42, 0.78, 0.45, 0.95, 0.35, 0.65], // Mon
      [0.40, 0.68, 0.95, 0.65, 0.42, 0.78, 0.25], // Tue
      [0.55, 0.72, 0.30, 0.88, 0.58, 0.95, 0.48], // Wed
      [0.28, 0.85, 0.55, 0.38, 0.95, 0.48, 0.70], // Thu
      [0.60, 0.95, 0.48, 0.95, 0.45, 0.30, 0.75], // Fri
    ];

    $rawMatrix = isset($params['matrix']) && is_array($params['matrix'])
      ? $params['matrix']
      : $defaultMatrix;

    $rx = isset($params['rx']) ? (float)$params['rx'] : 7.0;

    $classNames = 'mono-chart-svg mono-matrix-svg';
    if (!empty($style['svgClass'])) {
      $classNames .= ' ' . htmlspecialchars($style['svgClass'], ENT_QUOTES, 'UTF-8');
    }
    $labelClass = !empty($style['labelClass']) ? ' ' . htmlspecialchars($style['labelClass'], ENT_QUOTES, 'UTF-8') : '';
    $axisClass = !empty($style['axisClass']) ? ' ' . htmlspecialchars($style['axisClass'], ENT_QUOTES, 'UTF-8') : '';

    // Geometría del contenedor SVG y panel interno
    $viewBoxW = 320.0;
    $viewBoxH = 180.0;

    $panelX = 1.0;
    $panelY = 1.0;
    $panelW = 318.0;
    $panelH = 178.0;
    $panelRx = 16.0;

    $cellSize = 26.0;
    $cellGap = 7.5;
    $rx = isset($params['rx']) ? (float)$params['rx'] : 8.0;

    $gridW = ($colCount * $cellSize) + (($colCount - 1) * $cellGap);
    $gridH = ($rowCount * $cellSize) + (($rowCount - 1) * $cellGap);

    $padRight = 16.0;
    $gridStartX = round($panelX + $panelW - $padRight - $gridW, 1);
    $gridStartY = round($panelY + (($panelH - $gridH) / 2.0), 1);

    $labelAnchorX = round($gridStartX - 15.0, 1);

    ob_start();
    ?>
    <svg viewBox="0 0 <?= $viewBoxW ?> <?= $viewBoxH ?>" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>" data-chart="matrix-heatmap" <?= $style['tooltipAttr'] ?> data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
      <!-- Panel de Fondo Oscuro con Bordes Redondeados (Fiel a la captura) -->
      <rect x="<?= number_format($panelX, 1, '.', '') ?>" y="<?= number_format($panelY, 1, '.', '') ?>" width="<?= number_format($panelW, 1, '.', '') ?>" height="<?= number_format($panelH, 1, '.', '') ?>" rx="<?= number_format($panelRx, 1, '.', '') ?>" class="mono-matrix-panel" fill="#111114" stroke="rgba(255,255,255,0.08)" stroke-width="1.2" />

      <!-- Eje Y: Etiquetas de Días / Filas -->
      <g class="mono-matrix-labels">
        <?php for ($r = 0; $r < $rowCount; $r++): 
          $rowName = htmlspecialchars($rowLabels[$r], ENT_QUOTES, 'UTF-8');
          $rowCenterY = round($gridStartY + ($r * ($cellSize + $cellGap)) + ($cellSize / 2.0) + 3.5, 1);
        ?>
        <text x="<?= number_format($labelAnchorX, 1, '.', '') ?>" y="<?= number_format($rowCenterY, 1, '.', '') ?>" text-anchor="end" class="mono-matrix-row-label<?= $axisClass ?>"><?= $rowName ?></text>
        <?php endfor; ?>
      </g>

      <!-- Rejilla de Celdas (Rounded Node Cells) -->
      <g class="mono-matrix-grid">
        <?php 
        $staggerIndex = 0;
        for ($r = 0; $r < $rowCount; $r++): 
          $rowLabel = $rowLabels[$r] ?? ('R' . ($r + 1));
          for ($c = 0; $c < $colCount; $c++):
            $colLabel = $colLabels[$c] ?? ('C' . ($c + 1));
            $cellData = $rawMatrix[$r][$c] ?? 0.20;

            if (is_array($cellData)) {
              $intensity = isset($cellData['intensity']) ? (float)$cellData['intensity'] : 0.5;
              $displayVal = isset($cellData['val']) ? (string)$cellData['val'] : (round($intensity * 100) . '%');
              $cellTitle = isset($cellData['label']) ? $cellData['label'] : "{$rowLabel} • {$colLabel}";
            } else {
              $intensity = (float)$cellData;
              $intensity = max(0.0, min(1.0, $intensity));
              $displayVal = round($intensity * 100) . '%';
              $cellTitle = "{$rowLabel} • {$colLabel}";
            }

            $intensity = max(0.0, min(1.0, $intensity));
            $lightness = round(0.18 + ($intensity * 0.78), 2);
            $hexFallback = sprintf('#%02x%02x%02x', (int)($lightness * 255), (int)($lightness * 255), (int)($lightness * 255));
            $cellColor = "oklch(from var(--chart-base-color, {$hexFallback}) {$lightness} c h)";

            $cx = round($gridStartX + ($c * ($cellSize + $cellGap)), 1);
            $cy = round($gridStartY + ($r * ($cellSize + $cellGap)), 1);

            $pctStr = round($intensity * 100) . '%';
            $staggerDelay = $staggerIndex * 20; // 20ms de desfase progresivo
            $staggerIndex++;
        ?>
        <rect class="mono-matrix-cell" x="<?= number_format($cx, 1, '.', '') ?>" y="<?= number_format($cy, 1, '.', '') ?>" width="<?= number_format($cellSize, 1, '.', '') ?>" height="<?= number_format($cellSize, 1, '.', '') ?>" rx="<?= number_format($rx, 1, '.', '') ?>" fill="<?= $cellColor ?>" data-label="<?= htmlspecialchars($cellTitle, ENT_QUOTES, 'UTF-8') ?>" data-val="<?= htmlspecialchars($displayVal, ENT_QUOTES, 'UTF-8') ?>" data-density="<?= $pctStr ?>" data-delay="<?= $staggerDelay ?>" style="--cell-delay: <?= $staggerDelay ?>ms;" />
        <?php endfor; ?>
        <?php endfor; ?>
      </g>
    </svg>
    <?php
    return trim(ob_get_clean());
  }
}

