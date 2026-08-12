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
    $this->validateIdentifier($table);
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
    $sql = "DELETE FROM {$this->table}";
    $this->query_foreign($sql);

    if ($this->table_exist("sqlite_sequence")) {
      $this->query_foreign("DELETE FROM sqlite_sequence WHERE name = ?", [$this->table]);
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

    $fragment = " ,GROUP_CONCAT({$col}, {$sep}) AS {$alias} ";

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

    if ($cant !== null) {
      $this->limit = " {$cant} OFFSET {$ini} ";
    } else {
      $this->limit = " {$ini} OFFSET 0 ";
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
    $sql = "SELECT * FROM {$this->table} {$this->join} LIMIT 1 OFFSET 0";
    $consul = $this->pdo_conexion()->query($sql);
    $data = $consul ? $consul->fetch(PDO::FETCH_ASSOC) : false;

    if ($data) {
      $data = array_keys($data);
      $data = array_diff($data, $camp);
      $data = implode(', ', $data);
      $this->select = " {$data} ";
    }

    return $this;
  }
}
