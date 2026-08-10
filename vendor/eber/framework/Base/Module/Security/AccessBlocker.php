<?php

namespace Base\Module\Security;

use Core\ErrorHandler;

/**
 * Gestor de Bloqueo de Accesos y Baneos de IP (SRP).
 * 
 * Responsabilidad Única: Administrar la lista de IPs bloqueadas, su expiración,
 * rate limiting por reincidencia y la persistencia en Database/blocked_ips.json.
 */
class AccessBlocker
{
  /**
   * Obtiene la ruta del archivo de persistencia de bloqueos.
   */
  public static function getStorageFilePath(): string
  {
    $dir = defined('ROUTE_SAFETY') ? rtrim(ROUTE_SAFETY, '/') : (defined('ROOT_PATH') ? ROOT_PATH . '/App/Safety' : str_replace('\\', '/', getcwd()) . '/App/Safety');
    if (!is_dir($dir)) {
      @mkdir($dir, 0755, true);
    }
    return $dir . '/blocked_ips.json';
  }

  /**
   * Carga los datos guardados en JSON.
   */
  private static function loadStorageData(): array
  {
    $file = self::getStorageFilePath();
    if (!file_exists($file)) {
      return ['blocked' => [], 'attempts' => []];
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : ['blocked' => [], 'attempts' => []];
  }

  /**
   * Guarda los datos en el archivo JSON.
   */
  private static function saveStorageData(array $data): void
  {
    $file = self::getStorageFilePath();
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
  }

  /**
   * Bloquea el acceso a una IP especificada.
   *
   * @param string $ip
   * @param string $reason
   * @param int $durationSeconds Duración en segundos (0 para permanente).
   * @return bool
   */
  public static function blockIp(string $ip, string $reason = 'Actividad Sospechosa', int $durationSeconds = 86400): bool
  {
    $data = self::loadStorageData();
    $expiresAt = ($durationSeconds > 0) ? (time() + $durationSeconds) : 0;

    $data['blocked'][$ip] = [
      'ip' => $ip,
      'reason' => $reason,
      'blocked_at' => date('Y-m-d H:i:s'),
      'expires_at' => $expiresAt ? date('Y-m-d H:i:s', $expiresAt) : 'permanent',
      'expires_timestamp' => $expiresAt
    ];

    self::saveStorageData($data);
    return true;
  }

  /**
   * Desbloquea una IP.
   *
   * @param string $ip
   * @return bool
   */
  public static function unblockIp(string $ip): bool
  {
    $data = self::loadStorageData();
    if (isset($data['blocked'][$ip])) {
      unset($data['blocked'][$ip]);
      self::saveStorageData($data);
      return true;
    }
    return false;
  }

  /**
   * Verifica si una IP se encuentra bloqueada.
   *
   * @param string $ip
   * @return bool
   */
  public static function isIpBlocked(string $ip): bool
  {
    $data = self::loadStorageData();

    if (!isset($data['blocked'][$ip])) {
      return false;
    }

    $info = $data['blocked'][$ip];
    $expiresTimestamp = $info['expires_timestamp'] ?? 0;

    if ($expiresTimestamp > 0 && time() > $expiresTimestamp) {
      self::unblockIp($ip);
      return false;
    }

    return true;
  }

  /**
   * Retorna la lista activa de IPs bloqueadas.
   *
   * @return array
   */
  public static function getBlockedIps(): array
  {
    $data = self::loadStorageData();
    $active = [];
    $currentTime = time();

    foreach ($data['blocked'] as $ip => $info) {
      $exp = $info['expires_timestamp'] ?? 0;
      if ($exp === 0 || $currentTime <= $exp) {
        $active[$ip] = $info;
      }
    }

    return $active;
  }

  /**
   * Registra un intento de amenaza por IP y aplica bloqueo automático tras reincidencia.
   *
   * @param string $ip
   * @param string $reason
   * @param int $threshold Umbral de intentos permitidos.
   * @param int $windowSeconds Ventana de tiempo en segundos.
   * @return bool
   */
  public static function recordIntrusionAttempt(string $ip, string $reason, int $threshold = 5, int $windowSeconds = 3600): bool
  {
    $data = self::loadStorageData();
    $currentTime = time();

    if (!isset($data['attempts'][$ip])) {
      $data['attempts'][$ip] = [];
    }

    $validAttempts = array_filter($data['attempts'][$ip], function ($timestamp) use ($currentTime, $windowSeconds) {
      return ($currentTime - $timestamp) <= $windowSeconds;
    });

    $validAttempts[] = $currentTime;
    $data['attempts'][$ip] = array_values($validAttempts);

    $blocked = false;
    if (count($data['attempts'][$ip]) >= $threshold) {
      self::blockIp($ip, "Bloqueo automático: Superó {$threshold} intentos de intrusión ({$reason})", 86400);
      $blocked = true;
      unset($data['attempts'][$ip]);
    } else {
      self::saveStorageData($data);
    }

    return $blocked;
  }

  /**
   * Comprueba si la IP del cliente actual está bloqueada y ejecuta ErrorHandler::handle403() si aplica.
   */
  public static function checkAndBlockRequest(): void
  {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (self::isIpBlocked($ip)) {
      ErrorHandler::handle403('Acceso denegado por motivos de seguridad.');
    }
  }
}
