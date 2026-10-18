<?php
  use Base\Module\GraphicsModule;

  /**
   * Vista de Estadísticas por Enlace (Dashboard.StatisticsPanel.Links.links)
   * 
   * Muestra las métricas detalladas por botón y redes sociales:
   * 1. Métrica global de CTO y rendimiento (Arc Meter, Clics vs Visitas, Enlaces vs RRSS).
   * 2. Destacado "Enlace Estrella" (el botón con mayor volumen y CTR).
   * 3. Tabla / Tarjetas de enlaces individuales (clics del mes, acumulados, CTR individual y móvil vs desktop con barras planas).
   * 4. Desglose de Redes Sociales (RRSS).
   * 5. Distribución global de dispositivos (Móvil vs Escritorio con proporción bicolor uniforme).
   *
   * @var array  $stats     Estructura completa retornada por StatisticsModels::getStatsData()
   * @var array  $cto       Métricas evaluadas de CTO global
   * @var bool   $isPremium Booleano que indica si el creador cuenta con suscripción activa
   * @var array  $card      Datos de diseño y perfil del usuario
   * @var string $user      Nombre de usuario
   * @var array  $uri       Rutas de navegación
   */

  $userProfile    = !empty($user) ? $user : ($card["profile"] ?? "user");
  $linksDetailed  = is_array($stats["links_detailed"] ?? null) ? $stats["links_detailed"] : [];
  $items          = is_array($linksDetailed["items"] ?? null) ? $linksDetailed["items"] : [];
  $rrss           = is_array($linksDetailed["rrss"] ?? null) ? $linksDetailed["rrss"] : [];
  $starLink       = $linksDetailed["star_link"] ?? null;
  $totalMonthClicks = (int)($linksDetailed["total_month_clicks"] ?? 0);
  $totalAllClicks   = (int)($linksDetailed["total_all_time_clicks"] ?? 0);
  $mobilePct        = (float)($linksDetailed["global_mobile_pct"] ?? $linksDetailed["mobile_percentage"] ?? 0.0);
  $desktopPct       = (float)($linksDetailed["global_desktop_pct"] ?? $linksDetailed["desktop_percentage"] ?? 0.0);
?>

<div class="flex-column gap25 w100 texto">

  <!-- ========================================================
       Cabecera de Estadísticas por Enlace
       ======================================================== -->
  <div class="flex-row center-between wrap gap15 w100">
    <div class="flex-column gap5">
      <h2 class="m0 x24 bold700 texto flex-row center-start gap10">
        <span class="color-secondary"><?= svg("link", "x24"); ?></span>
        Estadísticas por Enlace
      </h2>
      <p class="m0 color-secondary">Rendimiento individual de cada botón, tasa de conversión (CTR), Enlace Estrella y análisis por dispositivos.</p>
    </div>

    <div class="flex-row center-center gap10">
      <?php if ($isPremium): ?>
        <span class="premium-lock-badge">
          <?= svg("check-solid", "x12"); ?>
          Plan Premium
        </span>
      <?php else: ?>
        <a href="/planes" class="btn-premium-unlock" title="Desbloquea el análisis detallado por botón">
          <?= svg("lock", "x12"); ?>
          Mejorar a Premium
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- ========================================================
       1. Métricas Globales de Rendimiento y CTO
       Visible para todos (Free y Premium)
       ======================================================== -->
  <?php
    _part("Dashboard.cto", [
      "cto" => $cto ?? ($stats["cto"] ?? [])
    ]);
  ?>

  <!-- ========================================================
       2. Destacado: «Enlace Estrella»
       Premium: Botón con mayor rendimiento destacado con trofeo
       Free: Teaser con candado
       ======================================================== -->
  <div class="p25 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
    <div class="flex-row center-between wrap gap10 w100">
      <div class="flex-row center-start gap10">
        <span class="flex-row center-center color-warning x22">
          <?= svg("trophy", "x22 color-warning"); ?>
        </span>
        <div class="flex-column gap2">
          <p class="m0 bold600 texto">Enlace Estrella</p>
          <p class="m0 x13 color-secondary">El enlace o botón de tu perfil que genera la mayor cantidad de clics e interacción.</p>
        </div>
      </div>

      <?php if ($isPremium): ?>
        <span class="star-link-badge">
          <?= svg("trophy", "x10"); ?> Mayor conversión
        </span>
      <?php else: ?>
        <span class="premium-lock-badge">
          <?= svg("lock", "x10"); ?> Premium
        </span>
      <?php endif; ?>
    </div>

    <?php if ($isPremium): ?>
      <!-- Vista Desbloqueada (Premium) -->
      <?php if ($starLink && !empty($starLink['title'])): ?>
        <div class="p15 br15 back-card-graphic shadow-card-graphic flex-column gap12 w100">
          <div class="flex-row center-between wrap gap10">
            <div class="flex-column gap4">
              <div class="flex-row center-start gap8">
                <span class="star-link-badge">
                  <?= svg("trophy", "x10"); ?> #1
                </span>
                <strong class="x16 bold700 texto"><?= e($starLink['title']); ?></strong>
              </div>
              <?php if (!empty($starLink['url'])): ?>
                <a href="<?= eUrl($starLink['url']); ?>" target="_blank" rel="noopener noreferrer" class="x12 color-secondary hover-underline flex-row center-start gap4">
                  <?= svg("link", "x11"); ?>
                  <?= e(mb_strimwidth($starLink['url'], 0, 60, '...')); ?>
                </a>
              <?php endif; ?>
            </div>

            <div class="flex-row center-end wrap gap10">
              <div class="flex-column center-end">
                <span class="x22 bold700 color-success leading-none"><?= e((string)$starLink['month_clicks']); ?></span>
                <span class="x11 color-secondary">clics este mes</span>
              </div>
              <div class="flex-column center-end pl15" style="border-left: 1px solid rgba(128,128,128,0.2);">
                <span class="x22 bold700 texto leading-none"><?= e((string)$starLink['all_time_clicks']); ?></span>
                <span class="x11 color-secondary">clics totales</span>
              </div>
            </div>
          </div>

          <!-- Métricas de Tasa CTR y Distribución de Dispositivos -->
          <div class="flex-row center-between wrap gap12 pt10" style="border-top: 1px solid rgba(128,128,128,0.12);">
            <div class="flex-row center-start gap15">
              <span class="x13 color-secondary">
                CTR del mes: <strong class="texto bold600"><?= e((string)$starLink['ctr_month']); ?>%</strong>
              </span>
              <span class="x13 color-secondary">
                CTR histórico: <strong class="texto bold600"><?= e((string)$starLink['ctr_total']); ?>%</strong>
              </span>
            </div>

            <div class="flex-row center-end gap10">
              <span class="x12 color-secondary">Móvil: <strong class="texto"><?= e((string)$starLink['mobile_percentage']); ?>%</strong></span>
              <span class="x12 color-secondary">Escritorio: <strong class="texto"><?= e((string)$starLink['desktop_percentage']); ?>%</strong></span>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="flex-column center p30 gap10 text-center">
          <span class="color-secondary x30"><?= svg("trophy", "x30"); ?></span>
          <p class="m0 bold600 texto">Aún no se determina tu Enlace Estrella</p>
          <p class="m0 x13 color-secondary max-wpx450">Cuando tus enlaces reciban clics de los visitantes, el botón con mayor interacción se destacará automáticamente aquí.</p>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <!-- Vista Bloqueada (Free Teaser) -->
      <div class="premium-blur-container w100">
        <div class="premium-blur-content flex-column gap10 w100 pt5">
          <div class="p15 br15 back-card-graphic shadow-card-graphic flex-row center-between">
            <div class="flex-column gap4">
              <span class="star-link-badge">★ Enlace Estrella</span>
              <strong class="x16 bold700 texto">Mi Curso Online / Tienda Oficial</strong>
              <span class="x12 color-secondary">https://ejemplo.com/tienda</span>
            </div>
            <div class="flex-row gap15">
              <div class="flex-column center-end">
                <span class="x22 bold700 color-success">148</span>
                <span class="x11 color-secondary">clics este mes</span>
              </div>
              <div class="flex-column center-end">
                <span class="x22 bold700 texto">18.5%</span>
                <span class="x11 color-secondary">tasa CTR</span>
              </div>
            </div>
          </div>
        </div>

        <div class="premium-lock-overlay">
          <span class="color-warning x30 mb6"><?= svg("lock", "x30"); ?></span>
          <h4 class="m0 x17 bold700 texto mb4">Descubre cuál es tu botón más exitoso</h4>
          <p class="m0 color-secondary x13 max-wpx450 mb15">Identifica con precisión qué enlace de tu perfil convierte mejor y genera más ventas o clics.</p>
          <a href="/planes" class="btn-premium-unlock">
            <?= svg("lock", "x12"); ?>
            Desbloquear Enlace Estrella
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- ========================================================
       3. Detalle Individual por Enlace (Tabla / Tarjetas)
       Premium: Clics mensuales, totales, CTR y dispositivos por cada botón
       Free: Teaser con candado
       ======================================================== -->
  <div class="p25 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
    <div class="flex-row center-between wrap gap10 w100">
      <div class="flex-row center-start gap10">
        <span class="flex-row center-center color-secondary x20">
          <?= svg("list", "x20"); ?>
        </span>
        <div class="flex-column gap2">
          <p class="m0 bold600 texto">Rendimiento por Botón Individual</p>
          <p class="m0 x13 color-secondary">Métricas desglosadas para cada uno de los enlaces activos configurados en tu perfil.</p>
        </div>
      </div>

      <?php if (!$isPremium): ?>
        <span class="premium-lock-badge">
          <?= svg("lock", "x10"); ?> Premium
        </span>
      <?php endif; ?>
    </div>

    <?php if ($isPremium): ?>
      <!-- Vista Desbloqueada (Premium) -->
      <?php if (!empty($items)): ?>
        <div class="flex-column gap12 w100 pt5">
          <?php foreach ($items as $it): ?>
            <div class="p15 br15 back-card-graphic shadow-card-graphic flex-column gap10 w100 hover-scale-soft">
              <div class="flex-row center-between wrap gap10">
                <div class="flex-column gap4 flex-1" style="min-width: 220px;">
                  <div class="flex-row center-start gap8 wrap">
                    <strong class="x15 bold600 texto"><?= e($it['title']); ?></strong>
                    <?php if (!empty($it['is_star'])): ?>
                      <span class="star-link-badge">
                        <?= svg("trophy", "x9"); ?> Estrella
                      </span>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($it['url'])): ?>
                    <a href="<?= eUrl($it['url']); ?>" target="_blank" rel="noopener noreferrer" class="x12 color-secondary hover-underline flex-row center-start gap4">
                      <?= svg("link", "x11"); ?>
                      <?= e(mb_strimwidth($it['url'], 0, 50, '...')); ?>
                    </a>
                  <?php endif; ?>
                </div>

                <div class="flex-row center-end wrap gap15">
                  <div class="flex-column center-end">
                    <span class="x18 bold700 color-success leading-none"><?= e((string)$it['month_clicks']); ?></span>
                    <span class="x11 color-secondary">mes actual</span>
                  </div>
                  <div class="flex-column center-end">
                    <span class="x18 bold700 texto leading-none"><?= e((string)$it['all_time_clicks']); ?></span>
                    <span class="x11 color-secondary">acumulados</span>
                  </div>
                  <div class="flex-column center-end">
                    <span class="x18 bold700 texto leading-none"><?= e((string)$it['ctr_month']); ?>%</span>
                    <span class="x11 color-secondary">CTR mes</span>
                  </div>
                </div>
              </div>

              <!-- Barra Móvil vs Escritorio para este enlace (Bicolor plana idéntica a cto.php) -->
              <div class="flex-column gap6 pt5" style="border-top: 1px solid rgba(128,128,128,0.1);">
                <div class="flex-row center-between x11 color-secondary">
                  <span class="flex-row center-start gap5">
                    <span class="back-success" style="display: inline-block; width: 8px; height: 8px; border-radius: 50%;"></span>
                    <span>Móvil: <strong class="texto"><?= e((string)$it['mobile_percentage']); ?>%</strong> (<?= e((string)$it['mobile_clicks']); ?>)</span>
                  </span>
                  <span class="flex-row center-start gap5">
                    <span class="back8" style="display: inline-block; width: 8px; height: 8px; border-radius: 50%;"></span>
                    <span>Escritorio: <strong class="texto"><?= e((string)$it['desktop_percentage']); ?>%</strong> (<?= e((string)$it['desktop_clicks']); ?>)</span>
                  </span>
                </div>
                <div class="w100 hpx8 br10 flex-row overflow-hidden" style="background: rgba(128,128,128,0.15);">
                  <div class="back-success" style="width: <?= min(100, max(0, (float)$it['mobile_percentage'])); ?>%; height: 100%; transition: width 0.3s;" title="Móvil: <?= e((string)$it['mobile_percentage']); ?>%"></div>
                  <div class="back8" style="width: <?= min(100, max(0, (float)$it['desktop_percentage'])); ?>%; height: 100%; transition: width 0.3s;" title="Escritorio: <?= e((string)$it['desktop_percentage']); ?>%"></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="flex-column center p30 gap10 text-center">
          <span class="color-secondary x30"><?= svg("link", "x30"); ?></span>
          <p class="m0 bold600 texto">No tienes enlaces de contenido configurados</p>
          <p class="m0 x13 color-secondary">Agrega enlaces desde el menú Contenido > Enlaces para empezar a registrar su rendimiento.</p>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <!-- Vista Bloqueada (Free Teaser) -->
      <div class="premium-blur-container w100">
        <div class="premium-blur-content flex-column gap12 w100 pt5">
          <div class="p12 br12 back-card-graphic shadow-card-graphic flex-row center-between">
            <div class="flex-column gap4">
              <strong class="texto">Mi Canal de YouTube</strong>
              <span class="x12 color-secondary">https://youtube.com/@canal</span>
            </div>
            <div class="flex-row gap15">
              <span>85 clics</span>
              <span>12.4% CTR</span>
            </div>
          </div>
          <div class="p12 br12 back-card-graphic shadow-card-graphic flex-row center-between">
            <div class="flex-column gap4">
              <strong class="texto">Descargar E-book Gratuito</strong>
              <span class="x12 color-secondary">https://descargas.com/guia</span>
            </div>
            <div class="flex-row gap15">
              <span>62 clics</span>
              <span>9.1% CTR</span>
            </div>
          </div>
          <div class="p12 br12 back-card-graphic shadow-card-graphic flex-row center-between">
            <div class="flex-column gap4">
              <strong class="texto">Contacto Directo por WhatsApp</strong>
              <span class="x12 color-secondary">https://wa.me/56900000000</span>
            </div>
            <div class="flex-row gap15">
              <span>41 clics</span>
              <span>6.0% CTR</span>
            </div>
          </div>
        </div>

        <div class="premium-lock-overlay">
          <span class="color-warning x28 mb6"><?= svg("lock", "x28"); ?></span>
          <h4 class="m0 x16 bold700 texto mb4">Análisis Detallado por Cada Botón</h4>
          <p class="m0 color-secondary x13 max-wpx450 mb15">Desbloquea la tasa de clics (CTR) individual, porcentaje móvil vs escritorio y métricas históricas de cada enlace.</p>
          <a href="/planes" class="btn-premium-unlock">
            <?= svg("lock", "x12"); ?>
            Desbloquear con Premium
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- ========================================================
       4. Desglose de Redes Sociales (RRSS)
       Premium: Clics y porcentaje por cada red social configurada
       Free: Teaser con candado
       ======================================================== -->
  <?php if (!empty($rrss)): ?>
    <div class="p25 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
      <div class="flex-row center-between wrap gap10 w100">
        <div class="flex-row center-start gap10">
          <span class="flex-row center-center color-secondary x20">
            <?= svg("share-node", "x20"); ?>
          </span>
          <div class="flex-column gap2">
            <p class="m0 bold600 texto">Interacción en Redes Sociales (Iconos)</p>
            <p class="m0 x13 color-secondary">Clics registrados en los accesos rápidos a redes sociales de tu cabecera.</p>
          </div>
        </div>

        <?php if (!$isPremium): ?>
          <span class="premium-lock-badge">
            <?= svg("lock", "x10"); ?> Premium
          </span>
        <?php endif; ?>
      </div>

      <?php if ($isPremium): ?>
        <div class="flex-row wrap gap12 w100 pt5">
          <?php foreach ($rrss as $r): 
            $rName = !empty($r['name']) ? $r['name'] : ucfirst($r['network'] ?? 'Red social');
            $rKey  = !empty($r['key']) ? $r['key'] : ($r['icon'] ?? ($r['network'] ?? 'share-node'));
          ?>
            <div class="flex-row center-between gap15 p12 pl15 pr15 br12 back-card-graphic shadow-card-graphic flex-1 hover-scale-soft" style="min-width: 180px;">
              <div class="flex-row center-start gap10">
                <span class="x20 color-secondary"><?= svg($rKey, 'x20'); ?></span>
                <strong class="texto bold600"><?= e($rName); ?></strong>
              </div>
              <div class="flex-column center-end">
                <span class="bold700 texto x15"><?= e((string)($r['month_clicks'] ?? 0)); ?></span>
                <span class="x11 color-secondary">clics mes</span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="premium-blur-container w100">
          <div class="premium-blur-content flex-row wrap gap12 w100 pt5">
            <div class="flex-row center-between p12 br12 back-card-graphic shadow-card-graphic flex-1" style="min-width: 180px;">
              <span>Instagram</span>
              <span>35 clics</span>
            </div>
            <div class="flex-row center-between p12 br12 back-card-graphic shadow-card-graphic flex-1" style="min-width: 180px;">
              <span>TikTok</span>
              <span>28 clics</span>
            </div>
          </div>
          <div class="premium-lock-overlay">
            <span class="color-warning x24 mb5"><?= svg("lock", "x24"); ?></span>
            <p class="m0 bold700 texto mb4">Métricas de Redes Sociales</p>
            <p class="m0 color-secondary x13 mb12">Descubre cuáles de tus perfiles sociales reciben más interés.</p>
            <a href="/planes" class="btn-premium-unlock">
              <?= svg("lock", "x12"); ?> Desbloquear con Premium
            </a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- ========================================================
       5. Distribución de Dispositivos (Móvil vs Escritorio)
       ======================================================== -->
  <div class="p25 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
    <div class="flex-row center-between wrap gap10 w100">
      <div class="flex-row center-start gap10">
        <span class="flex-row center-center color-secondary x20">
          <?= svg("sliders", "x20"); ?>
        </span>
        <div class="flex-column gap2">
          <p class="m0 bold600 texto">Dispositivos de Conversión</p>
          <p class="m0 x13 color-secondary">Proporción general de clics originados en smartphones versus ordenadores.</p>
        </div>
      </div>

      <div class="flex-row center-end gap15 x13">
        <span class="flex-row center-start gap5">
          <span class="back-success" style="display: inline-block; width: 10px; height: 10px; border-radius: 50%;"></span>
          <span class="color-secondary">Móvil: <strong class="texto"><?= e((string)$mobilePct); ?>%</strong></span>
        </span>
        <span class="flex-row center-start gap5">
          <span class="back8" style="display: inline-block; width: 10px; height: 10px; border-radius: 50%;"></span>
          <span class="color-secondary">Escritorio: <strong class="texto"><?= e((string)$desktopPct); ?>%</strong></span>
        </span>
      </div>
    </div>

    <div class="flex-column gap8 w100 pt5">
      <!-- Barra de proporción visual bicolor idéntica a cto.php -->
      <div class="w100 hpx12 br10 flex-row overflow-hidden" style="background: rgba(128,128,128,0.15);">
        <div class="back-success" style="width: <?= min(100, max(0, (float)$mobilePct)); ?>%; height: 100%; transition: width 0.3s;" title="Móvil: <?= e((string)$mobilePct); ?>%"></div>
        <div class="back8" style="width: <?= min(100, max(0, (float)$desktopPct)); ?>%; height: 100%; transition: width 0.3s;" title="Escritorio: <?= e((string)$desktopPct); ?>%"></div>
      </div>
    </div>
  </div>

</div>
