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

        <!-- 2. Contenido oculto del Modal como Hermano Siguiente (nextElementSibling) -->
        <div class="hidden">
          <div class="flex-column center-center w100 wrap">
            <div class="wpx600 w-sml-100 back-modal-item br-desk-15 br-mid-15 br-sml-0 p20 text-menu-modal text-protected h-dvh-sml">
              <div class="flex-column gap15 w100">
                
                <!-- Encabezado del Modal -->
                <div class="flex-row center-between wrap border-bottom pb10">
                  <div class="flex-row center-start gap10">
                    <span class="p8 br100 back-body flex-row center-center text-primary shadow-soft">
                      <?= svg($sNet['icon'], "x20") ?>
                    </span>
                    <h3 class="bold700 x20 textb"><?= e($sNet['name']) ?></h3>
                  </div>
                  <span class="p5 pl12 pr12 br50 back-body bold700 text-primary">
                    <?= number_format($sNet['total']) ?> visitas
                  </span>
                </div>

                <!-- Resumen de Día y Hora Pico -->
                <div class="grid col-desk-2 col-mid-2 col-sml-1 gap10 w100">
                  <div class="flex-row center-between p12 br12 border-item-panel back-body">
                    <span class="text-muted bold500 flex-row center-start gap5"><?= svg("calendar", "x16") ?> Día Pico:</span>
                    <span class="bold700 textb"><?= e($sNet['bestDay']) ?></span>
                  </div>
                  <div class="flex-row center-between p12 br12 border-item-panel back-body">
                    <span class="text-muted bold500 flex-row center-start gap5"><?= svg("clock", "x16") ?> Hora Pico:</span>
                    <span class="bold700 text-primary"><?= e($sNet['bestHour']) ?></span>
                  </div>
                </div>

                <!-- Gráfico Stacked Column de Visitas por Día de la Semana -->
                <div class="flex-column gap8 p15 br15 border-item-panel back-body shadow-soft w100">
                  <h5 class="bold700 x16 flex-row center-start gap8">
                    <?= svg("chart-column-solid", "x18") ?> Rendimiento Semanal (Día y Horarios)
                  </h5>
                  <p class="text-muted x12">Distribución de tráfico semanal desglosada por franja horaria.</p>

                  <div class="chart-social-stacked w100 mt5" 
                       data-chart-categories='<?= json_encode($sNet['dayLabels'] ?? []) ?>'
                       data-chart-series='<?= json_encode($sNet['stackedSeries'] ?? []) ?>'>
                  </div>
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
