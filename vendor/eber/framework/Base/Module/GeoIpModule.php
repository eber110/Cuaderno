<?php
namespace Base\Module;

use GeoIp2\Database\Reader;

/**
 * Módulo para extraer información geográfica de una dirección IP usando la base de datos MaxMind GeoIP2.
 * Requiere que el archivo GeoLite2-City.mmdb se encuentre en el directorio /Database/ del proyecto.
 */
class GeoIpModule {
    private static $reader = null;

    /**
     * Resuelve la ruta del archivo de base de datos GeoLite2-City.mmdb.
     * 
     * @return string|null Ruta absoluta si el archivo existe, o null si no se encuentra.
     */
    public static function getDatabasePath(): ?string {
        $paths = [];

        if (defined('ROUTE_DATABASE')) {
            $paths[] = rtrim(ROUTE_DATABASE, '/\\') . '/GeoLite2-City.mmdb';
        }
        if (defined('ROOT_PATH')) {
            $paths[] = rtrim(ROOT_PATH, '/\\') . '/Database/GeoLite2-City.mmdb';
        }
        $paths[] = getcwd() . '/Database/GeoLite2-City.mmdb';
        // Fallbacks de compatibilidad para repositorios locales y ubicaciones previas
        $paths[] = __DIR__ . '/../../Database/GeoLite2-City.mmdb';
        $paths[] = __DIR__ . '/../../Resources/dbLocation/GeoLite2-City.mmdb';

        foreach ($paths as $path) {
            if (!empty($path) && file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Inicializa el lector de la base de datos MMDB.
     */
    private static function init() {
        if (self::$reader === null) {
            $dbPath = self::getDatabasePath();
            
            if ($dbPath !== null) {
                self::$reader = new Reader($dbPath);
            } else {
                $target = defined('ROUTE_DATABASE') ? ROUTE_DATABASE . 'GeoLite2-City.mmdb' : 'Database/GeoLite2-City.mmdb';
                throw new \Exception("La base de datos GeoIP no se encuentra en: {$target}. Por favor, ejecuta 'composer update-geoip' para descargarla en la carpeta /Database/ del proyecto.");
            }
        }
    }

    /**
     * Obtiene el registro completo de la ciudad a partir de una IP.
     * 
     * @param string $ip La dirección IP a buscar.
     * @return \GeoIp2\Model\City|null Retorna el modelo City o null si no se encuentra.
     */
    public static function getCityRecord(string $ip) {
        try {
            self::init();
            return self::$reader->city($ip);
        } catch (\Exception $e) {
            // Captura AddressNotFoundException u otros errores si la IP no existe o la DB no se ha cargado.
            return null; 
        }
    }

    /**
     * Obtiene el nombre de la ciudad a partir de una IP.
     * 
     * @param string $ip
     * @return string|null
     */
    public static function getCityName(string $ip) {
        $record = self::getCityRecord($ip);
        return $record ? $record->city->name : null;
    }

    /**
     * Obtiene el nombre del país a partir de una IP.
     * 
     * @param string $ip
     * @return string|null
     */
    public static function getCountryName(string $ip) {
        $record = self::getCityRecord($ip);
        return $record ? $record->country->name : null;
    }

    /**
     * Obtiene el código ISO del país a partir de una IP (ej: 'CL', 'US', 'ES').
     * 
     * @param string $ip
     * @return string|null
     */
    public static function getCountryCode(string $ip) {
        $record = self::getCityRecord($ip);
        return $record ? $record->country->isoCode : null;
    }

    /**
     * Obtiene el nombre del estado/región/provincia a partir de una IP.
     * 
     * @param string $ip
     * @return string|null
     */
    public static function getStateName(string $ip) {
        $record = self::getCityRecord($ip);
        return ($record && !empty($record->subdivisions)) ? $record->subdivisions[0]->name : null;
    }

    /**
     * Obtiene el código ISO del estado/región/provincia a partir de una IP.
     * 
     * @param string $ip
     * @return string|null
     */
    public static function getStateCode(string $ip) {
        $record = self::getCityRecord($ip);
        return ($record && !empty($record->subdivisions)) ? $record->subdivisions[0]->isoCode : null;
    }

    /**
     * Obtiene el código postal a partir de una IP.
     * 
     * @param string $ip
     * @return string|null
     */
    public static function getPostalCode(string $ip) {
        $record = self::getCityRecord($ip);
        return $record ? $record->postal->code : null;
    }

    /**
     * Obtiene la latitud de la ubicación de la IP.
     * 
     * @param string $ip
     * @return float|null
     */
    public static function getLatitude(string $ip) {
        $record = self::getCityRecord($ip);
        return $record ? $record->location->latitude : null;
    }

    /**
     * Obtiene la longitud de la ubicación de la IP.
     * 
     * @param string $ip
     * @return float|null
     */
    public static function getLongitude(string $ip) {
        $record = self::getCityRecord($ip);
        return $record ? $record->location->longitude : null;
    }

    /**
     * Obtiene la zona horaria a partir de la IP (ej: 'America/Santiago').
     * 
     * @param string $ip
     * @return string|null
     */
    public static function getTimeZone(string $ip) {
        $record = self::getCityRecord($ip);
        return $record ? $record->location->timeZone : null;
    }

    /**
     * Obtiene el nombre del continente a partir de la IP.
     * 
     * @param string $ip
     * @return string|null
     */
    public static function getContinentName(string $ip) {
        $record = self::getCityRecord($ip);
        return $record ? $record->continent->name : null;
    }

    /**
     * Obtiene el código del continente a partir de la IP (ej: 'SA', 'NA', 'EU').
     * 
     * @param string $ip
     * @return string|null
     */
    public static function getContinentCode(string $ip) {
        $record = self::getCityRecord($ip);
        return $record ? $record->continent->code : null;
    }
}
