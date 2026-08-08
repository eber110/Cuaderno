<?php

/**
 * Configuración principal de la aplicación.
 * 
 * Las variables configurables están en .env
 * Este archivo define constantes derivadas y de estructura.
 */

// ==================== ROOT PATH ====================
if (!defined('ROOT_PATH')) {
    $rootPath = str_replace('\\', '/', dirname(__DIR__, 3));
    define('ROOT_PATH', $rootPath);
}

// ==================== FRAMEWORK PATH ====================
define('FRAMEWORK_PATH', rtrim(str_replace('\\', '/', __DIR__), '/') . '/');

// ==================== DATABASE ====================
define('NAMESERVER', $_ENV['DB_HOST']);
define('USER', $_ENV['DB_USERNAME']);
define('PASS', $_ENV['DB_PASSWORD']);
define('BD', $_ENV['DB_DATABASE']);
define('CHARSET', $_ENV['DB_CHARSET']);
define('DB_DRIVER', $_ENV['DB_CONNECTION'] ?? 'mysql');

// ==================== ENVIRONMENT & SECURITY ====================
define('ENVIRONMENT', $_ENV['APP_ENV'] ?? 'DEV');
define('CSRF_PROTECTION', filter_var($_ENV['CSRF_PROTECTION'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('DB_POOLING', filter_var($_ENV['DB_POOLING'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('USE_CACHE', filter_var($_ENV['USE_CACHE'] ?? true, FILTER_VALIDATE_BOOLEAN));

// Configuración de sesión segura (antes de session_start)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

// Whitelist de tablas SQL permitidas
if (!defined('ALLOWED_TABLES')) {
	define('ALLOWED_TABLES', [
		'sitesettings',
		'users',
		'roles',
		'userroles',
		'media',
		'mediables',
		'pages',
		'blogposts',
		'categories',
		'tags',
		'blogpostcategories',
		'blogposttags',
		'comments',
		'survey',
		'surveyanswers',
		'products',
		'productcategories',
		'producttags',
		'orders',
		'orderitems',
		'navigationmenus',
		'menuitems',
		'audittrail',
		'productvariants',
		'addresses',
		'payments',
		'notifications',
		'userpreferences',
		'visitorlog',
		'emailregister',
		'interactions',
		'visitor_log'
	]);
}

// ==================== OPTIMIZATION FLAGS ====================
define('LAZY_LOADING', filter_var($_ENV['LAZY_LOADING'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('CLEANUP_RESOURCES', filter_var($_ENV['CLEANUP_RESOURCES'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('FILE_CACHE', filter_var($_ENV['FILE_CACHE'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('MEMORY_CACHE', filter_var($_ENV['MEMORY_CACHE'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('DB_CACHE', filter_var($_ENV['DB_CACHE'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('MINIFY_ASSETS', filter_var($_ENV['MINIFY_ASSETS'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('USE_CDN', filter_var($_ENV['USE_CDN'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('OPTIMIZE_FONTS', filter_var($_ENV['OPTIMIZE_FONTS'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('LAZY_LOAD_IMAGES', filter_var($_ENV['LAZY_LOAD_IMAGES'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('USE_INDEXES', filter_var($_ENV['USE_INDEXES'] ?? true, FILTER_VALIDATE_BOOLEAN));

// ==================== URL & SCHEME ====================
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
	|| (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
	|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
	|| (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on');

$request_scheme = $_SERVER['REQUEST_SCHEME'] ?? ($isHttps ? 'https' : 'http');
define('SCHEME', $request_scheme . '://');

$server_name = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
define('URL', SCHEME . trim((string) $server_name, '/') . '/');
define('APP', '');
define('LINK', trim((string) URL, '/'));

$app = (APP !== '') ? '/' . APP : '';
define('DOMAIN', trim(URL, '/') . $app . '/');
define('ROOT', trim(URL, '/') . $app);
define('HOST', $_SERVER['HTTP_HOST'] ?? $server_name);

// Hosts (subdominios) a no indexar
$noIndexHosts = $_ENV['NO_INDEX_HOSTS'] ?? '';
define('NO_INDEX_HOSTS', array_filter(array_map('trim', explode(',', $noIndexHosts))));

// Dominio forzado en producción
define('FORCE_DOMAIN', $_ENV['FORCE_DOMAIN'] ?? '');

// URLs noIndex para SEO
if (!defined('NO_INDEX')) {
	define('NO_INDEX', [
		'/test',
		'/test1',
		'/crear-publicacion',
		'/sitemap.xml',
		'/robots.txt',
		'/crear-usuario',
		'/terminar-sesion',
		'/admin*',
		'/ingresar'
	]);
}

// ==================== FEATURES ====================
define('LOGIN_ACTIVE', filter_var($_ENV['LOGIN_ACTIVE'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('LOGUSER', filter_var($_ENV['LOG_USER'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('LOGVISITOR', filter_var($_ENV['LOG_VISITOR'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('COUNT_VISIT', filter_var($_ENV['COUNT_VISIT'] ?? false, FILTER_VALIDATE_BOOLEAN));

// ==================== TIME CONSTANTS ====================
define('TIME_SEC', 1);
define('TIME_MIN', 60);
define('TIME_HOUR', 3600);
define('TIME_DAY', 86400);
define('TIME_WEEK', 604800);      // 7 días
define('TIME_MONTH_S', 2592000); // 30 días estándar
define('TIME_YEAR', 31536000);   // 365 días estándar

// ==================== SESSION ====================
define('TIME_SESSION', TIME_MONTH_S);
define('PATH_SESSION', '/');
define('DOMAIN_SESSION', '');
define('SSL_SESSION', $isHttps);

// ==================== ROUTES (Filesystem) ====================
define('ROUTE_VIEW', ROOT_PATH . '/App/Views/');
define('ROUTE_ERROR_VIEW', ROOT_PATH . '/App/errorViews/');
define('ROUTE_CONTROLLER', ROOT_PATH . '/App/Controllers/');
define('ROUTE_MIDDLEWARE', ROOT_PATH . '/App/Middleware/');
define('ROUTE_MODEL', ROOT_PATH . '/App/Models/');
define('ROUTE_TEMPLATE', ROOT_PATH . '/App/Segment/Template/');
define('ROUTE_FORM', ROOT_PATH . '/App/Segment/Form/');
define('ROUTE_MENU', ROOT_PATH . '/App/Segment/Menu/');
define('ROUTE_RESOURCE', ROOT_PATH . '/App/Rsc/');
define('ROUTE_ICO', ROOT_PATH . '/App/Rsc/Ico/');
define('ROUTE_ICON', ROOT_PATH . '/App/Rsc/Ico/');
define('ROUTE_IMG', ROOT_PATH . '/App/Public/Img/');
define('ROUTE_IMG_PUBLIC', ROOT_PATH . '/App/Public/Img/');
define('ROUTE_DATABASE', ROOT_PATH . '/Database/');

// ==================== URL PATHS (for HTML) ====================
define('URL_RESOURCE', '/App/Rsc/');
define('URL_IMG', '/App/Public/Img/Custom/');
define('URL_IMG_PUBLIC', '/App/Public/Img/');
define('URL_ICON', '/App/Rsc/Ico/');

// ==================== SITE INFO (from .env) ====================
define('NAME_SITE', $_ENV['APP_NAME'] ?? 'Mi Sitio');
define('DESCRIPTION', $_ENV['APP_DESCRIPTION'] ?? '');
define('LOGO', rtrim(DOMAIN, '/') . '/' . ltrim(URL_IMG, '/') . 'logo.png');
define('LOGOPAG', rtrim(DOMAIN, '/') . '/' . ltrim(URL_IMG, '/') . 'logo-pag.png');
define('IMG_OG', rtrim(DOMAIN, '/') . '/' . ltrim(URL_IMG, '/') . 'img-og.png');
define('DIR_IMG_APP', URL_IMG);

// ==================== SOCIAL MEDIA (from .env) ====================
define('SOCIAL_GITHUB', $_ENV['SOCIAL_GITHUB'] ?? '');
define('SOCIAL_TWITTER', $_ENV['SOCIAL_TWITTER'] ?? '');
define('SOCIAL_INSTAGRAM', $_ENV['SOCIAL_INSTAGRAM'] ?? '');
define('SOCIAL_LINKEDIN', $_ENV['SOCIAL_LINKEDIN'] ?? '');

// ==================== GEOLOCATION (from .env) ====================
define('GEO_API_PRIMARY', $_ENV['GEO_API_PRIMARY'] ?? 'https://ip.guide/');
define('GEO_API_FALLBACK', $_ENV['GEO_API_FALLBACK'] ?? 'https://api.ipquery.io/');
define('GEO_CONNECT_TIMEOUT', (int)($_ENV['GEO_CONNECT_TIMEOUT'] ?? 3));
define('GEO_REQUEST_TIMEOUT', (int)($_ENV['GEO_REQUEST_TIMEOUT'] ?? 5));

// ==================== SEO LOCALE (from .env) ====================
define('OG_LOCALE', $_ENV['OG_LOCALE'] ?? 'es');
define('SEO_PERSON_NAME', $_ENV['SEO_PERSON_NAME'] ?? '');
define('SEO_PERSON_URL', $_ENV['SEO_PERSON_URL'] ?? '');
define('SEO_PERSON_JOB', $_ENV['SEO_PERSON_JOB'] ?? '');
define('SEO_PERSON_KNOWS', $_ENV['SEO_PERSON_KNOWS'] ?? '');

// ==================== VISITS ====================
define('TABLE_VISIT', 'visitor_log');
define('TABLE_USER', 'users');
define('MY_USER', 'id_user');
define('MY_IP', $_ENV['DEV_IP'] ?? '127.0.0.1');

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

define('DIR_UPLOAD_MEDIA', $_ENV['DIR_UPLOAD_MEDIA'] ?? (ROOT_PATH . '/Uploads/'));
define('DIR_UPLOAD_GIF', $_ENV['DIR_UPLOAD_GIF'] ?? (ROOT_PATH . '/Uploads/'));
define('DIR_SHOW_MEDIA', $_ENV['DIR_SHOW_MEDIA'] ?? (ROOT . URL_IMG_PUBLIC));
define('DIR_IMG_SHOW_STATIC', $_ENV['DIR_IMG_SHOW_STATIC'] ?? (ROOT . '/App/Public/Img/'));
define('DIR_UPLOAD_MEDIA_STATIC', $_ENV['DIR_UPLOAD_MEDIA_STATIC'] ?? (ROOT . '/App/Public/Img/'));

define('COMPRESS_IMAGE', filter_var($_ENV['COMPRESS_IMAGE'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('MAX_IMAGE_KB', (int)($_ENV['MAX_IMAGE_KB'] ?? 0));
define('OUTPUT_FORMAT', $_ENV['IMAGE_FORMAT'] ?? 'webp');
define('SMART_COMPRESSION', filter_var($_ENV['SMART_COMPRESSION'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('MAX_IMAGE_WIDTH', (int)($_ENV['MAX_IMAGE_WIDTH'] ?? 1920));
define('MAX_IMAGE_HEIGHT', (int)($_ENV['MAX_IMAGE_HEIGHT'] ?? 1080));
define('MIN_QUALITY', (int)($_ENV['MIN_QUALITY'] ?? 20));
define('QUALITY_STEP', (int)($_ENV['QUALITY_STEP'] ?? 5));

// Calidad de compresión - Landscape
define('XL_QUALITY', 65);
define('XL_WIDE', 0.55);
define('XL_PNG_QUALITY', 9);
define('QUALITY', 70);
define('WIDE', 0.65);
define('PNG_QUALITY', 8);
define('MID_QUALITY', 75);
define('MID_WIDE', 0.80);
define('MID_PNG_QUALITY', 6);
define('SML_QUALITY', 80);
define('SML_WIDE', 0.95);
define('SML_PNG_QUALITY', 5);

// Calidad de compresión - Portrait
define('P_XL_QUALITY', 65);
define('P_XL_WIDE', 0.40);
define('P_XL_PNG_QUALITY', 8);
define('P_QUALITY', 70);
define('P_WIDE', 0.55);
define('P_PNG_QUALITY', 7);
define('P_MID_QUALITY', 75);
define('P_MID_WIDE', 0.68);
define('P_MID_PNG_QUALITY', 6);
define('P_SML_QUALITY', 80);
define('P_SML_WIDE', 0.95);
define('P_SML_PNG_QUALITY', 5);

// Etiquetas válidas para text_editor.js
if (!defined('TAGS_VALID_TEXT_INPUT')) {
	define('TAGS_VALID_TEXT_INPUT', '<h1><h2><h3><h4><strong><p><blockquote><ul><ol><li>');
}

// ==================== INIT SESSION ====================
if (php_sapi_name() !== 'cli' && !headers_sent()) {
	\Base\Module\Session::start();
}
