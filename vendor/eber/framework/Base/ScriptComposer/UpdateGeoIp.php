<?php
namespace Base\ScriptComposer;

// Asegurar autoloading correcto sin importar si se corre desde la raíz o dentro de vendor
$baseDir = getcwd();
if (file_exists($baseDir . '/vendor/autoload.php')) {
    require_once $baseDir . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

use Dotenv\Dotenv;
use PharData;
use Exception;

class UpdateGeoIp
{
    public static function run()
    {
        ini_set('memory_limit', '512M'); // Aumentar límite de memoria para extracción con PharData
        echo "Iniciando actualización de la base de datos GeoIP...\n";

        // Obtener la raíz del proyecto actual
        $baseDir = getcwd();
        if (file_exists($baseDir . '/.env')) {
            $dotenv = Dotenv::createImmutable($baseDir);
            $dotenv->load();
        } elseif (file_exists(__DIR__ . '/../../.env')) {
            $dotenv = Dotenv::createImmutable(realpath(__DIR__ . '/../../'));
            $dotenv->load();
        }

        $licenseKey = $_ENV['MAXMIND_LICENSE_KEY'] ?? getenv('MAXMIND_LICENSE_KEY');

        if (empty($licenseKey)) {
            echo "ERROR: No se encontró la variable MAXMIND_LICENSE_KEY en tu archivo .env\n";
            echo "Por favor, regístrate en MaxMind, obtén una License Key gratuita y añádela a tu .env\n";
            exit(1);
        }

        // Directorio Database en la raíz del proyecto
        $databaseDir = $baseDir . '/Database';
        if (!is_dir($databaseDir)) {
            if (!@mkdir($databaseDir, 0755, true)) {
                echo "ERROR: No se pudo crear el directorio: {$databaseDir}\n";
                exit(1);
            }
        }

        // Asegurar que exista .gitkeep en Database
        $gitkeep = $databaseDir . '/.gitkeep';
        if (!file_exists($gitkeep)) {
            @file_put_contents($gitkeep, "");
        }

        $url = "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key={$licenseKey}&suffix=tar.gz";
        $tmpTarGz = $databaseDir . '/GeoLite2-City.tar.gz';
        $tmpTar = $databaseDir . '/GeoLite2-City.tar';
        $finalMmdbPath = $databaseDir . '/GeoLite2-City.mmdb';

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
            
            // Extraer a un directorio temporal dentro de Database
            $extractPath = $databaseDir . '/temp_extract';
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
                echo "✅ Base de datos GeoLite2-City.mmdb instalada y actualizada correctamente en: {$finalMmdbPath}\n";
            } else {
                echo "❌ Se descargó y extrajo el archivo pero no se encontró un archivo .mmdb dentro.\n";
                exit(1);
            }

        } catch (Exception $e) {
            echo "ERROR al procesar el archivo comprimido: " . $e->getMessage() . "\n";
            // Limpieza de emergencia
            if (file_exists($tmpTarGz)) @unlink($tmpTarGz);
            if (file_exists($tmpTar)) @unlink($tmpTar);
            if (isset($extractPath) && is_dir($extractPath)) self::deleteDir($extractPath);
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
