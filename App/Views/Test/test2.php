<?php
  /** @var array $demo */
  $arc = $demo["arcMeter"] ?? [];
  $stacked = $demo["stackedTones"] ?? [];
  $treemap = $demo["treemap"] ?? [];
  $spline = $demo["hybridSpline"] ?? [];
  $pillars = $demo["pillPillars"] ?? [];
?>

<div class="container container-xl-mid flex-column gap30 w100" style="padding: 40px 20px 120px 20px; min-height: 100vh; background-color: #0c0c0e; color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

  <!-- =========================================================================
       PARTE 1: LIBRERÍA DE GRÁFICOS SVG MONOCROMÁTICOS MINIMALISTAS
       ========================================================================= -->
  <div class="flex-column gap15 w100" style="border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 25px;">
    <div class="flex-row justify-between align-center flex-wrap gap15">
      <div>
        <div class="flex-row align-center gap10">
          <span class="mono-chart-badge" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2);">
            Librería de Gráficos • Vanilla SVG & CSS
          </span>
          <h1 style="font-size: 26px; font-weight: 800; margin: 0; letter-spacing: -0.02em; color: #ffffff;">
            Gráficos SVG Minimalistas Animados
          </h1>
        </div>
        <p style="color: #a1a1aa; margin: 6px 0 0 0; font-size: 14px;">
          Arquitectura matemática vectorial pura: Arcos de 180°, barras de cápsula multicapa, particiones treemap, spline cúbico Bézier continuo y conmutador de orientación Col/Row.
        </p>
      </div>

      <!-- BOTONERA DE CONTROL DE LOS GRÁFICOS -->
      <div class="flex-row align-center gap10 flex-wrap">
        <button id="btnPlayChartsEntry" class="mono-chart-switch-btn active" style="padding: 8px 18px; font-size: 13px;" type="button">
          ▶ Reproducir Entrada
        </button>
        <button id="btnPlayChartsExit" class="mono-chart-switch-btn" style="padding: 8px 18px; font-size: 13px;" type="button">
          ⏹ Reproducir Salida (.is-exiting)
        </button>
        <button id="btnResetCharts" class="mono-chart-switch-btn" style="padding: 8px 18px; font-size: 13px;" type="button">
          ↺ Reiniciar Gráficos
        </button>
      </div>
    </div>
  </div>

  <!-- COLECCIÓN DE LAS 5 TARJETAS DE GRÁFICOS SVG -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(330px, 1fr)); gap: 24px;" class="w100">
    
    <!-- 1. GRÁFICO: ARC METER GAUGE (SPEEDOMETER 180°) -->
    <div class="mono-chart-card" data-chart-type="arc-meter">
      <div class="mono-chart-header">
        <div class="mono-chart-top-row">
          <h3 class="mono-chart-title"><?= e($arc["title"] ?? "ARC METER") ?></h3>
          <div class="flex-row align-center gap8">
            <span class="mono-chart-badge"><?= e($arc["tag"] ?? "Speedometer") ?></span>
            <div class="mono-segmented-pill mono-arc-val-pills" title="Cambiar valor dinámico">
              <button type="button" class="mono-segmented-opt <?= ($arc["value"] ?? 20) == 20 ? 'active' : '' ?>" data-arc-target="20">20%</button>
              <button type="button" class="mono-segmented-opt <?= ($arc["value"] ?? 20) == 70 ? 'active' : '' ?>" data-arc-target="70">70%</button>
              <button type="button" class="mono-segmented-opt <?= ($arc["value"] ?? 20) == 100 ? 'active' : '' ?>" data-arc-target="100">100%</button>
            </div>
          </div>
        </div>
        <div class="mono-chart-headline">
          <span class="mono-chart-value-big" data-target="<?= e($arc["value"] ?? 20) ?>" data-suffix="%">
            0%
          </span>
          <span class="mono-chart-label-big"><?= e($arc["label"] ?? "Optimal Load") ?></span>
        </div>
      </div>

      <div class="mono-chart-viewport">
        <svg viewBox="0 0 300 160" preserveAspectRatio="xMidYMid meet">
          <!-- Pista base completa semicircular de radio 80 -->
          <path d="M 70 135 A 80 80 0 0 1 230 135" class="mono-arc-track" />
          <!-- Arco activo blanco con stroke-dashoffset animado -->
          <path d="M 70 135 A 80 80 0 0 1 230 135" class="mono-arc-meter-val"
                data-value="<?= e($arc["value"] ?? 20) ?>"
                stroke-dasharray="251.33 251.33"
                stroke-dashoffset="251.33"
                style="stroke-dasharray: 251.33 251.33; stroke-dashoffset: 251.33; opacity: 0;" />
          <!-- Lecturas centrales del velocímetro -->
          <text x="150" y="112" class="mono-arc-center-number">0%</text>
          <text x="150" y="136" class="mono-arc-center-label"><?= e($arc["status"] ?? "Optimal Load") ?></text>
        </svg>
      </div>

      <div class="mono-chart-info-row">
        <span><?= e($arc["metaKey"] ?? "Active nodes") ?></span>
        <span class="info-right"><?= e($arc["metaVal"] ?? "12 / 16") ?></span>
      </div>

      <div class="mono-chart-footer">
        <div class="mono-chart-footer-text">
          <h4 class="mono-chart-footer-title"><?= e($arc["footerTitle"] ?? "Mono Arc Meter") ?></h4>
          <p class="mono-chart-footer-desc"><?= e($arc["footerDesc"] ?? "Semi-circular track with linecap") ?></p>
        </div>
        <button type="button" class="mono-chart-copy-btn" title="Copiar SVG" aria-label="Copiar SVG">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- 2. GRÁFICO: STACKED TONES (BARRAS APILADAS MONOCROMÁTICAS) -->
    <div class="mono-chart-card" data-chart-type="stacked-tones">
      <div class="mono-chart-header">
        <div class="mono-chart-top-row">
          <h3 class="mono-chart-title"><?= e($stacked["title"] ?? "STACKED TONES") ?></h3>
          <span class="mono-chart-badge"><?= e($stacked["tag"] ?? "Bar") ?></span>
        </div>
        <div class="mono-chart-headline">
          <span class="mono-chart-value-big text-animation text-counter" data-target="1248" data-duration="400">1,248</span>
          <span class="mono-chart-label-big"><?= e($stacked["label"] ?? "Total units") ?></span>
        </div>
      </div>

      <div class="mono-chart-viewport">
        <svg viewBox="0 0 320 190" preserveAspectRatio="xMidYMid meet">
          <defs>
            <?php 
              $quarters = $stacked["quarters"] ?? [];
              $qCoords = [
                0 => ["x" => 55, "w" => 32],
                1 => ["x" => 120, "w" => 32],
                2 => ["x" => 185, "w" => 32],
                3 => ["x" => 250, "w" => 32]
              ];
              foreach ($quarters as $idx => $q):
                $scale = 135 / 160;
                $totalH = $q["total"] * $scale;
                $yBase = 155 - $totalH;
                $qx = $qCoords[$idx]["x"];
            ?>
            <clipPath id="stacked-clip-<?= $idx ?>">
              <rect class="mono-stacked-clip-rect" x="<?= $qx ?>" y="<?= $yBase ?>" width="32" height="<?= $totalH ?>" rx="16" ry="16" data-target-y="<?= $yBase ?>" data-target-h="<?= $totalH ?>" />
            </clipPath>
            <?php endforeach; ?>
          </defs>

          <!-- Rejilla punteada horizontal Y -->
          <line x1="36" y1="20" x2="300" y2="20" class="mono-svg-grid-line" />
          <line x1="36" y1="54" x2="300" y2="54" class="mono-svg-grid-line" />
          <line x1="36" y1="88" x2="300" y2="88" class="mono-svg-grid-line" />
          <line x1="36" y1="122" x2="300" y2="122" class="mono-svg-grid-line" />
          <line x1="36" y1="155" x2="300" y2="155" class="mono-svg-grid-line" />

          <!-- Etiquetas del eje Y -->
          <text x="28" y="20" class="mono-svg-axis-text mono-svg-axis-text-y">160</text>
          <text x="28" y="54" class="mono-svg-axis-text mono-svg-axis-text-y">120</text>
          <text x="28" y="88" class="mono-svg-axis-text mono-svg-axis-text-y">80</text>
          <text x="28" y="122" class="mono-svg-axis-text mono-svg-axis-text-y">40</text>
          <text x="28" y="155" class="mono-svg-axis-text mono-svg-axis-text-y">0</text>

          <!-- Columnas multicapa apiladas protegidas por el clipPath de cápsula redondeada -->
          <?php foreach ($quarters as $idx => $q): 
            $qx = $qCoords[$idx]["x"];
            $scale = 135 / 160;
            $hDark = $q["dark"] * $scale;
            $hMid = $q["mid"] * $scale;
            $hWhite = $q["white"] * $scale;
            $totalH = $hDark + $hMid + $hWhite;
            $yDark = 155 - $totalH;
            $yMid = $yDark + $hDark;
            $yWhite = $yMid + $hMid;
          ?>
          <g class="mono-stacked-bar-item" clip-path="url(#stacked-clip-<?= $idx ?>)"
             data-label="<?= e($q["label"]) ?>" data-total="<?= e($q["total"]) ?>"
             data-white="<?= e($q["white"]) ?>" data-mid="<?= e($q["mid"]) ?>" data-dark="<?= e($q["dark"]) ?>">
            <rect x="<?= $qx ?>" y="<?= $yDark ?>" width="32" height="<?= $hDark + 1 ?>" class="mono-layer-top" />
            <rect x="<?= $qx ?>" y="<?= $yMid ?>" width="32" height="<?= $hMid + 1 ?>" class="mono-layer-mid" />
            <rect x="<?= $qx ?>" y="<?= $yWhite ?>" width="32" height="<?= $hWhite + 2 ?>" class="mono-layer-base" />
          </g>
          <text x="<?= $qx + 16 ?>" y="174" class="mono-svg-axis-text"><?= e($q["label"]) ?></text>
          <?php endforeach; ?>
        </svg>
      </div>

      <div class="mono-chart-info-row">
        <span><?= e($stacked["metaKey"] ?? "Peak period") ?></span>
        <span class="info-right"><?= e($stacked["metaVal"] ?? "Q3 (+24%)") ?></span>
      </div>

      <div class="mono-chart-footer">
        <div class="mono-chart-footer-text">
          <h4 class="mono-chart-footer-title"><?= e($stacked["footerTitle"] ?? "Mono Stacked Tones") ?></h4>
          <p class="mono-chart-footer-desc"><?= e($stacked["footerDesc"] ?? "Multi-layer rounded capsule bars") ?></p>
        </div>
        <button type="button" class="mono-chart-copy-btn" title="Copiar SVG" aria-label="Copiar SVG">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- 3. GRÁFICO: TILE TREEMAP (PARTICIÓN DE BLOQUES) -->
    <div class="mono-chart-card" data-chart-type="tile-treemap">
      <div class="mono-chart-header">
        <div class="mono-chart-top-row">
          <h3 class="mono-chart-title"><?= e($treemap["title"] ?? "TILE TREEMAP") ?></h3>
          <span class="mono-chart-badge"><?= e($treemap["tag"] ?? "Partition") ?></span>
        </div>
        <div class="mono-chart-headline">
          <span class="mono-chart-value-big text-animation text-counter" data-target="100" data-suffix="%" data-duration="400">100%</span>
          <span class="mono-chart-label-big"><?= e($treemap["label"] ?? "Allocation") ?></span>
        </div>
      </div>

      <div class="mono-chart-viewport" style="padding: 12px;">
        <div class="mono-treemap-grid">
          <?php 
            $tiles = $treemap["tiles"] ?? [];
            foreach ($tiles as $tile):
          ?>
          <div class="mono-treemap-tile <?= e($tile["class"]) ?>" style="opacity: 0;" data-name="<?= e($tile["name"]) ?>" data-pct="<?= e($tile["pct"]) ?>" data-info="<?= e($tile["info"]) ?>">
            <p class="mono-treemap-tile-name"><?= e($tile["name"]) ?></p>
            <p class="mono-treemap-tile-pct"><?= e($tile["pct"]) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="mono-chart-info-row">
        <span><?= e($treemap["metaKey"] ?? "Primary share") ?></span>
        <span class="info-right"><?= e($treemap["metaVal"] ?? "Storage (45%)") ?></span>
      </div>

      <div class="mono-chart-footer">
        <div class="mono-chart-footer-text">
          <h4 class="mono-chart-footer-title"><?= e($treemap["footerTitle"] ?? "Mono Tile Treemap") ?></h4>
          <p class="mono-chart-footer-desc"><?= e($treemap["footerDesc"] ?? "Partition blocks with rounded corners") ?></p>
        </div>
        <button type="button" class="mono-chart-copy-btn" title="Copiar SVG" aria-label="Copiar SVG">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- 4. GRÁFICO: HYBRID SPLINE + BAR (BARRAS Y SPLINE INTERACTIVO) -->
    <div class="mono-chart-card" data-chart-type="hybrid-spline">
      <div class="mono-chart-header">
        <div class="mono-chart-top-row">
          <h3 class="mono-chart-title"><?= e($spline["title"] ?? "HYBRID SPLINE") ?></h3>
          <button type="button" class="mono-chart-switch-btn active" id="btnToggleSpline">Spline On</button>
        </div>
        <div class="mono-chart-headline">
          <span class="mono-chart-value-big"><?= e($spline["value"] ?? "8.4k") ?></span>
          <span class="mono-chart-label-big"><?= e($spline["label"] ?? "Peak volume") ?></span>
        </div>
      </div>

      <div class="mono-chart-viewport">
        <svg viewBox="0 0 340 190" preserveAspectRatio="xMidYMid meet">
          <!-- Rejilla horizontal punteada -->
          <line x1="20" y1="30" x2="320" y2="30" class="mono-svg-grid-line" />
          <line x1="20" y1="70" x2="320" y2="70" class="mono-svg-grid-line" />
          <line x1="20" y1="110" x2="320" y2="110" class="mono-svg-grid-line" />
          <line x1="20" y1="150" x2="320" y2="150" class="mono-svg-grid-line" />

          <!-- 5 Barras translúcidas de cápsula redondeada -->
          <rect x="28" y="92" width="32" height="68" class="mono-hybrid-bar" data-month="Jan" data-val="4.2" />
          <rect x="92" y="45" width="32" height="115" class="mono-hybrid-bar" data-month="Feb" data-val="7.1" />
          <rect x="156" y="66" width="32" height="94" class="mono-hybrid-bar" data-month="Mar" data-val="5.8" />
          <rect x="220" y="24" width="32" height="136" class="mono-hybrid-bar" data-month="Apr" data-val="8.4" />
          <rect x="284" y="50" width="32" height="110" class="mono-hybrid-bar" data-month="May" data-val="6.9" />

          <!-- Curva Spline Bézier matemática continua -->
          <path class="mono-spline-path" />

          <!-- Puntos circulares en las cúspides -->
          <circle cx="44" cy="92" r="4.5" class="mono-spline-point" data-month="Jan" data-val="4.2" />
          <circle cx="108" cy="45" r="4.5" class="mono-spline-point" data-month="Feb" data-val="7.1" />
          <circle cx="172" cy="66" r="4.5" class="mono-spline-point" data-month="Mar" data-val="5.8" />
          <circle cx="236" cy="24" r="4.5" class="mono-spline-point" data-month="Apr" data-val="8.4" />
          <circle cx="300" cy="50" r="4.5" class="mono-spline-point" data-month="May" data-val="6.9" />

          <!-- Etiquetas X -->
          <text x="44" y="175" class="mono-svg-axis-text">Jan</text>
          <text x="108" y="175" class="mono-svg-axis-text">Feb</text>
          <text x="172" y="175" class="mono-svg-axis-text">Mar</text>
          <text x="236" y="175" class="mono-svg-axis-text">Apr</text>
          <text x="300" y="175" class="mono-svg-axis-text">May</text>
        </svg>
      </div>

      <div class="mono-chart-info-row">
        <span><?= e($spline["metaKey"] ?? "Efficiency") ?></span>
        <span class="info-right"><?= e($spline["metaVal"] ?? "94.2%") ?></span>
      </div>

      <div class="mono-chart-footer">
        <div class="mono-chart-footer-text">
          <h4 class="mono-chart-footer-title"><?= e($spline["footerTitle"] ?? "Mono Hybrid Spline + Bar") ?></h4>
          <p class="mono-chart-footer-desc"><?= e($spline["footerDesc"] ?? "Overlay spline on capsule bars") ?></p>
        </div>
        <button type="button" class="mono-chart-copy-btn" title="Copiar SVG" aria-label="Copiar SVG">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- 5. GRÁFICO: ROUNDED PILL PILLARS (COLUMNAS EMPAREJADAS CON CONMUTADOR COL/ROW) -->
    <div class="mono-chart-card" data-chart-type="pill-pillars">
      <div class="mono-chart-header">
        <div class="mono-chart-top-row">
          <h3 class="mono-chart-title"><?= e($pillars["title"] ?? "ROUNDED PILL") ?></h3>
          <div class="mono-segmented-pill">
            <button type="button" class="mono-segmented-opt active" data-mode="col">Col</button>
            <button type="button" class="mono-segmented-opt" data-mode="row">Row</button>
          </div>
        </div>
        <div class="mono-chart-headline">
          <span class="mono-chart-value-big"><?= e($pillars["value"] ?? "42.8") ?></span>
          <span class="mono-chart-label-big"><?= e($pillars["label"] ?? "Index score") ?></span>
        </div>
      </div>

      <div class="mono-chart-viewport">
        <svg viewBox="0 0 340 190" preserveAspectRatio="xMidYMid meet">
          <!-- Modo Columna (Vertical) -->
          <g class="pillar-vertical-group">
            <line x1="25" y1="35" x2="315" y2="35" class="mono-svg-grid-line" />
            <line x1="25" y1="75" x2="315" y2="75" class="mono-svg-grid-line" />
            <line x1="25" y1="115" x2="315" y2="115" class="mono-svg-grid-line" />
            <line x1="25" y1="155" x2="315" y2="155" class="mono-svg-grid-line" />

            <!-- Grupo A -->
            <rect x="42" y="55" width="16" height="100" class="mono-pillar-primary" data-label="Grp A (Principal)" data-val="85" />
            <rect x="62" y="93" width="16" height="62" class="mono-pillar-secondary" data-label="Grp A (Secundario)" data-val="52" />
            <text x="60" y="174" class="mono-svg-axis-text">Grp A</text>

            <!-- Grupo B -->
            <rect x="114" y="81" width="16" height="74" class="mono-pillar-primary" data-label="Grp B (Principal)" data-val="62" />
            <rect x="134" y="50" width="16" height="105" class="mono-pillar-secondary" data-label="Grp B (Secundario)" data-val="88" />
            <text x="132" y="174" class="mono-svg-axis-text">Grp B</text>

            <!-- Grupo C -->
            <rect x="186" y="44" width="16" height="111" class="mono-pillar-primary" data-label="Grp C (Principal)" data-val="94" />
            <rect x="206" y="107" width="16" height="48" class="mono-pillar-secondary" data-label="Grp C (Secundario)" data-val="40" />
            <text x="204" y="174" class="mono-svg-axis-text">Grp C</text>

            <!-- Grupo D -->
            <rect x="258" y="66" width="16" height="89" class="mono-pillar-primary" data-label="Grp D (Principal)" data-val="75" />
            <rect x="278" y="78" width="16" height="77" class="mono-pillar-secondary" data-label="Grp D (Secundario)" data-val="65" />
            <text x="276" y="174" class="mono-svg-axis-text">Grp D</text>
          </g>

          <!-- Modo Fila (Horizontal) -->
          <g class="pillar-horizontal-group" style="display: none; opacity: 0;">
            <line x1="75" y1="20" x2="75" y2="160" class="mono-svg-grid-line" />
            <line x1="135" y1="20" x2="135" y2="160" class="mono-svg-grid-line" />
            <line x1="195" y1="20" x2="195" y2="160" class="mono-svg-grid-line" />
            <line x1="255" y1="20" x2="255" y2="160" class="mono-svg-grid-line" />

            <!-- Fila A -->
            <text x="65" y="38" class="mono-svg-axis-text mono-svg-axis-text-y">Grp A</text>
            <rect x="75" y="26" width="180" height="10" class="mono-pillar-primary" data-label="Grp A (Principal)" data-val="85" />
            <rect x="75" y="39" width="110" height="10" class="mono-pillar-secondary" data-label="Grp A (Secundario)" data-val="52" />

            <!-- Fila B -->
            <text x="65" y="73" class="mono-svg-axis-text mono-svg-axis-text-y">Grp B</text>
            <rect x="75" y="61" width="130" height="10" class="mono-pillar-primary" data-label="Grp B (Principal)" data-val="62" />
            <rect x="75" y="74" width="185" height="10" class="mono-pillar-secondary" data-label="Grp B (Secundario)" data-val="88" />

            <!-- Fila C -->
            <text x="65" y="108" class="mono-svg-axis-text mono-svg-axis-text-y">Grp C</text>
            <rect x="75" y="96" width="200" height="10" class="mono-pillar-primary" data-label="Grp C (Principal)" data-val="94" />
            <rect x="75" y="109" width="85" height="10" class="mono-pillar-secondary" data-label="Grp C (Secundario)" data-val="40" />

            <!-- Fila D -->
            <text x="65" y="143" class="mono-svg-axis-text mono-svg-axis-text-y">Grp D</text>
            <rect x="75" y="131" width="160" height="10" class="mono-pillar-primary" data-label="Grp D (Principal)" data-val="75" />
            <rect x="75" y="144" width="138" height="10" class="mono-pillar-secondary" data-label="Grp D (Secundario)" data-val="65" />
          </g>
        </svg>
      </div>

      <div class="mono-chart-info-row">
        <span><?= e($pillars["metaKey"] ?? "Dominant set") ?></span>
        <span class="info-right"><?= e($pillars["metaVal"] ?? "Alpha series") ?></span>
      </div>

      <div class="mono-chart-footer">
        <div class="mono-chart-footer-text">
          <h4 class="mono-chart-footer-title"><?= e($pillars["footerTitle"] ?? "Mono Rounded Pill Pillars") ?></h4>
          <p class="mono-chart-footer-desc"><?= e($pillars["footerDesc"] ?? "Paired capsule pillars with orientation toggle") ?></p>
        </div>
        <button type="button" class="mono-chart-copy-btn" title="Copiar SVG" aria-label="Copiar SVG">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
          </svg>
        </button>
      </div>
    </div>

  </div>

  <!-- =========================================================================
       SEPARADOR ENTRE LIBRERÍAS
       ========================================================================= -->
  <div style="border-top: 1px dashed rgba(255,255,255,0.12); margin: 40px 0 10px 0; position: relative; text-align: center;">
    <span style="position: absolute; top: -11px; left: 50%; transform: translateX(-50%); background: #0c0c0e; padding: 0 16px; font-size: 11px; color: #71717a; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;">
      Librería 2: Animaciones de Textos y Contadores
    </span>
  </div>

  <!-- =========================================================================
       PARTE 2: LABORATORIO COMPLETO DE ANIMACIONES DE TEXTOS Y NÚMEROS (INMUNE)
       ========================================================================= -->
  <div class="flex-column gap25 w100" style="padding-top: 10px;">
    
    <!-- CABECERA Y PANEL DE CONTROL DEL LABORATORIO DE TEXTOS -->
    <div class="flex-column gap15 w100" style="border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 25px;">
      <div class="flex-row justify-between align-center flex-wrap gap15">
        <div>
          <div class="flex-row align-center gap10">
            <span class="sandbox-badge">Vanilla JS • Sin IDs</span>
            <h2 style="font-size: 22px; font-weight: 700; margin: 0; letter-spacing: -0.02em; color: #ffffff;">
              Laboratorio de Animaciones: Textos y Números
            </h2>
          </div>
          <p style="color: #71717a; margin: 6px 0 0 0; font-size: 14px;">
            Todas las animaciones se invocan con la clase base <code style="color: #a1a1aa; background: #1c1c22; padding: 2px 6px; border-radius: 4px;">.text-animation</code> seguida de la variante (ej: <code style="color: #a1a1aa; background: #1c1c22; padding: 2px 6px; border-radius: 4px;">.text-scramble</code>, <code style="color: #a1a1aa; background: #1c1c22; padding: 2px 6px; border-radius: 4px;">.text-counter</code>) y se procesan en masa mediante <code style="color: #a1a1aa; background: #1c1c22; padding: 2px 6px; border-radius: 4px;">querySelectorAll()</code>.
          </p>
        </div>

        <!-- BOTONERA DE CONTROL INTERACTIVO DEL LABORATORIO -->
        <div class="flex-row align-center gap10 flex-wrap">
          <button id="btnPlayEntry" class="sandbox-btn sandbox-btn-primary" type="button">
            ▶ Reproducir Entrada
          </button>
          <button id="btnToggleLoop" class="sandbox-btn" type="button">
            ⟳ Alternar Loops
          </button>
          <button id="btnPlayExit" class="sandbox-btn sandbox-btn-danger" type="button">
            ⏹ Reproducir Salida
          </button>
          <button id="btnReset" class="sandbox-btn" type="button">
            ↺ Reiniciar Textos
          </button>
        </div>
      </div>
    </div>

    <!-- SECCIÓN 1: CONTADORES NUMÉRICOS DE LAS TARJETAS (class="text-animation text-counter") -->
    <div class="flex-column gap15 w100">
      <div class="flex-row align-center justify-between">
        <h3 style="font-size: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa; margin: 0;">
          1. Métricas Numéricas (<span style="color: #ffffff;">.text-animation .text-counter</span>)
        </h3>
        <span style="font-size: 12px; color: #71717a;">Procesados con querySelectorAll</span>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px;" class="w100">
        
        <!-- Métrica 1: Arc Meter (78%) -->
        <div class="sandbox-card" style="padding: 24px;">
          <div class="flex-row justify-between align-center" style="margin-bottom: 16px;">
            <span class="text-animation text-fade-blur" style="font-size: 13px; font-weight: 700; letter-spacing: 0.06em; color: #a1a1aa;">
              <?= e($arc["title"] ?? "ARC METER") ?>
            </span>
            <span class="sandbox-badge"><?= e($arc["tag"] ?? "Speedometer") ?></span>
          </div>
          <div class="flex-row align-baseline gap10" style="margin-bottom: 8px;">
            <span class="text-animation text-counter" style="font-size: 42px; font-weight: 800; color: #ffffff; letter-spacing: -0.03em;" 
                  data-target="78" data-suffix="%" data-duration="1200">
              0%
            </span>
            <span class="text-animation text-slide-up" style="font-size: 15px; color: #71717a;"><?= e($arc["label"] ?? "load index") ?></span>
          </div>
          <div style="font-size: 12px; color: #52525b; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 12px; margin-top: 16px;" class="flex-row justify-between">
            <span><?= e($arc["category"] ?? "Rounded Semi-Circle Arc") ?></span>
            <span style="color: #a1a1aa;"><?= e($arc["status"] ?? "Optimal Load") ?></span>
          </div>
        </div>

        <!-- Métrica 2: Stacked Tones (160) -->
        <div class="sandbox-card" style="padding: 24px;">
          <div class="flex-row justify-between align-center" style="margin-bottom: 16px;">
            <span class="text-animation text-fade-blur" style="font-size: 13px; font-weight: 700; letter-spacing: 0.06em; color: #a1a1aa;">
              <?= e($stacked["title"] ?? "STACKED TONES") ?>
            </span>
            <span class="sandbox-badge"><?= e($stacked["tag"] ?? "Layers") ?></span>
          </div>
          <div class="flex-row align-baseline gap10" style="margin-bottom: 8px;">
            <span class="text-animation text-counter" style="font-size: 42px; font-weight: 800; color: #ffffff; letter-spacing: -0.03em;" 
                  data-target="160" data-duration="1350">
              0
            </span>
            <span class="text-animation text-slide-up" style="font-size: 15px; color: #71717a;"><?= e($stacked["label"] ?? "cumulative") ?></span>
          </div>
          <div style="font-size: 12px; color: #52525b; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 12px; margin-top: 16px;" class="flex-row justify-between">
            <span><?= e($stacked["category"] ?? "3 Monochrome Layers") ?></span>
            <span style="color: #a1a1aa;">Stacked Geometry</span>
          </div>
        </div>

        <!-- Métrica 3: Tile Treemap (100%) -->
        <div class="sandbox-card" style="padding: 24px;">
          <div class="flex-row justify-between align-center" style="margin-bottom: 16px;">
            <span class="text-animation text-fade-blur" style="font-size: 13px; font-weight: 700; letter-spacing: 0.06em; color: #a1a1aa;">
              <?= e($treemap["title"] ?? "TILE TREEMAP") ?>
            </span>
            <span class="sandbox-badge"><?= e($treemap["tag"] ?? "Allocation") ?></span>
          </div>
          <div class="flex-row align-baseline gap10" style="margin-bottom: 8px;">
            <span class="text-animation text-counter" style="font-size: 42px; font-weight: 800; color: #ffffff; letter-spacing: -0.03em;" 
                  data-target="100" data-suffix="%" data-duration="1100">
              0%
            </span>
            <span class="text-animation text-slide-up" style="font-size: 15px; color: #71717a;"><?= e($treemap["label"] ?? "partitioned") ?></span>
          </div>
          <div style="font-size: 12px; color: #52525b; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 12px; margin-top: 16px;" class="flex-row justify-between">
            <span><?= e($treemap["category"] ?? "Rounded Corner Tiles") ?></span>
            <span style="color: #a1a1aa;">4 Resource Partitions</span>
          </div>
        </div>

        <!-- Métrica 4: Test monetario (Moneda con decimales) -->
        <div class="sandbox-card" style="padding: 24px;">
          <div class="flex-row justify-between align-center" style="margin-bottom: 16px;">
            <span class="text-animation text-fade-blur" style="font-size: 13px; font-weight: 700; letter-spacing: 0.06em; color: #a1a1aa;">
              REVENUE FLOW
            </span>
            <span class="sandbox-badge">Real-time</span>
          </div>
          <div class="flex-row align-baseline gap10" style="margin-bottom: 8px;">
            <span class="text-animation text-counter" style="font-size: 42px; font-weight: 800; color: #ffffff; letter-spacing: -0.03em;" 
                  data-target="4850.50" data-prefix="$" data-decimals="2" data-duration="1500">
              $0.00
            </span>
            <span class="text-animation text-slide-up" style="font-size: 15px; color: #71717a;">USD total</span>
          </div>
          <div style="font-size: 12px; color: #52525b; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 12px; margin-top: 16px;" class="flex-row justify-between">
            <span>Comas y Decimales</span>
            <span style="color: #10b981;">+14.2%</span>
          </div>
        </div>

      </div>
    </div>

    <!-- SECCIÓN 2: EFECTOS DE ENTRADA (Múltiples elementos probados con querySelectorAll) -->
    <div class="flex-column gap15 w100" style="margin-top: 10px;">
      <div class="flex-row align-center justify-between">
        <h3 style="font-size: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa; margin: 0;">
          2. Galería de Efectos de Entrada de Texto
        </h3>
        <span style="font-size: 12px; color: #71717a;">Invocación por clases sin IDs</span>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;" class="w100">

        <!-- Efecto Scramble / Decoder (Demostración de 2 elementos simultáneos con la misma clase) -->
        <div class="sandbox-card" style="padding: 24px;">
          <div class="flex-row justify-between align-center" style="margin-bottom: 16px;">
            <span class="sandbox-badge">.text-animation.text-scramble</span>
            <button class="sandbox-btn" style="padding: 4px 10px; font-size: 11px;" type="button" 
                    onclick="document.querySelectorAll('.text-animation.text-scramble').forEach(el => window.TextAnimator.animateText(el, 'scramble'))">
              Probar
            </button>
          </div>
          <div class="flex-column justify-center" style="min-height: 56px;">
            <p class="text-animation text-scramble" style="font-size: 20px; font-weight: 700; color: #ffffff; margin: 0; line-height: 1.35;">
              SPEEDOMETER GAUGE 78%
            </p>
            <p class="text-animation text-scramble" style="font-size: 14px; font-weight: 500; color: #a1a1aa; margin: 6px 0 0 0; line-height: 1.35;">
              seguridad verificada • 99.8%
            </p>
          </div>
          <p style="font-size: 12px; color: #71717a; margin: 12px 0 0 0;">
            Ambos textos usan <code style="color: #a1a1aa;">.text-scramble</code> manteniendo estrictamente su misma tipografía, tamaño y centrado sin saltos en el contenedor.
          </p>
        </div>

        <!-- Efecto Typewriter con Cursor -->
        <div class="sandbox-card" style="padding: 24px;">
          <div class="flex-row justify-between align-center" style="margin-bottom: 12px;">
            <span class="sandbox-badge">.text-animation.text-typewriter</span>
            <button class="sandbox-btn" style="padding: 4px 10px; font-size: 11px;" type="button" 
                    onclick="document.querySelectorAll('.text-animation.text-typewriter').forEach(el => window.TextAnimator.animateText(el, 'typewriter'))">
              Probar
            </button>
          </div>
          <p style="font-size: 18px; font-weight: 600; color: #ffffff; margin: 0; min-height: 28px;">
            <span class="text-animation text-typewriter">Monochrome Minimalist Interface</span>
          </p>
          <p style="font-size: 12px; color: #71717a; margin: 10px 0 0 0;">
            Escritura secuencial de caracteres con cursor parpadeante dinámico.
          </p>
        </div>

        <!-- Efecto Fade Blur -->
        <div class="sandbox-card" style="padding: 24px;">
          <div class="flex-row justify-between align-center" style="margin-bottom: 12px;">
            <span class="sandbox-badge">.text-animation.text-fade-blur</span>
            <button class="sandbox-btn" style="padding: 4px 10px; font-size: 11px;" type="button" 
                    onclick="document.querySelectorAll('.text-animation.text-fade-blur').forEach(el => window.TextAnimator.animateText(el, 'fade-blur'))">
              Probar
            </button>
          </div>
          <p class="text-animation text-fade-blur" style="font-size: 18px; font-weight: 600; color: #ffffff; margin: 0; min-height: 28px;">
            Smooth exponential ease-out entry
          </p>
          <p style="font-size: 12px; color: #71717a; margin: 10px 0 0 0;">
            Desenfoque progresivo con curva cúbica suave (estilo Apple).
          </p>
        </div>

        <!-- Efecto Split Chars -->
        <div class="sandbox-card" style="padding: 24px;">
          <div class="flex-row justify-between align-center" style="margin-bottom: 12px;">
            <span class="sandbox-badge">.text-animation.text-split-chars</span>
            <button class="sandbox-btn" style="padding: 4px 10px; font-size: 11px;" type="button" 
                    onclick="document.querySelectorAll('.text-animation.text-split-chars').forEach(el => window.TextAnimator.animateText(el, 'split-chars'))">
              Probar
            </button>
          </div>
          <p class="text-animation text-split-chars" style="font-size: 18px; font-weight: 700; color: #ffffff; margin: 0; min-height: 28px;">
            PARTITION ALLOCATION
          </p>
          <p style="font-size: 12px; color: #71717a; margin: 10px 0 0 0;">
            Aparición escalonada letra por letra mediante micro-fragmentos del DOM.
          </p>
        </div>

      </div>
    </div>

    <!-- SECCIÓN 3: LOOPS CONTINUOS (class="text-animation text-loop-...") -->
    <div class="flex-column gap15 w100" style="margin-top: 10px;">
      <div class="flex-row align-center justify-between">
        <h3 style="font-size: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa; margin: 0;">
          3. Bucles Continuos (<span style="color: #ffffff;">.text-animation .text-loop-*</span>)
        </h3>
        <span style="font-size: 12px; color: #71717a;">Estados de Actividad</span>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;" class="w100">

        <!-- Loop Pulse -->
        <div class="sandbox-card" style="padding: 24px;">
          <div class="flex-row justify-between align-center" style="margin-bottom: 14px;">
            <span class="sandbox-badge">.text-loop-pulse</span>
            <span class="text-animation text-loop-pulse" style="font-size: 11px; font-weight: 700; color: #10b981; display: inline-flex; align-items: center; gap: 6px;">
              <span style="width: 7px; height: 7px; background-color: #10b981; border-radius: 50%;"></span>
              ACTIVO EN VIVO
            </span>
          </div>
          <p style="font-size: 14px; color: #e4e4e7; margin: 0;">
            Pulsación rítmica sutil para indicar métricas en streaming o conexión en vivo.
          </p>
        </div>

        <!-- Loop Shimmer -->
        <div class="sandbox-card" style="padding: 24px;">
          <div class="flex-row justify-between align-center" style="margin-bottom: 14px;">
            <span class="sandbox-badge">.text-loop-shimmer</span>
          </div>
          <p class="text-animation text-loop-shimmer" style="font-size: 18px; font-weight: 800; margin: 0;">
            MONOCHROME GRAPHICS ENGINE
          </p>
          <p style="font-size: 13px; color: #71717a; margin: 8px 0 0 0;">
            Barrido de luz metálica continua a lo largo del degradado del texto.
          </p>
        </div>

        <!-- Loop Breathing -->
        <div class="sandbox-card" style="padding: 24px;">
          <div class="flex-row justify-between align-center" style="margin-bottom: 14px;">
            <span class="sandbox-badge">.text-loop-breathing</span>
          </div>
          <p class="text-animation text-loop-breathing" style="font-size: 18px; font-weight: 700; color: #ffffff; margin: 0;">
            Optimal Cluster Health
          </p>
          <p style="font-size: 13px; color: #71717a; margin: 8px 0 0 0;">
            Respiración sutil de sombra y brillo para resaltar indicadores críticos.
          </p>
        </div>

      </div>
    </div>

    <!-- SECCIÓN 4: DEMOSTRACIÓN DE SALIDAS SINCRONIZADAS -->
    <div class="sandbox-card flex-column gap15 w100" style="padding: 28px; margin-top: 10px; border-color: rgba(255,255,255,0.14);">
      <div class="flex-row justify-between align-center flex-wrap gap10">
        <div>
          <span class="sandbox-badge" style="background: rgba(239,68,68,0.15); color: #f87171; border-color: rgba(239,68,68,0.3);">
            Salidas Sincronizadas
          </span>
          <h4 style="font-size: 18px; font-weight: 700; color: #ffffff; margin: 8px 0 4px 0;">
            Comportamiento Coordinado de Desaparición
          </h4>
          <p style="font-size: 13px; color: #71717a; margin: 0;">
            Al ejecutar la salida, el contador numérico desciende a cero en cuenta regresiva mientras los textos se desvanecen.
          </p>
        </div>
        <button class="sandbox-btn sandbox-btn-danger" type="button" 
                onclick="document.querySelectorAll('.card-exit-demo .text-animation').forEach(el => window.TextAnimator.animateExit(el))">
          Probar Salida en esta Tarjeta
        </button>
      </div>

      <div class="card-exit-demo flex-row align-center justify-between p20 flex-wrap gap20" style="background: #121215; border-radius: 14px; border: 1px dashed rgba(255,255,255,0.1); padding: 20px;">
        <div>
          <p class="text-animation text-slide-up" style="font-size: 13px; font-weight: 700; color: #a1a1aa; letter-spacing: 0.05em; margin: 0 0 6px 0;">
            BUFFER STATUS
          </p>
          <h5 class="text-animation text-fade-blur" style="font-size: 26px; font-weight: 800; color: #ffffff; margin: 0;">
            Procesamiento Completado
          </h5>
        </div>
        <div class="flex-row align-baseline gap10">
          <span class="text-animation text-counter" style="font-size: 48px; font-weight: 800; color: #ffffff;" 
                data-target="94" data-suffix="%" data-duration="900">
            94%
          </span>
          <span class="text-animation text-fade-blur" style="font-size: 14px; color: #71717a;">efficiency index</span>
        </div>
      </div>
    </div>

    <!-- SECCIÓN 5: INTEGRACIÓN CON SCROLL OBSERVER DEL FRAMEWORK -->
    <div class="observer flex-column gap15 w100" style="margin-top: 50px; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.08); padding-bottom: 20px;">
      <div class="flex-row align-center justify-between">
        <div>
          <span class="sandbox-badge" style="background: rgba(16,185,129,0.15); color: #34d399; border-color: rgba(16,185,129,0.3);">
            Framework Component • scrollObserver.js
          </span>
          <h4 style="font-size: 18px; font-weight: 700; color: #ffffff; margin: 8px 0 4px 0;">
            5. Activación Automática al hacer Scroll (.observer y ob-*)
          </h4>
          <p style="font-size: 13px; color: #71717a; margin: 0;">
            Estos elementos están configurados con <code style="color: #a1a1aa;">ob-20</code> dentro de un contenedor <code style="color: #a1a1aa;">.observer</code>. Se disparan automáticamente solo cuando entran en la pantalla al hacer scroll.
          </p>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;" class="w100">
        
        <div class="sandbox-card" style="padding: 24px;">
          <span class="sandbox-badge">Contador con ob-20</span>
          <div class="flex-row align-baseline gap10" style="margin: 12px 0 6px 0;">
            <span class="text-animation text-counter ob-20" style="font-size: 40px; font-weight: 800; color: #ffffff;" data-target="99.9" data-suffix="%" data-decimals="1" data-duration="1500">
              0%
            </span>
            <span class="text-animation text-fade-blur ob-20" style="font-size: 14px; color: #71717a;">uptime</span>
          </div>
          <p style="font-size: 12px; color: #52525b; margin: 0;">Disparado por ScrollObserver al 20% de visibilidad.</p>
        </div>

        <div class="sandbox-card" style="padding: 24px;">
          <span class="sandbox-badge">Scramble con ob-20</span>
          <p class="text-animation text-scramble ob-20" style="font-size: 18px; font-weight: 700; color: #ffffff; margin: 12px 0 6px 0; min-height: 24px;">
            DECENTRALIZED NETWORK
          </p>
          <p style="font-size: 12px; color: #52525b; margin: 0;">Decodifica de forma autónoma al aparecer en el scroll.</p>
        </div>

        <div class="sandbox-card" style="padding: 24px;">
          <span class="sandbox-badge">Typewriter con ob-20</span>
          <p style="font-size: 16px; font-weight: 600; color: #ffffff; margin: 12px 0 6px 0; min-height: 24px;">
            <span class="text-animation text-typewriter ob-20">Reactive Viewport Trigger</span>
          </p>
          <p style="font-size: 12px; color: #52525b; margin: 0;">Escribe automáticamente al entrar en el viewport.</p>
        </div>

      </div>
    </div>

  </div>

</div>

<!-- SCRIPT DE CONTROL PARA AMBAS LIBRERÍAS (GRÁFICOS SVG Y TEXTANIMATOR) -->
<script type="module">
  // 1. Instancias de los dos módulos (globales o import dinámico seguro)
  let SvgChart = window.SvgChartAnimator;
  if (!SvgChart) {
    try {
      SvgChart = await import('/App/Public/Js/svgChartAnimator.js');
    } catch (e) {
      console.warn('Importando svgChartAnimator vía ruta alternativa:', e);
    }
  }

  let TextAnim = window.TextAnimator;
  if (!TextAnim) {
    try {
      TextAnim = await import('/App/Public/Js/textAnimator.js');
    } catch (e) {
      console.warn('Importando textAnimator vía ruta alternativa:', e);
    }
  }

  // 2. Control de la Librería de Gráficos SVG
  document.getElementById('btnPlayChartsEntry')?.addEventListener('click', () => {
    if (SvgChart && typeof SvgChart.initAllSvgCharts === 'function') {
      SvgChart.initAllSvgCharts(document, true);
    }
  });

  document.getElementById('btnPlayChartsExit')?.addEventListener('click', () => {
    if (SvgChart && typeof SvgChart.playAllSvgChartExits === 'function') {
      SvgChart.playAllSvgChartExits(document);
    }
  });

  document.getElementById('btnResetCharts')?.addEventListener('click', () => {
    const cards = document.querySelectorAll('.mono-chart-card');
    cards.forEach(card => card.classList.remove('is-exiting'));
    if (SvgChart && typeof SvgChart.initAllSvgCharts === 'function') {
      SvgChart.initAllSvgCharts(document, true);
    }
  });

  // 3. Control de la Librería de Textos y Contadores (Sandbox)
  document.getElementById('btnPlayEntry')?.addEventListener('click', () => {
    if (TextAnim && typeof TextAnim.playAllTextAnimations === 'function') {
      TextAnim.playAllTextAnimations(document, true);
    }
  });

  document.getElementById('btnPlayExit')?.addEventListener('click', () => {
    if (TextAnim && typeof TextAnim.playAllTextExits === 'function') {
      TextAnim.playAllTextExits(document);
    }
  });

  document.getElementById('btnToggleLoop')?.addEventListener('click', () => {
    if (TextAnim && typeof TextAnim.toggleAllLoops === 'function') {
      TextAnim.toggleAllLoops(document);
    }
  });

  document.getElementById('btnReset')?.addEventListener('click', () => {
    if (TextAnim && typeof TextAnim.playAllTextAnimations === 'function') {
      TextAnim.playAllTextAnimations(document, true);
    }
  });

  // 4. Los módulos gestionan su ciclo de vida al cargar el DOM de forma autónoma
</script>