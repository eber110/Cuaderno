<div class="flex-column center-center gap15 p30 p-sml-20">

  <?php 
    /** @var mixed $card */
    
    ($card["content"]) ?? "null";

    for ($i=0; $i < count($card["content"]); $i++) {
      if ($card["content"][$i][0] == "link") {
        if ($card["content"][$i][4] == true) {
          # code...
          _part("User.".$card['style']."", ["dataContent" => $i]);
        }
      }
    }
  ?>

</div>