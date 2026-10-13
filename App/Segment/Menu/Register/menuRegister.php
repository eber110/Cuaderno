<?php
use App\Models\BannerTextModels;

$bannerText = BannerTextModels::getRotatingText(6);
?>
<nav class="w100 sticky top2 flex-column center-center before">
  <div class="wpx1000 w-mid-100 w-sml-100 p20 pl30 pr30 br-desk-100 br-mid-100 br-sml-15 back-body border1 shadow-menu flex-row flex-column-sml gap10">
    <a href="/" class="w40 texto flex-row center-center gap10"><?= svg("arrow-l-l");?> Volver</a>
    <p class="x20"><?= e($bannerText);?></p>
  </div>
</nav>