/**
 * Componente Active Viewers (Usuarios en línea).
 * 
 * Envía periódicamente señales de presencia (heartbeat) al backend
 * y actualiza dinámicamente todos los badges de usuarios en línea presentes en la página.
 * 
 * @function activeViewers
 * @description Maneja la señal de presencia y la actualización múltiple de badges en línea.
 */
export function activeViewers() {
  const badgeEls = document.querySelectorAll('.active-viewers-badge, #active-viewers-badge');
  const container = document.querySelector('[data-profile-user], .track-link-click[data-user], .back-card-container');
  if (badgeEls.length === 0 && !container) return;

  const pathSegments = window.location.pathname.split('/').filter(Boolean);
  let urlUser = '';
  if (pathSegments[0] === 'panel' && pathSegments[1]) {
    urlUser = pathSegments[1];
  } else if (pathSegments[0] && pathSegments[0] !== 'panel') {
    urlUser = pathSegments[0];
  }

  let targetUser = '';
  for (const badge of badgeEls) {
    if (badge.dataset.profileUser || badge.dataset.user) {
      targetUser = badge.dataset.profileUser || badge.dataset.user;
      break;
    }
  }

  if (!targetUser) {
    targetUser = container?.dataset.profileUser || 
                 container?.dataset.user || 
                 urlUser || '';
  }

  if (!targetUser) return;

  let activeToken = localStorage.getItem('viewer_session_token');
  if (!activeToken) {
    activeToken = 'vt_' + Math.random().toString(36).substring(2, 11) + Date.now().toString(36);
    localStorage.setItem('viewer_session_token', activeToken);
  }

  let intervalId = null;

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
          localStorage.setItem('viewer_session_token', activeToken);
        }

        const count = (typeof data.count === 'number') ? data.count : 0;
        updateBadgeUI(count);
      }
    } catch (e) {}
  }

  function updateBadgeUI(count) {
    const badges = document.querySelectorAll('.active-viewers-badge, #active-viewers-badge');
    const countEls = document.querySelectorAll('.active-viewers-count, #active-viewers-count');
    const textEls = document.querySelectorAll('.active-viewers-text, #active-viewers-text');

    badges.forEach(badge => {
      if (count <= 0) {
        badge.classList.add('hidden');
      } else {
        badge.classList.remove('hidden');
      }
    });

    if (count > 0) {
      countEls.forEach(el => {
        el.textContent = count;
      });

      textEls.forEach(el => {
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

  // Primer envío diferido para no bloquear la ruta crítica inicial ni competir con FCP/LCP
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

  // Pausar/Reanudar cuando la pestaña cambia de visibilidad para ahorrar recursos
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
