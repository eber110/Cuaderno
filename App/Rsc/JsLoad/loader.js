/**
 * loader.js
 * Cargador unificado de funciones JS
 * Lee la configuración desde window.jsConfig (incrustado por PHP)
 * y ejecuta las funciones según su modo (async/defer)
 */
import * as functionModule from "/App/Public/Min/Js/js.min.js";

/**
 * Ejecuta las funciones configuradas para un modo específico
 * @param {Object} config - Configuración de funciones
 */
async function executeFunctions(config) {
  if (!config || typeof config !== 'object') {
    return;
  }

  for (const functionName in config) {
    const args = config[functionName];

    if (typeof functionModule[functionName] === 'function') {
      try {
        await functionModule[functionName](...args);
      } catch (error) {
        console.error(`Error ejecutando "${functionName}":`, error);
      }
    } else {
      console.warn(`ADVERTENCIA: La función "${functionName}" no existe en el módulo.`);
    }
  }
}

/**
 * Inicializa el loader según el modo de carga del script
 */
async function initLoader() {
  // Verificar si la configuración está disponible
  if (!window.jsConfig) {
    console.error("Error: window.jsConfig no está definido. Verifica que inlineJsConfig() se ejecute antes de este script.");
    return;
  }

  const config = window.jsConfig;
  const functions = config.functions || {};

  // Determinar el modo de carga del script actual
  const currentScript = document.currentScript;
  const isAsync = currentScript?.hasAttribute('async');
  const isDefer = currentScript?.hasAttribute('defer');

  // Ejecutar funciones según el modo
  if (isAsync && functions.async) {
    await executeFunctions(functions.async);
  }

  if (isDefer && functions.defer) {
    await executeFunctions(functions.defer);
  }

  // Si no tiene ningún atributo o tiene ambos, ejecutar defer por defecto
  if (!isAsync && !isDefer && functions.defer) {
    await executeFunctions(functions.defer);
  }

  // Remover la clase de carga una vez finalizada la inicialización de todos los módulos (Anti-FOUC)
  document.documentElement.classList.remove('js-loading');
}

// Ejecutar el loader
initLoader();
