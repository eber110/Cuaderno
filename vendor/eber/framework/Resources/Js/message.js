// export function descriptionComponent() {
  
//   /**
//    * crear una funcion que tenga un identificador llamado "description" y un texto dentro del div
//    * segun el id este div se creara una ventana satelite del componente el cual tendra informacion del componente 
//    * esta informacion sera el texto del div
//    */

// }

// /**
//  * esta función crea una notificación según las variables que envies por la url con el método GET
//  * puedes crear las notificaciones que necesites asociado a las variables que configures.
//  * También puedes agregar estilo css a traves de clases en el segundo parámetro de la función
//  * @param {*} varMsg - ingresa el nombre de la variable que activa la notificación tomando el contenido de la variable utilizada
//  * @param {*} styleClass - agrega estilo css a traves de clases css
//  */
// export function notification(varMsg, styleClass = 'center-fixed-top') {
  
//   const url = new URL(window.location.href);
//   const params = new URLSearchParams(url.search);
//   const message = params.get(varMsg);

//   if (message !== null) {
    
//     const messageMsg = document.createElement('div');
//     messageMsg.className = `fixed before text-protected ${styleClass}`;

//     const cancelMessage = document.createElement('div');
//     cancelMessage.id = 'cancel-error';
//     cancelMessage.className = 'w-auto absolute top right p0 m0 mr10 pointer';
//     cancelMessage.innerHTML = '<i class="fa-solid fa-xmark x16"></i>';

//     const contentMessage = document.createElement('div');
//     contentMessage.className = 'm12 mr20 ml20';
//     contentMessage.innerText = message;

//     messageMsg.appendChild(cancelMessage);
//     messageMsg.appendChild(contentMessage);
//     document.body.appendChild(messageMsg);

//     cancelMessage.addEventListener('click', () => {messageMsg.remove()});
  
//     setTimeout(() => {
//       messageMsg.remove();
//     }, 10000);

//   }
// }