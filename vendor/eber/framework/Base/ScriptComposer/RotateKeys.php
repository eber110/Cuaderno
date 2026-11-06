<?php

/**
 * Eber Framework - Gestor de Rotación de Claves de Seguridad (SEED y DEPLOY_TOKEN)
 * 
 * Permite rotar la semilla criptográfica (SEED) y el token de despliegue (DEPLOY_TOKEN)
 * ante sospechas de intrusión, auditorías de seguridad o mantenimiento preventivo.
 * 
 * Uso:
 *   frame key:rotate [--seed] [--token] [--force]
 */

if (php_sapi_name() !== 'cli') {
  die("Este script solo puede ejecutarse desde la interfaz de comandos (CLI).\n");
}

class RotateKeys
{
  private string $basePath;

  public function __construct()
  {
    $this->basePath = str_replace('\\', '/', getcwd());
  }

  public function run(array $args): void
  {
    echo "\n======================================================\n";
    echo "🔐 FRAME — ROTACIÓN DE CLAVES Y SEMILLAS DE SEGURIDAD\n";
    echo "======================================================\n\n";

    $envFile = $this->basePath . '/.env';
    if (!file_exists($envFile)) {
      echo "❌ Error: No se encontró el archivo .env en la raíz del proyecto.\n";
      echo "   Ruta: {$envFile}\n";
      exit(1);
    }

    $rotateSeed  = in_array('--seed', $args, true);
    $rotateToken = in_array('--token', $args, true) || in_array('--deploy', $args, true);
    $force       = in_array('--force', $args, true) || in_array('-y', $args, true);

    // Si no se pasaron flags específicos, rotar ambas claves
    if (!$rotateSeed && !$rotateToken) {
      $rotateSeed  = true;
      $rotateToken = true;
    }

    if (!$force) {
      echo "⚠️  ADVERTENCIA DE SEGURIDAD ANTE ROTACIÓN:\n";
      if ($rotateSeed) {
        echo "   • Rotar SEED: Invalidará todas las cookies JWT, tokens CSRF y sesiones activas de usuarios.\n";
      }
      if ($rotateToken) {
        echo "   • Rotar DEPLOY_TOKEN: Exigirá actualizar la clave en el servidor remoto para seguir usando 'frame deploy'.\n";
      }
      echo "\n¿Deseas continuar y generar nuevas claves criptográficas? (s/N): ";
      $confirm = strtolower(trim((string)fgets(STDIN)));
      if (!in_array($confirm, ['s', 'si', 'y', 'yes'], true)) {
        echo "Operación cancelada por el usuario. No se modificó ningún archivo.\n\n";
        return;
      }
      echo "\n";
    }

    $envContent = (string)file_get_contents($envFile);
    $newSeed = null;
    $newToken = null;

    // 1. Rotar SEED (64 caracteres hexadecimales)
    if ($rotateSeed) {
      $newSeed = bin2hex(random_bytes(32));
      if (str_contains($envContent, 'SEED=')) {
        $envContent = preg_replace('/^SEED\s*=.*$/m', 'SEED="' . $newSeed . '"', $envContent);
      } else {
        $envContent = "SEED=\"{$newSeed}\"\n" . $envContent;
      }
      echo "✅ Nueva semilla de seguridad (SEED) generada y actualizada en .env.\n";
    }

    // 2. Rotar DEPLOY_TOKEN (32 caracteres hexadecimales)
    if ($rotateToken) {
      $newToken = bin2hex(random_bytes(16));
      if (str_contains($envContent, 'DEPLOY_TOKEN=')) {
        $envContent = preg_replace('/^DEPLOY_TOKEN\s*=.*$/m', 'DEPLOY_TOKEN="' . $newToken . '"', $envContent);
      } else {
        $envContent .= "\nDEPLOY_TOKEN=\"{$newToken}\"\n";
      }
      echo "✅ Nuevo token de despliegue (DEPLOY_TOKEN) generado y actualizado en .env.\n";

      // Actualizar también deploy.php local si existe
      $deployFile = $this->basePath . '/deploy.php';
      if (file_exists($deployFile)) {
        $deployContent = (string)file_get_contents($deployFile);
        $deployContent = preg_replace('/\$expectedToken\s*=\s*\'[^\']*\';/', "\$expectedToken = '{$newToken}';", $deployContent);
        file_put_contents($deployFile, $deployContent);
        echo "✅ Archivo local 'deploy.php' sincronizado con el nuevo token.\n";
      }
    }

    file_put_contents($envFile, $envContent);

    echo "\n======================================================\n";
    echo "🎉 ROTACIÓN DE CREDENCIALES COMPLETADA\n";
    echo "======================================================\n";
    if ($newSeed) {
      echo "🔑 Nueva SEED:         {$newSeed}\n";
    }
    if ($newToken) {
      echo "🚀 Nuevo DEPLOY_TOKEN: {$newToken}\n";
    }
    echo "======================================================\n\n";

    echo "👉 PASOS OBLIGATORIOS PARA RECONECTAR EL SERVIDOR REMOTO:\n";
    if ($newToken) {
      echo "   1. Accede a tu servidor de producción (cPanel / FTP / SSH).\n";
      echo "   2. Abre el archivo 'deploy.php' y actualiza la línea:\n";
      echo "      \$expectedToken = '{$newToken}';\n";
      echo "      (o actualiza DEPLOY_TOKEN=\"{$newToken}\" en el .env de producción).\n\n";
    }
    if ($newSeed) {
      echo "   3. En el archivo .env de tu servidor remoto, actualiza:\n";
      echo "      SEED=\"{$newSeed}\"\n";
      echo "      para sincronizar el cifrado de sesiones y evitar discrepancias.\n\n";
    }
    echo "🛡️  Tu aplicación está ahora protegida contra accesos con las credenciales anteriores.\n\n";
  }
}

// Iniciar ejecución si se llama directamente
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
  $rotator = new RotateKeys();
  $rotator->run($_SERVER['argv'] ?? []);
}
