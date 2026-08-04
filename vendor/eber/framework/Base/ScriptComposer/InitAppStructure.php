<?php

/**
 * Script para crear la estructura de directorios de /App
 * Se ejecuta automáticamente después de instalar con Composer
 * 
 * Uso en composer.json:
 * "scripts": {
 *   "post-install-cmd": ["php vendor/eber/framework/Base/ScriptComposer/InitAppStructure.php"],
 *   "post-update-cmd": ["php vendor/eber/framework/Base/ScriptComposer/InitAppStructure.php"]
 * }
 */

class InitAppStructure
{
  private $basePath;
  private $appPath;
  private $directories = [
    'App/Components',
    'App/Config',
    'App/Controllers',
    'App/Middleware',
    'App/Models',
    'App/Providers',
    'App/Public',
    'App/Route',
    'App/Segment',
    'App/Views',
    // Subdirectorios importantes
    'App/Middleware/MiddlewareInterface',
    'App/Public/Css',
    'App/Public/Img',
    'App/Public/Img/Custom',
    'App/Public/Img/Thumbnail',
    'App/Public/Js',
    'App/Public/Min',
    'App/Public/Min/Css',
    'App/Public/Min/Js',
    'App/Public/Uploads',
    'App/Segment/Form',
    'App/Segment/Menu',
    'App/Segment/Template',
    // Rsc (solo Fonts, Ico y Library de resources)
    'App/Rsc',
    'App/Rsc/Fonts',
    'App/Rsc/Ico',
    'App/Rsc/Library',
    // Vistas de error (independientes de App/Views)
    'App/errorViews',
    // Directorios de sistema (Caché, Logs y Uploads en la raíz)
    'Cache',
    'Logs',
    'Logs/VisitLog',
    'Uploads',
  ];

  private $isFrameworkRepo = false;

  public function __construct()
  {
    // Obtener la ruta base del proyecto
    $this->basePath = getcwd();
    $this->appPath = $this->basePath . '/App';

    // Detectar si estamos en el repositorio del framework mismo
    $composerJsonPath = $this->basePath . '/composer.json';
    if (file_exists($composerJsonPath)) {
      $composerData = json_decode(file_get_contents($composerJsonPath), true);
      if (($composerData['name'] ?? '') === 'eber/framework') {
        $this->isFrameworkRepo = true;
      }
    }
  }

  public function create()
  {
    echo "🔧 Iniciando estructura de directorios de /App...\n\n";

    $created = 0;
    $skipped = 0;

    foreach ($this->directories as $dir) {
      $fullPath = $this->basePath . '/' . $dir;

      if (is_dir($fullPath)) {
        echo "⏭️  Saltado: {$dir} (ya existe)\n";
        $skipped++;
      } else {
        if (@mkdir($fullPath, 0755, true)) {
          echo "✅ Creado: {$dir}\n";
          $created++;
        } else {
          echo "❌ Error: No se pudo crear {$dir}\n";
        }
      }
    }

    // Crear directorio Bootstrap
    $bootstrapPath = $this->basePath . '/Bootstrap';
    if (!is_dir($bootstrapPath)) {
      if (@mkdir($bootstrapPath, 0755, true)) {
        echo "✅ Creado: Bootstrap\n";
        $created++;
      } else {
        echo "❌ Error: No se pudo crear Bootstrap\n";
      }
    } else {
      echo "⏭️  Saltado: Bootstrap (ya existe)\n";
      $skipped++;
    }

    // Copiar archivos de Resources del framework al proyecto
    $this->copyResources();

    // Crear archivos necesarios
    $this->createBootstrapFiles();
    $this->createConfigFiles();
    $this->createCookieConfig();
    $this->createCorsConfig();
    $this->createTokenConfig();
    $this->createLoadLibraryJsConfig();
    $this->createProvidersFiles();
    $this->createEnvFiles();
    $this->createJsConfig();
    $this->createHtaccess();
    $this->createIndexPhp();
    $this->createGitignore();
    $this->copyErrorView();
    $this->createThemeConfig();
    $this->copyAgentsFile();

    echo "\n📊 Resumen:\n";
    echo "   Directorios creados: {$created}\n";
    echo "   Directorios existentes: {$skipped}\n";
    echo "\n✨ ¡Estructura de /App lista!\n";

    return true;
  }

  /**
   * Copia los archivos de Resources del framework al directorio App/Resources/ del proyecto.
   */
  private function copyResources()
  {
    $frameworkResources = __DIR__ . '/../../Resources';
    $projectRsc = $this->basePath . '/App/Rsc';

    if (!is_dir($frameworkResources)) {
      echo "⚠️  Directorio Resources del framework no encontrado: {$frameworkResources}\n";
      return;
    }

    if (!is_dir($projectRsc)) {
      echo "⚠️  Directorio App/Rsc no existe, creándolo...\n";
      @mkdir($projectRsc, 0755, true);
    }

    $copied = 0;
    $skipped = 0;

    // Copiar Fonts
    $srcFonts = $frameworkResources . '/Fonts';
    $destFonts = $projectRsc . '/Fonts';
    if (is_dir($srcFonts)) {
      $this->copyDirectory($srcFonts, $destFonts, $copied, $skipped);
    }

    // Copiar Ico
    $srcIco = $frameworkResources . '/Ico';
    $destIco = $projectRsc . '/Ico';
    if (is_dir($srcIco)) {
      $this->copyDirectory($srcIco, $destIco, $copied, $skipped);
    }

    // Copiar Library
    $srcLibrary = $frameworkResources . '/Library';
    $destLibrary = $projectRsc . '/Library';
    if (is_dir($srcLibrary)) {
      $this->copyDirectory($srcLibrary, $destLibrary, $copied, $skipped);
    }

    echo "\n📦 Recursos (Fonts, Ico y Library) copiados: {$copied} archivos, {$skipped} existentes\n";
  }

  /**
   * Copia recursivamente un directorio.
   */
  private function copyDirectory(string $source, string $dest, int &$copied, int &$skipped)
  {
    $dir = opendir($source);
    if (!$dir) {
      return;
    }

    @mkdir($dest, 0755, true);

    while (($file = readdir($dir)) !== false) {
      if ($file === '.' || $file === '..') {
        continue;
      }

      $srcFile = $source . DIRECTORY_SEPARATOR . $file;
      $destFile = $dest . DIRECTORY_SEPARATOR . $file;

      if (is_dir($srcFile)) {
        $this->copyDirectory($srcFile, $destFile, $copied, $skipped);
      } else {
        // No sobrescribir archivos existentes en el proyecto
        if (file_exists($destFile)) {
          $skipped++;
        } else {
          if (@copy($srcFile, $destFile)) {
            $copied++;
          }
        }
      }
    }
    closedir($dir);
  }

  private function createBootstrapFiles()
  {
    $appPhpPath = $this->basePath . '/Bootstrap/App.php';

    if (file_exists($appPhpPath)) {
      $content = file_get_contents($appPhpPath);
      if (str_contains($content, "require_once __DIR__ . '/../providers.json.php';") || str_contains($content, "require_once __DIR__ . '/..//providers.json.php';")) {
        $content = str_replace(
          [
            "\$providers = require_once __DIR__ . '/../providers.json.php';",
            "\$providers = require_once __DIR__ . '/..//providers.json.php';"
          ],
          "\$providers = \\Core\\ConfigLoader\\ProviderLoader::load();",
          $content
        );
        if (file_put_contents($appPhpPath, $content)) {
          echo "🔧 Actualizado: Bootstrap/App.php para usar ProviderLoader\n";
        }
      } else {
        echo "⏭️  Saltado: Bootstrap/App.php (ya existe y está actualizado)\n";
      }
      return;
    }

    $content = <<<'PHP'
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
PHP;

    if (file_put_contents($appPhpPath, $content)) {
      echo "✅ Creado: Bootstrap/App.php\n";
    } else {
      echo "❌ Error: No se pudo crear Bootstrap/App.php\n";
    }
  }

  private function createConfigFiles()
  {
    $oldConfigPath = $this->basePath . '/config.php';
    $newConfigPath = $this->basePath . '/App/Config/config.php';

    // Asegurar que el directorio App/Config existe
    if (!is_dir(dirname($newConfigPath))) {
      @mkdir(dirname($newConfigPath), 0755, true);
    }

    // Si existe el antiguo config.php, trasladarlo y corregir su basePath
    if (file_exists($oldConfigPath) && !file_exists($newConfigPath) && !$this->isFrameworkRepo) {
      $configContent = file_get_contents($oldConfigPath);
      $configContent = str_replace(
        ['$basePath = __DIR__;', '$basePath = __DIR__ ;'],
        '$basePath = dirname(__DIR__, 2);',
        $configContent
      );
      if (file_put_contents($newConfigPath, $configContent)) {
        @unlink($oldConfigPath);
        echo "🚚 Trasladado y corregido: config.php a App/Config/config.php\n";
      } else {
        echo "❌ Error: No se pudo trasladar config.php a App/Config/config.php\n";
      }
    }

    // Si ya existe el nuevo config.php
    if (file_exists($newConfigPath)) {
      // Validar y corregir si tiene el basePath erróneo de la migración previa
      $configContent = file_get_contents($newConfigPath);
      if (str_contains($configContent, '$basePath = __DIR__;')) {
        $configContent = str_replace(
          ['$basePath = __DIR__;', '$basePath = __DIR__ ;'],
          '$basePath = dirname(__DIR__, 2);',
          $configContent
        );
        file_put_contents($newConfigPath, $configContent);
        echo "🔧 Corregido: basePath en App/Config/config.php\n";
      }

      echo "⏭️  Saltado: App/Config/config.php (ya existe)\n";
      // Eliminar el antiguo config.php si quedó duplicado en la raíz (excepto en el repo del framework)
      if (file_exists($oldConfigPath) && !$this->isFrameworkRepo) {
        @unlink($oldConfigPath);
      }
      return;
    }

    $content = <<<'PHP'
<?php

/**
 * Configuración de la aplicación.
 * Este archivo carga las variables de entorno y la configuración del framework.
 */

use Dotenv\Dotenv;

// Ruta base del proyecto
$basePath = dirname(__DIR__, 2);

// Intentar cargar .env desde la raíz del proyecto
if (file_exists($basePath . '/.env')) {
    $dotenv = Dotenv::createImmutable($basePath);
    $dotenv->load();
}
// Si no existe en la raíz, intentar desde el framework
elseif (file_exists($basePath . '/vendor/eber/framework/.env')) {
    $dotenv = Dotenv::createImmutable($basePath . '/vendor/eber/framework');
    $dotenv->load();
}

// Cargar configuración del framework
require_once $basePath . '/vendor/eber/framework/config.php';
PHP;

    if (file_put_contents($newConfigPath, $content)) {
      echo "✅ Creado: App/Config/config.php\n";
    } else {
      echo "❌ Error: No se pudo crear App/Config/config.php\n";
    }

    // Eliminar el config.php residual en la raíz por si acaso (excepto en el repo del framework)
    if (file_exists($oldConfigPath) && !$this->isFrameworkRepo) {
      @unlink($oldConfigPath);
    }
  }

  private function createCookieConfig()
  {
    $cookieConfigPath = $this->basePath . '/App/Config/CookieConfiguration.php';

    if (file_exists($cookieConfigPath)) {
      echo "⏭️  Saltado: App/Config/CookieConfiguration.php (ya existe)\n";
      return;
    }

    $content = <<<'PHP'
<?php

use Base\Module\CookieModule;

/**
 * Creación de cookies del sitio.
 * En esta sección se configura todas las cookies del sitio 
 * para que estén disponibles al inicio de la aplicación.
 */
//$cookie = CookieModule::set("NombreDeLaCookie", ["expired" => TIME_YEAR]);
PHP;

    if (file_put_contents($cookieConfigPath, $content)) {
      echo "✅ Creado: App/Config/CookieConfiguration.php\n";
    } else {
      echo "❌ Error: No se pudo crear App/Config/CookieConfiguration.php\n";
    }
  }

  private function createCorsConfig()
  {
    $corsConfigPath = $this->basePath . '/App/Config/CorsConfiguration.php';

    if (file_exists($corsConfigPath)) {
      echo "⏭️  Saltado: App/Config/CorsConfiguration.php (ya existe)\n";
      return;
    }

    $content = <<<'PHP'
<?php

/**
 * Configuración de CORS (Cross-Origin Resource Sharing).
 * 
 * Permite definir políticas de acceso interdominio (CORS) diferenciadas
 * para múltiples endpoints o microservicios de tu aplicación.
 * 
 * =========================================================================
 * EXPLICACIÓN DE PROPIEDADES DISPONIBLES EN CADA PERFIL:
 * =========================================================================
 * - 'enabled': (bool) Habilita o deshabilita CORS para el patrón de ruta.
 * - 'allowed_origins': (array) Dominios de origen permitidos para realizar peticiones.
 *                      Ejemplo: ['https://tusitio.com', 'https://admin.tusitio.com']
 *                      Usa ['*'] para permitir peticiones desde cualquier origen.
 * - 'allowed_methods': (array) Métodos HTTP autorizados en la petición CORS.
 *                      Ejemplo: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
 * - 'allowed_headers': (array) Cabeceras HTTP permitidas en la petición.
 *                      Ejemplo: ['Content-Type', 'Authorization', 'X-Requested-With']
 * - 'exposed_headers': (array) Cabeceras que la respuesta expone y el cliente puede leer.
 * - 'supports_credentials': (bool) Establece en true si necesitas dar soporte a credenciales,
 *                           cookies de sesión o autorización HTTP. 
 *                           IMPORTANTE: Si está en true, 'allowed_origins' NO puede ser ['*'].
 *                           Debes colocar los dominios autorizados de forma explícita.
 * - 'max_age': (int) Segundos que el navegador del cliente puede cachear la respuesta
 *              de una solicitud de pre-vuelo (preflight/OPTIONS) sin repetir la consulta.
 * 
 * =========================================================================
 * EJEMPLO DE CONFIGURACIÓN DE MULTIPLES ENDPOINTS (COPIAR SI SE NECESITA):
 * =========================================================================
 * return [
 *     'enabled' => true,
 *     'paths' => [
 *         // Perfil de API interna (más seguro con credenciales)
 *         'api/v1/*' => [
 *             'allowed_origins' => ['https://mi-aplicacion.com'],
 *             'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
 *             'allowed_headers' => ['Content-Type', 'Authorization'],
 *             'supports_credentials' => true,
 *             'max_age' => 86400,
 *         ],
 *         // Perfil de API pública (solo lectura y abierto a cualquier origen)
 *         'public/*' => [
 *             'allowed_origins' => ['*'],
 *             'allowed_methods' => ['GET', 'OPTIONS'],
 *             'allowed_headers' => ['Content-Type'],
 *             'supports_credentials' => false,
 *             'max_age' => 3600,
 *         ],
 *         // Fallback general para cualquier otra ruta no especificada
 *         '*' => [
 *             'allowed_origins' => ['*'],
 *             'allowed_methods' => ['GET', 'POST', 'OPTIONS'],
 *             'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
 *             'supports_credentials' => false,
 *             'max_age' => 86400,
 *         ]
 *     ]
 * ];
 */

return [
    // CORS desactivado por defecto. Actívalo definiendo tus rutas y orígenes aquí.
    'enabled' => false,
    
    'paths' => [
        // Define tus patrones de ruta y configuraciones específicas
    ]
];
PHP;

    if (file_put_contents($corsConfigPath, $content)) {
      echo "✅ Creado: App/Config/CorsConfiguration.php\n";
    } else {
      echo "❌ Error: No se pudo crear App/Config/CorsConfiguration.php\n";
    }
  }

  private function createTokenConfig()
  {
    $tokenConfigPath = $this->basePath . '/App/Config/TokenConfiguration.php';

    if (file_exists($tokenConfigPath)) {
      echo "⏭️  Saltado: App/Config/TokenConfiguration.php (ya existe)\n";
      return;
    }

    $content = <<<'PHP'
<?php

/**
 * Configuración del Módulo de Tokens y JWT.
 * 
 * Permite definir los perfiles de tokens firmados (como correos, recuperación de clave)
 * y los parámetros para JWT (JSON Web Tokens) de sesión persistente.
 * 
 * =========================================================================
 * RECORDATORIO RÁPIDO DE USO / TUTORIAL DE INTEGRACIÓN:
 * =========================================================================
 * 
 * 1. CÓMO AGREGAR UN PERFIL:
 *    Simplemente añade una clave bajo el array 'profiles'. Cada perfil define
 *    su propio tiempo de expiración ('expiration') en segundos y el algoritmo
 *    de firma ('algo').
 * 
 * 2. CÓMO USAR EN UN CONTROLADOR (CREAR Y ENVIAR):
 *    Cuando un usuario realiza una acción (ej. registrarse), creas el token:
 *    
 *    $data = ['userId' => 123, 'email' => 'user@example.com'];
 *    $token = \Base\Module\TokenModule::from('emails')->create($data);
 *    
 *    // Envías por correo el enlace:
 *    $link = DOMAIN . "verificar-cuenta?token=" . $token;
 * 
 * 3. CÓMO VALIDAR (PÁGINA DE ATERRIZAJE / CONTROLADOR):
 *    Cuando el usuario hace click en el enlace, lees el token GET y lo validas:
 * 
 *    $token = $_GET['token'] ?? '';
 *    $datos = \Base\Module\TokenModule::from('emails')->validate($token);
 *    
 *    if ($datos === false) {
 *        // Token alterado, corrupto o ya expirado. Denegar acción.
 *    } else {
 *        // Acceso permitido. Los datos son válidos e íntegros.
 *        $userId = $datos['userId'];
 *    }
 * 
 * 4. PERSISTENCIA DE SESIÓN CON JWT:
 *    - Crear en el Login (Controlador):
 *      $jwt = \Base\Module\TokenModule::configJWT(['id' => 123, 'role' => 'admin']);
 *      \Base\Module\CookieModule::set('auth_token', ['value' => $jwt, 'httponly' => true, 'secure' => true]);
 * 
 *    - Validar en el acceso (Middleware):
 *      $jwt = \Base\Module\CookieModule::get('auth_token');
 *      if ($jwt && $user = \Base\Module\TokenModule::validateJWT($jwt)) {
 *          // Sesión autorizada, pasar $user al request
 *      }
 */
return [
    // Perfiles personalizados de tokens firmados autocontenidos
    'profiles' => [
        'emails' => [
            'expiration' => 3600, // 1 hora
            'algo' => 'sha256',   // Algoritmo de firma hmac
        ],
        'recovery' => [
            'expiration' => 900,  // 15 minutos
            'algo' => 'sha256',
        ],
    ],

    // Parámetros de JSON Web Tokens (JWT) para persistencia de sesión
    'jwt' => [
        'expiration' => 604800, // 7 días (en segundos)
    ]
];
PHP;

    if (file_put_contents($tokenConfigPath, $content)) {
      echo "✅ Creado: App/Config/TokenConfiguration.php\n";
    } else {
      echo "❌ Error: No se pudo crear App/Config/TokenConfiguration.php\n";
    }
  }

  private function createLoadLibraryJsConfig()
  {
    $libConfigPath = $this->basePath . '/App/Config/loadLibraryJsConfiguration.php';

    if (file_exists($libConfigPath)) {
      echo "⏭️  Saltado: App/Config/loadLibraryJsConfiguration.php (ya existe)\n";
      return;
    }

    $content = <<<'PHP'
<?php

/**
 * Configuración de librerías JS y CSS para carga automática en el <head>.
 * 
 * Define las carpetas dentro de /App/Rsc/Library/ que deben cargarse.
 * Si el nombre de la carpeta de una librería no está en este array o está comentado, no se cargará.
 * El orden en este array determina el orden de inyección en el HTML.
 * 
 * Ejemplo:
 * return [
 *     'Gsap',
 *     'ApexCharts',
 * ];
 */

return [
    'Gsap',
];
PHP;

    if (file_put_contents($libConfigPath, $content)) {
      echo "✅ Creado: App/Config/loadLibraryJsConfiguration.php\n";
    } else {
      echo "❌ Error: No se pudo crear App/Config/loadLibraryJsConfiguration.php\n";
    }
  }

  private function createProvidersFiles()
  {
    $providersJsonPath = $this->basePath . '/providers.json';
    $providersJsonPhpPath = $this->basePath . '/providers.json.php';

    // Eliminar providers.json.php si existe en la raíz ya que se usa la clase ProviderLoader
    if (file_exists($providersJsonPhpPath) && !$this->isFrameworkRepo) {
      if (@unlink($providersJsonPhpPath)) {
        echo "🗑️  Eliminado: providers.json.php residual de la raíz\n";
      }
    }

    // providers.json
    if (!file_exists($providersJsonPath)) {
      $content = json_encode(['providers' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
      if (file_put_contents($providersJsonPath, $content)) {
        echo "✅ Creado: providers.json\n";
      } else {
        echo "❌ Error: No se pudo crear providers.json\n";
      }
    } else {
      echo "⏭️  Saltado: providers.json (ya existe)\n";
    }
  }

  private function createEnvFiles()
  {
    $envPath = $this->basePath . '/.env';
    $envExamplePath = $this->basePath . '/.env.example';

    // Buscar .env.example en el framework
    $frameworkEnvExample = __DIR__ . '/../../.env.example';

    if (!file_exists($envPath)) {
      // Si existe un .env.example en el framework, copiarlo como base
      if (file_exists($frameworkEnvExample)) {
        if (@copy($frameworkEnvExample, $envPath)) {
          echo "✅ Creado: .env (desde plantilla del framework)\n";
        } else {
          echo "❌ Error: No se pudo crear .env\n";
        }
      } else {
        echo "⏭️  Saltado: .env (no se encontró plantilla)\n";
      }
    } else {
      echo "⏭️  Saltado: .env (ya existe)\n";
    }

    if (file_exists($frameworkEnvExample)) {
      if (@copy($frameworkEnvExample, $envExamplePath)) {
        echo "✅ Actualizado: .env.example (con la plantilla más reciente del framework)\n";
      } else {
        echo "❌ Error: No se pudo actualizar .env.example\n";
      }
    }
  }

  private function createJsConfig()
  {
    $jsConfigPath = $this->basePath . '/jsConfig.json';

    if (file_exists($jsConfigPath)) {
      echo "⏭️  Saltado: jsConfig.json (ya existe)\n";
      return;
    }

    $content = json_encode([
      'priority' => ['aaSvgStore.js', 'component.js', 'config.js', 'querys.js'],
      'exclude' => [],
      'functions' => [
        'defer' => new \stdClass(),
        'async' => new \stdClass()
      ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if (file_put_contents($jsConfigPath, $content)) {
      echo "✅ Creado: jsConfig.json\n";
    } else {
      echo "❌ Error: No se pudo crear jsConfig.json\n";
    }
  }

  private function createHtaccess()
  {
    $htaccessPath = $this->basePath . '/.htaccess';

    if (file_exists($htaccessPath)) {
      echo "⏭️  Saltado: .htaccess (ya existe)\n";
      return;
    }

    $content = <<<'HTACCESS'
<IfModule mod_expires.c>
  ExpiresActive On

  # Fuentes Tipográficas (1 año de caché)
  ExpiresByType application/font-woff2 "access plus 1 year"
  ExpiresByType application/font-woff "access plus 1 year"
  ExpiresByType application/font-ttf "access plus 1 year"
  ExpiresByType application/x-font-ttf "access plus 1 year"
  ExpiresByType application/vnd.ms-fontobject "access plus 1 year"
  ExpiresByType font/ttf "access plus 1 year"
  ExpiresByType font/otf "access plus 1 year"
  ExpiresByType font/woff2 "access plus 1 year"

  # CSS y JavaScript (1 mes de caché)
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType text/javascript "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
  ExpiresByType application/x-javascript "access plus 1 month"

  # Iconos e Imágenes (1 año de caché)
  ExpiresByType image/svg+xml "access plus 1 month"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/gif "access plus 1 year"
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType image/avif "access plus 1 year"
  ExpiresByType image/x-icon "access plus 1 year"
</IfModule>

<IfModule mod_headers.c>
  # Para asegurar que los proxies también cacheen los recursos
  Header append Cache-Control "public"
</IfModule>

<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
</IfModule>

Options -MultiViews
RewriteEngine On

# --- PROTECCIÓN CONTRA HOTLINKING ---
# Evita que otros sitios web roben ancho de banda incrustando tus imágenes.
# Descomenta las siguientes líneas si deseas habilitarlo:
# RewriteCond %{HTTP_REFERER} !^$
# RewriteCond %{HTTP_REFERER} !^https?://([^/]+\.)?%{HTTP_HOST} [NC]
# RewriteCond %{HTTP_REFERER} !google\. [NC]
# RewriteCond %{HTTP_REFERER} !bing\. [NC]
# RewriteCond %{HTTP_REFERER} !yahoo\. [NC]
# RewriteRule \.(jpg|jpeg|png|gif|webp|svg|avif|ico)$ - [F,NC]

RewriteCond %{REQUEST_FILENAME} !-s
RewriteCond %{REQUEST_FILENAME} !-l
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [QSA,L]
HTACCESS;

    if (file_put_contents($htaccessPath, $content)) {
      echo "✅ Creado: .htaccess\n";
    } else {
      echo "❌ Error: No se pudo crear .htaccess\n";
    }
  }

  private function createIndexPhp()
  {
    $indexPath = $this->basePath . '/index.php';

    if (file_exists($indexPath)) {
      // Si estamos en el repo del framework, no actualizar index.php
      if ($this->isFrameworkRepo) {
        echo "⏭️  Saltado: index.php (ya existe y estamos en el repositorio del framework)\n";
        return;
      }

      $content = file_get_contents($indexPath);
      if (str_contains($content, "require_once __DIR__ . '/config.php';")) {
        $content = str_replace(
          "require_once __DIR__ . '/config.php';",
          "require_once __DIR__ . '/App/Config/config.php';",
          $content
        );
        if (file_put_contents($indexPath, $content)) {
          echo "🔧 Actualizado: index.php para usar App/Config/config.php\n";
        }
      } else {
        echo "⏭️  Saltado: index.php (ya existe y está actualizado)\n";
      }
      return;
    }

    $content = <<<'PHP'
<?php

define('ROOT_PATH', str_replace('\\', '/', __DIR__));

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/App/Config/config.php';

if (defined('ENVIRONMENT') && ENVIRONMENT === 'production' && defined('FORCE_DOMAIN') && !empty(FORCE_DOMAIN)) {
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    if ($currentHost !== FORCE_DOMAIN) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $protocol . FORCE_DOMAIN . $_SERVER['REQUEST_URI']);
        exit();
    }
}

require_once __DIR__ . '/Bootstrap/App.php';

\Core\Route::run();
PHP;

    if (file_put_contents($indexPath, $content)) {
      echo "✅ Creado: index.php\n";
    } else {
      echo "❌ Error: No se pudo crear index.php\n";
    }
  }

  private function createGitignore()
  {
    $gitignorePath = $this->basePath . '/.gitignore';

    if (file_exists($gitignorePath)) {
      echo "⏭️  Saltado: .gitignore (ya existe)\n";
      return;
    }

    $content = <<<'GITIGNORE'
/vendor/
.env
/App/Public/Min/
/cache/
/logs/
/Uploads/
GITIGNORE;

    if (file_put_contents($gitignorePath, $content)) {
      echo "✅ Creado: .gitignore\n";
    } else {
      echo "❌ Error: No se pudo crear .gitignore\n";
    }
  }

  private function copyErrorView()
  {
    $source = __DIR__ . '/../Error/HandlerError.php';
    $dest = $this->basePath . '/App/errorViews/HandlerError.php';

    if (file_exists($dest)) {
      echo "⏭️  Saltado: App/errorViews/HandlerError.php (ya existe)\n";
      return;
    }

    if (!file_exists($source)) {
      echo "⚠️  No se encontró el template de error en: {$source}\n";
      return;
    }

    if (@copy($source, $dest)) {
      echo "✅ Creado: App/errorViews/HandlerError.php\n";
    } else {
      echo "❌ Error: No se pudo crear App/errorViews/HandlerError.php\n";
    }
  }

  private function createThemeConfig()
  {
    $themeConfigPath = $this->basePath . '/App/Public/Css/_configTheme.css';

    if (file_exists($themeConfigPath)) {
      echo "⏭️  Saltado: App/Public/Css/_configTheme.css (ya existe)\n";
      return;
    }

    $content = <<<'CSS'
/***VAR***/

:root {
  --font: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  --font2: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  --font3: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  --font-size: 20px;
  --width-window: 1500px;
  --scrollbar-width: thin;
  --scrollbar-hide: none;

  --body: #FFF9E5;

  --text-primary:   #00124D;
  --text-secondary: #FFF9E5;
  --text-inactive:   #bdbdbd;

  /*Config color site*/
  --back-color: #FFC7B3;
  --back-color2: #B3FFED;
  --back-color3: #B3C4FF;
  --back-color4: #00FFC3;
  --back-color5: #FF4400;
  --back-color6: #003CFF;
  --back-color7: #B3EBFF;
  --back-color8: #00124D;

  --back-color-hover: oklch(from var(--back-color) calc(l * 1.2) calc(c - 0.02) h);
  --back-color2-hover: oklch(from var(--back-color2) calc(l * 1.2) calc(c - 0.02) h);
  --back-color3-hover: oklch(from var(--back-color3) calc(l * 1.2) calc(c - 0.02) h);
  --back-color4-hover: oklch(from var(--back-color4) calc(l * 1.2) calc(c - 0.02) h);
  --back-color5-hover: oklch(from var(--back-color5) calc(l * 1.2) calc(c - 0.02) h);
  --back-color6-hover: oklch(from var(--back-color6) calc(l * 1.2) calc(c - 0.02) h);
  --back-color7-hover: oklch(from var(--back-color7) calc(l * 1.2) calc(c - 0.02) h);
  --back-color8-hover: oklch(from var(--back-color8) calc(l * 1.2) calc(c - 0.02) h);

  --success: #00b900;
  --hover-success: #04ea04;

  --caution: #ff8c00;
  --hover-caution: #fead4b;

  --danger: #ff0000;
  --hover-danger: #fc6161;

  --sepia: sepia(40%);

  --transition: all 0.15s ease-in-out;
  --transition-slow: all 0.25s ease-in-out;
  
}

/* Modo oscuro - sobrescribe la paleta de colores */
[data-theme="dark"] {

  --body: #092743;

  --text-primary:   #e6f3ff;
  --text-secondary: #051c31;
  --text-inactive:   #bdbdbd;

  --back-color:  #0C345A;
  --back-color2: #124E87;
  --back-color3: #1869B4;
  --back-color4: #4B9CE7;
  --back-color5: #78B4ED;
  --back-color6: #A5CDF3;
  --back-color7: #432509;
  --back-color8: #F6D8BC;

}
CSS;

    if (file_put_contents($themeConfigPath, $content)) {
      echo "✅ Creado: App/Public/Css/_configTheme.css\n";
    } else {
      echo "❌ Error: No se pudo crear App/Public/Css/_configTheme.css\n";
    }
  }

  /**
   * Copia el AGENTS.md del framework al proyecto nuevo.
   * Así todos los agentes y desarrolladores del proyecto siguen las mismas convenciones.
   */
  private function copyAgentsFile()
  {
    $source = __DIR__ . '/../../AGENTS_PROYECTO.md';
    $dest = $this->basePath . '/AGENTS.md';

    if ($this->isFrameworkRepo) {
      echo "⏭️  Saltado: AGENTS.md (repo del framework, usa su propia guía)\n";
      return;
    }

    if (file_exists($dest)) {
      echo "⏭️  Saltado: AGENTS.md (ya existe)\n";
      return;
    }

    if (!file_exists($source)) {
      echo "⚠️  No se encontró el template AGENTS.md en: {$source}\n";
      return;
    }

    if (@copy($source, $dest)) {
      echo "✅ Creado: AGENTS.md (guía de trabajo del proyecto)\n";
    } else {
      echo "❌ Error: No se pudo crear AGENTS.md\n";
    }
  }

  public static function run()
  {
    $init = new self();
    return $init->create();
  }
}

// Ejecutar si se llama directamente
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
  InitAppStructure::run();
}
