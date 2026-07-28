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

    const buttons = document.querySelectorAll(options.toggleSelector);

    buttons.forEach(btn => {

      const iconLight = btn.querySelector('.dm-icon-light');
      const iconDark = btn.querySelector('.dm-icon-dark');

      if (iconLight) iconLight.style.display = theme === DARK ? 'none' : 'inline-block';
      if (iconDark) iconDark.style.display = theme === DARK ? 'inline-block' : 'none';

    });

  };

  // Conecta los botones toggle que estén en el DOM
  const bindToggleButtons = () => {

    const buttons = document.querySelectorAll(options.toggleSelector);

    buttons.forEach(btn => {
      btn.addEventListener('click', toggleTheme);
    });

  };

  // Inicialización
  const init = () => {

    // Aplicar tema guardado antes de que el usuario interactúe
    const theme = getSavedTheme();
    applyTheme(theme);

    document.addEventListener('DOMContentLoaded', () => {

      bindToggleButtons();
      updateToggleIcons(theme);

    });

    // Si el DOM ya está listo
    if (document.readyState !== 'loading') {
      bindToggleButtons();
      updateToggleIcons(theme);
    }

    // Escucha cambios en la preferencia del sistema (solo si no hay preferencia guardada)
    if (options.autoDetect || (window.fmeDefaultTheme || 'system') === 'system') {

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

