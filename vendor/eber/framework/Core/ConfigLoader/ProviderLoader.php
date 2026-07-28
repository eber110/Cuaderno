<?php

namespace Core\ConfigLoader;

class ProviderLoader
{
    /**
     * Carga y devuelve la lista completa de clases de Service Providers.
     * Lee desde providers.json en la raíz del proyecto.
     * 
     * @return array
     */
    public static function load(): array
    {
        // En algunos entornos ROOT_PATH no se define con diagonal final. Asegurar ruta correcta.
        $providersFile = rtrim(ROOT_PATH, '/\\') . '/providers.json';

        // Si no existe providers.json en la raíz, usar el del framework
        if (!file_exists($providersFile)) {
            $providersFile = rtrim(FRAMEWORK_PATH, '/\\') . '/providers.json';
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

        return [];
    }
}
