<?php
  /** 
   * @var mixed $card 
   * @var int $dataContent
   */
  $bannerData = $card["content"][$dataContent] ?? [];
  $url        = trim($bannerData["url"] ?? "");
  if ($url !== '' && !preg_match('#^https?://#i', $url) && strpos($url, 'mailto:') !== 0 && strpos($url, 'tel:') !== 0) {
    $url = "https://" . $url;
  }
  $imgSrc     = $bannerData["imgSrc"] ?? "";
  $size       = $bannerData["size"] ?? "720x1024";
  $rawImgShow = $bannerData["imgShow"] ?? true;
  $imgShow    = ($rawImgShow === true || $rawImgShow === 'true' || $rawImgShow === 1 || $rawImgShow === '1');
  $hasImg     = !empty($imgSrc) && strpos($imgSrc, 'no-image.webp') === false;

  $bgColor    = !empty($bannerData["bg_color"]) ? $bannerData["bg_color"] : "#f2e5ff";
  $bgOpacity  = isset($bannerData["bg_opacity"]) ? max(0, min(100, (int)$bannerData["bg_opacity"])) : 100;

  if (!$imgShow || !$hasImg) {
    return;
  }

  $aspectRatio = match ($size) {
    "720x720"   => "720 / 720",
    "1024x720"  => "1024 / 720",
    default     => "720 / 1024",
  };

  $borderCard = ($card["borders"][0] == "br50") ? "br20" : ($card["borders"][0] ?? "br15");
  $shadowCard = $card["shadow"] ?? "shadow-card";
  $profile    = $card["profile"] ?? "";
  $imgOpacity = number_format($bgOpacity / 100, 2, '.', '');
?>

<div data-content-index="<?= $dataContent ?>" class="banner-block-wrapper w100 position-relative <?= $borderCard ?> <?= $shadowCard ?> overflow-hidden" style="aspect-ratio: <?= $aspectRatio ?>; background-color: <?= e($bgColor) ?>;">
  <a href="<?= e($url ?: '#') ?>" <?= !empty($url) ? 'target="_blank" rel="noopener noreferrer"' : '' ?> class="w100 h100 flex-row center-center track-link-click" data-user="<?= e($profile) ?>" data-link-id="<?= e($url) ?>" style="text-decoration: none; display: block; width: 100%; height: 100%; position: relative;">
    <img src="<?= e($imgSrc) ?>" alt="Banner" class="cover w100 h100" style="object-fit: cover; width: 100%; height: 100%; display: block; border: none; opacity: <?= $imgOpacity ?>;" fetchpriority="high">
  </a>
</div>
