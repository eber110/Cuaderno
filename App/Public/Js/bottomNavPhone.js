/**
 * Componente Bottom Navigation Phone - Menú inferior para dispositivos móviles.
 * 
 * Gestiona la navegación por niveles en el menú inferior del dashboard:
 * - Nivel raíz: Diseño, Contenido, Estadísticas (divididos equitativamente).
 * - Nivel submenú: Botón House (volver) + sub-enlaces remotos con scroll horizontal
 *   y ancho mínimo de 80px por cuadro.
 * - Sincroniza el estado activo con el sistema de paneles remotos (remoteContent).
 * - Respeta la regla de memoria de 1 hora:
 *   - Si el usuario recarga antes de 1 hora: restaura el submenú y botón anterior.
 *   - Si transcurre más de 1 hora: vuelve al menú principal con Diseño seleccionado.
 *
 * @function bottomNavPhone
 * @description Controla la transición de menús, botón House, scroll horizontal, estado activo y memoria en móviles.
 * @returns {void}
 */

function readActiveMemory() {
  if (typeof window !== "undefined" && window.__menuMemory?.get) return window.__menuMemory.get();
  if (typeof getMenuMemory === "function") return getMenuMemory();
  return null;
}

function saveActiveMemory(remoteId) {
  if (typeof window !== "undefined" && window.__menuMemory?.set) {
    window.__menuMemory.set(remoteId);
    return;
  }
  if (typeof setMenuMemory === "function") {
    setMenuMemory(remoteId);
  }
}

export function bottomNavPhone() {
  const container = document.getElementById("bottom-nav-phone");
  if (!container) return;

  const rootTrack = document.getElementById("bottom-nav-root");
  const allTracks = container.querySelectorAll(".bottom-nav-track");
  const hasGsap = typeof gsap !== "undefined";

  /**
   * Actualiza el resaltado activo en los 3 botones del menú raíz
   * según el panel remoto actualmente activo.
   * 
   * @param {string} remoteId ID del panel remoto activo.
   */
  function updateRootActiveState(remoteId) {
    if (!rootTrack) return;

    const designBtn = rootTrack.querySelector('[data-root-group="design"]');
    const contentBtn = rootTrack.querySelector('[data-root-group="content"]');
    const statsBtn = rootTrack.querySelector('[data-remote="statistics-remote"]');

    // Limpiar clases active
    if (designBtn) designBtn.classList.remove("active");
    if (contentBtn) contentBtn.classList.remove("active");
    if (statsBtn) statsBtn.classList.remove("active");

    const designRemotes = [
      "header-remote",
      "background-remote",
      "button-remote",
      "color-remote",
      "hide-profile-remote"
    ];
    const contentRemotes = [
      "Content-button",
      "Content-rrss"
    ];

    if (designRemotes.includes(remoteId)) {
      if (designBtn) designBtn.classList.add("active");
    } else if (contentRemotes.includes(remoteId)) {
      if (contentBtn) contentBtn.classList.add("active");
    } else if (remoteId === "statistics-remote") {
      if (statsBtn) statsBtn.classList.add("active");
    } else {
      // Fallback a Diseño
      if (designBtn) designBtn.classList.add("active");
    }
  }

  /**
   * Muestra un track específico y oculta los demás.
   * 
   * @param {string} trackId ID del elemento contenedor a mostrar.
   * @param {boolean} [animate=true] Si debe aplicar transición suave.
   */
  function showTrack(trackId, animate = true) {
    const targetTrack = document.getElementById(trackId);
    if (!targetTrack) return;

    let currentTrack = null;
    allTracks.forEach((track) => {
      if (!track.classList.contains("hidden")) {
        currentTrack = track;
      }
    });

    if (currentTrack === targetTrack) return;

    const doSwitch = () => {
      allTracks.forEach((track) => {
        if (track !== targetTrack) {
          track.classList.add("hidden");
        }
      });
      targetTrack.classList.remove("hidden");

      // Auto-centrar botón activo si el submenú tiene scroll horizontal
      const activeBtn = targetTrack.querySelector(".remote-btn.active");
      if (activeBtn) {
        setTimeout(() => {
          activeBtn.scrollIntoView({ behavior: "smooth", inline: "center", block: "nearest" });
        }, 60);
      } else {
        targetTrack.scrollLeft = 0;
      }
    };

    if (hasGsap && animate && currentTrack) {
      gsap.to(currentTrack, {
        opacity: 0,
        duration: 0.15,
        ease: "power1.inOut",
        onComplete: () => {
          gsap.set(currentTrack, { clearProps: "opacity" });
          doSwitch();
          gsap.fromTo(
            targetTrack,
            { opacity: 0 },
            { opacity: 1, duration: 0.18, ease: "power1.out" }
          );
        }
      });
    } else {
      doSwitch();
    }
  }

  /**
   * Vuelve a la vista raíz (Diseño, Contenido, Estadísticas).
   */
  function showRoot() {
    showTrack("bottom-nav-root");

    // Identificar qué panel remoto está activo en el DOM para marcar el botón raíz
    const activeRemoteContent = document.querySelector(".remote-container .remote-content.active");
    const activeId = activeRemoteContent ? activeRemoteContent.id : "header-remote";
    updateRootActiveState(activeId);
  }

  /**
   * Encuentra qué submenú contiene un remoteId específico.
   * 
   * @param {string} remoteId ID del panel remoto.
   * @returns {HTMLElement|null} El elemento track del submenú o null.
   */
  function findSubmenuByRemote(remoteId) {
    if (!remoteId) return null;
    const btn = container.querySelector(`.remote-btn[data-remote="${remoteId}"]`);
    if (!btn) return null;
    return btn.closest(".bottom-nav-track");
  }

  /**
   * Sincroniza la vista y el botón activo según el panel remoto actualmente activo.
   * 
   * @param {string} remoteId ID del panel remoto.
   * @param {boolean} [animate=false] Si debe animar el cambio.
   */
  function syncActiveRemote(remoteId, animate = false) {
    if (!remoteId) return;

    // Actualizar clase active en todos los botones de la barra inferior
    container.querySelectorAll(".remote-btn").forEach((btn) => {
      if (btn.dataset.remote === remoteId) {
        btn.classList.add("active");
      } else {
        btn.classList.remove("active");
      }
    });

    const parentTrack = findSubmenuByRemote(remoteId);
    if (parentTrack) {
      showTrack(parentTrack.id, animate);
    } else {
      updateRootActiveState(remoteId);
    }
  }

  // 1. Delegación de clicks dentro del contenedor del menú inferior
  container.addEventListener("click", (e) => {
    // A. Click en botón House (Volver al menú raíz)
    const backBtn = e.target.closest("[data-bottom-back]");
    if (backBtn) {
      e.preventDefault();
      showRoot();
      return;
    }

    // B. Click en botón de apertura de submenú (Diseño / Contenido)
    const targetTrigger = e.target.closest("[data-bottom-target]");
    if (targetTrigger) {
      e.preventDefault();
      const targetSubmenuId = targetTrigger.dataset.bottomTarget;
      const targetSubmenu = document.getElementById(targetSubmenuId);
      if (targetSubmenu) {
        showTrack(targetSubmenuId, true);

        // Si no hay un botón activo en el submenú, seleccionar el primero
        const currentActive = targetSubmenu.querySelector(".remote-btn.active");
        if (!currentActive) {
          const firstRemote = targetSubmenu.querySelector(".remote-btn");
          if (firstRemote) {
            firstRemote.click();
          }
        } else if (currentActive.dataset.remote) {
          saveActiveMemory(currentActive.dataset.remote);
        }
      }
      return;
    }

    // C. Click en botón remoto (.remote-btn)
    const remoteBtn = e.target.closest(".remote-btn");
    if (remoteBtn) {
      const remoteId = remoteBtn.dataset.remote;
      if (remoteId) {
        // Asegurar resaltado activo en la barra inferior
        container.querySelectorAll(".remote-btn").forEach((b) => b.classList.remove("active"));
        remoteBtn.classList.add("active");

        // Guardar en la memoria unificada con 1 hora de TTL
        saveActiveMemory(remoteId);

        // Si está en un track con scroll, centrarlo suavemente
        const parentTrack = remoteBtn.closest(".bottom-nav-track");
        if (parentTrack && parentTrack !== rootTrack) {
          setTimeout(() => {
            remoteBtn.scrollIntoView({ behavior: "smooth", inline: "center", block: "nearest" });
          }, 50);
        } else {
          updateRootActiveState(remoteId);
        }
      }
    }
  });

  // 2. Escuchar clicks globales en cualquier .remote-btn para sincronizar la barra inferior
  document.addEventListener("click", (e) => {
    const externalRemoteBtn = e.target.closest(".remote-btn");
    if (!externalRemoteBtn) return;
    if (container.contains(externalRemoteBtn)) return; // ya gestionado

    const remoteId = externalRemoteBtn.dataset.remote;
    if (remoteId) {
      syncActiveRemote(remoteId, false);
    }
  });

  // 3. Inicialización: validar memoria de 1 hora
  function initDefaultActive() {
    // A. Consultar memoria con expiración de 1 hora
    const savedRemoteId = readActiveMemory();

    if (savedRemoteId) {
      // Memoria vigente (< 1 hora): restaurar el menú y panel en el que estaba
      syncActiveRemote(savedRemoteId, false);
    } else {
      // Memoria expirada (> 1 hora) o primera visita:
      // Volver a la pantalla principal por defecto (Diseño)
      showRoot();
      updateRootActiveState("header-remote");
    }
  }

  // Inicializar en el próximo tick del navegador para asegurar que el DOM esté listo
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initDefaultActive);
  } else {
    setTimeout(initDefaultActive, 20);
  }
}
