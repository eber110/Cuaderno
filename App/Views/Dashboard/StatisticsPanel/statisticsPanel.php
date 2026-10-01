<?php
  /**
   * @var array $stats
   * @var array $card
   * @var string $user
   * @var array $uri
   */
  $userProfile = !empty($user) ? $user : ($card["profile"] ?? "user");
?>

<div class="flex-column gap20 w100 texto">
  <div class="p30 br20 back-card shadow-1 flex-column center-center gap15 text-center">
    <div class="x22 bold600">Estadísticas</div>
    <p class="color-secondary">Módulo de estadísticas preparado para la nueva arquitectura y reglas de negocio.</p>
  </div>
</div>
