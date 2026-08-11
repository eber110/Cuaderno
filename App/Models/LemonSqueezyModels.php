<?php

namespace App\Models;

use Base\Builder\Builder;

/**
 * Clase LemonSqueezyModels
 * 
 * Suministro y gestión de datos de transacciones, órdenes y suscripciones
 * provenientes de la pasarela Lemon Squeezy.
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
    $existing = $model->where("lemon_order_id", $lemonOrderId)->first();

    $payload = [
      "lemon_order_id"    => $lemonOrderId,
      "store_id"          => $data["store_id"] ?? "",
      "customer_id"       => $data["customer_id"] ?? "",
      "user_id"           => $data["user_id"] ?? null,
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
    $existing = $model->where("lemon_subscription_id", $lemonSubId)->first();

    $payload = [
      "lemon_subscription_id" => $lemonSubId,
      "store_id"              => $data["store_id"] ?? "",
      "customer_id"           => $data["customer_id"] ?? "",
      "order_id"              => $data["order_id"] ?? "",
      "product_id"            => $data["product_id"] ?? "",
      "variant_id"            => $data["variant_id"] ?? "",
      "user_id"               => $data["user_id"] ?? null,
      "user_email"            => $data["user_email"] ?? "",
      "status"                => $data["status"] ?? "active",
      "trial_ends_at"         => $data["trial_ends_at"] ?? null,
      "renews_at"             => $data["renews_at"] ?? null,
      "ends_at"               => $data["ends_at"] ?? null,
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
      ->first();

    return (!empty($result) && is_array($result)) ? $result : null;
  }

  /**
   * Obtiene las órdenes asociadas a un usuario específico.
   * 
   * @param string $userId ID o username del usuario.
   * @return array Lista de órdenes.
   */
  public static function getOrdersByUser(string $userId): array
  {
    $result = (new self("lemon_squeezy_orders"))
      ->where("user_id", $userId)
      ->orderBy("created_at_record", "DESC")
      ->get();

    return is_array($result) ? $result : [];
  }

  /**
   * Obtiene la suscripción activa de un usuario.
   * 
   * @param string $userId ID o username del usuario.
   * @return array|null Registro de la suscripción o null.
   */
  public static function getSubscriptionByUser(string $userId): ?array
  {
    $subModel = new self("lemon_squeezy_subscriptions");
    $result = $subModel->where("user_id", $userId)
      ->where("status", "active")
      ->orderBy("created_at_sub", "DESC")
      ->first();

    return (!empty($result) && is_array($result)) ? $result : null;
  }
}
