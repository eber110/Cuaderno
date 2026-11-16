<?php

namespace Base\Module\Security;

use Base\Module\SecurityModule;
use Core\ErrorHandler;

/**
 * Gestor de Bloqueo de Accesos y Baneos de IP (SRP).
 * 
 * Responsabilidad Única: Administrar la lista de IPs bloqueadas, su expiración,
 * rate limiting por reincidencia y la persistencia atómica en App/Safety/blocked_ips.json.
 */
class AccessBlocker
{
  private const MAX_ATTEMPT_IPS = 5000;

  /**
   * Ejecuta una transacción atómica lectura-modificación-escritura sobre un archivo JSON con flock(LOCK_EX).
   *
   * @param string $filePath Ruta del archivo JSON.
   * @param array $default Estructura inicial si no existe o está vacío/corrupto.
   * @param callable $mutator Callback que recibe el array decodificado (por valor/retorno) o [&$data, &$modified].
   * @return mixed Valor retornado opcionalmente o los datos finales.
   */
  public static function updateJsonTransaction(string $filePath, array $default, callable $mutator): mixed
  {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
      @mkdir($dir, 0755, true);
    }

    $fp = @fopen($filePath, 'c+');
    if ($fp === false) {
      $data = $default;
      return $mutator($data);
    }

    try {
      flock($fp, LOCK_EX);
      clearstatcache(true, $filePath);
      $raw = stream_get_contents($fp);
      $decoded = ($raw !== false && trim($raw) !== '') ? json_decode($raw, true) : null;
      $data = is_array($decoded) ? $decoded : $default;

      $dirty = false;
      $result = $mutator($data, $dirty);

      if ($dirty) {
        rewind($fp);
        ftruncate($fp, 0);
        fwrite($fp, (string)json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        fflush($fp);
      }

      return $result;
    } finally {
      flock($fp, LOCK_UN);
      fclose($fp);
    }
  }

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
   * Carga los datos guardados en JSON bajo bloqueo compartido.
   */
  private static function loadStorageData(): array
  {
    $file = self::getStorageFilePath();
    if (!file_exists($file)) {
      return ['blocked' => [], 'attempts' => []];
    }

    $fp = @fopen($file, 'r');
    if ($fp === false) {
      return ['blocked' => [], 'attempts' => []];
    }

    try {
      flock($fp, LOCK_SH);
      $content = stream_get_contents($fp);
      $data = ($content !== false && trim($content) !== '') ? json_decode($content, true) : null;
      return is_array($data) ? $data : ['blocked' => [], 'attempts' => []];
    } finally {
      flock($fp, LOCK_UN);
      fclose($fp);
    }
  }

  /**
   * Poda entradas expiradas y limita el crecimiento de `attempts` para evitar agotamiento de disco/RAM.
   */
  private static function pruneStorageData(array &$data, int $windowSeconds = 3600): void
  {
    $now = time();
    if (!isset($data['blocked']) || !is_array($data['blocked'])) {
      $data['blocked'] = [];
    }
    if (!isset($data['attempts']) || !is_array($data['attempts'])) {
      $data['attempts'] = [];
    }

    foreach ($data['blocked'] as $bIp => $info) {
      $exp = (int)($info['expires_timestamp'] ?? 0);
      if ($exp > 0 && $now > $exp) {
        unset($data['blocked'][$bIp]);
      }
    }

    foreach ($data['attempts'] as $aIp => $timestamps) {
      if (!is_array($timestamps)) {
        unset($data['attempts'][$aIp]);
        continue;
      }
      $recent = array_values(array_filter($timestamps, static fn($ts): bool => ($now - (int)$ts) <= $windowSeconds));
      if (empty($recent)) {
        unset($data['attempts'][$aIp]);
      } else {
        $data['attempts'][$aIp] = $recent;
      }
    }

    if (count($data['attempts']) > self::MAX_ATTEMPT_IPS) {
      $data['attempts'] = array_slice($data['attempts'], -self::MAX_ATTEMPT_IPS, null, true);
    }
  }

  /**
   * Bloquea el acceso a una IP especificada (nunca bloquea proxies de confianza configurados).
   *
   * @param string $ip
   * @param string $reason
   * @param int $durationSeconds Duración en segundos (0 para permanente).
   * @return bool
   */
  public static function blockIp(string $ip, string $reason = 'Actividad Sospechosa', int $durationSeconds = 86400): bool
  {
    if (class_exists(SecurityModule::class) && SecurityModule::isTrustedProxy($ip)) {
      return false;
    }

    self::updateJsonTransaction(
      self::getStorageFilePath(),
      ['blocked' => [], 'attempts' => []],
      static function (array &$data, bool &$dirty) use ($ip, $reason, $durationSeconds): void {
        self::pruneStorageData($data);
        $expiresAt = ($durationSeconds > 0) ? (time() + $durationSeconds) : 0;

        $data['blocked'][$ip] = [
          'ip' => $ip,
          'reason' => $reason,
          'blocked_at' => date('Y-m-d H:i:s'),
          'expires_at' => $expiresAt ? date('Y-m-d H:i:s', $expiresAt) : 'permanent',
          'expires_timestamp' => $expiresAt
        ];
        $dirty = true;
      }
    );

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
    return (bool)self::updateJsonTransaction(
      self::getStorageFilePath(),
      ['blocked' => [], 'attempts' => []],
      static function (array &$data, bool &$dirty) use ($ip): bool {
        if (isset($data['blocked'][$ip])) {
          unset($data['blocked'][$ip]);
          $dirty = true;
          return true;
        }
        return false;
      }
    );
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
    $expiresTimestamp = (int)($info['expires_timestamp'] ?? 0);

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

    foreach (($data['blocked'] ?? []) as $ip => $info) {
      $exp = (int)($info['expires_timestamp'] ?? 0);
      if ($exp === 0 || $currentTime <= $exp) {
        $active[$ip] = $info;
      }
    }

    return $active;
  }

  /**
   * Registra un intento de amenaza por IP y aplica bloqueo automático atómico tras reincidencia.
   *
   * @param string $ip
   * @param string $reason
   * @param int $threshold Umbral de intentos permitidos.
   * @param int $windowSeconds Ventana de tiempo en segundos.
   * @return bool
   */
  public static function recordIntrusionAttempt(string $ip, string $reason, int $threshold = 5, int $windowSeconds = 3600): bool
  {
    if ($ip === '' || $ip === 'unknown' || (class_exists(SecurityModule::class) && SecurityModule::isTrustedProxy($ip))) {
      return false;
    }

    return (bool)self::updateJsonTransaction(
      self::getStorageFilePath(),
      ['blocked' => [], 'attempts' => []],
      static function (array &$data, bool &$dirty) use ($ip, $reason, $threshold, $windowSeconds): bool {
        self::pruneStorageData($data, $windowSeconds);
        $currentTime = time();

        $existing = is_array($data['attempts'][$ip] ?? null) ? $data['attempts'][$ip] : [];
        $validAttempts = array_values(array_filter($existing, static fn($ts): bool => ($currentTime - (int)$ts) <= $windowSeconds));
        $validAttempts[] = $currentTime;
        $data['attempts'][$ip] = $validAttempts;
        $dirty = true;

        if (count($validAttempts) >= $threshold) {
          $expiresAt = $currentTime + 86400;
          $data['blocked'][$ip] = [
            'ip' => $ip,
            'reason' => "Bloqueo automático: Superó {$threshold} intentos de intrusión ({$reason})",
            'blocked_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'expires_timestamp' => $expiresAt
          ];
          unset($data['attempts'][$ip]);
          return true;
        }

        return false;
      }
    );
  }

  /**
   * Comprueba si la IP del cliente actual resuelta está bloqueada y ejecuta ErrorHandler::handle403().
   */
  public static function checkAndBlockRequest(): void
  {
    $ip = class_exists(SecurityModule::class)
      ? SecurityModule::resolveClientIp()
      : ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

    if (self::isIpBlocked($ip)) {
      ErrorHandler::handle403('Acceso denegado por motivos de seguridad.');
    }
  }
}
