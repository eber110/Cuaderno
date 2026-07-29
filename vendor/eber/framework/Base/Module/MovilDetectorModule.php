<?php

namespace Base\Module;

/**
 * Módulo para detección de dispositivos, navegadores y sistemas operativos.
 */
class MovilDetectorModule
{
    private static ?string $userAgent = null;

    /**
     * Obtiene el User-Agent actual.
     */
    private static function getUserAgent(): string
    {
        if (self::$userAgent === null) {
            self::$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
        return self::$userAgent;
    }

    /**
     * Permite sobreescribir el User-Agent para pruebas o casos específicos.
     */
    public static function setUserAgent(string $userAgent): void
    {
        self::$userAgent = $userAgent;
    }

    /**
     * Devuelve dos parámetros para verificar si se usa en un navegador web de escritorio o móvil (compatibilidad)
     * @return int 1 -> es un móvil/tablet. 2 -> es un navegador (desktop).
     */
    public static function is_movil(): int
    {
        return (self::isMobile() || self::isTablet()) ? 1 : 2;
    }

    /**
     * Verifica si el dispositivo es un teléfono móvil.
     */
    public static function isMobile(): bool
    {
        $ua = strtolower(self::getUserAgent());
        
        // Excluimos explícitamente tablets comunes si queremos diferenciarlos bien,
        // aunque muchos móviles comparten firmas.
        if (self::isTablet()) {
            return false;
        }

        $mobileRegex = "/(android(?!.*tablet)|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i";
        
        return (bool) preg_match($mobileRegex, $ua);
    }

    /**
     * Verifica si el dispositivo es una Tablet (iPad, Android Tablet, etc).
     */
    public static function isTablet(): bool
    {
        $ua = strtolower(self::getUserAgent());
        
        // Android tablet suele decir Android pero NO Mobile.
        $isAndroidTablet = (strpos($ua, 'android') !== false && strpos($ua, 'mobile') === false);
        $isIpad = strpos($ua, 'ipad') !== false;
        $isKindle = strpos($ua, 'kindle') !== false || strpos($ua, 'silk/') !== false;
        $isPlaybook = strpos($ua, 'playbook') !== false;
        
        return $isAndroidTablet || $isIpad || $isKindle || $isPlaybook;
    }

    /**
     * Verifica si el dispositivo es de Escritorio (Desktop/Navegador tradicional).
     */
    public static function isDesktop(): bool
    {
        return !self::isMobile() && !self::isTablet();
    }

    /**
     * Identifica el sistema operativo.
     */
    public static function getOS(): string
    {
        $ua = self::getUserAgent();
        
        if (preg_match('/windows|win32/i', $ua)) return 'Windows';
        if (preg_match('/iphone|ipad|ipod/i', $ua)) return 'iOS';
        if (preg_match('/macintosh|mac os x/i', $ua)) return 'macOS';
        if (preg_match('/linux/i', $ua) && !preg_match('/android/i', $ua)) return 'Linux';
        if (preg_match('/android/i', $ua)) return 'Android';
        if (preg_match('/blackberry/i', $ua)) return 'BlackBerry';
        if (preg_match('/webos/i', $ua)) return 'Mobile';
        
        return 'Desconocido';
    }

    /**
     * Alias rápido para iOS.
     */
    public static function isIOS(): bool
    {
        return self::getOS() === 'iOS';
    }

    /**
     * Alias rápido para Android.
     */
    public static function isAndroid(): bool
    {
        return self::getOS() === 'Android';
    }

    /**
     * Identifica el tipo de dispositivo como string para almacenamiento estructurado (mobile, tablet, desktop).
     */
    public static function getDeviceType(): string
    {
        if (self::isTablet()) return 'tablet';
        if (self::isMobile()) return 'mobile';
        return 'desktop';
    }

    /**
     * Identifica el navegador, incluyendo navegadores integrados In-App (Instagram, TikTok, Facebook).
     */
    public static function getBrowser(): string
    {
        $ua = self::getUserAgent();
        
        if (preg_match('/Instagram/i', $ua)) return 'Instagram App';
        if (preg_match('/TikTok|musical_ly|ByteLocale/i', $ua)) return 'TikTok App';
        if (preg_match('/FBAN|FBAV|FB_IAB/i', $ua)) return 'Facebook App';
        if (preg_match('/Twitter/i', $ua)) return 'X (Twitter) App';
        if (preg_match('/MSIE/i', $ua) || preg_match('/Trident/i', $ua)) return 'Internet Explorer';
        if (preg_match('/Edg/i', $ua)) return 'Edge';
        if (preg_match('/Firefox/i', $ua)) return 'Firefox';
        if (preg_match('/OPR/i', $ua) || preg_match('/Opera/i', $ua)) return 'Opera';
        if (preg_match('/Chrome/i', $ua)) return 'Chrome'; // Debe ir después de Opera y Edge
        if (preg_match('/Safari/i', $ua)) return 'Safari'; // Debe ir después de Chrome
        if (preg_match('/Netscape/i', $ua)) return 'Netscape';
        
        return 'Desconocido';
    }

    /**
     * Devuelve toda la información del dispositivo en un array.
     */
    public static function deviceInfo(): array
    {
        return [
            'device_type'=> self::getDeviceType(),
            'is_mobile'  => self::isMobile(),
            'is_tablet'  => self::isTablet(),
            'is_desktop' => self::isDesktop(),
            'os'         => self::getOS(),
            'browser'    => self::getBrowser(),
            'user_agent' => self::getUserAgent()
        ];
    }
}