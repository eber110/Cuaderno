<?php

/**
 * RECEPTOR DE DESPLIEGUE — EBER FRAMEWORK
 * 
 * Este archivo recibe y descomprime los despliegues de 'frame deploy'.
 * Solo acepta peticiones POST con el token de seguridad autorizado.
 */

// 1. Obtener token esperado (desde .env o token embebido)
$expectedToken = '###DEPLOY_TOKEN###';

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
  $envLines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
  foreach ($envLines as $line) {
    $line = trim($line);
    if (str_starts_with($line, 'DEPLOY_TOKEN=')) {
      $val = trim(substr($line, 13), " \"'");
      if (!empty($val)) {
        $expectedToken = $val;
      }
      break;
    }
  }
}

// 2. Si la petición es GET, mostrar estado protegido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'service' => 'Frame Deploy Receiver',
    'status'  => 'active',
    'message' => 'Endpoint protegido listo para recibir despliegues POST.'
  ]);
  exit;
}

// 3. Validar token de seguridad (timing attack safe)
$receivedToken = $_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? $_POST['token'] ?? '';
if (empty($receivedToken) || !hash_equals($expectedToken, $receivedToken)) {
  http_response_code(403);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => 'Acceso no autorizado. Token de despliegue inválido o ausente.']);
  exit;
}

// 4. Validar archivo ZIP recibido
if (empty($_FILES['package']['tmp_name']) || !is_uploaded_file($_FILES['package']['tmp_name'])) {
  http_response_code(400);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => 'No se recibió ningún paquete ZIP para desplegar.']);
  exit;
}

$startTime = microtime(true);
$zipPath = $_FILES['package']['tmp_name'];
$zip = new ZipArchive();

if ($zip->open($zipPath) !== true) {
  http_response_code(422);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => 'El archivo recibido no es un ZIP válido o está dañado.']);
  exit;
}

// 5. PROTECCIÓN CRÍTICA DE DATOS:
// Aunque el cliente excluye estas carpetas, el servidor valida estrictamente que ninguna
// entrada pueda sobrescribir Database/, Uploads/ ni .env en producción.
$allowedFiles = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
  $stat = $zip->statIndex($i);
  $name = $stat['name'];

  // Bloquear path traversal
  if (str_contains($name, '../') || str_contains($name, '..\\')) {
    continue;
  }

  // NUNCA sobrescribir datos de usuario ni configuración
  if (preg_match('#^(Database/|Uploads/|\.env(\.|$))#i', $name)) {
    continue;
  }

  $allowedFiles[] = $name;
}

if (!empty($allowedFiles)) {
  $zip->extractTo(__DIR__, $allowedFiles);
}
$zip->close();
@unlink($zipPath);

// 6. Limpiar caché de vistas si existe
$viewCache = __DIR__ . '/Cache/Views';
if (is_dir($viewCache)) {
  $cachedFiles = glob($viewCache . '/*') ?: [];
  foreach ($cachedFiles as $cf) {
    if (is_file($cf)) @unlink($cf);
  }
}

// 7. Respuesta de éxito
$duration = round(microtime(true) - $startTime, 2);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
  'success'         => true,
  'message'         => "Despliegue completado con éxito en {$duration}s",
  'extracted_files' => count($allowedFiles),
  'duration'        => "{$duration}s"
]);