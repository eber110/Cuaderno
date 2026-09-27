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
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            header("Location: " . $url);
            exit;
        }

        // Validar URL externa (solo esquemas http o https)
        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
            ResponseModule::error("URL inválida", 400);
        }

        $parsedUrl = parse_url($url);
        $host = strtolower($parsedUrl['host'] ?? '');
        $scheme = strtolower($parsedUrl['scheme'] ?? '');

        if (empty($host) || !in_array($scheme, ['http', 'https'], true)) {
            ResponseModule::error("Protocolo o host no permitido", 400);
        }
        
        // Validar dominio permitido si existe lista blanca (con límite de punto estricto anti-SSRF)
        if (!empty($allowedDomains)) {
            $allowed = false;
            foreach ($allowedDomains as $domain) {
                $d = strtolower(trim($domain));
                if ($host === $d || str_ends_with($host, '.' . $d)) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                ResponseModule::error("Dominio no permitido", 403);
            }
        }

        // Resolver dirección IP y verificar que no sea privada ni reservada (anti-SSRF / metadata cloud)
        $ips = @gethostbynamel($host);
        if ($ips === false || empty($ips)) {
            ResponseModule::error("No se pudo resolver el host", 404);
        }
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                ResponseModule::error("Acceso a dirección IP privada o reservada denegado", 403);
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        // User agent de navegador común para evitar rechazos del servidor origen
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        
        $content = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$content) {
            ResponseModule::error("No se pudo obtener la imagen", 404);
        }

        // Validar que el Content-Type devuelto sea efectivamente de imagen
        if (!empty($contentType) && !str_starts_with(strtolower(trim($contentType)), 'image/')) {
            ResponseModule::error("El contenido solicitado no corresponde a una imagen válida", 400);
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
