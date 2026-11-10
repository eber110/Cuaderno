<?php

namespace Core;

use Base\Control\Control;
use Base\Module\SeoModule;

/**
 * Manejador de errores HTTP.
 * 
 * Gestiona la presentación de errores HTTP, registro y respuestas.
 * Renderiza las vistas de error ubicadas en /App/errorViews/ (ROUTE_ERROR_VIEW).
 * 
 * @example
 * // Error 404
 * ErrorHandler::handle404();
 * 
 * // Error personalizado
 * ErrorHandler::handleCode(403, 'FORBIDDEN', 'Acceso denegado');
 */
class ErrorHandler
{
    /**
     * Indica si estamos en desarrollo.
     */
    public static function isDev(): bool
    {
        if (!defined('ENVIRONMENT')) {
            return true;
        }
        $env = strtolower((string)ENVIRONMENT);
        return in_array($env, ['dev', 'development', 'local'], true);
    }

    /**
     * Registra un error en el log.
     */
    private static function log(int $code, string $message, array $context = []): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';

        $logData = [
            'time' => date('Y-m-d H:i:s'),
            'code' => $code,
            'msg' => $message,
            'ip' => $ip,
            'url' => $uri,
            'method' => $method,
        ];

        if (!empty($context)) {
            $logData['context'] = $context;
        }

        error_log('[ErrorHandler] ' . json_encode($logData, JSON_UNESCAPED_SLASHES));

        // Registrar intrusión si el error es de seguridad (403, 405) o si la ruta es sospechosa
        if (class_exists('\\Base\\Module\\SecurityModule')) {
            $isSuspicious = \Base\Module\SecurityModule::isSuspiciousPath($uri);
            if ($code === 403 || $code === 405 || $isSuspicious) {
                \Base\Module\SecurityModule::logIntrusion(
                    "HTTP Error {$code} ({$message})" . ($isSuspicious ? " [Ruta Sospechosa]" : ""),
                    $logData
                );
            }
        }
    }


    /**
     * Renderiza la página de error con los estilos del proyecto.
     */
    private static function render(int $httpCode, string $errorFile, string $title, string $description, array $data = []): void
    {
        http_response_code($httpCode);
        $_SERVER['REDIRECT_STATUS'] = (string)$httpCode;

        // Usar Control para renderizar con los estilos correctos
        if (class_exists('\Base\Module\SeoModule')) {
            \Base\Module\SeoModule::setMetaDescription($description);
            \Base\Module\SeoModule::setTitle($title);
        }
        $control = new Control();
        echo $control->pag_error($errorFile, $data);

        exit;
    }

    /**
     * Error 404 - No Encontrado.
     */
    public static function handle404(): void
    {
        self::log(404, 'Página no encontrada', [
            'referrer' => $_SERVER['HTTP_REFERER'] ?? 'direct'
        ]);

        $title = function_exists('__') ? __('global.errors.404_title') : 'Error 404 - No Encontrado';
        $desc = function_exists('__') ? __('global.errors.404_desc') : 'La página que buscas no existe en esta aplicación.';
        $btn = function_exists('__') ? __('global.back_home') : 'Volver al Inicio';
        $homeLink = function_exists('langUrl') ? langUrl('/') : (defined('LINK') ? LINK : '/');

        self::render(
            404,
            'HandlerError',
            $title,
            $desc,
            [404, '404', htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'), $homeLink, $btn, '']
        );
    }

    /**
     * Error 405 - Método No Permitido.
     */
    public static function handle405(string $allowedMethods = ''): void
    {
        self::log(405, 'Método no permitido', [
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'allowed' => $allowedMethods
        ]);

        if ($allowedMethods) {
            header("Allow: {$allowedMethods}");
        }

        $reqMethod = (string)($_SERVER['REQUEST_METHOD'] ?? 'desconocido');
        if (function_exists('__')) {
            $message = __('global.errors.405_method', ['method' => $reqMethod]);
            if ($allowedMethods) {
                $message .= ' ' . __('global.errors.405_allowed', ['methods' => $allowedMethods]);
            }
        } else {
            $message = 'El método ' . htmlspecialchars($reqMethod, ENT_QUOTES, 'UTF-8') . ' no está permitido.';
            if ($allowedMethods) {
                $message .= ' Métodos permitidos: ' . htmlspecialchars($allowedMethods, ENT_QUOTES, 'UTF-8');
            }
        }

        $title = function_exists('__') ? __('global.errors.405_title') : 'Error 405 - Método No Permitido';
        $desc = function_exists('__') ? __('global.errors.405_desc') : 'El método solicitado no está permitido para esta URI.';
        $btn = function_exists('__') ? __('global.back') : 'Volver';

        self::render(
            405,
            'HandlerError',
            $title,
            $desc,
            [405, 'METHOD_NOT_ALLOWED', $message, '', $btn, '']
        );
    }

    /**
     * Error 500 - Error Interno.
     */
    public static function handle500(string $message = '', bool $showDetails = false): void
    {
        self::log(500, $message ?: 'Error interno del servidor', [
            'trace' => self::isDev() ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5) : []
        ]);

        $defaultUserMsg = function_exists('__')
            ? __('global.errors.500_user')
            : 'Lo sentimos, algo salió mal. Estamos trabajando para solucionarlo.';

        $userMessage = (self::isDev() && $showDetails && $message)
            ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
            : htmlspecialchars($defaultUserMsg, ENT_QUOTES, 'UTF-8');

        $title = function_exists('__') ? __('global.errors.500_title') : 'Error 500 - Error del Servidor';
        $desc = function_exists('__') ? __('global.errors.500_desc') : 'Error interno del servidor.';
        $btn = function_exists('__') ? __('global.back') : 'Volver';

        self::render(
            500,
            'HandlerError',
            $title,
            $desc,
            [500, 'INTERNAL_SERVER_ERROR', $userMessage, '', $btn, '']
        );
    }

    /**
     * Error 403 - Prohibido.
     */
    public static function handle403(string $message = ''): void
    {
        if ($message === '') {
            $message = function_exists('__')
                ? __('global.errors.403_desc')
                : 'No tienes permiso para acceder a este recurso.';
        }

        self::log(403, $message);

        $title = function_exists('__') ? __('global.errors.403_title') : 'Error 403 - Acceso Prohibido';
        $btn = function_exists('__') ? __('global.back') : 'Volver';

        self::render(
            403,
            'HandlerError',
            $title,
            $message,
            [403, 'FORBIDDEN', htmlspecialchars($message, ENT_QUOTES, 'UTF-8'), '', $btn, '']
        );
    }

    /**
     * Error 401 - No Autorizado.
     */
    public static function handle401(string $message = ''): void
    {
        if ($message === '') {
            $message = function_exists('__')
                ? __('global.errors.401_desc')
                : 'Debes iniciar sesión para acceder.';
        }

        self::log(401, $message);

        $title = function_exists('__') ? __('global.errors.401_title') : 'Error 401 - No Autorizado';
        $btn = function_exists('__') ? __('global.login') : 'Iniciar Sesión';
        $loginUrl = function_exists('langUrl') ? langUrl('/login') : (defined('LINK') ? LINK . 'login' : '/login');

        self::render(
            401,
            'HandlerError',
            $title,
            $message,
            [401, 'UNAUTHORIZED', htmlspecialchars($message, ENT_QUOTES, 'UTF-8'), $loginUrl, $btn, '']
        );
    }

    /**
     * Error personalizado.
     * 
     * @param int $http Código HTTP.
     * @param int|string $code Código de error interno.
     * @param string $message Mensaje para el usuario.
     * @param string $redirect URL de redirección (opcional).
     * @param string $buttonText Texto del botón.
     * @param string $svg Icono SVG (opcional).
     */
    public static function handleCode(
        int $http,
        int|string $code,
        string $message,
        string $redirect = '',
        string $buttonText = '',
        string $svg = ''
    ): void {
        if ($buttonText === '') {
            $buttonText = function_exists('__') ? __('global.leave') : 'Salir de aquí';
        }

        self::log($http, $message, [
            'code' => $code,
            'redirect' => $redirect
        ]);

        $errorPrefix = function_exists('__') ? __('global.errors.title') : 'Error';

        self::render(
            $http,
            'HandlerError',
            "{$errorPrefix} {$code}",
            $message,
            [$http, $code, htmlspecialchars($message, ENT_QUOTES, 'UTF-8'), $redirect, $buttonText, $svg]
        );
    }

    /**
     * Solo establece el código HTTP sin renderizar.
     * 
     * @param int $http Código HTTP.
     * @return string Código como string.
     */
    public static function setHttpCode(int $http): string
    {
        http_response_code($http);
        return $_SERVER['REDIRECT_STATUS'] = (string)$http;
    }

  // ============================================
  // MÉTODOS LEGACY (compatibilidad hacia atrás)
  // ============================================

    /**
     * @deprecated Usar handleCode() directamente.
     */
    public static function handle_code($http, $code, $msg, $redirect = '', $textButton = '', $svg = ''): void
    {
        self::handleCode((int)$http, $code, $msg, $redirect, $textButton, $svg);
    }

    /**
     * @deprecated Usar setHttpCode() directamente.
     */
    public static function stateHttp($http): string
    {
        return self::setHttpCode((int)$http);
    }
}
