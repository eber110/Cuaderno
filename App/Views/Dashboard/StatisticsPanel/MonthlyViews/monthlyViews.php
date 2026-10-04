<?php
  use Base\Module\GraphicsModule;

  /**
   * @var array $monthly Array con datos de visitas del mes actual, meses anteriores, promedio, evaluación de tendencia y gráfico spline.
   */
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

<div class="p25 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
  
  <!-- Encabezado de la tarjeta -->
  <div class="flex-row center-between wrap gap10 w100">
    <div class="flex-row center-start gap10">
      <span class="flex-row center-center color-secondary x20">
        <?= svg("chart", "x20"); ?>
      </span>
      <p class="m0 bold600 texto">Visitas del mes actual</p>
    </div>

    <?php if (!empty($monthLabel)): ?>
      <span class="back-card-graphic shadow-card-graphic hover-scale-soft p5 pl10 pr10 br10">
        <?= e($monthLabel); ?>
      </span>
    <?php endif; ?>
  </div>

  <!-- Valor principal con indicador de flecha condicional -->
  <div class="flex-row center-start gap12 my5">
    <span class="x45 bold700 texto leading-none"><?= e((string)$currentTotal); ?></span>
    
    <?php if (!empty($arrow)): ?>
      <span class="flex-row center-center <?= e($arrowClass); ?> x28" title="<?= e($statusText); ?>" aria-label="<?= e($statusText); ?>">
        <?= svg($arrow, $arrowClass); ?>
      </span>
    <?php endif; ?>
  </div>

  <!-- Evaluación comparativa con el promedio histórico de meses anteriores -->
  <div class="flex-column gap5 w100">
    <?php if ($hasHistory): ?>
      <p class="m0 color-secondary">
        Promedio de meses anteriores: <strong class="texto bold600"><?= e((string)$previousAvg); ?></strong> visitas / mes.
      </p>

      <?php if ($trend === "up"): ?>
        <div class="flex-row center-start gap5 color-success bold600">
          <?= svg("arrow-up", "color-success"); ?>
          <span>Subió un <?= e((string)$pctChange); ?>% respecto al promedio anterior</span>
        </div>
      <?php elseif ($trend === "down"): ?>
        <div class="flex-row center-start gap5 color-danger bold600">
          <?= svg("arrow-down", "color-danger"); ?>
          <span>Bajó un <?= e((string)$pctChange); ?>% respecto al promedio anterior</span>
        </div>
      <?php else: ?>
        <div class="flex-row center-start gap5 color-secondary bold500">
          <span>Se mantuvo igual al promedio de meses anteriores</span>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <p class="m0 x14 color-secondary">
        Este es tu primer mes con registro de visitas. Con el paso de los meses se calculará el promedio para mostrar la tendencia.
      </p>
    <?php endif; ?>
  </div>

  <!-- Gráfico evolutivo Hybrid Spline de visitas mensuales -->
  <?php if ($hasSpline): ?>
    <div class="pt15 flex-column gap12 w100" style="border-top: 1px solid rgba(128,128,128,0.15);">
      <div class="flex-row center-between wrap gap10 w100">
        <div class="flex-column gap2">
          <span class="bold600 uppercase color-secondary">
            Evolución de visitas mensuales (<?= e((string)count($spline["values"])); ?> meses)
          </span>
          <span class="color-secondary">
            Historial de meses evaluados frente al mes en curso
          </span>
        </div>
      </div>

      <!-- Renderizado SVG del gráfico vectorial Hybrid Spline -->
      <div class="flex-row center-center br15 w100">
        <div class="overflow-hidden wpx600 w-mid-100 w-sml-100">
        <?= GraphicsModule::hybridSpline([
            "values"        => $spline["values"],
            "labels"        => $spline["labels"],
            "displayLabels" => $spline["displayLabels"] ?? [],
            "displayValues" => $spline["displayValues"] ?? [],
            "unit"          => $spline["unit"] ?? "Visitas",
            "autoScale"     => $spline["autoScale"] ?? true,
            "color"         => $spline["color"] ?? "texto",
            "axisLabel"     => $spline["axisLabel"] ?? "color-secondary",
            "transition"    => $spline["transition"] ?? 450,
            "tooltip"       => true
          ]); 
        ?>
        </div>
      </div>

      <!-- Desglose numérico accesible de los meses anteriores evaluados -->
      <?php if (!empty($previousList)): ?>
        <div class="flex-row wrap gap10 pt10">
          <?php foreach ($previousList as $prev): ?>
            <div class="flex-row center-between gap10 back-card-graphic shadow-card-graphic hover-scale-soft p5 pl10 pr10 br10">
              <span class="color-secondary"><?= e((string)($prev["mes"] ?? "")); ?></span>
              <strong class="texto bold600"><?= e((string)($prev["total"] ?? 0)); ?> visitas</strong>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>
