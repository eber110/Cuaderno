<?php

namespace Base\Studio\Controllers;

use Base\Control\Control;
use Base\Studio\Services\ConfigCompilerService;
use Base\Studio\Services\ConfigSchema;
use Base\Studio\Services\EnvConfigService;
use Base\Studio\StudioKernel;

/**
 * Controlador de Frame Studio para gestión visual de .env, constantes seguras y compilación al Vault.
 */
class ConfigController extends Control
{
  /**
   * Devuelve esquema de configuración, ambos perfiles (local/production) y estado del Vault.
   */
  public function state(): void
  {
    if (!StudioKernel::guardRequest($this, false)) {
      return;
    }

    $basePath = defined('ROOT_PATH') ? ROOT_PATH : getcwd();
    $this->json([
      'success' => true,
      'sections' => ConfigSchema::getSections(),
      'fields' => ConfigSchema::getFields(),
      'profiles' => [
        'local' => EnvConfigService::readProfile('local', $basePath),
        'production' => EnvConfigService::readProfile('production', $basePath),
      ],
      'status' => ConfigCompilerService::getStatus($basePath),
    ]);
  }

  /**
   * Guarda un perfil ('local' o 'production') y opcionalmente compila para producción.
   */
  public function save(): void
  {
    if (!StudioKernel::guardRequest($this, true)) {
      return;
    }

    $data = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($data)) {
      $this->json(['success' => false, 'message' => 'Payload JSON inválido.'], 400);
      return;
    }

    $profile = (($data['profile'] ?? 'local') === 'production') ? 'production' : 'local';
    $values = is_array($data['values'] ?? null) ? $data['values'] : [];
    $compileAfter = !empty($data['compile']);

    $basePath = defined('ROOT_PATH') ? ROOT_PATH : getcwd();
    $result = EnvConfigService::saveProfile($profile, $values, $basePath);
    if (empty($result['success'])) {
      $this->json($result, 422);
      return;
    }

    if ($compileAfter && $profile === 'production') {
      $result['compile'] = ConfigCompilerService::compile($basePath);
    }
    $result['status'] = ConfigCompilerService::getStatus($basePath);

    $this->json($result);
  }

  /**
   * Genera un SEED o DEPLOY_TOKEN criptográfico seguro.
   */
  public function generateKey(): void
  {
    if (!StudioKernel::guardRequest($this, true)) {
      return;
    }

    $data = json_decode((string)file_get_contents('php://input'), true);
    $type = strtolower((string)($data['type'] ?? 'seed'));
    $key = EnvConfigService::generateCryptoKey($type);

    $this->json([
      'success' => true,
      'type' => $type,
      'key' => $key,
    ]);
  }

  /**
   * Prueba la conexión PDO con los parámetros enviados desde Studio.
   */
  public function testDatabase(): void
  {
    if (!StudioKernel::guardRequest($this, true)) {
      return;
    }

    $data = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($data)) {
      $this->json(['success' => false, 'message' => 'Payload JSON inválido.'], 400);
      return;
    }

    $basePath = defined('ROOT_PATH') ? ROOT_PATH : getcwd();
    $res = EnvConfigService::testDatabaseConnection($data, $basePath);
    $this->json($res, !empty($res['success']) ? 200 : 422);
  }

  /**
   * Compila compiled.config.php y secrets.vault.php para producción.
   */
  public function compileProduction(): void
  {
    if (!StudioKernel::guardRequest($this, true)) {
      return;
    }

    $data = json_decode((string)file_get_contents('php://input'), true);
    $override = is_array($data['values'] ?? null) ? $data['values'] : null;
    $rotateKey = !empty($data['rotateKey']);

    $basePath = defined('ROOT_PATH') ? ROOT_PATH : getcwd();
    $res = ConfigCompilerService::compile($basePath, $override, $rotateKey);
    $res['status'] = ConfigCompilerService::getStatus($basePath);

    $this->json($res, !empty($res['success']) ? 200 : 422);
  }
}
