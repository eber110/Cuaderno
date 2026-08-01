<?php
  /** 
   * @var bool $isSample 
   * @var string $userProfile 
   */
?>
<div class="flex-row center-between wrap gap10 p20 br15 border-item-panel back-body">
  <div>
    <h3 class="bold700 x20 flex-row center-start gap10">
      <?= svg("chart", "x20") ?> Estadísticas y Analíticas
      <?php if ($isSample) : ?>
        <span class="p5 pl10 pr10 br50 back-modal-item text-muted bold600">Vista de Muestra</span>
      <?php endif; ?>
    </h3>
    <p class="text-muted">
      <?= $isSample ? 'Ejemplo demostrativo de cómo se visualizarán las métricas de tu perfil' : 'Monitoreo en tiempo real almacenado en SQLite' ?>
    </p>
  </div>

  <!-- Botón para simular visita/clic de prueba -->
  <form action="/panel/<?= e($userProfile) ?>/simular-datos" method="post">
    <button type="submit" class="p10 pl15 pr15 br20 texto pointer flex-row center-center gap5 bold500 border-none shadow-1">
      <?= svg("add") ?> Simular Visita / Clic
    </button>
  </form>
</div>
