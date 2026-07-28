/**
 * Botón flotante para volver al inicio de la página.
 * 
 * @function toUp
 * @description Crea un botón "scroll to top" que aparece cuando el usuario
 *              ha scrolleado más allá de una pantalla de altura.
 * 
 * @param {string} [class_style=''] - Clases CSS adicionales para el botón
 * 
 * @example
 * // En jsConfig.json
 * "toUp": ["color2 flex row-direction center-center shine-hover"]
 * 
 * @returns {void}
 */
export function toUp(class_style = '') {
  const newDiv = document.createElement('div');
  const createPost = document.querySelector('#create-post');
  newDiv.id = 'toUp';

  // Estilos base
  newDiv.style.opacity = '0';
  newDiv.style.position = 'fixed';
  newDiv.style.bottom = createPost !== null ? '70px' : '20px';
  newDiv.style.cursor = 'pointer';
  newDiv.style.right = '10px';
  newDiv.style.width = '45px';
  newDiv.style.display = "none";
  newDiv.className = `btn ${class_style}`;
  newDiv.style.borderRadius = '50%';
  newDiv.style.aspectRatio = '1/1';
  newDiv.style.border = 'solid 1px white';
  newDiv.style.zIndex = '1000';
  newDiv.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="svg-style" viewBox="0 0 384 512" fill="currentColor"><path d="M214.6 41.4c-12.5-12.5-32.8-12.5-45.3 0l-160 160c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L160 141.2 160 448c0 17.7 14.3 32 32 32s32-14.3 32-32l0-306.7L329.4 246.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-160-160z"/></svg>';

  document.body.appendChild(newDiv);

  let mouseTimeout;
  let isVisible = false;
  let lastScrollY = window.scrollY;

  function mostrarDiv() {
    newDiv.style.display = "flex";
    setTimeout(() => {
      newDiv.style.opacity = '1';
      isVisible = true;
    }, 10);
  }

  function ocultarDiv() {
    newDiv.style.opacity = '0';
    setTimeout(() => {
      newDiv.style.display = "none";
      isVisible = false;
    }, 500);
  }

  function manejarScroll() {
    const scrollY = window.scrollY;
    const windowHeight = window.innerHeight;
    lastScrollY = scrollY;

    if (scrollY > windowHeight) {
      if (!isVisible) mostrarDiv();
    } else {
      if (isVisible) ocultarDiv();
    }
  }

  function manejarMouseMove() {
    const scrollY = window.scrollY;
    const windowHeight = window.innerHeight;

    if (scrollY > windowHeight) {
      if (!isVisible) mostrarDiv();
      clearTimeout(mouseTimeout);
      mouseTimeout = setTimeout(() => ocultarDiv(), 1000);
    }
  }

  newDiv.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

  window.addEventListener("scroll", manejarScroll);
  window.addEventListener("mousemove", manejarMouseMove);
}
