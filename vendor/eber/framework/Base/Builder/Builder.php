<?php

namespace Base\Builder;

use Closure;
use Core\Conexion;
use Core\ErrorHandler;
use PDOException;
use PDO;
use Base\Module\Session;

class Builder extends Conexion
{

  protected $table;
  protected $sql;
  protected $query;
  protected $select = '*';
  protected $count = '';
  protected $order = '';
  protected $limit = '';
  protected $concat;
  protected $where = '';
  protected $join;
  protected $full_join;
  protected $group;
  protected $having = '';
  protected $where_not_in;
  protected $values = [];
  protected $id_insert = '';
  protected $query_error = null;
  protected $total_reg;
  protected $columnTotal;
  protected $infoColumn = [];
  protected $last_id;
  protected $rateLimitMaxAttempts = null;
  protected $rateLimitBlockSeconds = 60;

  /**
   * Constructor de la clase Builder.
   *
   * @param string|null $table El nombre de la tabla principal para la consulta.
   */
  public function __construct($table = null)
  {

    parent::__construct();
    (!is_null($table) ? $this->table = $table : null);
  }

  /**
   * Establece un límite de tasa (Rate Limit) para la ejecución de la consulta.
   *
   * @param int $attempts El número máximo de intentos permitidos.
   * @param int $seconds El tiempo de bloqueo en segundos (por defecto 60). 0 para bloqueo indefinido.
   * @return self
   */
  public function rate(int $attempts, int $seconds = 60): self
  {
    $this->rateLimitMaxAttempts = $attempts;
    $this->rateLimitBlockSeconds = $seconds;
    return $this;
  }

  /**
   * Verifica si la IP y acción actual están bloqueadas por el límite de tasa.
   *
   * @param string $actionKey Identificador único para el bloqueo.
   * @return int|bool Segundos restantes si está bloqueado, o true si está permitido.
   */
  protected function checkRateLimit(string $actionKey)
  {
    if ($this->rateLimitMaxAttempts === null) {
      return true;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $tableName = defined('DB_DRIVER') && DB_DRIVER === 'pgsql' ? 'ratelimits' : 'RateLimits';

    // Usamos una instancia limpia para no alterar el estado de esta consulta
    $db = new self($tableName);
    if (!$db->table_exist($tableName)) {
      return true;
    }

    $record = $db->where('ip', $ip)->where('action_key', $actionKey)->get_one();

    if ($record && !empty($record[0])) {
      $row = $record[0];
      $blockedUntil = $row['blocked_until'];

      if ($blockedUntil !== null) {
        $now = time();
        $blockedTime = strtotime($blockedUntil);
        if ($blockedTime > $now) {
          return $blockedTime - $now; // Segundos restantes
        } else {
          // El bloqueo ya expiró, reseteamos el contador
          $this->clearRateLimit($actionKey);
        }
      }
    }

    return true;
  }

  /**
   * Incrementa el contador de intentos del límite de tasa.
   *
   * @param string $actionKey Identificador único para el bloqueo.
   * @return void
   */
  protected function incrementRateLimit(string $actionKey): void
  {
    if ($this->rateLimitMaxAttempts === null) {
      return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $tableName = defined('DB_DRIVER') && DB_DRIVER === 'pgsql' ? 'ratelimits' : 'RateLimits';

    $db = new self($tableName);
    if (!$db->table_exist($tableName)) {
      return;
    }

    $record = $db->where('ip', $ip)->where('action_key', $actionKey)->get_one();

    if ($record && !empty($record[0])) {
      $row = $record[0];
      $attempts = $row['attempts'] + 1;
      
      $updateData = [
        'attempts' => $attempts,
        'last_attempt' => date('Y-m-d H:i:s')
      ];

      if ($attempts >= $this->rateLimitMaxAttempts) {
        $updateData['blocked_until'] = ($this->rateLimitBlockSeconds === 0)
          ? '9999-12-31 23:59:59'
          : date('Y-m-d H:i:s', time() + $this->rateLimitBlockSeconds);
      }

      $db2 = new self($tableName);
      // Actualizamos usando una consulta directa para soportar múltiples WHERE dinámicamente
      $sql = "UPDATE {$tableName} SET attempts = ?, blocked_until = ?, last_attempt = ? WHERE ip = ? AND action_key = ?";
      $db2->query_foreign($sql, [$updateData['attempts'], $updateData['blocked_until'] ?? null, $updateData['last_attempt'], $ip, $actionKey]);
    } else {
      $insertData = [
        'ip' => $ip,
        'action_key' => $actionKey,
        'attempts' => 1,
        'last_attempt' => date('Y-m-d H:i:s'),
        'blocked_until' => null
      ];

      if ($this->rateLimitMaxAttempts <= 1) {
        $insertData['blocked_until'] = ($this->rateLimitBlockSeconds === 0)
          ? '9999-12-31 23:59:59'
          : date('Y-m-d H:i:s', time() + $this->rateLimitBlockSeconds);
      }

      $db2 = new self($tableName);
      $db2->create($insertData);
    }
  }

  /**
   * Resetea/elimina el límite de tasa para la IP y acción dada.
   *
   * @param string $actionKey Identificador único.
   * @param string|null $ip IP específica, o nulo para auto-detectar.
   * @return void
   */
  public function clearRateLimit(string $actionKey, ?string $ip = null): void
  {
    $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    $tableName = defined('DB_DRIVER') && DB_DRIVER === 'pgsql' ? 'ratelimits' : 'RateLimits';

    $db = new self($tableName);
    if (!$db->table_exist($tableName)) {
      return;
    }

    $sql = "DELETE FROM {$tableName} WHERE ip = ? AND action_key = ?";
    $db->query_foreign($sql, [$ip, $actionKey]);
  }

  /**
   * Valida un identificador SQL (nombre de tabla o columna) para prevenir inyección SQL.
   * Solo permite caracteres alfanuméricos, guiones bajos, puntos y alias (AS).
   *
   * @param string $identifier El identificador a validar.
   * @return string El identificador validado.
   * @throws \InvalidArgumentException Si el identificador contiene caracteres no permitidos.
   */
  protected function validateIdentifier($identifier)
  {

    // Permite: letras, números, guiones bajos, puntos, espacios (para alias), y la palabra AS
    // Ejemplos válidos: "users", "users.id", "users AS u", "tabla_1.columna_2"
    $pattern = '/^[a-zA-Z_][a-zA-Z0-9_\.]*(\s+(AS\s+)?[a-zA-Z_][a-zA-Z0-9_]*)?$/i';

    // Dividir por comas para manejar múltiples columnas
    $parts = array_map('trim', explode(',', $identifier));

    foreach ($parts as $part) {

      // Permitir * para SELECT *
      if ($part === '*') continue;

      // Permitir expresiones con alias como "tabla.*"
      if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*\.\*$/', $part)) continue;

      if (!preg_match($pattern, $part)) {

        throw new \InvalidArgumentException("Identificador SQL inválido: {$part}");
      }
    }

    return $identifier;
  }

  /**
   * Valida que un valor de ordenamiento sea válido (ASC o DESC).
   *
   * @param string $order El valor de ordenamiento.
   * @return string El valor validado (ASC o DESC).
   */
  protected function validateOrderDirection($order)
  {

    $order = strtoupper(trim($order));

    if (!in_array($order, ['ASC', 'DESC'])) {

      return 'ASC'; // Valor por defecto seguro

    }

    return $order;
  }

  /**
   * Procesa las consultas preparadas con PDO.
   * Ejecuta el SQL, maneja errores y almacena resultados internos.
   *
   * @param string $sql   La consulta SQL a preparar y ejecutar.
   * @param array  $datas Los valores (bindings) para la consulta preparada.
   * @return self|array   Retorna $this en caso de éxito, o un array de error en caso de fallo.
   */
  private function query($sql, $datas = [])
  {

    try {

      // Obtenemos la conexión una sola vez.
      $pdo = $this->pdo_conexion();

      // 1. Unificamos la lógica: siempre usar prepare y execute.
      // Es más seguro y consistente.
      $stmt = $pdo->prepare($sql);

      // 2. Simplificamos la ejecución: execute() puede recibir el array directamente.
      $stmt->execute($datas);

      // Guardamos el statement para poder usar fetch(), fetchAll(), etc., después.
      $this->query = $stmt;

      // 3. Obtenemos la información de las columnas
      $this->columnTotal = $stmt->columnCount();

      // Verificamos si la consulta es un SELECT (que devuelve columnas)
      if ($this->columnTotal > 0) {

        // getColumnMeta() necesita un bucle para obtener la info de cada columna.
        $this->infoColumn = []; // Inicializamos el array de información

        for ($i = 0; $i < $this->columnTotal; $i++) {

          // Obtenemos la metadata de la columna en la posición $i
          $this->infoColumn[] = $stmt->getColumnMeta($i);
        }
      } else {

        // Si no es un SELECT (INSERT, UPDATE, DELETE), el array de info estará vacío.
        $this->infoColumn = [];
      }

      // Obtenemos el ID del último registro insertado (si la consulta fue un INSERT).
      $this->last_id = $pdo->lastInsertId();

      return $this;
    } catch (PDOException $e) {

      // 4. Mejoramos el manejo de errores para obtener el mensaje real.
      $this->query_error = [
        'failed' => true,
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'raw' => $e
      ];

      // Solo mostrar detalles en desarrollo, no en producción
      if (defined('ENVIRONMENT') && ENVIRONMENT === 'DEV') {

        ErrorHandler::handle_code(500, 1052, "Error en builder() <br>" . $this->query_error['message'] . "<br> <div class='x16 color3'>" . $this->query_error['raw'] . "</div>" . "<br> <div class='x18 bold600 color2'>" . $sql . "</div>");
      } else {

        // En producción, mensaje genérico sin exponer SQL
        ErrorHandler::handle_code(500, 1052, "Error en la consulta de base de datos. Por favor, contacte al administrador.");
      }

      return $this->query_error;
    }
  }

  /**
   * Ejecuta una consulta SQL cruda (raw) de forma pública.
   * Es un alias público para el método privado query().
   *
   * @param string $sql  La consulta SQL a ejecutar.
   * @param array  $data Los valores (bindings) para la consulta.
   * @return self
   */
  public function query_foreign($sql, $data = [])
  {

    $this->values = $data;
    $this->query($sql, $this->values);
    return $this;
  }

  /**
   * Devuelve el último ID insertado en la base de datos después de una consulta INSERT.
   *
   * @return string|false El último ID insertado, o false si no aplica.
   */
  public function last_id()
  {

    return $this->last_id;
  }

  /**
   * Verifica si una tabla específica existe en la base de datos.
   *
   * @param string $table El nombre de la tabla a verificar.
   * @return bool True si la tabla existe, False si no.
   */
  public function table_exist($table): bool
  {

    // Validar el nombre de la tabla antes de usarlo
    $this->validateIdentifier($table);

    if (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') {

      $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?";
    } else {

      // MySQL: SHOW TABLES no soporta placeholders, usamos información schema
      $sql = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?";
    }

    $query = $this->query($sql, [$table])->get_all();

    if (!empty($query[0])) {

      return true;
    } else {

      return false;
    }
  }

  /**
   * Construye y ejecuta la consulta (si no se ha hecho) y devuelve un único registro.
   *
   * @return array|false Un array asociativo con el primer registro encontrado, o false si no hay resultados.
   */
  public function get_one()
  {

    if (empty($this->query)) {

      $sql = "SELECT {$this->select} {$this->concat} {$this->count} {$this->total_reg} FROM {$this->table} AS {$this->table}";

      if ($this->join) {

        $sql .= " {$this->join}";
      }

      if ($this->full_join) {

        $sql .= " {$this->full_join} ";
      }

      if ($this->where) {

        $sql .= " {$this->where}";
      }

      if ($this->group) {

        $sql .= " GROUP BY {$this->group}";
      }

      if ($this->having) {

        $sql .= " {$this->having}";
      }

      if ($this->order) {

        $sql .= " ORDER BY {$this->order}";
      }

      // INTEGRACIÓN DE RATE LIMIT (Evitar recursión sobre la propia tabla de rate limit)
      if ($this->rateLimitMaxAttempts !== null && strcasecmp($this->table, 'ratelimits') !== 0 && strcasecmp($this->table, 'RateLimits') !== 0) {
        $actionKey = 'query:' . md5($sql . json_encode($this->values));
        $rateStatus = $this->checkRateLimit($actionKey);
        if ($rateStatus !== true) {
          $this->reset();
          throw new \Exception("Rate limit exceeded. Try again in " . $rateStatus . " seconds.");
        }
        $this->incrementRateLimit($actionKey);
      }

      $this->query($sql, $this->values);
    }

    $msg_error = $this->query_error;

    if (!$msg_error) {

      $reg[0] = $this->query->fetch(PDO::FETCH_ASSOC);

      // Resetear el estado para permitir nuevas consultas
      $this->reset();

      // Retorna false si no hay resultados, o el array con los datos
      return $reg[0] === false ? false : $reg;
    } else {

      $this->reset();
      return false;
    }
  }

  /**
   * Construye y ejecuta la consulta (si no se ha hecho) y devuelve todos los registros.
   *
   * @return array Un array de arrays asociativos con todos los registros, o un array de error.
   */
  public function get_all()
  {

    if (empty($this->query)) {

      $sql = "SELECT {$this->select} {$this->concat} {$this->count} {$this->total_reg} FROM {$this->table} AS {$this->table}";

      if ($this->join) {

        $sql .= " {$this->join} ";
      }

      if ($this->full_join) {

        $sql .= " {$this->full_join} ";
      }

      if ($this->where) {

        $sql .= " {$this->where}";
      }

      if ($this->group) {

        $sql .= " GROUP BY {$this->group} ";
      }

      if ($this->having) {

        $sql .= " {$this->having}";
      }

      if ($this->order) {

        $sql .= " ORDER BY {$this->order}";
      }

      if ($this->limit) {

        $sql .= " LIMIT {$this->limit}";
      }

      // INTEGRACIÓN DE RATE LIMIT (Evitar recursión sobre la propia tabla de rate limit)
      if ($this->rateLimitMaxAttempts !== null && strcasecmp($this->table, 'ratelimits') !== 0 && strcasecmp($this->table, 'RateLimits') !== 0) {
        $actionKey = 'query:' . md5($sql . json_encode($this->values));
        $rateStatus = $this->checkRateLimit($actionKey);
        if ($rateStatus !== true) {
          $this->reset();
          throw new \Exception("Rate limit exceeded. Try again in " . $rateStatus . " seconds.");
        }
        $this->incrementRateLimit($actionKey);
      }

      $this->query($sql, $this->values);
    }

    $msg_error = $this->query_error;

    if (!$msg_error) {

      $reg = $this->query->fetchAll(PDO::FETCH_ASSOC);

      // Resetear el estado para permitir nuevas consultas
      $this->reset();

      // Retorna false si no hay resultados, o el array con los datos
      return empty($reg) ? false : $reg;
    } else {

      $this->reset();
      return false;
    }
  }

  /**
   * Agrega una subconsulta de conteo total (COUNT(*) OVER()) para paginación.
   * Esto permite obtener el total de registros sin aplicar el LIMIT.
   *
   * @return self
   */
  public function count_reg()
  {

    $this->total_reg = ",COUNT(*) OVER() AS total_reg_in_table ";
    return $this;
  }

  /**
   * Busca registros donde una columna específica coincide con un valor.
   *
   * @param string $col  El nombre de la columna de la BD.
   * @param mixed  $camp El valor a comparar.
   * @return array|false Los resultados de la consulta, o false si no hay resultados.
   */
  public function find($col, $camp)
  {

    $sql = "SELECT {$this->select} FROM {$this->table} WHERE {$col} = (?)";
    return $this->query($sql, [$camp])->get_all();
  }

  /**
   * Especifica las columnas que debe recuperar la consulta.
   *
   * @param string ...$camp Lista de columnas a seleccionar (ej. 'id', 'nombre', 'email').
   * @return self
   */
  public function select(...$camp)
  {

    $camp = implode(', ', $camp);
    $this->select = "{$camp}";
    return $this;
  }

  /**
   * Especifica las columnas que NO debe recuperar la consulta.
   * Ejecuta una consulta preliminar para obtener todos los nombres de columna y luego filtra.
   *
   * @param string ...$camp Lista de columnas a excluir.
   * @return self
   */
  public function non_select(...$camp)
  {

    // Optimización: Agregar LIMIT 1 para evitar escaneo completo de tabla solo para obtener columnas
    if (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') {

      $sql = "SELECT * FROM {$this->table} {$this->join} LIMIT 1 OFFSET 0";
    } else {

      $sql = "SELECT * FROM {$this->table} {$this->join} LIMIT 1";
    }

    $consul = $this->pdo_conexion()->query($sql);
    $data = $consul->fetch(PDO::FETCH_ASSOC);

    if ($data) {

      $data = array_keys($data);
      $data = array_diff($data, $camp);
      $data = implode(', ', $data);
      $this->select = " {$data} ";
    }

    return $this;
  }

  /**
   * Agrega una función de agregación COUNT() a la consulta.
   *
   * @param string      $col      La columna a contar (ej. 'id' o '*').
   * @param string|null $alias    Un alias opcional para el resultado del conteo (ej. 'total').
   * @param bool        $distinct Si es true, usa COUNT(DISTINCT column) para evitar duplicados.
   * @return self
   */
  public function count($col, $alias = null, $distinct = false)
  {

    $countExpr = $distinct ? "COUNT(DISTINCT {$col})" : "COUNT({$col})";

    if ($alias != null) {

      if ($this->count) {

        $this->count .= " ,{$countExpr} as {$alias} ";
      } else {

        $this->count = " ,{$countExpr} as {$alias} ";
      }
    } else {

      if ($this->count) {

        $this->count .= " ,{$countExpr} ";
      } else {

        $this->count = " ,{$countExpr} ";
      }
    }

    return $this;
  }

  /**
   * Agrega una función de agregación GROUP_CONCAT() a la consulta.
   *
   * @param string      $col   La columna a concatenar.
   * @param string      $sep   El separador a usar (ej. "','").
   * @param string|null $alias Un alias opcional para el resultado.
   * @return self
   */
  public function group_concat($col, $sep, $alias = null)
  {

    if (is_null($alias)) {

      $alias = $sep;
      $sep = "','";
    }

    if (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') {

      // PostgreSQL usa STRING_AGG
      $fragment = " ,STRING_AGG({$col}::text, {$sep}) AS {$alias} ";
    } else {

      // MySQL usa GROUP_CONCAT
      $fragment = " ,GROUP_CONCAT({$col} SEPARATOR {$sep}) AS {$alias} ";
    }

    if ($this->concat) {

      $this->concat .= $fragment;
    } else {

      $this->concat = $fragment;
    }

    return $this;
  }

  /**
   * Agrega una cláusula GROUP BY a la consulta.
   *
   * @param string $group La columna o columnas por las que agrupar.
   * @return self
   */
  public function group($group)
  {

    // Validar identificadores para prevenir inyección SQL
    $this->validateIdentifier($group);
    $this->group = " {$group} ";
    return $this;
  }

  /**
   * Unico método para añadir cláusulas WHERE.
   * Detecta la operación a realizar basado en los argumentos.
   *
   * Ejemplos:
   * where('id', 1)
   * where('id', '>', 1)
   * where('id', [1, 2, 3])
   * where('status', 'IS NULL')
   * where(function($query) { $query->where('a', 1)->orWhere('b', 2); })
   *
   * @param string|Closure $col         La columna, o una función para agrupar.
   * @param mixed|null     $op          El operador, o el valor.
   * @param mixed|null     $val         El valor.
   * @param string         $boolean El conector lógico ('AND' o 'OR').
   * @return self
   */
  public function where($col, $op = null, $val = null, $boolean = 'AND')
  {

    // Caso 1: Agrupación de condiciones con ( ... )
    if ($col instanceof Closure) {

      $query = new self();
      $col($query);
      $nestedSql = ltrim($query->getWhere(), 'WHERE ');

      if ($nestedSql) {

        $this->addClause("({$nestedSql})", $query->getValues(), $boolean);
      }

      return $this;
    }

    // --- LÓGICA CORREGIDA PARA INTERPRETAR ARGUMENTOS ---
    $column = $col;
    $operator = $op;
    $value = $val;

    if (func_num_args() === 2) {

      if (is_array($op)) {

        // Abreviatura para IN: where('id', [1, 2, 3])
        $operator = 'IN';
        $value = $op;
      } elseif (is_string($op) && in_array(strtoupper(trim($op)), ['IS NULL', 'IS NOT NULL'])) {

        // CORRECCIÓN: Detecta IS NULL / IS NOT NULL explícitamente
        $operator = strtoupper(trim($op));
        $value = null; // No hay valor para este operador

      } else {

        // Abreviatura para igualdad: where('id', 1)
        $operator = '=';
        $value = $op;
      }
    }
    // --- FIN DE LA LÓGICA DE INTERPRETACIÓN ---

    $operator = strtoupper(trim($operator));
    $sql = '';
    $bindings = [];

    if (in_array($operator, ['IN', 'NOT IN'])) {

      if (!empty($value)) {

        $placeholders = implode(', ', array_fill(0, count($value), '?'));
        $sql = "{$column} {$operator} ({$placeholders})";
        $bindings = $value;
      } else {

        $sql = '0 = 1';
      }
    } elseif (in_array($operator, ['BETWEEN', 'NOT BETWEEN'])) {

      $sql = "{$column} {$operator} ? AND ?";
      $bindings = $value;
    } elseif (in_array($operator, ['IS NULL', 'IS NOT NULL'])) {

      // Esta lógica ahora funcionará correctamente
      $sql = "{$column} {$operator}";
    } else {

      $sql = "{$column} {$operator} ?";
      $bindings[] = $value;
    }

    $this->addClause($sql, $bindings, $boolean);
    return $this;
  }

  /**
   * Método auxiliar para construir y concatenar cláusulas WHERE.
   *
   * @param string $sql     El fragmento SQL de la condición.
   * @param array  $values  Los valores (bindings) para este fragmento.
   * @param string $boolean El conector ('AND' o 'OR').
   * @return void
   */
  protected function addClause($sql, array $values, $boolean)
  {

    if ($this->where) {

      $this->where .= " {$boolean} {$sql}";
    } else {

      $this->where = "WHERE {$sql}";
    }

    if (!empty($values)) {

      $this->values = array_merge($this->values, $values);
    }
  }

  /**
   * Obtiene la cadena SQL del WHERE construida hasta el momento.
   *
   * @return string
   */
  public function getWhere()
  {

    return $this->where;
  }

  /**
   * Obtiene todos los valores (bindings) para las cláusulas WHERE.
   *
   * @return array
   */
  public function getValues()
  {

    return $this->values;
  }

  /**
   * Agrega una cláusula HAVING a la consulta para filtrar después de un GROUP BY.
   *
   * @param string     $column La columna o función de agregación (ej. 'COUNT(id)').
   * @param string     $op     El operador de comparación (ej. '>', '=', '<=').
   * @param mixed|null $val    El valor a comparar. Si es nulo, $op se usa como valor y el operador es '='.
   * @return self
   */
  public function having($col, $op, $val = null)
  {

    if (is_null($val)) {

      $val = $op;
      $op = "=";
    }

    if ($this->having) {

      // Si ya existe una cláusula having, se concatena con AND
      $this->having .= " AND {$col} {$op} (?) ";
    } else {

      // Es la primera cláusula, por lo que se inicia con HAVING
      $this->having = " HAVING {$col} {$op} (?) ";
    }

    $this->values[] = $val;

    return $this;
  }

  /**
   * Agrega una cláusula ORDER BY a la consulta.
   *
   * @param string $col   La columna por la que ordenar.
   * @param string $order La dirección del orden ('ASC' o 'DESC').
   * @return self
   */
  public function order($col, $order = 'ASC')
  {

    // Validar identificadores y dirección para prevenir inyección SQL
    $this->validateIdentifier($col);
    $order = $this->validateOrderDirection($order);

    if ($this->order) {

      $this->order .= ", {$col} {$order}";
    } else {

      $this->order = "{$col} {$order}";
    }

    return $this;
  }

  /**
   * Agrega una cláusula LIMIT (y opcionalmente OFFSET) a la consulta.
   *
   * @param int|null $ini  El número de registros (LIMIT) o el inicio (OFFSET) si $cant está presente.
   * @param int|null $cant El número de registros (LIMIT) si $ini se usa como OFFSET.
   * @return self
   */
  public function limit($ini = null, $cant = null)
  {

    // Límite por defecto si no se proporciona nada
    if (!isset($ini)) {

      if (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') {

        $this->limit = " 100 OFFSET 0 ";
      } else {

        $this->limit = " 0, 100 ";
      }

      return $this;
    }

    if ($cant !== null) {

      // $ini es el desplazamiento (offset), $cant es el conteo de límite
      if (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') {

        $this->limit = " {$cant} OFFSET {$ini} ";
      } else {

        $this->limit = " {$ini},{$cant} ";
      }
    } else {

      // Solo se proporcionó $ini.
      // En el código existente: $ini se trata como 'conteo', pero se almacena como "0, $ini" (Offset 0, Límite $ini)
      if (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') {

        $this->limit = " {$ini} OFFSET 0 ";
      } else {

        $this->limit = " 0, {$ini} ";
      }
    }

    return $this;
  }

  /**
   * Agrega una cláusula LEFT JOIN a la consulta.
   *
   * @param string $second_table La segunda tabla para unir.
   * @param string $id1          La columna de la tabla principal (this->table) para la unión.
   * @param string $id2          La columna de la segunda tabla para la unión.
   * @return self
   */
  /**
   * Agrega una cláusula LEFT JOIN a la consulta.
   * Soporta sintaxis Legacy (3 args) y Smart (variadic args).
   *
   * @param string $table  La segunda tabla para unir.
   * @param mixed  ...$params Argumentos variables.
   * @return self
   */
  public function join($table, ...$params)
  {

    $this->validateIdentifier($table);
    $condition = $this->_buildJoinCondition($table, $params);

    if ($this->join) {
      $this->join .= " LEFT JOIN {$table} AS {$table} {$condition} ";
    } else {
      $this->join = " LEFT JOIN {$table} AS {$table} {$condition} ";
    }

    return $this;
  }

  /**
   * Agrega una cláusula LEFT JOIN a la consulta con especificación explícita de tablas.
   * Permite unir tablas intermedias sin depender de $this->table.
   *
   * Ejemplo de uso:
   * ->joinOn("categories", "blogpostcategories.category_id_cat", "categories.category_id")
   *
   * @param string $second_table La tabla a unir.
   * @param string $condition1   La primera columna con formato "tabla.columna".
   * @param string $condition2   La segunda columna con formato "tabla.columna".
   * @return self
   */
  /**
   * Agrega una cláusula LEFT JOIN a la consulta.
   * Soporta sintaxis Legacy (3 args) y Smart (variadic args).
   *
   * @param string $table  La segunda tabla para unir.
   * @param mixed  ...$params Argumentos variables.
   * @return self
   */
  public function joinOn($table, ...$params)
  {
    $this->validateIdentifier($table);
    
    // Logic specific for joinOn - usually explicit ON clauses
    // Check Legacy: joinOn($table, $cond1, $cond2) -> ON $cond1 = $cond2
    if (count($params) === 2 && !str_contains($params[0], '=') && !str_contains($params[1], '=')) {
        $this->validateIdentifier($params[0]);
        $this->validateIdentifier($params[1]);
        $condition = "ON {$params[0]} = {$params[1]}";
    } else {
        // Smart/Raw mode for joinOn
        $conditions = [];
        foreach ($params as $param) {
            // Apply dot-to-equals sugar: "t1.c1.t2.c2" -> "t1.c1 = t2.c2"
            // if param looks like identifier.identifier.identifier.identifier !!
            // User requested: joinOn("cat", "blogpostcategories.category_id_cat", "categories.category_id") => ON ... = ...
            // The smart loop below handles complex conditions joined by AND
             if (preg_match('/^([a-zA-Z0-9_]+\.[a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+\.[a-zA-Z0-9_]+)$/', $param, $matches)) {
                 $conditions[] = "{$matches[1]} = {$matches[2]}";
             } else {
                 $conditions[] = $param; 
             }
        }
        $condition = "ON " . implode(" AND ", $conditions);
    }

    $joinClause = " LEFT JOIN {$table} {$condition} ";

    if ($this->join) {
      $this->join .= $joinClause;
    } else {
      $this->join = $joinClause;
    }

    return $this;
  }

  /**
   * Agrega una cláusula FULL JOIN a la consulta.
   *
   * @param string $second_table La segunda tabla para unir.
   * @param string $id1          La columna de la tabla principal (this->table) para la unión.
   * @param string $id2          La columna de la segunda tabla para la unión.
   * @return self
   */
  /**
   * Agrega una cláusula FULL JOIN a la consulta.
   * Soporta sintaxis Legacy (3 args) y Smart (variadic args).
   *
   * @param string $table  La segunda tabla para unir.
   * @param mixed  ...$params Argumentos variables.
   * @return self
   */
  public function full_join($table, ...$params)
  {

    $this->validateIdentifier($table);
    $condition = $this->_buildJoinCondition($table, $params);

    if ($this->full_join) {
      $this->full_join .= " FULL JOIN {$table} AS {$table} {$condition} ";
    } else {
      $this->full_join = " FULL JOIN {$table} AS {$table} {$condition} ";
    }

    return $this;
  }

  /**
   * Crea un nuevo registro en la base de datos.
   * Sanitiza la entrada y opcionalmente genera un ID único.
   *
   * @param array       $datas   Un array asociativo (columna => valor) con los datos a insertar.
   * @param string|null $encoder (Opcional) El nombre de la columna donde se generará un ID único (hex).
   * @return string El ID del último registro insertado.
   */
  public function create($datas, $encoder = null)
  {

    //mantiene el formato de texto en la BD, codificando en caracteres especiales
    foreach ($datas as $key => $value) {

      if (gettype($value) == 'string') {

        if (empty(TAGS_VALID_TEXT_INPUT)) {

          $value = htmlentities($value, ENT_QUOTES, "UTF-8");
        } else {

          $value = strip_tags($value, TAGS_VALID_TEXT_INPUT);
        }
      } else {

        $value = $value;
      }

      $data_out[$key] = $value;

      if ($encoder !== null) {

        $unique_id = random_bytes(15);
        $unique_id = bin2hex($unique_id);
        $data_out[$encoder] = $unique_id;
      }
    }

    $data = array_values($data_out);
    $keys_data = array_keys($data_out);

    foreach ($data as $value) {

      $values[] = '?';
    }

    $key = implode(', ', $keys_data);
    $value = implode(', ', $values);
    $sql = "INSERT INTO {$this->table} ( {$key} ) VALUES ( {$value} )";
    $this->query($sql, $data);

    // Si generamos un ID único manualmente, retornarlo. Si no, usar lastInsertId.
    if ($encoder != null) {

      $id_rescue = $data_out[$encoder];
    } else {

      $id_rescue = $this->last_id;
    }

    return $id_rescue;
  }

  /**
   * Actualiza un registro en la base de datos.
   *
   * @param string      $col     La columna que sirve como identificador (ej. 'id').
   * @param mixed       $camp    El valor del identificador para la cláusula WHERE.
   * @param array       $datas   Un array asociativo (columna => valor) con los datos a actualizar.
   * @param string|null $encoder (Opcional) Si se proporciona, genera un nuevo ID único para esa columna.
   * @return void
   */
  public function update($col, $camp, $datas, $encoder = null)
  {

    //mantiene el formato de texto en la BD, codificando en caracteres especiales
    foreach ($datas as $key => $value) {

      if (gettype($value) == 'string') {

        if (empty(TAGS_VALID_TEXT_INPUT)) {

          $value = htmlentities($value, ENT_QUOTES, "UTF-8");
        } else {

          $value = strip_tags($value, TAGS_VALID_TEXT_INPUT);
        }
      } else {

        $value = $value;
      }

      $data_out[$key] = $value;

      if ($encoder !== null) {

        $unique_id = random_bytes(10);
        $unique_id = bin2hex($unique_id);
        $data_out[$encoder] = $unique_id;
      }
    }

    $data = array_values($data_out);
    $keys_data = array_keys($data_out);
    $count_datas = count($data);

    $bindings = [];

    for ($i = 0; $i < $count_datas; $i++) {

      $data_field[] = $keys_data[$i] . " = ?";
      $bindings[] = $data[$i];
    }

    $fields = implode(', ', $data_field);

    $sql = "UPDATE {$this->table} SET {$fields} WHERE {$col} = ?";
    $bindings[] = $camp;
    $this->query($sql, $bindings);
  }

  /**
   * Elimina uno o varios registros de la base de datos.
   *
   * @param string      $col  La columna de la BD para la cláusula WHERE.
   * @param mixed|array $camp El valor (si es único) o un array de valores a eliminar.
   * @return void
   */
  public function delete($col, $camp)
  {

    if (is_array($camp)) {

      foreach ($camp as $value) {

        $sql = "DELETE FROM {$this->table} WHERE {$col} = ?";
        $this->query($sql, [$value]);
      }

      return;
    } else {

      $sql = "DELETE FROM {$this->table} WHERE {$col} = ?";
      $this->query($sql, [$camp]);
      return;
    }
  }

  /**
   * Crea un nuevo registro de usuario, con encriptación de contraseña.
   *
   * @param array       $datas   Un array asociativo (columna => valor) con los datos.
   * @param string      $pass    (Obligatorio) El nombre de la columna de la contraseña para encriptar.
   * @param string|null $encoder (Opcional) El nombre de la columna donde se generará un ID único.
   * @return string El ID del último registro insertado.
   */
  public function register($datas, $pass, $encoder = null)
  {

    //mantiene el formato de texto en la BD, codificando en caracteres especiales
    foreach ($datas as $key => $value) {

      if (gettype($value) == 'string') {

        if (empty(TAGS_VALID_TEXT_INPUT)) {

          $value = htmlentities($value, ENT_QUOTES, "UTF-8");
        } else {

          $value = strip_tags($value, TAGS_VALID_TEXT_INPUT);
        }
      } else {

        $value = $value;
      }

      $data_out[$key] = $value;

      if ($encoder !== null) {

        $unique_id = random_bytes(15);
        $unique_id = bin2hex($unique_id);
        $data_out[$encoder] = $unique_id;
      }
    }

    $password = $data_out[$pass];
    $data_out[$pass] = password_hash($password, PASSWORD_DEFAULT);
    $data = array_values($data_out);
    $keys_data = array_keys($data_out);

    foreach ($data as $value) {

      $values[] = '?';
    }

    $key = implode(', ', $keys_data);
    $value = implode(', ', $values);
    $sql = "INSERT INTO {$this->table} ( {$key} ) VALUES ( {$value} )";
    $this->query($sql, $data);

    // Si generamos un ID único manualmente, retornarlo. Si no, usar lastInsertId.
    if ($encoder !== null && isset($data_out[$encoder])) {

      $id_rescue = $data_out[$encoder];
    } else {

      $id_rescue = $this->last_id;
    }

    return $id_rescue;
  }

  /**
   * Actualiza un registro de usuario, con re-encriptación de contraseña si se provee.
   *
   * @param string      $col     La columna que sirve como identificador (ej. 'id').
   * @param mixed       $camp    El valor del identificador para la cláusula WHERE.
   * @param array       $datas   Un array asociativo (columna => valor) con los datos a actualizar.
   * @param string      $pass    (Obligatorio) El nombre de la columna de la contraseña.
   * @param string|null $encoder (Opcional) Si se proporciona, genera un nuevo ID único para esa columna.
   * @return void
   */
  public function register_update($col, $camp, $datas, $pass, $encoder = null)
  {

    //mantiene el formato de texto en la BD, codificando en caracteres especiales
    foreach ($datas as $key => $value) {

      if (gettype($value) == 'string') {

        if (empty(TAGS_VALID_TEXT_INPUT)) {

          $value = htmlentities($value, ENT_QUOTES, "UTF-8");
        } else {

          $value = strip_tags($value, TAGS_VALID_TEXT_INPUT);
        }
      } else {

        $value = $value;
      }

      $data_out[$key] = $value;

      if ($encoder !== null) {

        $unique_id = random_bytes(15);
        $unique_id = bin2hex($unique_id);
        $data_out[$encoder] = $unique_id;
      }
    }

    $password = $data_out[$pass];
    $data_out[$pass] = password_hash($password, PASSWORD_DEFAULT);
    $data = array_values($data_out);
    $keys_data = array_keys($data_out);
    $count_datas = count($data);

    $bindings = [];

    for ($i = 0; $i < $count_datas; $i++) {

      $data_field[] = $keys_data[$i] . " = ?";
      $bindings[] = $data[$i];
    }

    $fields = implode(', ', $data_field);
    $sql = "UPDATE {$this->table} SET {$fields} WHERE {$col} = ?";
    $bindings[] = $camp;
    $this->query($sql, $bindings);
  }

  /**
   * Valida las credenciales de un usuario y crea una sesión si son correctas.
   *
   * @param string $col_pass La columna de la BD que almacena la contraseña.
   * @param string $pass     La contraseña proporcionada por el usuario (en texto plano).
   * @param mixed  $camp     El valor del identificador de usuario (ej. 'usuario@correo.com').
   * @param array  $cols     Un array de columnas donde buscar el $camp (ej. ['email', 'username']).
   * @param array  $noSelect Columnas a excluir de la sesión.
   * @return array Un array de estado: [bool $exito, int|string $error_o_info]
   * - [false, 0] -> No existe el usuario.
   * - [false, 1] -> La clave no corresponde.
   * - [true, 'encrypted' => true] -> Éxito (clave hasheada).
   * - [true, 'encrypted' => false] -> Éxito (clave en texto plano).
   */
  public function login($col_pass, $pass, $camp, $cols = [], $noSelect = [])
  {
    // Guardar configuración localmente antes de que consultas internas puedan resetear el estado
    $maxAttempts = $this->rateLimitMaxAttempts;
    $blockSeconds = $this->rateLimitBlockSeconds;

    // Límite de tasa para Login: usar la clave 'login:' seguido del username/email
    $actionKey = 'login:' . $camp;
    $rateStatus = $this->checkRateLimit($actionKey);
    if ($rateStatus !== true) {
      $this->reset();
      return [false, 'rate_limited', $rateStatus];
    }

    $no_data = false;

    foreach ($cols as $value) {

      $data[] = $this->find($value, $camp);
    }

    for ($i = 0; $i < count($data); $i++) {

      if (!empty($data[$i])) {

        $data = $data[$i];
        $no_data = true;
      }
    }

    if ($no_data) {

      $reg = $data[0];
    } else {

      // Restaurar rate limit antes de incrementar
      $this->rateLimitMaxAttempts = $maxAttempts;
      $this->rateLimitBlockSeconds = $blockSeconds;
      $this->incrementRateLimit($actionKey);
      $this->reset();
      return [false, 0]; // No existe el usuario

    }

    $verify_pass = password_verify($pass, $reg[$col_pass]);

    if (!$verify_pass) {

      if ($pass === $reg[$col_pass]) {

        // Coincide con texto plano (no encriptado)
        $this->rateLimitMaxAttempts = $maxAttempts;
        $this->rateLimitBlockSeconds = $blockSeconds;
        $this->clearRateLimit($actionKey);
        Session::create_user_session($reg, $noSelect);
        $this->reset();
        return [true, 'encrypted' => false];
      } else {

        // Restaurar rate limit antes de incrementar
        $this->rateLimitMaxAttempts = $maxAttempts;
        $this->rateLimitBlockSeconds = $blockSeconds;
        $this->incrementRateLimit($actionKey);
        $this->reset();
        return [false, 1]; // La clave no corresponde

      }
    } else {

      // Éxito con clave encriptada
      $this->rateLimitMaxAttempts = $maxAttempts;
      $this->rateLimitBlockSeconds = $blockSeconds;
      $this->clearRateLimit($actionKey);
      Session::create_user_session($reg, $noSelect);
      $this->reset();
      return [true, 'encrypted' => true];
    }
  }

  /**
   * Genera un conjunto de resultados paginados.
   * Utiliza count_reg() y limit() para construir la consulta paginada.
   *
   * @param int $cant     El número de registros por página.
   * @param int $inOffSet (Opcional) Número de registros iniciales a omitir del conjunto paginable.
   *                      Por ejemplo, si hay 4 posts y usas pag(2, 1), se omite el primer post
   *                      y se paginan los 3 restantes: página 1 muestra 2 posts, página 2 muestra 1 post.
   * @return array Un array con la estructura de paginación y los registros.
   */
  public function pag($cant, $inOffSet = 0)
  {

    $pag = isset($_GET['p']) ? (int)$_GET['p'] : 1;

    // Asegurar que inOffSet sea un entero no negativo
    $inOffSet = max(0, (int)$inOffSet);

    // Calcular desplazamiento (offset) incluyendo el offset inicial
    $offset = ($pag - 1) * $cant + $inOffSet;
    if ($offset < 0) $offset = 0;

    $result = $this
      ->count_reg()
      ->limit($offset, $cant)
      ->get_all();

    // Manejar el caso cuando get_all() retorna false (sin resultados)
    if ($result === false) {
      $result = [];
    }

    $cant_post_show = count($result);
    $totalReal = $result[0]['total_reg_in_table'] ?? 0;

    // Ajustar el total restándole el offset inicial (registros que se omiten)
    $total = max(0, $totalReal - $inOffSet);

    // Calcular total de páginas
    $cant_pag = ($total > 0) ? (int)ceil($total / $cant) : 1;

    // Limpiar base de URL
    $url = $_SERVER["REDIRECT_URL"] ?? $_SERVER["PHP_SELF"];

    // --- LÓGICA DE REDIRECCIÓN ---
    // Solo redirigir si el parámetro 'pag' está explícitamente presente en la URL
    if (isset($_GET['p'])) {

      $val = (int)$_GET['p'];

      // Caso 1: Menor que 1, o exactamente 1 (URL limpia), o 0
      if ($val <= 1) {

        header("Location: {$url}");
        exit();
      }

      // Caso 2: Mayor que el total de páginas (y el total de páginas es conocido)
      if ($val > $cant_pag && $cant_pag > 0) {

        header("Location: {$url}?p={$cant_pag}");
        exit();
      }
    }

    // ----------------------

    $current_pag = $pag;
    if ($current_pag < 1) $current_pag = 1;
    if ($current_pag > $cant_pag) $current_pag = $cant_pag;


    #borramos el registro ['total_reg_in_table'] del array result
    foreach ($result as $key => $value) {

      unset($result[$key]['total_reg_in_table']);
    }

    // Generar Enlaces
    // Previo: Si el actual es 2, el previo es 1 (URL limpia). Si el actual > 2, el previo es actual-1.
    $prev_pag = null;
    $prev_pag_number = 0;

    if ($current_pag > 1) {

      $prev_num = $current_pag - 1;
      $prev_pag_number = $prev_num;

      if ($prev_num == 1) {

        $prev_pag = $url;
      } else {

        $prev_pag = "{$url}?p={$prev_num}";
      }
    }

    // Siguiente: Estándar
    $next_pag = null;
    $next_pag_number = $cant_pag;

    if ($current_pag < $cant_pag) {

      $next_num = $current_pag + 1;
      $next_pag_number = $next_num; // Corrección: el siguiente número es lógica simple
      $next_pag = "{$url}?p={$next_num}";
    }

    return [
      'total_post' => $total,
      'post_per_page' => $cant,
      'cant_post_show' => $cant_post_show,
      'cant_pag' => $cant_pag,
      'current_pag' => (int)$current_pag,
      'prev_pag' => $prev_pag,
      'prev_pag_number' => $prev_pag_number,
      'next_pag' => $next_pag,
      'next_pag_number' => $next_pag_number,
      'uri_pag' => $url,
      'register' => $result,
    ];
  }

  // ============================================================================
  // NUEVOS MÉTODOS - CONDICIONES WHERE
  // ============================================================================

  /**
   * Agrega una condición WHERE con conector OR.
   *
   * @param string|Closure $col La columna o una función para agrupar.
   * @param mixed|null     $op  El operador o el valor.
   * @param mixed|null     $val El valor.
   * @return self
   */
  public function orWhere($col, $op = null, $val = null)
  {

    return $this->where($col, $op, $val, 'OR');
  }

  /**
   * Agrega una condición WHERE column IS NULL.
   *
   * @param string $col La columna.
   * @return self
   */
  public function whereNull($col)
  {

    return $this->where($col, 'IS NULL');
  }

  /**
   * Agrega una condición WHERE column IS NOT NULL.
   *
   * @param string $col La columna.
   * @return self
   */
  public function whereNotNull($col)
  {

    return $this->where($col, 'IS NOT NULL');
  }

  /**
   * Agrega una condición WHERE column BETWEEN value1 AND value2.
   *
   * @param string $col    La columna.
   * @param array  $values Array con dos valores [min, max].
   * @return self
   */
  public function whereBetween($col, array $values)
  {

    return $this->where($col, 'BETWEEN', $values);
  }

  /**
   * Agrega una condición WHERE column NOT BETWEEN value1 AND value2.
   *
   * @param string $col    La columna.
   * @param array  $values Array con dos valores [min, max].
   * @return self
   */
  public function whereNotBetween($col, array $values)
  {

    return $this->where($col, 'NOT BETWEEN', $values);
  }

  /**
   * Agrega una condición WHERE column IN (...).
   *
   * @param string $col    La columna.
   * @param array  $values Array de valores.
   * @return self
   */
  public function whereIn($col, array $values)
  {

    return $this->where($col, 'IN', $values);
  }

  /**
   * Agrega una condición WHERE column NOT IN (...).
   *
   * @param string $col    La columna.
   * @param array  $values Array de valores.
   * @return self
   */
  public function whereNotIn($col, array $values)
  {

    return $this->where($col, 'NOT IN', $values);
  }

  /**
   * Agrega una condición WHERE con SQL crudo.
   *
   * @param string $sql      El fragmento SQL crudo.
   * @param array  $bindings Los valores para los placeholders.
   * @return self
   */
  public function whereRaw($sql, array $bindings = [])
  {

    $this->addClause($sql, $bindings, 'AND');
    return $this;
  }

  /**
   * Agrega una condición OR WHERE con SQL crudo.
   *
   * @param string $sql      El fragmento SQL crudo.
   * @param array  $bindings Los valores para los placeholders.
   * @return self
   */
  public function orWhereRaw($sql, array $bindings = [])
  {

    $this->addClause($sql, $bindings, 'OR');
    return $this;
  }

  // ============================================================================
  // NUEVOS MÉTODOS - JOINs ADICIONALES
  // ============================================================================

  /**
   * Agrega una cláusula RIGHT JOIN a la consulta.
   *
   * @param string $second_table La segunda tabla para unir.
   * @param string $id1          La columna de la tabla principal.
   * @param string $id2          La columna de la segunda tabla.
   * @return self
   */
  /**
   * Agrega una cláusula RIGHT JOIN a la consulta.
   * Soporta sintaxis Legacy (3 args) y Smart (variadic args).
   *
   * @param string $table  La segunda tabla para unir.
   * @param mixed  ...$params Argumentos variables.
   * @return self
   */
  public function rightJoin($table, ...$params)
  {

    $this->validateIdentifier($table);
    $condition = $this->_buildJoinCondition($table, $params);

    if ($this->join) {
      $this->join .= " RIGHT JOIN {$table} AS {$table} {$condition} ";
    } else {
      $this->join = " RIGHT JOIN {$table} AS {$table} {$condition} ";
    }

    return $this;
  }

  /**
   * Agrega una cláusula INNER JOIN a la consulta.
   *
   * @param string $second_table La segunda tabla para unir.
   * @param string $id1          La columna de la tabla principal.
   * @param string $id2          La columna de la segunda tabla.
   * @return self
   */
  /**
   * Agrega una cláusula INNER JOIN a la consulta.
   * Soporta sintaxis Legacy (3 args) y Smart (variadic args).
   *
   * @param string $table  La segunda tabla para unir.
   * @param mixed  ...$params Argumentos variables.
   * @return self
   */
  public function innerJoin($table, ...$params)
  {

    $this->validateIdentifier($table);
    $condition = $this->_buildJoinCondition($table, $params);

    if ($this->join) {
      $this->join .= " INNER JOIN {$table} AS {$table} {$condition} ";
    } else {
      $this->join = " INNER JOIN {$table} AS {$table} {$condition} ";
    }

    return $this;
  }

  /**
   * Construye la condición ON para los Joins.
   *
   * @param string $table  La tabla a unir.
   * @param array  $params Argumentos de condición.
   * @return string Cláusula ON completa (ej. "ON t1.id = t2.id AND ...").
   */
  protected function _buildJoinCondition($table, $params)
  {
      // Legacy Check: Si hay exactamente 2 parámetros y son identificadores simples (sin puntos ni iguales)
      // Asume: join('table', 'col1', 'col2') -> ON this.col1 = table.col2
      if (count($params) === 2 && !str_contains($params[0], '.') && !str_contains($params[0], '=') && !str_contains($params[1], '=')) {
          $this->validateIdentifier($params[0]);
          $this->validateIdentifier($params[1]);
          return "ON {$this->table}.{$params[0]} = {$table}.{$params[1]}";
      }

      // Smart Syntax
      $conditions = [];
      foreach ($params as $param) {
          
          // Case 1: Literal String Comparison (e.g. "col.'value'" or "col.'value'")
          // Matches: identifier . 'value' OR identifier . "value"
          if (preg_match('/^([a-zA-Z0-9_]+)\.(["\'].+["\'])$/', $param, $matches)) {
              // Assume column belongs to the JOINED table ($table) when comparing to a literal
              $conditions[] = "{$table}.{$matches[1]} = {$matches[2]}";
          }
          // Case 2: Literal Number Comparison (e.g. "col.123")
          // Matches: identifier . digits
          elseif (preg_match('/^([a-zA-Z0-9_]+)\.([0-9]+)$/', $param, $matches)) {
               // Assume column belongs to the JOINED table
               $conditions[] = "{$table}.{$matches[1]} = {$matches[2]}";
          }
          // Case 3: Column to Column Relation (e.g. "col1.col2")
          // Matches: identifier . identifier
          elseif (preg_match('/^([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)$/', $param, $matches)) {
              // Assume first col is THIS table, second col is JOINED table
              $conditions[] = "{$this->table}.{$matches[1]} = {$table}.{$matches[2]}";
          } 
          // Case 4: Raw Condition
          else {
              $conditions[] = $param;
          }
      }

      return "ON " . implode(" AND ", $conditions);
  }

  // ============================================================================
  // NUEVOS MÉTODOS - FUNCIONES DE AGREGACIÓN
  // ============================================================================

  /**
   * Agrega una función SUM() a la consulta.
   *
   * @param string      $col   La columna a sumar.
   * @param string|null $alias Un alias opcional.
   * @return self
   */
  public function sum($col, $alias = null)
  {

    $alias = $alias ?? 'sum_' . $col;
    $this->validateIdentifier($col);

    if ($this->count) {

      $this->count .= " ,SUM({$col}) AS {$alias} ";
    } else {

      $this->count = " ,SUM({$col}) AS {$alias} ";
    }

    return $this;
  }

  /**
   * Agrega una función AVG() a la consulta.
   *
   * @param string      $col   La columna a promediar.
   * @param string|null $alias Un alias opcional.
   * @return self
   */
  public function avg($col, $alias = null)
  {

    $alias = $alias ?? 'avg_' . $col;
    $this->validateIdentifier($col);

    if ($this->count) {

      $this->count .= " ,AVG({$col}) AS {$alias} ";
    } else {

      $this->count = " ,AVG({$col}) AS {$alias} ";
    }

    return $this;
  }

  /**
   * Agrega una función MIN() a la consulta.
   *
   * @param string      $col   La columna.
   * @param string|null $alias Un alias opcional.
   * @return self
   */
  public function min($col, $alias = null)
  {

    $alias = $alias ?? 'min_' . $col;
    $this->validateIdentifier($col);

    if ($this->count) {

      $this->count .= " ,MIN({$col}) AS {$alias} ";
    } else {

      $this->count = " ,MIN({$col}) AS {$alias} ";
    }

    return $this;
  }

  /**
   * Agrega una función MAX() a la consulta.
   *
   * @param string      $col   La columna.
   * @param string|null $alias Un alias opcional.
   * @return self
   */
  public function max($col, $alias = null)
  {

    $alias = $alias ?? 'max_' . $col;
    $this->validateIdentifier($col);

    if ($this->count) {

      $this->count .= " ,MAX({$col}) AS {$alias} ";
    } else {

      $this->count = " ,MAX({$col}) AS {$alias} ";
    }

    return $this;
  }

  /**
   * Hace que el SELECT sea DISTINCT.
   *
   * @return self
   */
  public function distinct()
  {

    if (strpos($this->select, 'DISTINCT') === false) {

      $this->select = "DISTINCT {$this->select}";
    }

    return $this;
  }

  // ============================================================================
  // NUEVOS MÉTODOS - UTILIDADES
  // ============================================================================

  /**
   * Agrega una expresión SQL cruda al SELECT.
   *
   * @param string $expression La expresión SQL.
   * @return self
   */
  public function selectRaw($expression)
  {

    if ($this->select === '*') {

      $this->select = $expression;
    } else {

      $this->select .= ", {$expression}";
    }

    return $this;
  }

  /**
   * Obtiene el primer registro de la consulta.
   *
   * @return array|false El primer registro o false si no hay resultados.
   */
  public function first()
  {

    return $this->limit(1)->get_one();
  }

  /**
   * Verifica si existen registros que coincidan con la consulta.
   *
   * @return bool True si existen registros, false si no.
   */
  public function exists()
  {

    $result = $this->limit(1)->get_one();

    return $result !== false;
  }

  /**
   * Obtiene los valores de una sola columna como un array plano.
   *
   * @param string $col La columna a extraer.
   * @return array|false Array de valores de la columna, o false si no hay resultados.
   */
  public function pluck($col)
  {

    $this->validateIdentifier($col);
    $results = $this->select($col)->get_all();

    if ($results === false) {
      return false;
    }

    return array_column($results, $col);
  }

  /**
   * Obtiene el SQL que se ejecutaría sin ejecutarlo (para debugging).
   *
   * @return array Array con 'sql' y 'bindings'.
   */
  public function toSql()
  {

    $sql = "SELECT {$this->select} {$this->concat} {$this->count} {$this->total_reg} FROM {$this->table} AS {$this->table}";

    if ($this->join) {

      $sql .= " {$this->join}";
    }

    if ($this->full_join) {

      $sql .= " {$this->full_join} ";
    }

    if ($this->where) {

      $sql .= " {$this->where}";
    }

    if ($this->group) {

      $sql .= " GROUP BY {$this->group}";
    }

    if ($this->having) {

      $sql .= " {$this->having}";
    }

    if ($this->order) {

      $sql .= " ORDER BY {$this->order}";
    }

    if ($this->limit) {

      $sql .= " LIMIT {$this->limit}";
    }

    return [
      'sql' => $sql,
      'bindings' => $this->values
    ];
  }

  /**
   * Incrementa el valor de una columna.
   *
   * @param string $col    La columna a incrementar.
   * @param int    $amount La cantidad a incrementar (por defecto 1).
   * @return void
   */
  public function increment($col, $amount = 1)
  {

    $this->validateIdentifier($col);
    $amount = (int)$amount;

    $sql = "UPDATE {$this->table} SET {$col} = {$col} + ?";

    if ($this->where) {

      $sql .= " {$this->where}";
    }

    $bindings = array_merge([$amount], $this->values);
    $this->query($sql, $bindings);
  }

  /**
   * Decrementa el valor de una columna.
   *
   * @param string $col    La columna a decrementar.
   * @param int    $amount La cantidad a decrementar (por defecto 1).
   * @return void
   */
  public function decrement($col, $amount = 1)
  {

    $this->validateIdentifier($col);
    $amount = (int)$amount;

    $sql = "UPDATE {$this->table} SET {$col} = {$col} - ?";

    if ($this->where) {

      $sql .= " {$this->where}";
    }

    $bindings = array_merge([$amount], $this->values);
    $this->query($sql, $bindings);
  }

  /**
   * Vacía la tabla (TRUNCATE).
   * ¡CUIDADO! Esta operación elimina todos los registros.
   *
   * @return void
   */
  public function truncate()
  {

    if (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') {

      $sql = "TRUNCATE TABLE {$this->table} RESTART IDENTITY";
    } else {

      $sql = "TRUNCATE TABLE {$this->table}";
    }

    $this->query($sql);
  }

  // ============================================================================
  // NUEVOS MÉTODOS - TRANSACCIONES
  // ============================================================================

  /**
   * Inicia una transacción de base de datos.
   *
   * @return bool
   */
  public function beginTransaction()
  {

    return Conexion::pdo_conexion()->beginTransaction();
  }

  /**
   * Confirma la transacción actual.
   *
   * @return bool
   */
  public function commit()
  {

    return Conexion::pdo_conexion()->commit();
  }

  /**
   * Revierte la transacción actual.
   *
   * @return bool
   */
  public function rollback()
  {

    return Conexion::pdo_conexion()->rollBack();
  }

  /**
   * Ejecuta un callback dentro de una transacción.
   * Si el callback lanza una excepción, se hace rollback automáticamente.
   *
   * @param callable $callback El callback a ejecutar.
   * @return mixed El resultado del callback.
   * @throws \Exception Si ocurre un error durante la transacción.
   */
  public function transaction(callable $callback)
  {

    $this->beginTransaction();

    try {

      $result = $callback($this);
      $this->commit();

      return $result;
    } catch (\Exception $e) {

      $this->rollback();
      throw $e;
    }
  }

  /**
   * Reinicia el estado del builder para una nueva consulta.
   *
   * @return self
   */
  public function reset()
  {

    $this->sql = null;
    $this->query = null;
    $this->select = '*';
    $this->count = '';
    $this->order = '';
    $this->limit = '';
    $this->concat = null;
    $this->where = '';
    $this->join = null;
    $this->full_join = null;
    $this->group = null;
    $this->having = '';
    $this->where_not_in = null;
    $this->values = [];
    $this->query_error = null;
    $this->rateLimitMaxAttempts = null;
    $this->rateLimitBlockSeconds = 60;

    return $this;
  }
}
