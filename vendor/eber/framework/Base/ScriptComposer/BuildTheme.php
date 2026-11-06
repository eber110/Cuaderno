<?php

/**
 * Eber Framework - Theme Engine Builder CLI
 *
 * Compila App/Config/theme.json a App/Public/Css/_configTheme.css.
 * 
 * Uso:
 *   php Base/ScriptComposer/BuildTheme.php [--force]
 *   frame theme:build [--force]
 */

if (php_sapi_name() !== "cli") {
  die("Este script solo puede ejecutarse desde la interfaz de comandos (CLI).\n");
}

if (file_exists(getcwd() . "/vendor/autoload.php")) {
  require_once getcwd() . "/vendor/autoload.php";
} else {
  require_once dirname(__DIR__, 2) . "/vendor/autoload.php";
}

if (!defined("ROOT_PATH")) {
  define("ROOT_PATH", str_replace("\\", "/", getcwd()));
}

use Base\Module\ThemeModule;

$args = array_slice($_SERVER["argv"] ?? [], 1);
$force = in_array("--force", $args, true) || in_array("-f", $args, true);

echo "\n\033[1;36m======================================================\033[0m\n";
echo "\033[1;32m 🎨 EBER FRAMEWORK — THEME ENGINE COMPILER\033[0m\n";
echo "\033[1;36m======================================================\033[0m\n\n";

$result = ThemeModule::build(ROOT_PATH, $force);

if ($result["success"]) {
  if ($result["updated"]) {
    echo "\033[32m✅ " . $result["message"] . "\033[0m\n\n";
    echo "\033[90mTip: Ejecuta \033[33mframe min\033[90m para compilar los cambios al bundle de producción.\033[0m\n\n";
    exit(0);
  } else {
    echo "\033[33m⏭️  " . $result["message"] . "\033[0m\n";
    echo "\033[90mUsa \033[36mframe theme:build --force\033[90m para forzar la regeneración del archivo CSS.\033[0m\n\n";
    exit(0);
  }
} else {
  echo "\033[31m❌ " . $result["message"] . "\033[0m\n\n";
  exit(1);
}
