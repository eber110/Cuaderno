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

?>

<div id="error-<?= $msg[1]?>" class="container-xl h-dvh flex-column center-center">
  <div class="card back8 br0 w100 flex-column center-center">

    <div class="w50 w-mid-90 w-sml-100 flex-between-column">

      <div class="text-center color2 x50 bold600 flex-row center-start gap10">
        <p class="x50 bold600 flex-row center-center"><?= $svg?></p>
        <p class="x50 bold600">Error</p>
        <p class="x50 bold600"><?= $msg[1]?>!</p>
      </div>
      <p class="mb10 m0 x25 color2 no-indent pt10 pb10"><?= $msg[2]?></p>

      <?php if (!empty($dbDiagnostic) && $isDevOrAdmin) : ?>
        <div class="p15 br10 back-card mt10 mb15 w100 text-left color3 x14" style="border: 1px solid rgba(255,100,100,0.3); font-family: monospace;">
          <p class="bold700 color-caution mb5">Diagnóstico Base de Datos (Solo visible para Administrador / Dev):</p>
          <ul style="margin: 0; padding-left: 20px;">
            <li><strong>Driver:</strong> <?= e($dbDiagnostic['driver']) ?></li>
            <li><strong>Ruta BD:</strong> <?= e($dbDiagnostic['database']) ?></li>
            <?php if ($dbDiagnostic['driver'] === 'sqlite') : ?>
              <li><strong>Archivo existe:</strong> <?= $dbDiagnostic['file_exists'] ? '<span class="color-success">Sí</span>' : '<span class="color-danger bold700">NO (archivo faltante en servidor)</span>' ?></li>
              <li><strong>Directorio escribible:</strong> <?= $dbDiagnostic['dir_writable'] ? '<span class="color-success">Sí</span>' : '<span class="color-danger bold700">NO (permiso denegado para www-data en carpeta Database/)</span>' ?></li>
              <li><strong>Archivo escribible:</strong> <?= $dbDiagnostic['file_writable'] ? '<span class="color-success">Sí</span>' : '<span class="color-danger bold700">NO (permiso denegado para www-data en archivo .sqlite)</span>' ?></li>
            <?php endif; ?>
            <?php if (!empty($dbDiagnostic['last_error'])) : ?>
              <li><strong>Último error PDO:</strong> <?= e($dbDiagnostic['last_error']) ?></li>
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
