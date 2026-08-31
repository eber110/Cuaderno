/**
 * Controlador para la gestión y sincronización de precios y descuentos de productos.
 * 
 * Sincroniza dinámicamente el switch de rebaja/oferta con la visibilidad de los campos
 * de descuento y porcentaje, y realiza los cálculos bidireccionales en tiempo real
 * garantizando que el porcentaje no contenga decimales y el precio rebajado no supere el precio original.
 * 
 * @function productPricingController
 * @description Maneja los eventos de cambio y entrada en items de producto.
 * @returns {void}
 */
export function productPricingController() {
  /**
   * Sincroniza la visibilidad de la sección de descuento de un contenedor de producto o sub-producto.
   *
   * @param {HTMLElement} container Contenedor (.sub-product-item o .sortable-item)
   */
  function syncDiscountVisibility(container) {
    if (!container) return;
    const switchInput = container.querySelector('.product-offer-switch');
    const discountWrapper = container.querySelector('.product-discount-wrapper');
    if (!switchInput || !discountWrapper) return;

    const isChecked = switchInput.checked || switchInput.getAttribute('active') === '1';
    discountWrapper.style.display = isChecked ? 'flex' : 'none';
  }

  /**
   * Inicializa la visibilidad de descuentos en todos los productos y sub-productos.
   */
  function initProductPricing() {
    document.querySelectorAll('.sub-product-item, .sortable-item').forEach((container) => {
      syncDiscountVisibility(container);
    });
  }

  // Inicializar al cargar
  initProductPricing();

  if (!window.__productPricingControllerInitialized) {
    window.__productPricingControllerInitialized = true;

    // Reinicializar cuando la vista previa se actualice por Fetch
    document.addEventListener('previewUpdated', () => {
      initProductPricing();
    });

    // Escuchar cambios en los switches de oferta
    document.addEventListener('change', (e) => {
      const target = e.target;
      if (!target) return;

      if (target.classList.contains('product-offer-switch') || target.closest('.product-offer-switch')) {
        const container = target.closest('.sub-product-item') || target.closest('.sortable-item');
        if (container) {
          syncDiscountVisibility(container);
        }
      }
    });

    // Sincronización en tiempo real al escribir en los inputs de precios y descuentos
    document.addEventListener('input', (e) => {
      const target = e.target;
      if (!target) return;

      const container = target.closest('.sub-product-item') || target.closest('.sortable-item');
      if (!container) return;

      const priceInput = container.querySelector('.product-price-input');
      const discountInput = container.querySelector('.product-discount-input');
      const percentageInput = container.querySelector('.product-porcentage-input');

      if (!priceInput || !discountInput || !percentageInput) return;

      const priceVal = parseFloat(priceInput.value) || 0;

      // 1. El usuario modificó el porcentaje de descuento
      if (target === percentageInput) {
        let pct = parseInt(percentageInput.value, 10);
        if (isNaN(pct) || pct < 0) {
          pct = 0;
        } else if (pct > 100) {
          pct = 100;
        }

        percentageInput.value = pct;

        if (priceVal > 0) {
          const calculatedDiscount = Math.round(priceVal * (1 - (pct / 100)));
          discountInput.value = calculatedDiscount;
        } else {
          discountInput.value = '';
        }
      }

      // 2. El usuario modificó el precio rebajado directamente
      else if (target === discountInput) {
        let discountVal = parseFloat(discountInput.value);

        if (isNaN(discountVal) || discountVal < 0) {
          discountVal = 0;
          discountInput.value = '';
          percentageInput.value = 0;
          return;
        }

        if (priceVal > 0) {
          // El precio rebajado no debe ser mayor al precio original
          if (discountVal > priceVal) {
            discountVal = priceVal;
            discountInput.value = priceVal;
          }

          const calculatedPct = Math.round(((priceVal - discountVal) / priceVal) * 100);
          percentageInput.value = Math.max(0, Math.min(100, calculatedPct));
        } else {
          percentageInput.value = 0;
        }
      }

      // 3. El usuario modificó el precio original
      else if (target === priceInput) {
        if (priceVal > 0) {
          const currentPct = parseInt(percentageInput.value, 10) || 0;
          if (currentPct > 0) {
            const calculatedDiscount = Math.round(priceVal * (1 - (currentPct / 100)));
            discountInput.value = calculatedDiscount;
          } else {
            const currentDiscount = parseFloat(discountInput.value) || 0;
            if (currentDiscount > priceVal) {
              discountInput.value = priceVal;
              percentageInput.value = 0;
            } else if (currentDiscount > 0) {
              const calculatedPct = Math.round(((priceVal - currentDiscount) / priceVal) * 100);
              percentageInput.value = Math.max(0, Math.min(100, calculatedPct));
            }
          }
        }
      }
    });
  }
}
