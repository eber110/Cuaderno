<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   * @var mixed $prodCount
   */
  $selected = "";
  $cant = is_array($card["content"] ?? null) ? count($card["content"]) : 0;
  $separatorIcons = \App\Models\DesignModels::getSeparatorIcons();
?>
<form class="auto-submit w100" action="<?= $uri["formDesign"]?>" method="post" enctype="multipart/form-data">
  <?= class_exists('\Base\Module\SecurityModule') ? \Base\Module\SecurityModule::csrfField() : '' ?>

  <div class="flex-column top-center gap20">

    <!-- Botones Iniciadores para Añadir Contenido -->
    <div class="flex-column top-start gap10 w100">
      <p class="bold500 x16 texto">Añadir nuevo elemento</p>
      <div class="flex-row center-start gap10 w100 wrap">
        <?php foreach (\App\Models\DesignModels::getBlockTypes() as $type => $config): ?>
          <button type="button" data-action="add-block-template" data-type="<?= $type ?>" class="p10 pl15 pr15 br20 back-card-graphic shadow-card-graphic hover-scale-soft pointer flex-row center-center gap5 bold500 texto" style="border: none;">
            <?= svg($config["icon"]) ?> <?= $config["name"] ?>
          </button>
        <?php endforeach; ?>
</div>
    </div>

    <!-- Lista de elementos existentes (Sortable Drag & Drop) -->
    <div id="sortable-content-list" class="flex-column gap20 w100">
      <?php for ($i=0; $i < $cant; $i++) {
        $item = $card["content"][$i];
        include __DIR__ . "/contentItem.php";
      } ?>
    </div>
    <input type="submit" value="Guardar" class="hidden">
  </div>
</form>
<!-- TEMPLATES PARA FASE 3 -->
<div id="editor-templates" style="display:none;">
  <?php
    $blockTypesCatalog = \App\Models\DesignModels::getBlockTypes();
    $templateTypes = array_keys($blockTypesCatalog);
    $allowedStyles = ["buttonRegular"];
    $rawStyle  = $card["style"] ?? "buttonRegular";
    $cardStyle = in_array($rawStyle, $allowedStyles, true) ? $rawStyle : "buttonRegular";

    foreach ($templateTypes as $tType) {
      $i = "{{INDEX}}";

      $defaultSubProducts = [
        [
          "img"        => "no-image.webp",
          "imgSrc"     => DIR_UPLOAD_MEDIA_STATIC . "Custom/no-image.webp",
          "title"      => "",
          "url"        => "",
          "price"      => "",
          "offer"      => false,
          "discount"   => "",
          "porcentage" => 0,
          "imgDefault" => false,
          "imgShow"    => true,
          "metaTitle"  => "",
          "metaDesc"   => "",
          "metaImg"    => ""
        ],
        [
          "img"        => "no-image.webp",
          "imgSrc"     => DIR_UPLOAD_MEDIA_STATIC . "Custom/no-image.webp",
          "title"      => "",
          "url"        => "",
          "price"      => "",
          "offer"      => false,
          "discount"   => "",
          "porcentage" => 0,
          "imgDefault" => false,
          "imgShow"    => true,
          "metaTitle"  => "",
          "metaDesc"   => "",
          "metaImg"    => ""
        ]
      ];

      $item = [
        "id"                    => "{{BLOCK_ID}}",
        "type"                  => $tType,
        "title"                 => "",
        "url"                   => "",
        "img"                   => "no-image.webp",
        "imgSrc"                => DIR_UPLOAD_MEDIA_STATIC . "Custom/no-image.webp",
        "imgDefault"            => false,
        "imgShow"               => true,
        "active"                => ($tType === "separator"),
        "metaTitle"             => "",
        "metaDesc"              => "",
        "metaImg"               => "",
        "price"                 => "",
        "offer"                 => false,
        "discount"              => "",
        "porcentage"            => 0,
        "desc"                  => "",
        "button_text"           => "Suscribirme",
        "size"                  => ($tType === "campaign") ? "horizontal" : "",
        "img_position"          => "background",
        "bg_color"              => ($tType === "banner") ? "#f2e5ff" : "#1e1e1e",
        "bg_opacity"            => ($tType === "banner") ? 100 : 80,
        "text_position"         => "center",
        "text_align"            => ($tType === "text") ? "left" : "center",
        "title_size"            => ($tType === "title") ? "small" : "large",
        "title_weight"          => "500",
        "text"                  => "",
        "text_weight"           => "400",
        "desc_size"             => "medium",
        "title_color"           => "#ffffff",
        "desc_color"            => "#ffffff",
        "btn_bg_color"          => $card["back"] ?? "#000000",
        "btn_text_color"        => $card["color"] ?? "#ffffff",
        "ask_name"              => true,
        "ask_whatsapp"          => false,
        "has_countdown"         => false,
        "countdown_date"        => "",
        "countdown_bg_color"    => $card["back"] ?? "#000000",
        "countdown_text_color"  => $card["color"] ?? "#ffffff",
        "countdown_text_size"   => "medium",
        "countdown_widget_size" => "medium",
        "video_mode"            => "link",
        "separator_mode"        => "space",
        "separator_icon"        => "none",
        "separator_size"        => "large",
        "space_size"            => "40",
        "layout"                => "grid",
        "products"              => ($tType === "product_group") ? $defaultSubProducts : []
      ];
      
      echo "<template id=\"tpl_block_{$tType}\">";
      include __DIR__ . "/contentItem.php";
      echo "</template>";

      $viewBase = $blockTypesCatalog[$tType]["viewBase"] ?? null;
      $partName = ($tType === "link")
        ? "User." . $cardStyle
        : ($viewBase ? (str_ends_with($viewBase, ".") ? $viewBase . $cardStyle : $viewBase) : null);

      if ($partName) {
         $mockCard = $card;
         $mockCard["content"]["{{INDEX}}"] = $item;
         echo "<template id=\"tpl_preview_{$tType}\">";
         _part($partName, [
            "dataContent" => "{{INDEX}}",
            "card"        => $mockCard,
            "isActive"    => ($tType === "separator"),
            "isPreview"   => true,
            "blockId"     => "{{BLOCK_ID}}"
         ]);
         echo "</template>";
      }
    }
  ?>
</div>
