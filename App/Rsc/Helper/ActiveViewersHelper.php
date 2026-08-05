<?php

namespace App\Rsc\Helper;

use Base\Module\AnalyticsModule;
use Exception;
use PDO;

/**
 * Clase ActiveViewersHelper
 * 
 * Helper independiente del proyecto para gestionar el recuento y tablas de usuarios en línea (Active Viewers)
 * sin modificar los archivos core del framework.
 */
class ActiveViewersHelper
{
  /**
   * Garantiza que la tabla de sesiones activas exista en la base de datos SQLite.
   */
  private static function initTable(PDO $pdo): void
  {
    $sql = "
      CREATE TABLE IF NOT EXISTS active_sessions (
        session_token TEXT PRIMARY KEY,
        profile_id TEXT NOT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      );

      CREATE INDEX IF NOT EXISTS idx_active_sessions_profile ON active_sessions(profile_id, updated_at);
    ";

    $pdo->exec($sql);
  }

  /**
   * Registra/actualiza el heartbeat de un visitante y retorna la cantidad de usuarios activos viendo el perfil.
   *
   * @param string $profileId Username o ID del perfil.
   * @param string $sessionToken Token único por sesión/navegador.
   * @return int Conteo de usuarios activos viendo el perfil en los últimos 30 segundos.
   */
  public static function registerHeartbeat(string $profileId, string $sessionToken): int
  {
    try {
      $pdo = AnalyticsModule::getPdo();
      self::initTable($pdo);

      $profile = mb_strtolower($profileId, 'UTF-8');
      $nowUtc = gmdate('Y-m-d H:i:s');

      // Verificar si el usuario actual logueado es el mismo dueño del perfil
      $isOwner = false;
      if (\Base\Module\Session::session_active()) {
        $loggedInUser = mb_strtolower(\Base\Module\Session::session_data("username") ?? '', 'UTF-8');
        if (!empty($loggedInUser) && $loggedInUser === $profile) {
          $isOwner = true;
        }
      }

      if (!$isOwner) {
        // Solo registrar/actualizar si NO es el propio dueño del perfil
        $stmt = $pdo->prepare("
          REPLACE INTO active_sessions (session_token, profile_id, updated_at)
          VALUES (:token, :profile, :updated)
        ");
        $stmt->execute([
          ':token'   => $sessionToken,
          ':profile' => $profile,
          ':updated' => $nowUtc
        ]);
      } else {
        // Si es el dueño, eliminar su token por si estuvo registrado antes como visitante
        $stmtDel = $pdo->prepare("DELETE FROM active_sessions WHERE session_token = :token");
        $stmtDel->execute([':token' => $sessionToken]);
      }

      // Limpiar sesiones inactivas de más de 60 segundos
      $cutoffUtc = gmdate('Y-m-d H:i:s', time() - 60);
      $stmtClean = $pdo->prepare("DELETE FROM active_sessions WHERE updated_at < :cutoff");
      $stmtClean->execute([':cutoff' => $cutoffUtc]);

      // Contar usuarios activos en los últimos 30 segundos
      $activeCutoffUtc = gmdate('Y-m-d H:i:s', time() - 30);
      $stmtCount = $pdo->prepare("
        SELECT COUNT(DISTINCT session_token) as active_count
        FROM active_sessions
        WHERE profile_id = :profile AND updated_at >= :activeCutoff
      ");
      $stmtCount->execute([
        ':profile'      => $profile,
        ':activeCutoff' => $activeCutoffUtc
      ]);

      return (int)($stmtCount->fetchColumn() ?: 0);
    } catch (Exception $e) {
      error_log("ActiveViewersHelper registerHeartbeat Error: " . $e->getMessage());
      return 0;
    }
  }
}
