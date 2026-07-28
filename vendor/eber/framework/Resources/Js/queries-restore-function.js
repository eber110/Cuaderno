// // responsive-handler.js
// export default class ResponsiveHandler {
//   constructor() {
//     // Define los media queries
//     this.phoneQuery = window.matchMedia('(max-width: 576px)');
//     this.tabletQuery = window.matchMedia('(min-width: 577px) and (max-width: 992px)');
//     this.desktopQuery = window.matchMedia('(min-width: 993px)');
    
//     // Objeto para almacenar funciones por dispositivo
//     this.handlers = {
//       phone: [],
//       tablet: [],
//       desktop: []
//     };
    
//     // Configurar los listeners para cambios de media query
//     this.setupEventListeners();
    
//     // Ejecutar las funciones correspondientes al tamaño inicial de pantalla
//     this.checkCurrentViewport();
//   }
  
//   /**
//    * Agrega listeners para cada media query
//    */
//   setupEventListeners() {
//     // Para compatibilidad con navegadores más antiguos
//     const eventType = 'addEventListener' in this.phoneQuery ? 'addEventListener' : 'addListener';
//     const event = 'addEventListener' in this.phoneQuery ? 'change' : '';
    
//     this.phoneQuery[eventType](event, () => this.checkCurrentViewport());
//     this.tabletQuery[eventType](event, () => this.checkCurrentViewport());
//     this.desktopQuery[eventType](event, () => this.checkCurrentViewport());
//   }
  
//   /**
//    * Registra una función para ejecutarse en un tipo de dispositivo específico
//    * @param {string} device - 'phone', 'tablet', o 'desktop'
//    * @param {Function} callback - La función a ejecutar
//    * @return {Function} - Función para eliminar este handler
//    */
//   register(device, callback) {
//     if (!this.handlers[device]) {
//       console.error(`Tipo de dispositivo no válido: ${device}. Use 'phone', 'tablet' o 'desktop'.`);
//       return () => {};
//     }
    
//     this.handlers[device].push(callback);
    
//     // Si ya estamos en ese viewport, ejecutar inmediatamente
//     if (
//       (device === 'phone' && this.phoneQuery.matches) ||
//       (device === 'tablet' && this.tabletQuery.matches) ||
//       (device === 'desktop' && this.desktopQuery.matches)
//     ) {
//       callback();
//     }
    
//     // Retornar función para des-registrar
//     return () => {
//       const index = this.handlers[device].indexOf(callback);
//       if (index > -1) {
//         this.handlers[device].splice(index, 1);
//       }
//     };
//   }
  
//   /**
//    * Ejecuta todas las funciones registradas para el viewport actual
//    */
//   checkCurrentViewport() {
//     if (this.phoneQuery.matches) {
//       this.executeHandlers('phone');
//     } else if (this.tabletQuery.matches) {
//       this.executeHandlers('tablet');
//     } else if (this.desktopQuery.matches) {
//       this.executeHandlers('desktop');
//     }
//   }
  
//   /**
//    * Ejecuta todas las funciones registradas para un tipo de dispositivo
//    * @param {string} device - 'phone', 'tablet', o 'desktop'
//    */
//   executeHandlers(device) {
//     this.handlers[device].forEach(callback => {
//       try {
//         callback();
//       } catch (error) {
//         console.error(`Error al ejecutar función para ${device}:`, error);
//       }
//     });
//   }
// }