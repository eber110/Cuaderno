<div class="flex-column center-center gap15 p30 p-sml-20">

  <?php 
    /** @var mixed $card */
    
    ($card["content"]) ?? "null";

    for ($i=0; $i < count($card["content"]); $i++) {
      $itemType = $card["content"][$i]["type"] ?? '';
      $itemActive = $card["content"][$i]["active"] ?? false;
      if ($itemType == "link") {
        if ($itemActive == true) {
          # code...
          _part("User.".$card['style']."", ["dataContent" => $i]);
        }
      }

      if ($itemType == "product") {
        if ($itemActive == true) {
          # code...
          _part("User.".$card['style']."", ["dataContent" => $i]);
        }
      }

    }
  ?>

</div>