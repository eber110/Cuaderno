<?php
  /** 
   * @var mixed $card 
   * @var int $dataContent
   */
  $titleData = $card["content"][$dataContent] ?? [];
  $titleText = trim($titleData["title"] ?? "");
  $size   = $titleData["title_size"] ?? "small";
  $weight = $titleData["title_weight"] ?? "500";

  $sizeClass = match ($size) {
    "medium" => "x20",
    "large"  => "x24",
    default  => "x18",
  };

  $weightClass = match ($weight) {
    "600"    => "bold600",
    "700"    => "bold700",
    "900"    => "bold900",
    default  => "bold500",
  };

  $showTitle = ($titleText !== "" && (!isset($isActive) || $isActive));
?>

<div data-content-index="<?= e((string)$dataContent) ?>" data-block-id="<?= e($blockId ?? ($card['content'][$dataContent]['id'] ?? '')) ?>" data-block-type="title" class="title-block-wrapper w100 flex-row center-center text-center p0" style="<?= $showTitle ? '' : 'display: none;' ?>">
  <h2 data-bind="title" data-bind-class="x18|x20|x24:titleSizeClass;bold500|bold600|bold700|bold900:titleWeightClass" class="<?= $sizeClass ?> <?= $weightClass ?> title-color" style="margin: 0;">
    <?= e($titleText) ?>
  </h2>
</div>
