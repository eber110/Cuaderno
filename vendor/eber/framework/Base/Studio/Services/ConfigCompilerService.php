<?php

namespace Base\Studio\Services;

use Base\Module\Security\ConfigVault;

/**
 * Compilador de configuración de producción y generador del Vault AEAD autocontenido en public_html.
 */
class ConfigCompilerService
{
  private static function resolveBasePath(?string $basePath): string
  {
    $raw = $basePath ?: (defined('ROOT_PATH') ? ROOT_PATH : getcwd());
    return rtrim(str_replace('\\', '/', (string)$raw), '/');
  }

  /**
   * Garantiza la existencia de archivos .gitignore en App/Config/ y App/Safety/.
   */
  public static function ensureSensitiveGitignores(string $basePath): void
  {
    $root = self::resolveBasePath($basePath);
    $configDir = $root . '/App/Config';
    $safetyDir = $root . '/App/Safety';

    if (!is_dir($configDir)) {
      @mkdir($configDir, 0755, true);
    }
    if (!is_dir($safetyDir)) {
      @mkdir($safetyDir, 0755, true);
    }

    $configGi = $configDir . '/.gitignore';
    if (!is_file($configGi)) {
      $src = is_file($root . '/Resources/Config/.gitignore')
        ? $root . '/Resources/Config/.gitignore'
        : $root . '/vendor/eber/framework/Resources/Config/.gitignore';
      if (is_file($src)) {
        @copy($src, $configGi);
      } else {
        @file_put_contents($configGi, "compiled.config.php\nsecrets.vault.php\n*.vault.php\n*.key.php\n*.local.json\n.studio.*.json\n", LOCK_EX);
      }
    }

    $safetyGi = $safetyDir . '/.gitignore';
    if (!is_file($safetyGi)) {
      $src = is_file($root . '/Resources/Safety/.gitignore')
        ? $root . '/Resources/Safety/.gitignore'
        : $root . '/vendor/eber/framework/Resources/Safety/.gitignore';
      if (is_file($src)) {
        @copy($src, $safetyGi);
      } else {
        @file_put_contents($safetyGi, "vault.key.php\n*.key.php\n*.vault.php\nblocked_ips.json\nintrusions.json\n", LOCK_EX);
      }
    }
  }

  /**
   * Compila App/Config/compiled.config.php, App/Config/secrets.vault.php y App/Safety/vault.key.php.
   */
  public static function compile(?string $basePath = null, ?array $overrideValues = null, bool $rotateKey = false): array
  {
    $root = self::resolveBasePath($basePath);
    self::ensureSensitiveGitignores($root);

    $rawProfile = EnvConfigService::readProfile('production', $root);
    if (is_array($overrideValues)) {
      $rawProfile = array_merge($rawProfile, $overrideValues);
    }

    $validation = ConfigSchema::validate($rawProfile, 'production');
    if (!empty($validation['errors'])) {
      return [
        'success' => false,
        'message' => 'Error de validación en perfil de producción.',
        'errors' => $validation['errors'],
      ];
    }

    $values = $validation['values'];
    $values['APP_ENV'] = 'production';

    // Asegurar SEED y DEPLOY_TOKEN criptográficos mínimos si estuvieran vacíos
    if (empty($values['SEED']) || strlen((string)$values['SEED']) < 32) {
      $values['SEED'] = EnvConfigService::generateCryptoKey('seed');
    }
    if (empty($values['DEPLOY_TOKEN']) || strlen((string)$values['DEPLOY_TOKEN']) < 16) {
      $values['DEPLOY_TOKEN'] = EnvConfigService::generateCryptoKey('token');
    }

    // Resolver o generar clave maestra autocontenida en App/Safety/vault.key.php
    $keyFile = $root . '/' . ConfigVault::VAULT_KEY_REL;
    $keyCreated = false;
    $masterKey = null;

    if (!$rotateKey) {
      $masterKey = ConfigVault::resolveMasterKey($root);
    }

    if ($masterKey === null) {
      $masterKey = ConfigVault::generateMasterKey();
      $keyPhp = "<?php\n\n"
        . "if (!defined('ROOT_PATH')) {\n  exit;\n}\n\n"
        . "/**\n * Clave maestra autocontenida del Vault de Frame.\n * NUNCA subir a Git.\n */\n"
        . "return [\n  'key' => '" . $masterKey . "',\n  'generated_at' => '" . date('c') . "',\n];\n";
      if (@file_put_contents($keyFile, $keyPhp, LOCK_EX) === false) {
        return ['success' => false, 'message' => 'No se pudo escribir App/Safety/vault.key.php.'];
      }
      @chmod($keyFile, 0600);
      $keyCreated = true;
    }

    // Separar Grupo 1 (compilado no sensible) y Grupo 2 (Vault cifrado)
    $compiledEnv = [];
    foreach (ConfigSchema::getCompiledEnvKeys() as $key) {
      if (array_key_exists($key, $values)) {
        $compiledEnv[$key] = $values[$key];
      }
    }
    $compiledEnv['APP_ENV'] = 'production';

    $compiledConstants = [];
    foreach (ConfigSchema::getCompiledConstKeys() as $key) {
      if (array_key_exists($key, $values)) {
        $compiledConstants[$key] = $values[$key];
      }
    }

    $secrets = [];
    foreach (ConfigSchema::getVaultKeys() as $key) {
      if (array_key_exists($key, $values)) {
        $secrets[$key] = $values[$key];
      }
    }

    // Extraer sección pública de jsConfig.json y librerías activas
    $jsConfig = JsRegistryService::readJsConfig($root);
    $libraries = JsRegistryService::readConfiguredLibraries($root);

    $compiledPayload = [
      'version' => 1,
      'generated_at' => date('c'),
      'env' => $compiledEnv,
      'constants' => $compiledConstants,
      'jsConfig' => [
        'priority' => $jsConfig['priority'],
        'exclude' => $jsConfig['exclude'],
        'functions' => $jsConfig['functions'],
      ],
      'libraries' => $libraries,
    ];

    $compiledPath = $root . '/' . ConfigVault::COMPILED_CONFIG_REL;
    $compiledPhp = "<?php\n\n"
      . "if (!defined('ROOT_PATH')) {\n  exit;\n}\n\n"
      . "/**\n * Configuración de producción compilada (cacheada por OPcache).\n * Generado automáticamente por Frame Studio / ConfigCompilerService.\n */\n"
      . "return " . var_export($compiledPayload, true) . ";\n";

    if (@file_put_contents($compiledPath, $compiledPhp, LOCK_EX) === false) {
      return ['success' => false, 'message' => 'No se pudo escribir App/Config/compiled.config.php.'];
    }

    $envelope = ConfigVault::encrypt($secrets, $masterKey);
    $vaultPath = $root . '/' . ConfigVault::SECRETS_VAULT_REL;
    $vaultPhp = "<?php\n\n"
      . "if (!defined('ROOT_PATH')) {\n  exit;\n}\n\n"
      . "/**\n * Secretos de producción cifrados con AEAD (" . $envelope['cipher'] . ").\n * Generado automáticamente por Frame Studio / ConfigCompilerService.\n */\n"
      . "return " . var_export($envelope, true) . ";\n";

    if (@file_put_contents($vaultPath, $vaultPhp, LOCK_EX) === false) {
      return ['success' => false, 'message' => 'No se pudo escribir App/Config/secrets.vault.php.'];
    }
    @chmod($vaultPath, 0600);

    return [
      'success' => true,
      'message' => 'Configuración de producción compilada y cifrada con éxito.',
      'cipher' => $envelope['cipher'],
      'compiledPath' => ConfigVault::COMPILED_CONFIG_REL,
      'vaultPath' => ConfigVault::SECRETS_VAULT_REL,
      'keyPath' => ConfigVault::VAULT_KEY_REL,
      'keyCreated' => $keyCreated,
      'envCount' => count($compiledEnv),
      'constantsCount' => count($compiledConstants),
      'secretsCount' => count($secrets),
      'compiledBytes' => (int)filesize($compiledPath),
      'vaultBytes' => (int)filesize($vaultPath),
    ];
  }

  /**
   * Sincroniza únicamente la sección pública de jsConfig y librerías activas
   * dentro de App/Config/compiled.config.php si ya existe, sin tocar env,
   * constants, secrets.vault.php ni vault.key.php.
   *
   * @param string|null $basePath Ruta raíz del proyecto.
   * @return array<string, mixed>
   */
  public static function syncJsConfig(?string $basePath = null): array
  {
    $root = self::resolveBasePath($basePath);
    $compiledPath = $root . '/' . ConfigVault::COMPILED_CONFIG_REL;

    if (!is_file($compiledPath)) {
      return [
        'success' => true,
        'exists' => false,
        'updated' => false,
      ];
    }

    if (!defined('ROOT_PATH')) {
      define('ROOT_PATH', $root);
    }

    $existing = require $compiledPath;
    if (!is_array($existing)) {
      return [
        'success' => false,
        'exists' => true,
        'updated' => false,
        'message' => 'App/Config/compiled.config.php no tiene un formato válido.',
      ];
    }

    $jsConfig = JsRegistryService::readJsConfig($root);
    $newJsConfig = [
      'priority' => $jsConfig['priority'],
      'exclude' => $jsConfig['exclude'],
      'functions' => $jsConfig['functions'],
    ];
    $newLibraries = JsRegistryService::readConfiguredLibraries($root);

    $changed = ($existing['jsConfig'] ?? null) !== $newJsConfig
      || ($existing['libraries'] ?? null) !== $newLibraries;

    if (!$changed) {
      return [
        'success' => true,
        'exists' => true,
        'updated' => false,
      ];
    }

    $existing['jsConfig'] = $newJsConfig;
    $existing['libraries'] = $newLibraries;
    $existing['generated_at'] = date('c');

    $compiledPhp = "<?php\n\n"
      . "if (!defined('ROOT_PATH')) {\n  exit;\n}\n\n"
      . "/**\n * Configuración de producción compilada (cacheada por OPcache).\n * Generado automáticamente por Frame Studio / ConfigCompilerService.\n */\n"
      . "return " . var_export($existing, true) . ";\n";

    if (@file_put_contents($compiledPath, $compiledPhp, LOCK_EX) === false) {
      return [
        'success' => false,
        'exists' => true,
        'updated' => false,
        'message' => 'No se pudo escribir App/Config/compiled.config.php.',
      ];
    }

    if (function_exists('opcache_invalidate')) {
      @opcache_invalidate($compiledPath, true);
    }

    return [
      'success' => true,
      'exists' => true,
      'updated' => true,
      'compiledPath' => ConfigVault::COMPILED_CONFIG_REL,
    ];
  }

  /**
   * Devuelve el estado actual de los artefactos compilados en App/Config y App/Safety.
   *
   * @return array<string, mixed>
   */
  public static function getStatus(?string $basePath = null): array
  {
    $root = self::resolveBasePath($basePath);
    $compiledPath = $root . '/' . ConfigVault::COMPILED_CONFIG_REL;
    $vaultPath = $root . '/' . ConfigVault::SECRETS_VAULT_REL;
    $keyPath = $root . '/' . ConfigVault::VAULT_KEY_REL;
    $draftPath = $root . '/' . EnvConfigService::PRODUCTION_DRAFT_REL;

    return [
      'compiledExists' => is_file($compiledPath),
      'compiledMtime' => is_file($compiledPath) ? date('Y-m-d H:i:s', (int)filemtime($compiledPath)) : null,
      'compiledBytes' => is_file($compiledPath) ? (int)filesize($compiledPath) : 0,
      'vaultExists' => is_file($vaultPath),
      'vaultMtime' => is_file($vaultPath) ? date('Y-m-d H:i:s', (int)filemtime($vaultPath)) : null,
      'vaultBytes' => is_file($vaultPath) ? (int)filesize($vaultPath) : 0,
      'keyExists' => is_file($keyPath),
      'draftExists' => is_file($draftPath),
      'cipher' => ConfigVault::getPreferredCipher(),
    ];
  }
}
