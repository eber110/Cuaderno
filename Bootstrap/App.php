<?php

/**
 * Bootstrap de la aplicación.
 * Este archivo carga todos los Service Providers registrados.
 */

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