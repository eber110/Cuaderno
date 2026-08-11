<?php

namespace App\Controllers;

use App\Models\LemonSqueezyModels;
use App\Providers\LemonSqueezyProvider;
use Base\Control\Control;
use Base\Module\ResponseModule;
use Base\Module\SecurityModule;
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
 * 
 * Utiliza SecurityModule para el acceso seguro a variables de entrada HTTP sin superglobales directas.
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
    $variantId = SecurityModule::get("variant_id", "2004539", true);

    $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (str_contains($clientIp, ',')) {
      $clientIp = trim(explode(',', $clientIp)[0]);
    }

    $countryCode = "CL";
    try {
      if (class_exists('Base\Module\GeoIpModule')) {
        $detected = \Base\Module\GeoIpModule::getCountryCode($clientIp);
        if (!empty($detected)) {
          $countryCode = strtoupper($detected);
        }
      }
    } catch (\Throwable $e) {
      $countryCode = "CL";
    }

    return $this->view("Checkout.checkout", [
      "userEmail"   => $userEmail,
      "username"    => $username,
      "variantId"   => $variantId,
      "countryCode" => $countryCode,
      "locale"      => "es"
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
   * 
   * @param array $requestData Datos enviados por el enrutador
   */
  public function checkout(array $requestData = [])
  {
    $rawInput = file_get_contents("php://input");
    $decoded  = json_decode($rawInput, true);

    $getVars  = SecurityModule::sanitizeArray($_GET ?? []);
    $postVars = SecurityModule::sanitizeArray($_POST ?? []);
    $reqVars  = SecurityModule::sanitizeArray(is_array($requestData) ? $requestData : []);

    $input = is_array($decoded) ? SecurityModule::sanitizeArray($decoded) : array_merge($getVars, $postVars, $reqVars);

    $variantId   = trim((string)($input["variant_id"] ?? "2004539"));
    $redirectUrl = trim((string)($input["redirect_url"] ?? ""));
    $userEmail   = trim((string)($input["email"] ?? Session::session_data("email") ?? ""));
    $userName    = trim((string)($input["name"] ?? Session::session_data("username") ?? ""));
    $userId      = trim((string)($input["user_id"] ?? Session::session_data("username") ?? ""));

    if (empty($variantId)) {
      $reqMethod = $_SERVER["REQUEST_METHOD"] ?? "GET";
      if ($reqMethod === "GET") {
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
      "email" => $userEmail,
      "name"  => $userName
    ];

    if (!empty($redirectUrl) && str_starts_with(strtolower($redirectUrl), "https://")) {
      $options["redirect_url"] = $redirectUrl;
    }

    if (!empty($input["discount_code"])) {
      $options["discount_code"] = trim((string)$input["discount_code"]);
    }

    if (!empty($input["custom_price_cents"])) {
      $options["custom_price_cents"] = (int)$input["custom_price_cents"];
    }

    $buyBase = defined("LEMON_SQUEEZY_BUY_URL") ? LEMON_SQUEEZY_BUY_URL : "https://clikhub.lemonsqueezy.com/checkout/buy/e2ba4ce6-2307-4d5e-b965-47b519aca9de";
    $queryParams = [];
    if (!empty($userEmail)) {
      $queryParams["checkout[email]"] = $userEmail;
    }
    if (!empty($userId)) {
      $queryParams["checkout[custom][user_id]"] = $userId;
    }
    if (!empty($input["discount_code"])) {
      $queryParams["checkout[discount_code]"] = trim((string)$input["discount_code"]);
    }
    if (!empty($redirectUrl) && str_starts_with(strtolower($redirectUrl), "https://")) {
      $queryParams["checkout[redirect_url]"] = $redirectUrl;
    }

    // Idioma predeterminado (es = Español)
    $locale = !empty($input["locale"]) ? trim((string)$input["locale"]) : "es";
    $queryParams["checkout[locale]"] = $locale;

    // Ubicación / País por defecto (detectado automáticamente o 'CL' por defecto)
    $country = !empty($input["country"]) ? strtoupper(trim((string)$input["country"])) : null;
    if (empty($country)) {
      $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
      if (str_contains($clientIp, ',')) {
        $clientIp = trim(explode(',', $clientIp)[0]);
      }
      try {
        if (class_exists('Base\Module\GeoIpModule')) {
          $detected = \Base\Module\GeoIpModule::getCountryCode($clientIp);
          if (!empty($detected)) {
            $country = strtoupper($detected);
          }
        }
      } catch (\Throwable $e) {}
    }
    if (empty($country)) {
      $country = "CL";
    }
    $queryParams["checkout[country]"] = $country;
    $queryParams["checkout[billing_address][country]"] = $country;

    $checkoutUrl = $buyBase . (!empty($queryParams) ? (str_contains($buyBase, "?") ? "&" : "?") . http_build_query($queryParams) : "");




    $httpAccept = $_SERVER["HTTP_ACCEPT"] ?? "";
    $wantsJson  = str_contains($httpAccept, "application/json");
    if ($wantsJson || !empty($input["json"])) {
      header("Content-Type: application/json");
      echo json_encode([
        "success"      => true,
        "checkout_url" => $checkoutUrl
      ]);
      exit;
    }

    return ResponseModule::redirect($checkoutUrl);
  }


  /**
   * Endpoint receptor de Webhooks de Lemon Squeezy.
   * POST /lemon-squeezy/webhook
   */
  public function webhook()
  {
    $payload   = file_get_contents("php://input");
    $rawSig    = $_SERVER["HTTP_X_SIGNATURE"] ?? $_SERVER["HTTP_X_LEMON_SQUEEZY_SIGNATURE"] ?? "";
    $signature = SecurityModule::sanitize($rawSig);

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

    $eventName  = SecurityModule::sanitize($data["meta"]["event_name"] ?? "");
    $customData = SecurityModule::sanitizeArray($data["meta"]["custom_data"] ?? []);
    $attributes = $data["data"]["attributes"] ?? [];
    $resourceId = SecurityModule::sanitize((string)($data["data"]["id"] ?? ""));

    $processed = false;

    switch ($eventName) {
      case "order_created":
      case "order_refunded":
        $orderData = [
          "lemon_order_id" => $resourceId,
          "store_id"       => SecurityModule::sanitize((string)($attributes["store_id"] ?? "")),
          "customer_id"    => SecurityModule::sanitize((string)($attributes["customer_id"] ?? "")),
          "user_id"        => SecurityModule::sanitize((string)($customData["user_id"] ?? "")),
          "customer_name"  => SecurityModule::sanitize((string)($attributes["user_name"] ?? "")),
          "customer_email" => SecurityModule::sanitize((string)($attributes["user_email"] ?? "")),
          "order_number"   => SecurityModule::sanitize((string)($attributes["order_number"] ?? "")),
          "status"         => SecurityModule::sanitize((string)($attributes["status"] ?? "paid")),
          "currency"       => SecurityModule::sanitize((string)($attributes["currency"] ?? "USD")),
          "total_cents"    => (int)($attributes["total"] ?? 0),
          "variant_id"     => SecurityModule::sanitize((string)($attributes["first_order_item"]["variant_id"] ?? "")),
          "product_name"   => SecurityModule::sanitize((string)($attributes["first_order_item"]["product_name"] ?? "")),
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
          "lemon_subscription_id" => $resourceId,
          "store_id"              => SecurityModule::sanitize((string)($attributes["store_id"] ?? "")),
          "customer_id"           => SecurityModule::sanitize((string)($attributes["customer_id"] ?? "")),
          "order_id"              => SecurityModule::sanitize((string)($attributes["order_id"] ?? "")),
          "product_id"            => SecurityModule::sanitize((string)($attributes["product_id"] ?? "")),
          "variant_id"            => SecurityModule::sanitize((string)($attributes["variant_id"] ?? "")),
          "user_id"               => SecurityModule::sanitize((string)($customData["user_id"] ?? "")),
          "user_email"            => SecurityModule::sanitize((string)($attributes["user_email"] ?? "")),
          "status"                => SecurityModule::sanitize((string)($attributes["status"] ?? "active")),
          "trial_ends_at"         => self::parseSqlDate($attributes["trial_ends_at"] ?? null),
          "renews_at"             => self::parseSqlDate($attributes["renews_at"] ?? null),
          "ends_at"               => self::parseSqlDate($attributes["ends_at"] ?? null),
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

    if (Session::session_active()) {
      $premium = LemonSqueezyModels::isUserSubscribed(Session::session_data("user_id"));
      $_SESSION["user"]["premium"] = $premium;
    }else{
      $_SESSION["user"]["premium"] = false;
    }

    $httpAccept = $_SERVER["HTTP_ACCEPT"] ?? "";
    $wantsJson  = str_contains($httpAccept, "application/json");
    $jsonFlag   = SecurityModule::get("json");

    if ($wantsJson || !empty($jsonFlag)) {
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

    $httpAccept = $_SERVER["HTTP_ACCEPT"] ?? "";
    $wantsJson  = str_contains($httpAccept, "application/json");
    $jsonFlag   = SecurityModule::get("json");

    if ($wantsJson || !empty($jsonFlag)) {
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

  /**
   * Convierte una fecha ISO 8601 a formato compatible con MySQL/PostgreSQL (YYYY-MM-DD HH:MM:SS) o NULL.
   * 
   * @param string|null $dateStr Fecha en formato ISO o nula.
   * @return string|null Fecha formateada o null.
   */
  public static function parseSqlDate(?string $dateStr): ?string
  {
    if (empty($dateStr)) {
      return null;
    }
    $timestamp = strtotime($dateStr);
    return $timestamp !== false ? date("Y-m-d H:i:s", $timestamp) : null;
  }
}

