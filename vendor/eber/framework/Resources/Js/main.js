/*
Los import estarán en esta sección
*/

export function dynamicStyles(){

  const elements = document.querySelectorAll('*:not(head, meta, style, script, title, link, body, html)');

  elements.forEach(element => {

    const classNames = element.className;

    if (!classNames) {
      
      return;
      
    }

   const classes = classNames.split(' ');

   classes.forEach(cls => {

     if (!cls) {
        
       return;
        
     }

     const parts = cls.split('-');

     if (parts.length < 2) {
        
       return;
        
     }

     const property = parts[0];
     const value = parts.slice(1).join('-');

     const camelCaseProperty = property.replace(/-([a-z])/g, function (g) {

       return g[1].toUpperCase();

     });


     if (element.style[camelCaseProperty] !== undefined) {
        
       element.style[camelCaseProperty] = value;

     } else {

       console.warn(`Property "${property}" (camelCase: "${camelCaseProperty}") is not a valid style property for element:`, element);
      
     }

   });

 });

}