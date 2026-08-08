<?php
  /** 
   * @var array $summary
   * @var array $recommendation 
   */
  $monthlyUniques = (int)($summary['unique_views'] ?? 0);
  if ($monthlyUniques < 50) {
    return;
  }

  $hasRecommendation = !empty($recommendation['bestDay']) && !empty($recommendation['bestHour']) && (($recommendation['bestDayTotal'] ?? 0) > 0 || ($recommendation['bestHourTotal'] ?? 0) > 0);
?>
<div class="flex-column gap15 p20 br20 back-card-graphic shadow-card-graphic hover-scale-soft w100">
  <div class="flex-row center-between wrap gap10">
    <div class="flex-row center-start gap10">
      <div class="br100 p10 back-modal-item flex-row center-center text-primary" style="flex-shrink: 0;">
        <?= svg("clock", "x20") ?>
      </div>
      <div>
        <h4 class="bold700 x20 flex-row center-start gap8 texto">Recomendación de Horario para Publicar</h4>
        <p class="text-muted">Optimizado según la actividad real de tus visitantes</p>
      </div>
    </div>
    <span class="texto bold700 flex-row center-center gap6 advice">
      <?= svg("lightbulb", "x16") ?> Horario Sugerido
    </span>
  </div>

  <div class="grid col-desk-3 col-mid-3 col-sml-1 gap15 w100 mt5">
    <!-- Mejor Día -->
    <div class="flex-column p15 br12 btn-card-graphic hover-scale-soft shadow-card-graphic gap5 back-modal-item">
      <span class="text-muted bold600">Mejor Día de la Semana</span>
      <?php if ($hasRecommendation) : ?>
        <span class="x22 bold700 text-primary"><?= e($recommendation['bestDay']) ?></span>
        <span class="text-muted"><?= e($recommendation['bestDayTotal']) ?> visitas ese día</span>
      <?php else : ?>
        <span class="x22 bold700 text-muted">Sin datos</span>
        <span class="text-muted">0 visitas ese día</span>
      <?php endif; ?>
    </div>

    <!-- Mejor Horario -->
    <div class="flex-column p15 br12 btn-card-graphic hover-scale-soft shadow-card-graphic gap5 back-modal-item">
      <span class="text-muted bold600">Franja Horaria Recomendada</span>
      <?php if ($hasRecommendation) : ?>
        <span class="x22 bold700 text-primary"><?= e($recommendation['bestHour']) ?></span>
        <span class="text-muted"><?= e($recommendation['bestHourTotal']) ?> visitas en esa franja</span>
      <?php else : ?>
        <span class="x22 bold700 text-muted">Sin datos</span>
        <span class="text-muted">0 visitas en esa franja</span>
      <?php endif; ?>
    </div>

    <!-- Consejo de Impacto -->
    <div class="flex-column p15 br12 btn-card-graphic hover-scale-soft shadow-card-graphic gap5 back-modal-item">
      <span class="text-muted bold600">Consejo de Impacto</span>
      <p class="bold500 texto leading-normal">
        <?php if ($hasRecommendation) : ?>
          Tus seguidores están más activos los <strong class="text-primary"><?= e($recommendation['bestDay']) ?></strong> entre las <strong class="text-primary"><?= e($recommendation['bestHour']) ?> hrs</strong>.
        <?php else : ?>
          Aún no hay suficiente actividad registrada para recomendar un horario de publicación.
        <?php endif; ?>
      </p>
    </div>
  </div>
</div>
