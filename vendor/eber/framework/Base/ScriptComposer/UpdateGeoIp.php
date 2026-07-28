<?php
namespace Base\ScriptComposer;

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use PharData;
use Exception;

class UpdateGeoIp
{
    public static function run()
    {
        echo "Iniciando actualizacion de la base de datos GeoIP...\n";

        // Cargar variables de entorno si existe .env
        $baseDir = realpath(__DIR__ . '/../../');
        if (file_exists($baseDir . '/.env')) {
            $dotenv = Dotenv::createImmutable($baseDir);
            $dotenv->load();
        }

        $licenseKey = $_ENV['MAXMIND_LICENSE_KEY'] ?? getenv('MAXMIND_LICENSE_KEY');

        if (empty($licenseKey)) {
            echo "ERROR: No se encontró la variable MAXMIND_LICENSE_KEY en tu archivo .env\n";
            echo "Por favor, regístrate en MaxMind, obtén una License Key gratuita y añádela a tu .env\n";
            exit(1);
        }

        $url = "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key={$licenseKey}&suffix=tar.gz";
        $tmpTarGz = $baseDir . '/Resources/dbLocation/GeoLite2-City.tar.gz';
        $tmpTar = $baseDir . '/Resources/dbLocation/GeoLite2-City.tar';
        $dbLocation = $baseDir . '/Resources/dbLocation/';
        $finalMmdbPath = $dbLocation . 'GeoLite2-City.mmdb';

        if (!is_dir($dbLocation)) {
            mkdir($dbLocation, 0755, true);
        }

        echo "Descargando la base de datos (esto puede tardar unos segundos)...\n";
        
        $context = stream_context_create([
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: PHP Script\r\n"
            ]
        ]);
        
        $tarGzContent = file_get_contents($url, false, $context);
        
        if ($tarGzContent === false) {
            echo "ERROR: Falló la descarga desde MaxMind. Verifica tu License Key o conexión a internet.\n";
            exit(1);
        }

        file_put_contents($tmpTarGz, $tarGzContent);
        echo "Descarga completada. Extrayendo archivos...\n";

        try {
            // Eliminar restos anteriores si existen
            if (file_exists($tmpTar)) @unlink($tmpTar);

            $phar = new PharData($tmpTarGz);
            $phar->decompress(); // Crea un archivo .tar

            $tar = new PharData($tmpTar);
            
            // Extraer a un directorio temporal
            $extractPath = $dbLocation . 'temp_extract';
            if (!is_dir($extractPath)) {
                mkdir($extractPath, 0755, true);
            }
            $tar->extractTo($extractPath, null, true);
            
            // Buscar el archivo .mmdb dentro de la carpeta extraída
            $mmdbFound = false;
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($extractPath));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'mmdb') {
                    copy($file->getRealPath(), $finalMmdbPath);
                    $mmdbFound = true;
                    break;
                }
            }

            // Limpieza
            self::deleteDir($extractPath);
            @unlink($tmpTarGz);
            @unlink($tmpTar);

            if ($mmdbFound) {
                echo "✅ Base de datos GeoLite2-City.mmdb instalada y actualizada correctamente.\n";
            } else {
                echo "❌ Se descargó y extrajo el archivo pero no se encontró un archivo .mmdb dentro.\n";
                exit(1);
            }

        } catch (Exception $e) {
            echo "ERROR al procesar el archivo comprimido: " . $e->getMessage() . "\n";
            // Limpieza de emergencia
            if (file_exists($tmpTarGz)) @unlink($tmpTarGz);
            if (file_exists($tmpTar)) @unlink($tmpTar);
            exit(1);
        }
    }

    private static function deleteDir($dirPath) {
        if (!is_dir($dirPath)) {
            return;
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }
}

// Ejecutar automáticamente al invocar el script
UpdateGeoIp::run();
