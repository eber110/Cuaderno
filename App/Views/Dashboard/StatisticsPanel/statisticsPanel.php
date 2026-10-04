<?php
  /**
   * @var array  $stats        Estructura completa de analíticas del usuario
   * @var array  $monthlyViews Métricas y evaluación comparativa del mes actual vs promedio anterior
   * @var array  $cto          Métricas de CTO, promedio de enlaces/visitas e índice Enlaces vs RRSS
   * @var bool   $isPremium    Indicador de si el creador cuenta con suscripción activa
   * @var array  $card         Datos de diseño y perfil del usuario
   * @var string $user         Nombre de usuario
   * @var array  $uri          Rutas de navegación y guardado
   */
  $userProfile = !empty($user) ? $user : ($card["profile"] ?? "user");
?>

<div class="flex-column gap20 w100 texto">

  <!-- Cabecera general del panel de estadísticas -->
  <div class="flex-row center-between wrap gap10 w100 mb10">
    <div class="flex-column gap5">
      <h2 class="m0 x24 bold700 texto flex-row center-start gap10">
        <?= svg("chart", "x24"); ?>
        Estadísticas
      </h2>
      <p class="m0 x14 color-secondary">Métricas de rendimiento y análisis de audiencia de tu perfil.</p>
    </div>
  </div>

  <?php
    // Sección 1: Visitas del mes actual y evaluación de tendencia con respecto al promedio histórico
    _part("Dashboard.monthlyViews", [
      "monthly" => $monthlyViews ?? ($stats["monthly_views"] ?? [])
    ]);

    // Sección 2: Métrica de CTO (Clics vs Visitas), promedio de enlaces e índice Enlaces vs RRSS
    _part("Dashboard.cto", [
      "cto" => $cto ?? ($stats["cto"] ?? [])
    ]);
  ?>

</div>

