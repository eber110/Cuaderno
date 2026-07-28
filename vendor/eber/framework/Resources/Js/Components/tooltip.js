/**
 * Sistema de tooltips.
 * Incluye animaciones GSAP opcionales con clase 'animated'.
 * 
 * @function tooltip
 * @description Muestra información adicional al hacer hover sobre elementos.
 *              La posición se ajusta automáticamente según el espacio disponible.
 * 
 * @example
 * // HTML - Sin animación
 * <button class="tooltip" data-tooltip="Texto del tooltip">Hover me</button>
 * 
 * // HTML - Con animación GSAP
 * <button class="tooltip animated" data-tooltip="Texto del tooltip">Hover me</button>
 * 
 * // HTML - Con clases de estilo personalizadas
 * <div class="tooltip" data-tooltip="Información" style-tooltip="back-danger textw p20">Hover me</div>
 * 
 * @css .tooltip - Elemento con tooltip
 * @css .tooltip.animated - Activa animaciones GSAP
 * @attribute data-tooltip - Texto a mostrar
 * @attribute style-tooltip - Clases CSS adicionales para dar estilo al tooltip
 * @css .top/.bottom/.left/.right - Posición del tooltip
 * 
 * @requires gsap - GreenSock Animation Platform (opcional)
 * @returns {void}
 */
export function tooltip() {
  // Verificar si GSAP está disponible
  const hasGsap = typeof gsap !== 'undefined';

  // Inyectar estilos por defecto en el documento para evitar que los estilos en línea
  // anulen las clases personalizadas de CSS (especificidad CSS)
  if (!document.getElementById('default-tooltip-styles')) {
    const style = document.createElement('style');
    style.id = 'default-tooltip-styles';
    style.textContent = `
      .tooltip-popup {
        background: rgba(0, 0, 0, 0.85);
        color: white;
        max-width: 250px;
      }
    `;
    document.head.appendChild(style);
  }

  let activeTooltip = null;
  let activeElement = null;

  function showTooltip(element) {
    const text = element.dataset.tooltip;
    if (!text) return;

    const useAnimation = hasGsap && element.classList.contains('animated');

    // Remover tooltip existente
    if (activeTooltip) {
      activeTooltip.remove();
      activeTooltip = null;
    }

    activeElement = element;

    // Crear tooltip
    activeTooltip = document.createElement('div');
    
    // Obtener clases personalizadas de estilo desde el atributo style-tooltip
    const styleTooltip = element.getAttribute('style-tooltip') || element.dataset.styleTooltip || '';
    activeTooltip.className = 'tooltip-popup p10 br5 x14';
    if (styleTooltip) {
      activeTooltip.className += ' ' + styleTooltip;
    }

    // Cabeceras esenciales de posicionamiento y comportamiento (sin anular colores o anchos)
    activeTooltip.style.cssText = `
      position: fixed;
      z-index: 999999;
      pointer-events: none;
    `;
    activeTooltip.textContent = text;
    document.body.appendChild(activeTooltip);

    // Posicionar
    const rect = element.getBoundingClientRect();
    const tooltipRect = activeTooltip.getBoundingClientRect();

    let top, left;
    const gap = 8;

    if (element.classList.contains('bottom')) {
      top = rect.bottom + gap;
      left = rect.left + (rect.width - tooltipRect.width) / 2;
    } else if (element.classList.contains('left')) {
      top = rect.top + (rect.height - tooltipRect.height) / 2;
      left = rect.left - tooltipRect.width - gap;
    } else if (element.classList.contains('right')) {
      top = rect.top + (rect.height - tooltipRect.height) / 2;
      left = rect.right + gap;
    } else {
      // Default: top
      top = rect.top - tooltipRect.height - gap;
      left = rect.left + (rect.width - tooltipRect.width) / 2;
    }

    // Ajustar si sale de pantalla
    left = Math.max(10, Math.min(left, window.innerWidth - tooltipRect.width - 10));
    top = Math.max(10, Math.min(top, window.innerHeight - tooltipRect.height - 10));

    activeTooltip.style.top = `${top}px`;
    activeTooltip.style.left = `${left}px`;

    // Animación de entrada
    if (useAnimation) {
      gsap.fromTo(activeTooltip,
        { opacity: 0, scale: 0.9 },
        { opacity: 1, scale: 1, duration: 0.2, ease: 'power2.out' }
      );
    } else {
      activeTooltip.style.opacity = '0';
      activeTooltip.style.transition = 'opacity 0.2s ease';
      requestAnimationFrame(() => {
        if (activeTooltip) activeTooltip.style.opacity = '1';
      });
    }
  }

  function hideTooltip() {
    if (!activeTooltip) return;

    const useAnimation = hasGsap && activeElement && activeElement.classList.contains('animated');
    const tooltipToRemove = activeTooltip;
    activeTooltip = null;
    activeElement = null;

    if (useAnimation) {
      gsap.to(tooltipToRemove, {
        opacity: 0,
        scale: 0.9,
        duration: 0.15,
        ease: 'power1.in',
        onComplete: () => tooltipToRemove.remove()
      });
    } else {
      tooltipToRemove.remove();
    }
  }

  // Delegación de eventos robusta y libre de parpadeo (flicker-free)
  document.addEventListener('mouseover', (e) => {
    if (!e.target || !e.target.closest) return;
    const tooltipEl = e.target.closest('.tooltip[data-tooltip]');
    
    if (!tooltipEl) return;

    // Si ya estamos mostrando el tooltip para este elemento, ignorar
    if (activeElement === tooltipEl) return;

    showTooltip(tooltipEl);
  });

  document.addEventListener('mouseout', (e) => {
    if (!activeElement) return;

    const related = e.relatedTarget;
    
    // Si relatedTarget es válido y está dentro de activeElement (o es el tooltip mismo), ignorar
    if (related) {
      if (activeElement.contains(related) || related === activeTooltip || activeTooltip?.contains(related)) {
        return;
      }
    } else {
      // Si relatedTarget es null (a veces pasa en movimientos rápidos o bordes de SVG/texto),
      // verificamos si las coordenadas del cursor siguen dentro de las fronteras físicas de activeElement.
      const rect = activeElement.getBoundingClientRect();
      const x = e.clientX;
      const y = e.clientY;
      
      if (x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom) {
        return;
      }
    }

    hideTooltip();
  });
}
