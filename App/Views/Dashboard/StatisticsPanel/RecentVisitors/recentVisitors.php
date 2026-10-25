<?php
  /**
   * Vista de Visitantes Recientes (Dashboard.StatisticsPanel.RecentVisitors.recentVisitors)
   *
   * Muestra el feed cronológico «¿Quién te visitó?»:
   * - Plan Premium: Lista de creadores y usuarios registrados con avatar, nombre, fecha relativa y visitas.
   * - Plan Free: Teaser desenfocado con candado de suscripción y conteo real de visitantes.
   *
   * @var array $visitors  Lista de usuarios registrados que visitaron el perfil
   * @var bool  $isPremium Booleano que indica si el creador cuenta con suscripción activa
   */

  $visitors     = is_array($visitors ?? null) ? $visitors : (is_array($stats["visitors"] ?? null) ? $stats["visitors"] : []);
  $canViewSubs  = isset($canViewSubs) ? (bool)$canViewSubs : \App\Models\StatisticsModels::canViewSubscriptions();
  $isPremium    = !$canViewSubs ? true : (bool)($isPremium ?? false);
  $visitorCount = count($visitors);
?>

<div class="p25 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
  <div class="flex-row center-between wrap gap10 w100">
    <div class="flex-row center-start gap10">
      <span class="flex-row center-center color-secondary x20">
        <?= svg("user", "x20"); ?>
      </span>
      <div class="flex-column gap2">
        <p class="m0 bold600 texto">¿Quién te visitó recientemente?</p>
        <p class="m0 x13 color-secondary">Usuarios registrados de la comunidad que han navegado por tu perfil.</p>
      </div>
    </div>

    <?php if ($canViewSubs && !$isPremium): ?>
      <span class="premium-lock-badge">
        <?= svg("lock", "x10"); ?> Premium
      </span>
    <?php else: ?>
      <span class="back-card-graphic shadow-card-graphic p5 pl10 pr10 br10 bold500 x12 color-secondary">
        <?= e((string)$visitorCount); ?> <?= $visitorCount === 1 ? 'visitante' : 'visitantes'; ?>
      </span>
    <?php endif; ?>
  </div>

  <?php if ($isPremium): ?>
    <!-- Vista Desbloqueada (Premium) -->
    <?php if (!empty($visitors)): ?>
      <div class="flex-column gap10 w100 pt10">
        <?php foreach ($visitors as $v): ?>
          <div class="flex-row center-between wrap gap10 p10 pl15 pr15 br12 back-card-graphic shadow-card-graphic hover-scale-soft">
            <div class="flex-row center-start gap12">
              <img src="<?= eUrl($v['avatar']); ?>" alt="<?= e($v['display_name']); ?>" class="br50 object-cover hpx40 wpx40" width="40" height="40" loading="lazy">
              <div class="flex-column gap2">
                <a href="<?= eUrl($v['profile_url']); ?>" target="_blank" rel="noopener noreferrer" class="bold600 texto hover-underline leading-none">
                  <?= e($v['display_name']); ?>
                </a>
                <span class="x12 color-secondary">@<?= e($v['username']); ?></span>
              </div>
            </div>

            <div class="flex-row center-end gap15">
              <span class="x12 color-secondary flex-row center-start gap4">
                <?= svg("clock", "x12"); ?>
                <?= e($v['time_ago']); ?>
              </span>
              <span class="back-card-graphic shadow-card-graphic p4 pl8 pr8 br10 x11 bold600 color-secondary">
                <?= e((string)$v['visit_count']); ?> <?= $v['visit_count'] === 1 ? 'visita' : 'visitas'; ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="flex-column center p30 gap10 text-center">
        <span class="color-secondary x30"><?= svg("user", "x30"); ?></span>
        <p class="m0 bold600 texto">Aún no hay visitas de usuarios registrados</p>
        <p class="m0 x13 color-secondary max-wpx450">Cuando otros creadores y seguidores con cuenta visiten tu enlace, aparecerán registrados en este feed cronológico.</p>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Vista Bloqueada (Free Teaser) -->
    <div class="premium-blur-container w100">
      <div class="premium-blur-content flex-column gap10 w100 pt5">
        <div class="flex-row center-between p10 pl15 pr15 br12 back-card-graphic shadow-card-graphic">
          <div class="flex-row center-start gap12">
            <div class="br50 back-card-graphic" style="width:40px; height:40px;"></div>
            <div class="flex-column gap4">
              <span class="bold600 texto">Usuario de la comunidad</span>
              <span class="x12 color-secondary">@usuario_creador</span>
            </div>
          </div>
          <span class="x12 color-secondary">Hace 2 horas</span>
        </div>
        <div class="flex-row center-between p10 pl15 pr15 br12 back-card-graphic shadow-card-graphic">
          <div class="flex-row center-start gap12">
            <div class="br50 back-card-graphic" style="width:40px; height:40px;"></div>
            <div class="flex-column gap4">
              <span class="bold600 texto">Seguidor destacado</span>
              <span class="x12 color-secondary">@seguidor_web</span>
            </div>
          </div>
          <span class="x12 color-secondary">Ayer</span>
        </div>
        <div class="flex-row center-between p10 pl15 pr15 br12 back-card-graphic shadow-card-graphic">
          <div class="flex-row center-start gap12">
            <div class="br50 back-card-graphic" style="width:40px; height:40px;"></div>
            <div class="flex-column gap4">
              <span class="bold600 texto">Visitante recurrente</span>
              <span class="x12 color-secondary">@creador_pro</span>
            </div>
          </div>
          <span class="x12 color-secondary">Hace 3 días</span>
        </div>
      </div>

      <div class="premium-lock-overlay">
        <span class="color-warning x32 mb8"><?= svg("lock", "x32"); ?></span>
        <h4 class="m0 x18 bold700 texto mb5">Descubre quién visita tu perfil</h4>
        <p class="m0 color-secondary x13 max-wpx450 mb15">
          <?php if ($visitorCount > 0): ?>
            Tienes <strong class="texto bold600"><?= e((string)$visitorCount); ?></strong> <?= $visitorCount === 1 ? 'usuario registrado que visitó' : 'usuarios registrados que visitaron'; ?> tu perfil.
          <?php else: ?>
            Conoce en tiempo real la identidad de los creadores y usuarios que revisan tus enlaces.
          <?php endif; ?>
          Desbloquea el feed completo con el Plan Premium.
        </p>
        <a href="/planes" class="btn-premium-unlock">
          <?= svg("lock", "x13"); ?>
          Desbloquear con Premium
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>
