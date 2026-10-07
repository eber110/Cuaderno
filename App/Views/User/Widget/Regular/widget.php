<div id="preview-widget-container" class="preview-widget-container flex-column center-center gap15 p30 p-sml-20 w100" style="box-sizing: border-box;">

  <?php 
    /** 
     * @var mixed $card 
     * @var bool|null $isPreview
     */
    $content   = is_array($card["content"] ?? null) ? $card["content"] : [];
    $allowedStyles = ["buttonRegular"];
    $rawStyle  = $card["style"] ?? "buttonRegular";
    $cardStyle = in_array($rawStyle, $allowedStyles, true) ? $rawStyle : "buttonRegular";
    $isPreview = !empty($isPreview) || !empty($card["isPreview"]);

    for ($i = 0; $i < count($content); $i++) {
      $itemType   = $content[$i]["type"] ?? '';
      $itemActive = $content[$i]["active"] ?? false;
      $isActive   = ($itemActive === true || $itemActive === "true" || $itemActive === 1 || $itemActive === "1");

      // En el perfil público de visitantes, no renderizar elementos inactivos
      if (!$isActive && !$isPreview) {
        continue;
      }

      // Expiración de cuenta regresiva para campañas (solo en perfil público, nunca en vista previa)
      if ($itemType === "campaign" && !$isPreview) {
        $hasCountdown  = !empty($content[$i]["has_countdown"]);
        $countdownDate = $content[$i]["countdown_date"] ?? "";
        if ($hasCountdown && !empty($countdownDate)) {
          $ts = strtotime($countdownDate);
          if ($ts !== false && $ts <= time()) {
            continue; // Expiró el tiempo límite en el perfil público
          }
        }
      }

      $partName = match ($itemType) {
        "link"          => "User." . $cardStyle,
        "product"       => "User.productRegular",
        "product_group" => "User.productGroup",
        "campaign"      => "User.campaign",
        "banner"        => "User.banner",
        "title"         => "User.title",
        "text"          => "User.text",
        "separator"     => "User.separator",
        "video"         => "User.video",
        default         => null,
      };

      if ($partName) {
        _part($partName, [
          "dataContent" => $i,
          "blockId"     => $content[$i]["id"] ?? "",
          "card"        => $card,
          "isActive"    => $isActive,
          "isPreview"   => $isPreview
        ]);
      }
    }
  ?>

</div>