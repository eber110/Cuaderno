<?php
  /** 
   * @var array $socialStats 
   */
?>
<div class="flex-column gap15 p20 br20 border-item-panel back-body shadow-soft w100">
  <div class="flex-row center-between wrap gap10">
    <div>
      <h4 class="bold700 x18 flex-row center-start gap10">
        <?= svg("share-from", "x22") ?> Rendimiento y Horarios por Red Social
      </h4>
      <p class="text-muted x13">Desglose de visitas, día pico y hora óptima según la fuente de tráfico (Referrer)</p>
    </div>
    <span class="x12 p5 pl10 pr10 br50 back-modal-item bold600 text-muted">
      Calculado por Referrer
    </span>
  </div>

  <?php if (!empty($socialStats)) : ?>
    <div class="grid col-desk-4 col-mid-2 col-sml-1 gap15 w100 mt5">
      <?php foreach ($socialStats as $sNet) : ?>
        <div class="flex-column gap10 p15 br15 border-item-panel back-modal-item shadow-soft">
          
          <!-- Encabezado de la Red Social -->
          <div class="flex-row center-between wrap gap5">
            <div class="flex-row center-start gap8">
              <span class="p8 br100 back-body flex-row center-center text-primary shadow-soft">
                <?= svg($sNet['icon'], "x20") ?>
              </span>
              <span class="bold700 x15"><?= e($sNet['name']) ?></span>
            </div>
            <span class="bold700 x15 text-primary"><?= number_format($sNet['total']) ?> <span class="x11 bold500 text-muted">visitas</span></span>
          </div>

          <div class="w100 border-top mt2 mb2"></div>

          <!-- Mejor Día y Hora de esta Red -->
          <div class="flex-column gap6 x13">
            <div class="flex-row center-between">
              <span class="text-muted bold500 flex-row center-start gap5"><?= svg("calendar", "x14") ?> Día Pico:</span>
              <span class="bold700 textb"><?= e($sNet['bestDay']) ?></span>
            </div>
            <div class="flex-row center-between">
              <span class="text-muted bold500 flex-row center-start gap5"><?= svg("clock", "x14") ?> Hora Pico:</span>
              <span class="bold700 text-primary"><?= e($sNet['bestHour']) ?></span>
            </div>
          </div>

        </div>
      <?php endforeach; ?>
    </div>
  <?php else : ?>
    <p class="text-muted x14">No hay datos por red social registrados aún.</p>
  <?php endif; ?>
</div>
