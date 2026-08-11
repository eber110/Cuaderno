<?php

namespace App\Models;

use Base\Builder\Builder;

/**
 * Clase LemonSqueezyModels
 * 
 * Suministro y gestión de datos de transacciones, órdenes y suscripciones
 * provenientes de la pasarela Lemon Squeezy.
 * Utiliza estrictamente get_one() y get_all() de Base\Builder\Builder.
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
   * Obtiene la suscripción activa de un usuario.
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
   * Verifica si un usuario tiene una suscripción activa o en periodo de prueba.
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
}
