<?php
  /** 
   * @var mixed $card 
   * @var int $dataContent
   */
  $titleData = $card["content"][$dataContent] ?? [];
  $titleText = trim($titleData["title"] ?? "");
  if ($titleText === "") {
    return;
  }

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
?>

<div class="w100 flex-row center-center text-center p0">
  <h2 class="<?= $sizeClass ?> <?= $weightClass ?> title-color" style="margin: 0;">
    <?= e($titleText) ?>
  </h2>
</div>
