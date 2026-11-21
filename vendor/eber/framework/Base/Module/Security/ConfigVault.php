<?php

namespace Base\Module\Security;

/**
 * Cargador y motor criptográfico AEAD de configuración para producción.
 *
 * Descifra App/Config/secrets.vault.php usando FRAME_MASTER_KEY (variable del servidor)
 * o la clave autocontenida en App/Safety/vault.key.php e hidrata el entorno
 * junto con App/Config/compiled.config.php sin depender de .env ni Dotenv.
 */
class ConfigVault
{
  public const COMPILED_CONFIG_REL = 'App/Config/compiled.config.php';
  public const SECRETS_VAULT_REL = 'App/Config/secrets.vault.php';
  public const VAULT_KEY_REL = 'App/Safety/vault.key.php';

  /**
   * Constantes editables permitidas en compiled.config.php (whitelist anti-sobrescritura).
   */
  private const ALLOWED_COMPILED_CONSTANTS = [
    'ALLOWED_TABLES',
    'NO_INDEX',
    'TAGS_VALID_TEXT_INPUT',
    'QUALITY',
    'P_QUALITY',
  ];

  private static bool $booted = false;
  private static array $compiled = [];
  private static array $secrets = [];

  /**
   * Determina el algoritmo AEAD disponible (XChaCha20-Poly1305 preferido o AES-256-GCM).
   */
  public static function getPreferredCipher(): string
  {
    if (function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')) {
      return 'xchacha20-poly1305';
    }
    if (extension_loaded('openssl') && in_array('aes-256-gcm', openssl_get_cipher_methods(true), true)) {
      return 'aes-256-gcm';
    }
    throw new \RuntimeException('No hay extensión criptográfica AEAD disponible (sodium u openssl).');
  }

  /**
   * Genera una clave maestra criptográfica de 256 bits en formato hexadecimal (64 chars).
   */
  public static function generateMasterKey(): string
  {
    return bin2hex(random_bytes(32));
  }

  /**
   * Resuelve la clave maestra desde variable de entorno del servidor o App/Safety/vault.key.php.
   */
  public static function resolveMasterKey(?string $basePath = null): ?string
  {
    $root = self::resolveBasePath($basePath);

    $envCandidates = [
      $_ENV['FRAME_MASTER_KEY'] ?? null,
      $_SERVER['FRAME_MASTER_KEY'] ?? null,
      getenv('FRAME_MASTER_KEY') !== false ? getenv('FRAME_MASTER_KEY') : null,
    ];

    foreach ($envCandidates as $candidate) {
      if (is_string($candidate)) {
        $trimmed = strtolower(trim($candidate));
        if (preg_match('/^[a-f0-9]{64}$/', $trimmed)) {
          return $trimmed;
        }
      }
    }

    $keyFile = $root . '/' . self::VAULT_KEY_REL;
    if (is_file($keyFile)) {
      if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $root);
      }
      $loaded = require $keyFile;
      $rawKey = is_array($loaded) ? ($loaded['key'] ?? '') : (is_string($loaded) ? $loaded : '');
      $trimmed = strtolower(trim((string)$rawKey));
      if (preg_match('/^[a-f0-9]{64}$/', $trimmed)) {
        return $trimmed;
      }
    }

    return null;
  }

  /**
   * Cifra un array asociativo de secretos mediante cifrado autenticado AEAD.
   *
   * @return array<string, mixed>
   */
  public static function encrypt(array $secrets, string $masterKeyHex): array
  {
    $keyBin = self::decodeHexKey($masterKeyHex);
    $plaintext = json_encode($secrets, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $cipher = self::getPreferredCipher();
    $aad = 'eber-frame-vault-v1';

    if ($cipher === 'xchacha20-poly1305') {
      $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
      $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, $aad, $nonce, $keyBin);
      return [
        'v' => 1,
        'cipher' => $cipher,
        'nonce' => base64_encode($nonce),
        'tag' => '',
        'ciphertext' => base64_encode($ciphertext),
      ];
    }

    $nonce = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $keyBin, OPENSSL_RAW_DATA, $nonce, $tag, $aad, 16);
    if ($ciphertext === false || strlen($tag) !== 16) {
      throw new \RuntimeException('Error al cifrar el Vault con AES-256-GCM.');
    }

    return [
      'v' => 1,
      'cipher' => 'aes-256-gcm',
      'nonce' => base64_encode($nonce),
      'tag' => base64_encode($tag),
      'ciphertext' => base64_encode($ciphertext),
    ];
  }

  /**
   * Descifra y verifica integridad AEAD de un envelope de secrets.vault.php (fail-closed).
   *
   * @return array<string, mixed>
   */
  public static function decrypt(array $envelope, string $masterKeyHex): array
  {
    if (($envelope['v'] ?? null) !== 1 || empty($envelope['cipher']) || empty($envelope['nonce']) || !isset($envelope['ciphertext'])) {
      throw new \RuntimeException('Formato de Vault inválido o corrupto.');
    }

    $keyBin = self::decodeHexKey($masterKeyHex);
    $cipher = (string)$envelope['cipher'];
    $nonce = base64_decode((string)$envelope['nonce'], true);
    $ciphertext = base64_decode((string)$envelope['ciphertext'], true);
    $aad = 'eber-frame-vault-v1';

    if ($nonce === false || $ciphertext === false) {
      throw new \RuntimeException('Codificación de Vault inválida.');
    }

    $plaintext = false;
    if ($cipher === 'xchacha20-poly1305') {
      if (!function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_decrypt')) {
        throw new \RuntimeException('El servidor no dispone de ext-sodium para descifrar XChaCha20-Poly1305.');
      }
      if (strlen($nonce) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES) {
        throw new \RuntimeException('Nonce de Vault inválido.');
      }
      $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ciphertext, $aad, $nonce, $keyBin);
    } elseif ($cipher === 'aes-256-gcm') {
      $tag = base64_decode((string)($envelope['tag'] ?? ''), true);
      if ($tag === false || strlen($tag) !== 16 || strlen($nonce) !== 12) {
        throw new \RuntimeException('Tag o IV de Vault AES-256-GCM inválido.');
      }
      $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $keyBin, OPENSSL_RAW_DATA, $nonce, $tag, $aad);
    } else {
      throw new \RuntimeException('Algoritmo de Vault no soportado.');
    }

    if (!is_string($plaintext) || $plaintext === '') {
      throw new \RuntimeException('Fallo de autenticación al descifrar secrets.vault.php (clave incorrecta o archivo alterado).');
    }

    $decoded = json_decode($plaintext, true);
    if (!is_array($decoded)) {
      throw new \RuntimeException('Payload JSON de Vault corrupto.');
    }

    return $decoded;
  }

  /**
   * Comprueba si el proyecto cuenta con artefactos compilados de producción.
   */
  public static function hasCompiledProduction(?string $basePath = null): bool
  {
    $root = self::resolveBasePath($basePath);
    return is_file($root . '/' . self::COMPILED_CONFIG_REL)
      && is_file($root . '/' . self::SECRETS_VAULT_REL);
  }

  /**
   * Arranca la configuración compilada y descifra el Vault hidratando entorno y constantes seguras.
   */
  public static function boot(?string $basePath = null): bool
  {
    if (self::$booted) {
      return true;
    }

    $root = self::resolveBasePath($basePath);
    $compiledPath = $root . '/' . self::COMPILED_CONFIG_REL;
    $vaultPath = $root . '/' . self::SECRETS_VAULT_REL;

    if (!is_file($compiledPath) || !is_file($vaultPath)) {
      return false;
    }

    if (!defined('ROOT_PATH')) {
      define('ROOT_PATH', $root);
    }

    $compiledData = require $compiledPath;
    if (!is_array($compiledData)) {
      throw new \RuntimeException('Archivo compiled.config.php inválido.');
    }

    $envelope = require $vaultPath;
    if (!is_array($envelope)) {
      throw new \RuntimeException('Archivo secrets.vault.php inválido.');
    }

    $masterKey = self::resolveMasterKey($root);
    if ($masterKey === null) {
      throw new \RuntimeException('No se encontró clave maestra para descifrar secrets.vault.php.');
    }

    $secrets = self::decrypt($envelope, $masterKey);

    $envVars = is_array($compiledData['env'] ?? null) ? $compiledData['env'] : [];
    foreach ($secrets as $k => $v) {
      if (is_string($k)) {
        $envVars[$k] = $v;
      }
    }

    // Forzar modo producción fail-closed al arrancar desde compilado de producción
    $envVars['APP_ENV'] = 'production';
    $envVars['ENVIRONMENT'] = 'production';

    foreach ($envVars as $key => $val) {
      $keyStr = (string)$key;
      if ($keyStr === '' || str_starts_with($keyStr, 'HTTP_') || str_starts_with($keyStr, 'SERVER_') || str_starts_with($keyStr, 'REMOTE_') || str_starts_with($keyStr, 'REQUEST_')) {
        continue;
      }
      $strVal = is_bool($val) ? ($val ? 'true' : 'false') : (is_array($val) ? implode(',', $val) : (string)$val);
      $_ENV[$keyStr] = $strVal;
      $_SERVER[$keyStr] = $strVal;
    }

    $constants = is_array($compiledData['constants'] ?? null) ? $compiledData['constants'] : [];
    foreach ($constants as $constName => $constValue) {
      if (in_array((string)$constName, self::ALLOWED_COMPILED_CONSTANTS, true) && !defined((string)$constName)) {
        define((string)$constName, $constValue);
      }
    }

    self::$compiled = $compiledData;
    self::$secrets = $secrets;
    self::$booted = true;

    return true;
  }

  public static function isBooted(): bool
  {
    return self::$booted;
  }

  public static function getCompiledSection(string $section, mixed $default = null): mixed
  {
    return self::$compiled[$section] ?? $default;
  }

  /**
   * Lee únicamente los secretos descifrados del Vault sin hidratar constantes globales.
   *
   * @return array<string, mixed>
   */
  public static function readSecretsOnly(?string $basePath = null): array
  {
    $root = self::resolveBasePath($basePath);
    $vaultPath = $root . '/' . self::SECRETS_VAULT_REL;
    if (!is_file($vaultPath)) {
      return [];
    }
    if (!defined('ROOT_PATH')) {
      define('ROOT_PATH', $root);
    }
    $envelope = require $vaultPath;
    if (!is_array($envelope)) {
      return [];
    }
    $masterKey = self::resolveMasterKey($root);
    if ($masterKey === null) {
      throw new \RuntimeException('Clave maestra ausente para leer secrets.vault.php.');
    }
    return self::decrypt($envelope, $masterKey);
  }

  public static function reset(): void
  {
    self::$booted = false;
    self::$compiled = [];
    self::$secrets = [];
  }

  private static function decodeHexKey(string $masterKeyHex): string
  {
    $trimmed = strtolower(trim($masterKeyHex));
    if (!preg_match('/^[a-f0-9]{64}$/', $trimmed)) {
      throw new \RuntimeException('La clave maestra debe tener exactamente 64 caracteres hexadecimales (256 bits).');
    }
    $bin = @hex2bin($trimmed);
    if ($bin === false || strlen($bin) !== 32) {
      throw new \RuntimeException('No se pudo decodificar la clave maestra.');
    }
    return $bin;
  }

  private static function resolveBasePath(?string $basePath): string
  {
    $raw = $basePath ?: (defined('ROOT_PATH') ? ROOT_PATH : getcwd());
    return rtrim(str_replace('\\', '/', (string)$raw), '/');
  }
}
