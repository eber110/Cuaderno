<?php
  /** 
   * @var mixed $card 
   * @var int $dataContent
   */
  $textData    = $card["content"][$dataContent] ?? [];
  $textContent = trim((string)($textData["text"] ?? ($textData["title"] ?? "")));
  $weight = $textData["text_weight"] ?? "400";
  $align  = $textData["text_align"] ?? "left";

  $weightClass = ($weight === "500") ? "bold500" : "bold400";

  $alignClass = match ($align) {
    "center" => "text-center",
    "right"  => "text-right",
    default  => "text-left",
  };

  $showText = ($textContent !== "" && (!isset($isActive) || $isActive));
?>

<div data-content-index="<?= e((string)$dataContent) ?>" data-block-id="<?= e($blockId ?? ($card['content'][$dataContent]['id'] ?? '')) ?>" data-block-type="text" data-bind-class="text-left|text-center|text-right:textAlignClass" class="text-block-wrapper w100 <?= $alignClass ?> p0" style="box-sizing: border-box;<?= $showText ? '' : ' display: none;' ?>">
  <p data-bind="text" data-bind-nl2br="true" data-bind-class="bold400|bold500:textWeightClass" class="<?= $weightClass ?> color-text-card capitalize-p">
    <?= nl2br(e($textContent)) ?>
  </p>
</div>
