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
    private static function isDev(): bool
    {
        return defined('ENVIRONMENT') && ENVIRONMENT === 'development';
    }

    /**
     * Registra un error en el log.
     */
    private static function log(int $code, string $message, array $context = []): void
    {
        $logData = [
            'time' => date('Y-m-d H:i:s'),
            'code' => $code,
            'msg' => $message,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        ];

        if (!empty($context)) {
            $logData['context'] = $context;
        }

        error_log('[ErrorHandler] ' . json_encode($logData, JSON_UNESCAPED_SLASHES));
    }

    /**
     * Renderiza la página de error con los estilos del proyecto.
     */
    private static function render(int $httpCode, string $errorFile, string $title, string $description, array $data = []): void
    {
        http_response_code($httpCode);
        $_SERVER['REDIRECT_STATUS'] = (string)$httpCode;

        // Usar Control para renderizar con los estilos correctos
        $control = new Control();
        $control->meta_description($description);
        $control->title($title);
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

        self::render(
            404,
            'HandlerError',
            'Error 404 - No Encontrado',
            'La página que buscas no existe en esta aplicación.',
            [404, '404', 'La página que buscas no existe en esta aplicación.', defined('LINK') ? LINK : '/', 'Volver al Inicio', '']
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

        $message = 'El método ' . htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'desconocido') . ' no está permitido.';
        if ($allowedMethods) {
            $message .= ' Métodos permitidos: ' . htmlspecialchars($allowedMethods);
        }

        self::render(
            405,
            'HandlerError',
            'Error 405 - Método No Permitido',
            'El método solicitado no está permitido para esta URI.',
            [405, 'METHOD_NOT_ALLOWED', $message, '', 'Volver', '']
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

        $userMessage = (self::isDev() && $showDetails && $message)
            ? htmlspecialchars($message)
            : 'Lo sentimos, algo salió mal. Estamos trabajando para solucionarlo.';

        self::render(
            500,
            'HandlerError',
            'Error 500 - Error del Servidor',
            'Error interno del servidor.',
            [500, 'INTERNAL_SERVER_ERROR', $userMessage, '', 'Volver', '']
        );
    }

    /**
     * Error 403 - Prohibido.
     */
    public static function handle403(string $message = 'No tienes permiso para acceder a este recurso.'): void
    {
        self::log(403, $message);

        self::render(
            403,
            'HandlerError',
            'Error 403 - Acceso Prohibido',
            $message,
            [403, 'FORBIDDEN', htmlspecialchars($message), '', 'Volver', '']
        );
    }

    /**
     * Error 401 - No Autorizado.
     */
    public static function handle401(string $message = 'Debes iniciar sesión para acceder.'): void
    {
        self::log(401, $message);

        self::render(
            401,
            'HandlerError',
            'Error 401 - No Autorizado',
            $message,
            [401, 'UNAUTHORIZED', htmlspecialchars($message), defined('LINK') ? LINK . 'login' : '/login', 'Iniciar Sesión', '']
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
        string $buttonText = 'Salir de aquí',
        string $svg = ''
    ): void {
        self::log($http, $message, [
            'code' => $code,
            'redirect' => $redirect
        ]);

        self::render(
            $http,
            'HandlerError',
            "Error {$code}",
            $message,
            [$http, $code, htmlspecialchars($message), $redirect, $buttonText, $svg]
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
    public static function handle_code($http, $code, $msg, $redirect = '', $textButton = 'Salir de aquí', $svg = ''): void
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
