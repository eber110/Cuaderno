<?php

/**
 * Configuración del Módulo de Tokens y JWT.
 * 
 * Permite definir los perfiles de tokens firmados (como correos, recuperación de clave)
 * y los parámetros para JWT (JSON Web Tokens) de sesión persistente.
 * 
 * =========================================================================
 * RECORDATORIO RÁPIDO DE USO / TUTORIAL DE INTEGRACIÓN:
 * =========================================================================
 * 
 * 1. CÓMO AGREGAR UN PERFIL:
 *    Simplemente añade una clave bajo el array 'profiles'. Cada perfil define
 *    su propio tiempo de expiración ('expiration') en segundos y el algoritmo
 *    de firma ('algo').
 * 
 * 2. CÓMO USAR EN UN CONTROLADOR (CREAR Y ENVIAR):
 *    Cuando un usuario realiza una acción (ej. registrarse), creas el token:
 *    
 *    $data = ['userId' => 123, 'email' => 'user@example.com'];
 *    $token = \Base\Module\TokenModule::from('emails')->create($data);
 *    
 *    // Envías por correo el enlace:
 *    $link = DOMAIN . "verificar-cuenta?token=" . $token;
 * 
 * 3. CÓMO VALIDAR (PÁGINA DE ATERRIZAJE / CONTROLADOR):
 *    Cuando el usuario hace click en el enlace, lees el token GET y lo validas:
 * 
 *    $token = $_GET['token'] ?? '';
 *    $datos = \Base\Module\TokenModule::from('emails')->validate($token);
 *    
 *    if ($datos === false) {
 *        // Token alterado, corrupto o ya expirado. Denegar acción.
 *    } else {
 *        // Acceso permitido. Los datos son válidos e íntegros.
 *        $userId = $datos['userId'];
 *    }
 * 
 * 4. PERSISTENCIA DE SESIÓN CON JWT:
 *    - Crear en el Login (Controlador):
 *      $jwt = \Base\Module\TokenModule::configJWT(['id' => 123, 'role' => 'admin']);
 *      \Base\Module\CookieModule::set('auth_token', ['value' => $jwt, 'httponly' => true, 'secure' => true]);
 * 
 *    - Validar en el acceso (Middleware):
 *      $jwt = \Base\Module\CookieModule::get('auth_token');
 *      if ($jwt && $user = \Base\Module\TokenModule::validateJWT($jwt)) {
 *          // Sesión autorizada, pasar $user al request
 *      }
 */
return [
    // Perfiles personalizados de tokens firmados autocontenidos
    'profiles' => [
        'emails' => [
            'expiration' => 3600, // 1 hora
            'algo' => 'sha256',   // Algoritmo de firma hmac
        ],
        'recovery' => [
            'expiration' => 900,  // 15 minutos
            'algo' => 'sha256',
        ],
    ],

    // Parámetros de JSON Web Tokens (JWT) para persistencia de sesión
    'jwt' => [
        'expiration' => TIME_MONTH_S, // 30 días (en segundos)
    ]
];