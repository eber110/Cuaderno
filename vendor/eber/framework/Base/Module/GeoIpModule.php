<?php
namespace Base\Module;

use GeoIp2\Database\Reader;

/**
 * Módulo para extraer información geográfica de una dirección IP usando la base de datos MaxMind GeoIP2.
 * Requiere que el archivo GeoLite2-City.mmdb se encuentre en el directorio /Resources/dbLocation/ del framework.
 */
class GeoIpModule {
    private static $reader = null;

    /**
     * Inicializa el lector de la base de datos MMDB.
     */
    private static function init() {
        if (self::$reader === null) {
            // Asume que la base de datos está ubicada en el directorio del framework (Resources/dbLocation)
            $dbPath = __DIR__ . '/../../Resources/dbLocation/GeoLite2-City.mmdb';
            
            if (file_exists($dbPath)) {
                self::$reader = new Reader($dbPath);
            } else {
                throw new \Exception("La base de datos GeoIP no se encuentra en la ruta: {$dbPath}. Por favor, añade el archivo GeoLite2-City.mmdb en la carpeta /Resources/dbLocation/.");
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
}
