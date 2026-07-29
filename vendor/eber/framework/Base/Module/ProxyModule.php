<?php

namespace Base\Module;

/**
 * Módulo para servir recursos externos a través de un proxy (evita bloqueos de clientes, CORS, AdBlockers, etc.)
 */
class ProxyModule
{
    /**
     * Hace de proxy para una imagen externa y la sirve directamente.
     * 
     * @param string $url URL de la imagen a descargar.
     * @param array $allowedDomains Opcional: lista de dominios permitidos (ej: ['licdn.com', 'linkedin.com']). Si está vacío, permite todos.
     * @return never
     */
    public static function proxyImage(string $url, array $allowedDomains = []): never
    {
        // Si la URL es una ruta relativa (local del servidor), simplemente redirigimos.
        if (str_starts_with($url, '/')) {
            header("Location: " . $url);
            exit;
        }

        // Validar URL externa
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            ResponseModule::error("URL inválida", 400);
        }

        $parsedUrl = parse_url($url);
        
        // Validar dominio permitido si existe lista blanca
        if (!empty($allowedDomains)) {
            $host = strtolower($parsedUrl['host'] ?? '');
            $allowed = false;
            foreach ($allowedDomains as $domain) {
                if (str_ends_with($host, strtolower($domain))) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                ResponseModule::error("Dominio no permitido", 403);
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // User agent de navegador común para evitar rechazos del servidor origen
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        
        $content = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$content) {
            ResponseModule::error("No se pudo obtener la imagen", 404);
        }

        // Limpiar cualquier buffer previo (evita caracteres corruptos en la imagen)
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Servir la imagen con encabezados de caché
        http_response_code(200);
        header("Content-Type: " . ($contentType ?: 'image/jpeg'));
        header("Cache-Control: public, max-age=86400"); // Cache de 1 día en el navegador del usuario
        
        echo $content;
        exit;
    }
}
