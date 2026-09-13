<?php

namespace Core;

use PDO;
use PDOException;
use \Core\ErrorHandler;

/**
 * Clase de conexión a base de datos con patrón Singleton.
 * 
 * Cuando DB_POOLING está activo, reutiliza la misma conexión PDO
 * para todas las instancias, mejorando el rendimiento.
 */
class Conexion
{
  /**
   * Instancia PDO compartida (Singleton por request).
   */
  private static ?PDO $sharedPdo = null;

  /**
   * Estado de la conexión compartida.
   */
  private static array $sharedState = [];

  /**
   * Último error registrado al conectar.
   */
  private static ?string $lastError = null;

  private $host;
  private $db;
  private $user;
  private $password;
  private $charset;
  private $pdo;
  private $error;
  private $state = [];

  public function __construct($host = null, $db = null, $user = null, $password = null, $charset = null)
  {
    $this->host = $host ?? (defined('NAMESERVER') ? NAMESERVER : '127.0.0.1');
    $this->db = $db ?? (defined('BD') ? BD : '');
    $this->user = $user ?? (defined('USER') ? USER : 'root');
    $this->password = $password ?? (defined('PASS') ? PASS : '');
    $this->charset = $charset ?? (defined('CHARSET') ? CHARSET : 'utf8mb4');

    // Reutilizar conexión compartida si es la conexión por defecto y ya existe
    if ($this->isDefaultConnection() && self::$sharedPdo !== null) {
      $this->pdo = self::$sharedPdo;
      $this->state = self::$sharedState;
      return;
    }

    // Conectar a la base de datos
    $this->connect();

    // Si es la conexión por defecto y la conexión fue exitosa, almacenarla como compartida
    if ($this->isDefaultConnection() && ($this->state[0] ?? false) === true) {
      self::$sharedPdo = $this->pdo;
      self::$sharedState = $this->state;
    }
  }

  /**
   * Determina si los parámetros corresponden a la conexión principal por defecto.
   */
  private function isDefaultConnection(): bool
  {
    $defaultHost = defined('NAMESERVER') ? NAMESERVER : '127.0.0.1';
    $defaultDb = defined('BD') ? BD : '';
    $defaultUser = defined('USER') ? USER : 'root';

    return $this->host === $defaultHost && $this->db === $defaultDb && $this->user === $defaultUser;
  }

  private function connect()
  {
    $port = defined('DB_PORT') && !empty(DB_PORT) ? DB_PORT : ($_ENV['DB_PORT'] ?? '');
    $portStr = !empty($port) ? ";port={$port}" : '';

    if (defined('DB_DRIVER') && DB_DRIVER === 'sqlite') {
      $sqlitePath = $this->db;
      if ($sqlitePath !== ':memory:' && !empty($sqlitePath) && !file_exists($sqlitePath) && defined('ROOT_PATH')) {
        $candidate = rtrim(ROOT_PATH, '/\\') . '/' . ltrim($sqlitePath, '/\\');
        if (file_exists($candidate)) {
          $sqlitePath = $candidate;
        }
      }
      $dsn = "sqlite:{$sqlitePath}";
    } elseif (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') {
      $dsn = "pgsql:host={$this->host}{$portStr};dbname={$this->db};options='--client_encoding={$this->charset}'";
    } else {
      $dsn = "mysql:host={$this->host}{$portStr};dbname={$this->db};charset={$this->charset}";
    }

    $options = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
      // Conexión persistente para mejor rendimiento si DB_POOLING está habilitado
      PDO::ATTR_PERSISTENT         => defined('DB_POOLING') && DB_POOLING,
    ];

    try {
      $this->pdo = new PDO($dsn, $this->user, $this->password, $options);

      if (defined('DB_DRIVER') && DB_DRIVER === 'sqlite') {
        $this->pdo->exec("PRAGMA journal_mode = WAL;");
        $this->pdo->exec("PRAGMA synchronous = NORMAL;");
        $this->pdo->exec("PRAGMA busy_timeout = 5000;");
        $this->pdo->exec("PRAGMA cache_size = -64000;");
        $this->pdo->exec("PRAGMA mmap_size = 268435456;");
        $this->pdo->exec("PRAGMA temp_store = MEMORY;");
      }

      $this->state = [true, "Conexión exitosa"];
      self::$lastError = null;
      return $this->state;
    } catch (PDOException $e) {
      $this->error = $e->getMessage();
      self::$lastError = $e->getMessage();
      $this->state = [false, $this->getSecureErrorMessage($e)];

      // Manejar errores específicos
      $this->handleConnectionError($e);

      return $this->state;
    }
  }

  /**
   * Obtiene un mensaje de error seguro según el entorno.
   */
  private function getSecureErrorMessage(PDOException $e): string
  {
    // En producción, no mostrar detalles del error
    $isProd = defined('ENVIRONMENT') && in_array(strtolower((string)ENVIRONMENT), ['production', 'prod'], true);
    if ($isProd) {
      return 'Error de conexión a la base de datos';
    }

    // En desarrollo, mostrar el mensaje completo
    return $e->getMessage();
  }

  /**
   * Maneja errores de conexión específicos.
   */
  private function handleConnectionError(PDOException $e): void
  {
    // Solo mostrar errores detallados en desarrollo
    $isProd = defined('ENVIRONMENT') && in_array(strtolower((string)ENVIRONMENT), ['production', 'prod'], true);
    $showDetails = !$isProd;

    if ($e->getCode() == 1049) {
      $message = $showDetails
        ? 'Este error indica que no existe la base de datos. Verifique que exista la base de datos si no es asi, diríjase a su panel y cree una antes de instalar las tablas a la base de datos'
        : 'Error de configuración de base de datos';
      ErrorHandler::handle_code(409, '1049', $message);
    }

    if ($e->getCode() == 1045) {
      $message = $showDetails
        ? 'Las credenciales de acceso a la base de dato son incorrectas. Por favor revise su configuración y vuelva a intentarlo'
        : 'Error de autenticación de base de datos';
      ErrorHandler::handle_code(401, '1045', $message);
    }
  }

  public function pdo_state()
  {
    return $this->state;
  }

  public function pdo_conexion()
  {
    return $this->pdo;
  }

  /**
   * Obtiene el último error de conexión registrado.
   */
  public static function getLastError(): ?string
  {
    return self::$lastError;
  }

  /**
   * Reinicia la conexión Singleton (útil para tests).
   */
  public static function resetConnection(): void
  {
    self::$sharedPdo = null;
    self::$sharedState = [];
    self::$lastError = null;
  }

  /**
   * Establece manualmente una instancia PDO (útil para inyección o tests).
   */
  public static function setConnection(?PDO $pdo): void
  {
    self::$sharedPdo = $pdo;
    self::$sharedState = $pdo !== null ? [true, 'Conexión inyectada'] : [];
    self::$lastError = null;
  }

  /**
   * Obtiene la instancia PDO compartida directamente.
   * Si aún no existe, inicializa la conexión por defecto.
   */
  public static function getInstance(): ?PDO
  {
    if (self::$sharedPdo === null) {
      $conexion = new self();
      if ($conexion->pdo !== null) {
        self::$sharedPdo = $conexion->pdo;
        self::$sharedState = $conexion->state;
      }
    }
    return self::$sharedPdo;
  }

  /**
   * Alias de conveniencia para getInstance() (Composición).
   */
  public static function getPdo(): ?PDO
  {
    return self::getInstance();
  }
}
