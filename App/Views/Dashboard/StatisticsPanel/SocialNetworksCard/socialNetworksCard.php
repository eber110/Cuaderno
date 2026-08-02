<?php
  /** 
   * @var array $socialStats 
   */
?>
<div class="flex-column gap15 p20 br20 border-item-panel back-body shadow-soft w100">
  <div class="flex-row center-between wrap gap10">
    <div>
      <h4 class="bold700 x20 flex-row center-start gap10">
        <?= svg("share-from", "x20") ?> Rendimiento y Horarios por Red Social
      </h4>
      <p class="text-muted">Desglose de visitas, día pico y hora óptima según la fuente de tráfico (Referrer)</p>
    </div>
    <span class="p5 pl10 pr10 br50 back-modal-item bold600 text-muted">
      Calculado por Referrer
    </span>
  </div>

  <?php if (!empty($socialStats)) : ?>
    <div class="grid col-desk-4 col-mid-2 col-sml-1 gap15 w100 mt5">
      <?php foreach ($socialStats as $sNet) : ?>
        <!-- 1. Tarjeta clicable (.modal-btn) -->
        <div class="flex-column center-between p15 br15 border-item-panel text-c gap10 shadow-soft modal-btn darken pointer |hover-scale-soft relative min-hpx180">
          
          <!-- Encabezado de la Red Social -->
          <div class="flex-row center-between wrap gap5 w100">
            <div class="flex-row center-start gap8">
              <span class="p8 br100 back-body flex-row center-center text-primary shadow-soft">
                <?= svg($sNet['icon'], "x20") ?>
              </span>
              <span class="bold700 textb x16"><?= e($sNet['name']) ?></span>
            </div>
            <span class="bold700 text-primary x16"><?= number_format($sNet['total']) ?> <span class="font-normal text-muted x12">visitas</span></span>
          </div>

          <div class="w100 border-top mt2 mb2"></div>

          <!-- Mejor Día y Hora de esta Red -->
          <div class="flex-column gap6 w100 text-l">
            <div class="flex-row center-between">
              <span class="text-muted bold500 flex-row center-start gap5"><?= svg("calendar", "x16") ?> Día Pico:</span>
              <span class="bold700 textb"><?= e($sNet['bestDay']) ?></span>
            </div>
            <div class="flex-row center-between">
              <span class="text-muted bold500 flex-row center-start gap5"><?= svg("clock", "x16") ?> Hora Pico:</span>
              <span class="bold700 text-primary"><?= e($sNet['bestHour']) ?></span>
            </div>
          </div>

          <!-- Botón Pill para desplegar el modal con gráfico -->
          <span class="flex-row center-center gap5 back-modal-item pl10 pr10 p5 br50 mt5 w100 text-muted bold500">
            <?= svg("eye", "x20") ?> Ver gráfico y desglose
          </span>

        </div>

        <!-- 2. MODAL CON FORMATO IDÉNTICO A GENERALSUMMARYCARD -->
        <div class="hidden">
          <div class="flex-column center-center w100 p20 h-dvh">
            <div class="wpx600 w-sml-100">
              <div id="close-modal-btn" class="modal-close-button pointer absolute top right p2 m5 closed-modal-preview br50 z-index-20">
                <?= svg("xmark", "x20") ?>
              </div>
            </div>
            <div class="wpx600 w-sml-100 overflow-y-scroll back-modal-item br-desk-15 br-mid-15 br-sml-0 p25 text-menu-modal relative shadow-1 flex-column gap20">
              
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
                <div class="flex-column p12 br12 border-item-panel back-body gap3">
                  <span class="text-muted bold500">Total Visitas</span>
                  <span class="x20 bold700 text-primary"><?= number_format($sNet['total']) ?></span>
                  <span class="text-muted x12">Mes activo</span>
                </div>
                <div class="flex-column p12 br12 border-item-panel back-body gap3">
                  <span class="text-muted bold500 flex-row center-start gap5"><?= svg("calendar", "x16") ?> Día Pico</span>
                  <span class="x20 bold700 textb"><?= e($sNet['bestDay']) ?></span>
                  <span class="text-muted x12">Mayor interacción</span>
                </div>
                <div class="flex-column p12 br12 border-item-panel back-body gap3">
                  <span class="text-muted bold500 flex-row center-start gap5"><?= svg("clock", "x16") ?> Hora Pico</span>
                  <span class="x20 bold700 text-primary"><?= e($sNet['bestHour']) ?></span>
                  <span class="text-muted x12">Horario óptimo</span>
                </div>
              </div>

              <div class="w100 border-top"></div>

              <!-- SECCIÓN DE GRÁFICO (STACKED COLUMN) -->
              <div class="flex-column gap10 w100 p15 br15 border-item-panel back-body shadow-soft">
                <div class="flex-row center-between">
                  <h4 class="bold600 x20 flex-row center-start gap5 textb">
                    <?= svg("chart", "x20") ?> Rendimiento Semanal (Día y Horarios)
                  </h4>
                </div>
                <p class="text-muted">
                  Distribución del volumen de visitas recibidas desde <?= e($sNet['name']) ?> por cada día de la semana (columnas), clasificada en 4 franjas horarias (Mañana, Tarde, Noche y Madrugada).
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
