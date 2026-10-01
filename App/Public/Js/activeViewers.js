/**
 * Componente Active Viewers (Usuarios en línea por perfil).
 * 
 * Envía periódicamente señales de presencia (heartbeat) al backend para un perfil específico
 * y actualiza dinámicamente los badges de usuarios en línea conectados exclusivamente a ese perfil.
 * 
 * Esta métrica es estrictamente personal por usuario: no contabiliza ni muestra usuarios conectados
 * de forma global en el sitio, únicamente los visitantes concurrentes al perfil del usuario en cuestión.
 * 
 * @function activeViewers
 * @description Maneja la señal de presencia y la actualización de badges en línea del perfil individual.
 */
export function activeViewers() {
  const badgeEls = document.querySelectorAll('.active-viewers-badge, #active-viewers-badge');
  const container = document.querySelector('[data-profile-user], .track-link-click[data-user], .back-card-container');
  if (badgeEls.length === 0 && !container) return;

  // Rutas reservadas del sistema que no corresponden a ningún perfil de usuario
  const reservedPaths = [
    'panel', 'ingresar', 'registrar', 'recuperar', 'salir', 
    'suscripcion', 'planes', 'lemon-squeezy', 'proxy', 'op', 
    'robots.txt', 'sitemap.xml', 'llms.txt'
  ];

  const pathSegments = window.location.pathname.split('/').filter(Boolean);
  let urlUser = '';

  if (pathSegments[0] === 'panel' && pathSegments[1]) {
    urlUser = pathSegments[1];
  } else if (pathSegments[0] && !reservedPaths.includes(pathSegments[0].toLowerCase())) {
    urlUser = pathSegments[0];
  }

  // Determinar el perfil objetivo (targetUser) con prioridad en los atributos de datos
  let targetUser = '';
  for (const badge of badgeEls) {
    const candidate = badge.dataset.profileUser || badge.dataset.user;
    if (candidate) {
      targetUser = candidate;
      break;
    }
  }

  if (!targetUser) {
    targetUser = container?.dataset.profileUser || 
                 container?.dataset.user || 
                 urlUser || '';
  }

  targetUser = targetUser.trim().toLowerCase();

  // Si no se identifica un perfil de usuario válido o es una ruta reservada, no ejecutar
  if (!targetUser || reservedPaths.includes(targetUser)) return;

  // Token de sesión aislado y específico por perfil en localStorage
  const tokenKey = 'viewer_session_token_' + targetUser;
  let activeToken = localStorage.getItem(tokenKey);
  if (!activeToken) {
    activeToken = 'vt_' + targetUser + '_' + Math.random().toString(36).substring(2, 11) + Date.now().toString(36);
    localStorage.setItem(tokenKey, activeToken);
  }

  let intervalId = null;

  /**
   * Envía el latido de presencia para el perfil del usuario actual.
   * La respuesta contiene únicamente la cantidad de visitantes activos en este perfil.
   */
  async function sendHeartbeat() {
    try {
      const response = await fetch('/op/active-viewers', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user: targetUser, token: activeToken })
      });

      if (!response.ok) return;
      const data = await response.json();

      if (data && data.success) {
        if (data.token) {
          activeToken = data.token;
          localStorage.setItem(tokenKey, activeToken);
        }

        const count = (typeof data.count === 'number') ? data.count : 0;
        updateBadgeUI(count);
      }
    } catch (e) {}
  }

  /**
   * Actualiza la interfaz de los badges de usuarios en línea vinculados a este perfil.
   * 
   * @param {number} count Cantidad de usuarios en línea viendo este perfil específico.
   */
  function updateBadgeUI(count) {
    const badges = document.querySelectorAll('.active-viewers-badge, #active-viewers-badge');
    const countEls = document.querySelectorAll('.active-viewers-count, #active-viewers-count');
    const textEls = document.querySelectorAll('.active-viewers-text, #active-viewers-text');

    badges.forEach(badge => {
      const badgeUser = (badge.dataset.profileUser || badge.dataset.user || '').toLowerCase();
      if (badgeUser && badgeUser !== targetUser) return;

      if (count <= 0) {
        badge.classList.add('hidden');
      } else {
        badge.classList.remove('hidden');
      }
    });

    if (count > 0) {
      countEls.forEach(el => {
        const parentBadge = el.closest('.active-viewers-badge, #active-viewers-badge');
        const badgeUser = (parentBadge?.dataset.profileUser || parentBadge?.dataset.user || '').toLowerCase();
        if (badgeUser && badgeUser !== targetUser) return;
        el.textContent = count;
      });

      textEls.forEach(el => {
        const parentBadge = el.closest('.active-viewers-badge, #active-viewers-badge');
        const badgeUser = (parentBadge?.dataset.profileUser || parentBadge?.dataset.user || '').toLowerCase();
        if (badgeUser && badgeUser !== targetUser) return;
        el.textContent = count === 1 ? '1 en línea' : `${count} en línea`;
      });
    }
  }

  // Iniciar timer recurrente solo después de ejecutar el primer heartbeat
  const startInterval = () => {
    if (!intervalId) {
      intervalId = setInterval(sendHeartbeat, 15000);
    }
  };

  // Primer envío diferido para no competir con la carga crítica inicial (FCP/LCP)
  if ('requestIdleCallback' in window) {
    requestIdleCallback(() => {
      sendHeartbeat();
      startInterval();
    }, { timeout: 3000 });
  } else {
    setTimeout(() => {
      sendHeartbeat();
      startInterval();
    }, 2000);
  }

  // Pausar/Reanudar cuando la pestaña cambia de visibilidad para optimizar recursos del cliente y servidor
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      if (intervalId) {
        clearInterval(intervalId);
        intervalId = null;
      }
    } else {
      sendHeartbeat();
      if (!intervalId) {
        intervalId = setInterval(sendHeartbeat, 15000);
      }
    }
  });
}
