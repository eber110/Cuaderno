<?php

namespace App\DatabaseComponent;

use PDO;
use Exception;

/**
 * Clase DatabaseMaintenance
 * 
 * Sistema centralizado de depuración, auditoría, migración y mantenimiento de la base de datos SQLite.
 * 
 * Funcionalidades principales:
 * 1. Detección y eliminación de registros huérfanos en todas las tablas con relación a usuarios.
 * 2. Unificación y sincronización de index_user como identificador único y seguro en cada tabla.
 * 3. Actualización de esquemas (columnas index_user faltantes e índices de alto rendimiento).
 * 4. Optimización de almacenamiento (PRAGMA optimize, VACUUM).
 */
class DatabaseMaintenance
{
  /**
   * Obtiene la conexión PDO a la base de datos activa.
   *
   * @param PDO|null $pdo Conexión existente opcional.
   * @return PDO
   */
  public static function getPdo(?PDO $pdo = null): PDO
  {
    if ($pdo !== null) {
      return $pdo;
    }

    if (class_exists('\Base\Module\AnalyticsModule')) {
      return \Base\Module\AnalyticsModule::getPdo();
    }

    $baseDir = defined('ROUTE_DATABASE') ? rtrim(ROUTE_DATABASE, '/\\') : (defined('ROOT_PATH') ? ROOT_PATH . '/Database' : getcwd() . '/Database');
    $dbFile = (defined('DB_DRIVER') && DB_DRIVER === 'sqlite' && defined('BD') && !empty(BD)) ? BD : $baseDir . '/clikhub.sqlite';

    if (!str_starts_with($dbFile, '/') && !preg_match('/^[a-zA-Z]:[\\\\\/]/', $dbFile)) {
      $dbFile = (defined('ROOT_PATH') ? rtrim(ROOT_PATH, '/\\') : getcwd()) . '/' . ltrim($dbFile, '/\\');
    }

    $instance = new PDO("sqlite:" . $dbFile, null, null, [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_TIMEOUT            => 5
    ]);

    $instance->exec("PRAGMA journal_mode = WAL;");
    $instance->exec("PRAGMA synchronous = NORMAL;");
    $instance->exec("PRAGMA busy_timeout = 5000;");

    return $instance;
  }

  /**
   * Asegura que todas las tablas relacionadas con usuarios cuenten con la columna index_user
   * y sus correspondientes índices de rendimiento.
   *
   * @param PDO|null $pdo Instancia PDO.
   * @return array Registro de cambios aplicados.
   */
  public static function ensureSchema(?PDO $pdo = null): array
  {
    $db = self::getPdo($pdo);
    $log = [];

    // Tablas que deben contener index_user
    $tables = [
      "user_designs",
      "profile_views",
      "link_clicks",
      "user_subscriptions_cache",
      "active_sessions",
      "lemon_squeezy_orders",
      "lemon_squeezy_subscriptions",
      "userroles",
      "analytics_events"
    ];

    foreach ($tables as $table) {
      try {
        // Verificar si la tabla existe
        $tableExists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$table}'")->fetchColumn();
        if (!$tableExists) {
          continue;
        }

        // Obtener columnas de la tabla
        $colsStmt = $db->query("PRAGMA table_info({$table})");
        $cols = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $colNames = array_map(fn($c) => $c['name'] ?? '', $cols);

        if (!in_array('index_user', $colNames, true)) {
          $db->exec("ALTER TABLE {$table} ADD COLUMN index_user VARCHAR(64) NULL;");
          $log[] = "Columna 'index_user' agregada exitosamente a la tabla '{$table}'.";
        }

        // Crear índice en index_user si no existe
        $indexName = "idx_{$table}_index_user";
        $db->exec("CREATE INDEX IF NOT EXISTS {$indexName} ON {$table}(index_user);");
        $log[] = "Índice '{$indexName}' verificado/creado en '{$table}'.";

      } catch (Exception $e) {
        $log[] = "Error al migrar estructura en '{$table}': " . $e->getMessage();
      }
    }

    // Crear índice único en users.index_user
    try {
      $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_index_user ON users(index_user);");
      $log[] = "Índice único 'idx_users_index_user' verificado/creado en 'users'.";
    } catch (Exception $e) {
      $log[] = "Aviso en índice único users(index_user): " . $e->getMessage();
    }

    // Triggers automáticos para mantener index_user en profile_views y link_clicks si NEW.index_user es NULL
    try {
      $db->exec("
        CREATE TRIGGER IF NOT EXISTS trg_profile_views_index_user 
        AFTER INSERT ON profile_views 
        FOR EACH ROW 
        WHEN NEW.index_user IS NULL 
        BEGIN 
          UPDATE profile_views 
          SET index_user = (
            CASE 
              WHEN EXISTS (SELECT 1 FROM users WHERE users.username = NEW.profile_id) 
                THEN (SELECT index_user FROM users WHERE users.username = NEW.profile_id)
              WHEN EXISTS (SELECT 1 FROM users WHERE users.index_user = NEW.profile_id)
                THEN NEW.profile_id
              ELSE NULL 
            END
          ) 
          WHERE id = NEW.id; 
        END;
      ");

      $db->exec("
        CREATE TRIGGER IF NOT EXISTS trg_link_clicks_index_user 
        AFTER INSERT ON link_clicks 
        FOR EACH ROW 
        WHEN NEW.index_user IS NULL 
        BEGIN 
          UPDATE link_clicks 
          SET index_user = (
            CASE 
              WHEN EXISTS (SELECT 1 FROM users WHERE users.username = NEW.profile_id) 
                THEN (SELECT index_user FROM users WHERE users.username = NEW.profile_id)
              WHEN EXISTS (SELECT 1 FROM users WHERE users.index_user = NEW.profile_id)
                THEN NEW.profile_id
              ELSE NULL 
            END
          ) 
          WHERE id = NEW.id; 
        END;
      ");
      $log[] = "Triggers de integridad automática para index_user configurados.";
    } catch (Exception $e) {
      $log[] = "Aviso en creación de triggers: " . $e->getMessage();
    }

    return [
      "success" => true,
      "log"     => $log
    ];
  }

  /**
   * Sincroniza y propaga el valor de index_user a todos los registros de cada tabla,
   * asignando un index_user seguro a usuarios existentes que no lo tengan.
   *
   * @param PDO|null $pdo Instancia PDO.
   * @return array Resumen de registros actualizados por tabla.
   */
  public static function syncIndexUsers(?PDO $pdo = null): array
  {
    $db = self::getPdo($pdo);
    $updated = [];

    // 1. users: Generar index_user para cualquier usuario que tenga NULL o vacío
    $usersWithoutIndex = $db->query("SELECT user_id, username FROM users WHERE index_user IS NULL OR TRIM(index_user) = ''")->fetchAll();
    $usersUpdatedCount = 0;
    if (!empty($usersWithoutIndex)) {
      $stmtUpUser = $db->prepare("UPDATE users SET index_user = :index_user WHERE user_id = :user_id");
      foreach ($usersWithoutIndex as $u) {
        $newIndex = bin2hex(random_bytes(15));
        $stmtUpUser->execute([
          ':index_user' => $newIndex,
          ':user_id'    => $u['user_id']
        ]);
        $usersUpdatedCount++;
      }
    }
    $updated['users'] = $usersUpdatedCount;

    // 2. user_designs: Actualizar index_user enlazando con users.username
    $sqlDesigns = "
      UPDATE user_designs 
      SET index_user = (SELECT index_user FROM users WHERE users.username = user_designs.username)
      WHERE (index_user IS NULL OR TRIM(index_user) = '') 
        AND username IN (SELECT username FROM users);
    ";
    $updated['user_designs'] = $db->exec($sqlDesigns) ?: 0;

    // 3. profile_views: Actualizar index_user y normalizar profile_id
    $sqlViews = "
      UPDATE profile_views 
      SET index_user = (SELECT index_user FROM users WHERE users.username = profile_views.profile_id)
      WHERE (index_user IS NULL OR TRIM(index_user) = '') 
        AND profile_id IN (SELECT username FROM users);
    ";
    $updated['profile_views_index'] = $db->exec($sqlViews) ?: 0;

    // También actualizar profile_id con el index_user para consistencia
    $sqlViewsProfileId = "
      UPDATE profile_views 
      SET profile_id = index_user 
      WHERE index_user IS NOT NULL 
        AND TRIM(index_user) != '' 
        AND profile_id != index_user;
    ";
    $updated['profile_views_profile_id_sync'] = $db->exec($sqlViewsProfileId) ?: 0;

    // 4. link_clicks: Actualizar index_user y normalizar profile_id
    $sqlClicks = "
      UPDATE link_clicks 
      SET index_user = (SELECT index_user FROM users WHERE users.username = link_clicks.profile_id)
      WHERE (index_user IS NULL OR TRIM(index_user) = '') 
        AND profile_id IN (SELECT username FROM users);
    ";
    $updated['link_clicks_index'] = $db->exec($sqlClicks) ?: 0;

    $sqlClicksProfileId = "
      UPDATE link_clicks 
      SET profile_id = index_user 
      WHERE index_user IS NOT NULL 
        AND TRIM(index_user) != '' 
        AND profile_id != index_user;
    ";
    $updated['link_clicks_profile_id_sync'] = $db->exec($sqlClicksProfileId) ?: 0;

    // 5. active_sessions: Actualizar index_user
    $sqlSessions = "
      UPDATE active_sessions 
      SET index_user = (SELECT index_user FROM users WHERE users.username = active_sessions.profile_id)
      WHERE (index_user IS NULL OR TRIM(index_user) = '') 
        AND profile_id IN (SELECT username FROM users);
    ";
    $updated['active_sessions'] = $db->exec($sqlSessions) ?: 0;

    // 6. user_subscriptions_cache: Asignar index_user
    // Caso A: Si user_id coincide con username
    $sqlSubA = "
      UPDATE user_subscriptions_cache 
      SET index_user = (SELECT index_user FROM users WHERE users.username = user_subscriptions_cache.user_id)
      WHERE (index_user IS NULL OR TRIM(index_user) = '') 
        AND user_id IN (SELECT username FROM users);
    ";
    $countSubA = $db->exec($sqlSubA) ?: 0;

    // Caso B: Si user_id es numérico y coincide con user_id
    $sqlSubB = "
      UPDATE user_subscriptions_cache 
      SET index_user = (SELECT index_user FROM users WHERE CAST(users.user_id AS TEXT) = user_subscriptions_cache.user_id)
      WHERE (index_user IS NULL OR TRIM(index_user) = '') 
        AND user_id IN (SELECT CAST(user_id AS TEXT) FROM users);
    ";
    $countSubB = $db->exec($sqlSubB) ?: 0;

    // Caso C: Si username está poblado
    $sqlSubC = "
      UPDATE user_subscriptions_cache 
      SET index_user = (SELECT index_user FROM users WHERE users.username = user_subscriptions_cache.username)
      WHERE (index_user IS NULL OR TRIM(index_user) = '') 
        AND username IN (SELECT username FROM users);
    ";
    $countSubC = $db->exec($sqlSubC) ?: 0;

    $updated['user_subscriptions_cache'] = $countSubA + $countSubB + $countSubC;

    // 7. userroles: Asignar index_user
    $sqlRoles = "
      UPDATE userroles 
      SET index_user = (SELECT index_user FROM users WHERE users.user_id = userroles.user_id)
      WHERE (index_user IS NULL OR TRIM(index_user) = '');
    ";
    $updated['userroles'] = $db->exec($sqlRoles) ?: 0;

    // 8. lemon_squeezy_orders & subscriptions
    $sqlOrders = "
      UPDATE lemon_squeezy_orders 
      SET index_user = (
        CASE 
          WHEN EXISTS (SELECT 1 FROM users WHERE CAST(users.user_id AS TEXT) = lemon_squeezy_orders.user_id)
            THEN (SELECT index_user FROM users WHERE CAST(users.user_id AS TEXT) = lemon_squeezy_orders.user_id)
          WHEN EXISTS (SELECT 1 FROM users WHERE users.username = lemon_squeezy_orders.user_id)
            THEN (SELECT index_user FROM users WHERE users.username = lemon_squeezy_orders.user_id)
          WHEN EXISTS (SELECT 1 FROM users WHERE users.index_user = lemon_squeezy_orders.user_id)
            THEN lemon_squeezy_orders.user_id
          ELSE NULL 
        END
      )
      WHERE (index_user IS NULL OR TRIM(index_user) = '') AND user_id IS NOT NULL;
    ";
    $updated['lemon_squeezy_orders'] = $db->exec($sqlOrders) ?: 0;

    $sqlSubs = "
      UPDATE lemon_squeezy_subscriptions 
      SET index_user = (
        CASE 
          WHEN EXISTS (SELECT 1 FROM users WHERE CAST(users.user_id AS TEXT) = lemon_squeezy_subscriptions.user_id)
            THEN (SELECT index_user FROM users WHERE CAST(users.user_id AS TEXT) = lemon_squeezy_subscriptions.user_id)
          WHEN EXISTS (SELECT 1 FROM users WHERE users.username = lemon_squeezy_subscriptions.user_id)
            THEN (SELECT index_user FROM users WHERE users.username = lemon_squeezy_subscriptions.user_id)
          WHEN EXISTS (SELECT 1 FROM users WHERE users.index_user = lemon_squeezy_subscriptions.user_id)
            THEN lemon_squeezy_subscriptions.user_id
          ELSE NULL 
        END
      )
      WHERE (index_user IS NULL OR TRIM(index_user) = '') AND user_id IS NOT NULL;
    ";
    $updated['lemon_squeezy_subscriptions'] = $db->exec($sqlSubs) ?: 0;

    return [
      "success" => true,
      "updated" => $updated
    ];
  }

  /**
   * Audita exhaustivamente la base de datos para detectar registros huérfanos
   * que no pertenezcan a ningún usuario registrado en la tabla users.
   *
   * @param PDO|null $pdo Instancia PDO.
   * @return array Reporte diagnóstico detallado por tabla.
   */
  public static function auditOrphans(?PDO $pdo = null): array
  {
    $db = self::getPdo($pdo);
    $report = [
      "has_orphans"   => false,
      "total_orphans" => 0,
      "tables"        => []
    ];

    // 1. user_designs huérfanos
    $designs = $db->query("
      SELECT id, username, profile, index_user 
      FROM user_designs 
      WHERE (index_user IS NULL OR index_user NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL))
        AND username NOT IN (SELECT username FROM users)
    ")->fetchAll(PDO::FETCH_ASSOC);

    $report['tables']['user_designs'] = [
      'count'   => count($designs),
      'samples' => array_slice($designs, 0, 10)
    ];

    // 2. profile_views huérfanos
    $views = $db->query("
      SELECT id, profile_id, index_user, ip_address, created_at 
      FROM profile_views 
      WHERE (index_user IS NULL OR index_user NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL))
        AND profile_id NOT IN (SELECT username FROM users)
        AND profile_id NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL)
    ")->fetchAll(PDO::FETCH_ASSOC);

    $report['tables']['profile_views'] = [
      'count'   => count($views),
      'samples' => array_slice($views, 0, 10)
    ];

    // 3. link_clicks huérfanos
    $clicks = $db->query("
      SELECT id, link_id, profile_id, index_user, created_at 
      FROM link_clicks 
      WHERE (index_user IS NULL OR index_user NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL))
        AND profile_id NOT IN (SELECT username FROM users)
        AND profile_id NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL)
    ")->fetchAll(PDO::FETCH_ASSOC);

    $report['tables']['link_clicks'] = [
      'count'   => count($clicks),
      'samples' => array_slice($clicks, 0, 10)
    ];

    // 4. user_subscriptions_cache huérfanos
    $subs = $db->query("
      SELECT user_id, username, index_user, is_premium 
      FROM user_subscriptions_cache 
      WHERE (index_user IS NULL OR index_user NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL))
        AND (username IS NULL OR username = '' OR username NOT IN (SELECT username FROM users))
        AND user_id NOT IN (SELECT CAST(user_id AS TEXT) FROM users)
        AND user_id NOT IN (SELECT username FROM users)
        AND user_id NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL)
    ")->fetchAll(PDO::FETCH_ASSOC);

    $report['tables']['user_subscriptions_cache'] = [
      'count'   => count($subs),
      'samples' => array_slice($subs, 0, 10)
    ];

    // 5. active_sessions huérfanos
    $sessions = $db->query("
      SELECT session_token, profile_id, index_user 
      FROM active_sessions 
      WHERE (index_user IS NULL OR index_user NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL))
        AND profile_id NOT IN (SELECT username FROM users)
        AND profile_id NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL)
    ")->fetchAll(PDO::FETCH_ASSOC);

    $report['tables']['active_sessions'] = [
      'count'   => count($sessions),
      'samples' => array_slice($sessions, 0, 10)
    ];

    // 6. userroles huérfanos
    $userroles = $db->query("
      SELECT user_role_id, user_id, index_user 
      FROM userroles 
      WHERE (index_user IS NULL OR index_user NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL))
        AND user_id NOT IN (SELECT user_id FROM users)
    ")->fetchAll(PDO::FETCH_ASSOC);

    $report['tables']['userroles'] = [
      'count'   => count($userroles),
      'samples' => array_slice($userroles, 0, 10)
    ];

    // 7. lemon_squeezy_orders huérfanos
    $orders = $db->query("
      SELECT id, lemon_order_id, user_id, index_user 
      FROM lemon_squeezy_orders 
      WHERE user_id IS NOT NULL AND user_id != ''
        AND (index_user IS NULL OR index_user NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL))
        AND user_id NOT IN (SELECT CAST(user_id AS TEXT) FROM users)
        AND user_id NOT IN (SELECT username FROM users)
        AND user_id NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL)
    ")->fetchAll(PDO::FETCH_ASSOC);

    $report['tables']['lemon_squeezy_orders'] = [
      'count'   => count($orders),
      'samples' => array_slice($orders, 0, 10)
    ];

    // 8. lemon_squeezy_subscriptions huérfanos
    $lemonSubs = $db->query("
      SELECT id, lemon_subscription_id, user_id, index_user 
      FROM lemon_squeezy_subscriptions 
      WHERE user_id IS NOT NULL AND user_id != ''
        AND (index_user IS NULL OR index_user NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL))
        AND user_id NOT IN (SELECT CAST(user_id AS TEXT) FROM users)
        AND user_id NOT IN (SELECT username FROM users)
        AND user_id NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL)
    ")->fetchAll(PDO::FETCH_ASSOC);

    $report['tables']['lemon_squeezy_subscriptions'] = [
      'count'   => count($lemonSubs),
      'samples' => array_slice($lemonSubs, 0, 10)
    ];

    $total = 0;
    foreach ($report['tables'] as $t => $info) {
      $total += $info['count'];
    }

    $report['total_orphans'] = $total;
    $report['has_orphans']   = $total > 0;

    return $report;
  }

  /**
   * Elimina de forma transaccional y controlada todos los registros huérfanos detectados.
   *
   * @param PDO|null $pdo Instancia PDO.
   * @return array Resumen de registros eliminados por tabla.
   */
  public static function cleanOrphans(?PDO $pdo = null): array
  {
    $db = self::getPdo($pdo);
    $deleted = [];

    $db->beginTransaction();
    try {
      // 1. user_designs
      $sqlDesigns = "
        DELETE FROM user_designs 
        WHERE (index_user IS NULL OR index_user NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL))
          AND username NOT IN (SELECT username FROM users);
      ";
      $deleted['user_designs'] = $db->exec($sqlDesigns) ?: 0;

      // 2. profile_views
      $sqlViews = "
        DELETE FROM profile_views 
        WHERE (index_user IS NULL OR index_user NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL))
          AND profile_id NOT IN (SELECT username FROM users)
          AND profile_id NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL);
      ";
      $deleted['profile_views'] = $db->exec($sqlViews) ?: 0;

      // 3. link_clicks
      $sqlClicks = "
        DELETE FROM link_clicks 
        WHERE (index_user IS NULL OR index_user NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL))
          AND profile_id NOT IN (SELECT username FROM users)
          AND profile_id NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL);
      ";
      $deleted['link_clicks'] = $db->exec($sqlClicks) ?: 0;

      // 4. user_subscriptions_cache
      $sqlSubs = "
        DELETE FROM user_subscriptions_cache 
        WHERE (index_user IS NULL OR index_user NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL))
          AND (username IS NULL OR username = '' OR username NOT IN (SELECT username FROM users))
          AND user_id NOT IN (SELECT CAST(user_id AS TEXT) FROM users)
          AND user_id NOT IN (SELECT username FROM users)
          AND user_id NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL);
      ";
      $deleted['user_subscriptions_cache'] = $db->exec($sqlSubs) ?: 0;

      // 5. active_sessions
      $sqlSessions = "
        DELETE FROM active_sessions 
        WHERE (index_user IS NULL OR index_user NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL))
          AND profile_id NOT IN (SELECT username FROM users)
          AND profile_id NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL);
      ";
      $deleted['active_sessions'] = $db->exec($sqlSessions) ?: 0;

      // 6. userroles
      $sqlRoles = "
        DELETE FROM userroles 
        WHERE (index_user IS NULL OR index_user NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL))
          AND user_id NOT IN (SELECT user_id FROM users);
      ";
      $deleted['userroles'] = $db->exec($sqlRoles) ?: 0;

      // 7. lemon_squeezy_orders
      $sqlOrders = "
        DELETE FROM lemon_squeezy_orders 
        WHERE user_id IS NOT NULL AND user_id != ''
          AND (index_user IS NULL OR index_user NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL))
          AND user_id NOT IN (SELECT CAST(user_id AS TEXT) FROM users)
          AND user_id NOT IN (SELECT username FROM users)
          AND user_id NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL);
      ";
      $deleted['lemon_squeezy_orders'] = $db->exec($sqlOrders) ?: 0;

      // 8. lemon_squeezy_subscriptions
      $sqlLemonSubs = "
        DELETE FROM lemon_squeezy_subscriptions 
        WHERE user_id IS NOT NULL AND user_id != ''
          AND (index_user IS NULL OR index_user NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL))
          AND user_id NOT IN (SELECT CAST(user_id AS TEXT) FROM users)
          AND user_id NOT IN (SELECT username FROM users)
          AND user_id NOT IN (SELECT index_user FROM users WHERE index_user IS NOT NULL);
      ";
      $deleted['lemon_squeezy_subscriptions'] = $db->exec($sqlLemonSubs) ?: 0;

      $db->commit();

      $totalDeleted = array_sum($deleted);

      return [
        "success"       => true,
        "total_deleted" => $totalDeleted,
        "details"       => $deleted
      ];
    } catch (Exception $e) {
      $db->rollBack();
      return [
        "success" => false,
        "error"   => $e->getMessage(),
        "details" => $deleted
      ];
    }
  }

  /**
   * Consolida la tabla user_subscriptions_cache para que cada index_user tenga una única fila.
   *
   * @param PDO|null $pdo Instancia PDO.
   * @return int Cantidad de registros consolidados.
   */
  public static function consolidateSubscriptionCache(?PDO $pdo = null): int
  {
    $db = self::getPdo($pdo);
    $consolidatedCount = 0;

    // Buscar usuarios válidos
    $users = $db->query("SELECT user_id, username, index_user FROM users WHERE index_user IS NOT NULL AND index_user != ''")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
      $indexUser = $user['index_user'];
      $username  = $user['username'];
      $userId    = (string)$user['user_id'];

      // Encontrar todos los registros en caché asociados a este usuario
      $stmt = $db->prepare("
        SELECT * FROM user_subscriptions_cache 
        WHERE index_user = :idx OR user_id = :uid OR user_id = :uname OR username = :uname
        ORDER BY is_premium DESC, updated_at DESC
      ");
      $stmt->execute([
        ':idx'   => $indexUser,
        ':uid'   => $userId,
        ':uname' => $username
      ]);
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if (count($rows) > 1) {
        // Tomar el más relevante (preferir is_premium = 1 o el más actualizado)
        $best = $rows[0];

        // Eliminar duplicados
        $stmtDel = $db->prepare("
          DELETE FROM user_subscriptions_cache 
          WHERE index_user = :idx OR user_id = :uid OR user_id = :uname OR username = :uname
        ");
        $stmtDel->execute([
          ':idx'   => $indexUser,
          ':uid'   => $userId,
          ':uname' => $username
        ]);

        // Re-insertar como único registro con user_id = index_user y index_user = index_user
        $stmtIns = $db->prepare("
          INSERT OR REPLACE INTO user_subscriptions_cache 
          (user_id, index_user, username, is_premium, status, renews_at, ends_at, updated_at)
          VALUES (:uid, :idx, :uname, :prem, :status, :renews, :ends, :updated)
        ");
        $stmtIns->execute([
          ':uid'     => $indexUser,
          ':idx'     => $indexUser,
          ':uname'   => $username,
          ':prem'    => $best['is_premium'] ?? 0,
          ':status'  => $best['status'] ?? 'inactive',
          ':renews'  => $best['renews_at'] ?? '',
          ':ends'    => $best['ends_at'] ?? '',
          ':updated' => $best['updated_at'] ?? date('Y-m-d H:i:s')
        ]);

        $consolidatedCount++;
      } elseif (count($rows) === 1) {
        // Asegurar que index_user y user_id estén normalizados
        $stmtNorm = $db->prepare("
          UPDATE user_subscriptions_cache 
          SET index_user = :idx, username = :uname 
          WHERE user_id = :orig_id
        ");
        $stmtNorm->execute([
          ':idx'     => $indexUser,
          ':uname'   => $username,
          ':orig_id' => $rows[0]['user_id']
        ]);
      }
    }

    return $consolidatedCount;
  }

  /**
   * Ejecuta la optimización y compactación del archivo SQLite para liberar espacio físico.
   *
   * @param PDO|null $pdo Instancia PDO.
   * @return array Estado de la optimización.
   */
  public static function vacuumAndOptimize(?PDO $pdo = null): array
  {
    $db = self::getPdo($pdo);
    try {
      $db->exec("PRAGMA optimize;");
      $db->exec("VACUUM;");
      return [
        "success" => true,
        "message" => "Base de datos optimizada y compactada con éxito (VACUUM)."
      ];
    } catch (Exception $e) {
      return [
        "success" => false,
        "error"   => $e->getMessage()
      ];
    }
  }

  /**
   * Ejecuta el ciclo integral de mantenimiento: migración de esquemas, sincronización de index_user,
   * consolidación de cachés, depuración de registros huérfanos y compactación.
   *
   * @param PDO|null $pdo Instancia PDO.
   * @param bool $cleanOrphans True para eliminar registros huérfanos.
   * @return array Informe completo de la operación.
   */
  public static function runFullMaintenance(?PDO $pdo = null, bool $cleanOrphans = true): array
  {
    $db = self::getPdo($pdo);
    $report = [];

    // Paso 1: Asegurar esquemas, columnas index_user faltantes e índices
    $report['schema'] = self::ensureSchema($db);

    // Paso 2: Sincronizar index_user en registros legítimos existentes
    $report['sync'] = self::syncIndexUsers($db);

    // Paso 3: Auditar huérfanos antes de limpiar
    $report['audit_before'] = self::auditOrphans($db);

    // Paso 4: Limpiar huérfanos si se solicitó
    if ($cleanOrphans) {
      $report['cleaning'] = self::cleanOrphans($db);
      $report['audit_after'] = self::auditOrphans($db);
    }

    // Paso 5: Consolidar cachés
    $report['consolidated_subscriptions'] = self::consolidateSubscriptionCache($db);

    // Paso 6: Optimización y VACUUM
    $report['vacuum'] = self::vacuumAndOptimize($db);

    return [
      "success"   => true,
      "timestamp" => date("Y-m-d H:i:s"),
      "report"    => $report
    ];
  }

  /**
   * Redistribuye uniformemente las fechas de los registros existentes en profile_views y link_clicks
   * a lo largo de los meses transcurridos del año en curso (Enero a Octubre), manteniendo la integridad
   * de los datos, el aislamiento por usuario (index_user) y proporciones realistas.
   *
   * @param PDO|null $pdo Instancia PDO.
   * @return array Resumen de la redistribución con conteos mensuales y totales modificados.
   */
  public static function spreadAnalyticsDatesAcrossYear(?PDO $pdo = null): array
  {
    $db = self::getPdo($pdo);

    $views  = $db->query("SELECT id FROM profile_views ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
    $clicks = $db->query("SELECT id FROM link_clicks ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);

    $totalViews  = count($views);
    $totalClicks = count($clicks);

    if ($totalViews === 0 && $totalClicks === 0) {
      return ["success" => true, "message" => "No hay registros para redistribuir."];
    }

    // Distribución planificada por mes (1 a 10 de 2026)
    $viewsDistribution = [
      1 => 55, 2 => 48, 3 => 62, 4 => 58, 5 => 70,
      6 => 65, 7 => 72, 8 => 68, 9 => 60, 10 => 33
    ];
    $clicksDistribution = [
      1 => 38, 2 => 34, 3 => 45, 4 => 41, 5 => 50,
      6 => 46, 7 => 51, 8 => 48, 9 => 42, 10 => 25
    ];

    // Ajuste proporcional dinámico si el total difiere
    if (array_sum($viewsDistribution) !== $totalViews && $totalViews > 0) {
      $ratio = $totalViews / array_sum($viewsDistribution);
      $accum = 0;
      foreach ($viewsDistribution as $m => $val) {
        if ($m === 10) {
          $viewsDistribution[$m] = $totalViews - $accum;
        } else {
          $newVal = (int)round($val * $ratio);
          $viewsDistribution[$m] = $newVal;
          $accum += $newVal;
        }
      }
    }

    if (array_sum($clicksDistribution) !== $totalClicks && $totalClicks > 0) {
      $ratio = $totalClicks / array_sum($clicksDistribution);
      $accum = 0;
      foreach ($clicksDistribution as $m => $val) {
        if ($m === 10) {
          $clicksDistribution[$m] = $totalClicks - $accum;
        } else {
          $newVal = (int)round($val * $ratio);
          $clicksDistribution[$m] = $newVal;
          $accum += $newVal;
        }
      }
    }

    $db->beginTransaction();

    try {
      // 1. Asignar fechas a profile_views
      $viewIndex = 0;
      $stmtUpdateView = $db->prepare("UPDATE profile_views SET created_at = :created_at WHERE id = :id");

      foreach ($viewsDistribution as $month => $count) {
        $daysInMonth = ($month === 2) ? 28 : (in_array($month, [4, 6, 9, 11]) ? 30 : 31);
        $maxDay = ($month === 10) ? 4 : $daysInMonth;

        for ($i = 0; $i < $count; $i++) {
          if ($viewIndex >= $totalViews) break;
          $id = $views[$viewIndex++];

          $day = 1 + (int)floor(($i / max(1, $count)) * ($maxDay - 1));
          if ($day > $maxDay) $day = $maxDay;

          $hour   = 8 + ($i % 15);
          $minute = ($i * 7) % 60;
          $second = ($i * 13) % 60;

          if ($month === 10 && $day === 4 && $hour > 17) {
            $hour = 8 + ($i % 9);
          }

          $dateStr = sprintf('2026-%02d-%02d %02d:%02d:%02d', $month, $day, $hour, $minute, $second);
          $stmtUpdateView->execute([':created_at' => $dateStr, ':id' => $id]);
        }
      }

      // 2. Asignar fechas a link_clicks
      $clickIndex = 0;
      $stmtUpdateClick = $db->prepare("UPDATE link_clicks SET created_at = :created_at WHERE id = :id");

      foreach ($clicksDistribution as $month => $count) {
        $daysInMonth = ($month === 2) ? 28 : (in_array($month, [4, 6, 9, 11]) ? 30 : 31);
        $maxDay = ($month === 10) ? 4 : $daysInMonth;

        for ($i = 0; $i < $count; $i++) {
          if ($clickIndex >= $totalClicks) break;
          $id = $clicks[$clickIndex++];

          $day = 1 + (int)floor(($i / max(1, $count)) * ($maxDay - 1));
          if ($day > $maxDay) $day = $maxDay;

          $hour   = 9 + ($i % 14);
          $minute = ($i * 11) % 60;
          $second = ($i * 17) % 60;

          if ($month === 10 && $day === 4 && $hour > 17) {
            $hour = 9 + ($i % 8);
          }

          $dateStr = sprintf('2026-%02d-%02d %02d:%02d:%02d', $month, $day, $hour, $minute, $second);
          $stmtUpdateClick->execute([':created_at' => $dateStr, ':id' => $id]);
        }
      }

      $db->commit();

      return [
        "success"             => true,
        "views_updated"       => $viewIndex,
        "clicks_updated"      => $clickIndex,
        "views_distribution"  => $viewsDistribution,
        "clicks_distribution" => $clicksDistribution
      ];
    } catch (\Throwable $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      throw $e;
    }
  }
}
