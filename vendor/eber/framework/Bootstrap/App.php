<?php
// bootstrap/app.php
// Sistema de inicialización de la aplicación
// Este archivo carga todos los Service Providers registrados

/**
 * Ciclo de vida de los Providers:
 * 
 * 1. register() - Se ejecuta PRIMERO en todos los providers
 *    Usa para: registrar bindings, configuraciones, servicios básicos
 *    NO uses otros servicios aquí (podrían no estar disponibles aún)
 * 
 * 2. boot() - Se ejecuta DESPUÉS de que todos los providers se registraron
 *    Usa para: lógica que depende de otros servicios, cargar datos para vistas
$providers = \Core\ConfigLoader\ProviderLoader::load();
$instances = [];

// Fase 1: Instanciar y ejecutar register() en todos los providers
foreach ($providers as $providerClass) {
  if (class_exists($providerClass)) {
    $provider = new $providerClass();
    $instances[] = $provider;

    if (method_exists($provider, 'register')) {
      $provider->register();
    }
  }
}

// Fase 2: Ejecutar boot() en todos los providers (después de que todos se registraron)
foreach ($instances as $provider) {
  if (method_exists($provider, 'boot')) {
    $provider->boot();
  }
}
