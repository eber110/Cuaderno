/**
 * Code Block - Módulo de bloques de código.
 *
 * @function codeBlock
 * @description Envuelve cada elemento <code> dentro de .post-content
 *              con un wrapper visual que incluye una barra de cabecera
 *              con el nombre del lenguaje y un botón para copiar el código.
 *
 *              Detecta el lenguaje en este orden:
 *              1. Atributo data-lang="js"
 *              2. Clase CSS language-* o lang-* (ej: class="language-typescript")
 *              3. Fallback: "código"
 *
 * @example
 * // HTML básico — sin lenguaje
 * <code>const x = 1;</code>
 *
 * // HTML con lenguaje vía clase
 * <code class="language-typescript">const x: number = 1;</code>
 *
 * // HTML con lenguaje vía data-attr
 * <code data-lang="php">echo "hola";</code>
 *
 * @css .code-block        — Wrapper principal
 * @css .code-block-header — Barra superior
 * @css .code-block-lang   — Label del lenguaje
 * @css .code-copy-btn     — Botón copiar
 * @css .copied            — Estado al haber copiado (se agrega 2s)
 *
 * @returns {void}
 */
export function codeBlock() {

  // Seleccionar solo los <code> directos dentro de .post-content
  const codeElements = document.querySelectorAll('.post-content code');

  // Verificar que existan elementos antes de continuar
  if (!codeElements.length) return;

  codeElements.forEach((code) => {

    // Saltar si ya fue procesado
    if (code.closest('.code-block')) return;

    // Saltar <code> inline (dentro de párrafo, li o td)
    const parent = code.parentElement;
    const inlineParents = ['P', 'LI', 'TD', 'TH', 'SPAN', 'A'];
    if (inlineParents.includes(parent?.tagName)) return;

    // Detectar el lenguaje
    const lang = detectLanguage(code);

    // Formatear JSON si el lenguaje es json o si el contenido es JSON válido
    const trimmedText = code.textContent.trim();
    if (lang.toLowerCase() === 'json' || trimmedText.startsWith('{') || trimmedText.startsWith('[')) {
      try {
        const parsed = JSON.parse(trimmedText);
        code.textContent = JSON.stringify(parsed, null, 2);
      } catch (e) {
        // Ignorar si no es JSON completamente válido
      }
    }

    // Asegurar que el contenido esté dentro de <pre>
    const pre = wrapInPre(code);

    // Eliminar la indentación común heredada del HTML
    dedent(code);

    // Crear el wrapper .code-block
    const wrapper = document.createElement('div');
    wrapper.className = 'code-block';

    // Crear la cabecera
    const header = buildHeader(lang, code);

    // Insertar en el DOM antes del <pre> original
    pre.parentNode.insertBefore(wrapper, pre);
    wrapper.appendChild(header);
    wrapper.appendChild(pre);

  });

  // Delegar el click del botón copiar
  setupCopyListener();

}


/**
 * Detecta el lenguaje desde data-lang, clase CSS o fallback.
 * @param {HTMLElement} code
 * @returns {string}
 */
function detectLanguage(code) {

  // Prioridad 1: data-lang
  if (code.dataset.lang) {
    return code.dataset.lang.trim();
  }

  // Prioridad 2: clase language-* o lang-*
  const classList = Array.from(code.classList);
  for (const cls of classList) {
    const match = cls.match(/^(?:language|lang)-(.+)$/i);
    if (match) return match[1];
  }

  // Fallback
  return 'código';

}


/**
 * Si el <code> no está dentro de un <pre>, lo envuelve.
 * Si ya está en un <pre>, lo devuelve tal cual.
 * @param {HTMLElement} code
 * @returns {HTMLPreElement}
 */
function wrapInPre(code) {

  if (code.parentElement?.tagName === 'PRE') {
    return code.parentElement;
  }

  const pre = document.createElement('pre');
  code.replaceWith(pre);
  pre.appendChild(code);
  return pre;

}


/**
 * Elimina la indentación común (dedent) del contenido del <code>.
 * Muy útil cuando el <code> está escrito con tabulación HTML en el .php.
 *
 * @example
 * // Entra con 4 espacios de indentación:
 * "    function foo() {\n    }\n"
 * // Sale sin indentación extra:
 * "function foo() {\n}\n"
 *
 * @param {HTMLElement} code
 * @returns {void}
 */
function dedent(code) {

  const raw = code.textContent;

  // Separar en líneas
  let lines = raw.split('\n');

  // Calcular el mínimo de espacios/tabs al inicio ignorando líneas vacías
  const minIndent = lines.reduce((min, line) => {
    // Ignorar líneas vacías o que solo tienen espacios
    if (!line.trim()) return min;

    const indent = line.match(/^(\s*)/)[1].length;
    return Math.min(min, indent);
  }, Infinity);

  // Eliminar la indentación común si existe
  if (isFinite(minIndent) && minIndent > 0) {
    lines = lines.map(line => line.slice(minIndent));
  }

  // Compactar la indentación: convertir grupos de 4 espacios a 2 espacios y tabs a 2 espacios
  const compacted = lines.map(line => {
    return line.replace(/^(\s+)/, (match) => {
      let spaceString = match.replace(/\t/g, '  '); // 1 tab -> 2 espacios
      return spaceString.replace(/ {4}/g, '  ');    // 4 espacios -> 2 espacios
    });
  });

  // Re-ensamblar el texto
  const processed = compacted
    .join('\n')
    .replace(/^\n/, '')   // quitar salto inicial si lo hay
    .replace(/\n$/, '');  // quitar salto final si lo hay

  code.textContent = processed;

}


/**
 * Construye la cabecera del bloque con label y botón copiar.
 * @param {string} lang
 * @param {HTMLElement} code
 * @returns {HTMLElement}
 */
function buildHeader(lang, code) {


  const header = document.createElement('div');
  header.className = 'code-block-header';

  // Label del lenguaje
  const langLabel = document.createElement('span');
  langLabel.className = 'code-block-lang';
  langLabel.textContent = lang;

  // Botón copiar con icono SVG
  const copyBtn = document.createElement('button');
  copyBtn.className = 'code-copy-btn copy-btn';
  copyBtn.setAttribute('aria-label', 'Copiar código');
  copyBtn.innerHTML = `
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
      <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
    </svg>
    Copiar
  `;

  // Guardar referencia al bloque de código
  copyBtn._codeRef = code;

  header.appendChild(langLabel);
  header.appendChild(copyBtn);
  return header;

}


/**
 * Configura el listener de copiar usando delegación de eventos.
 * Opera sobre todos los .code-copy-btn del documento.
 */
function setupCopyListener() {

  document.addEventListener('click', async (e) => {

    if (!e.target?.closest) return;
    const btn = e.target.closest('.code-copy-btn');
    if (!btn) return;

    // Obtener el texto a copiar
    let textToCopy = '';

    // Si el botón tiene referencia directa al <code>
    if (btn._codeRef) {

      textToCopy = btn._codeRef.textContent || '';

    } else {

      // Buscar el <code> dentro del mismo .code-block
      const block = btn.closest('.code-block');
      if (block) {
        textToCopy = block.querySelector('code')?.textContent || '';
      }

    }

    if (!textToCopy.trim()) return;

    // Copiar
    await copyText(textToCopy.trim());

    // Feedback visual
    showCopied(btn);

  });

}


/**
 * Copia el texto al portapapeles.
 * @param {string} text
 */
async function copyText(text) {

  if (navigator.clipboard?.writeText) {

    try {
      await navigator.clipboard.writeText(text);
    } catch {
      copyFallback(text);
    }

  } else {
    copyFallback(text);
  }

}


/**
 * Método alternativo de copia para navegadores sin Clipboard API.
 * @param {string} text
 */
function copyFallback(text) {

  const textarea = document.createElement('textarea');
  textarea.value = text;
  textarea.style.cssText = 'position:fixed;opacity:0;pointer-events:none;';
  document.body.appendChild(textarea);
  textarea.select();
  document.execCommand('copy');
  textarea.remove();

}


/**
 * Muestra el feedback de "copiado" en el botón durante 2 segundos.
 * @param {HTMLButtonElement} btn
 */
function showCopied(btn) {

  const originalHTML = btn.innerHTML;
  btn.innerHTML = `
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <polyline points="20 6 9 17 4 12"></polyline>
    </svg>
    Copiado
  `;
  btn.classList.add('copied');

  setTimeout(() => {
    btn.innerHTML = originalHTML;
    btn.classList.remove('copied');
  }, 2000);

}
