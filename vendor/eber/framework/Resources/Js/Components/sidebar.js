/**
 * Sidebar sticky bidireccional.
 * 
 * @function sidebar
 * @description Implementa un sidebar que se mantiene visible mientras el usuario
 *              hace scroll, ajustándose automáticamente según la dirección.
 * 
 * @example
 * // HTML
 * <aside data-sticky="true" data-top-gap="20" data-bottom-gap="20">
 *   Contenido del sidebar...
 * </aside>
 * 
 * @attribute data-sticky="true" - Activa el comportamiento sticky
 * @attribute data-top-gap - Espacio superior en px (o "auto")
 * @attribute data-bottom-gap - Espacio inferior en px
 * @attribute data-mobile-width - Ancho mínimo para activar sticky
 * 
 * @license MIT - Krzysztof Antosik
 * @version 1.7.1
 * @link https://github.com/Krzysztof-Antosik/Two-direction-Sticky-Sidebar/
 * 
 * @returns {void}
 */
export function sidebar() {
  const stickyElements = document.querySelectorAll(`[data-sticky="true"]`);

  stickyElements.forEach((stickyElement) => {
    const startPosition = stickyElement.getBoundingClientRect().top;

    let endScroll = window.innerHeight - stickyElement.offsetHeight - 500;
    let currPos = window.scrollY;
    let screenHeight = window.innerHeight;
    let stickyElementHeight = stickyElement.offsetHeight;
    let topGap = 0;
    let bottomGap = 0;
    let width = window.innerWidth;
    let mobileWidth = 0;

    setTimeout(() => {
      if (stickyElement.hasAttribute(`data-top-gap`)) {
        const dataTopGap = stickyElement.dataset.topGap;
        topGap = String(dataTopGap) === "auto" ? startPosition : parseInt(dataTopGap);
      }
      if (stickyElement.hasAttribute(`data-bottom-gap`)) {
        bottomGap = parseInt(stickyElement.dataset.bottomGap);
      }
      if (stickyElement.hasAttribute(`data-mobile-width`)) {
        mobileWidth = parseInt(stickyElement.dataset.mobileWidth);
      }
    }, 100);

    function offStickyOnMobile() {
      if (width > mobileWidth) {
        stickyElement.style.position = `sticky`;
        stickyElement.style.top = topGap + `px`;
        stickyElement.style.height = "fit-content";
      } else {
        stickyElement.removeAttribute(`style`);
      }
    }

    offStickyOnMobile();

    function positionStickySidebar() {
      endScroll = window.innerHeight - stickyElement.offsetHeight - bottomGap;
      let stickyElementTop = parseInt(stickyElement.style.top.replace(`px`, ``));

      if (stickyElementHeight + topGap + bottomGap > screenHeight) {
        if (window.scrollY < currPos) {
          // Scrolling up
          if (stickyElementTop < topGap) {
            stickyElement.style.top = (stickyElementTop + currPos - window.scrollY) + `px`;
          } else if (stickyElementTop >= topGap && stickyElementTop !== topGap) {
            stickyElement.style.top = topGap + `px`;
          }
        } else {
          // Scrolling down
          if (stickyElementTop > endScroll) {
            stickyElement.style.top = (stickyElementTop + currPos - window.scrollY) + `px`;
          } else if (stickyElementTop < endScroll && stickyElementTop !== endScroll) {
            stickyElement.style.top = endScroll + `px`;
          }
        }
      } else {
        stickyElement.style.top = topGap + `px`;
      }
      currPos = window.scrollY;
    }

    function updateSticky() {
      screenHeight = window.innerHeight;
      stickyElementHeight = stickyElement.offsetHeight;
      positionStickySidebar();
    }

    function initSticky() {
      width = window.innerWidth;
      currPos = window.scrollY;
      updateSticky();
      offStickyOnMobile();
    }

    setTimeout(() => {
      window.addEventListener(`resize`, initSticky);
      document.addEventListener(`scroll`, updateSticky, { capture: true, passive: true });
      initSticky();
    }, 300);
  });
}
