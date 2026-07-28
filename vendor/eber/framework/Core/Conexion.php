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
   * Instancia PDO compartida (Singleton).
   */
  private static ?PDO $sharedPdo = null;

  /**
   * Estado de la conexión compartida.
   */
  private static array $sharedState = [];

  private $host;
  private $db;
  private $user;
  private $password;
  private $charset;
  private $pdo;
  private $error;
  private $state = [];

  public function __construct($host = NAMESERVER, $db = BD, $user = USER, $password = PASS, $charset = CHARSET)
  {
    $this->host = $host;
    $this->db = $db;
    $this->user = $user;
    $this->password = $password;
    $this->charset = $charset;

    // Usar Singleton si DB_POOLING está activo y los parámetros son los mismos
    if ($this->shouldUseSingleton($host, $db, $user)) {
      $this->connectSingleton();
    } else {
      $this->connect();
    }
  }

  /**
   * Determina si se debe usar la conexión Singleton.
   */
  private function shouldUseSingleton($host, $db, $user): bool
  {
    // Solo usar Singleton si DB_POOLING está activo
    if (!defined('DB_POOLING') || !DB_POOLING) {
      return false;
    }

    // Solo para las credenciales por defecto
    return $host === NAMESERVER && $db === BD && $user === USER;
  }

  /**
   * Conecta usando el patrón Singleton (conexión compartida).
   */
  private function connectSingleton(): void
  {
    // Si ya existe una conexión válida, reutilizarla
    if (self::$sharedPdo !== null) {
      $this->pdo = self::$sharedPdo;
      $this->state = self::$sharedState;
      return;
    }

    // Crear nueva conexión y almacenarla
    $this->connect();

    if ($this->state[0] === true) {
      self::$sharedPdo = $this->pdo;
      self::$sharedState = $this->state;
    }
  }

  private function connect()
  {
    if (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') {
      $dsn = "pgsql:host={$this->host};dbname={$this->db};options='--client_encoding={$this->charset}'";
    } else {
      $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
    }

    $options = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
      // Conexión persistente para mejor rendimiento
      PDO::ATTR_PERSISTENT         => defined('DB_POOLING') && DB_POOLING,
    ];

    try {
      $this->pdo = new PDO($dsn, $this->user, $this->password, $options);
      $this->state = [true, "Conexión exitosa"];
      return $this->state;
    } catch (PDOException $e) {
      $this->error = $e->getMessage();
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
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
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
    $showDetails = !defined('ENVIRONMENT') || ENVIRONMENT !== 'production';

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
   * Reinicia la conexión Singleton (útil para tests).
   */
  public static function resetConnection(): void
  {
    self::$sharedPdo = null;
    self::$sharedState = [];
  }

  /**
   * Obtiene la instancia PDO compartida directamente.
   * Útil cuando solo necesitas la conexión sin crear una instancia de Conexion.
   */
  public static function getInstance(): ?PDO
  {
    if (self::$sharedPdo === null) {
      $conexion = new self();
    }
    return self::$sharedPdo;
  }
}
