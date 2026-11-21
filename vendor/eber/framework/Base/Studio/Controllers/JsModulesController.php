<?php

namespace Base\Studio\Controllers;

use Base\Control\Control;
use Base\Studio\Services\JsRegistryService;
use Base\Studio\StudioKernel;

/**
 * Controlador de Frame Studio para la gestión visual de módulos JS y librerías.
 */
class JsModulesController extends Control
{
  /**
   * Devuelve el catálogo de módulos JS, librerías y auto-detección inicial de uso.
   */
  public function catalog(): void
  {
    if (!StudioKernel::guardRequest($this, false)) {
      return;
    }

    $basePath = defined('ROOT_PATH') ? ROOT_PATH : getcwd();
    $catalog = JsRegistryService::getCatalog($basePath);
    $usage = JsRegistryService::detectUsage($basePath);

    $this->json([
      'success' => true,
      'catalog' => $catalog,
      'usage' => $usage,
    ]);
  }

  /**
   * Re-escanea vistas y segmentos del proyecto para detectar módulos y librerías usadas.
   */
  public function detect(): void
  {
    if (!StudioKernel::guardRequest($this, true)) {
      return;
    }

    $basePath = defined('ROOT_PATH') ? ROOT_PATH : getcwd();
    $usage = JsRegistryService::detectUsage($basePath);

    $this->json([
      'success' => true,
      'usage' => $usage,
    ]);
  }

  /**
   * Persiste jsConfig.json y loadLibraryJsConfiguration.php y recompila js.min.js.
   */
  public function save(): void
  {
    if (!StudioKernel::guardRequest($this, true)) {
      return;
    }

    $raw = file_get_contents('php://input');
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) {
      $this->json(['success' => false, 'message' => 'Payload JSON inválido.'], 400);
      return;
    }

    $basePath = defined('ROOT_PATH') ? ROOT_PATH : getcwd();
    $result = JsRegistryService::saveConfig($data, $basePath);
    $status = !empty($result['success']) ? 200 : 500;
    $this->json($result, $status);
  }
}
