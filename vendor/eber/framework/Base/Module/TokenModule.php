<?php

namespace Base\Module;

/**
 * Módulo para la generación y validación de tokens y JWT.
 * 
 * Permite manejar tokens seguros y firmados de forma estática y dinámica.
 */
class TokenModule
{
  /**
   * Nombre del perfil activo para este manejador dinámico.
   */
  private ?string $profile = null;

  /**
   * Configuración del perfil.
   */
  private array $profileConfig = [];

  /**
   * Crea una instancia del módulo vinculada a un perfil configurado en el proyecto.
   * 
   * @param string $profile Nombre del perfil (ej. 'emails', 'recovery').
   * @return self
   */
  public static function from(string $profile): self
  {
    $instance = new self();
    $instance->profile = $profile;

    $config = self::loadConfig();
    $profiles = $config['profiles'] ?? [];

    if (isset($profiles[$profile])) {
      $instance->profileConfig = $profiles[$profile];
    } else {
      // Fallback seguro si el perfil no existe
      $instance->profileConfig = [
        'expiration' => 3600,
        'algo' => 'sha256'
      ];
    }

    return $instance;
  }

  /**
   * Genera un token firmado para el perfil actual.
   * El token contiene la información y la fecha de expiración, haciéndolo completamente
   * autocontenido y libre de almacenamiento en base de datos.
   * 
   * @param mixed $data Datos a encapsular en el token.
   * @param int|null $customExpiration Expiración personalizada en segundos.
   * @return string
   */
  public function create(mixed $data, ?int $customExpiration = null): string
  {
    $ttl = $customExpiration ?? ($this->profileConfig['expiration'] ?? 3600);
    $expirationTime = time() + $ttl;

    $payload = [
      'data' => $data,
      'exp' => $expirationTime,
      'profile' => $this->profile
    ];

    $encodedPayload = self::base64UrlEncode(json_encode($payload));
    $algo = $this->profileConfig['algo'] ?? 'sha256';
    $secret = self::getSeed();

    $signature = hash_hmac($algo, $encodedPayload . '.' . $this->profile, $secret);
    $encodedSignature = self::base64UrlEncode($signature);

    return $encodedPayload . '.' . $encodedSignature;
  }

  /**
   * Valida el token del perfil actual y extrae sus datos si es correcto.
   * Verifica la integridad de la firma y que la fecha de expiración no haya pasado.
   * 
   * @param string $token Token firmado a validar.
   * @return mixed Datos originales si es válido, o false en caso de error o expiración.
   */
  public function validate(string $token): mixed
  {
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
      return false;
    }

    list($encodedPayload, $encodedSignature) = $parts;

    $algo = $this->profileConfig['algo'] ?? 'sha256';
    $secret = self::getSeed();

    $signature = hash_hmac($algo, $encodedPayload . '.' . $this->profile, $secret);
    $expectedSignature = self::base64UrlEncode($signature);

    // Comparación segura en tiempo constante para mitigar ataques de temporización
    if (!hash_equals($expectedSignature, $encodedSignature)) {
      return false;
    }

    $payload = json_decode(self::base64UrlDecode($encodedPayload), true);
    if (!is_array($payload)) {
      return false;
    }

    // Validar perfil
    if (($payload['profile'] ?? '') !== $this->profile) {
      return false;
    }

    // Validar expiración
    if (isset($payload['exp']) && time() > $payload['exp']) {
      return false;
    }

    return $payload['data'] ?? null;
  }

  // =========================================================================
  // METODOS PARA JWT (JSON WEB TOKENS)
  // =========================================================================

  /**
   * Crea un JSON Web Token (JWT) estándar firmado usando HS256.
   * 
   * @param array $dataSession Datos del usuario o sesión.
   * @param int|null $expiration Tiempo de expiración del JWT en segundos (default de config o 7 días).
   * @return string Token JWT en formato header.payload.signature
   */
  public static function configJWT(array $dataSession, ?int $expiration = null): string
  {
    $config = self::loadConfig();
    $jwtConfig = $config['jwt'] ?? [];
    $ttl = $expiration ?? ($jwtConfig['expiration'] ?? (86400 * 7));

    $header = [
      'alg' => 'HS256',
      'typ' => 'JWT'
    ];

    $payload = [
      'iat' => time(),
      'exp' => time() + $ttl,
      'data' => $dataSession
    ];

    $encodedHeader = self::base64UrlEncode(json_encode($header));
    $encodedPayload = self::base64UrlEncode(json_encode($payload));

    $secret = self::getSeed();
    $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $secret, true);
    $encodedSignature = self::base64UrlEncode($signature);

    return $encodedHeader . '.' . $encodedPayload . '.' . $encodedSignature;
  }

  /**
   * Valida un token JWT.
   * Verifica la autenticidad de la firma HS256 y la validez temporal del payload.
   * 
   * @param string $token Token JWT.
   * @return array|false Datos de la sesión si es válido, o false en caso contrario.
   */
  public static function validateJWT(string $token): array|false
  {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
      return false;
    }

    list($encodedHeader, $encodedPayload, $encodedSignature) = $parts;

    $secret = self::getSeed();
    $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $secret, true);
    $expectedSignature = self::base64UrlEncode($signature);

    // Comparación segura en tiempo constante
    if (!hash_equals($expectedSignature, $encodedSignature)) {
      return false;
    }

    $payload = json_decode(self::base64UrlDecode($encodedPayload), true);
    if (!is_array($payload)) {
      return false;
    }

    // Validar expiración
    if (isset($payload['exp']) && time() > $payload['exp']) {
      return false;
    }

    return $payload['data'] ?? false;
  }

  // =========================================================================
  // UTILERIAS Y CARGA DE CONFIGURACION
  // =========================================================================

  /**
   * Obtiene la semilla secreta desde el entorno (.env).
   * 
   * @return string
   */
  private static function getSeed(): string
  {
    $seed = $_ENV['SEED'] ?? getenv('SEED') ?? (defined('SEED') ? SEED : '');
    if (empty($seed)) {
      // Fallback a un hash del nombre del sitio o un valor constante seguro
      $seed = defined('NAME_SITE') ? md5(NAME_SITE) : 'fallback_secret_seed_phrase';
    }
    return $seed;
  }

  /**
   * Codificación base64 url-safe.
   */
  private static function base64UrlEncode(string $data): string
  {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
  }

  /**
   * Decodificación base64 url-safe.
   */
  private static function base64UrlDecode(string $data): string
  {
    $remainder = strlen($data) % 4;
    if ($remainder) {
      $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
  }

  /**
   * Carga la configuración de tokens del proyecto local.
   * 
   * @return array
   */
  private static function loadConfig(): array
  {
    $localConfigPath = null;
    if (defined('ROOT_PATH')) {
      $localConfigPath = ROOT_PATH . '/App/Config/TokenConfiguration.php';
    } else {
      $dirFallback = dirname(__DIR__, 5) . '/App/Config/TokenConfiguration.php';
      if (file_exists($dirFallback)) {
        $localConfigPath = $dirFallback;
      } else {
        $cwdFallback = getcwd() . '/App/Config/TokenConfiguration.php';
        if (file_exists($cwdFallback)) {
          $localConfigPath = $cwdFallback;
        }
      }
    }

    if ($localConfigPath && file_exists($localConfigPath)) {
      $config = require $localConfigPath;
      if (is_array($config)) {
        return $config;
      }
    }

    return [];
  }
}
