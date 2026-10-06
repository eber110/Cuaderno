/**
 * Módulo de Gestión de Memoria del Menú con Expiración.
 * 
 * Homologa la persistencia del menú seleccionado en Desktop, Tablet y Móvil:
 * - Guarda el panel remoto activo con timestamp en localStorage.
 * - Límite de vida (TTL): 1 hora (3.600.000 ms).
 * - Si el usuario navega o recarga antes de 1 hora, restaura el menú anterior.
 * - Si transcurre más de 1 hora, descarta la memoria y devuelve al menú por defecto (Diseño).
 *
 * @function menuMemory
 * @description Administrador de memoria con TTL para menús de navegación en el Dashboard.
 * @returns {void}
 */

export const MENU_MEMORY_TTL = 60 * 60 * 1000; // 1 hora en ms

/**
 * Obtiene la clave de almacenamiento base para la ruta actual.
 * @returns {string}
 */
export function getMenuMemoryKey() {
  return `vertical_menu_active_${window.location.pathname}`;
}

/**
 * Obtiene el panel remoto guardado si no ha expirado.
 * 
 * @returns {string|null} ID del panel remoto activo o null si expiró/no existe.
 */
export function getMenuMemory() {
  const baseKey = getMenuMemoryKey();
  const saved = localStorage.getItem(baseKey) || localStorage.getItem(baseKey + "_default");
  if (!saved) return null;

  try {
    const data = JSON.parse(saved);
    if (!data || !data.remote) {
      clearMenuMemory();
      return null;
    }

    const now = Date.now();
    if (typeof data.timestamp === "number" && (now - data.timestamp < MENU_MEMORY_TTL)) {
      return data.remote;
    } else {
      clearMenuMemory();
      return null;
    }
  } catch (_) {
    clearMenuMemory();
    return null;
  }
}

/**
 * Guarda el menú seleccionado en localStorage con timestamp actual.
 * Aplica para desktop, tablet y móvil.
 * 
 * @param {string} remoteId ID del panel remoto (ej: "header-remote", "statistics-remote").
 * @returns {void}
 */
export function setMenuMemory(remoteId) {
  if (!remoteId) return;
  const baseKey = getMenuMemoryKey();
  const payload = JSON.stringify({
    remote: remoteId,
    timestamp: Date.now()
  });

  try {
    localStorage.setItem(baseKey, payload);
    localStorage.setItem(baseKey + "_default", payload);
  } catch (_) {}
}

/**
 * Limpia la memoria del menú en localStorage.
 * @returns {void}
 */
export function clearMenuMemory() {
  const baseKey = getMenuMemoryKey();
  try {
    localStorage.removeItem(baseKey);
    localStorage.removeItem(baseKey + "_default");
  } catch (_) {}
}

// Exponer en window para interoperabilidad en el bundle unificado
if (typeof window !== "undefined") {
  window.__menuMemory = {
    get: getMenuMemory,
    set: setMenuMemory,
    clear: clearMenuMemory,
    key: getMenuMemoryKey,
    TTL: MENU_MEMORY_TTL
  };
}

/**
 * Inicializador del componente menuMemory.
 * Registra listeners de clics para capturar cualquier cambio de menú en Desktop, Tablet y Móvil.
 */
export function menuMemory() {
  // Delegación global: cada clic en un botón remoto o enlace de menú actualiza la memoria con nuevo timestamp
  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".remote-btn, .vertical-menu-link");
    if (!btn) return;

    const remoteId = btn.dataset.remote;
    if (remoteId) {
      setMenuMemory(remoteId);
    }
  });

  // Validar al iniciar si la memoria está vencida para limpiarla
  const activeRemote = getMenuMemory();
  if (!activeRemote) {
    clearMenuMemory();
  }
}
