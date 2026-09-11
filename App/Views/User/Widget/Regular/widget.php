<div class="flex-column center-center gap15 p30 p-sml-20 w100" style="box-sizing: border-box;">

  <?php 
    /** @var mixed $card */
    $content   = is_array($card["content"] ?? null) ? $card["content"] : [];
    $cardStyle = $card["style"] ?? "buttonRegular";

    for ($i = 0; $i < count($content); $i++) {
      $itemType   = $content[$i]["type"] ?? '';
      $itemActive = $content[$i]["active"] ?? false;
      //si es un link, se muestra esta plantilla
      if ($itemType === "link") {
        if ($itemActive === true || $itemActive === "true" || $itemActive === 1 || $itemActive === "1") {
          _part("User." . $cardStyle, ["dataContent" => $i, "card" => $card]);
        }
      }
      //si es producto se muestra la plantilla producto
      if ($itemType === "product") {
        if ($itemActive === true || $itemActive === "true" || $itemActive === 1 || $itemActive === "1") {
          _part("User." . "productRegular", ["dataContent" => $i, "card" => $card]);
        }
      }
      //si es grupo de productos se muestra la plantilla productGroup (grid o slide)
      if ($itemType === "product_group") {
        if ($itemActive === true || $itemActive === "true" || $itemActive === 1 || $itemActive === "1") {
          _part("User." . "productGroup", ["dataContent" => $i, "card" => $card]);
        }
      }
      //si es campaña de suscripción se muestra la plantilla campaign
      if ($itemType === "campaign") {
        if ($itemActive === true || $itemActive === "true" || $itemActive === 1 || $itemActive === "1") {
          $hasCountdown  = !empty($content[$i]["has_countdown"]);
          $countdownDate = $content[$i]["countdown_date"] ?? "";
          if ($hasCountdown && !empty($countdownDate)) {
            $ts = strtotime($countdownDate);
            if ($ts !== false && $ts <= time()) {
              continue; // Expiró el tiempo límite, se oculta el bloque
            }
          }
          _part("User.campaign", ["dataContent" => $i, "card" => $card]);
        }
      }
      //si es banner se muestra la plantilla banner
      if ($itemType === "banner") {
        if ($itemActive === true || $itemActive === "true" || $itemActive === 1 || $itemActive === "1") {
          _part("User.banner", ["dataContent" => $i, "card" => $card]);
        }
      }
      //si es titulo se muestra la plantilla title
      if ($itemType === "title") {
        if ($itemActive === true || $itemActive === "true" || $itemActive === 1 || $itemActive === "1") {
          _part("User.title", ["dataContent" => $i, "card" => $card]);
        }
      }
      //si es texto se muestra la plantilla text
      if ($itemType === "text") {
        if ($itemActive === true || $itemActive === "true" || $itemActive === 1 || $itemActive === "1") {
          _part("User.text", ["dataContent" => $i, "card" => $card]);
        }
      }
      //si es separador se muestra la plantilla separator
      if ($itemType === "separator") {
        if ($itemActive === true || $itemActive === "true" || $itemActive === 1 || $itemActive === "1") {
          _part("User.separator", ["dataContent" => $i, "card" => $card]);
        }
      }
    }
  ?>

</div>