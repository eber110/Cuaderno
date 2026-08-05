/**
 * Componente Active Viewers (Usuarios en línea).
 * 
 * Envía periódicamente señales de presencia (heartbeat) al backend
 * y actualiza dinámicamente el badge de usuarios en línea en el perfil.
 */
export function activeViewers() {
  const container = document.querySelector('[data-profile-user], .track-link-click[data-user], .back-card-container');
  if (!container) return;

  const targetUser = container.dataset.profileUser || 
                     container.dataset.user || 
                     window.location.pathname.split('/')[1] || '';

  if (!targetUser) return;

  const badgeEl = document.getElementById('active-viewers-badge');
  const countEl = document.getElementById('active-viewers-count');
  const textEl  = document.getElementById('active-viewers-text');

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

        const count = data.count || 1;
        updateBadgeUI(count);
      }
    } catch (e) {}
  }

  function updateBadgeUI(count) {
    if (!badgeEl) return;

    badgeEl.classList.remove('hidden');

    if (countEl) {
      countEl.textContent = count;
    }

    if (textEl) {
      if (count === 1) {
        textEl.textContent = '1 en línea';
      } else {
        textEl.textContent = `${count} en línea`;
      }
    }
  }

  // Primer envío inmediato
  sendHeartbeat();

  // Iniciar intervalo cada 15 segundos
  intervalId = setInterval(sendHeartbeat, 15000);

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
