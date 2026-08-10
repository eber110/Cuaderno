<?php

namespace Base\Module\Security;

/**
 * Registrador de Intrusiones y Amenazas de Seguridad (SRP).
 * 
 * Responsabilidad Única: Formatear y registrar registros detallados de amenazas,
 * peticiones sospechosas y actividades maliciosas en Logs/intrusions.log.
 */
class IntrusionLogger
{
  /**
   * Obtiene la ruta al directorio de seguridad App/Safety.
   */
  public static function getSafetyDirectory(): string
  {
    $dir = defined('ROUTE_SAFETY') ? rtrim(ROUTE_SAFETY, '/') : (defined('ROOT_PATH') ? ROOT_PATH . '/App/Safety' : str_replace('\\', '/', getcwd()) . '/App/Safety');
    if (!is_dir($dir)) {
      @mkdir($dir, 0755, true);
    }
    return $dir;
  }

  /**
   * Obtiene la ruta al archivo de log de intrusiones (.log o .json).
   */
  public static function getLogFilePath(string $extension = 'log'): string
  {
    return self::getSafetyDirectory() . '/intrusions.' . ltrim($extension, '.');
  }

  /**
   * Registra un evento de intrusión.
   *
   * @param string $reason Descripción o motivo de la amenaza.
   * @param array $context Contexto adicional de la petición.
   * @param bool $incrementCount Si es true, notifica a AccessBlocker para evaluar reincidencia de la IP.
   */
  public static function log(string $reason, array $context = [], bool $incrementCount = true): void
  {
    $ip = $context['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $uri = $context['url'] ?? $context['uri'] ?? $_SERVER['REQUEST_URI'] ?? 'unknown';
    $method = $context['method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $timestamp = date('Y-m-d H:i:s');
    $logEntry = [
      'timestamp' => $timestamp,
      'reason' => $reason,
      'ip' => $ip,
      'method' => $method,
      'uri' => $uri,
      'user_agent' => $userAgent,
      'context' => $context
    ];

    // 1. Guardar en App/Safety/intrusions.log
    $logFile = self::getLogFilePath('log');
    $formattedMessage = sprintf(
      "[%s] INTRUSION DETECTED | IP: %s | Method: %s | URI: %s | Reason: %s | UserAgent: %s\n",
      $timestamp,
      $ip,
      $method,
      $uri,
      $reason,
      $userAgent
    );
    if (!empty($context['details'])) {
      $formattedMessage .= "   Details: " . json_encode($context['details'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }
    @file_put_contents($logFile, $formattedMessage, FILE_APPEND | LOCK_EX);

    // 2. Guardar en App/Safety/intrusions.json
    $jsonFile = self::getLogFilePath('json');
    $jsonList = [];
    if (file_exists($jsonFile)) {
      $jsonContent = file_get_contents($jsonFile);
      $jsonList = json_decode($jsonContent, true) ?? [];
    }
    $jsonList[] = $logEntry;
    // Mantener los últimos 500 registros en JSON
    if (count($jsonList) > 500) {
      $jsonList = array_slice($jsonList, -500);
    }
    @file_put_contents($jsonFile, json_encode($jsonList, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);

    if ($incrementCount && class_exists('\\Base\\Module\\Security\\AccessBlocker')) {
      AccessBlocker::recordIntrusionAttempt($ip, $reason);
    }
  }

  /**
   * Lee las últimas entradas registradas en el log de intrusiones.
   *
   * @param int $lines
   * @return array
   */
  public static function getRecentLogs(int $lines = 100): array
  {
    $file = self::getLogFilePath();
    if (!file_exists($file)) {
      return [];
    }

    $content = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($content === false) {
      return [];
    }

    return array_slice($content, -$lines);
  }
}
