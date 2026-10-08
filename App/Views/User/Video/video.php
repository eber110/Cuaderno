<?php
  /** 
   * Vista de bloque Enlace de Video (User.video).
   * 
   * Modo Enlace: Presenta un banner con la carátula del video en formato 16:9,
   * un ícono de reproducción en el centro y una franja inferior para la descripción.
   * 
   * Modo Reproductor: Presenta exclusivamente el reproductor interactivo (YouTube, Vimeo o HTML5 video)
   * ocupando el tamaño del video sin descripción inferior.
   *
   * @var mixed $card Arreglo de configuración del diseño del usuario.
   * @var int $dataContent Índice del bloque en $card["content"].
   * @var bool|null $isActive Si el bloque se encuentra activo.
   * @var bool|null $isPreview Si se está renderizando dentro de la vista previa del panel.
   */
  $videoData   = $card["content"][$dataContent] ?? [];
  $url         = trim($videoData["url"] ?? "");
  if ($url !== "" && !preg_match('#^https?://#i', $url)) {
    $url = "https://" . $url;
  }
  $title       = trim($videoData["title"] ?? "");
  $videoMode   = $videoData["video_mode"] ?? "link";
  $imgSrc      = $videoData["imgSrc"] ?? "";
  $rawImgShow  = $videoData["imgShow"] ?? true;
  $imgShow     = ($rawImgShow === true || $rawImgShow === "true" || $rawImgShow === 1 || $rawImgShow === "1");
  $hasImg      = !empty($imgSrc) && strpos($imgSrc, "no-image.webp") === false;

  $borderCard  = ($card["borders"][0] == "br50") ? "br20" : ($card["borders"][0] ?? "br15");
  $shadowCard  = $card["shadow"] ?? "shadow-card";
  $profile     = $card["profile"] ?? "";
  $isPreview   = !empty($isPreview) || !empty($card["isPreview"]);

  // Detección de proveedor de video
  $youtubeId = \App\Models\DesignModels::extractYouTubeId($url);
  $vimeoMatch = [];
  $isVimeo = preg_match('#vimeo\.com/(?:video/)?([0-9]+)#i', $url, $vimeoMatch);
  $vimeoId = $isVimeo ? ($vimeoMatch[1] ?? "") : "";
  $isDirectVideo = preg_match('#\.(mp4|webm|ogg)(?:\?.*)?$#i', $url);

  $thumbnailUrl = ($hasImg && $imgShow) ? $imgSrc : "";
  if (empty($thumbnailUrl) && !empty($youtubeId)) {
    $thumbnailUrl = "https://i.ytimg.com/vi/" . $youtubeId . "/hqdefault.jpg";
  } elseif (empty($thumbnailUrl)) {
    $thumbnailUrl = DIR_UPLOAD_MEDIA_STATIC . "Custom/no-image.webp";
  }

  $showBlock = (!isset($isActive) || $isActive);
?>

<style>
  .video-facade { cursor: pointer; background-color: #000000; }
  .video-facade img { transition: transform 0.3s ease, filter 0.2s ease; }
  .video-facade:hover img { transform: scale(1.02); filter: brightness(1) !important; }
  .video-facade:hover .video-play-button { transform: scale(1.12); background: rgba(230, 33, 23, 0.95) !important; }
</style>

<?php if ($isPreview) : ?>
  <!-- ==================== VISTA PREVIA (EDITOR): AMBOS MODOS CONMUTABLES EN VIVO ==================== -->

  <!-- MODO REPRODUCTOR: Fachada diferida que solo inserta el iframe de YouTube al hacer clic -->
  <div data-content-index="<?= (int)$dataContent ?>" data-block-id="<?= e($blockId ?? ($card['content'][$dataContent]['id'] ?? '')) ?>" data-video-variant="player" class="video-block-wrapper video-player-mode w100 position-relative <?= e($borderCard) ?> <?= e($shadowCard) ?> overflow-hidden <?= ($videoMode === 'player') ? '' : 'hidden' ?>" style="aspect-ratio: 16 / 9; background-color: #000000;<?= ($showBlock && $videoMode === 'player') ? '' : ' display: none;' ?>">
    <?php if (!empty($youtubeId)) : ?>
      <div class="video-facade w100 h100 position-relative pointer overflow-hidden flex-row center-center"
           data-embed-url="https://www.youtube-nocookie.com/embed/<?= e($youtubeId) ?>?rel=0&autoplay=1"
           data-title="<?= e($title ?: "Video de YouTube") ?>"
           role="button"
           tabindex="0"
           aria-label="Reproducir video: <?= e($title ?: "Video de YouTube") ?>"
           onclick="var f=this;var u=f.getAttribute('data-embed-url');if(!u)return;var ifr=document.createElement('iframe');ifr.className='w100 h100';ifr.style.cssText='border:0;width:100%;height:100%;display:block;';ifr.src=u;ifr.title=f.getAttribute('data-title')||'Video';ifr.frameBorder='0';ifr.allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';ifr.allowFullscreen=true;f.replaceWith(ifr);">
        <img src="<?= eUrl($thumbnailUrl) ?>" alt="<?= e($title ?: "Video de YouTube") ?>" class="cover w100 h100" loading="lazy" style="object-fit: cover; width: 100%; height: 100%; display: block; filter: brightness(0.9); transition: filter 0.2s ease, transform 0.3s ease;" onerror="this.src='<?= eUrl(URL_IMG . 'no-image.webp') ?>';">
        <div class="video-play-overlay flex-row center-center" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
          <div class="video-play-button flex-row center-center br50" style="width: 58px; height: 58px; background: rgba(0, 0, 0, 0.72); backdrop-filter: blur(4px); box-shadow: 0 4px 18px rgba(0, 0, 0, 0.45); transition: transform 0.2s ease, background-color 0.2s ease;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="#ffffff" style="margin-left: 2px;" aria-hidden="true">
              <path d="M8 5v14l11-7z"/>
            </svg>
          </div>
        </div>
      </div>
    <?php elseif (!empty($vimeoId)) : ?>
      <div class="video-facade w100 h100 position-relative pointer overflow-hidden flex-row center-center"
           data-embed-url="https://player.vimeo.com/video/<?= e($vimeoId) ?>?autoplay=1"
           data-title="<?= e($title ?: "Video de Vimeo") ?>"
           role="button"
           tabindex="0"
           aria-label="Reproducir video: <?= e($title ?: "Video de Vimeo") ?>"
           onclick="var f=this;var u=f.getAttribute('data-embed-url');if(!u)return;var ifr=document.createElement('iframe');ifr.className='w100 h100';ifr.style.cssText='border:0;width:100%;height:100%;display:block;';ifr.src=u;ifr.title=f.getAttribute('data-title')||'Video';ifr.frameBorder='0';ifr.allow='autoplay; fullscreen; picture-in-picture';ifr.allowFullscreen=true;f.replaceWith(ifr);">
        <img src="<?= eUrl($thumbnailUrl) ?>" alt="<?= e($title ?: "Video de Vimeo") ?>" class="cover w100 h100" loading="lazy" style="object-fit: cover; width: 100%; height: 100%; display: block; filter: brightness(0.9); transition: filter 0.2s ease, transform 0.3s ease;" onerror="this.src='<?= eUrl(URL_IMG . 'no-image.webp') ?>';">
        <div class="video-play-overlay flex-row center-center" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
          <div class="video-play-button flex-row center-center br50" style="width: 58px; height: 58px; background: rgba(0, 0, 0, 0.72); backdrop-filter: blur(4px); box-shadow: 0 4px 18px rgba(0, 0, 0, 0.45); transition: transform 0.2s ease, background-color 0.2s ease;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="#ffffff" style="margin-left: 2px;" aria-hidden="true">
              <path d="M8 5v14l11-7z"/>
            </svg>
          </div>
        </div>
      </div>
    <?php elseif ($isDirectVideo) : ?>
      <video class="w100 h100" controls preload="metadata" <?= ($hasImg && $imgShow) ? 'poster="' . eUrl($imgSrc) . '"' : "" ?> style="object-fit: contain; width: 100%; height: 100%; display: block; background: #000;">
        <source src="<?= eUrl($url) ?>">
        Tu navegador no soporta el reproductor de video.
      </video>
    <?php else : ?>
      <a href="<?= eUrl($url ?: "#") ?>" target="_blank" rel="noopener noreferrer" class="w100 h100 flex-column center-center textc track-link-click" data-user="<?= e($profile) ?>" data-link-id="<?= e($url) ?>" style="text-decoration: none; color: #ffffff; background: #111111; padding: 20px; box-sizing: border-box;">
        <span class="x32 mb10" aria-hidden="true">&#9658;</span>
        <p class="x14 bold500"><?= e($title ?: "Reproducir video") ?></p>
        <span class="x12 text-muted mt5"><?= e($url) ?></span>
      </a>
    <?php endif; ?>
  </div>

  <!-- MODO ENLACE: Banner con carátula (16:9), ícono de reproducción y espacio inferior para la descripción -->
  <div data-content-index="<?= (int)$dataContent ?>" data-block-id="<?= e($blockId ?? ($card['content'][$dataContent]['id'] ?? '')) ?>" data-video-variant="link" class="video-block-wrapper video-link-mode w100 position-relative <?= e($borderCard) ?> <?= e($shadowCard) ?> overflow-hidden theme-button pointer <?= ($videoMode !== 'player') ? '' : 'hidden' ?>" style="<?= ($showBlock && $videoMode !== 'player') ? '' : ' display: none;' ?> padding: 0; text-decoration: none;">
    <a href="<?= eUrl($url ?: "#") ?>" target="_blank" rel="noopener noreferrer" class="w100 flex-column track-link-click" data-user="<?= e($profile) ?>" data-link-id="<?= e($url) ?>" style="text-decoration: none; color: inherit; display: flex; width: 100%;">
      
      <!-- Carátula del video (16:9) con botón de reproducción superpuesto -->
      <div class="w100 position-relative overflow-hidden" style="aspect-ratio: 16 / 9; background-color: #1a1a1a;">
        <img src="<?= eUrl(($hasImg && $imgShow) ? $imgSrc : DIR_UPLOAD_MEDIA_STATIC . "Custom/no-image.webp") ?>" alt="<?= e($title ?: "Video") ?>" class="cover w100 h100" style="object-fit: cover; display: block; width: 100%; height: 100%; border: none;" fetchpriority="high">

        <!-- Overlay con ícono de play en el centro -->
        <div class="video-play-overlay flex-row center-center" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
          <div class="video-play-button flex-row center-center br50" style="width: 54px; height: 54px; background: rgba(0, 0, 0, 0.65); backdrop-filter: blur(4px); box-shadow: 0 4px 18px rgba(0, 0, 0, 0.45); transition: transform 0.2s ease, background-color 0.2s ease;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#ffffff" style="margin-left: 2px;" aria-hidden="true">
              <path d="M8 5v14l11-7z"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Espacio para la descripción en la franja inferior del banner -->
      <?php if (!empty($title)) : ?>
        <div class="video-desc-wrap p12 pl15 pr15 w100 flex-row center-start" style="box-sizing: border-box;">
          <p class="bold500 cut-phrase w100 text-l" cant-col="2" style="margin: 0; color: inherit;"><?= e($title) ?></p>
        </div>
      <?php endif; ?>

    </a>
  </div>

<?php else : ?>
  <!-- ==================== PERFIL PÚBLICO (PRODUCCIÓN): SOLO EL MODO SELECCIONADO ==================== -->
  <?php if ($videoMode === "player") : ?>
    <!-- MODO REPRODUCTOR: Fachada diferida que solo inserta el iframe de YouTube al hacer clic -->
    <div data-content-index="<?= (int)$dataContent ?>" data-block-id="<?= e($blockId ?? ($card['content'][$dataContent]['id'] ?? '')) ?>" class="video-block-wrapper video-player-mode w100 position-relative <?= e($borderCard) ?> <?= e($shadowCard) ?> overflow-hidden" style="aspect-ratio: 16 / 9; background-color: #000000;<?= $showBlock ? "" : " display: none;" ?>">
      <?php if (!empty($youtubeId)) : ?>
        <div class="video-facade w100 h100 position-relative pointer overflow-hidden flex-row center-center"
             data-embed-url="https://www.youtube-nocookie.com/embed/<?= e($youtubeId) ?>?rel=0&autoplay=1"
             data-title="<?= e($title ?: "Video de YouTube") ?>"
             role="button"
             tabindex="0"
             aria-label="Reproducir video: <?= e($title ?: "Video de YouTube") ?>"
             onclick="var f=this;var u=f.getAttribute('data-embed-url');if(!u)return;var ifr=document.createElement('iframe');ifr.className='w100 h100';ifr.style.cssText='border:0;width:100%;height:100%;display:block;';ifr.src=u;ifr.title=f.getAttribute('data-title')||'Video';ifr.frameBorder='0';ifr.allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';ifr.allowFullscreen=true;f.replaceWith(ifr);">
          <img src="<?= eUrl($thumbnailUrl) ?>" alt="<?= e($title ?: "Video de YouTube") ?>" class="cover w100 h100" loading="lazy" style="object-fit: cover; width: 100%; height: 100%; display: block; filter: brightness(0.9); transition: filter 0.2s ease, transform 0.3s ease;" onerror="this.src='<?= eUrl(URL_IMG . 'no-image.webp') ?>';">
          <div class="video-play-overlay flex-row center-center" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
            <div class="video-play-button flex-row center-center br50" style="width: 58px; height: 58px; background: rgba(0, 0, 0, 0.72); backdrop-filter: blur(4px); box-shadow: 0 4px 18px rgba(0, 0, 0, 0.45); transition: transform 0.2s ease, background-color 0.2s ease;">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="#ffffff" style="margin-left: 2px;" aria-hidden="true">
                <path d="M8 5v14l11-7z"/>
              </svg>
            </div>
          </div>
        </div>
      <?php elseif (!empty($vimeoId)) : ?>
        <div class="video-facade w100 h100 position-relative pointer overflow-hidden flex-row center-center"
             data-embed-url="https://player.vimeo.com/video/<?= e($vimeoId) ?>?autoplay=1"
             data-title="<?= e($title ?: "Video de Vimeo") ?>"
             role="button"
             tabindex="0"
             aria-label="Reproducir video: <?= e($title ?: "Video de Vimeo") ?>"
             onclick="var f=this;var u=f.getAttribute('data-embed-url');if(!u)return;var ifr=document.createElement('iframe');ifr.className='w100 h100';ifr.style.cssText='border:0;width:100%;height:100%;display:block;';ifr.src=u;ifr.title=f.getAttribute('data-title')||'Video';ifr.frameBorder='0';ifr.allow='autoplay; fullscreen; picture-in-picture';ifr.allowFullscreen=true;f.replaceWith(ifr);">
          <img src="<?= eUrl($thumbnailUrl) ?>" alt="<?= e($title ?: "Video de Vimeo") ?>" class="cover w100 h100" loading="lazy" style="object-fit: cover; width: 100%; height: 100%; display: block; filter: brightness(0.9); transition: filter 0.2s ease, transform 0.3s ease;" onerror="this.src='<?= eUrl(URL_IMG . 'no-image.webp') ?>';">
          <div class="video-play-overlay flex-row center-center" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
            <div class="video-play-button flex-row center-center br50" style="width: 58px; height: 58px; background: rgba(0, 0, 0, 0.72); backdrop-filter: blur(4px); box-shadow: 0 4px 18px rgba(0, 0, 0, 0.45); transition: transform 0.2s ease, background-color 0.2s ease;">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="#ffffff" style="margin-left: 2px;" aria-hidden="true">
                <path d="M8 5v14l11-7z"/>
              </svg>
            </div>
          </div>
        </div>
      <?php elseif ($isDirectVideo) : ?>
        <video class="w100 h100" controls preload="metadata" <?= ($hasImg && $imgShow) ? 'poster="' . eUrl($imgSrc) . '"' : "" ?> style="object-fit: contain; width: 100%; height: 100%; display: block; background: #000;">
          <source src="<?= eUrl($url) ?>">
          Tu navegador no soporta el reproductor de video.
        </video>
      <?php else : ?>
        <a href="<?= eUrl($url ?: "#") ?>" target="_blank" rel="noopener noreferrer" class="w100 h100 flex-column center-center textc track-link-click" data-user="<?= e($profile) ?>" data-link-id="<?= e($url) ?>" style="text-decoration: none; color: #ffffff; background: #111111; padding: 20px; box-sizing: border-box;">
          <span class="x32 mb10" aria-hidden="true">&#9658;</span>
          <p class="x14 bold500"><?= e($title ?: "Reproducir video") ?></p>
          <span class="x12 text-muted mt5"><?= e($url) ?></span>
        </a>
      <?php endif; ?>
    </div>
  <?php else : ?>
    <!-- MODO ENLACE: Banner con carátula (16:9), ícono de reproducción y espacio inferior para la descripción -->
    <div data-content-index="<?= (int)$dataContent ?>" data-block-id="<?= e($blockId ?? ($card['content'][$dataContent]['id'] ?? '')) ?>" class="video-block-wrapper video-link-mode w100 position-relative <?= e($borderCard) ?> <?= e($shadowCard) ?> overflow-hidden theme-button pointer" style="<?= $showBlock ? "" : " display: none;" ?> padding: 0; text-decoration: none;">
      <a href="<?= eUrl($url ?: "#") ?>" target="_blank" rel="noopener noreferrer" class="w100 flex-column track-link-click" data-user="<?= e($profile) ?>" data-link-id="<?= e($url) ?>" style="text-decoration: none; color: inherit; display: flex; width: 100%;">
        <div class="w100 position-relative overflow-hidden" style="aspect-ratio: 16 / 9; background-color: #1a1a1a;">
          <img src="<?= eUrl(($hasImg && $imgShow) ? $imgSrc : DIR_UPLOAD_MEDIA_STATIC . "Custom/no-image.webp") ?>" alt="<?= e($title ?: "Video") ?>" class="cover w100 h100" style="object-fit: cover; display: block; width: 100%; height: 100%; border: none;" fetchpriority="high">
          <div class="video-play-overlay flex-row center-center" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
            <div class="video-play-button flex-row center-center br50" style="width: 54px; height: 54px; background: rgba(0, 0, 0, 0.65); backdrop-filter: blur(4px); box-shadow: 0 4px 18px rgba(0, 0, 0, 0.45); transition: transform 0.2s ease, background-color 0.2s ease;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="#ffffff" style="margin-left: 2px;" aria-hidden="true">
                <path d="M8 5v14l11-7z"/>
              </svg>
            </div>
          </div>
        </div>
        <?php if (!empty($title)) : ?>
          <div class="video-desc-wrap p12 pl15 pr15 w100 flex-row center-start" style="box-sizing: border-box;">
            <p class="bold500 cut-phrase w100 text-l" cant-col="2" style="margin: 0; color: inherit;"><?= e($title) ?></p>
          </div>
        <?php endif; ?>
      </a>
    </div>
  <?php endif; ?>
<?php endif; ?>
