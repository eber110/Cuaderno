<main class="color-text-card">
  <div class="absolute top z-index-10 p20 pt30 pb30 pt-sml-20 w100">
    <?php _component("Menu.menuUser");?>
  </div>

  <figure class="absolute top back1 ar-square hpx480 hpx-sml-360 w100 faded-image">
    <img src="<?= DIR_SHOW_MEDIA.'/Custom/porfolio_eber_dark.png'?>" alt="Avatar de <?= $user ?? "Usuario"?>" class="cover image-protected">
  </figure>

  <div class="flex-column bottom-center position-inset-hero hpx550 hpx-sml-480 p20 pt0 pb0">
    <h1 class="x30 bold700">Hola, <?= $user ?? "Usuario";?></h1>
    <p class="p10 w85 w-sml-100 text-c">Lorem ipsum dolor, sit amet consectetur adipisicing elit. Soluta eum ex, architecto atque molestiae delectus exercitationem perferendis asperiores quas voluptas ratione at sit, adipisci, sapiente perspiciatis eos autem deserunt ad?</p>
  </div>
</main>