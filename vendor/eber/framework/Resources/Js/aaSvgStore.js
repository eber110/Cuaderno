//svg-module.js

/**
 * Carga el contenido de un archivo SVG, extrae su viewBox y el primer path,
 * y devuelve un nuevo elemento SVG con personalizaciones.
 * Asume que los archivos SVG están en el directorio '/assets/svg/'
 * y tienen la extensión '.svg'.
 *
 * @param {string} name_icon El nombre del archivo SVG (sin extensión).
 * @param {string} [classNames=''] Clases personalizadas a añadir al SVG.
 * @param {string} [transform=''] Valor para el atributo 'transform' del primer path del SVG.
 * @returns {Promise<string>} Una promesa que resuelve con el HTML del SVG como string,
 *                            o un SVG de icono de error si falla la carga o el procesamiento.
 */
export async function svg(name_icon, classNames = '', transform = '') {
  const ROUTE_ICON_BASE = '/App/Rsc/Ico/'; // Define la ruta base de tus iconos SVG
  const route_svg = `${ROUTE_ICON_BASE}${name_icon}.svg`;

  try {
    const response = await fetch(route_svg);

    if (!response.ok) {
      // Si el archivo no existe o no se puede cargar, retorna un SVG de error
      console.warn(`SVG '${name_icon}' no encontrado o error al cargar en: ${route_svg}`);
      return `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="svg-error-icon ${classNames}" style="width: 1em;height: 1em;vertical-align: middle;display: inline-block;flex-shrink: 0;">
          <circle cx="12" cy="12" r="10" stroke="red" stroke-width="2" fill="none"></circle>
          <line x1="15" y1="9" x2="9" y2="15" stroke="red" stroke-width="2"></line>
          <line x1="9" y1="9" x2="15" y2="15" stroke="red" stroke-width="2"></line>
        </svg>
      `;
    }

    const content_svg = await response.text();

    // Usaremos DOMParser para analizar el SVG de forma similar a DOMDocument en PHP
    const parser = new DOMParser();
    const doc = parser.parseFromString(content_svg, 'image/svg+xml');
    const svgElement = doc.querySelector('svg');

    if (!svgElement) {
      console.error(`Contenido SVG inválido para '${name_icon}': no se encontró el elemento <svg> raíz.`);
      return `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="svg-error-icon ${classNames}" style="width: 1em;height: 1em;vertical-align: middle;display: inline-block;flex-shrink: 0;">
          <circle cx="12" cy="12" r="10" stroke="red" stroke-width="2" fill="none"></circle>
          <line x1="15" y1="9" x2="9" y2="15" stroke="red" stroke-width="2"></line>
          <line x1="9" y1="9" x2="15" y2="15" stroke="red" stroke-width="2"></line>
        </svg>
      `;
    }

    const viewBox = svgElement.getAttribute('viewBox') || '0 0 24 24';
    const pathElement = svgElement.querySelector('path'); // Obtenemos el primer path, igual que en tu PHP

    let pathData = '';
    if (pathElement && pathElement.hasAttribute('d')) {
      pathData = pathElement.getAttribute('d');
    } else {
      console.warn(`No se encontró el atributo 'd' en el primer path del SVG '${name_icon}'.`);
      // Puedes decidir si quieres un SVG de error o un SVG vacío en este caso
    }

    // Reconstruye el SVG con los parámetros dados
    const return_svg = `
      <svg xmlns="http://www.w3.org/2000/svg"
           viewBox="${viewBox}" fill="currentColor" class="svg-style ${classNames}" style="width: 1em;height: 1em;vertical-align: middle;display: inline-block;flex-shrink: 0;">
        <path ${transform ? `transform="${transform}"` : ''} d="${pathData}" />
      </svg>`;

    return return_svg;

  } catch (error) {
    console.error(`Error procesando SVG '${name_icon}':`, error);
    // Retorna un SVG de error genérico si ocurre una excepción
    return `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="svg-error-icon ${classNames}" style="width: 1em;height: 1em;vertical-align: middle;display: inline-block;flex-shrink: 0;">
        <circle cx="12" cy="12" r="10" stroke="red" stroke-width="2" fill="none"></circle>
        <line x1="15" y1="9" x2="9" y2="15" stroke="red" stroke-width="2"></line>
        <line x1="9" y1="9" x2="15" y2="15" stroke="red" stroke-width="2"></line>
      </svg>
    `;
  }
}