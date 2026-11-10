/**
 * Utilidad de internacionalización (i18n) para JavaScript del cliente.
 *
 * Lee los textos inyectados en <script type="application/json" id="i18n">
 * por LangModule::renderJsTranslations() y expone la función global y modular t().
 *
 * @module lang
 */

let cachedDictionary = null;

/**
 * Carga y parsea de forma perezosa el diccionario JSON desde el DOM.
 *
 * @returns {{ locale: string, fallback: string, messages: Record<string, any> }}
 */
function loadI18nDictionary() {
  if (cachedDictionary !== null) {
    return cachedDictionary;
  }

  const fallbackState = {
    locale: document.documentElement?.lang || 'es',
    fallback: 'es',
    messages: {}
  };

  const scriptEl = document.getElementById('i18n');
  if (!scriptEl || !scriptEl.textContent) {
    cachedDictionary = fallbackState;
    return cachedDictionary;
  }

  try {
    const parsed = JSON.parse(scriptEl.textContent);
    if (parsed && typeof parsed === 'object') {
      cachedDictionary = {
        locale: parsed.locale || fallbackState.locale,
        fallback: parsed.fallback || fallbackState.fallback,
        messages: parsed.messages && typeof parsed.messages === 'object' ? parsed.messages : {}
      };
      return cachedDictionary;
    }
  } catch (err) {
    console.warn('i18n: No se pudo parsear el bloque JSON #i18n', err);
  }

  cachedDictionary = fallbackState;
  return cachedDictionary;
}

/**
 * Traduce una clave del diccionario JS e interpola parámetros ':param'.
 *
 * Si la clave comienza con 'js.', se omite el prefijo automáticamente para buscar en messages.
 *
 * @function t
 * @description Obtiene un texto traducido en el cliente con soporte de interpolación y fallback.
 * @param {string} key - Clave de traducción (ej: 'copy' o 'js.copy')
 * @param {Record<string, string|number>} [params={}] - Parámetros a interpolar (ej: { count: 3 })
 * @param {string|null} [defaultText=null] - Texto de respaldo si la clave no existe
 * @returns {string} Texto traducido e interpolado
 *
 * @example
 * t('copy'); // "Copiar" o "Copy"
 * t('files_many', { count: 5 }, '5 archivos');
 */
export function t(key, params = {}, defaultText = null) {
  if (!key || typeof key !== 'string') {
    return defaultText ?? '';
  }

  const dict = loadI18nDictionary();
  const cleanKey = key.startsWith('js.') ? key.slice(3) : key;

  let value = dict.messages[cleanKey];

  if (value === undefined && cleanKey.includes('.')) {
    const parts = cleanKey.split('.');
    let cursor = dict.messages;
    for (const part of parts) {
      if (cursor && typeof cursor === 'object' && part in cursor) {
        cursor = cursor[part];
      } else {
        cursor = undefined;
        break;
      }
    }
    value = cursor;
  }

  let text = typeof value === 'string' || typeof value === 'number'
    ? String(value)
    : (defaultText !== null ? String(defaultText) : key);

  if (params && typeof params === 'object' && text.includes(':')) {
    const sortedKeys = Object.keys(params).sort((a, b) => b.length - a.length);
    for (const k of sortedKeys) {
      const token = ':' + k.replace(/^:/, '');
      text = text.split(token).join(String(params[k]));
    }
  }

  return text;
}

// Exponer globalmente en window para componentes o scripts inline
if (typeof window !== 'undefined' && typeof window.t !== 'function') {
  window.t = t;
}
