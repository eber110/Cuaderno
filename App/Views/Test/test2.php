<?php
  use Base\Module\GraphicsModule;

  // Configuración global de estilos para todos los gráficos (color base 'texto', etiquetas 'textw', ejes 'color1' y 400ms unificados)
  GraphicsModule::configStyle([
    "colorLabel" => "textw",
    "axisLabel"  => "#cfcfcf",
    "color"      => "color3",
    "transition" => 500
  ]);

  /** @var array $demo */
  $arc = $demo["arcMeter"];
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
    <div class="flex-column gap15 br20 p20" style="border: #52525b solid 1px;">
      <p>ARC METER</p>
      <p class="x40 bold900 text-animation text-counter p0 m0"><?= $arc["value"]."%";?></p>
      <div class="br15 back8 p20">
        <?php _chart("arcMeter", [
          "value" => $arc["value"]."%" ?? 90,
          "label" => $arc["label"] ?? "Optimal Load",
        ]); ?>
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
        <?php _chart("stackedTones", [
          "quarters" => $stacked["quarters"] ?? [],
          "maxVal"   => 160
        ]); ?>
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

      <div class="mono-chart-viewport" style="padding: 6px;">
        <?php _chart("tileTreemap", [
          "tiles" => $treemap["tiles"] ?? []
        ]); ?>
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
        <?php _chart("hybridSpline"); ?>
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
        <?php _chart(""); ?>
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
       DEMOSTRACIÓN DE VARIACIONES DE COLOR MONOCROMÁTICAS (OKLCH) Y PHP API
       ========================================================================= -->
  <div class="flex-column gap20 w100" style="background: #141417; border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 24px; margin-top: 10px;">
    <div class="flex-row justify-between align-center flex-wrap gap15">
      <div>
        <span class="mono-chart-badge" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.18);">
          API PHP • Base\Module\GraphicsModule
        </span>
        <h2 style="font-size: 20px; font-weight: 700; color: #ffffff; margin: 8px 0 0 0;">
          Demostración de Paleta Monocromática (Un solo color base con OKLCH)
        </h2>
      </div>
      <p style="font-size: 13px; color: #a1a1aa; max-width: 620px; margin: 0; line-height: 1.5;">
        Cada gráfico recibe únicamente <strong>un solo color base</strong> (vía clase <code style="color: #67e8f9;">.texto</code>, variable CSS <code style="color: #67e8f9;">var(--back-color5)</code> o HEX). Todas las partes secundarias (pistas de fondo, capas apiladas, rejillas y números) se calculan automáticamente mediante <strong>CSS OKLCH Relative Colors</strong>.
      </p>
    </div>

    <!-- Muestra de 3 llamadas personalizadas con colores distintos -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
      <!-- Variante 1: Clase texto (sin punto, color heredado) -->
      <div style="background: #09090b; border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 18px;" class="flex-column gap10">
        <div class="flex-row justify-between align-center">
          <span style="font-size: 11px; font-weight: 700; color: #71717a; text-transform: uppercase;">1. Clase texto (sin punto)</span>
          <span style="font-size: 11px; color: #38bdf8; font-family: monospace;">color: "texto"</span>
        </div>
        <div style="height: 140px;" class="flex-row center">
          <?php _chart("arcMeter", ["color" => "texto", "value" => 88, "label" => "Core System"]); ?>
        </div>
        <code style="font-size: 11px; color: #a1a1aa; background: rgba(255,255,255,0.04); padding: 6px 8px; border-radius: 6px;">
          _chart("arcMeter", ["color" => "texto", "value" => 88]);
        </code>
      </div>

      <!-- Variante 2: Variable Coral var(--back-color5) -->
      <div style="background: #09090b; border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 18px;" class="flex-column gap10">
        <div class="flex-row justify-between align-center">
          <span style="font-size: 11px; font-weight: 700; color: #71717a; text-transform: uppercase;">2. Variable --back-color5</span>
          <span style="font-size: 11px; color: #fb7185; font-family: monospace;">color: "var(--back-color5)"</span>
        </div>
        <div style="height: 140px;" class="flex-row center">
          <?php _chart("arcMeter", ["color" => "var(--back-color5)", "value" => 72, "label" => "Coral Engine"]); ?>
        </div>
        <code style="font-size: 11px; color: #a1a1aa; background: rgba(255,255,255,0.04); padding: 6px 8px; border-radius: 6px;">
          _chart("arcMeter", ["color" => "var(--back-color5)", "value" => 72]);
        </code>
      </div>

      <!-- Variante 3: Variable Azul var(--back-color6) en Stacked Tones -->
      <div style="background: #09090b; border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 18px;" class="flex-column gap10">
        <div class="flex-row justify-between align-center">
          <span style="font-size: 11px; font-weight: 700; color: #71717a; text-transform: uppercase;">3. Variable --back-color6</span>
          <span style="font-size: 11px; color: #818cf8; font-family: monospace;">color: "var(--back-color6)"</span>
        </div>
        <div style="height: 140px;" class="flex-row center">
          <?php _chart("stackedTones", ["color" => "var(--back-color6)"]); ?>
        </div>
        <code style="font-size: 11px; color: #a1a1aa; background: rgba(255,255,255,0.04); padding: 6px 8px; border-radius: 6px;">
          _chart("stackedTones", ["color" => "var(--back-color6)"]);
        </code>
      </div>

      <!-- Variante 4: HEX directo con # (#10b981 Verde Esmeralda) -->
      <div style="background: #09090b; border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 18px;" class="flex-column gap10">
        <div class="flex-row justify-between align-center">
          <span style="font-size: 11px; font-weight: 700; color: #71717a; text-transform: uppercase;">4. HEX con # (#10b981)</span>
          <span style="font-size: 11px; color: #10b981; font-family: monospace;">color: "#10b981"</span>
        </div>
        <div style="height: 140px;" class="flex-row center">
          <?php _chart("arcMeter", ["color" => "#10b981", "colorLabel" => "#ffffff", "value" => 94, "label" => "Emerald Matrix"]); ?>
        </div>
        <code style="font-size: 11px; color: #a1a1aa; background: rgba(255,255,255,0.04); padding: 6px 8px; border-radius: 6px;">
          _chart("arcMeter", ["color" => "#10b981", "colorLabel" => "#ffffff"]);
        </code>
      </div>

      <!-- Variante 5: HEX directo sin # (f59e0b Ámbar) -->
      <div style="background: #09090b; border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 18px;" class="flex-column gap10">
        <div class="flex-row justify-between align-center">
          <span style="font-size: 11px; font-weight: 700; color: #71717a; text-transform: uppercase;">5. HEX sin # (f59e0b)</span>
          <span style="font-size: 11px; color: #f59e0b; font-family: monospace;">color: "f59e0b"</span>
        </div>
        <div style="height: 140px;" class="flex-row center">
          <?php _chart("stackedTones", ["color" => "f59e0b", "axisLabel" => "a1a1aa"]); ?>
        </div>
        <code style="font-size: 11px; color: #a1a1aa; background: rgba(255,255,255,0.04); padding: 6px 8px; border-radius: 6px;">
          _chart("stackedTones", ["color" => "f59e0b", "axisLabel" => "a1a1aa"]);
        </code>
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
  // 1. Instancias de los módulos (globales o import dinámico seguro)
  let Charts = window.Charts;
  if (!Charts) {
    try {
      Charts = await import('/App/Rsc/Library/Charts/charts.js');
    } catch (e) {
      console.warn('Importando Charts vía ruta alternativa:', e);
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

  // 2. Control de la Librería de Gráficos SVG (GraphicsModule)
  document.getElementById('btnPlayChartsEntry')?.addEventListener('click', () => {
    document.querySelectorAll('.mono-chart-svg').forEach(svg => svg.classList.remove('is-exiting'));
    if (Charts && typeof Charts.initCharts === 'function') {
      Charts.initCharts(document);
    }
  });

  document.getElementById('btnPlayChartsExit')?.addEventListener('click', () => {
    document.querySelectorAll('.mono-chart-svg').forEach(svg => svg.classList.add('is-exiting'));
  });

  document.getElementById('btnResetCharts')?.addEventListener('click', () => {
    document.querySelectorAll('.mono-chart-svg').forEach(svg => svg.classList.remove('is-exiting'));
    if (Charts && typeof Charts.initCharts === 'function') {
      Charts.initCharts(document);
    }
  });

  // Selector interactivo de porcentajes en Arc Meter (20% / 70% / 100%)
  document.querySelectorAll('.mono-arc-val-pills button').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetPct = parseFloat(btn.dataset.arcTarget);
      document.querySelectorAll('.mono-arc-val-pills button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const card = btn.closest('.mono-chart-card');
      const arcSvg = card ? card.querySelector('.mono-arc-svg') : document.querySelector('.mono-arc-svg');
      const headlineNum = card ? card.querySelector('.mono-chart-value-big') : null;

      if (headlineNum) {
        headlineNum.textContent = `${targetPct}%`;
      }

      if (arcSvg && Charts && typeof Charts.animateArcMeterValue === 'function') {
        Charts.animateArcMeterValue(arcSvg, targetPct, { duration: 400, fromCurrent: true });
      }
    });
  });

  // Toggle interactivo Spline On / Off
  document.getElementById('btnToggleSpline')?.addEventListener('click', (e) => {
    const btn = e.currentTarget;
    const card = btn.closest('.mono-chart-card');
    if (!card) return;
    const path = card.querySelector('.mono-spline-path');
    const area = card.querySelector('.mono-spline-area');
    const dots = card.querySelectorAll('.mono-spline-dot');

    const isActive = btn.classList.contains('active');
    if (isActive) {
      btn.classList.remove('active');
      btn.textContent = 'Spline Off';
      if (path) path.style.opacity = '0';
      if (area) area.style.opacity = '0';
      dots.forEach(d => d.style.opacity = '0');
    } else {
      btn.classList.add('active');
      btn.textContent = 'Spline On';
      if (path) path.style.opacity = '1';
      if (area) area.style.opacity = '1';
      dots.forEach(d => d.style.opacity = '1');
    }
  });

  // Botón Copiar SVG al portapapeles
  document.querySelectorAll('.mono-chart-copy-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const card = btn.closest('.mono-chart-card') || btn.parentElement;
      const svg = card ? card.querySelector('svg') : null;
      if (!svg) return;
      try {
        await navigator.clipboard.writeText(svg.outerHTML);
        btn.classList.add('copied');
        const origTitle = btn.getAttribute('title');
        btn.setAttribute('title', '¡SVG Copiado!');
        setTimeout(() => {
          btn.classList.remove('copied');
          if (origTitle) btn.setAttribute('title', origTitle);
        }, 1800);
      } catch (err) {
        console.error('Error al copiar SVG:', err);
      }
    });
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

  // 4. Inicializar gráficos al cargar
  if (Charts && typeof Charts.initCharts === 'function') {
    Charts.initCharts(document);
  }
</script>