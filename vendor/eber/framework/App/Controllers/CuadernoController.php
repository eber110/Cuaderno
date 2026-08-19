<?php

namespace App\Controllers;

use Base\Control\Control;
use App\Models\CuadernoModel;
use Base\Module\HttpPostModule;

/**
 * Controlador CuadernoController para gestionar las notas del usuario.
 */
class CuadernoController extends Control
{
  private CuadernoModel $model;

  public function __construct()
  {
    $this->model = new CuadernoModel();
  }

  /**
   * Renderiza la página principal del cuaderno de notas.
   */
  public function index()
  {
    $categoria = $_GET['cat'] ?? null;
    $busqueda = $_GET['q'] ?? null;

    $notas = $this->model->obtenerTodas($categoria, $busqueda);
    $stats = $this->model->obtenerEstadisticas();

    return $this->view('cuaderno.index', [
      'title' => 'Mi Cuaderno Inteligente',
      'notas' => $notas,
      'stats' => $stats,
      'categoriaActual' => $categoria ?? 'todas',
      'busquedaActual' => $busqueda ?? ''
    ]);
  }

  /**
   * Procesa la creación de una nueva nota.
   */
  public function guardar()
  {
    $post = HttpPostModule::getPost();

    if (!empty($post['titulo']) && !empty($post['contenido'])) {
      $this->model->crearNota([
        'titulo' => $post['titulo'],
        'contenido' => $post['contenido'],
        'categoria' => $post['categoria'] ?? 'General',
        'color' => $post['color'] ?? '#3b82f6'
      ]);
    }

    header('Location: /cuaderno');
    exit();
  }

  /**
   * Cambia el estado de favorita de una nota.
   *
   * @param int|string $id ID de la nota.
   */
  public function toggleFavorite($id)
  {
    $this->model->toggleFavorite((int)$id);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/cuaderno'));
    exit();
  }

  /**
   * Fijar / desfijar una nota.
   *
   * @param int|string $id ID de la nota.
   */
  public function togglePin($id)
  {
    $this->model->togglePin((int)$id);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/cuaderno'));
    exit();
  }

  /**
   * Elimina una nota por su ID.
   *
   * @param int|string $id ID de la nota.
   */
  public function eliminar($id)
  {
    $this->model->eliminarNota((int)$id);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/cuaderno'));
    exit();
  }
}
