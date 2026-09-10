<?php
  /** 
   * @var mixed $card 
   * @var int $dataContent
   */
  $textData    = $card["content"][$dataContent] ?? [];
  $textContent = trim((string)($textData["text"] ?? ($textData["title"] ?? "")));
  if ($textContent === "") {
    return;
  }

  $weight = $textData["text_weight"] ?? "400";
  $align  = $textData["text_align"] ?? "left";

  $weightClass = ($weight === "500") ? "bold500" : "bold400";

  $alignClass = match ($align) {
    "center" => "text-center",
    "right"  => "text-right",
    default  => "text-left",
  };
?>

<div class="w100 <?= $alignClass ?> p0" style="box-sizing: border-box;">
  <p class="<?= $weightClass ?> color-text-card capitalize-p">
    <?= nl2br(e($textContent)) ?>
  </p>
</div>
