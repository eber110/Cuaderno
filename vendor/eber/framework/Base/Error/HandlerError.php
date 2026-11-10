<?php

  $msg[] = "";//variable declarativa
  $svg = "";
  $svgConstruct = explode(".", $msg[5]);

  if (!isset($svgConstruct[1])) {

    $svgConstruct[1] = "color-caution";

  }

  if ($svgConstruct[0] == '') {

    $svg = svg('triangle-exclamation-fill', 'color-caution');

  }else{

    $svg = svg($svgConstruct[0], $svgConstruct[1]);

  }

  // Detección de sesión para diagnóstico administrativo seguro de BD
  $isDevOrAdmin = false;
  if (class_exists('\Base\Module\Session')) {
    $sessionUser = \Base\Module\Session::session_data('username');
    if ($sessionUser === 'eber' || \Base\Module\Session::role() === 'admin' || (defined('ENVIRONMENT') && in_array(ENVIRONMENT, ['dev', 'development', 'local'], true))) {
      $isDevOrAdmin = true;
    }
  }

  $dbDiagnostic = null;
  if ((string)($msg[1] ?? '') === '1052') {
    $dbDriver     = defined('DB_DRIVER') ? DB_DRIVER : 'desconocido';
    $dbPath       = defined('BD') ? BD : '';
    $lastError    = class_exists('\Core\Conexion') ? \Core\Conexion::getLastError() : null;
    $fileExists   = ($dbDriver === 'sqlite' && !empty($dbPath)) ? file_exists($dbPath) : null;
    $dirWritable  = ($dbDriver === 'sqlite' && !empty($dbPath)) ? is_writable(dirname($dbPath)) : null;
    $fileWritable = ($dbDriver === 'sqlite' && !empty($dbPath) && $fileExists) ? is_writable($dbPath) : null;

    $dbDiagnostic = [
      'driver'        => $dbDriver,
      'database'      => $dbPath,
      'file_exists'   => $fileExists,
      'dir_writable'  => $dirWritable,
      'file_writable' => $fileWritable,
      'last_error'    => $lastError,
    ];
    error_log('[DB_ERROR_1052_DIAGNOSTIC] ' . json_encode($dbDiagnostic));
  }

  $errorLabel      = function_exists('__') ? __('global.errors.title') : 'Error';
  $yesLabel        = function_exists('__') ? __('global.yes') : 'Sí';
  $diagTitle       = function_exists('__') ? __('global.errors.db_diag_title') : 'Diagnóstico Base de Datos (Solo visible para Administrador / Dev):';
  $driverLabel     = function_exists('__') ? __('global.errors.db_driver') : 'Driver:';
  $pathLabel       = function_exists('__') ? __('global.errors.db_path') : 'Ruta BD:';
  $existsLabel     = function_exists('__') ? __('global.errors.db_file_exists') : 'Archivo existe:';
  $missingLabel    = function_exists('__') ? __('global.errors.db_file_missing') : 'NO (archivo faltante en servidor)';
  $dirWritLabel    = function_exists('__') ? __('global.errors.db_dir_writable') : 'Directorio escribible:';
  $dirDeniedLabel  = function_exists('__') ? __('global.errors.db_dir_denied') : 'NO (permiso denegado para www-data en carpeta Database/)';
  $fileWritLabel   = function_exists('__') ? __('global.errors.db_file_writable') : 'Archivo escribible:';
  $fileDeniedLabel = function_exists('__') ? __('global.errors.db_file_denied') : 'NO (permiso denegado para www-data en archivo .sqlite)';
  $lastErrorLabel  = function_exists('__') ? __('global.errors.db_last_error') : 'Último error PDO:';

?>

<div id="error-<?= $msg[1]?>" class="container-xl h-dvh flex-column center-center">
  <div class="card back8 br0 w100 flex-column center-center">

    <div class="w50 w-mid-90 w-sml-100 flex-between-column">

      <div class="text-center color2 x50 bold600 flex-row center-start gap10">
        <p class="x50 bold600 flex-row center-center"><?= $svg?></p>
        <p class="x50 bold600"><?= e($errorLabel) ?></p>
        <p class="x50 bold600"><?= $msg[1]?>!</p>
      </div>
      <p class="mb10 m0 x25 color2 no-indent pt10 pb10"><?= $msg[2]?></p>

      <?php if (!empty($dbDiagnostic) && $isDevOrAdmin) : ?>
        <div class="p15 br10 back-card mt10 mb15 w100 text-left color3 x14" style="border: 1px solid rgba(255,100,100,0.3); font-family: monospace;">
          <p class="bold700 color-caution mb5"><?= e($diagTitle) ?></p>
          <ul style="margin: 0; padding-left: 20px;">
            <li><strong><?= e($driverLabel) ?></strong> <?= e($dbDiagnostic['driver']) ?></li>
            <li><strong><?= e($pathLabel) ?></strong> <?= e($dbDiagnostic['database']) ?></li>
            <?php if ($dbDiagnostic['driver'] === 'sqlite') : ?>
              <li><strong><?= e($existsLabel) ?></strong> <?= $dbDiagnostic['file_exists'] ? '<span class="color-success">' . e($yesLabel) . '</span>' : '<span class="color-danger bold700">' . e($missingLabel) . '</span>' ?></li>
              <li><strong><?= e($dirWritLabel) ?></strong> <?= $dbDiagnostic['dir_writable'] ? '<span class="color-success">' . e($yesLabel) . '</span>' : '<span class="color-danger bold700">' . e($dirDeniedLabel) . '</span>' ?></li>
              <li><strong><?= e($fileWritLabel) ?></strong> <?= $dbDiagnostic['file_writable'] ? '<span class="color-success">' . e($yesLabel) . '</span>' : '<span class="color-danger bold700">' . e($fileDeniedLabel) . '</span>' ?></li>
            <?php endif; ?>
            <?php if (!empty($dbDiagnostic['last_error'])) : ?>
              <li><strong><?= e($lastErrorLabel) ?></strong> <?= e($dbDiagnostic['last_error']) ?></li>
            <?php endif; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($msg[3] != '') : ?>
        <div class="btn-cancel x20 br50 bold400 mb5">
          <a href="<?= $msg[3]?>"><?= $msg[4]?></a>
        </div>
      <?php endif;?>

    </div>

  </div>
</div>
