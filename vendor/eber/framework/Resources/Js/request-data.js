// export async function requestData(url, headers = {}) {

//   try {
      
//     const response = await fetch(url, {headers: headers});

//     if (!response.ok) {

//       throw new Error(`HTTP error! status: ${response.status}`);

//     }

//     const dataOut = await response.json();
//     return dataOut;

//   } catch (error) {

//     console.error('Error al cargar los datos:', error);
//     return '';

//   }

// }

// /**
//  * ingresa el parámetro url y recupera la información json desde la url especificada. como es una función asíncrona
//  * para recuperar los datos debe agregarlos a una variable y utilizar await antes del llamado de la función 
//  * (Ej, var data = await getRequestData('/url-datos-json'))
//  * @param {*} url -- esta es la url del archivo que sirve un json para recuperar los datos
//  * @returns 
//  */
// export async function getRequestData(url, header = {}) {

//   const existObj = Object.keys(header).length;

//   if (existObj == 0) {
    
//     return await requestData(url);
  
//   }

//   if (existObj >= 1) {
    
//     return await requestData(url, header);
    
//   }

// }