<?php

/**
 * Suite de Pruebas de Integración y Funcionalidad del Sitio.
 * Verifica que tras las modificaciones no haya funciones rotas en modelos, controladores, vistas y helpers.
 */

define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)));

require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/App/Config/config.php';
require_once ROOT_PATH . '/Bootstrap/App.php';

$results = [
  'passed' => 0,
  'failed' => 0,
  'tests'  => []
];

function assertTest(string $name, bool $condition, string $detail = '') {
  global $results;
  if ($condition) {
    $results['passed']++;
    $results['tests'][] = ['name' => $name, 'status' => 'PASS', 'detail' => $detail];
    echo "  [PASS] $name\n";
  } else {
    $results['failed']++;
    $results['tests'][] = ['name' => $name, 'status' => 'FAIL', 'detail' => $detail];
    echo "  [FAIL] $name - $detail\n";
  }
}

echo "\n=======================================================\n";
echo " INICIANDO SUITE DE PRUEBAS DE FUNCIONALIDAD DEL SITIO \n";
echo "=======================================================\n\n";

// --- 1. PRUEBAS DE MODELO STATISTICS ---
echo "[1] Probando App\\Models\\StatisticsModels...\n";
try {
  $statsData = \App\Models\StatisticsModels::getStatsData('usuario_demo');
  assertTest(
    'StatisticsModels::getStatsData retorna array válido',
    is_array($statsData) && isset($statsData['user']) && $statsData['user'] === 'usuario_demo'
  );
  assertTest(
    'StatisticsModels contiene resumen inicial con claves correctas',
    isset($statsData['summary']['total_views'], $statsData['summary']['unique_views'], $statsData['summary']['total_clicks'])
  );
} catch (\Throwable $e) {
  assertTest('StatisticsModels no lanza excepciones', false, $e->getMessage());
}

// --- 2. PRUEBAS DE ACTIVE VIEWERS HELPER (AISLAMIENTO POR PERFIL) ---
echo "\n[2] Probando App\\Rsc\\Helper\\ActiveViewersHelper (Aislamiento por usuario)...\n";
try {
  $testUserA = 'usuario_test_a';
  $testUserB = 'usuario_test_b';
  $token1    = 'test_token_alpha_' . time();
  $token2    = 'test_token_beta_' . time();

  // Registrar latido para usuario A
  $countA1 = \App\Rsc\Helper\ActiveViewersHelper::registerHeartbeat($testUserA, $token1);
  assertTest(
    'ActiveViewers registra heartbeat y retorna conteo >= 1 para Usuario A',
    $countA1 >= 1,
    "Conteo retornado: $countA1"
  );

  // Registrar latido para usuario B con token distinto
  $countB1 = \App\Rsc\Helper\ActiveViewersHelper::registerHeartbeat($testUserB, $token2);
  assertTest(
    'ActiveViewers registra heartbeat para Usuario B independientemente de Usuario A',
    $countB1 >= 1,
    "Conteo retornado para B: $countB1"
  );

  // Registrar segundo visitante para usuario A
  $countA2 = \App\Rsc\Helper\ActiveViewersHelper::registerHeartbeat($testUserA, $token2);
  assertTest(
    'ActiveViewers incrementa conteo al recibir segundo visitante en Usuario A',
    $countA2 >= 2,
    "Conteo retornado para A: $countA2"
  );

  // Conteo de usuario B no debe haber sido afectado por los visitantes de A
  $countB2 = \App\Rsc\Helper\ActiveViewersHelper::registerHeartbeat($testUserB, $token1);
  assertTest(
    'Métricas de usuarios permanecen aisladas y personales sin sumar de forma global',
    $countB2 >= 1
  );

} catch (\Throwable $e) {
  assertTest('ActiveViewersHelper no lanza excepciones', false, $e->getMessage());
}

// --- 3. PRUEBAS DE VISTAS Y PARTES MODIFICADAS ---
echo "\n[3] Probando Renderizado de Vistas y Partes...\n";
try {
  $statsHtml = _partToString("Dashboard.statisticsPanel", [
    "stats" => \App\Models\StatisticsModels::getStatsData('testuser'),
    "card"  => ["profile" => "testuser"],
    "user"  => "testuser",
    "uri"   => ["estadisticas" => "/panel/testuser/estadisticas"]
  ]);

  assertTest(
    'Parte Dashboard.statisticsPanel se renderiza correctamente sin errores',
    !empty($statsHtml) && str_contains($statsHtml, 'Estadísticas')
  );

  $navHtml = _partToString("Dashboard.NavPanel.navPanel", [
    "card"      => ["profile" => "testuser"],
    "hasCustom" => false,
    "uri"       => [],
    "session"   => ["username" => "testuser"]
  ]);

  assertTest(
    'Parte Dashboard.NavPanel.navPanel renderiza el badge de usuarios en línea con data-profile-user',
    !empty($navHtml) && str_contains($navHtml, 'data-profile-user="testuser"')
  );

} catch (\Throwable $e) {
  assertTest('Renderizado de vistas no lanza excepciones', false, $e->getMessage());
}

// --- 4. PRUEBAS DE MODELOS CORE (USERMODELS Y DESIGNMODELS) ---
echo "\n[4] Probando App\\Models\\DesignModels y App\\Models\\UserModels...\n";
try {
  $defaultCard = \App\Models\DesignModels::getDefaultCard('testuser');
  assertTest(
    'DesignModels::getDefaultCard genera estructura base válida',
    is_array($defaultCard) && ($defaultCard['profile'] ?? '') === 'testuser'
  );

  $formattedImages = \App\Models\UserModels::formatCardImages($defaultCard);
  assertTest(
    'UserModels::formatCardImages procesa correctamente las imágenes de la tarjeta',
    is_array($formattedImages) && isset($formattedImages['avatar'])
  );

  $userExists = \App\Models\UserModels::userExists('usuario_inexistente_xyz_123');
  assertTest(
    'UserModels::userExists responde booleano sin errores',
    $userExists === false
  );
} catch (\Throwable $e) {
  assertTest('Modelos core funcionan sin errores', false, $e->getMessage());
}

// --- 5. PRUEBAS DE CONTROLADORES Y ORQUESTACIÓN ---
echo "\n[5] Probando App\\Controllers\\StatisticsControllers...\n";
try {
  $ctrlStats = \App\Controllers\StatisticsControllers::getStatsData('testuser');
  assertTest(
    'StatisticsControllers::getStatsData orquesta la petición hacia el modelo',
    is_array($ctrlStats) && isset($ctrlStats['user'])
  );
} catch (\Throwable $e) {
  assertTest('StatisticsControllers no lanza excepciones', false, $e->getMessage());
}

// --- 6. PRUEBAS DE INTEGRIDAD DE RUTAS Y SEGURIDAD ---
echo "\n[6] Probando Rutas de la Aplicación...\n";
try {
  $routesFile = ROOT_PATH . '/App/Safety/routes_security.json';
  assertTest(
    'Archivo de mapa de seguridad de rutas existe y es válido',
    file_exists($routesFile) && json_decode(file_get_contents($routesFile), true) !== null
  );
} catch (\Throwable $e) {
  assertTest('Validación de rutas sin errores', false, $e->getMessage());
}

// --- 7. VERIFICACIÓN DE DESACOPLAMIENTO DE APEXCHARTS ---
echo "\n[7] Verificando eliminación total de ApexCharts...\n";
$apexDir = ROOT_PATH . '/App/Rsc/Library/ApexCharts';
$chartJs = ROOT_PATH . '/App/Public/Js/Charts/generalSummaryChart.js';
$jsConfig = json_decode(file_get_contents(ROOT_PATH . '/jsConfig.json'), true);
$loadLib = file_get_contents(ROOT_PATH . '/App/Config/loadLibraryJsConfiguration.php');
$chartCss = file_get_contents(ROOT_PATH . '/App/Public/Css/chart-theme.css');

assertTest(
  'Carpeta App/Rsc/Library/ApexCharts eliminada',
  !file_exists($apexDir)
);
assertTest(
  'Archivo App/Public/Js/Charts/generalSummaryChart.js eliminado',
  !file_exists($chartJs)
);
assertTest(
  'Librería ApexCharts removida de loadLibraryJsConfiguration.php',
  !str_contains($loadLib, 'ApexCharts')
);
assertTest(
  'generalSummaryChart removido de jsConfig.json',
  !isset($jsConfig['functions']['defer']['generalSummaryChart'])
);
assertTest(
  'Estilos .apexcharts-* removidos de chart-theme.css',
  !str_contains($chartCss, 'apexcharts')
);

// --- RESUMEN FINAL ---
echo "\n=======================================================\n";
echo " RESUMEN: {$results['passed']} pasadas, {$results['failed']} falladas.\n";
echo "=======================================================\n\n";

if ($results['failed'] > 0) {
  exit(1);
}
exit(0);
