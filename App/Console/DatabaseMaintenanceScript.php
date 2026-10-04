<?php

/**
 * Script de Consola para Mantenimiento y Depuración de la Base de Datos.
 * 
 * Uso:
 *   php App/Console/DatabaseMaintenanceScript.php [opciones]
 *   composer db-maintenance
 * 
 * Opciones:
 *   --audit   Solo detecta y reporta registros huérfanos sin alterar datos.
 *   --sync    Sincroniza y propaga el index_user a todas las tablas.
 *   --clean   Ejecuta la limpieza de registros huérfanos.
 *   --full    Ejecuta el ciclo completo (esquema + sync + auditoría + limpieza + vacuum) (por defecto).
 */

define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__, 2)));

require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/App/Config/config.php';

use App\DatabaseComponent\DatabaseMaintenance;

echo "========================================================\n";
echo "   SISTEMA DE MANTENIMIENTO Y DEPURACIÓN DE LA BD      \n";
echo "========================================================\n\n";

$options = getopt("", ["audit", "sync", "clean", "full"]);

$action = "full";
if (isset($options["audit"])) {
  $action = "audit";
} elseif (isset($options["sync"])) {
  $action = "sync";
} elseif (isset($options["clean"])) {
  $action = "clean";
}

try {
  $pdo = DatabaseMaintenance::getPdo();

  // Siempre asegurar esquemas primero
  echo "[1/4] Verificando esquemas y columnas 'index_user'...\n";
  $schemaRes = DatabaseMaintenance::ensureSchema($pdo);
  foreach ($schemaRes["log"] as $msg) {
    echo "  - $msg\n";
  }

  if ($action === "audit") {
    echo "\n[2/4] Auditando registros huérfanos...\n";
    $audit = DatabaseMaintenance::auditOrphans($pdo);
    echo "  Total de huérfanos encontrados: " . $audit["total_orphans"] . "\n";
    foreach ($audit["tables"] as $tbl => $info) {
      if ($info["count"] > 0) {
        echo "  - Tabla '{$tbl}': {$info['count']} registros huérfanos.\n";
      }
    }
    exit(0);
  }

  echo "\n[2/4] Sincronizando 'index_user' en registros válidos...\n";
  $syncRes = DatabaseMaintenance::syncIndexUsers($pdo);
  foreach ($syncRes["updated"] as $tbl => $count) {
    if ($count > 0) {
      echo "  - Tabla '{$tbl}': {$count} registros actualizados.\n";
    }
  }

  echo "\n[3/4] Auditando y depurando registros huérfanos...\n";
  $auditBefore = DatabaseMaintenance::auditOrphans($pdo);
  echo "  Huérfanos detectados antes de la limpieza: " . $auditBefore["total_orphans"] . "\n";

  $cleanRes = DatabaseMaintenance::cleanOrphans($pdo);
  if ($cleanRes["success"]) {
    echo "  Limpieza completada. Total de registros huérfanos eliminados: " . $cleanRes["total_deleted"] . "\n";
    foreach ($cleanRes["details"] as $tbl => $count) {
      if ($count > 0) {
        echo "  - Tabla '{$tbl}': {$count} registros eliminados.\n";
      }
    }
  } else {
    echo "  Error durante la limpieza: " . ($cleanRes["error"] ?? "Desconocido") . "\n";
  }

  echo "\n[3.5] Consolidando caché de suscripciones...\n";
  $consolidated = DatabaseMaintenance::consolidateSubscriptionCache($pdo);
  echo "  Registros consolidados: {$consolidated}\n";

  echo "\n[4/4] Optimizando y compactando almacenamiento (VACUUM)...\n";
  $vacRes = DatabaseMaintenance::vacuumAndOptimize($pdo);
  echo "  " . ($vacRes["message"] ?? ($vacRes["error"] ?? "Hecho")) . "\n";

  echo "\n========================================================\n";
  echo "       PROCESO DE MANTENIMIENTO FINALIZADO CON ÉXITO     \n";
  echo "========================================================\n";

} catch (\Throwable $e) {
  echo "\n[ERROR CRÍTICO] " . $e->getMessage() . "\n";
  exit(1);
}
