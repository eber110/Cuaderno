<?php

namespace Base\Module;

/**
 * Módulo de detección y filtrado de bots, rastreadores, scrapers y tráfico no orgánico.
 * 
 * Diseñado para operar en memoria de forma ultrarrápida sin dependencias externas ni llamadas de red.
 */
class BotDetectorModule
{
    /**
     * Patrones de firmas conocidas de bots, crawlers, scrapers y herramientas de automatización.
     */
    private const BOT_PATTERNS = [
        // Buscadores y motores de indexación
        'googlebot', 'bingbot', 'bingpreview', 'msnbot', 'yandex', 'baiduspider',
        'duckduckbot', 'sogou', 'exabot', 'konqueror', 'ahrefsbot', 'semrushbot',
        'dotbot', 'mj12bot', 'rogerbot', 'megaindex', 'blexbot', 'seekport',
        'serpstatbot', 'sistrix', 'screaming frog', 'seokicks', 'uptimerobot',

        // Previsualizadores de mensajería y redes sociales (Open Graph fetchers)
        'facebookexternalhit', 'twitterbot', 'telegrambot', 'whatsapp', 'discordbot',
        'slackbot', 'slack-imgproxy', 'linkedinbot', 'applebot', 'skypeuripreview',
        'pinterest', 'tumblr', 'viber', 'line-poker', 'vkshare', 'w3c_validator',
        'feedfetcher-google', 'flipboard', 'qwantify', 'bitlybot', 'x-bufferbot',

        // Librerías HTTP, CLI, scripts y herramientas de scraping
        'curl', 'wget', 'python-requests', 'python-urllib', 'aiohttp', 'guzzlehttp',
        'go-http-client', 'okhttp', 'postmanruntime', 'insomnia', 'httpie', 'http_request',
        'scrapy', 'node-fetch', 'axios', 'libwww-perl', 'apache-httpclient', 'zgrab',
        'rest-client', 'winhttp', 'urllib', 'faraday', 'mechanize',

        // Navegadores Headless y entornos de testing automatizado
        'headlesschrome', 'phantomjs', 'selenium', 'puppeteer', 'playwright',
        'electron', 'webdriver', 'cypress',

        // Palabras clave genéricas de rastreo
        'crawler', 'spider', 'scraper', 'archiver', 'transcoder', 'archive.org_bot'
    ];

    /**
     * Lista de dominios comunes asociados a referrer spam / ghost referrals.
     */
    private const SPAM_REFERRER_DOMAINS = [
        'semalt.com', 'buttons-for-website.com', 'darodar.com', 'ilovevitaly.com',
        'priceg.com', 'blackhatworth.com', 'hulfingtonpost.com', 'humanorightswatch.org',
        'free-floating-buttons.com', 'buy-cheap-online.info', 'traffic2cash.xyz',
        'site-auditor.online', 'trafficmonetize.org', 'best-seo-solution.com'
    ];

    /**
     * Evalúa si una petición HTTP o User-Agent proviene de un bot o crawler conocido.
     *
     * @param string|null $userAgent User-Agent a evaluar (si es null se toma de $_SERVER['HTTP_USER_AGENT']).
     * @return bool True si es identificado como bot o rastreador.
     */
    public static function isBot(?string $userAgent = null): bool
    {
        $ua = $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $ua = trim($ua);

        // User-Agent vacío, nulo o sospechosamente corto (menos de 10 caracteres)
        if (empty($ua) || strlen($ua) < 10) {
            return true;
        }

        $uaLower = mb_strtolower($ua, 'UTF-8');

        // Búsqueda rápida de coincidencias
        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($uaLower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtiene el nombre del bot si se detecta uno conocido.
     *
     * @param string|null $userAgent
     * @return string|null Nombre del bot detectado o null si parece ser un cliente normal.
     */
    public static function getBotName(?string $userAgent = null): ?string
    {
        $ua = $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $uaLower = mb_strtolower(trim($ua), 'UTF-8');

        if (empty($uaLower) || strlen($uaLower) < 10) {
            return 'Generic/Empty User-Agent';
        }

        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($uaLower, $pattern)) {
                return $pattern;
            }
        }

        return null;
    }

    /**
     * Valida y sanitiza una URL de referencia (HTTP Referer).
     * Descarta URLs malformadas, IPs locales, dominios internos y dominios de spam.
     *
     * @param string|null $referrer URL a validar.
     * @return string URL válida y limpia, o cadena vacía si es inválida, spam o directa.
     */
    public static function cleanReferrer(?string $referrer): string
    {
        if (empty($referrer)) {
            return '';
        }

        $referrer = trim($referrer);

        // Validación sintáctica de URL
        if (!filter_var($referrer, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parsed = parse_url($referrer);
        if (!$parsed || empty($parsed['host'])) {
            return '';
        }

        $host = strtolower($parsed['host']);

        // Descartar localhost / IPs privadas
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true) ||
            filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false && filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return '';
        }

        // Descartar si el host es el propio dominio de la aplicación
        $currentHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
        if (!empty($currentHost) && ($host === $currentHost || str_ends_with($host, '.' . $currentHost))) {
            return '';
        }

        if (defined('DOMAIN') && !empty(DOMAIN)) {
            $domainHost = parse_url(DOMAIN, PHP_URL_HOST);
            if (!empty($domainHost) && ($host === strtolower($domainHost) || str_ends_with($host, '.' . strtolower($domainHost)))) {
                return '';
            }
        }

        // Descartar lista de dominios de referrer spam
        foreach (self::SPAM_REFERRER_DOMAINS as $spamDomain) {
            if ($host === $spamDomain || str_ends_with($host, '.' . $spamDomain)) {
                return '';
            }
        }

        // Sanitización contra inyecciones XSS / cabeceras
        return htmlspecialchars($referrer, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Clasifica el tipo de referido orgánico en base a la URL.
     *
     * @param string|null $referrer
     * @return string 'search' | 'social' | 'referral' | 'direct'
     */
    public static function classifyReferrer(?string $referrer): string
    {
        $clean = self::cleanReferrer($referrer);
        if (empty($clean)) {
            return 'direct';
        }

        $host = strtolower(parse_url($clean, PHP_URL_HOST) ?? '');

        // Motores de búsqueda
        if (preg_match('/(google|bing|yahoo|duckduckgo|ecosia|yandex|baidu|ask)\./i', $host)) {
            return 'search';
        }

        // Redes sociales
        if (preg_match('/(instagram\.com|facebook\.com|fb\.com|t\.co|twitter\.com|x\.com|tiktok\.com|linkedin\.com|youtube\.com|pinterest\.com|reddit\.com|threads\.net)/i', $host)) {
            return 'social';
        }

        return 'referral';
    }
}
