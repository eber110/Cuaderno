<?php

namespace App\Providers;

/**
 * Clase LemonSqueezyProvider
 * 
 * Gestiona las solicitudes a la API REST v1 de Lemon Squeezy (Sandbox y Producción)
 * y la verificación de firmas de Webhooks.
 */
class LemonSqueezyProvider
{
  /** @var string URL base de la API v1 de Lemon Squeezy */
  private string $baseUrl = "https://api.lemonsqueezy.com/v1/";

  /** @var string API Key de Lemon Squeezy */
  private string $apiKey;

  /** @var string ID de la tienda */
  private string $storeId;

  /** @var string Secreto de verificación de Webhooks */
  private string $webhookSecret;

  /** @var string Modo de la pasarela (test/live) */
  private string $mode;

  /**
   * Constructor de LemonSqueezyProvider
   * 
   * @param string|null $apiKey Clave de API. Si es null, toma la constante global LEMON_SQUEEZY_API_KEY.
   * @param string|null $storeId ID de la tienda. Si es null, toma LEMON_SQUEEZY_STORE_ID.
   * @param string|null $webhookSecret Secreto de webhook. Si es null, toma LEMON_SQUEEZY_WEBHOOK_SECRET.
   */
  public function __construct(?string $apiKey = null, ?string $storeId = null, ?string $webhookSecret = null)
  {
    $this->apiKey        = $apiKey ?? (defined("LEMON_SQUEEZY_API_KEY") ? LEMON_SQUEEZY_API_KEY : "");
    $this->storeId       = $storeId ?? (defined("LEMON_SQUEEZY_STORE_ID") ? LEMON_SQUEEZY_STORE_ID : "");
    $this->webhookSecret = $webhookSecret ?? (defined("LEMON_SQUEEZY_WEBHOOK_SECRET") ? LEMON_SQUEEZY_WEBHOOK_SECRET : "");
    $this->mode          = defined("LEMON_SQUEEZY_MODE") ? LEMON_SQUEEZY_MODE : "test";
  }

  /**
   * Realiza una petición cURL a la API v1 de Lemon Squeezy.
   * 
   * @param string $method Método HTTP (GET, POST, PATCH, DELETE).
   * @param string $endpoint Endpoint relativo (ej: 'users/me', 'checkouts').
   * @param array $data Datos a enviar en formato array asociativo (JSON).
   * @return array Respuesta decodificada de la API con 'success', 'status', 'data' o 'error'.
   */
  public function makeRequest(string $method, string $endpoint, array $data = []): array
  {
    if (empty($this->apiKey)) {
      return [
        "success" => false,
        "status"  => 401,
        "error"   => "Lemon Squeezy API Key no está configurada."
      ];
    }

    $url = rtrim($this->baseUrl, "/") . "/" . ltrim($endpoint, "/");
    $ch  = curl_init();

    $headers = [
      "Accept: application/vnd.api+json",
      "Content-Type: application/vnd.api+json",
      "Authorization: Bearer " . $this->apiKey
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $upperMethod = strtoupper($method);
    if ($upperMethod === "POST") {
      curl_setopt($ch, CURLOPT_POST, true);
      if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      }
    } elseif ($upperMethod === "PATCH") {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
      if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      }
    } elseif ($upperMethod === "DELETE") {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    } elseif ($upperMethod === "GET" && !empty($data)) {
      $url .= "?" . http_build_query($data);
      curl_setopt($ch, CURLOPT_URL, $url);
    }

    // Bypass SSL en entorno DEV local si fuera necesario
    if (defined("ENVIRONMENT") && ENVIRONMENT === "DEV") {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
      return [
        "success" => false,
        "status"  => 0,
        "error"   => "Error cURL: " . $curlError
      ];
    }

    $decoded = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300) {
      return [
        "success" => true,
        "status"  => $httpCode,
        "data"    => $decoded["data"] ?? $decoded
      ];
    }

    $errorMessage = $decoded["errors"][0]["detail"] ?? $decoded["message"] ?? "Error desconocido en la API de Lemon Squeezy";

    return [
      "success" => false,
      "status"  => $httpCode,
      "error"   => $errorMessage,
      "details" => $decoded
    ];
  }

  /**
   * Verifica la autenticación con la API realizando un ping al endpoint /users/me.
   * 
   * @return array Resultado de la verificación.
   */
  public function ping(): array
  {
    return $this->makeRequest("GET", "users/me");
  }

  /**
   * Obtiene la información de una tienda o lista de tiendas.
   * 
   * @param string|null $storeId ID de la tienda específica. Si es null o vacío, lista todas las tiendas asociadas.
   * @return array Información de la tienda.
   */
  public function getStore(?string $storeId = null): array
  {
    $targetId = $storeId ?? $this->storeId;
    $endpoint = !empty($targetId) ? "stores/" . $targetId : "stores";
    return $this->makeRequest("GET", $endpoint);
  }

  /**
   * Obtiene la lista de productos de la tienda.
   * 
   * @param string|null $storeId ID de la tienda.
   * @return array Lista de productos.
   */
  public function getProducts(?string $storeId = null): array
  {
    $targetId = $storeId ?? $this->storeId;
    $params   = !empty($targetId) ? ["filter" => ["store_id" => $targetId]] : [];
    return $this->makeRequest("GET", "products", $params);
  }

  /**
   * Obtiene la lista de variantes de un producto o de la tienda.
   * 
   * @param string|null $productId ID del producto para filtrar variantes.
   * @return array Lista de variantes.
   */
  public function getVariants(?string $productId = null): array
  {
    $params = !empty($productId) ? ["filter" => ["product_id" => $productId]] : [];
    return $this->makeRequest("GET", "variants", $params);
  }

  /**
   * Crea una sesión de Checkout (URL de compra) en Lemon Squeezy.
   * 
   * @param string $variantId ID de la variante del producto a vender.
   * @param array $customData Datos personalizados que se adjuntarán a la orden (ej: user_id, coupon, source).
   * @param array $options Opciones de checkout (redirect_url, email, name, discount_code, custom_price_cents, expires_at).
   * @param string|null $storeId ID de la tienda opcional.
   * @return array Respuesta con la URL del checkout ('url') y metadatos.
   */
  public function createCheckout(string $variantId, array $customData = [], array $options = [], ?string $storeId = null): array
  {
    $targetStoreId = $storeId ?? $this->storeId;

    if (empty($targetStoreId)) {
      return [
        "success" => false,
        "status"  => 400,
        "error"   => "Se requiere un Store ID de Lemon Squeezy para generar el checkout."
      ];
    }

    $attributes = [
      "checkout_data" => []
    ];

    if (!empty($customData)) {
      $attributes["checkout_data"]["custom"] = $customData;
    }

    if (!empty($options["email"])) {
      $attributes["checkout_data"]["email"] = $options["email"];
    }

    if (!empty($options["name"])) {
      $attributes["checkout_data"]["name"] = $options["name"];
    }

    if (!empty($options["discount_code"])) {
      $attributes["checkout_data"]["discount_code"] = $options["discount_code"];
    }

    if (!empty($options["custom_price_cents"])) {
      $attributes["checkout_data"]["custom_price"] = (int)$options["custom_price_cents"];
    }

    $productOptions = [];
    if (!empty($options["redirect_url"])) {
      $productOptions["redirect_url"] = $options["redirect_url"];
    }

    if (!empty($productOptions)) {
      $attributes["product_options"] = $productOptions;
    }

    if (isset($options["expires_at"])) {
      $attributes["expires_at"] = $options["expires_at"];
    }

    // Definición de estructura JSON:API para Lemon Squeezy
    $payload = [
      "data" => [
        "type" => "checkouts",
        "attributes" => $attributes,
        "relationships" => [
          "store" => [
            "data" => [
              "type" => "stores",
              "id"   => (string)$targetStoreId
            ]
          ],
          "variant" => [
            "data" => [
              "type" => "variants",
              "id"   => (string)$variantId
            ]
          ]
        ]
      ]
    ];

    $result = $this->makeRequest("POST", "checkouts", $payload);

    if ($result["success"] && isset($result["data"]["attributes"]["url"])) {
      return [
        "success"      => true,
        "checkout_url" => $result["data"]["attributes"]["url"],
        "checkout_id"  => $result["data"]["id"] ?? null,
        "data"         => $result["data"]
      ];
    }

    return $result;
  }

  /**
   * Valida la firma HMAC SHA-256 enviada por Lemon Squeezy en los Webhooks.
   * 
   * @param string $payload Contenido crudo de la solicitud (php://input).
   * @param string $signatureHeader Valor de la cabecera HTTP 'X-Signature'.
   * @return bool True si la firma es válida, false de lo contrario.
   */
  public function verifyWebhookSignature(string $payload, string $signatureHeader): bool
  {
    if (empty($this->webhookSecret) || empty($signatureHeader)) {
      return false;
    }

    $computedSignature = hash_hmac("sha256", $payload, $this->webhookSecret);
    return hash_equals($computedSignature, $signatureHeader);
  }

  /**
   * Obtiene los detalles de una orden por su ID.
   * 
   * @param string $orderId ID de la orden en Lemon Squeezy.
   * @return array Datos de la orden.
   */
  public function getOrder(string $orderId): array
  {
    return $this->makeRequest("GET", "orders/" . $orderId);
  }

  /**
   * Obtiene los detalles de una suscripción por su ID.
   * 
   * @param string $subscriptionId ID de la suscripción en Lemon Squeezy.
   * @return array Datos de la suscripción.
   */
  public function getSubscription(string $subscriptionId): array
  {
    return $this->makeRequest("GET", "subscriptions/" . $subscriptionId);
  }
}
