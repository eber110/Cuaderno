<?php

/**
 * Configuración principal de la aplicación - Eber Framework.
 * 
 * Las variables configurables provienen de .env.
 * Este archivo define constantes derivadas y de estructura.
 */

// ==================== ROOT PATH ====================
if (!defined('ROOT_PATH')) {
    $rootPath = file_exists(dirname(__DIR__, 3) . '/vendor')
        ? dirname(__DIR__, 3)
        : __DIR__;
    define('ROOT_PATH', str_replace('\\', '/', $rootPath));
}

// ==================== FRAMEWORK PATH ====================
if (!defined('FRAMEWORK_PATH')) {
    define('FRAMEWORK_PATH', rtrim(str_replace('\\', '/', __DIR__), '/') . '/');
}

// ==================== DATABASE ====================
if (!defined('NAMESERVER')) define('NAMESERVER', $_ENV['DB_HOST'] ?? '127.0.0.1');
if (!defined('DB_HOST')) define('DB_HOST', NAMESERVER);
if (!defined('DB_PORT')) define('DB_PORT', $_ENV['DB_PORT'] ?? '');
if (!defined('USER')) define('USER', $_ENV['DB_USERNAME'] ?? 'root');
if (!defined('DB_USERNAME')) define('DB_USERNAME', USER);
if (!defined('PASS')) define('PASS', $_ENV['DB_PASSWORD'] ?? '');
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', PASS);
if (!defined('BD')) define('BD', $_ENV['DB_DATABASE'] ?? '');
if (!defined('DB_DATABASE')) define('DB_DATABASE', BD);
if (!defined('CHARSET')) define('CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');
if (!defined('DB_CHARSET')) define('DB_CHARSET', CHARSET);
if (!defined('DB_DRIVER')) define('DB_DRIVER', $_ENV['DB_CONNECTION'] ?? 'mysql');
if (!defined('DB_CONNECTION')) define('DB_CONNECTION', DB_DRIVER);

// ==================== ENVIRONMENT & SECURITY ====================
$rawEnv = strtolower($_ENV['APP_ENV'] ?? $_ENV['ENVIRONMENT'] ?? 'development');
$normalizedEnv = in_array($rawEnv, ['prod', 'production'], true) ? 'production' : 'development';
if (!defined('ENVIRONMENT')) define('ENVIRONMENT', $normalizedEnv);
if (!defined('APP_ENV')) define('APP_ENV', $normalizedEnv);

if (!defined('CSRF_PROTECTION')) define('CSRF_PROTECTION', filter_var($_ENV['CSRF_PROTECTION'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('DB_POOLING')) define('DB_POOLING', filter_var($_ENV['DB_POOLING'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('USE_CACHE')) define('USE_CACHE', filter_var($_ENV['USE_CACHE'] ?? true, FILTER_VALIDATE_BOOLEAN));

// Configuración de sesión segura (antes de session_start)
if (session_status() !== PHP_SESSION_ACTIVE) {
	@ini_set('session.cookie_httponly', '1');
	@ini_set('session.cookie_secure', '1');
	@ini_set('session.use_strict_mode', '1');
	@ini_set('session.use_only_cookies', '1');
}

// Whitelist de tablas SQL (Zero-Config: array vacío permite cualquier tabla con identificador SQL seguro)
if (!defined('ALLOWED_TABLES')) {
	define('ALLOWED_TABLES', []);
}

// ==================== URL & SCHEME ====================
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
	|| (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
	|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
	|| (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on');

$request_scheme = $_SERVER['REQUEST_SCHEME'] ?? ($isHttps ? 'https' : 'http');
if (!defined('SCHEME')) define('SCHEME', $request_scheme . '://');

$server_name = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
if (!defined('URL')) define('URL', SCHEME . trim((string) $server_name, '/') . '/');
if (!defined('APP')) define('APP', '');
if (!defined('LINK')) define('LINK', trim((string) URL, '/'));

$app = (APP !== '') ? '/' . APP : '';
if (!defined('DOMAIN')) define('DOMAIN', trim(URL, '/') . $app . '/');
if (!defined('ROOT')) define('ROOT', trim(URL, '/') . $app);
if (!defined('HOST')) define('HOST', $_SERVER['HTTP_HOST'] ?? $server_name);

// Hosts (subdominios) a no indexar
$noIndexHosts = $_ENV['NO_INDEX_HOSTS'] ?? '';
if (!defined('NO_INDEX_HOSTS')) define('NO_INDEX_HOSTS', array_filter(array_map('trim', explode(',', $noIndexHosts))));

// Dominio forzado en producción
if (!defined('FORCE_DOMAIN')) define('FORCE_DOMAIN', $_ENV['FORCE_DOMAIN'] ?? '');

// URLs noIndex para SEO por defecto
if (!defined('NO_INDEX')) {
	define('NO_INDEX', [
		'/test',
		'/test1',
		'/sitemap.xml',
		'/robots.txt',
		'/terminar-sesion',
		'/admin*'
	]);
}

// ==================== TIME CONSTANTS ====================
if (!defined('TIME_SEC')) define('TIME_SEC', 1);
if (!defined('TIME_MIN')) define('TIME_MIN', 60);
if (!defined('TIME_HOUR')) define('TIME_HOUR', 3600);
if (!defined('TIME_DAY')) define('TIME_DAY', 86400);
if (!defined('TIME_WEEK')) define('TIME_WEEK', 604800);        // 7 días
if (!defined('TIME_MONTH_S')) define('TIME_MONTH_S', 2592000); // 30 días estándar
if (!defined('TIME_YEAR')) define('TIME_YEAR', 31536000);     // 365 días estándar

// ==================== SESSION ====================
if (!defined('TIME_SESSION')) define('TIME_SESSION', TIME_MONTH_S);
if (!defined('PATH_SESSION')) define('PATH_SESSION', '/');
if (!defined('DOMAIN_SESSION')) define('DOMAIN_SESSION', '');
if (!defined('SSL_SESSION')) define('SSL_SESSION', $isHttps);

// ==================== ROUTES (Filesystem) ====================
if (!defined('ROUTE_VIEW')) define('ROUTE_VIEW', ROOT_PATH . '/App/Views/');
if (!defined('ROUTE_ERROR_VIEW')) define('ROUTE_ERROR_VIEW', ROOT_PATH . '/App/errorViews/');
if (!defined('ROUTE_CONTROLLER')) define('ROUTE_CONTROLLER', ROOT_PATH . '/App/Controllers/');
if (!defined('ROUTE_MIDDLEWARE')) define('ROUTE_MIDDLEWARE', ROOT_PATH . '/App/Middleware/');
if (!defined('ROUTE_MODEL')) define('ROUTE_MODEL', ROOT_PATH . '/App/Models/');
if (!defined('ROUTE_TEMPLATE')) define('ROUTE_TEMPLATE', ROOT_PATH . '/App/Segment/Template/');
if (!defined('ROUTE_FORM')) define('ROUTE_FORM', ROOT_PATH . '/App/Segment/Form/');
if (!defined('ROUTE_MENU')) define('ROUTE_MENU', ROOT_PATH . '/App/Segment/Menu/');
if (!defined('ROUTE_RESOURCE')) define('ROUTE_RESOURCE', ROOT_PATH . '/App/Rsc/');
if (!defined('ROUTE_ICO')) define('ROUTE_ICO', ROOT_PATH . '/App/Rsc/Ico/');
if (!defined('ROUTE_ICON')) define('ROUTE_ICON', ROOT_PATH . '/App/Rsc/Ico/');
if (!defined('ROUTE_IMG')) define('ROUTE_IMG', ROOT_PATH . '/App/Public/Img/');
if (!defined('ROUTE_IMG_PUBLIC')) define('ROUTE_IMG_PUBLIC', ROOT_PATH . '/App/Public/Img/');
if (!defined('ROUTE_DATABASE')) define('ROUTE_DATABASE', ROOT_PATH . '/Database/');
if (!defined('ROUTE_DATABASE_COMPONENT')) define('ROUTE_DATABASE_COMPONENT', ROOT_PATH . '/App/DatabaseComponent/');
if (!defined('ROUTE_SAFETY')) define('ROUTE_SAFETY', ROOT_PATH . '/App/Safety/');

// ==================== URL PATHS (para HTML) ====================
if (!defined('URL_RESOURCE')) define('URL_RESOURCE', '/App/Rsc/');
if (!defined('URL_IMG')) define('URL_IMG', '/App/Public/Img/Custom/');
if (!defined('URL_IMG_PUBLIC')) define('URL_IMG_PUBLIC', '/App/Public/Img/');
if (!defined('URL_ICON')) define('URL_ICON', '/App/Rsc/Ico/');

// ==================== SITE INFO (desde .env) ====================
if (!defined('NAME_SITE')) define('NAME_SITE', $_ENV['APP_NAME'] ?? 'Mi Sitio');
if (!defined('DESCRIPTION')) define('DESCRIPTION', $_ENV['APP_DESCRIPTION'] ?? '');
if (!defined('LOGO')) define('LOGO', rtrim(DOMAIN, '/') . '/' . ltrim(URL_IMG, '/') . 'logo.png');
if (!defined('LOGOPAG')) define('LOGOPAG', rtrim(DOMAIN, '/') . '/' . ltrim(URL_IMG, '/') . 'logo-pag.png');
if (!defined('IMG_OG')) define('IMG_OG', rtrim(DOMAIN, '/') . '/' . ltrim(URL_IMG, '/') . 'img-og.png');
if (!defined('DIR_IMG_APP')) define('DIR_IMG_APP', URL_IMG);

// ==================== GEOLOCATION (desde .env) ====================
if (!defined('GEO_API_PRIMARY')) define('GEO_API_PRIMARY', $_ENV['GEO_API_PRIMARY'] ?? 'https://ip.guide/');
if (!defined('GEO_API_FALLBACK')) define('GEO_API_FALLBACK', $_ENV['GEO_API_FALLBACK'] ?? 'https://api.ipquery.io/');
if (!defined('GEO_CONNECT_TIMEOUT')) define('GEO_CONNECT_TIMEOUT', (int)($_ENV['GEO_CONNECT_TIMEOUT'] ?? 3));
if (!defined('GEO_REQUEST_TIMEOUT')) define('GEO_REQUEST_TIMEOUT', (int)($_ENV['GEO_REQUEST_TIMEOUT'] ?? 5));

// ==================== SEO LOCALE (desde .env) ====================
if (!defined('OG_LOCALE')) define('OG_LOCALE', $_ENV['OG_LOCALE'] ?? 'es');
if (!defined('SEO_PERSON_NAME')) define('SEO_PERSON_NAME', $_ENV['SEO_PERSON_NAME'] ?? '');
if (!defined('SEO_PERSON_URL')) define('SEO_PERSON_URL', $_ENV['SEO_PERSON_URL'] ?? '');
if (!defined('SEO_PERSON_JOB')) define('SEO_PERSON_JOB', $_ENV['SEO_PERSON_JOB'] ?? '');
if (!defined('SEO_PERSON_KNOWS')) define('SEO_PERSON_KNOWS', $_ENV['SEO_PERSON_KNOWS'] ?? '');

// ==================== IMAGE PROCESSING ====================
if (!defined('IMG_ADMITTED')) {
	define('IMG_ADMITTED', [
		'image/jpg',
		'image/jpeg',
		'image/gif',
		'image/png',
		'image/webp',
		'image/avif',
		'image/bmp',
		'image/x-ms-bmp',
		'image/tiff',
		'image/x-tiff',
		'image/heic',
		'image/heif'
	]);
}

if (!defined('DIR_UPLOAD_MEDIA')) {
	$envUpload = $_ENV['DIR_UPLOAD_MEDIA'] ?? '';
	if (empty($envUpload) || $envUpload === '/Uploads/' || $envUpload === 'Uploads/' || $envUpload === '/Uploads' || $envUpload === 'Uploads') {
		define('DIR_UPLOAD_MEDIA', ROOT_PATH . '/Uploads/');
	} elseif (!str_contains($envUpload, ':') && str_starts_with($envUpload, '/')) {
		define('DIR_UPLOAD_MEDIA', ROOT_PATH . $envUpload);
	} else {
		define('DIR_UPLOAD_MEDIA', rtrim($envUpload, '/\\') . '/');
	}
}

if (!defined('DIR_UPLOAD_GIF')) {
	$envGif = $_ENV['DIR_UPLOAD_GIF'] ?? '';
	if (empty($envGif) || $envGif === '/Uploads/' || $envGif === 'Uploads/' || $envGif === '/Uploads' || $envGif === 'Uploads') {
		define('DIR_UPLOAD_GIF', ROOT_PATH . '/Uploads/');
	} elseif (!str_contains($envGif, ':') && str_starts_with($envGif, '/')) {
		define('DIR_UPLOAD_GIF', ROOT_PATH . $envGif);
	} else {
		define('DIR_UPLOAD_GIF', rtrim($envGif, '/\\') . '/');
	}
}

if (!defined('DIR_SHOW_MEDIA')) {
	$envShow = $_ENV['DIR_SHOW_MEDIA'] ?? '';
	if (empty($envShow) || $envShow === '/Uploads/' || $envShow === 'Uploads/' || $envShow === '/Uploads' || $envShow === 'Uploads') {
		define('DIR_SHOW_MEDIA', ROOT . '/Uploads/');
	} elseif (str_starts_with($envShow, 'http')) {
		define('DIR_SHOW_MEDIA', rtrim($envShow, '/') . '/');
	} else {
		define('DIR_SHOW_MEDIA', ROOT . '/' . ltrim($envShow, '/'));
	}
}

if (!defined('DIR_IMG_SHOW_STATIC')) define('DIR_IMG_SHOW_STATIC', ROOT . '/App/Public/Img/');
if (!defined('DIR_UPLOAD_MEDIA_STATIC')) define('DIR_UPLOAD_MEDIA_STATIC', ROOT . '/App/Public/Img/');

if (!defined('COMPRESS_IMAGE')) define('COMPRESS_IMAGE', filter_var($_ENV['COMPRESS_IMAGE'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('MAX_IMAGE_KB')) define('MAX_IMAGE_KB', (int)($_ENV['MAX_IMAGE_KB'] ?? 0));
if (!defined('OUTPUT_FORMAT')) define('OUTPUT_FORMAT', $_ENV['IMAGE_FORMAT'] ?? 'webp');
if (!defined('SMART_COMPRESSION')) define('SMART_COMPRESSION', filter_var($_ENV['SMART_COMPRESSION'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('MAX_IMAGE_WIDTH')) define('MAX_IMAGE_WIDTH', (int)($_ENV['MAX_IMAGE_WIDTH'] ?? 1920));
if (!defined('MAX_IMAGE_HEIGHT')) define('MAX_IMAGE_HEIGHT', (int)($_ENV['MAX_IMAGE_HEIGHT'] ?? 1080));
if (!defined('MIN_QUALITY')) define('MIN_QUALITY', (int)($_ENV['MIN_QUALITY'] ?? 20));
if (!defined('QUALITY_STEP')) define('QUALITY_STEP', (int)($_ENV['QUALITY_STEP'] ?? 5));
if (!defined('LAZY_LOAD_IMAGES')) define('LAZY_LOAD_IMAGES', filter_var($_ENV['LAZY_LOAD_IMAGES'] ?? true, FILTER_VALIDATE_BOOLEAN));

// Calidad de compresión - Landscape
if (!defined('XL_QUALITY')) define('XL_QUALITY', 65);
if (!defined('XL_WIDE')) define('XL_WIDE', 0.55);
if (!defined('XL_PNG_QUALITY')) define('XL_PNG_QUALITY', 9);
if (!defined('QUALITY')) define('QUALITY', 70);
if (!defined('WIDE')) define('WIDE', 0.65);
if (!defined('PNG_QUALITY')) define('PNG_QUALITY', 8);
if (!defined('MID_QUALITY')) define('MID_QUALITY', 75);
if (!defined('MID_WIDE')) define('MID_WIDE', 0.80);
if (!defined('MID_PNG_QUALITY')) define('MID_PNG_QUALITY', 6);
if (!defined('SML_QUALITY')) define('SML_QUALITY', 80);
if (!defined('SML_WIDE')) define('SML_WIDE', 0.95);
if (!defined('SML_PNG_QUALITY')) define('SML_PNG_QUALITY', 5);

// Calidad de compresión - Portrait
if (!defined('P_XL_QUALITY')) define('P_XL_QUALITY', 65);
if (!defined('P_XL_WIDE')) define('P_XL_WIDE', 0.40);
if (!defined('P_XL_PNG_QUALITY')) define('P_XL_PNG_QUALITY', 8);
if (!defined('P_QUALITY')) define('P_QUALITY', 70);
if (!defined('P_WIDE')) define('P_WIDE', 0.55);
if (!defined('P_PNG_QUALITY')) define('P_PNG_QUALITY', 7);
if (!defined('P_MID_QUALITY')) define('P_MID_QUALITY', 75);
if (!defined('P_MID_WIDE')) define('P_MID_WIDE', 0.68);
if (!defined('P_MID_PNG_QUALITY')) define('P_MID_PNG_QUALITY', 6);
if (!defined('P_SML_QUALITY')) define('P_SML_QUALITY', 80);
if (!defined('P_SML_WIDE')) define('P_SML_WIDE', 0.95);
if (!defined('P_SML_PNG_QUALITY')) define('P_SML_PNG_QUALITY', 5);

// Etiquetas válidas para text_editor.js
if (!defined('TAGS_VALID_TEXT_INPUT')) {
	define('TAGS_VALID_TEXT_INPUT', '<h1><h2><h3><h4><strong><p><blockquote><ul><ol><li>');
}

// ==================== INIT SESSION ====================
if (php_sapi_name() !== 'cli' && !headers_sent()) {
	\Base\Module\Session::start();
}
