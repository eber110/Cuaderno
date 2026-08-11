<?php

namespace App\Controllers;

use App\Models\LemonSqueezyModels;
use App\Providers\LemonSqueezyProvider;
use Base\Control\Control;
use Base\Module\HttpPostModule;
use Base\Module\ResponseModule;
use Base\Module\SeoModule;
use Base\Module\Session;

/**
 * Clase LemonSqueezyControllers
 * 
 * Controlador encargado de la orquestación del flujo de pagos con Lemon Squeezy:
 * - Páginas de venta, éxito y advertencia/cancelación.
 * - Generación de links de checkout.
 * - Recepción y verificación de Webhooks.
 * - Test de conexión Sandbox.
 */
class LemonSqueezyControllers extends Control
{
  /** @var LemonSqueezyProvider Instancia del proveedor de API */
  private LemonSqueezyProvider $provider;

  public function __construct()
  {
    $this->provider = new LemonSqueezyProvider();
  }

  /**
   * Renderiza la página principal de venta/suscripción.
   * GET /suscripcion
   */
  public function salesPage()
  {
    SeoModule::setTitle("Suscripción Premium Pro - $4 USD/mes");
    SeoModule::setMetaDescription("Suscríbete para obtener acceso ilimitado a todas las herramientas Pro por $4 USD mensuales (~ $3.500 CLP).");

    $userEmail = Session::session_data("email") ?? "";
    $username  = Session::session_data("username") ?? "";
    $variantId = trim((string)($_GET["variant_id"] ?? "2004539"));

    return $this->view("Checkout.checkout", [
      "userEmail" => $userEmail,
      "username"  => $username,
      "variantId" => $variantId
    ]);
  }

  /**
   * Endpoint de prueba para verificar la validez de las credenciales de la API.
   * GET /lemon-squeezy/test
   */
  public function testApi()
  {
    $pingResult  = $this->provider->ping();
    $storeResult = $this->provider->getStore();

    header("Content-Type: application/json");
    echo json_encode([
      "success"      => $pingResult["success"],
      "mode"         => defined("LEMON_SQUEEZY_MODE") ? LEMON_SQUEEZY_MODE : "test",
      "user_ping"    => $pingResult,
      "store_info"   => $storeResult
    ], JSON_PRETTY_PRINT);
    exit;
  }

  /**
   * Genera un checkout en Lemon Squeezy y redirige o devuelve la URL en JSON.
   * POST /lemon-squeezy/checkout
   */
  public function checkout()
  {
    $rawInput = file_get_contents("php://input");
    $decoded  = json_decode($rawInput, true);
    $input    = is_array($decoded) ? $decoded : HttpPostModule::get_post();

    $variantId   = trim((string)($input["variant_id"] ?? $_GET["variant_id"] ?? "2004539"));

    $redirectUrl = trim((string)($input["redirect_url"] ?? $_GET["redirect_url"] ?? ""));
    $userEmail   = trim((string)($input["email"] ?? $_GET["email"] ?? Session::session_data("email") ?? ""));
    $userName    = trim((string)($input["name"] ?? $_GET["name"] ?? Session::session_data("username") ?? ""));
    $userId      = trim((string)($input["user_id"] ?? $_GET["user_id"] ?? Session::session_data("username") ?? ""));

    if (empty($variantId)) {
      // Si no se especifica variante, redirigir a la página de venta
      if ($_SERVER["REQUEST_METHOD"] === "GET") {
        return ResponseModule::redirect("/suscripcion");
      }

      header("Content-Type: application/json", true, 400);
      echo json_encode([
        "success" => false,
        "error"   => "Falta el parametro 'variant_id' requerido para crear el checkout."
      ]);
      exit;
    }

    if (empty($redirectUrl)) {
      $redirectUrl = DOMAIN . "lemon-squeezy/success";
    }

    $customData = [];
    if (!empty($userId)) {
      $customData["user_id"] = $userId;
    }
    if (!empty($input["custom_data"]) && is_array($input["custom_data"])) {
      $customData = array_merge($customData, $input["custom_data"]);
    }

    $options = [
      "redirect_url" => $redirectUrl,
      "email"        => $userEmail,
      "name"         => $userName
    ];

    if (!empty($input["discount_code"])) {
      $options["discount_code"] = trim($input["discount_code"]);
    }

    if (!empty($input["custom_price_cents"])) {
      $options["custom_price_cents"] = (int)$input["custom_price_cents"];
    }

    $result = $this->provider->createCheckout($variantId, $customData, $options);

    $wantsJson = isset($_SERVER["HTTP_ACCEPT"]) && str_contains($_SERVER["HTTP_ACCEPT"], "application/json");
    if ($wantsJson || !empty($input["json"])) {
      header("Content-Type: application/json");
      echo json_encode($result);
      exit;
    }

    if ($result["success"] && !empty($result["checkout_url"])) {
      return ResponseModule::redirect($result["checkout_url"]);
    }

    header("Content-Type: application/json", true, 400);
    echo json_encode([
      "success" => false,
      "error"   => $result["error"] ?? "No se pudo generar el enlace de checkout."
    ]);
    exit;
  }

  /**
   * Endpoint receptor de Webhooks de Lemon Squeezy.
   * POST /lemon-squeezy/webhook
   */
  public function webhook()
  {
    $payload   = file_get_contents("php://input");
    $signature = $_SERVER["HTTP_X_SIGNATURE"] ?? $_SERVER["HTTP_X_LEMON_SQUEEZY_SIGNATURE"] ?? "";

    if (empty($payload)) {
      header("Content-Type: application/json", true, 400);
      echo json_encode(["error" => "Payload de webhook vacío."]);
      exit;
    }

    // Verificación de firma digital HMAC SHA-256
    if (!$this->provider->verifyWebhookSignature($payload, $signature)) {
      header("Content-Type: application/json", true, 401);
      echo json_encode(["error" => "Firma HMAC de webhook inválida o no autorizada."]);
      exit;
    }

    $data = json_decode($payload, true);
    if (!is_array($data)) {
      header("Content-Type: application/json", true, 400);
      echo json_encode(["error" => "Formato JSON inválido."]);
      exit;
    }

    $eventName  = $data["meta"]["event_name"] ?? "";
    $customData = $data["meta"]["custom_data"] ?? [];
    $attributes = $data["data"]["attributes"] ?? [];
    $resourceId = $data["data"]["id"] ?? "";

    $processed = false;

    switch ($eventName) {
      case "order_created":
      case "order_refunded":
        $orderData = [
          "lemon_order_id" => (string)$resourceId,
          "store_id"       => (string)($attributes["store_id"] ?? ""),
          "customer_id"    => (string)($attributes["customer_id"] ?? ""),
          "user_id"        => $customData["user_id"] ?? null,
          "customer_name"  => $attributes["user_name"] ?? "",
          "customer_email" => $attributes["user_email"] ?? "",
          "order_number"   => (string)($attributes["order_number"] ?? ""),
          "status"         => $attributes["status"] ?? "paid",
          "currency"       => $attributes["currency"] ?? "USD",
          "total_cents"    => (int)($attributes["total"] ?? 0),
          "variant_id"     => (string)($attributes["first_order_item"]["variant_id"] ?? ""),
          "product_name"   => $attributes["first_order_item"]["product_name"] ?? "",
          "raw_payload"    => $data
        ];
        $processed = LemonSqueezyModels::saveOrder($orderData);
        break;

      case "subscription_created":
      case "subscription_updated":
      case "subscription_cancelled":
      case "subscription_expired":
      case "subscription_resumed":
        $subData = [
          "lemon_subscription_id" => (string)$resourceId,
          "store_id"              => (string)($attributes["store_id"] ?? ""),
          "customer_id"           => (string)($attributes["customer_id"] ?? ""),
          "order_id"              => (string)($attributes["order_id"] ?? ""),
          "product_id"            => (string)($attributes["product_id"] ?? ""),
          "variant_id"            => (string)($attributes["variant_id"] ?? ""),
          "user_id"               => $customData["user_id"] ?? null,
          "user_email"            => $attributes["user_email"] ?? "",
          "status"                => $attributes["status"] ?? "active",
          "trial_ends_at"         => $attributes["trial_ends_at"] ?? null,
          "renews_at"             => $attributes["renews_at"] ?? null,
          "ends_at"               => $attributes["ends_at"] ?? null,
          "raw_payload"           => $data
        ];
        $processed = LemonSqueezyModels::saveSubscription($subData);
        break;

      default:
        $processed = true;
        break;
    }

    header("Content-Type: application/json");
    echo json_encode([
      "success"   => true,
      "event"     => $eventName,
      "processed" => $processed
    ]);
    exit;
  }

  /**
   * Vista de retorno cuando una compra en Lemon Squeezy finaliza con éxito.
   * GET /lemon-squeezy/success
   */
  public function success()
  {
    SeoModule::setTitle("¡Pago Exitoso! - Suscripción Activada");
    SeoModule::setMetaDescription("Tu pago ha sido procesado con éxito. Bienvenido al plan Pro.");

    $wantsJson = isset($_SERVER["HTTP_ACCEPT"]) && str_contains($_SERVER["HTTP_ACCEPT"], "application/json");
    if ($wantsJson || !empty($_GET["json"])) {
      header("Content-Type: application/json");
      echo json_encode([
        "success" => true,
        "message" => "¡Pago procesado con éxito! Gracias por tu compra."
      ]);
      exit;
    }

    return $this->view("Checkout.success", [
      "message" => "Gracias por tu compra. Tu suscripción Pro de $4 USD se ha activado correctamente."
    ]);
  }

  /**
   * Vista de retorno si el usuario cancela o falla el proceso de compra.
   * GET /lemon-squeezy/cancel
   */
  public function cancel()
  {
    SeoModule::setTitle("Proceso Cancelado - Suscripción Pendiente");
    SeoModule::setMetaDescription("El proceso de compra fue cancelado o no completado.");

    $wantsJson = isset($_SERVER["HTTP_ACCEPT"]) && str_contains($_SERVER["HTTP_ACCEPT"], "application/json");
    if ($wantsJson || !empty($_GET["json"])) {
      header("Content-Type: application/json");
      echo json_encode([
        "success" => false,
        "message" => "El proceso de compra fue cancelado por el usuario."
      ]);
      exit;
    }

    return $this->view("Checkout.warning", [
      "message" => "El proceso de suscripción no se completó o fue cancelado. No se realizó ningún cobro."
    ]);
  }
}
