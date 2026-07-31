<?php

/**
 * Configuración de CORS (Cross-Origin Resource Sharing).
 * 
 * Permite definir políticas de acceso interdominio (CORS) diferenciadas
 * para múltiples endpoints o microservicios de tu aplicación.
 * 
 * =========================================================================
 * EXPLICACIÓN DE PROPIEDADES DISPONIBLES EN CADA PERFIL:
 * =========================================================================
 * - 'enabled': (bool) Habilita o deshabilita CORS para el patrón de ruta.
 * - 'allowed_origins': (array) Dominios de origen permitidos para realizar peticiones.
 *                      Ejemplo: ['https://tusitio.com', 'https://admin.tusitio.com']
 *                      Usa ['*'] para permitir peticiones desde cualquier origen.
 * - 'allowed_methods': (array) Métodos HTTP autorizados en la petición CORS.
 *                      Ejemplo: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
 * - 'allowed_headers': (array) Cabeceras HTTP permitidas en la petición.
 *                      Ejemplo: ['Content-Type', 'Authorization', 'X-Requested-With']
 * - 'exposed_headers': (array) Cabeceras que la respuesta expone y el cliente puede leer.
 * - 'supports_credentials': (bool) Establece en true si necesitas dar soporte a credenciales,
 *                           cookies de sesión o autorización HTTP. 
 *                           IMPORTANTE: Si está en true, 'allowed_origins' NO puede ser ['*'].
 *                           Debes colocar los dominios autorizados de forma explícita.
 * - 'max_age': (int) Segundos que el navegador del cliente puede cachear la respuesta
 *              de una solicitud de pre-vuelo (preflight/OPTIONS) sin repetir la consulta.
 * 
 * =========================================================================
 * EJEMPLO DE CONFIGURACIÓN DE MULTIPLES ENDPOINTS (COPIAR SI SE NECESITA):
 * =========================================================================
 * return [
 *     'enabled' => true,
 *     'paths' => [
 *         // Perfil de API interna (más seguro con credenciales)
 *         'api/v1/*' => [
 *             'allowed_origins' => ['https://mi-aplicacion.com'],
 *             'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
 *             'allowed_headers' => ['Content-Type', 'Authorization'],
 *             'supports_credentials' => true,
 *             'max_age' => 86400,
 *         ],
 *         // Perfil de API pública (solo lectura y abierto a cualquier origen)
 *         'public/*' => [
 *             'allowed_origins' => ['*'],
 *             'allowed_methods' => ['GET', 'OPTIONS'],
 *             'allowed_headers' => ['Content-Type'],
 *             'supports_credentials' => false,
 *             'max_age' => 3600,
 *         ],
 *         // Fallback general para cualquier otra ruta no especificada
 *         '*' => [
 *             'allowed_origins' => ['*'],
 *             'allowed_methods' => ['GET', 'POST', 'OPTIONS'],
 *             'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
 *             'supports_credentials' => false,
 *             'max_age' => 86400,
 *         ]
 *     ]
 * ];
 */

return [
  // CORS desactivado por defecto. Actívalo definiendo tus rutas y orígenes aquí.
  'enabled' => false,
    
  'paths' => [
    // Define tus patrones de ruta y configuraciones específicas
    '/' => [
      'allowed_origins' => ['*'],
      'allowed_methods' => ['GET', 'POST', 'OPTIONS'],
      'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
      'supports_credentials' => false,
      'max_age' => 86400,
    ]
  ]
];