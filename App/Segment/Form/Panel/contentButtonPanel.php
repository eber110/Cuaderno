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
    $templateTypes = array_keys(\App\Models\DesignModels::getBlockTypes());
    foreach ($templateTypes as $tType) {
      $i = "{{INDEX}}";
      $item = ["type" => $tType];
      echo "<template id=\"tpl_block_{$tType}\">";
      include __DIR__ . "/contentItem.php";
      echo "</template>";
    }
  ?>
</div>
