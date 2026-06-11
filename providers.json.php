<?php

/**
 * Cargador de providers desde providers.json
 * Lee la lista de providers desde providers.json en la raíz del proyecto
 */

$basePath = __DIR__;
$providersFile = $basePath . '/providers.json';

// Si no existe providers.json en la raíz, usar el del framework
if (!file_exists($providersFile)) {
  $providersFile = $basePath . '/vendor/eber/framework/providers.json';
}

if (file_exists($providersFile)) {
  $json = file_get_contents($providersFile);
  $config = json_decode($json, true);

  if (json_last_error() === JSON_ERROR_NONE && isset($config['providers'])) {
    return array_map(function ($provider) {
      // Convertir puntos a backslashes
      $namespace = str_replace('.', '\\', $provider);
      // Agregar el prefijo App\Providers\
      return 'App\\Providers\\' . $namespace;
    }, $config['providers']);
  }
}

// Fallback si providers.json no existe o tiene errores
return [];