// var selectors = '.size-height';

// // Función para calcular y ajustar la altura de elementos con la clase size-height
// export function adjustSizeHeight() {
//   // Seleccionar todos los elementos con la clase size-height
//   const sizeHeightElements = document.querySelectorAll(selectors);

//   sizeHeightElements.forEach(element => {
//     // Verificar si hay un parámetro entre corchetes
//     const menuSelector = element.getAttribute('class').match(/\[(.*?)\]/);
    
//     if (menuSelector) {
//       // Encontrar el elemento de menú especificado
//       const menuElement = document.querySelector(`.${menuSelector[1]}`);
      
//       if (menuElement) {
//         // Calcular la altura del menú
//         const menuHeight = menuElement.offsetHeight;
        
//         // Establecer la altura total de la ventana menos la altura del menú
//         element.style.height = `${window.innerHeight - menuHeight}px`;
//       }

//     } else {
//       // Si no hay parámetro, establecer altura completa de la ventana
//       element.style.height = `${window.innerHeight}px`;
//     }
    
//   });

// }

// // Ejecutar al cargar la página
// window.addEventListener('load', adjustSizeHeight);

// // Reajustar cuando se redimensiona la ventana
// window.addEventListener('resize', adjustSizeHeight);

// // Ejemplo de uso en HTML:
// // <div class="size-height">Contenido a altura completa</div>
// // <div class="size-height size-height[menu-principal]">Contenido restando altura del menú</div>