<?php

/**
 * Eber Framework - Compilador de Configuración de Producción y Vault AEAD
 *
 * Uso:
 *   frame config:build [--rotate-key]
 *   composer config:build
 */

if (php_sapi_name() !== 'cli') {
  die("Este script solo puede ejecutarse desde la interfaz de comandos (CLI).\n");
}

$cwd = str_replace('\\', '/', getcwd());
$autoload = file_exists($cwd . '/vendor/autoload.php')
  ? $cwd . '/vendor/autoload.php'
  : dirname(__DIR__, 2) . '/vendor/autoload.php';

if (file_exists($autoload)) {
  require_once $autoload;
}

if (!defined('ROOT_PATH')) {
  define('ROOT_PATH', $cwd);
}

require_once __DIR__ . '/FrameworkGuard.php';
\Base\ScriptComposer\FrameworkGuard::abortIfFrameworkRepo("'frame config:build'", $cwd);

require_once __DIR__ . '/FrameworkGuard.php';
\Base\ScriptComposer\FrameworkGuard::abortIfFrameworkRepo("'frame config:build'", $cwd);

$rotateKey = in_array('--rotate-key', $_SERVER['argv'] ?? [], true);

echo "\n======================================================\n";
echo "🔐 FRAME — COMPILADOR DE CONFIGURACIÓN Y VAULT\n";
echo "======================================================\n\n";

$result = \Base\Studio\Services\ConfigCompilerService::compile($cwd, null, $rotateKey);
if (empty($result['success'])) {
  echo "❌ Error: " . ($result['message'] ?? 'No se pudo compilar la configuración.') . "\n";
  if (!empty($result['errors']) && is_array($result['errors'])) {
    foreach ($result['errors'] as $field => $msg) {
      echo "   • {$field}: {$msg}\n";
    }
  }
  exit(1);
}

echo "✅ Algoritmo AEAD:       {$result['cipher']}\n";
echo "✅ Config compilada:     {$result['compiledPath']} ({$result['compiledBytes']} B, {$result['envCount']} vars, {$result['constantsCount']} constantes)\n";
echo "✅ Vault cifrado:        {$result['vaultPath']} ({$result['vaultBytes']} B, {$result['secretsCount']} secretos)\n";
echo ($result['keyCreated'] ? "🔑 Clave generada:       " : "🔒 Clave conservada:     ") . "{$result['keyPath']}\n\n";
