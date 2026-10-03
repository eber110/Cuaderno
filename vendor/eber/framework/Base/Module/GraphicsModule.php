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

    return [
      'svgClass'   => implode(' ', $svgClasses),
      'labelClass' => $labelClass,
      'axisClass'  => $axisClass,
      'styleAttr'  => implode(' ', $styleParts),
      'transition' => $transition,
      'colorRaw'   => $colorRaw,
      'labelRaw'   => $labelRaw,
      'axisRaw'    => $axisRaw,
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

    $classNames = 'mono-chart-svg mono-arc-svg';
    if (!empty($style['svgClass'])) {
      $classNames .= ' ' . htmlspecialchars($style['svgClass'], ENT_QUOTES, 'UTF-8');
    }

    $labelClass = !empty($style['labelClass']) ? ' ' . htmlspecialchars($style['labelClass'], ENT_QUOTES, 'UTF-8') : '';

    $perimeter = 251.33;
    $targetOffset = $perimeter * (1 - ($value / 100));

    ob_start();
    ?>
    <svg viewBox="0 0 240 145" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>" data-chart="arc-meter" data-target-pct="<?= $value ?>" data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
      <!-- Pista de fondo (variación OKLCH suave) -->
      <path d="M 30,125 A 80,80 0 0,1 210,125" class="mono-arc-track" fill="none" stroke-width="16" stroke-linecap="round" />
      
      <!-- Arco dinámico activo -->
      <path d="M 30,125 A 80,80 0 0,1 210,125" class="mono-arc-meter-val" fill="none" stroke-width="16" stroke-linecap="round" stroke-dasharray="<?= $perimeter ?> <?= $perimeter ?>" stroke-dashoffset="<?= $perimeter ?>" data-target-offset="<?= number_format($targetOffset, 2, '.', '') ?>" />
      
      <!-- Lecturas centrales con clase de color para etiquetas (colorLabel) -->
      <?php if ($showNumber): ?>
        <text x="120" y="103" text-anchor="middle" class="mono-arc-center-number<?= $labelClass ?>">0%</text>
      <?php endif; ?>
      <?php if ($showLabel): ?>
        <text x="120" y="126" text-anchor="middle" class="mono-arc-center-label<?= $labelClass ?>"><?= $label ?></text>
      <?php endif; ?>
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
    <svg viewBox="0 0 320 190" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>" data-chart="stacked-tones" data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
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
        $hDark = (float) ($q['dark'] ?? ($q['v3'] ?? 20)) * $scale;
        $hMid = (float) ($q['mid'] ?? ($q['v2'] ?? 30)) * $scale;
        $hWhite = (float) ($q['white'] ?? ($q['v1'] ?? 45)) * $scale;
        $totalH = $hDark + $hMid + $hWhite;
        $yDark = 155 - $totalH;
        $yMid = $yDark + $hDark;
        $yWhite = $yMid + $hMid;
      ?>
      <g class="mono-stacked-bar-item" clip-path="url(#<?= $uid ?>-clip-<?= $idx ?>)" data-label="<?= htmlspecialchars((string) $q['label'], ENT_QUOTES, 'UTF-8') ?>" data-total="<?= htmlspecialchars((string) ($q['total'] ?? $totalH), ENT_QUOTES, 'UTF-8') ?>">
        <rect x="<?= number_format($qx, 2, '.', '') ?>" y="<?= number_format($yDark, 2, '.', '') ?>" width="<?= number_format($barWidth, 2, '.', '') ?>" height="<?= number_format($hDark + 1, 2, '.', '') ?>" class="mono-layer-top" />
        <rect x="<?= number_format($qx, 2, '.', '') ?>" y="<?= number_format($yMid, 2, '.', '') ?>" width="<?= number_format($barWidth, 2, '.', '') ?>" height="<?= number_format($hMid + 1, 2, '.', '') ?>" class="mono-layer-mid" />
        <rect x="<?= number_format($qx, 2, '.', '') ?>" y="<?= number_format($yWhite, 2, '.', '') ?>" width="<?= number_format($barWidth, 2, '.', '') ?>" height="<?= number_format($hWhite + 2, 2, '.', '') ?>" class="mono-layer-base" />
      </g>
      <text x="<?= number_format($cx, 2, '.', '') ?>" y="174" class="mono-svg-axis-text mono-svg-axis-text-x<?= $axisClass ?>"><?= htmlspecialchars((string) $q['label'], ENT_QUOTES, 'UTF-8') ?></text>
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
    <svg viewBox="0 0 320 190" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>" data-chart="tile-treemap" data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
      <?php foreach ($rects as $r): 
        $t = $r['tile'];
        $label = htmlspecialchars((string) ($t['label'] ?? ($t['name'] ?? '')), ENT_QUOTES, 'UTF-8');
        $rawPct = $t['pct'] ?? ($t['value'] ?? '');
        $pct = htmlspecialchars(rtrim((string) $rawPct, '%'), ENT_QUOTES, 'UTF-8');
        $tag = htmlspecialchars((string) ($t['tag'] ?? ($t['info'] ?? ($t['desc'] ?? ''))), ENT_QUOTES, 'UTF-8');
        $isCompact = $r['h'] < 52.0;
        $showTag = !empty($tag) && $r['h'] >= 72.0 && $r['w'] >= 75.0;
      ?>
      <g class="mono-treemap-tile-group <?= $r['cls'] ?>" data-label="<?= $label ?>" data-pct="<?= $pct ?>%">
        <rect x="<?= number_format($r['x'], 1, '.', '') ?>" y="<?= number_format($r['y'], 1, '.', '') ?>" width="<?= number_format($r['w'], 1, '.', '') ?>" height="<?= number_format($r['h'], 1, '.', '') ?>" rx="12" class="mono-treemap-tile-rect" />
        
        <?php if ($isCompact): ?>
          <!-- Disposición compacta en línea para mosaicos con altura reducida -->
          <text x="<?= number_format($r['x'] + 12, 1, '.', '') ?>" y="<?= number_format($r['y'] + ($r['h'] / 2) + 4, 1, '.', '') ?>" class="mono-treemap-title<?= $labelClass ?>"><?= $label ?> <tspan class="mono-treemap-pct<?= $labelClass ?>" font-weight="700" dx="6"><?= $pct ?>%</tspan></text>
        <?php else: ?>
          <!-- Disposición estándar en bloque -->
          <text x="<?= number_format($r['x'] + 14, 1, '.', '') ?>" y="<?= number_format($r['y'] + 24, 1, '.', '') ?>" class="mono-treemap-title<?= $labelClass ?>"><?= $label ?></text>
          <text x="<?= number_format($r['x'] + 14, 1, '.', '') ?>" y="<?= number_format($r['y'] + 50, 1, '.', '') ?>" class="mono-treemap-pct<?= $labelClass ?>"><?= $pct ?>%</text>
          <?php if ($showTag): ?>
            <text x="<?= number_format($r['x'] + 14, 1, '.', '') ?>" y="<?= number_format($r['y'] + 72, 1, '.', '') ?>" class="mono-treemap-tag<?= $labelClass ?>"><?= $tag ?></text>
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
      $h = (max(0, min(100, (float) $val)) / 100.0) * $maxH;
      $y = $baselineY - $h;
      $points[] = ['x' => $x, 'y' => $y, 'val' => $val];
    }

    // Cálculo matemático de Spline Bézier Catmull-Rom a Cúbica
    $pathD = self::computeSplinePath($points, 0.25);
    $areaD = $pathD . ' L ' . end($points)['x'] . ',' . $baselineY . ' L ' . $points[0]['x'] . ',' . $baselineY . ' Z';

    ob_start();
    ?>
    <svg viewBox="0 0 320 180" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>" data-chart="hybrid-spline" data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
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
        $barH = ($pt['val'] / 100) * $maxH;
        $barY = $baselineY - $barH;
        $rx = min(8.0, $barWidth / 2);
      ?>
      <rect x="<?= number_format($barX, 2, '.', '') ?>" y="<?= number_format($barY, 2, '.', '') ?>" width="<?= number_format($barWidth, 2, '.', '') ?>" height="<?= number_format($barH, 2, '.', '') ?>" rx="<?= number_format($rx, 2, '.', '') ?>" class="mono-spline-bar-rect" data-target-h="<?= number_format($barH, 2, '.', '') ?>" data-target-y="<?= number_format($barY, 2, '.', '') ?>" />
      <?php endforeach; ?>

      <!-- Área degradada de la curva -->
      <path d="<?= $areaD ?>" fill="url(#<?= $uid ?>-grad)" class="mono-spline-area" />

      <!-- Curva continua de spline principal -->
      <path d="<?= $pathD ?>" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="mono-spline-path" />

      <!-- Vértices / Puntos circulares -->
      <?php foreach ($points as $idx => $pt): ?>
        <circle cx="<?= number_format($pt['x'], 2, '.', '') ?>" cy="<?= number_format($pt['y'], 2, '.', '') ?>" r="5" class="mono-spline-dot" data-val="<?= $pt['val'] ?>" />
      <?php endforeach; ?>

      <!-- Etiquetas del eje X con axisLabel distribuidas dinámicamente -->
      <?php foreach ($labels as $idx => $lbl): 
        $x = $xCoords[$idx] ?? ($plotX1 + $idx * $step);
      ?>
      <text x="<?= number_format($x, 2, '.', '') ?>" y="172" class="mono-svg-axis-text mono-svg-axis-text-x<?= $axisClass ?>"><?= htmlspecialchars((string) $lbl, ENT_QUOTES, 'UTF-8') ?></text>
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
    <svg viewBox="0 0 320 180" class="<?= $classNames ?>" style="<?= $style['styleAttr'] ?>" data-chart="pill-pillars" data-duration="<?= $style['transition'] ?>" preserveAspectRatio="xMidYMid meet">
      <!-- Rejilla punteada horizontal -->
      <line x1="28" y1="30" x2="306" y2="30" class="mono-svg-grid-line" />
      <line x1="28" y1="72" x2="306" y2="72" class="mono-svg-grid-line" />
      <line x1="28" y1="114" x2="306" y2="114" class="mono-svg-grid-line" />
      <line x1="28" y1="155" x2="306" y2="155" class="mono-svg-grid-line" />

      <?php foreach ($pairs as $idx => $p): 
        $cx = $plotX1 + ($idx + 0.5) * $slotWidth;
        $x1 = $cx - $pillarWidth - ($pillarGap / 2);
        $x2 = $cx + ($pillarGap / 2);

        $h1 = (($p['v1'] ?? 50) / 100) * $maxH;
        $y1 = $baselineY - $h1;

        $h2 = (($p['v2'] ?? 30) / 100) * $maxH;
        $y2 = $baselineY - $h2;
      ?>
      <g class="mono-pillar-pair-group" data-label="<?= htmlspecialchars((string) $p['label'], ENT_QUOTES, 'UTF-8') ?>">
        <!-- Pilar primario (color base) -->
        <rect x="<?= number_format($x1, 2, '.', '') ?>" y="<?= number_format($y1, 2, '.', '') ?>" width="<?= number_format($pillarWidth, 2, '.', '') ?>" height="<?= number_format($h1, 2, '.', '') ?>" rx="<?= number_format($rx, 2, '.', '') ?>" class="mono-pillar-primary" data-target-h="<?= number_format($h1, 2, '.', '') ?>" data-target-y="<?= number_format($y1, 2, '.', '') ?>" />
        <!-- Pilar secundario (segunda variación OKLCH) -->
        <rect x="<?= number_format($x2, 2, '.', '') ?>" y="<?= number_format($y2, 2, '.', '') ?>" width="<?= number_format($pillarWidth, 2, '.', '') ?>" height="<?= number_format($h2, 2, '.', '') ?>" rx="<?= number_format($rx, 2, '.', '') ?>" class="mono-pillar-secondary" data-target-h="<?= number_format($h2, 2, '.', '') ?>" data-target-y="<?= number_format($y2, 2, '.', '') ?>" />
        <!-- Etiqueta eje X con axisLabel -->
        <text x="<?= number_format($cx, 2, '.', '') ?>" y="172" class="mono-svg-axis-text mono-svg-axis-text-x<?= $axisClass ?>"><?= htmlspecialchars((string) $p['label'], ENT_QUOTES, 'UTF-8') ?></text>
      </g>
      <?php endforeach; ?>
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
}
