<?php
  use Base\Module\GraphicsModule;

  /**
   * @var array|null $monthly Array con datos de visitas del mes actual, meses anteriores, promedio, evaluación de tendencia y gráfico spline.
   * @var array|null $stats   Estructura analítica global opcional
   */
  $statsList     = isset($stats) && is_array($stats) ? $stats : [];
  $monthlyData   = isset($monthly) && is_array($monthly) ? $monthly : (isset($statsList["monthly_views"]) && is_array($statsList["monthly_views"]) ? $statsList["monthly_views"] : []);
  $monthly       = $monthlyData;
  $currentTotal  = (int)($monthly["current_month_total"] ?? 0);
  $monthLabel    = $monthly["current_month_label"] ?? "";
  $hasHistory    = !empty($monthly["has_history"]);
  $previousAvg   = $monthly["previous_months_avg"] ?? 0;
  $previousCount = (int)($monthly["previous_months_count"] ?? 0);
  $previousList  = is_array($monthly["previous_months"] ?? null) ? $monthly["previous_months"] : [];
  $trend         = $monthly["trend"] ?? "equal";
  $arrow         = $monthly["arrow"] ?? null;
  $arrowClass    = $monthly["arrow_class"] ?? "";
  $statusText    = $monthly["status_text"] ?? "";
  $pctChange     = $monthly["pct_change"] ?? 0;
  $spline        = is_array($monthly["spline"] ?? null) ? $monthly["spline"] : [];
  $hasSpline     = !empty($spline["values"]) && count($spline["values"]) > 0;
?>

<div class="back-card-graphic shadow-card-graphic stats-card flex-column gap18 w100">
  
  <!-- Encabezado de la tarjeta -->
  <div class="flex-row center-between wrap gap12 w100">
    <div class="flex-row center-start gap12">
      <span class="stats-icon-box">
        <?= svg("chart", "x18"); ?>
      </span>
      <div class="ui-field-header">
        <p class="m0 bold500 texto">Visitas del mes actual</p>
        <p class="m0 color-secondary">Comparativa frente a tu promedio mensual</p>
      </div>
    </div>

    <?php if (!empty($monthLabel)): ?>
      <span class="stats-pill-badge">
        <?= e($monthLabel); ?>
      </span>
    <?php endif; ?>
  </div>

  <!-- Valor principal con indicador de flecha condicional -->
  <div class="stats-subcard flex-column gap10 w100">
    <div class="flex-row center-start gap12 wrap">
      <span class="x36 x-sml-28 bold500 texto stats-kpi-value"><?= e((string)$currentTotal); ?></span>
      <span class="color-secondary">visitas registradas</span>

      <?php if (!empty($arrow)): ?>
        <span class="flex-row center-center <?= e($arrowClass); ?> x24" title="<?= e($statusText); ?>" aria-label="<?= e($statusText); ?>">
          <?= svg($arrow, $arrowClass); ?>
        </span>
      <?php endif; ?>
    </div>

    <!-- Evaluación comparativa con el promedio histórico de meses anteriores -->
    <div class="flex-column gap6 w100 stats-divider pt10">
      <?php if ($hasHistory): ?>
        <p class="m0 color-secondary">
          Promedio de meses anteriores: <strong class="texto bold500"><?= e((string)$previousAvg); ?></strong> visitas por mes.
        </p>

        <?php if ($trend === "up"): ?>
          <div class="flex-row center-start gap6 color-success bold500">
            <?= svg("arrow-up", "color-success x16"); ?>
            <span>Subió un <?= e((string)$pctChange); ?>% respecto al promedio anterior</span>
          </div>
        <?php elseif ($trend === "down"): ?>
          <div class="flex-row center-start gap6 color-danger bold500">
            <?= svg("arrow-down", "color-danger x16"); ?>
            <span>Bajó un <?= e((string)$pctChange); ?>% respecto al promedio anterior</span>
          </div>
        <?php else: ?>
          <div class="flex-row center-start gap6 color-secondary">
            <span>Se mantuvo igual al promedio de meses anteriores</span>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <p class="m0 color-secondary">
          Este es tu primer mes con registro de visitas. Con el paso de los meses se calculará el promedio para mostrar la tendencia.
        </p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Gráfico evolutivo Hybrid Spline de visitas mensuales -->
  <?php if ($hasSpline): ?>
    <div class="pt18 flex-column gap14 w100 stats-divider">
      <div class="ui-field-header">
        <span class="bold500 texto">
          Evolución de visitas mensuales (<?= e((string)count($spline["values"])); ?> meses)
        </span>
        <span class="color-secondary">
          Historial de meses evaluados frente al mes en curso
        </span>
      </div>

      <!-- Renderizado SVG del gráfico vectorial Hybrid Spline -->
      <div class="stats-chart-box flex-row center-center w100">
        <div class="w100 wpx-max-650 overflow-hidden">
        <?= GraphicsModule::hybridSpline([
            "values"        => $spline["values"],
            "labels"        => $spline["labels"],
            "displayLabels" => $spline["displayLabels"] ?? [],
            "displayValues" => $spline["displayValues"] ?? [],
            "unit"          => $spline["unit"] ?? "Visitas",
            "autoScale"     => $spline["autoScale"] ?? true,
            "axisLabelSize" => 10,
            "barWidth"      => 14,
            "color"         => $spline["color"] ?? "color1",
            "transition"    => $spline["transition"] ?? 450,
            "tooltip"       => true
          ]); 
        ?>
        </div>
      </div>

      <!-- Desglose numérico accesible de los meses anteriores evaluados -->
      <?php if (!empty($previousList)): ?>
        <div class="flex-column gap10 pt6">
          <p class="m0 bold500 texto">Registro de meses anteriores</p>
          <div class="flex-row wrap gap10">
            <?php foreach ($previousList as $prev): ?>
              <div class="stats-chip hover-scale-soft">
                <span class="color-secondary"><?= e((string)($prev["mes"] ?? "")); ?></span>
                <strong class="texto bold500"><?= e((string)($prev["total"] ?? 0)); ?> visitas</strong>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>
