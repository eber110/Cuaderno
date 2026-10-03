<?php
  /** @var array $demo */
  $arc = $demo["arcMeter"] ?? [];
  $stacked = $demo["stackedTones"] ?? [];
  $treemap = $demo["treemap"] ?? [];
?>

<div class="container container-xl-mid flex-column gap25 w100" style="padding: 40px 20px; min-height: 100vh; background-color: #0d0d11; color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

  <!-- CABECERA PRINCIPAL Y PANEL DE CONTROL -->
  <div class="flex-column gap15 w100" style="border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 25px;">
    <div class="flex-row justify-between align-center flex-wrap gap15">
      <div>
        <div class="flex-row align-center gap10">
          <span class="sandbox-badge">Vanilla JS • Sin IDs</span>
          <h1 style="font-size: 24px; font-weight: 700; margin: 0; letter-spacing: -0.02em;">Laboratorio de Animaciones: Textos y Números</h1>
        </div>
        <p style="color: #71717a; margin: 6px 0 0 0; font-size: 14px;">
          Todas las animaciones se invocan con la clase base <code style="color: #a1a1aa; background: #1c1c22; padding: 2px 6px; border-radius: 4px;">.text-animation</code> seguida de la variante (ej: <code style="color: #a1a1aa; background: #1c1c22; padding: 2px 6px; border-radius: 4px;">.text-scramble</code>, <code style="color: #a1a1aa; background: #1c1c22; padding: 2px 6px; border-radius: 4px;">.text-counter</code>) y se procesan en masa mediante <code style="color: #a1a1aa; background: #1c1c22; padding: 2px 6px; border-radius: 4px;">querySelectorAll()</code>.
        </p>
      </div>

      <!-- BOTONERA DE CONTROL INTERACTIVO -->
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
          ↺ Reiniciar
        </button>
      </div>
    </div>
  </div>

  <!-- SECCIÓN 1: CONTADORES NUMÉRICOS DE LAS TARJETAS (class="text-animation text-counter") -->
  <div class="flex-column gap15 w100">
    <div class="flex-row align-center justify-between">
      <h2 style="font-size: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa; margin: 0;">
        1. Métricas Numéricas (<span style="color: #ffffff;">.text-animation .text-counter</span>)
      </h2>
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
      <h2 style="font-size: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa; margin: 0;">
        2. Galería de Efectos de Entrada de Texto
      </h2>
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
      <h2 style="font-size: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa; margin: 0;">
        3. Bucles Continuos (<span style="color: #ffffff;">.text-animation .text-loop-*</span>)
      </h2>
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
        <h3 style="font-size: 18px; font-weight: 700; color: #ffffff; margin: 8px 0 4px 0;">
          Comportamiento Coordinado de Desaparición
        </h3>
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
        <h4 class="text-animation text-fade-blur" style="font-size: 26px; font-weight: 800; color: #ffffff; margin: 0;">
          Procesamiento Completado
        </h4>
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
  <div class="observer flex-column gap15 w100" style="margin-top: 80px; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.08); padding-bottom: 120px;">
    <div class="flex-row align-center justify-between">
      <div>
        <span class="sandbox-badge" style="background: rgba(16,185,129,0.15); color: #34d399; border-color: rgba(16,185,129,0.3);">
          Framework Component • scrollObserver.js
        </span>
        <h2 style="font-size: 18px; font-weight: 700; color: #ffffff; margin: 8px 0 4px 0;">
          5. Activación Automática al hacer Scroll (.observer y ob-*)
        </h2>
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

<!-- SCRIPT DE CONTROL DEL LABORATORIO -->
<script type="module">
  // Usar TextAnimator expuesto en window por el bundle del framework o importar con ruta absoluta
  let TextAnimator = window.TextAnimator;
  if (!TextAnimator) {
    try {
      TextAnimator = await import('/App/Public/Js/textAnimator.js');
    } catch (e) {
      console.error('Error importando textAnimator:', e);
    }
  }

  const { playAllTextAnimations, playAllTextExits, toggleAllLoops } = TextAnimator || {};

  // Botones de control del sandbox (procesan en masa mediante querySelectorAll)
  document.getElementById('btnPlayEntry')?.addEventListener('click', () => {
    if (playAllTextAnimations) playAllTextAnimations(document, true);
  });

  document.getElementById('btnPlayExit')?.addEventListener('click', () => {
    if (playAllTextExits) playAllTextExits(document);
  });

  document.getElementById('btnToggleLoop')?.addEventListener('click', () => {
    if (toggleAllLoops) toggleAllLoops(document);
  });

  document.getElementById('btnReset')?.addEventListener('click', () => {
    if (playAllTextAnimations) playAllTextAnimations(document, true);
  });

  // Ejecución inicial automática (solo elementos fuera de scroll observer)
  setTimeout(() => {
    if (playAllTextAnimations) playAllTextAnimations(document, false);
  }, 250);
</script>