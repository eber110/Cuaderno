/**
 * Ajuste dinámico de altura respecto al viewport o restando un elemento (ej. menú/navbar).
 *
 * @function adjustSizeHeight
 * @description Calcula y sincroniza en tiempo real la altura de los elementos `.size-height`,
 *              pudiendo descontar la altura de un menú mediante `.size-height[clase-menu]`
 *              o `data-size-subtract=".selector"` usando `100dvh` y `ResizeObserver`.
 *
 * @example
 * <div class="size-height">Contenido a altura completa</div>
 * <div class="size-height size-height[menu-principal]">Contenido restando altura del menú</div>
 *
 * @returns {void}
 */
export function adjustSizeHeight() {
  const selectors = '.size-height';
  const supportsDvh = typeof CSS !== 'undefined' && typeof CSS.supports === 'function' && CSS.supports('height', '100dvh');
  const observedMenus = new WeakSet();
  let menuObserver = null;

  const resolveMenuSelector = (element) => {
    const dataTarget = element.getAttribute('data-size-subtract');
    if (dataTarget && dataTarget.trim() !== '') {
      return dataTarget.trim();
    }

    const classAttr = element.getAttribute('class') || '';
    const match = classAttr.match(/size-height\[([^\]]+)\]/);
    if (match && match[1]) {
      const raw = match[1].trim();
      return raw.startsWith('.') || raw.startsWith('#') ? raw : `.${raw}`;
    }

    return null;
  };

  const applyHeights = () => {
    const elements = document.querySelectorAll(selectors);
    if (elements.length === 0) return;

    elements.forEach(element => {
      const menuSelector = resolveMenuSelector(element);

      if (menuSelector) {
        const menuElement = document.querySelector(menuSelector);
        const menuHeight = menuElement ? Math.round(menuElement.offsetHeight) : 0;

        if (menuElement && typeof ResizeObserver !== 'undefined' && !observedMenus.has(menuElement)) {
          if (!menuObserver) {
            menuObserver = new ResizeObserver(() => requestAnimationFrame(applyHeights));
          }
          menuObserver.observe(menuElement);
          observedMenus.add(menuElement);
        }

        element.style.height = supportsDvh
          ? `calc(100dvh - ${menuHeight}px)`
          : `${Math.max(0, window.innerHeight - menuHeight)}px`;
      } else {
        element.style.height = supportsDvh ? '100dvh' : `${window.innerHeight}px`;
      }
    });
  };

  applyHeights();

  if (typeof window !== 'undefined' && !window.__fmeAdjustSizeHeightBound) {
    window.__fmeAdjustSizeHeightBound = true;
    window.addEventListener('resize', () => requestAnimationFrame(applyHeights), { passive: true });
    window.addEventListener('load', applyHeights, { once: true });
  }
}
