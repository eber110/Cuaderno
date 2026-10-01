/**
 * Módulo de Analíticas y Rastreo Orgánico de Visitas y Clics.
 * 
 * Centraliza la responsabilidad de registrar visitas a perfiles y clics en enlaces,
 * aplicando defensas del lado del cliente contra bots, rastreadores y eventos artificiales:
 * 1. Filtrado de navegadores automatizados (navigator.webdriver).
 * 2. Registro diferido de visitas tras confirmación de presencia humana (retardo de permanencia o interacción real).
 * 3. Filtrado de eventos de clics artificiales no originados físicamente por el usuario (e.isTrusted).
 * 4. Protección contra bots mediante enlaces honeypot invisibles.
 * 
 * @function analyticsTracker
 * @description Inicializa el rastreo de visitas orgánicas y clics verificados.
 */
export function analyticsTracker() {
  // Evitar navegadores headless/automatizados evidentes
  if (navigator.webdriver) {
    return;
  }

  initProfileViewTracker();
  initLinkClickTracker();
}

/**
 * Alias de compatibilidad hacia atrás por si algún script antiguo invoca trackClick.
 */
export function trackClick() {
  initLinkClickTracker();
}

/**
 * Resuelve el usuario del perfil actual en base al DOM o a la URL.
 * 
 * @returns {string} Nombre de usuario limpio o cadena vacía.
 */
function resolveProfileUser() {
  const container = document.querySelector('[data-profile-user], .track-link-click[data-user], .back-card-container');
  
  const reservedPaths = [
    'panel', 'ingresar', 'registrar', 'recuperar', 'salir',
    'suscripcion', 'planes', 'lemon-squeezy', 'proxy', 'op',
    'robots.txt', 'sitemap.xml', 'llms.txt', 'uploads', 'app', 'cache'
  ];

  const pathSegments = window.location.pathname.split('/').filter(Boolean);
  let urlUser = '';

  if (pathSegments[0] === 'panel' && pathSegments[1]) {
    urlUser = pathSegments[1];
  } else if (pathSegments[0] && !reservedPaths.includes(pathSegments[0].toLowerCase())) {
    urlUser = pathSegments[0];
  }

  const targetUser = container?.dataset.profileUser ||
                     container?.dataset.user ||
                     urlUser || '';

  const clean = targetUser.trim().toLowerCase();
  return reservedPaths.includes(clean) ? '' : clean;
}

/**
 * Rastrea la visita al perfil únicamente tras confirmar interacción o permanencia humana.
 */
function initProfileViewTracker() {
  const user = resolveProfileUser();
  if (!user) return;

  // No registrar visitas si se navega dentro del panel de administración
  if (window.location.pathname.startsWith('/panel/')) {
    return;
  }

  let viewSent = false;
  let timerId = null;

  const sendView = () => {
    if (viewSent) return;
    if (document.hidden) return; // Esperar a que la pestaña esté visible para confirmar presencia humana

    viewSent = true;

    if (timerId) {
      clearTimeout(timerId);
      timerId = null;
    }

    // Limpiar listeners de interacción tras el primer disparo
    window.removeEventListener('scroll', sendView);
    window.removeEventListener('pointerdown', sendView);
    window.removeEventListener('touchstart', sendView);
    window.removeEventListener('keydown', sendView);

    const payload = JSON.stringify({
      user: user,
      referrer: document.referrer || ''
    });

    if (navigator.sendBeacon) {
      navigator.sendBeacon('/op/track-view', payload);
    } else {
      fetch('/op/track-view', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: payload,
        keepalive: true
      }).catch(function() {});
    }
  };

  // 1. Envío si el usuario permanece más de 2 segundos en el perfil
  timerId = setTimeout(sendView, 2000);

  // 2. Envío anticipado si el usuario interactúa activamente (scroll, toque, clic, teclado)
  window.addEventListener('scroll', sendView, { once: true, passive: true });
  window.addEventListener('pointerdown', sendView, { once: true, passive: true });
  window.addEventListener('touchstart', sendView, { once: true, passive: true });
  window.addEventListener('keydown', sendView, { once: true, passive: true });

  // 3. Manejo de cambio de visibilidad de la pestaña
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden && !viewSent) {
      sendView();
    }
  });
}

/**
 * Rastrea los clics en enlaces con validación anti-bot.
 */
function initLinkClickTracker() {
  if (window.__cuaderno_click_tracker_initialized) return;
  window.__cuaderno_click_tracker_initialized = true;

  document.addEventListener('click', function(e) {
    // 1. Descartar eventos artificiales no originados físicamente por un humano
    if (e.isTrusted === false) {
      return;
    }

    const link = e.target.closest('.track-link-click');
    if (!link) return;

    // 2. Comprobación de enlace trampa honeypot
    const isTrap = link.classList.contains('hp-trap-link') || link.dataset.trap === 'true';
    if (isTrap) {
      e.preventDefault();
      return;
    }

    const user = link.dataset.user || resolveProfileUser();
    const linkId = link.dataset.linkId;

    if (user && linkId) {
      const payload = JSON.stringify({
        user: user,
        linkId: linkId,
        isTrusted: true
      });

      if (navigator.sendBeacon) {
        navigator.sendBeacon('/op/track-click', payload);
      } else {
        fetch('/op/track-click', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: payload,
          keepalive: true
        }).catch(function() {});
      }
    }
  });
}
