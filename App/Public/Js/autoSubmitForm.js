/**
 * Componente AutoSubmitForm.
 *
 * Punto de entrada diferido del módulo que inicializa los contadores regresivos
 * dinámicos en los bloques de campaña.
 *
 * @function autoSubmitForm
 * @description Inicializa los contadores de campañas activas en la vista previa y perfil público.
 * @returns {void}
 */
export function autoSubmitForm() {
  initCampaignCountdowns();
}

/**
 * Inicializa contadores regresivos dinámicos en los bloques de campaña
 * para que funcionen tanto en carga inicial como en actualizaciones de vista previa.
 *
 * @function initCampaignCountdowns
 * @returns {void}
 */
export function initCampaignCountdowns() {
  document.querySelectorAll('[data-countdown]').forEach((box) => {
    if (box.dataset.countdownActive === 'true') return;
    box.dataset.countdownActive = 'true';

    const targetStr = box.getAttribute('data-countdown');
    if (!targetStr) return;
    const target = new Date(targetStr).getTime();
    if (isNaN(target)) return;

    const wrapper = box.closest('.campaign-block-wrapper');

    function updateCountdown() {
      const now = new Date().getTime();
      const diff = target - now;

      if (diff <= 0) {
        if (box.__countdownTimer) clearInterval(box.__countdownTimer);
        if (wrapper) wrapper.style.display = 'none';
        return;
      }

      const days = Math.floor(diff / (1000 * 60 * 60 * 24));
      const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((diff % (1000 * 60)) / 1000);

      const dEl = box.querySelector('.countdown-days');
      const hEl = box.querySelector('.countdown-hours');
      const mEl = box.querySelector('.countdown-minutes');
      const sEl = box.querySelector('.countdown-seconds');

      if (dEl) dEl.textContent = String(days).padStart(2, '0');
      if (hEl) hEl.textContent = String(hours).padStart(2, '0');
      if (mEl) mEl.textContent = String(minutes).padStart(2, '0');
      if (sEl) sEl.textContent = String(seconds).padStart(2, '0');
    }

    updateCountdown();
    box.__countdownTimer = setInterval(updateCountdown, 1000);
  });
}
