<?php

namespace App\Models;

use Base\Builder\BuilderSqlite;

/**
 * Modelo CuadernoModel para la gestión de notas utilizando el motor SQLite.
 */
class CuadernoModel
{
  private BuilderSqlite $db;

  public function __construct()
  {
    $this->db = new BuilderSqlite('notas');
    $this->initTable();
  }

  /**
   * Crea la estructura de la tabla 'notas' en SQLite si no existe aún.
   *
   * @return void
   */
  private function initTable(): void
  {
    if (!$this->db->table_exist('notas')) {
      $sql = "CREATE TABLE notas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        contenido TEXT NOT NULL,
        categoria TEXT DEFAULT 'General',
        color TEXT DEFAULT '#3b82f6',
        is_favorite INTEGER DEFAULT 0,
        is_pinned INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )";
      $this->db->query_foreign($sql);
    }
  }

  /**
   * Obtiene todas las notas con filtros opcionales de categoría y búsqueda por palabra clave.
   *
   * @param string|null $categoria Categoría para filtrar.
   * @param string|null $busqueda  Término de búsqueda.
   * @return array Listado de notas.
   */
  public function obtenerTodas(?string $categoria = null, ?string $busqueda = null): array
  {
    $this->db->reset();

    if (!empty($categoria) && $categoria !== 'todas') {
      if ($categoria === 'favoritas') {
        $this->db->where('is_favorite', 1);
      } else {
        $this->db->where('categoria', $categoria);
      }
    }

    if (!empty($busqueda)) {
      $this->db->where(function ($query) use ($busqueda) {
        $query->where('titulo', 'LIKE', "%{$busqueda}%")
              ->orWhere('contenido', 'LIKE', "%{$busqueda}%");
      });
    }

    // Ordenar primero las fijadas arriba, luego por fecha descendente
    $notas = $this->db->order('is_pinned', 'DESC')->order('id', 'DESC')->get_all();

    return $notas ?: [];
  }

  /**
   * Obtiene métricas y conteos de las notas.
   *
   * @return array Estadísticas de notas (total, favoritas, fijadas, categorías).
   */
  public function obtenerEstadisticas(): array
  {
    $todas = (new BuilderSqlite('notas'))->get_all() ?: [];
    
    $total = count($todas);
    $favoritas = 0;
    $fijadas = 0;
    $categorias = [];

    foreach ($todas as $nota) {
      if (!empty($nota['is_favorite'])) $favoritas++;
      if (!empty($nota['is_pinned'])) $fijadas++;
      
      $cat = $nota['categoria'] ?? 'General';
      $categorias[$cat] = ($categorias[$cat] ?? 0) + 1;
    }

    return [
      'total' => $total,
      'favoritas' => $favoritas,
      'fijadas' => $fijadas,
      'categorias' => $categorias
    ];
  }

  /**
   * Crea una nueva nota en la base de datos.
   *
   * @param array $datos Datos de la nota (titulo, contenido, categoria, color).
   * @return string|false ID de la nota creada o false.
   */
  public function crearNota(array $datos)
  {
    $data = [
      'titulo' => trim($datos['titulo'] ?? ''),
      'contenido' => trim($datos['contenido'] ?? ''),
      'categoria' => !empty($datos['categoria']) ? trim($datos['categoria']) : 'General',
      'color' => $datos['color'] ?? '#3b82f6',
      'is_favorite' => 0,
      'is_pinned' => 0,
      'created_at' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s')
    ];

    return $this->db->create($data);
  }

  /**
   * Alterna el estado de favorita de una nota.
   *
   * @param int $id ID de la nota.
   * @return void
   */
  public function toggleFavorite(int $id): void
  {
    $nota = (new BuilderSqlite('notas'))->where('id', $id)->get_one();
    if ($nota && !empty($nota[0])) {
      $nuevoEstado = $nota[0]['is_favorite'] == 1 ? 0 : 1;
      (new BuilderSqlite('notas'))->update('id', $id, [
        'is_favorite' => $nuevoEstado,
        'updated_at' => date('Y-m-d H:i:s')
      ]);
    }
  }

  /**
   * Alterna el estado de fijada (pin) de una nota.
   *
   * @param int $id ID de la nota.
   * @return void
   */
  public function togglePin(int $id): void
  {
    $nota = (new BuilderSqlite('notas'))->where('id', $id)->get_one();
    if ($nota && !empty($nota[0])) {
      $nuevoEstado = $nota[0]['is_pinned'] == 1 ? 0 : 1;
      (new BuilderSqlite('notas'))->update('id', $id, [
        'is_pinned' => $nuevoEstado,
        'updated_at' => date('Y-m-d H:i:s')
      ]);
    }
  }

  /**
   * Elimina una nota por su ID.
   *
   * @param int $id ID de la nota a eliminar.
   * @return void
   */
  public function eliminarNota(int $id): void
  {
    $this->db->delete('id', $id);
  }
}
