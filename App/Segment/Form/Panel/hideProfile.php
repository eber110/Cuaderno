<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   */
  $isHide = !empty($card["hide"]);
?>
<form class="auto-submit" action="<?= $uri["formDesign"]?>" method="post">
  <input type="hidden" name="hide_form_submitted" value="1">
  <input type="checkbox" name="hide" value="true" data-option="true,false" active="<?= $isHide ? '1' : '2' ?>" <?= $isHide ? 'checked' : '' ?> class="checkbox-switch process-auto-submit hidden">
  <input type="submit" value="guardar" class="hidden">
</form>
