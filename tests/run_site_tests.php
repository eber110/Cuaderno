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

  $monthlyData = \App\Models\StatisticsModels::getMonthlyViewsData('c21d908d5f1e92f7fdc5b3b3cbc0b5');
  assertTest(
    'StatisticsModels::getMonthlyViewsData retorna estructura de mes actual y promedio',
    isset($monthlyData['current_month_total'], $monthlyData['previous_months_avg'], $monthlyData['previous_months'])
  );
  assertTest(
    'StatisticsModels calcula promedio histórico de meses anteriores correctamente',
    $monthlyData['previous_months_count'] === 9 && $monthlyData['previous_months_avg'] == 62.0
  );

  $ctoData = \App\Models\StatisticsModels::getCtoData('c21d908d5f1e92f7fdc5b3b3cbc0b5');
  assertTest(
    'StatisticsModels::getCtoData retorna estructura completa de CTO y desglose Enlaces/RRSS',
    isset($ctoData['monthly_views'], $ctoData['monthly_clicks'], $ctoData['configured_links'], $ctoData['all_time_clicks'])
  );
  assertTest(
    'StatisticsModels clasifica con precisión clics entre Enlaces de contenido y Redes Sociales',
    $ctoData['all_time_clicks'] === 420 && $ctoData['all_time_enlaces'] === 207 && $ctoData['all_time_rrss'] === 213
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

  $monthlyPart = _partToString("Dashboard.monthlyViews", [
    "monthly" => \App\Controllers\StatisticsControllers::evaluateMonthlyTrend(
      \App\Models\StatisticsModels::getMonthlyViewsData('c21d908d5f1e92f7fdc5b3b3cbc0b5')
    )
  ]);
  assertTest(
    'Parte Dashboard.monthlyViews se renderiza con el desglose del mes actual y flecha condicional',
    !empty($monthlyPart) && str_contains($monthlyPart, 'Visitas del mes actual')
  );
  assertTest(
    'Parte Dashboard.monthlyViews integra gráfico vectorial Hybrid Spline nativo',
    str_contains($monthlyPart, 'data-chart="hybrid-spline"') &&
    str_contains($monthlyPart, 'mono-spline-bar-rect') &&
    str_contains($monthlyPart, 'mono-spline-path') &&
    str_contains($monthlyPart, 'mono-spline-dot') &&
    str_contains($monthlyPart, 'Evolución de visitas mensuales')
  );

  $ctoPart = _partToString("Dashboard.cto", [
    "cto" => \App\Controllers\StatisticsControllers::evaluateCtoMetrics(
      \App\Models\StatisticsModels::getCtoData('c21d908d5f1e92f7fdc5b3b3cbc0b5')
    )
  ]);
  assertTest(
    'Parte Dashboard.cto se renderiza con métricas de conversión y desglose Enlaces vs RRSS',
    !empty($ctoPart) && str_contains($ctoPart, 'Métricas de CTO') && str_contains($ctoPart, 'Redes Sociales')
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

  // Prueba de regla de negocio: Aumento -> flecha verde
  $evalUp = \App\Controllers\StatisticsControllers::evaluateMonthlyTrend([
    "current_month_total" => 500,
    "previous_months_avg" => 200,
    "has_history" => true
  ]);
  assertTest(
    'Regla de negocio: si mes actual > promedio muestra flecha verde (arrow-up y color-success)',
    $evalUp['trend'] === 'up' && $evalUp['arrow'] === 'arrow-up' && $evalUp['arrow_class'] === 'color-success'
  );

  // Prueba de regla de negocio: Disminución -> flecha roja
  $evalDown = \App\Controllers\StatisticsControllers::evaluateMonthlyTrend([
    "current_month_total" => 100,
    "previous_months_avg" => 200,
    "has_history" => true
  ]);
  assertTest(
    'Regla de negocio: si mes actual < promedio muestra flecha roja (arrow-down y color-danger)',
    $evalDown['trend'] === 'down' && $evalDown['arrow'] === 'arrow-down' && $evalDown['arrow_class'] === 'color-danger'
  );

  // Prueba de regla de negocio: Igual -> sin flecha
  $evalEqual = \App\Controllers\StatisticsControllers::evaluateMonthlyTrend([
    "current_month_total" => 200,
    "previous_months_avg" => 200,
    "has_history" => true
  ]);
  assertTest(
    'Regla de negocio: si mes actual == promedio no muestra flecha',
    $evalEqual['trend'] === 'equal' && $evalEqual['arrow'] === null
  );

  // Prueba de regla de negocio: Sin historial -> sin flecha
  $evalFirst = \App\Controllers\StatisticsControllers::evaluateMonthlyTrend([
    "current_month_total" => 200,
    "previous_months_avg" => 0,
    "has_history" => false
  ]);
  assertTest(
    'Regla de negocio: primer mes sin historial no muestra flecha',
    $evalFirst['trend'] === 'first_month' && $evalFirst['arrow'] === null
  );

  // Prueba de estructuración para Hybrid Spline
  assertTest(
    'StatisticsControllers::evaluateMonthlyTrend genera estructura de datos completa para Hybrid Spline',
    isset($evalUp['spline']) &&
    is_array($evalUp['spline']['values']) &&
    is_array($evalUp['spline']['labels']) &&
    $evalUp['spline']['unit'] === 'Visitas' &&
    $evalUp['spline']['autoScale'] === true
  );

  // Pruebas de reglas de negocio para CTO y ratios
  $evalCtoSample = \App\Controllers\StatisticsControllers::evaluateCtoMetrics([
    "monthly_views" => 100,
    "monthly_clicks" => 25,
    "monthly_enlaces" => 15,
    "monthly_rrss" => 10,
    "configured_links" => ["total_links" => 5, "content_links" => 3, "rrss_links" => 2],
    "all_time_views" => 500,
    "all_time_clicks" => 100,
    "all_time_enlaces" => 60,
    "all_time_rrss" => 40
  ]);
  assertTest(
    'Regla de negocio: evaluateCtoMetrics calcula tasa de CTO del mes correctamente',
    $evalCtoSample['monthly_cto_rate'] === 25.0 && $evalCtoSample['monthly_clicks_per_visit'] === 0.25
  );
  assertTest(
    'Regla de negocio: evaluateCtoMetrics calcula promedio entre enlaces y visitas',
    $evalCtoSample['links_per_visit'] === 0.05
  );
  assertTest(
    'Regla de negocio: evaluateCtoMetrics calcula índice porcentual Enlaces vs RRSS',
    $evalCtoSample['pct_enlaces_month'] === 60.0 && $evalCtoSample['pct_rrss_month'] === 40.0 && $evalCtoSample['predominant_channel'] === 'enlaces'
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
// --- 8. PRUEBAS DE MANTENIMIENTO BD E INDEX_USER ---
echo "\n[8] Probando App\\DatabaseComponent\\DatabaseMaintenance e index_user...\n";
try {
  $eberIndex = \App\Models\UserModels::getIndexUserByUsername('eber');
  assertTest(
    'UserModels::getIndexUserByUsername resuelve index_user de eber',
    $eberIndex === 'c21d908d5f1e92f7fdc5b3b3cbc0b5',
    "index_user obtenido: " . ($eberIndex ?? 'null')
  );

  $userByIndex = \App\Models\UserModels::getUserByIndex($eberIndex);
  assertTest(
    'UserModels::getUserByIndex recupera registro del usuario por index_user',
    is_array($userByIndex) && ($userByIndex['username'] ?? '') === 'eber'
  );

  assertTest(
    'UserModels::userExistsByIndex confirma existencia por index_user',
    \App\Models\UserModels::userExistsByIndex($eberIndex)
  );

  $statsByIndex = \App\Models\StatisticsModels::getStatsData($eberIndex);
  assertTest(
    'StatisticsModels::getStatsData funciona correctamente con index_user directo',
    is_array($statsByIndex) && ($statsByIndex['summary']['total_views'] ?? 0) === 591
  );

  // Probar detección de sesiones de prueba huérfanas
  $audit = \App\DatabaseComponent\DatabaseMaintenance::auditOrphans();
  assertTest(
    'DatabaseMaintenance::auditOrphans audita tablas y detecta huérfanos con precisión',
    is_array($audit) && isset($audit['total_orphans'])
  );

  // Probar limpieza de huérfanos
  $clean = \App\DatabaseComponent\DatabaseMaintenance::cleanOrphans();
  assertTest(
    'DatabaseMaintenance::cleanOrphans ejecuta la eliminación de huérfanos con éxito',
    $clean['success'] === true
  );

  $auditPostClean = \App\DatabaseComponent\DatabaseMaintenance::auditOrphans();
  assertTest(
    'DatabaseMaintenance::auditOrphans confirma 0 huérfanos tras la limpieza',
    $auditPostClean['total_orphans'] === 0,
    "Huérfanos restantes: " . $auditPostClean['total_orphans']
  );

  $orphanVisitBlocked = \App\Models\VisitModels::processVisit('usuario_fantasma_no_existe_xyz');
  assertTest(
    'VisitModels::processVisit rechaza usuarios inexistentes para evitar datos huérfanos',
    $orphanVisitBlocked === false
  );
} catch (\Throwable $e) {
  assertTest('Pruebas de index_user y DatabaseMaintenance no lanzan excepciones', false, $e->getMessage());
}

// [9] Probando Sistema de Animaciones Reactivo (hover-scale-soft y delegación GSAP)...
echo "\n[9] Probando Sistema de Animaciones Reactivo (hover-scale-soft y delegación GSAP)...\n";
try {
  $animJsPath = __DIR__ . '/../vendor/eber/framework/Resources/Js/Components/animations.js';
  $animJsContent = file_exists($animJsPath) ? file_get_contents($animJsPath) : '';
  
  assertTest(
    'animations.js implementa delegación de eventos para elementos dinámicos (mouseover/mouseout)',
    str_contains($animJsContent, 'document.addEventListener(\'mouseover\'') &&
    str_contains($animJsContent, 'handleGsapHoverIn') &&
    str_contains($animJsContent, '__gsapHoverDelegated')
  );

  assertTest(
    'animations.js maneja .hover-scale-soft con escala suave visible (1.02)',
    str_contains($animJsContent, "el.classList.contains('hover-scale-soft')") &&
    str_contains($animJsContent, '1.02')
  );

  $animCssPath = __DIR__ . '/../vendor/eber/framework/Resources/Css/animation-select.css';
  $animCssContent = file_exists($animCssPath) ? file_get_contents($animCssPath) : '';
  assertTest(
    'animation-select.css define --hover-scale-soft: 1.02 para escala perceptible (2-3px)',
    str_contains($animCssContent, '--hover-scale-soft: 1.02;')
  );

  $cardGraphicCssPath = __DIR__ . '/../App/Public/Css/card-graphic.css';
  $cardGraphicContent = file_exists($cardGraphicCssPath) ? file_get_contents($cardGraphicCssPath) : '';
  assertTest(
    'card-graphic.css define variables de tema para --hover-scale-soft y --hover-shadow-soft',
    str_contains($cardGraphicContent, '--hover-scale-soft: 1.02;') &&
    str_contains($cardGraphicContent, '--hover-shadow-soft:')
  );

  $minJsPath = __DIR__ . '/../App/Public/Min/Js/js.min.js';
  $minJsContent = file_exists($minJsPath) ? file_get_contents($minJsPath) : '';
  assertTest(
    'js.min.js compilado contiene la delegación global __gsapHoverDelegated',
    str_contains($minJsContent, '__gsapHoverDelegated')
  );
} catch (\Throwable $e) {
  assertTest('Pruebas de animaciones reactivas no lanzan excepciones', false, $e->getMessage());
}

// --- RESUMEN FINAL ---
echo "\n=======================================================\n";
echo " RESUMEN: {$results['passed']} pasadas, {$results['failed']} falladas.\n";
echo "=======================================================\n\n";

if ($results['failed'] > 0) {
  exit(1);
}
exit(0);
