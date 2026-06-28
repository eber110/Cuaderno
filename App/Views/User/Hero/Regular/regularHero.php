<main class="color-text-card">
  <?php _component("Menu.menuUser");?>
  <div class="hem15 flex-column center-center">
    
    <div class="flex-column center-center hpx120">
      <figure class="hpx100 wpx100 br100 back1">
        <img src="<?= DIR_SHOW_MEDIA.'/Custom/porfolio_eber_dark.png'?>" alt="Avatar de <?= $user ?? "Usuario"?>" class="cover image-protected">
      </figure>
    </div>
    <h1 class="x30 bold500">Hola, <?= $user ?? "Usuario";?></h1>
    <p class="p10 w85 w-sml-80 text-c">Lorem ipsum dolor, sit amet consectetur adipisicing elit. Soluta eum ex, architecto atque molestiae delectus exercitationem perferendis asperiores quas voluptas ratione at sit, adipisci, sapiente perspiciatis eos autem deserunt ad?</p>

  </div>
</main>