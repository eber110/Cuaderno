<?php
  /**
   * Vista contenedora/enrutadora de estadísticas para retrocompatibilidad.
   *
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
  <?php
    _part("Dashboard.StatisticsPanel.General.general", [
      "stats"        => $stats ?? [],
      "monthlyViews" => $monthlyViews ?? ($stats["monthly_views"] ?? []),
      "isPremium"    => $isPremium ?? false,
      "card"         => $card ?? [],
      "user"         => $userProfile,
      "uri"          => $uri ?? []
    ]);
  ?>
</div>
