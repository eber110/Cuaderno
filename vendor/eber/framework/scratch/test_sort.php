<?php
require 'vendor/autoload.php';

// Mock de ROOT_PATH para prueba
if (!defined('ROOT_PATH')) define('ROOT_PATH', __DIR__);

$config = [
  "priority" => ["aaSvgStore.js", "config.js"],
  "exclude" => [],
  "functions" => [
    "defer" => [
      "animations" => [],
      "toUp" => []
    ],
    "async" => []
  ]
];

file_put_contents(__DIR__ . '/jsConfig.json', json_encode($config, JSON_PRETTY_PRINT));

$files = [
    '/App/Public/Js/custom.js',
    '/App/Public/Js/animations.js',
    '/vendor/framework/Resources/Js/config.js',
    '/vendor/framework/Resources/Js/aaSvgStore.js',
    '/vendor/framework/Resources/Js/animations.js',
    '/vendor/framework/Resources/Js/toUp.js',
    '/vendor/framework/Resources/Js/unused.js'
];

function sortFilesByPriority(array $files, ?string $orderFile = null): array
{
    if ($orderFile === null) {
      $orderFile = ROOT_PATH . '/jsConfig.json';
    }
    // Si no existe el archivo de orden, devolver los archivos sin modificar
    if (!file_exists($orderFile)) {
      return $files;
    }

    $config = json_decode(file_get_contents($orderFile), true);

    if (!$config) {
      return $files;
    }

    $priority = $config['priority'] ?? [];
    $exclude = $config['exclude'] ?? [];

    $deferModules = array_keys($config['functions']['defer'] ?? []);
    $asyncModules = array_keys($config['functions']['async'] ?? []);

    $allowedFiles = $priority;
    foreach (array_merge($deferModules, $asyncModules) as $mod) {
        $filename = $mod . '.js';
        if (!in_array($filename, $allowedFiles)) {
            $allowedFiles[] = $filename;
        }
    }

    $priorityFiles = [];
    $remainingFiles = [];

    foreach ($files as $file) {
      $basename = basename($file);

      // CRUCE DE INFORMACIÓN: Filtrado estricto
      // Solo minificar los archivos que están explícitamente listados en jsConfig.json
      if (!in_array($basename, $allowedFiles)) {
          continue;
      }

      if (in_array($basename, $exclude)) {
          continue;
      }

      $priorityIndex = array_search($basename, $priority);

      if ($priorityIndex !== false) {
        if (!isset($priorityFiles[$priorityIndex])) {
            $priorityFiles[$priorityIndex] = $file;
        }
      } else {
        if (!isset($remainingFiles[$basename])) {
            $remainingFiles[$basename] = $file;
        }
      }
    }

    ksort($priorityFiles);
    ksort($remainingFiles);

    return array_merge(array_values($priorityFiles), array_values($remainingFiles));
}

$sorted = sortFilesByPriority($files);
print_r($sorted);
