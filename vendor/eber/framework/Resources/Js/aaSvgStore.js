// aaSvgStore.js

const svgCache = new Map();

function escapeSvgAttr(val) {
  return String(val ?? '')
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function sanitizeSvgClasses(classNames) {
  return String(classNames ?? '')
    .split(/\s+/)
    .filter(c => /^[a-zA-Z0-9_-]+$/.test(c))
    .join(' ');
}

function isSafeIconName(name) {
  if (typeof name !== 'string' || name.length === 0 || name.length > 120) {
    return false;
  }
  if (name.includes('..') || name.startsWith('/') || name.includes('\\')) {
    return false;
  }
  return /^[a-zA-Z0-9_/-]+$/.test(name);
}

/**
 * Construye el HTML de icono de error SVG de respaldo.
 *
 * @param {string} classNames Clases CSS adicionales
 * @returns {string} HTML del SVG de error
 */
function buildErrorSvg(classNames = '') {
  const safeClasses = sanitizeSvgClasses(classNames);
  return `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="svg-error-icon ${safeClasses}" style="width: 1em;height: 1em;vertical-align: middle;display: inline-block;flex-shrink: 0;">
      <circle cx="12" cy="12" r="10" stroke="red" stroke-width="2" fill="none"></circle>
      <line x1="15" y1="9" x2="9" y2="15" stroke="red" stroke-width="2"></line>
      <line x1="9" y1="9" x2="15" y2="15" stroke="red" stroke-width="2"></line>
    </svg>
  `;
}

/**
 * Obtiene y cachea en memoria los metadatos de un archivo SVG (`viewBox` y `pathData`).
 *
 * @param {string} name_icon Nombre del icono sin extensión
 * @returns {Promise<{viewBox: string, pathData: string}|null>}
 */
async function loadSvgData(name_icon) {
  if (!isSafeIconName(name_icon)) {
    return null;
  }
  if (svgCache.has(name_icon)) {
    return svgCache.get(name_icon);
  }

  const ROUTE_ICON_BASE = '/App/Rsc/Ico/';
  const route_svg = `${ROUTE_ICON_BASE}${name_icon}.svg`;

  const loadPromise = (async () => {
    const response = await fetch(route_svg);
    if (!response.ok) {
      console.warn(`SVG '${name_icon}' no encontrado o error al cargar en: ${route_svg}`);
      return null;
    }

    const content_svg = await response.text();
    const parser = new DOMParser();
    const doc = parser.parseFromString(content_svg, 'image/svg+xml');
    const svgElement = doc.querySelector('svg');

    if (!svgElement) {
      console.error(`Contenido SVG inválido para '${name_icon}': no se encontró el elemento <svg> raíz.`);
      return null;
    }

    const viewBox = svgElement.getAttribute('viewBox') || '0 0 24 24';
    const pathElement = svgElement.querySelector('path');
    let pathData = '';

    if (pathElement && pathElement.hasAttribute('d')) {
      pathData = pathElement.getAttribute('d');
    } else {
      console.warn(`No se encontró el atributo 'd' en el primer path del SVG '${name_icon}'.`);
    }

    return { viewBox, pathData };
  })();

  svgCache.set(name_icon, loadPromise);

  try {
    const result = await loadPromise;
    if (!result) {
      svgCache.delete(name_icon);
    }
    return result;
  } catch (error) {
    svgCache.delete(name_icon);
    throw error;
  }
}

/**
 * Carga el contenido de un archivo SVG (con caché en memoria), extrae su viewBox y el primer path,
 * y devuelve un nuevo elemento SVG con personalizaciones.
 * Asume que los archivos SVG están en '/App/Rsc/Ico/' y tienen la extensión '.svg'.
 *
 * @param {string} name_icon El nombre del archivo SVG (sin extensión).
 * @param {string} [classNames=''] Clases personalizadas a añadir al SVG.
 * @param {string} [transform=''] Valor para el atributo 'transform' del primer path del SVG.
 * @returns {Promise<string>} HTML del SVG como string, o un SVG de icono de error si falla.
 */
export async function svg(name_icon, classNames = '', transform = '') {
  try {
    const data = await loadSvgData(name_icon);
    if (!data) {
      return buildErrorSvg(classNames);
    }

    const safeClasses = sanitizeSvgClasses(classNames);
    const safeViewBox = escapeSvgAttr(data.viewBox);
    const safePath = escapeSvgAttr(data.pathData);
    const safeTransform = transform ? escapeSvgAttr(transform) : '';

    return `
      <svg xmlns="http://www.w3.org/2000/svg"
           viewBox="${safeViewBox}" fill="currentColor" class="svg-style ${safeClasses}" style="width: 1em;height: 1em;vertical-align: middle;display: inline-block;flex-shrink: 0;">
        <path ${safeTransform ? `transform="${safeTransform}"` : ''} d="${safePath}" />
      </svg>`;
  } catch (error) {
    console.error(`Error procesando SVG '${name_icon}':`, error);
    return buildErrorSvg(classNames);
  }
}
