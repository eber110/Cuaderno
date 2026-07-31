/**
 * Componente shareModalColor.
 * 
 * Analiza el color predominante de la imagen en los modales de compartir (modalMenuShare y modalUserShare).
 * Aplica la fórmula OKLCH solicitada para transformar dinámicamente el fondo de la tarjeta:
 * oklch(from rgb(r,g,b) calc(l * 1.15) calc(c - 0.03) calc(h - 30) / 90%)
 * 
 * - Si el color predominante es CLARO (luminancia > 0.65): fija el fondo a #f3f3f1 y el texto a negro.
 * - Si el color es OSCURO / MEDIO: aplica la fórmula OKLCH sobre el color predominante y texto blanco.
 */
export function shareModalColor() {
  function processShareCard(card) {
    const img = card.querySelector(".js-share-card-img");
    if (!img || !img.src) return;

    // Probar lectura de color en una imagen temporal aislada en memoria
    const tempImg = new Image();
    tempImg.crossOrigin = "anonymous";

    tempImg.onload = function () {
      try {
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");
        const w = 30;
        const h = 30;
        canvas.width = w;
        canvas.height = h;

        ctx.drawImage(tempImg, 0, 0, w, h);
        const imageData = ctx.getImageData(0, 0, w, h).data;

        let totalR = 0;
        let totalG = 0;
        let totalB = 0;
        let count = 0;

        for (let i = 0; i < imageData.length; i += 4) {
          const alpha = imageData[i + 3];
          if (alpha < 128) continue; // Ignorar píxeles transparentes
          totalR += imageData[i];
          totalG += imageData[i + 1];
          totalB += imageData[i + 2];
          count++;
        }

        if (count === 0) return;

        const r = Math.round(totalR / count);
        const g = Math.round(totalG / count);
        const b = Math.round(totalB / count);

        // Calcular luminancia relativa (0.299R + 0.587G + 0.114B)
        const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

        if (luminance > 0.65) {
          // Color claro: fondo #f3f3f1 y texto negro
          card.style.setProperty("background-color", "#f3f3f1", "important");
          card.style.setProperty("color", "#000000", "important");
          const urlEl = card.querySelector(".js-share-card-url");
          if (urlEl) urlEl.style.setProperty("color", "#000000", "important");
        } else {
          // Color oscuro/medio: aplicar fórmula OKLCH sobre el color predominante extraído
          card.style.setProperty("background-color", `oklch(from rgb(${r}, ${g}, ${b}) calc(l * 1.15) calc(c - 0.03) calc(h - 30) / 90%)`, "important");
          card.style.setProperty("color", "#ffffff", "important");
          const urlEl = card.querySelector(".js-share-card-url");
          if (urlEl) urlEl.style.setProperty("color", "#ffffff", "important");
        }
      } catch (err) {
        // Fallback silencioso
      }
    };

    tempImg.onerror = function () {
      // Fallback silencioso sin errores bloqueantes en consola
    };

    tempImg.src = img.src;
  }

  function init() {
    document.querySelectorAll(".js-share-card-color").forEach(processShareCard);
  }

  init();

  document.addEventListener("click", (e) => {
    if (e.target.closest(".modal-btn, [data-modal]")) {
      setTimeout(init, 80);
    }
  });

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.addedNodes.length > 0) {
        init();
      }
    });
  });

  observer.observe(document.body, { childList: true, subtree: true });
}
