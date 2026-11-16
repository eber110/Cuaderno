<?php

/**
 * RECEPTOR DE DESPLIEGUE — EBER FRAMEWORK
 *
 * Este archivo recibe y descomprime los despliegues de 'frame deploy'.
 * Solo acepta peticiones POST autenticadas con DEPLOY_TOKEN y valida
 * integridad, anti-ZipSlip, anti-symlinks y lista de IPs permitidas.
 */

// 1. No exponer banner ni confirmar existencia en métodos distintos a POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(404);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => 'Not found']);
  exit;
}

// 2. Obtener configuración desde .env o token embebido (compatible con frame key:rotate)
$expectedToken = '###DEPLOY_TOKEN###';
$deployEnabled = true;
$allowedIps = [];

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
  $envLines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
  foreach ($envLines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
      continue;
    }
    if (str_starts_with($line, 'DEPLOY_TOKEN=')) {
      $val = trim(substr($line, 13), " \"'");
      if ($val !== '') {
        $expectedToken = $val;
      }
    } elseif (str_starts_with($line, 'DEPLOY_ENABLED=')) {
      $val = strtolower(trim(substr($line, 15), " \"'"));
      $deployEnabled = !in_array($val, ['false', '0', 'off', 'no'], true);
    } elseif (str_starts_with($line, 'DEPLOY_ALLOWED_IPS=')) {
      $val = trim(substr($line, 19), " \"'");
      if ($val !== '') {
        $allowedIps = array_values(array_filter(array_map('trim', explode(',', $val))));
      }
    }
  }
}

header('Content-Type: application/json; charset=utf-8');

if (!$deployEnabled) {
  http_response_code(403);
  echo json_encode(['error' => 'Despliegue deshabilitado por configuración (DEPLOY_ENABLED=false).']);
  exit;
}

// 3. Validar IP origen si DEPLOY_ALLOWED_IPS está definido
$clientIp = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
if (!empty($allowedIps)) {
  $ipAllowed = false;
  $ipBin = @inet_pton($clientIp);
  foreach ($allowedIps as $rule) {
    if ($rule === $clientIp) {
      $ipAllowed = true;
      break;
    }
    if (str_contains($rule, '/') && $ipBin !== false) {
      [$net, $mask] = explode('/', $rule, 2);
      $netBin = @inet_pton($net);
      if ($netBin !== false && strlen($netBin) === strlen($ipBin) && ctype_digit($mask)) {
        $bits = (int)$mask;
        $maxBits = strlen($ipBin) * 8;
        if ($bits >= 0 && $bits <= $maxBits) {
          $bytes = intdiv($bits, 8);
          $rem = $bits % 8;
          $maskBin = str_repeat("\xff", $bytes);
          if ($rem > 0) {
            $maskBin .= chr((0xff << (8 - $rem)) & 0xff);
          }
          $maskBin = str_pad($maskBin, strlen($ipBin), "\x00");
          if (($ipBin & $maskBin) === ($netBin & $maskBin)) {
            $ipAllowed = true;
            break;
          }
        }
      }
    }
  }
  if (!$ipAllowed) {
    http_response_code(403);
    echo json_encode(['error' => 'IP no autorizada para despliegue.']);
    exit;
  }
}

// 4. Fail-closed si no existe token válido configurado
if ($expectedToken === '' || $expectedToken === '###DEPLOY_TOKEN###' || strlen($expectedToken) < 16) {
  http_response_code(403);
  echo json_encode(['error' => 'Receptor de despliegue no configurado correctamente.']);
  exit;
}

// 5. Rate limiting atómico contra fuerza bruta sobre DEPLOY_TOKEN
$rlFile = sys_get_temp_dir() . '/frame_deploy_rl_' . sha1(__FILE__) . '.json';
$now = time();
$rlWindow = 900;
$rlMaxAttempts = 5;

$isRateLimited = false;
$rlFp = @fopen($rlFile, 'c+');
if ($rlFp && @flock($rlFp, LOCK_EX)) {
  $rawRl = stream_get_contents($rlFp);
  $rlData = json_decode((string)$rawRl, true);
  if (!is_array($rlData)) {
    $rlData = [];
  }
  $attempts = array_values(array_filter(
    (array)($rlData[$clientIp] ?? []),
    fn($ts) => is_int($ts) && ($now - $ts) < $rlWindow
  ));
  if (count($attempts) >= $rlMaxAttempts) {
    $isRateLimited = true;
  }
  $rlData[$clientIp] = $attempts;
  ftruncate($rlFp, 0);
  rewind($rlFp);
  fwrite($rlFp, json_encode($rlData));
  fflush($rlFp);
  flock($rlFp, LOCK_UN);
  fclose($rlFp);
}

if ($isRateLimited) {
  http_response_code(429);
  echo json_encode(['error' => 'Demasiados intentos de despliegue fallidos. Espera unos minutos.']);
  exit;
}

// 6. Validar token de seguridad (timing-attack safe)
$receivedToken = (string)($_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? $_POST['token'] ?? '');
if ($receivedToken === '' || !hash_equals($expectedToken, $receivedToken)) {
  $rlFp = @fopen($rlFile, 'c+');
  if ($rlFp && @flock($rlFp, LOCK_EX)) {
    $rawRl = stream_get_contents($rlFp);
    $rlData = json_decode((string)$rawRl, true);
    if (!is_array($rlData)) {
      $rlData = [];
    }
    $attempts = array_values(array_filter(
      (array)($rlData[$clientIp] ?? []),
      fn($ts) => is_int($ts) && ($now - $ts) < $rlWindow
    ));
    $attempts[] = $now;
    $rlData[$clientIp] = $attempts;
    ftruncate($rlFp, 0);
    rewind($rlFp);
    fwrite($rlFp, json_encode($rlData));
    fflush($rlFp);
    flock($rlFp, LOCK_UN);
    fclose($rlFp);
  }

  http_response_code(403);
  echo json_encode(['error' => 'Acceso no autorizado. Token de despliegue inválido o ausente.']);
  exit;
}

// 7. Validar archivo ZIP recibido
if (empty($_FILES['package']['tmp_name']) || !is_uploaded_file($_FILES['package']['tmp_name'])) {
  http_response_code(400);
  echo json_encode(['error' => 'No se recibió ningún paquete ZIP para desplegar.']);
  exit;
}

$zipPath = $_FILES['package']['tmp_name'];

// 8. Validar firma HMAC + anti-replay si el cliente envió cabeceras firmadas
$hdrTs  = (string)($_SERVER['HTTP_X_DEPLOY_TIMESTAMP'] ?? '');
$hdrSha = (string)($_SERVER['HTTP_X_DEPLOY_SHA256'] ?? '');
$hdrSig = (string)($_SERVER['HTTP_X_DEPLOY_SIGNATURE'] ?? '');
if ($hdrTs !== '' || $hdrSha !== '' || $hdrSig !== '') {
  if (!ctype_digit($hdrTs) || abs(time() - (int)$hdrTs) > 300) {
    http_response_code(403);
    echo json_encode(['error' => 'Firma de despliegue expirada o timestamp inválido.']);
    exit;
  }
  $actualSha = (string)@hash_file('sha256', $zipPath);
  if ($actualSha === '' || !hash_equals(strtolower($hdrSha), strtolower($actualSha))) {
    http_response_code(403);
    echo json_encode(['error' => 'Integridad del paquete ZIP comprometida (SHA-256 no coincide).']);
    exit;
  }
  $expectedSig = hash_hmac('sha256', $hdrTs . '|' . $actualSha, $expectedToken);
  if (!hash_equals($expectedSig, $hdrSig)) {
    http_response_code(403);
    echo json_encode(['error' => 'Firma HMAC de despliegue inválida.']);
    exit;
  }
}

$startTime = microtime(true);
$zip = new ZipArchive();

if ($zip->open($zipPath) !== true) {
  http_response_code(422);
  echo json_encode(['error' => 'El archivo recibido no es un ZIP válido o está dañado.']);
  exit;
}

// 9. PROTECCIÓN CRÍTICA ANTI-ZIPSLIP, ANTI-ZIPBOMB, ANTI-SYMLINKS Y DATOS PROTEGIDOS
$maxEntries = 15000;
$maxTotalUncompressed = 256 * 1024 * 1024; // 256 MB
$maxSingleFileBytes = 32 * 1024 * 1024;    // 32 MB

if ($zip->numFiles > $maxEntries) {
  $zip->close();
  @unlink($zipPath);
  http_response_code(413);
  echo json_encode(['error' => 'Paquete ZIP excede el máximo de entradas permitidas.']);
  exit;
}

$allowedFiles = [];
$totalUncompressed = 0;

for ($i = 0; $i < $zip->numFiles; $i++) {
  $stat = $zip->statIndex($i);
  $name = (string)($stat['name'] ?? '');
  $size = (int)($stat['size'] ?? 0);

  // Rechazar por completo rutas maliciosas (traversal, backslashes, unidades Windows, rutas absolutas o byte nulo)
  if (
    $name === '' ||
    str_contains($name, "\0") ||
    str_contains($name, '\\') ||
    str_starts_with($name, '/') ||
    preg_match('/^[a-zA-Z]:/', $name) ||
    preg_match('#(^|/)\.\.?(/|$)#', $name)
  ) {
    $zip->close();
    @unlink($zipPath);
    http_response_code(422);
    echo json_encode(['error' => 'Paquete ZIP rechazado: entrada con path traversal o ruta ilegal detectada.']);
    exit;
  }

  // Bloquear enlaces simbólicos Unix dentro del ZIP
  $opsys = 0;
  $attr = 0;
  if ($zip->getExternalAttributesIndex($i, $opsys, $attr)) {
    if ($opsys === ZipArchive::OPSYS_UNIX && ((($attr >> 16) & 0170000) === 0120000)) {
      $zip->close();
      @unlink($zipPath);
      http_response_code(422);
      echo json_encode(['error' => 'Paquete ZIP rechazado: los enlaces simbólicos no están permitidos.']);
      exit;
    }
  }

  if ($size < 0 || $size > $maxSingleFileBytes) {
    $zip->close();
    @unlink($zipPath);
    http_response_code(413);
    echo json_encode(['error' => 'Paquete ZIP rechazado: archivo individual excede tamaño máximo permitido.']);
    exit;
  }

  $totalUncompressed += $size;
  if ($totalUncompressed > $maxTotalUncompressed) {
    $zip->close();
    @unlink($zipPath);
    http_response_code(413);
    echo json_encode(['error' => 'Paquete ZIP rechazado: tamaño descomprimido excede el límite seguro.']);
    exit;
  }

  // NUNCA sobrescribir datos de usuario, secretos, logs ni el propio deploy.php
  if (preg_match('#^(Database/|Uploads/|App/Public/Uploads/|App/Safety/|Logs/|Cache/|\.git/|\.env(\.|$)|deploy\.php$)#i', $name)) {
    continue;
  }

  $allowedFiles[] = $name;
}

if (!empty($allowedFiles)) {
  $zip->extractTo(__DIR__, $allowedFiles);
}
$zip->close();
@unlink($zipPath);

// Limpiar contador de intentos fallidos tras despliegue exitoso
$rlFp = @fopen($rlFile, 'c+');
if ($rlFp && @flock($rlFp, LOCK_EX)) {
  $rawRl = stream_get_contents($rlFp);
  $rlData = json_decode((string)$rawRl, true);
  if (is_array($rlData) && isset($rlData[$clientIp])) {
    unset($rlData[$clientIp]);
    ftruncate($rlFp, 0);
    rewind($rlFp);
    fwrite($rlFp, json_encode($rlData));
    fflush($rlFp);
  }
  flock($rlFp, LOCK_UN);
  fclose($rlFp);
}

// 10. Limpiar caché de vistas si existe
$viewCache = __DIR__ . '/Cache/Views';
if (is_dir($viewCache)) {
  $cachedFiles = glob($viewCache . '/*') ?: [];
  foreach ($cachedFiles as $cf) {
    if (is_file($cf)) @unlink($cf);
  }
}

// 11. Respuesta de éxito
$duration = round(microtime(true) - $startTime, 2);
echo json_encode([
  'success'         => true,
  'message'         => "Despliegue completado con éxito en {$duration}s",
  'extracted_files' => count($allowedFiles),
  'duration'        => "{$duration}s"
]);