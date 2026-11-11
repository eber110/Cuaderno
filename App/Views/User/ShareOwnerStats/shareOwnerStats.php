<?php
  /**
   * Pie de los modales de compartir.
   * Dueño con sesión: saludo + métricas del enlace/perfil + acceso a configuración.
   * Visitante: invitación a registrarse en Clikhub.
   *
   * @var bool $isOwner
   * @var string $sessionUser
   * @var string $displayName
   * @var string $statsType
   * @var int $statsTotal
   * @var int $statsWeek
   */
  $isOwner     = $isOwner ?? false;
  $sessionUser = $sessionUser ?? "";
  $displayName = $displayName ?? "";
  $statsType   = $statsType ?? "link";
  $statsTotal  = (int)($statsTotal ?? 0);
  $statsWeek   = (int)($statsWeek ?? 0);

  $statsLabel = $statsType === "profile" ? "visitas" : "clicks";
?>
<?php if ($isOwner) : ?>
  <div class="flex-column top-start gap25 gap-sml-15 w100 mt20 mt5">
    <div>
      <p class="bold700">Hola, <span class="capitalize-first"><?= e($sessionUser) ?>.</span></p>
      <p>Impulsa tus números hoy. Mantén tu espacio actualizado y compártelo en tus redes para atraer más tráfico al instante.</p>
    </div>

    <div class="grid col-2 gap15 w100">
      <div class="flex-column top-center gap5 w100 p15 p-sml-10 br20 border-card-modal">
        <p class="x28 bold700 anton"><?= e(nd($statsTotal, 0)) ?></p>
        <p class="x16 line-h11 text-c">Total de <?= e($statsLabel) ?></p>
      </div>
      <div class="flex-column top-center gap5 w100 p15 p-sml-10 br20 border-card-modal">
        <p class="x28 bold700 anton"><?= e(nd($statsWeek, 0)) ?></p>
        <p class="x16 line-h11 text-c">Total de <?= e($statsLabel) ?> esta semana</p>
      </div>
    </div>

    <div class="flex-row center-center gap15 w100">
      <a href="<?= eUrl("/panel/" . rawurlencode($sessionUser)) ?>" class="br50 w100 btn-share-register p15 p-sml-10 flex-row center-center text-c bold700">Configuración</a>
    </div>
  </div>
<?php else : ?>
  <div class="flex-column top-start gap20 gap-sml-10 w100 mt20 mt5">
    <div class="">
      <p class="bold700">Únete a <?= e($displayName) ?> en Clikhub.</p>
      <p>Un solo enlace, todas tus redes. Tu espacio personal gratis para conectar a tu audiencia con todo lo que creas.</p>
    </div>

    <div class="flex-row center-center gap15 w100">
      <a href="/registrar" class="br50 w100 btn-share-register p15 p-sml-10 flex-row center-center text-c bold700">Regístrate gratis</a>
      <a href="/" class="br50 w100 btn-share-see-more p15 p-sml-10 flex-row center-center text-c bold700">Descubre más</a>
    </div>
  </div>
<?php endif; ?>
