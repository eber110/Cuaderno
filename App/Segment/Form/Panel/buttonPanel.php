<?php
  /** @var mixed $card */
  $selected = "border-selected-item";
?>
<form class="auto-submit w100" action="/test/1" method="post">

  <div class="flex-column top-between gap20">
    
    <div class="flex-column top-start gap10 w100">
      <p>Estilo de botones</p>

      <div class="flex-row center-end gap10 w100">
        <input type="radio" id="button-solid" name="style" class="hidden-radio" value="Regular" <?php if ($card["style"] == "Regular") echo "checked";?>>
        <label for="button-solid">
          <div class="border8 p5 br20 flex-column center-center gap5 pointer <?php if ($card["style"] == "Regular") echo $selected;?>">
            <div class="button-style-background flex-row center-center">
              <div class="<?= $card["borders"][0]?> button-fill-style"></div>
            </div>
            <p class="x16">Solido</p>
          </div>
        </label>
      </div>
    </div>

    <div class="flex-row center-between">
      <p>Redondeo de esquinas</p>

      <div class="">
        <label for="square" class="flex-row center-start p10 gap10 pointer border8 br15 <?php if ($card["borders"][0] == "br0") echo $selected;?>">
        <input type="radio" id="square" name="borders" value="br0,br0" class="hidden-radio" <?php if ($card["borders"][0] == "br0") echo "checked";?>>
          <p class="x16 bold500 textb">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V4H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
          </p>
        </label>
      </div>

      <div class="">
        <label for="round1" class="flex-row center-start p10 gap10 pointer border8 br15 <?php if ($card["borders"][0] == "br10") echo $selected;?>">
        <input type="radio" id="round1" name="borders" value="br10,br5" class="hidden-radio" <?php if ($card["borders"][0] == "br10") echo "checked";?>>
          <p class="x16 bold500 textb">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V8C4 5.79086 5.79086 4 8 4H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
          </p>
        </label>
      </div>

      <div class="">
        <input type="radio" id="round2" name="borders" value="br20,br12" class="hidden-radio" <?php if ($card["borders"][0] == "br20") echo "checked";?>>
        <label for="round2" class="flex-row center-start p10 gap10 pointer border8 br15 <?php if ($card["borders"][0] == "br20") echo $selected;?>">
          <p class="x16 bold500 textb">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V12C4 7.58172 7.58172 4 12 4H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
          </p>
        </label>
      </div>

      <div class="">
        <input type="radio" id="round3" name="borders" value="br50,br50" class="hidden-radio" <?php if ($card["borders"][0] == "br50") echo "checked";?>>
        <label for="round3" class="flex-row center-start p10 gap10 pointer border8 br15 <?php if ($card["borders"][0] == "br50") echo $selected;?>">
          <p class="x16 bold500 textb">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V12C4 7.58172 7.58172 4 12 4H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
          </p>
        </label>
      </div>
    </div>

  </div>

  <input type="submit" value="guardar" class="hidden">
</form>