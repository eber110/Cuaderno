<?php

namespace App\Rsc\Helper;

use Base\Module\AnalyticsModule;
use Base\Module\Session;
use Exception;
use PDO;

/**
 * Clase ActiveViewersHelper
 * 
 * Helper para gestionar el registro de presencia y recuento de usuarios activos en tiempo real (Active Viewers).
 * La información es estrictamente personal por perfil y no global del sitio web.
 */
class ActiveViewersHelper
{
  /**
   * Garantiza que la tabla de sesiones activas exista en la base de datos SQLite.
   *
   * @param PDO $pdo Instancia de conexión PDO.
   * @return void
   */
  private static function initTable(PDO $pdo): void
  {
    $sql = "
      CREATE TABLE IF NOT EXISTS active_sessions (
        session_token TEXT NOT NULL,
        profile_id TEXT NOT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (session_token, profile_id)
      );

      CREATE INDEX IF NOT EXISTS idx_active_sessions_profile ON active_sessions(profile_id, updated_at);
    ";

    $pdo->exec($sql);
  }

  /**
   * Registra o actualiza el latido (heartbeat) de un visitante para un perfil específico
   * y retorna la cantidad de usuarios activos conectados exclusivamente a ese perfil.
   *
   * @param string $profileId Nombre de usuario del perfil consultado.
   * @param string $sessionToken Token único de la sesión del visitante.
   * @return int Conteo de usuarios activos conectados a este perfil en los últimos 30 segundos.
   */
  public static function registerHeartbeat(string $profileId, string $sessionToken): int
  {
    try {
      $pdo = AnalyticsModule::getPdo();
      self::initTable($pdo);

      $profile = mb_strtolower(trim($profileId), 'UTF-8');
      if (empty($profile)) {
        return 0;
      }

      $nowUtc = gmdate('Y-m-d H:i:s');

      // Verificar si quien envía el heartbeat es el propio dueño del perfil logueado
      $isOwner = false;
      if (Session::session_active()) {
        $loggedInUser = mb_strtolower(Session::session_data("username") ?? '', 'UTF-8');
        if (!empty($loggedInUser) && $loggedInUser === $profile) {
          $isOwner = true;
        }
      }

      if (!$isOwner) {
        // Solo registrar/actualizar si es un visitante externo al perfil
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
        // Si es el dueño visualizando su panel o perfil, descartar su token para no inflar sus propios visitantes
        $stmtDel = $pdo->prepare("DELETE FROM active_sessions WHERE profile_id = :profile AND session_token = :token");
        $stmtDel->execute([
          ':profile' => $profile,
          ':token'   => $sessionToken
        ]);
      }

      // Limpiar sesiones inactivas de más de 60 segundos
      $cutoffUtc = gmdate('Y-m-d H:i:s', time() - 60);
      $stmtClean = $pdo->prepare("DELETE FROM active_sessions WHERE updated_at < :cutoff");
      $stmtClean->execute([':cutoff' => $cutoffUtc]);

      // Contar usuarios activos en los últimos 30 segundos EXCLUSIVAMENTE para este perfil
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
