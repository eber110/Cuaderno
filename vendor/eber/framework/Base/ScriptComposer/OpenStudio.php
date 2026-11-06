<?php

/**
 * Frame - Theme Studio CLI Launcher
 *
 * Abre Theme Studio en el navegador y arranca el servidor local de desarrollo si no está corriendo.
 * 
 * Uso:
 *   frame theme:studio [--port=8000] [--no-server]
 *   frame studio
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

$port = 8000;
$noServer = false;
$noOpen = false;

foreach ($_SERVER["argv"] ?? [] as $arg) {
  if (preg_match('/^--port=(\d+)$/', $arg, $m)) {
    $port = (int)$m[1];
  }
  if ($arg === "--no-server" || $arg === "--info") {
    $noServer = true;
  }
  if ($arg === "--no-open") {
    $noOpen = true;
  }
}

$url = "http://localhost:{$port}/_frame/theme-studio";

echo "\n\033[1;36m======================================================\033[0m\n";
echo "\033[1;32m 🎨 FRAME — THEME STUDIO\033[0m\n";
echo "\033[1;36m======================================================\033[0m\n\n";

// Verificar si ya hay un servidor escuchando en el puerto
$isListening = false;
$sock = @fsockopen("127.0.0.1", $port, $errno, $errstr, 0.3);
if (is_resource($sock)) {
  $isListening = true;
  fclose($sock);
}

function openBrowserUrl(string $targetUrl): void
{
  if (stripos(PHP_OS, "WIN") === 0) {
    pclose(popen("start \"\" \"{$targetUrl}\"", "r"));
  } elseif (stripos(PHP_OS, "DAR") === 0) {
    exec("open " . escapeshellarg($targetUrl) . " > /dev/null 2>&1 &");
  } else {
    exec("xdg-open " . escapeshellarg($targetUrl) . " > /dev/null 2>&1 &");
  }
}

if ($isListening) {
  echo "✅ Servidor web detectado activo en el puerto \033[33m{$port}\033[0m.\n\n";
  echo "📍 \033[1mURL de Theme Studio:\033[0m\n";
  echo "   \033[36m" . $url . "\033[0m\n\n";

  if (!$noOpen) {
    echo "🌐 Abriendo Theme Studio en tu navegador predeterminado...\n\n";
    openBrowserUrl($url);
  }
  exit(0);
}

if ($noServer) {
  echo "📍 \033[1mURL de acceso:\033[0m\n";
  echo "   \033[36m" . $url . "\033[0m\n\n";
  echo "💡 \033[1mPara iniciar el servidor manualmente:\033[0m\n";
  echo "   \033[33mphp -S localhost:{$port}\033[0m\n\n";
  exit(0);
}

// Iniciar servidor integrado y abrir navegador
echo "🚀 Iniciando servidor de desarrollo PHP en \033[33mhttp://localhost:{$port}\033[0m...\n";
echo "📍 \033[1mURL de Theme Studio:\033[0m \033[36m{$url}\033[0m\n\n";

if (!$noOpen) {
  echo "🌐 Abriendo Theme Studio en tu navegador...\n";
  openBrowserUrl($url);
}

echo "💡 \033[90mPresiona \033[33mCtrl + C\033[90m en esta terminal para detener el servidor cuando termines.\033[0m\n\n";

$phpBinary = PHP_BINARY ?: "php";
passthru("\"{$phpBinary}\" -S localhost:{$port}");
