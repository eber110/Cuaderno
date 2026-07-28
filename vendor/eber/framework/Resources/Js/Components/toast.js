/**
 * Sistema de notificaciones toast programáticas.
 * Incluye animaciones GSAP opcionales.
 * 
 * @function toast
 * @description Muestra notificaciones temporales en la esquina de la pantalla.
 *              Se puede llamar programáticamente desde cualquier parte.
 * 
 * @param {string} message - Mensaje a mostrar
 * @param {string} [type='info'] - Tipo: 'success', 'error', 'warning', 'info'
 * @param {number} [duration=3000] - Duración en milisegundos
 * @param {boolean} [animated=false] - Usar animaciones GSAP
 * 
 * @example
 * // JavaScript sin animación
 * toast('Guardado correctamente', 'success');
 * 
 * // JavaScript con animación GSAP
 * toast('Guardado correctamente', 'success', 3000, true);
 * 
 * @returns {void}
 */
export function toast(message, type = 'info', duration = 3000, animated = false) {
  // Verificar si GSAP está disponible
  const hasGsap = typeof gsap !== 'undefined';
  const useAnimation = hasGsap && animated;

  // Crear contenedor si no existe
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'fixed flex column-direction gap10';
    container.style.cssText = 'bottom: 20px; right: 20px; z-index: 999999;';
    document.body.appendChild(container);
  }

  // Crear toast
  const toastEl = document.createElement('div');
  toastEl.className = `toast toast-${type} p15 br10 flex row-direction center-y gap10`;

  // Estilos según tipo
  const styles = {
    success: 'back-success color7',
    error: 'back-danger color2',
    warning: 'back-caution color1',
    info: 'back1 color2'
  };
  toastEl.classList.add(...(styles[type] || styles.info).split(' '));

  // Iconos según tipo
  const icons = {
    success: '✓',
    error: '✕',
    warning: '⚠',
    info: 'ℹ'
  };

  toastEl.innerHTML = `
    <span class="toast-icon x18 bold700">${icons[type] || icons.info}</span>
    <span class="toast-message">${message}</span>
    <button class="toast-close ml10 pointer x16 bold700" style="background:none;border:none;color:inherit;">✕</button>
  `;

  container.appendChild(toastEl);

  // Función para remover
  let removeTimer;
  const removeToast = () => {
    clearTimeout(removeTimer);

    if (useAnimation) {
      gsap.to(toastEl, {
        x: '100%',
        opacity: 0,
        scale: 0.9,
        duration: 0.3,
        ease: 'power2.in',
        onComplete: () => toastEl.remove()
      });
    } else {
      toastEl.style.transform = 'translateX(100%)';
      toastEl.style.opacity = '0';
      setTimeout(() => toastEl.remove(), 300);
    }
  };

  // Animación de entrada
  if (useAnimation) {
    gsap.fromTo(toastEl,
      { x: '100%', opacity: 0, scale: 0.9 },
      {
        x: 0,
        opacity: 1,
        scale: 1,
        duration: 0.4,
        ease: 'back.out(1.4)'
      }
    );
  } else {
    toastEl.style.transform = 'translateX(100%)';
    toastEl.style.opacity = '0';
    toastEl.style.transition = 'all 0.3s ease';

    requestAnimationFrame(() => {
      toastEl.style.transform = 'translateX(0)';
      toastEl.style.opacity = '1';
    });
  }

  // Cerrar al click
  toastEl.querySelector('.toast-close').addEventListener('click', removeToast);

  // Auto-cerrar
  removeTimer = setTimeout(removeToast, duration);

  // Pausar timer al hover
  toastEl.addEventListener('mouseenter', () => clearTimeout(removeTimer));
  toastEl.addEventListener('mouseleave', () => {
    removeTimer = setTimeout(removeToast, duration / 2);
  });
}

// Exponer toast globalmente para uso programático
window.toast = toast;

/**
 * Inicializador de disparadores de toast via HTML.
 * Incluye animaciones GSAP opcionales con clase 'animated'.
 * 
 * @function toastTrigger
 * @description Permite disparar toasts desde HTML sin JavaScript inline.
 *              Usa data-attributes para configurar el mensaje y tipo.
 * 
 * @example
 * // HTML - Sin animación
 * <button class="toast-trigger" data-message="Guardado!" data-type="success">Guardar</button>
 * 
 * // HTML - Con animación GSAP
 * <button class="toast-trigger animated" data-message="Guardado!" data-type="success">Guardar</button>
 * 
 * @attribute data-message - Mensaje a mostrar (requerido)
 * @attribute data-type - Tipo: 'success', 'error', 'warning', 'info' (default: 'info')
 * @attribute data-duration - Duración en ms (default: 3000)
 * @css .toast-trigger.animated - Activa animaciones GSAP
 * 
 * @returns {void}
 */
export function toastTrigger() {
  document.addEventListener('click', (e) => {
    if (!e.target || !e.target.closest) return;
    const trigger = e.target.closest('.toast-trigger');
    if (!trigger) return;

    const message = trigger.dataset.message;
    if (!message) return;

    const type = trigger.dataset.type || 'info';
    const duration = parseInt(trigger.dataset.duration) || 3000;
    const animated = trigger.classList.contains('animated');

    toast(message, type, duration, animated);
  });
}
