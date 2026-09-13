<?php

namespace Base\Module;

use Exception;

/**
 * Servicio unificado de geolocalización por dirección IP.
 * 
 * Estrategia híbrida:
 * 1. Comprueba si es IP local (127.0.0.1, ::1)
 * 2. Prioriza el driver local MaxMind MMDB (GeoIpModule) para máxima velocidad sin latencia de red.
 * 3. Si no existe la base de datos MMDB o no encuentra la IP, recurre al driver remoto de APIs HTTP (VisitModule).
 */
class GeoLocation
{
  private const LOCAL_IPS = ['::1', '127.0.0.1', 'localhost'];

  /**
   * Determina si una IP es de entorno local o privada.
   */
  public static function isLocalIp(string $ip): bool
  {
    if (in_array($ip, self::LOCAL_IPS, true)) {
      return true;
    }
    return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
  }

  /**
   * Alias de lookup(). Resuelve la información geográfica de una IP.
   *
   * @param string $ip Dirección IP a consultar
   * @return array Array normalizado con país, código de país, ciudad y driver utilizado
   */
  public static function resolve(string $ip): array
  {
    return self::lookup($ip);
  }

  /**
   * Resuelve la información geográfica de una IP.
   *
   * @param string $ip Dirección IP a consultar
   * @return array Array normalizado con país, código de país, ciudad y driver utilizado
   */
  public static function lookup(string $ip): array
  {
    $ip = trim($ip);

    // 1. Caso IP local
    if (self::isLocalIp($ip)) {
      return [
        'ip'           => $ip,
        'country_code' => 'DEV',
        'country_name' => 'Localhost',
        'city_name'    => 'Local Development',
        'source'       => 'local_network'
      ];
    }

    // 2. Intentar base local MaxMind MMDB (GeoIpModule)
    if (class_exists(GeoIpModule::class) && GeoIpModule::getDatabasePath() !== null) {
      try {
        $record = GeoIpModule::getCityRecord($ip);
        if ($record && !empty($record->country->isoCode)) {
          return [
            'ip'           => $ip,
            'country_code' => $record->country->isoCode ?? 'N/A',
            'country_name' => $record->country->name ?? 'Desconocido',
            'city_name'    => $record->city->name ?? 'Desconocido',
            'source'       => 'maxmind_mmdb'
          ];
        }
      } catch (Exception $e) {
        // Fallback silencioso a API remota
      }
    }

    // 3. Fallback: Driver remoto vía API HTTP (VisitModule)
    if (class_exists(VisitModule::class)) {
      try {
        $geo = VisitModule::fetchGeoData($ip);
        $code = $geo['codigo'] ?? $geo['codigo_pais'] ?? 'N/A';
        if (is_array($geo) && !empty($code) && $code !== 'N/A') {
          return [
            'ip'           => $ip,
            'country_code' => $code,
            'country_name' => $geo['pais'] ?? 'Desconocido',
            'city_name'    => $geo['ciudad'] ?? 'Desconocido',
            'source'       => 'remote_api'
          ];
        }
      } catch (Exception $e) {
        error_log("GeoLocation remote API error: " . $e->getMessage());
      }
    }

    return [
      'ip'           => $ip,
      'country_code' => 'N/A',
      'country_name' => 'Desconocido',
      'city_name'    => 'Desconocido',
      'source'       => 'none'
    ];
  }
}
