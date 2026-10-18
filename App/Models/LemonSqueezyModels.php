<?php

namespace App\Models;

use Base\Builder\Builder;
use Exception;

/**
 * Clase LemonSqueezyModels
 * 
 * Suministro y gestión de datos de transacciones, órdenes y suscripciones
 * provenientes de la pasarela Lemon Squeezy.
 * Utiliza el modelo estandarizado SubscriptionModels (user_subscriptions_cache en SQLite)
 * para verificar vencimientos y estado Premium sin impactar el límite de conexiones a MySQL.
 */
class LemonSqueezyModels extends Builder
{
  /** @var string Tabla principal del modelo */
  protected $table = "lemon_squeezy_orders";

  /**
   * Guarda o actualiza el registro de una orden enviada por Lemon Squeezy.
   * 
   * @param array $data Datos estructurados de la orden.
   * @return bool True si se guardó con éxito, False de lo contrario.
   */
  public static function saveOrder(array $data): bool
  {
    $lemonOrderId = $data["lemon_order_id"] ?? "";

    if (empty($lemonOrderId)) {
      return false;
    }

    $model = new self("lemon_squeezy_orders");
    try {
      if (!$model->table_exist("lemon_squeezy_orders")) {
        \App\DatabaseComponent\LemonSqueezyTable::setupTables();
      }
    } catch (\Throwable $e) {}

    $existing = $model->where("lemon_order_id", $lemonOrderId)->get_one();

    $payload = [
      "lemon_order_id"    => $lemonOrderId,
      "store_id"          => $data["store_id"] ?? "",
      "customer_id"       => $data["customer_id"] ?? "",
      "user_id"           => !empty($data["user_id"]) ? (string)$data["user_id"] : null,
      "customer_name"     => $data["customer_name"] ?? "",
      "customer_email"    => $data["customer_email"] ?? "",
      "order_number"      => $data["order_number"] ?? "",
      "status"            => $data["status"] ?? "pending",
      "currency"          => $data["currency"] ?? "USD",
      "total_cents"       => (int)($data["total_cents"] ?? 0),
      "variant_id"        => $data["variant_id"] ?? "",
      "product_name"      => $data["product_name"] ?? "",
      "raw_payload"       => is_array($data["raw_payload"] ?? null) ? json_encode($data["raw_payload"]) : ($data["raw_payload"] ?? null),
      "updated_at_record" => date("Y-m-d H:i:s")
    ];

    if (!empty($existing) && is_array($existing)) {
      $updateModel = new self("lemon_squeezy_orders");
      $updateModel->update("lemon_order_id", $lemonOrderId, $payload);
      return true;
    } else {
      $payload["created_at_record"] = date("Y-m-d H:i:s");
      $createModel = new self("lemon_squeezy_orders");
      $createModel->create($payload);
      return true;
    }
  }

  /**
   * Guarda o actualiza el registro de una suscripción enviada por Lemon Squeezy.
   * 
   * @param array $data Datos estructurados de la suscripción.
   * @return bool True si se guardó con éxito, False de lo contrario.
   */
  public static function saveSubscription(array $data): bool
  {
    $lemonSubId = $data["lemon_subscription_id"] ?? "";

    if (empty($lemonSubId)) {
      return false;
    }

    $model = new self("lemon_squeezy_subscriptions");
    try {
      if (!$model->table_exist("lemon_squeezy_subscriptions")) {
        \App\DatabaseComponent\LemonSqueezyTable::setupTables();
      }
    } catch (\Throwable $e) {}

    $existing = $model->where("lemon_subscription_id", $lemonSubId)->get_one();

    $formatDate = function ($dateVal) {
      if (empty($dateVal)) return null;
      $ts = strtotime($dateVal);
      return $ts !== false ? date("Y-m-d H:i:s", $ts) : null;
    };

    $payload = [
      "lemon_subscription_id" => $lemonSubId,
      "store_id"              => $data["store_id"] ?? "",
      "customer_id"           => $data["customer_id"] ?? "",
      "order_id"              => $data["order_id"] ?? "",
      "product_id"            => $data["product_id"] ?? "",
      "variant_id"            => $data["variant_id"] ?? "",
      "user_id"               => !empty($data["user_id"]) ? (string)$data["user_id"] : null,
      "user_email"            => $data["user_email"] ?? "",
      "status"                => $data["status"] ?? "active",
      "trial_ends_at"         => $formatDate($data["trial_ends_at"] ?? null),
      "renews_at"             => $formatDate($data["renews_at"] ?? null),
      "ends_at"               => $formatDate($data["ends_at"] ?? null),
      "raw_payload"           => is_array($data["raw_payload"] ?? null) ? json_encode($data["raw_payload"]) : ($data["raw_payload"] ?? null),
      "updated_at_sub"        => date("Y-m-d H:i:s")
    ];

    if (!empty($existing) && is_array($existing)) {
      $updateModel = new self("lemon_squeezy_subscriptions");
      $updateModel->update("lemon_subscription_id", $lemonSubId, $payload);
      return true;
    } else {
      $payload["created_at_sub"] = date("Y-m-d H:i:s");
      $createModel = new self("lemon_squeezy_subscriptions");
      $createModel->create($payload);
      return true;
    }
  }

  /**
   * Obtiene una orden por su identificador de Lemon Squeezy.
   * 
   * @param string $lemonOrderId ID de la orden en Lemon Squeezy.
   * @return array|null Registro de la orden o null.
   */
  public static function getOrderByLemonId(string $lemonOrderId): ?array
  {
    $result = (new self("lemon_squeezy_orders"))
      ->where("lemon_order_id", $lemonOrderId)
      ->get_one();

    if (!empty($result) && is_array($result)) {
      return isset($result[0]) && is_array($result[0]) ? $result[0] : $result;
    }
    return null;
  }

  /**
   * Obtiene las órdenes asociadas a un usuario específico.
   * 
   * @param string|int $userId ID o username del usuario.
   * @return array Lista de órdenes.
   */
  public static function getOrdersByUser(string|int $userId): array
  {
    $result = (new self("lemon_squeezy_orders"))
      ->where("user_id", (string)$userId)
      ->order("created_at_record", "DESC")
      ->get_all();

    return (is_array($result) && $result !== false) ? $result : [];
  }

  /**
   * Obtiene la suscripción activa de un usuario desde la BD principal MySQL.
   * 
   * @param string|int $userId ID o username del usuario.
   * @return array|null Registro de la suscripción o null.
   */
  public static function getSubscriptionByUser(string|int $userId): ?array
  {
    $subModel = new self("lemon_squeezy_subscriptions");
    $result = $subModel->where("user_id", (string)$userId)
      ->where("status", "active")
      ->order("created_at_sub", "DESC")
      ->get_one();

    if (!empty($result) && is_array($result)) {
      return isset($result[0]) && is_array($result[0]) ? $result[0] : $result;
    }

    // Fallback defensivo: buscar por username de la sesión si la búsqueda por ID numérico no encuentra resultados
    if (class_exists('\Base\Module\Session')) {
      $sessionUser = \Base\Module\Session::session_data("username");
      if (!empty($sessionUser) && (string)$sessionUser !== (string)$userId) {
        $fallbackModel = new self("lemon_squeezy_subscriptions");
        $resFallback = $fallbackModel->where("user_id", (string)$sessionUser)
          ->where("status", "active")
          ->order("created_at_sub", "DESC")
          ->get_one();

        if (!empty($resFallback) && is_array($resFallback)) {
          return isset($resFallback[0]) && is_array($resFallback[0]) ? $resFallback[0] : $resFallback;
        }
      }
    }

    return null;
  }

  /**
   * Verifica si un usuario tiene una suscripción activa o en periodo de prueba en la BD MySQL.
   * 
   * @param string|int $userId ID o username del usuario.
   * @return bool True si está activo o en prueba, False de lo contrario.
   */
  public static function isUserSubscribed(string|int $userId): bool
  {
    $sub = self::getSubscriptionByUser($userId);
    return !empty($sub) && in_array($sub["status"], ["active", "on_trial"], true);
  }

  /**
   * Obtiene la suscripción activa de un usuario junto con la orden de pago asociada.
   * 
   * @param string|int $userId ID o username del usuario.
   * @return array|null Datos consolidados de suscripción u orden o null.
   */
  public static function getSubscriptionWithOrder(string|int $userId): ?array
  {
    $subscription = self::getSubscriptionByUser($userId);
    if (empty($subscription)) {
      return null;
    }

    $order = null;
    if (!empty($subscription["order_id"])) {
      $order = self::getOrderByLemonId($subscription["order_id"]);
    }

    return [
      "subscription" => $subscription,
      "order"        => $order
    ];
  }

  /**
   * Actualiza el estado y fecha de vencimiento Premium en la BD local SQLite (Database/clikhub.sqlite)
   * y en la sesión activa, evitando el consumo de inodos y liberando conexiones MySQL.
   * 
   * @param string|int $userId ID del usuario.
   * @param array $subData Datos de la suscripción (status, renews_at, ends_at).
   * @param string|null $username Username opcional para búsquedas alternativas.
   * @return bool True si se actualizó el registro en SQLite, False de lo contrario.
   */
  public static function updateUserPremiumCache(string|int $userId, array $subData, ?string $username = null): bool
  {
    $userIdStr = (string)$userId;
    if (empty($userIdStr)) {
      return false;
    }

    $status    = $subData["status"] ?? "inactive";
    $isPremium = in_array($status, ["active", "on_trial"], true) ? 1 : 0;
    $renewsAt  = $subData["renews_at"] ?? null;
    $endsAt    = $subData["ends_at"] ?? null;

    $premiumPayload = [
      "is_premium" => (bool)$isPremium,
      "expires_at" => $renewsAt ?? $endsAt,
      "status"     => $status,
      "updated_at" => date("Y-m-d H:i:s")
    ];

    // 1. Actualizar Sesión si es el usuario actualmente activo
    if (session_status() === PHP_SESSION_ACTIVE || (class_exists('\Base\Module\Session') && \Base\Module\Session::session_active())) {
      $sessionUserId = \Base\Module\Session::session_data("user_id");
      $sessionUser   = \Base\Module\Session::session_data("username");
      if ((string)$sessionUserId === $userIdStr || (!empty($sessionUser) && $sessionUser === $username)) {
        $_SESSION["premium"] = $premiumPayload;
        if (isset($_SESSION["user"]) && is_array($_SESSION["user"])) {
          $_SESSION["user"]["premium"] = $premiumPayload;
        }
      }
    }

    // 2. Persistir en la BD local SQLite usando el Builder de SubscriptionModels
    try {
      $indexUser = null;
      if (class_exists('\App\Models\UserModels')) {
        $indexUser = \App\Models\UserModels::getIndexUserByUsername($username ?? $userIdStr) 
          ?? (\App\Models\UserModels::userExistsByIndex($userIdStr) ? $userIdStr : null);
      }

      $services = ($isPremium === 1) ? '1,2' : '1';
      $sql = "INSERT INTO user_subscriptions_cache (user_id, index_user, username, is_premium, services, status, renews_at, ends_at, updated_at)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
              ON CONFLICT(user_id) DO UPDATE SET
                index_user = COALESCE(EXCLUDED.index_user, user_subscriptions_cache.index_user),
                username = EXCLUDED.username,
                is_premium = EXCLUDED.is_premium,
                services = EXCLUDED.services,
                status = EXCLUDED.status,
                renews_at = EXCLUDED.renews_at,
                ends_at = EXCLUDED.ends_at,
                updated_at = CURRENT_TIMESTAMP";

      (new SubscriptionModels())->rawQuery($sql, [
        $userIdStr,
        $indexUser,
        (string)($username ?? ""),
        $isPremium,
        $services,
        $status,
        (string)($renewsAt ?? ""),
        (string)($endsAt ?? "")
      ]);
      return true;
    } catch (\Throwable $e) {
      error_log("SQLite Save Error in LemonSqueezyModels::updateUserPremiumCache: " . $e->getMessage());
    }

    return false;
  }

  /**
   * Consulta el estado de suscripción de forma ultra-rápida (0 consultas MySQL)
   * verificando primero en la Sesión o en la BD local SQLite mediante SubscriptionModels.
   * Si la fecha de expiración ya pasó o no existe en SQLite, realiza el fallback a MySQL y guarda en SQLite.
   * 
   * @param string|int $userId ID o username del usuario.
   * @return bool True si la suscripción está activa y vigente, False de lo contrario.
   */
  public static function isUserSubscribedFast(string|int|null $userId = null): bool
  {
    if (empty($userId)) {
      return false;
    }
    $now = time();
    $userIdStr = (string)$userId;

    // 1. Verificación en Sesión RAM (0 DB, 0 I/O)
    if (session_status() === PHP_SESSION_ACTIVE || (class_exists('\Base\Module\Session') && \Base\Module\Session::session_active())) {
      $sessionPrem = \Base\Module\Session::session_data("premium") ?? (\Base\Module\Session::user_session_show()["premium"] ?? null);
      if (is_array($sessionPrem) && isset($sessionPrem["is_premium"])) {
        $isPrem    = !empty($sessionPrem["is_premium"]);
        $expiresAt = $sessionPrem["expires_at"] ?? null;

        if ($isPrem && (empty($expiresAt) || strtotime($expiresAt) >= $now)) {
          return true;
        }
        if (!$isPrem || (!empty($expiresAt) && strtotime($expiresAt) < $now)) {
          return false;
        }
      }
    }

    // 2. Verificación en la BD local SQLite mediante SubscriptionModels — 0 conexiones MySQL
    try {
      $row = SubscriptionModels::findSubscriptionRow($userIdStr);
      if ($row) {
        $isPrem    = (int)($row["is_premium"] ?? 0) === 1;
        $expiresAt = !empty($row["renews_at"]) ? $row["renews_at"] : ($row["ends_at"] ?? null);

        if ($isPrem && (empty($expiresAt) || strtotime($expiresAt) >= $now)) {
          return true;
        }
        if (!$isPrem || (!empty($expiresAt) && strtotime($expiresAt) < $now)) {
          return false;
        }
      }
    } catch (\Throwable $e) {
      error_log("SubscriptionModels Query Error in isUserSubscribedFast: " . $e->getMessage());
    }

    // 3. Fallback a MySQL (solo si SQLite aún no conoce a este usuario)
    $sub = self::getSubscriptionByUser($userId);
    $isSubbed = !empty($sub) && in_array($sub["status"], ["active", "on_trial"], true);
    $username = class_exists('\Base\Module\Session') ? \Base\Module\Session::session_data("username") : null;

    if (!empty($sub)) {
      self::updateUserPremiumCache($userId, $sub, $username);
    } else {
      self::updateUserPremiumCache($userId, ["status" => "inactive"], $username);
    }

    return $isSubbed;
  }
}
