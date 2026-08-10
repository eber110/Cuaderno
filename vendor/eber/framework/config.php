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
if (!defined('NAMESERVER')) define('NAMESERVER', $_ENV['DB_HOST'] ?? '');
if (!defined('USER')) define('USER', $_ENV['DB_USERNAME'] ?? '');
if (!defined('PASS')) define('PASS', $_ENV['DB_PASSWORD'] ?? '');
if (!defined('BD')) define('BD', $_ENV['DB_DATABASE'] ?? '');
if (!defined('CHARSET')) define('CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');
if (!defined('DB_DRIVER')) define('DB_DRIVER', $_ENV['DB_CONNECTION'] ?? 'mysql');

// ==================== ENVIRONMENT & SECURITY ====================
if (!defined('ENVIRONMENT')) define('ENVIRONMENT', $_ENV['APP_ENV'] ?? 'DEV');
if (!defined('CSRF_PROTECTION')) define('CSRF_PROTECTION', filter_var($_ENV['CSRF_PROTECTION'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('DB_POOLING')) define('DB_POOLING', filter_var($_ENV['DB_POOLING'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('USE_CACHE')) define('USE_CACHE', filter_var($_ENV['USE_CACHE'] ?? true, FILTER_VALIDATE_BOOLEAN));

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
if (!defined('LAZY_LOADING')) define('LAZY_LOADING', filter_var($_ENV['LAZY_LOADING'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('CLEANUP_RESOURCES')) define('CLEANUP_RESOURCES', filter_var($_ENV['CLEANUP_RESOURCES'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('FILE_CACHE')) define('FILE_CACHE', filter_var($_ENV['FILE_CACHE'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('MEMORY_CACHE')) define('MEMORY_CACHE', filter_var($_ENV['MEMORY_CACHE'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('DB_CACHE')) define('DB_CACHE', filter_var($_ENV['DB_CACHE'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('MINIFY_ASSETS')) define('MINIFY_ASSETS', filter_var($_ENV['MINIFY_ASSETS'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('USE_CDN')) define('USE_CDN', filter_var($_ENV['USE_CDN'] ?? false, FILTER_VALIDATE_BOOLEAN));
if (!defined('OPTIMIZE_FONTS')) define('OPTIMIZE_FONTS', filter_var($_ENV['OPTIMIZE_FONTS'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('LAZY_LOAD_IMAGES')) define('LAZY_LOAD_IMAGES', filter_var($_ENV['LAZY_LOAD_IMAGES'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('USE_INDEXES')) define('USE_INDEXES', filter_var($_ENV['USE_INDEXES'] ?? true, FILTER_VALIDATE_BOOLEAN));

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
if (!defined('LOGIN_ACTIVE')) define('LOGIN_ACTIVE', filter_var($_ENV['LOGIN_ACTIVE'] ?? false, FILTER_VALIDATE_BOOLEAN));
if (!defined('LOGUSER')) define('LOGUSER', filter_var($_ENV['LOG_USER'] ?? false, FILTER_VALIDATE_BOOLEAN));
if (!defined('LOGVISITOR')) define('LOGVISITOR', filter_var($_ENV['LOG_VISITOR'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('COUNT_VISIT')) define('COUNT_VISIT', filter_var($_ENV['COUNT_VISIT'] ?? false, FILTER_VALIDATE_BOOLEAN));

// ==================== TIME CONSTANTS ====================
if (!defined('TIME_SEC')) define('TIME_SEC', 1);
if (!defined('TIME_MIN')) define('TIME_MIN', 60);
if (!defined('TIME_HOUR')) define('TIME_HOUR', 3600);
if (!defined('TIME_DAY')) define('TIME_DAY', 86400);
if (!defined('TIME_WEEK')) define('TIME_WEEK', 604800);      // 7 días
if (!defined('TIME_MONTH_S')) define('TIME_MONTH_S', 2592000); // 30 días estándar
if (!defined('TIME_YEAR')) define('TIME_YEAR', 31536000);   // 365 días estándar

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

// ==================== URL PATHS (for HTML) ====================
if (!defined('URL_RESOURCE')) define('URL_RESOURCE', '/App/Rsc/');
if (!defined('URL_IMG')) define('URL_IMG', '/App/Public/Img/Custom/');
if (!defined('URL_IMG_PUBLIC')) define('URL_IMG_PUBLIC', '/App/Public/Img/');
if (!defined('URL_ICON')) define('URL_ICON', '/App/Rsc/Ico/');

// ==================== SITE INFO (from .env) ====================
if (!defined('NAME_SITE')) define('NAME_SITE', $_ENV['APP_NAME'] ?? 'Mi Sitio');
if (!defined('DESCRIPTION')) define('DESCRIPTION', $_ENV['APP_DESCRIPTION'] ?? '');
if (!defined('LOGO')) define('LOGO', rtrim(DOMAIN, '/') . '/' . ltrim(URL_IMG, '/') . 'logo.png');
if (!defined('LOGOPAG')) define('LOGOPAG', rtrim(DOMAIN, '/') . '/' . ltrim(URL_IMG, '/') . 'logo-pag.png');
if (!defined('IMG_OG')) define('IMG_OG', rtrim(DOMAIN, '/') . '/' . ltrim(URL_IMG, '/') . 'img-og.png');
if (!defined('DIR_IMG_APP')) define('DIR_IMG_APP', URL_IMG);

// ==================== SOCIAL MEDIA (from .env) ====================
if (!defined('SOCIAL_GITHUB')) define('SOCIAL_GITHUB', $_ENV['SOCIAL_GITHUB'] ?? '');
if (!defined('SOCIAL_TWITTER')) define('SOCIAL_TWITTER', $_ENV['SOCIAL_TWITTER'] ?? '');
if (!defined('SOCIAL_INSTAGRAM')) define('SOCIAL_INSTAGRAM', $_ENV['SOCIAL_INSTAGRAM'] ?? '');
if (!defined('SOCIAL_LINKEDIN')) define('SOCIAL_LINKEDIN', $_ENV['SOCIAL_LINKEDIN'] ?? '');

// ==================== GEOLOCATION (from .env) ====================
if (!defined('GEO_API_PRIMARY')) define('GEO_API_PRIMARY', $_ENV['GEO_API_PRIMARY'] ?? 'https://ip.guide/');
if (!defined('GEO_API_FALLBACK')) define('GEO_API_FALLBACK', $_ENV['GEO_API_FALLBACK'] ?? 'https://api.ipquery.io/');
if (!defined('GEO_CONNECT_TIMEOUT')) define('GEO_CONNECT_TIMEOUT', (int)($_ENV['GEO_CONNECT_TIMEOUT'] ?? 3));
if (!defined('GEO_REQUEST_TIMEOUT')) define('GEO_REQUEST_TIMEOUT', (int)($_ENV['GEO_REQUEST_TIMEOUT'] ?? 5));

// ==================== SEO LOCALE (from .env) ====================
if (!defined('OG_LOCALE')) define('OG_LOCALE', $_ENV['OG_LOCALE'] ?? 'es');
if (!defined('SEO_PERSON_NAME')) define('SEO_PERSON_NAME', $_ENV['SEO_PERSON_NAME'] ?? '');
if (!defined('SEO_PERSON_URL')) define('SEO_PERSON_URL', $_ENV['SEO_PERSON_URL'] ?? '');
if (!defined('SEO_PERSON_JOB')) define('SEO_PERSON_JOB', $_ENV['SEO_PERSON_JOB'] ?? '');
if (!defined('SEO_PERSON_KNOWS')) define('SEO_PERSON_KNOWS', $_ENV['SEO_PERSON_KNOWS'] ?? '');

// ==================== VISITS ====================
if (!defined('TABLE_VISIT')) define('TABLE_VISIT', 'visitor_log');
if (!defined('TABLE_USER')) define('TABLE_USER', 'users');
if (!defined('MY_USER')) define('MY_USER', 'id_user');
if (!defined('MY_IP')) define('MY_IP', $_ENV['DEV_IP'] ?? '127.0.0.1');

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

if (!defined('DIR_UPLOAD_MEDIA')) define('DIR_UPLOAD_MEDIA', $_ENV['DIR_UPLOAD_MEDIA'] ?? (ROOT_PATH . '/Uploads/'));
if (!defined('DIR_UPLOAD_GIF')) define('DIR_UPLOAD_GIF', $_ENV['DIR_UPLOAD_GIF'] ?? (ROOT_PATH . '/Uploads/'));
if (!defined('DIR_SHOW_MEDIA')) define('DIR_SHOW_MEDIA', $_ENV['DIR_SHOW_MEDIA'] ?? (ROOT . URL_IMG_PUBLIC));
if (!defined('DIR_IMG_SHOW_STATIC')) define('DIR_IMG_SHOW_STATIC', $_ENV['DIR_IMG_SHOW_STATIC'] ?? (ROOT . '/App/Public/Img/'));
if (!defined('DIR_UPLOAD_MEDIA_STATIC')) define('DIR_UPLOAD_MEDIA_STATIC', $_ENV['DIR_UPLOAD_MEDIA_STATIC'] ?? (ROOT . '/App/Public/Img/'));

if (!defined('COMPRESS_IMAGE')) define('COMPRESS_IMAGE', filter_var($_ENV['COMPRESS_IMAGE'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('MAX_IMAGE_KB')) define('MAX_IMAGE_KB', (int)($_ENV['MAX_IMAGE_KB'] ?? 0));
if (!defined('OUTPUT_FORMAT')) define('OUTPUT_FORMAT', $_ENV['IMAGE_FORMAT'] ?? 'webp');
if (!defined('SMART_COMPRESSION')) define('SMART_COMPRESSION', filter_var($_ENV['SMART_COMPRESSION'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('MAX_IMAGE_WIDTH')) define('MAX_IMAGE_WIDTH', (int)($_ENV['MAX_IMAGE_WIDTH'] ?? 1920));
if (!defined('MAX_IMAGE_HEIGHT')) define('MAX_IMAGE_HEIGHT', (int)($_ENV['MAX_IMAGE_HEIGHT'] ?? 1080));
if (!defined('MIN_QUALITY')) define('MIN_QUALITY', (int)($_ENV['MIN_QUALITY'] ?? 20));
if (!defined('QUALITY_STEP')) define('QUALITY_STEP', (int)($_ENV['QUALITY_STEP'] ?? 5));

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
