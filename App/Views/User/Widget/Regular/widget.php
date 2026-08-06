<div class="flex-column center-center gap15 p30 p-sml-20">

  <?php 
    /** @var mixed $card */
    $content   = is_array($card["content"] ?? null) ? $card["content"] : [];
    $cardStyle = $card["style"] ?? "buttonRegular";

    for ($i = 0; $i < count($content); $i++) {
      $itemType   = $content[$i]["type"] ?? '';
      $itemActive = $content[$i]["active"] ?? false;
      if ($itemType === "link" || $itemType === "product") {
        if ($itemActive === true || $itemActive === "true" || $itemActive === 1 || $itemActive === "1") {
          _part("User." . $cardStyle, ["dataContent" => $i]);
        }
      }
    }
  ?>

</div>