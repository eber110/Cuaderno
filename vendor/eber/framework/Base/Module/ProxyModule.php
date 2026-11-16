<?php

namespace Base\Module;

use Base\Module\Security\SafeHttpClient;

/**
 * Módulo para servir recursos externos a través de un proxy (evita bloqueos de clientes, CORS, AdBlockers, etc.)
 */
class ProxyModule
{
    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/avif',
        'image/x-icon',
        'image/vnd.microsoft.icon',
    ];

    /**
     * Hace de proxy para una imagen externa y la sirve directamente de forma segura.
     * Bloquea SVG/HTML para evitar XSS en el mismo origen y valida IPs públicas IPv4/IPv6.
     * 
     * @param string $url URL de la imagen a descargar.
     * @param array $allowedDomains Opcional: lista de dominios permitidos (ej: ['licdn.com', 'linkedin.com']).
     * @return never
     */
    public static function proxyImage(string $url, array $allowedDomains = []): never
    {
        $cleanInput = str_replace(["\r", "\n", "\0"], '', trim($url));

        // Si la URL es una ruta relativa estrictamente local (bloqueando // y /\), redirigir de forma segura
        if (str_starts_with($cleanInput, '/') && !str_starts_with($cleanInput, '//') && !str_starts_with($cleanInput, '/\\') && !str_contains($cleanInput, '\\')) {
            $safeLocal = SecurityModule::sanitizeRedirectUrl($cleanInput, false);
            header("Location: " . $safeLocal);
            exit;
        }

        $res = SafeHttpClient::fetch($cleanInput, [
            'allowedDomains' => $allowedDomains,
            'timeout' => 15,
            'maxBytes' => 5242880,
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);

        if (empty($res['success'])) {
            $status = (int)($res['status'] ?? 404);
            $msg = (string)($res['error'] ?? 'No se pudo obtener la imagen');
            ResponseModule::error($msg, $status);
        }

        // Extraer tipo MIME real sin parámetros y exigir lista blanca raster (nunca image/svg+xml)
        $rawType = strtolower(trim(explode(';', (string)$res['contentType'])[0]));
        $finfoMime = '';
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = @finfo_buffer($finfo, $res['body']);
                @finfo_close($finfo);
                if (is_string($detected)) {
                    $finfoMime = strtolower(trim($detected));
                }
            }
        }

        $effectiveMime = $finfoMime !== '' ? $finfoMime : $rawType;
        if (!in_array($effectiveMime, self::ALLOWED_IMAGE_MIMES, true)) {
            ResponseModule::error("Tipo de imagen no permitido por política de seguridad", 400);
        }

        // Limpiar cualquier buffer previo (evita caracteres corruptos en la imagen)
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Servir únicamente con Content-Type normalizado y cabeceras anti-sniffing/sandbox
        http_response_code(200);
        header("Content-Type: " . $effectiveMime);
        header("X-Content-Type-Options: nosniff");
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");
        header("Cache-Control: public, max-age=86400");

        echo $res['body'];
        exit;
    }
}
