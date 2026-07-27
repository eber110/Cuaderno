<?php

/**
 * Script Cron para volcamiento de registros de visitas desde JSON a Base de Datos.
 * 
 * Se ejecuta periódicamente (ej: cada 15 minutos).
 * Procesa registros en lotes de hasta 2000 elementos utilizando la clase Builder.
 */

// 1. Definir ROOT_PATH y requerir autoloader y configuración de la aplicación
if (!defined('ROOT_PATH')) {
  define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__, 2)));
}

require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/App/Config/config.php';

use Base\Builder\Builder;
use Base\Module\LogModule;

// Constantes de rutas y configuración
$visitsDir = "Cache/Visits";
$originalFile = $visitsDir . "/visit_register.json";
$processingFile = $visitsDir . "/visit_register_processing.json";
$batchSize = 2000;

// 2. Si existe visit_register.json, renombrarlo a visit_register_processing.json
// Esto permite que el sitio web siga acumulando nuevas visitas en visit_register.json de forma concurrente
if (file_exists(ROOT_PATH . '/' . $originalFile)) {
  LogModule::renameLog($originalFile, "visit_register_processing.json");
}

// 3. Verificar si existe el archivo de procesamiento
if (!file_exists(ROOT_PATH . '/' . $processingFile)) {
  echo "No hay registros de visitas pendientes por procesar.\n";
  exit(0);
}

// 4. Leer todos los registros decodificados usando LogModule
$records = LogModule::readLogLines($processingFile);
$totalRecords = count($records);

if ($totalRecords === 0) {
  LogModule::deleteLog($processingFile);
  echo "El archivo de procesamiento está vacío. Archivo eliminado.\n";
  exit(0);
}

echo "Procesando {$totalRecords} registros de visitas...\n";

// 5. Inicializar la tabla 'visits' con la clase Builder
$db = new Builder("visits");

// Crear la tabla si no existe
$createTableSql = "CREATE TABLE IF NOT EXISTS `visits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `visited_user` VARCHAR(100) NOT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `region` VARCHAR(100) DEFAULT NULL,
  `codigo` VARCHAR(20) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `referer` TEXT DEFAULT NULL,
  `visited_at` DATETIME DEFAULT NULL,
  `timestamp` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try {
  $db->query_foreign($createTableSql);
} catch (\Exception $e) {
  echo "Error al verificar o crear la tabla 'visits': " . $e->getMessage() . "\n";
  exit(1);
}

// 6. Tomar un lote de hasta 2000 registros
$batch = array_slice($records, 0, $batchSize);
$remainingRecords = array_slice($records, $batchSize);

$insertedCount = 0;

try {
  // Iniciar transacción de base de datos
  $db->beginTransaction();

  foreach ($batch as $record) {
    // Sanitizar y estructurar los campos a insertar en la BD
    $dataToInsert = [
      "visited_user" => mb_strtolower($record['visited_user'] ?? '', 'UTF-8'),
      "ip" => $record['ip'] ?? '127.0.0.1',
      "country" => $record['country'] ?? '',
      "city" => $record['city'] ?? '',
      "region" => $record['region'] ?? '',
      "codigo" => $record['codigo'] ?? '',
      "user_agent" => $record['user_agent'] ?? '',
      "referer" => $record['referer'] ?? '',
      "visited_at" => $record['visited_at'] ?? date('Y-m-d H:i:s'),
      "timestamp" => $record['timestamp'] ?? time()
    ];

    $db->create($dataToInsert);
    $insertedCount++;
  }

  // Confirmar la transacción
  $db->commit();
  echo "Se insertaron con éxito {$insertedCount} registros en la base de datos.\n";

} catch (\Exception $e) {
  // Revertir transacción en caso de error
  if (method_exists($db, 'rollBack')) {
    @$db->rollBack();
  }
  echo "Error durante la inserción masiva: " . $e->getMessage() . "\n";
  exit(1);
}

// 7. Gestionar el archivo de procesamiento post-inserción
LogModule::deleteLog($processingFile);

if (count($remainingRecords) > 0) {
  // Si quedaron registros pendientes por superar el límite de 2000, guardarlos para la siguiente corrida
  foreach ($remainingRecords as $remRecord) {
    LogModule::simpleLog([
      "dir" => ROOT_PATH . "/" . $visitsDir,
      "name" => "visit_register_processing",
      "content" => $remRecord
    ]);
  }
  echo "Quedaron " . count($remainingRecords) . " registros en 'visit_register_processing.json' para la siguiente ejecución cron.\n";
} else {
  echo "Procesamiento completado. Archivo 'visit_register_processing.json' eliminado.\n";
}
