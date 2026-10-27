/**
 * Módulo de modo oscuro para el Framework FME
 * Aplica data-theme en <html>, persiste en localStorage y detecta preferencia del sistema.
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

