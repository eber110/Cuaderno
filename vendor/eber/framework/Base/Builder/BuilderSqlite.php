<?php

namespace Base\Builder;

use PDO;

/**
 * Clase BuilderSqlite para la construcción y ejecución de consultas SQL adaptadas a SQLite.
 * Hereda todos los métodos fluidos de Builder y sobrescribe aquellos específicos del motor SQLite.
 */
class BuilderSqlite extends Builder
{
  /**
   * Constructor de la clase BuilderSqlite.
   *
   * @param string|null $table El nombre de la tabla principal para la consulta.
   */
  public function __construct($table = null)
  {
    parent::__construct($table);
  }

  /**
   * Verifica si una tabla específica existe en la base de datos SQLite.
   *
   * @param string $table El nombre de la tabla a verificar.
   * @return bool True si la tabla existe, False si no.
   */
  public function table_exist($table): bool
  {
    $table = trim((string)$table);
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
      throw new \InvalidArgumentException("Identificador de tabla SQL inválido: {$table}");
    }
    $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name = ?";
    $query = $this->query_foreign($sql, [$table])->get_all();

    return !empty($query[0]);
  }

  /**
   * Vacía la tabla en SQLite (DELETE FROM table) y reinicia el contador AUTOINCREMENT.
   *
   * @return void
   */
  public function truncate()
  {
    $table = $this->validateTableIdentifier((string)$this->table);
    $sql = "DELETE FROM {$table}";
    $this->query_foreign($sql);

    if ($this->table_exist("sqlite_sequence")) {
      $this->query_foreign("DELETE FROM sqlite_sequence WHERE name = ?", [$table]);
    }
  }

  /**
   * Agrega una función de agregación GROUP_CONCAT() adaptada a la sintaxis de SQLite.
   *
   * @param string      $col   La columna a concatenar.
   * @param string      $sep   El separador a usar.
   * @param string|null $alias Un alias opcional para el resultado.
   * @return self
   */
  public function group_concat($col, $sep = "','", $alias = null)
  {
    if (is_null($alias) && $sep !== "','") {
      $alias = $sep;
      $sep = "','";
    }

    $col = $this->validateSingleIdentifier((string)$col);
    $alias = $alias !== null ? $this->validateAlias((string)$alias) : ('group_' . str_replace('.', '_', $col));
    $safeSep = $this->quoteSeparatorLiteral((string)$sep);

    $fragment = " ,GROUP_CONCAT({$col}, {$safeSep}) AS {$alias} ";

    if ($this->concat) {
      $this->concat .= $fragment;
    } else {
      $this->concat = $fragment;
    }

    return $this;
  }

  /**
   * Agrega una cláusula LIMIT y OFFSET adaptada a SQLite (LIMIT cant OFFSET offset).
   *
   * @param int|null $ini  El desplazamiento (OFFSET) o límite si $cant es nulo.
   * @param int|null $cant El número de registros (LIMIT).
   * @return self
   */
  public function limit($ini = null, $cant = null)
  {
    if (!isset($ini)) {
      $this->limit = " 100 OFFSET 0 ";
      return $this;
    }

    if (!is_numeric($ini) || ($cant !== null && !is_numeric($cant))) {
      throw new \InvalidArgumentException("Parámetros LIMIT/OFFSET deben ser numéricos.");
    }

    if ($cant !== null) {
      $offsetVal = max(0, (int)$ini);
      $limitVal = max(0, (int)$cant);
      $this->limit = " {$limitVal} OFFSET {$offsetVal} ";
    } else {
      $limitVal = max(0, (int)$ini);
      $this->limit = " {$limitVal} OFFSET 0 ";
    }

    return $this;
  }

  /**
   * Especifica las columnas que NO debe recuperar la consulta (introspección con LIMIT 1 OFFSET 0 en SQLite).
   *
   * @param string ...$camp Lista de columnas a excluir.
   * @return self
   */
  public function non_select(...$camp)
  {
    $table = $this->validateTableIdentifier((string)$this->table);
    $excluded = [];
    foreach ($camp as $c) {
      $excluded[] = $this->validateSingleIdentifier((string)$c);
    }

    $joinClause = $this->join ? " {$this->join}" : '';
    $sql = "SELECT * FROM {$table}{$joinClause} LIMIT 1 OFFSET 0";
    $pdo = $this->pdo_conexion();
    if ($pdo === null) {
      $lastError = class_exists(\Core\Conexion::class) ? \Core\Conexion::getLastError() : null;
      $detail = !empty($lastError) ? ": {$lastError}" : " (verifique la configuración de BD en .env)";
      throw new \PDOException("No hay conexión activa a la base de datos{$detail}");
    }
    $consul = $pdo->query($sql);
    $data = $consul ? $consul->fetch(PDO::FETCH_ASSOC) : false;

    if ($data) {
      $cols = [];
      foreach (array_keys($data) as $colName) {
        if (!in_array($colName, $excluded, true)) {
          $cols[] = $this->validateSingleIdentifier((string)$colName);
        }
      }
      if (!empty($cols)) {
        $this->select = ' ' . implode(', ', $cols) . ' ';
      }
    }

    return $this;
  }
}
