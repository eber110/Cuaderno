<?php
  use App\Models\StatisticsModels;

  /**
   * Vista de Visitantes Recientes (Dashboard.StatisticsPanel.RecentVisitors.recentVisitors)
   *
   * @var array|null $visitors     Lista de usuarios registrados que visitaron el perfil
   * @var array|null $stats        Estructura analítica global
   * @var bool|null  $isPremium    Booleano que indica si el creador cuenta con suscripción activa
   * @var bool|null  $canViewSubs  Booleano que indica si las suscripciones están publicadas
   */

  $statsList    = isset($stats) && is_array($stats) ? $stats : [];
  $visitors     = isset($visitors) && is_array($visitors) 
    ? $visitors 
    : (isset($statsList["visitors"]) && is_array($statsList["visitors"]) ? $statsList["visitors"] : []);
  $canViewSubs  = isset($canViewSubs) ? (bool)$canViewSubs : StatisticsModels::canViewSubscriptions();
  $rawPremium   = isset($isPremium) ? (bool)$isPremium : false;
  $isPremium    = !$canViewSubs ? true : $rawPremium;
  $visitorCount = count($visitors);
?>

<div class="back-card-graphic shadow-card-graphic stats-card flex-column gap18 w100">
  <div class="flex-row center-between wrap gap12 w100">
    <div class="flex-row top-start gap12">
      <span class="stats-icon-box">
        <?= svg("user", "x18"); ?>
      </span>
      <div class="ui-field-header">
        <p class="m0 bold500 texto">¿Quién te visitó recientemente?</p>
        <p class="m0 color-secondary">Usuarios registrados de la comunidad que han navegado por tu perfil.</p>
      </div>
    </div>

    <?php if ($canViewSubs && !$isPremium): ?>
      <span class="premium-lock-badge">
        <?= svg("lock", "x12"); ?> Premium
      </span>
    <?php else: ?>
      <span class="stats-pill-badge">
        <?= e((string)$visitorCount); ?> <?= $visitorCount === 1 ? 'visitante' : 'visitantes'; ?>
      </span>
    <?php endif; ?>
  </div>

  <?php if ($isPremium): ?>
    <!-- Vista Desbloqueada (Premium) -->
    <?php if (!empty($visitors)): ?>
      <div class="flex-column gap10 w100">
        <?php foreach ($visitors as $v): ?>
          <div class="stats-subcard flex-row center-between wrap gap12">
            <div class="flex-row center-start gap12">
              <img src="<?= eUrl($v['avatar']); ?>" alt="<?= e($v['display_name']); ?>" class="br50 object-cover hpx40 wpx40" width="40" height="40" loading="lazy">
              <div class="ui-field-header">
                <a href="<?= eUrl($v['profile_url']); ?>" target="_blank" rel="noopener noreferrer" class="bold500 texto hover-underline">
                  <?= e($v['display_name']); ?>
                </a>
                <span class="color-secondary">@<?= e($v['username']); ?></span>
              </div>
            </div>

            <div class="flex-row center-end gap12">
              <span class="color-secondary flex-row center-start gap6">
                <span class="flex-row center-center x16 flex-shrink-0"><?= svg("clock", "x16"); ?></span>
                <?= e($v['time_ago']); ?>
              </span>
              <span class="stats-pill-badge">
                <?= e((string)$v['visit_count']); ?> <?= $v['visit_count'] === 1 ? 'visita' : 'visitas'; ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="stats-subcard flex-column center p30 gap10 text-center">
        <span class="stats-icon-box"><?= svg("user", "x20"); ?></span>
        <p class="m0 bold500 texto">Aún no hay visitas de usuarios registrados</p>
        <p class="m0 color-secondary wpx-max-450">Cuando otros creadores y seguidores con cuenta visiten tu enlace, aparecerán registrados en este feed cronológico.</p>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Vista Bloqueada (Free Teaser) -->
    <div class="premium-blur-container w100">
      <div class="premium-blur-content flex-column gap10 w100">
        <div class="stats-subcard flex-row center-between">
          <div class="flex-row center-start gap12">
            <div class="br50 stats-inner-card" style="width:40px; height:40px; padding:0 !important;"></div>
            <div class="ui-field-header">
              <span class="bold500 texto">Usuario de la comunidad</span>
              <span class="color-secondary">@usuario_creador</span>
            </div>
          </div>
          <span class="color-secondary">Hace 2 horas</span>
        </div>
        <div class="stats-subcard flex-row center-between">
          <div class="flex-row center-start gap12">
            <div class="br50 stats-inner-card" style="width:40px; height:40px; padding:0 !important;"></div>
            <div class="ui-field-header">
              <span class="bold500 texto">Seguidor destacado</span>
              <span class="color-secondary">@seguidor_web</span>
            </div>
          </div>
          <span class="color-secondary">Ayer</span>
        </div>
        <div class="stats-subcard flex-row center-between">
          <div class="flex-row center-start gap12">
            <div class="br50 stats-inner-card" style="width:40px; height:40px; padding:0 !important;"></div>
            <div class="ui-field-header">
              <span class="bold500 texto">Visitante recurrente</span>
              <span class="color-secondary">@creador_pro</span>
            </div>
          </div>
          <span class="color-secondary">Hace 3 días</span>
        </div>
      </div>

      <div class="premium-lock-overlay">
        <span class="stats-icon-box mb10"><?= svg("lock", "x20"); ?></span>
        <h4 class="m0 bold500 texto mb6">Descubre quién visita tu perfil</h4>
        <p class="m0 color-secondary wpx-max-450 mb15">
          <?php if ($visitorCount > 0): ?>
            Tienes <strong class="texto bold500"><?= e((string)$visitorCount); ?></strong> <?= $visitorCount === 1 ? 'usuario registrado que visitó' : 'usuarios registrados que visitaron'; ?> tu perfil.
          <?php else: ?>
            Conoce en tiempo real la identidad de los creadores y usuarios que revisan tus enlaces.
          <?php endif; ?>
          Desbloquea el feed completo con el Plan Premium.
        </p>
        <a href="/planes" class="btn-premium-unlock">
          <?= svg("lock", "x14"); ?>
          Desbloquear con Premium
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>
