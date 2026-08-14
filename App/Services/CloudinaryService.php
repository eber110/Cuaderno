<?php

namespace App\Services;

/**
 * Servicio CloudinaryService
 * 
 * Gestiona la autenticación, subida de videos cortos optimizados (~30s)
 * y eliminación de recursos multimedia en la API REST de Cloudinary.
 */
class CloudinaryService
{
  /**
   * Extrae las credenciales desde CLOUDINARY_URL si está presente.
   *
   * @return array
   */
  private static function getCredentialsFromUrl(): array
  {
    $url = defined("CLOUDINARY_URL") ? CLOUDINARY_URL : ($_ENV["CLOUDINARY_URL"] ?? getenv("CLOUDINARY_URL") ?: "");
    if (!empty($url) && str_starts_with($url, "cloudinary://")) {
      $parts = parse_url($url);
      return [
        "cloud_name" => $parts["host"] ?? "",
        "api_key"    => $parts["user"] ?? "",
        "api_secret" => $parts["pass"] ?? "",
      ];
    }
    return [];
  }

  /**
   * Obtiene el Cloud Name configurado.
   *
   * @return string
   */
  public static function getCloudName(): string
  {
    $fromUrl = self::getCredentialsFromUrl();
    if (!empty($fromUrl["cloud_name"])) {
      return $fromUrl["cloud_name"];
    }
    return defined("CLOUDINARY_CLOUD_NAME") ? CLOUDINARY_CLOUD_NAME : ($_ENV["CLOUDINARY_CLOUD_NAME"] ?? getenv("CLOUDINARY_CLOUD_NAME") ?: "");
  }

  /**
   * Obtiene el API Key configurado.
   *
   * @return string
   */
  public static function getApiKey(): string
  {
    $fromUrl = self::getCredentialsFromUrl();
    if (!empty($fromUrl["api_key"])) {
      return $fromUrl["api_key"];
    }
    return defined("CLOUDINARY_API_KEY") ? CLOUDINARY_API_KEY : ($_ENV["CLOUDINARY_API_KEY"] ?? getenv("CLOUDINARY_API_KEY") ?: "");
  }

  /**
   * Obtiene el API Secret configurado.
   *
   * @return string
   */
  public static function getApiSecret(): string
  {
    $fromUrl = self::getCredentialsFromUrl();
    if (!empty($fromUrl["api_secret"])) {
      return $fromUrl["api_secret"];
    }
    return defined("CLOUDINARY_API_SECRET") ? CLOUDINARY_API_SECRET : ($_ENV["CLOUDINARY_API_SECRET"] ?? getenv("CLOUDINARY_API_SECRET") ?: "");
  }

  /**
   * Verifica si las credenciales de Cloudinary están presentes.
   *
   * @return bool
   */
  public static function isConfigured(): bool
  {
    return !empty(self::getCloudName()) && !empty(self::getApiKey()) && !empty(self::getApiSecret());
  }

  public static ?string $lastErrorMessage = null;

  public static function getLastErrorMessage(): ?string
  {
    return self::$lastErrorMessage;
  }

  /**
   * Genera los parámetros y firma requeridos para subida directa en segundo plano desde el navegador a Cloudinary.
   *
   * @param string $folder Carpeta destino en Cloudinary.
   * @param string $transformation Cadena de transformaciones deseadas.
   * @return array Parámetros firmados y endpoint.
   */
  public static function createSignedUploadParams(string $folder = "cuaderno/backgrounds", string $transformation = "du_20,w_720,c_limit,q_auto,vc_auto,ac_none,f_auto"): array
  {
    if (!self::isConfigured()) {
      return ["success" => false, "error" => "Cloudinary no está configurado."];
    }

    $cloudName = self::getCloudName();
    $apiKey    = self::getApiKey();
    $apiSecret = self::getApiSecret();
    $timestamp = time();

    $paramsToSign = [
      "folder"    => $folder,
      "timestamp" => $timestamp,
    ];

    if (!empty($transformation)) {
      $paramsToSign["eager"] = $transformation;
    }

    ksort($paramsToSign);
    $signParts = [];
    foreach ($paramsToSign as $key => $val) {
      $signParts[] = "{$key}={$val}";
    }
    $stringToSign = implode("&", $signParts) . $apiSecret;
    $signature    = sha1($stringToSign);

    return [
      "success"        => true,
      "cloud_name"     => $cloudName,
      "api_key"        => $apiKey,
      "timestamp"      => $timestamp,
      "folder"         => $folder,
      "eager"          => $transformation,
      "signature"      => $signature,
      "upload_url"     => "https://api.cloudinary.com/v1_1/{$cloudName}/video/upload"
    ];
  }

  /**
   * Sube un archivo de video a Cloudinary con transformaciones de optimización y límite de duración.
   *
   * @param string $filePath Ruta absoluta del archivo local temporal a subir.
   * @param array $options Opciones adicionales (folder, max_duration, tags, etc.).
   * @return array|null Datos del video subido (secure_url, public_id, duration) o null en caso de error.
   */
  public static function uploadVideo(string $filePath, array $options = []): ?array
  {
    self::$lastErrorMessage = null;

    if (!self::isConfigured()) {
      self::$lastErrorMessage = "Cloudinary no está configurado en .env (faltan credenciales).";
      return null;
    }

    if (!file_exists($filePath)) {
      self::$lastErrorMessage = "El archivo temporal de video no fue encontrado en el servidor.";
      return null;
    }

    $cloudName = self::getCloudName();
    $apiKey    = self::getApiKey();
    $apiSecret = self::getApiSecret();

    $timestamp = time();
    $folder    = $options["folder"] ?? "cuaderno/backgrounds";

    // Parámetros a firmar (orden alfabético estricto requerido por Cloudinary)
    $paramsToSign = [
      "folder"    => $folder,
      "timestamp" => $timestamp,
    ];

    if (!empty($options["transformation"])) {
      $paramsToSign["transformation"] = $options["transformation"];
    }

    // Construir string para firma SHA-1: key1=val1&key2=val2... + apiSecret
    ksort($paramsToSign);
    $signParts = [];
    foreach ($paramsToSign as $key => $val) {
      $signParts[] = "{$key}={$val}";
    }
    $stringToSign = implode("&", $signParts) . $apiSecret;
    $signature    = sha1($stringToSign);

    // Preparar payload multipart para cURL
    $postFields = [
      "file"      => new \CURLFile($filePath),
      "api_key"   => $apiKey,
      "timestamp" => $timestamp,
      "signature" => $signature,
      "folder"    => $folder,
    ];

    if (!empty($options["transformation"])) {
      $postFields["transformation"] = $options["transformation"];
    }

    $endpoint = "https://api.cloudinary.com/v1_1/{$cloudName}/video/upload";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
      $errData = json_decode((string)$response, true);
      self::$lastErrorMessage = $errData["error"]["message"] ?? $curlErr ?? "Error HTTP {$httpCode} al comunicar con Cloudinary";
      error_log("Cloudinary Video Upload Error [HTTP {$httpCode}]: " . self::$lastErrorMessage);
      return null;
    }

    $data = json_decode($response, true);
    if (!isset($data["secure_url"])) {
      return null;
    }

    return [
      "success"   => true,
      "url"       => $data["secure_url"],
      "public_id" => $data["public_id"] ?? "",
      "duration"  => $data["duration"] ?? null,
      "format"    => $data["format"] ?? "mp4",
      "bytes"     => $data["bytes"] ?? 0,
      "width"     => $data["width"] ?? null,
      "height"    => $data["height"] ?? null,
    ];
  }

  /**
   * Elimina un video de Cloudinary por su public_id.
   *
   * @param string $publicId Identificador del recurso en Cloudinary.
   * @return bool True si se eliminó con éxito o no existía.
   */
  public static function deleteVideo(string $publicId): bool
  {
    if (!self::isConfigured() || empty($publicId)) {
      return false;
    }

    $cloudName = self::getCloudName();
    $apiKey    = self::getApiKey();
    $apiSecret = self::getApiSecret();

    $timestamp = time();
    $stringToSign = "public_id={$publicId}&timestamp={$timestamp}" . $apiSecret;
    $signature    = sha1($stringToSign);

    $postFields = [
      "public_id"  => $publicId,
      "api_key"    => $apiKey,
      "timestamp"  => $timestamp,
      "signature"  => $signature,
    ];

    $endpoint = "https://api.cloudinary.com/v1_1/{$cloudName}/video/destroy";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
      return false;
    }

    $data = json_decode($response, true);
    return isset($data["result"]) && ($data["result"] === "ok" || $data["result"] === "not found");
  }
}
