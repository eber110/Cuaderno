<?php
  /** 
   * @var array $recommendation 
   */
?>
<div class="flex-column gap15 p20 br20 border-item-panel back-body shadow-soft w100" style="border-left: 5px solid var(--primary-color, #dc2626);">
  <div class="flex-row center-between wrap gap10">
    <div class="flex-row center-start gap10">
      <div class="br100 p10 back-modal-item flex-row center-center text-primary" style="flex-shrink: 0;">
        <?= svg("clock", "x20") ?>
      </div>
      <div>
        <h4 class="bold700 x20 flex-row center-start gap8 textb">Recomendación de Horario para Publicar</h4>
        <p class="text-muted">Optimizado según la actividad real de tus visitantes</p>
      </div>
    </div>
    <span class="p6 pl12 pr12 br50 back-primary textw bold700 flex-row center-center gap5 shadow-soft">
      <?= svg("lightbulb", "x14") ?> Horario Sugerido
    </span>
  </div>

  <div class="grid col-desk-3 col-mid-3 col-sml-1 gap15 w100 mt5">
    <!-- Mejor Día -->
    <div class="flex-column p15 br12 border-item-panel gap5 back-modal-item">
      <span class="text-muted bold500 flex-row center-start gap5">
        <?= svg("calendar", "x14") ?> Mejor Día de la Semana
      </span>
      <span class="x20 bold700 text-primary"><?= e($recommendation['bestDay'] ?? 'Lunes') ?></span>
      <span class="text-muted"><?= e($recommendation['bestDayTotal'] ?? 0) ?> visitas ese día</span>
    </div>

    <!-- Mejor Horario -->
    <div class="flex-column p15 br12 border-item-panel gap5 back-modal-item">
      <span class="text-muted bold500 flex-row center-start gap5">
        <?= svg("clock", "x14") ?> Franja Horaria Pico
      </span>
      <span class="x20 bold700 text-primary"><?= e($recommendation['bestHour'] ?? '18:00 - 19:00') ?></span>
      <span class="text-muted"><?= e($recommendation['bestHourTotal'] ?? 0) ?> visitas en esa franja</span>
    </div>

    <!-- Consejo de Impacto -->
    <div class="flex-column p15 br12 border-item-panel gap5 back-modal-item">
      <span class="text-muted bold500 flex-row center-start gap5">
        <?= svg("lightbulb", "x14") ?> Consejo de Impacto
      </span>
      <p class="bold500 textb leading-normal">
        Tus seguidores están más activos los <strong class="text-primary"><?= e($recommendation['bestDay'] ?? 'Lunes') ?></strong> entre las <strong class="text-primary"><?= e($recommendation['bestHour'] ?? '18:00 - 19:00') ?> hrs</strong>.
      </p>
    </div>
  </div>
</div>
