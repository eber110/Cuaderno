<?php
  /** 
   * @var array $socialStats 
   */
?>
<div class="flex-column gap15 p20 br20 back-card-graphic shadow-card-graphic |hover-scale-soft w100">
  <div class="flex-row center-between wrap gap10">
    <div>
      <h4 class="bold700 x20 flex-row center-start gap10">
        <?= svg("share-from", "x20") ?> Rendimiento y Horarios por Red Social
      </h4>
      <p class="text-muted">Desglose de visitas, día pico y hora óptima según la fuente de tráfico (Referrer)</p>
    </div>
    <span class="advice bold600">
      Visitas referidas
    </span>
  </div>

  <?php if (!empty($socialStats)) : ?>
    <div class="grid col-desk-3 col-mid-2 col-sml-1 gap15 w100">
      <?php foreach ($socialStats as $sNet) : ?>
        <!-- 1. Tarjeta clicable (.modal-btn) -->
        <div class="flex-column between-center wrap p15 br15 back-card-graphic shadow-card-graphic gap30 modal-btn darken pointer hover-scale-soft">
          
          <!-- Encabezado de la Red Social -->
          <div class="flex-row center-start wrap gap5 w100">
            <div class="flex-row center-start gap8">
              <span class="flex-row center-center text-primary">
                <?= svg($sNet['icon'], "x20") ?>
              </span>
              <span class="bold700 textb x18"><?= e($sNet['name']) ?></span>
            </div>
          </div>
          
          <div class="flex-column center-center">
            <div class="flex-column center-center gap0 back-container-graphic shadow-card-graphic text-advice bold700 text-primary p10 br10 x35">
              <?= number_format($sNet['total']) ?>
              <span class="bold400 x18">visitas</span>
            </div>
          </div>

          <!-- Mejor Día y Hora de esta Red -->
          <div class="flex-column gap6 w100 text-l">
            <div class="advice x16">
              <p>haz tus publicaciones de <span class="bold500"><?= e($sNet['name']) ?></span> el día <span class="bold500"><?= e($sNet['bestDay']) ?></span> entre <span class="bold500"><?= e($sNet['bestHour']) ?></span></p>
              <p>es el tramo horario con más visitas de tus seguidores.</p>
            </div>
          </div>

          <!-- Botón Pill para desplegar el modal con gráfico -->
          <span class="flex-row center-center gap0 btn-card-graphic shadow-card-graphic hover-scale-soft br50 bold500">
            <?//= svg("eye", "x20") ?> Ver gráfico
          </span>

        </div>

        <!-- 2. MODAL CON FORMATO IDÉNTICO A GENERALSUMMARYCARD -->
        <div class="hidden">
          <div class="flex-column center-center w100 p20 h-dvh">
            <div class="wpx600 w-sml-100">
              <div id="close-modal-btn" class="modal-close-button absolute top right closed-modal-preview">
                <?= svg("xmark", "x20") ?>
              </div>
            </div>
            <div class="wpx600 w-sml-100 overflow-y-scroll back-container-graphic br-desk-15 br-mid-15 br-sml-0 p25 text-menu-modal relative shadow-1 flex-column gap20">
              
              <!-- Encabezado del Modal -->
              <div class="flex-column gap5">
                <h3 class="bold700 x20 flex-row center-start gap10 textb">
                  <?= svg($sNet['icon'], "x20") ?> Desglose de <?= e($sNet['name']) ?>
                </h3>
                <p class="text-muted">
                  Monitoreo detallado de tráfico diario y franjas horarias óptimas recibidas desde <?= e($sNet['name']) ?>.
                </p>
              </div>

              <!-- Indicadores Resumen (Día Pico vs Hora Pico vs Total) -->
              <div class="grid col-desk-3 col-sml-3 gap10 w100">
                <div class="flex-column p12 br12 back-card-graphic shadow-card-graphic gap3">
                  <span class="text-muted bold500">Total Visitas</span>
                  <span class="x20 bold700 text-primary"><?= number_format($sNet['total']) ?></span>
                  <span class="text-muted x12">Mes activo</span>
                </div>
                <div class="flex-column p12 br12 back-card-graphic shadow-card-graphic gap3">
                  <span class="text-muted bold500 flex-row top-start gap5"><?//= svg("calendar", "x16") ?> Día recomendado</span>
                  <span class="x20 bold700 textb"><?= e($sNet['bestDay']) ?></span>
                  <span class="text-muted x12">Mayor interacción</span>
                </div>
                <div class="flex-column p12 br12 back-card-graphic shadow-card-graphic gap3">
                  <span class="text-muted bold500 flex-row top-start gap5"><?//= svg("clock", "x16") ?> Hora recomendada</span>
                  <span class="x20 bold700 text-primary"><?= e($sNet['bestHour']) ?></span>
                  <span class="text-muted x12">Horario óptimo</span>
                </div>
              </div>

              <div class="w100 border-top"></div>

              <!-- SECCIÓN DE GRÁFICO (STACKED COLUMN) -->
              <div class="flex-column gap10 w100 p15 br15 back-card-graphic shadow-card-graphic">
                <div class="flex-row center-between">
                  <h4 class="bold600 x20 flex-row top-start gap5 textb">
                    <?= svg("chart", "x20") ?> Distribución Porcentual
                  </h4>
                  <span class="p2 pl8 pr8 br50 back-primary textw bold600">100% Stacked</span>
                </div>
                <p class="text-muted">
                  Porcentaje de tráfico aportado desde <?= e($sNet['name']) ?> por día de la semana (columnas al 100%), desglosado en franjas horarias con etiquetas directas.
                </p>

                <div class="chart-social-stacked w100 mt5" 
                     data-chart-categories='<?= json_encode($sNet['dayLabels'] ?? []) ?>'
                     data-chart-series='<?= json_encode($sNet['stackedSeries'] ?? []) ?>'>
                </div>
              </div>

            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else : ?>
    <p class="text-muted">No hay datos por red social registrados aún.</p>
  <?php endif; ?>
</div>
