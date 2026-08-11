<?php
  /** @var array $viewData */
  /** @var string $variantId */
  /** @var string $userEmail */
  /** @var string $username */
?>

<div class="container container-xl-mid container-sml flex-column gap30 w100 padding20 align-center">

  <div class="text-center max-w600 my20">
    <h1 class="bold800 font32 color-text mb10">Elige tu Plan y Comienza Hoy</h1>
    <p class="color-secondary font16 line-height-mid">
      Obtén acceso inmediato a todas las características exclusivas por un único cobro mensual recurrente sin compromisos a largo plazo.
    </p>
  </div>

  <?php
    _part("Checkout.Pricing.pricingCard", [
      "variantId"   => $variantId ?? "",
      "userEmail"   => $userEmail ?? "",
      "username"    => $username ?? "",
      "userId"      => $userId ?? "",
      "countryCode" => $countryCode ?? "CL",
      "locale"      => $locale ?? "es"
    ]);

  ?>


</div>
