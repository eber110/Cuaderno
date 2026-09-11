<?php
  /** 
   * @var mixed $card 
   * @var int $dataContent
   */
  $sepData   = $card["content"][$dataContent] ?? [];
  $sepIcon   = $sepData["separator_icon"] ?? "none";
  $sepMode   = $sepData["separator_mode"] ?? (($sepIcon === "none" || $sepIcon === "ban") ? "space" : "line");
  $sepSize   = $sepData["separator_size"] ?? "large";

  $isSpace = ($sepMode === "space" || $sepIcon === "none" || $sepIcon === "ban");
  $iconSvg = !$isSpace ? \App\Models\DesignModels::renderSeparatorSvg($sepIcon, ($sepSize === "small" ? "x18" : "x16")) : "";
?>

<?php if ($isSpace) : ?>
  <div class="w100" style="height: 10px;" aria-hidden="true"></div>
<?php else : ?>
  <div class="w100 flex-row center-center color-text-card p0" style="margin: 10px 0; box-sizing: border-box; user-select: none;" aria-hidden="true">
    <?php if ($sepSize === "small") : ?>
      <span class="flex-row center-center" style="width: 18px; height: 18px; font-size: 18px; flex-shrink: 0; line-height: 1;">
        <?= $iconSvg ?>
      </span>
    <?php else : ?>
      <?php $widthPercent = ($sepSize === "medium") ? "60%" : "100%"; ?>
      <div style="display: flex; flex-wrap: wrap; justify-content: center; align-content: flex-start; align-items: center; gap: 8px; height: 18px; overflow: hidden; width: <?= $widthPercent ?>; max-width: <?= $widthPercent ?>;">
        <?php for ($k = 0; $k < 35; $k++) : ?>
          <span class="flex-row center-center" style="width: 16px; height: 16px; font-size: 16px; flex-shrink: 0; line-height: 1;">
            <?= $iconSvg ?>
          </span>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
