/**
 * Sistema de notificaciones desde parámetros URL.
 * Incluye animaciones GSAP automáticas si está disponible.
 * 
 * @function notification
 * @async
 * @description Muestra notificaciones basadas en parámetros de la URL.
 *              Soporta: error, success, warning, danger.
 *              Las animaciones GSAP se activan automáticamente si la librería está cargada.
 * 
 * @example
 * // URL
 * http://tudominio.com?success=Operación%20exitosa
 * http://tudominio.com?error=Error%20en%20la%20operación
 * 
 * @requires svg() - Función para cargar iconos SVG
 * @requires gsap - GreenSock Animation Platform (opcional)
 * 
 * @returns {Promise<void>}
 */
export async function notification(optionalClass = "") {
  const x = await svg("xmark");

  // Verificar si GSAP está disponible
  const hasGsap = typeof gsap !== 'undefined';

  const params = new URLSearchParams(window.location.search);
  const errors = params.get("error");
  const success = params.get("success");
  const warning = params.get("warning");
  const danger = params.get("danger");
  const body = document.querySelector('body');

  if (!errors && !success && !warning && !danger) return;

  // Contenedor de la notificación
  const divNotification = document.createElement("div");
  divNotification.id = "divNotification";
  divNotification.className = "container-xl fixed text-protected pl20 pr20 flex-row gap10 top-center toppx40";
  divNotification.style.zIndex = 900000000;

  // Contenedor hijo
  const containerNotification = document.createElement("div");
  containerNotification.id = "containerNotification";

  // Botón de cerrar
  const closeNotification = document.createElement("div");
  closeNotification.id = "closeNotification";
  closeNotification.className = "absolute top right mt16 mr10 br50 pointer x18 bold200 wpx22 hpx22 ar-square flex-row center-center textw";
  closeNotification.innerHTML = x;

  // Mensaje
  const notificationMsg = document.createElement("div");
  notificationMsg.id = "notificationMsg";
  const classNotification = "p15 pl25 pr45 br15";

  // Extraer clases de formato de texto de optionalClass para aplicarlas directamente al mensaje
  const textFormattingClasses = [];
  if (optionalClass) {
    optionalClass.split(' ').forEach(cls => {
      if (!cls) return;
      if (
        cls.startsWith('text') || 
        cls.startsWith('color') || 
        cls.startsWith('bold') || 
        cls.startsWith('x')
      ) {
        textFormattingClasses.push(cls);
      }
    });
  }
  const textOptClasses = textFormattingClasses.join(' ');

  if (errors) {
    containerNotification.className = `back-danger ${classNotification} ${optionalClass}`;
    notificationMsg.className = textOptClasses;
    notificationMsg.innerHTML = errors;
  } else if (success) {
    containerNotification.className = `back-success ${classNotification} ${optionalClass}`;
    notificationMsg.className = textOptClasses;
    notificationMsg.innerHTML = success;
  } else if (warning) {
    containerNotification.className = `back-caution ${classNotification} ${optionalClass}`;
    notificationMsg.className = textOptClasses;
    notificationMsg.innerHTML = warning;
  } else if (danger) {
    containerNotification.className = `back-danger ${classNotification} ${optionalClass}`;
    notificationMsg.className = textOptClasses;
    notificationMsg.innerHTML = danger;
  }


  containerNotification.appendChild(closeNotification);
  containerNotification.appendChild(notificationMsg);
  divNotification.appendChild(containerNotification);
  body.appendChild(divNotification);

  // Animación de entrada
  if (hasGsap) {
    gsap.fromTo(divNotification,
      { y: -50, opacity: 0 },
      { y: 0, opacity: 1, duration: 0.5, ease: 'power3.out' }
    );
  }

  /**
   * Cierra la notificación con animación y limpia la URL.
   */
  function closeAndCleanUrl() {
    if (!divNotification.parentNode) return;

    const cleanup = () => {
      divNotification.remove();
      const nuevaUrl = window.location.pathname;
      window.history.replaceState({}, '', nuevaUrl);
    };

    if (hasGsap) {
      gsap.to(divNotification, {
        y: -30,
        opacity: 0,
        duration: 0.3,
        ease: 'power2.in',
        onComplete: cleanup
      });
    } else {
      cleanup();
    }
  }

  // Evento de cierre manual
  closeNotification.addEventListener("click", () => {
    clearTimeout(autoCloseTimer);
    closeAndCleanUrl();
  });

  // Auto-cierre después de 8 segundos
  const autoCloseTimer = setTimeout(closeAndCleanUrl, 8000);
}
