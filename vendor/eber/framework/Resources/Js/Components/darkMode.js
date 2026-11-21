/**
 * Módulo de modo oscuro para el Framework.
 * Aplica `data-theme="dark"` en `<html>`, persiste la preferencia en `localStorage` y detecta la preferencia del sistema.
 *
 * @function initDarkMode
 * @description Gestiona el cambio entre tema claro y oscuro con persistencia en `localStorage` y soporte automático para botones `.dark-mode-toggle`.
 *
 * @param {Object} [config={}] - Opciones de configuración
 * @param {string} [config.storageKey='fme-theme'] - Clave utilizada en `localStorage`
 * @param {string} [config.toggleSelector='.dark-mode-toggle'] - Selector CSS de los botones para alternar tema
 * @param {boolean} [config.autoDetect=true] - Detecta automáticamente `prefers-color-scheme: dark` si no hay preferencia guardada
 *
 * @example
 * // HTML - Botón selector de modo oscuro/claro
 * <button type="button" class="dark-mode-toggle" aria-label="Cambiar tema">
 *   <span class="dm-icon-light">☀️</span>
 *   <span class="dm-icon-dark">🌙</span>
 * </button>
 *
 * @css .dark-mode-toggle - Selector por defecto para botones que alternan el tema
 * @css .dm-icon-light - Icono visible en modo claro
 * @css .dm-icon-dark - Icono visible en modo oscuro
 * @attribute data-theme - Atributo aplicado en `<html>` (`"dark"` cuando está activo)
 *
 * @returns {void}
 */

export function initDarkMode(config = {}) {

  // Configuración por defecto
  const defaults = {
    storageKey: 'fme-theme',
    toggleSelector: '.dark-mode-toggle',
    autoDetect: true,
  };

  const options = { ...defaults, ...config };

  const DARK = 'dark';
  const LIGHT = 'light';

  // Obtiene el tema guardado o detecta preferencia del sistema
  const getSavedTheme = () => {

    // Si se fuerza un tema en la configuración de JS, usarlo sin importar lo guardado
    if (options.forceTheme) return options.forceTheme;

    const saved = localStorage.getItem(options.storageKey);

    if (saved) return saved;

    // Si no hay guardado, mirar el DEFAULT_THEME inyectado por el backend
    const defaultTheme = window.fmeDefaultTheme || 'system';

    if (defaultTheme === 'dark') {
      return DARK;
    } else if (defaultTheme === 'light') {
      return LIGHT;
    } else {
      // system / default: detectar la preferencia del navegador/sistema
      if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        return DARK;
      }
      return LIGHT;
    }

  };

  // Aplica el tema al <html>
  const applyTheme = (theme) => {

    const html = document.documentElement;

    if (theme === DARK) {
      html.setAttribute('data-theme', DARK);
    } else {
      html.removeAttribute('data-theme');
    }

  };

  // Alterna el tema y lo guarda
  const toggleTheme = () => {

    const current = document.documentElement.getAttribute('data-theme');
    const next = current === DARK ? LIGHT : DARK;

    applyTheme(next);
    localStorage.setItem(options.storageKey, next);

    // Actualiza el icono de todos los botones toggle
    updateToggleIcons(next);

  };

  // Actualiza el icono de los botones toggle según el tema activo
  const updateToggleIcons = (theme) => {

    const currentTheme = theme || (document.documentElement.getAttribute('data-theme') === DARK ? DARK : LIGHT);
    const buttons = document.querySelectorAll(options.toggleSelector);

    buttons.forEach(btn => {

      const iconLight = btn.querySelector('.dm-icon-light');
      const iconDark = btn.querySelector('.dm-icon-dark');

      if (iconLight) iconLight.style.display = currentTheme === DARK ? 'none' : 'inline-block';
      if (iconDark) iconDark.style.display = currentTheme === DARK ? 'inline-block' : 'none';

    });

  };

  // Conecta los botones toggle mediante delegación global para soportar elementos estáticos y dinámicos (responsive/AJAX)
  const bindToggleButtons = () => {

    if (document._hasDarkModeDelegate) return;
    document._hasDarkModeDelegate = true;

    // Delegación global para 'click': intercepta clicks en cualquier elemento actual o futuro
    document.addEventListener('click', (e) => {
      const btn = e.target.closest(options.toggleSelector);
      if (!btn) return;

      e.preventDefault();
      e.stopPropagation();
      toggleTheme();
    });

    // Detener propagación de touchstart en móviles para evitar que contenedores padres cierren menús o capturen el toque
    document.addEventListener('touchstart', (e) => {
      const btn = e.target.closest(options.toggleSelector);
      if (!btn) return;
      e.stopPropagation();
    }, { passive: true });

  };

  // Inicialización
  const init = () => {

    // Aplicar tema guardado antes de que el usuario interactúe
    const theme = getSavedTheme();
    applyTheme(theme);

    bindToggleButtons();
    updateToggleIcons(theme);

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        updateToggleIcons(theme);
      });
    }

    // Observador de mutaciones para actualizar iconos si elementos toggle son agregados dinámicamente o restaurados por responsive
    if (typeof MutationObserver !== 'undefined') {
      const domObserver = new MutationObserver(() => {
        const current = document.documentElement.getAttribute('data-theme') === DARK ? DARK : LIGHT;
        updateToggleIcons(current);
      });

      const rootEl = document.body || document.documentElement;
      domObserver.observe(rootEl, {
        childList: true,
        subtree: true
      });
    }

    // Escucha cambios en la preferencia del sistema (solo si no hay preferencia guardada)
    if ((options.autoDetect || (window.fmeDefaultTheme || 'system') === 'system') && window.matchMedia) {

      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {

        if (!localStorage.getItem(options.storageKey)) {
          const systemTheme = e.matches ? DARK : LIGHT;
          applyTheme(systemTheme);
          updateToggleIcons(systemTheme);
        }

      });

    }

  };

  init();

  // API pública
  return {
    toggle: toggleTheme,
    setTheme: (theme) => {
      applyTheme(theme);
      localStorage.setItem(options.storageKey, theme);
      updateToggleIcons(theme);
    },
    getTheme: () => document.documentElement.getAttribute('data-theme') || LIGHT,
  };

}

export const darkMode = initDarkMode;

