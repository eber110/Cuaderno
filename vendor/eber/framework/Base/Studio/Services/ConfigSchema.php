<?php

namespace Base\Studio\Services;

/**
 * Esquema declarativo y validador de variables de configuración gestionables en Frame Studio.
 *
 * Separa estrictamente qué variables pertenecen a configuración compilable (OPcache),
 * qué credenciales van cifradas al Vault AEAD, y cuáles son solo locales.
 * Bloquea cualquier constante estructural o dinámica del núcleo del framework.
 */
class ConfigSchema
{
  /**
   * Constantes estructurales y dinámicas protegidas (no editables desde Studio).
   */
  public const PROTECTED_KEYS = [
    'ROOT_PATH',
    'FRAMEWORK_PATH',
    'ENVIRONMENT',
    'SCHEME',
    'HOST',
    'URL',
    'APP',
    'LINK',
    'DOMAIN',
    'ROOT',
    'SSL_SESSION',
    'ROUTE_VIEW',
    'ROUTE_ERROR_VIEW',
    'ROUTE_CONTROLLER',
    'ROUTE_MIDDLEWARE',
    'ROUTE_MODEL',
    'ROUTE_TEMPLATE',
    'ROUTE_FORM',
    'ROUTE_MENU',
    'ROUTE_RESOURCE',
    'ROUTE_ICO',
    'ROUTE_ICON',
    'ROUTE_IMG',
    'ROUTE_IMG_PUBLIC',
    'ROUTE_DATABASE',
    'ROUTE_DATABASE_COMPONENT',
    'ROUTE_SAFETY',
    'ROUTE_LANG',
    'URL_RESOURCE',
    'URL_IMG',
    'URL_IMG_PUBLIC',
    'URL_ICON',
    'TIME_SEC',
    'TIME_MIN',
    'TIME_HOUR',
    'TIME_DAY',
    'TIME_WEEK',
    'TIME_MONTH_S',
    'TIME_YEAR',
    'TIME_SESSION',
    'PATH_SESSION',
    'DOMAIN_SESSION',
    'IMG_ADMITTED',
    'NAMESERVER',
    'USER',
    'PASS',
    'BD',
    'CHARSET',
    'DB_DRIVER',
    'NAME_SITE',
    'DESCRIPTION',
    'LOGO',
    'LOGOPAG',
    'IMG_OG',
    'DIR_IMG_APP',
    'DIR_IMG_SHOW_STATIC',
    'DIR_UPLOAD_MEDIA_STATIC',
    'DIR_SHOW_MEDIA',
    'OUTPUT_FORMAT',
  ];

  /**
   * Devuelve las secciones visuales de configuración.
   *
   * @return array<int, array<string, string>>
   */
  public static function getSections(): array
  {
    return [
      ['id' => 'app', 'icon' => '🌐', 'label' => 'Aplicación y Dominio', 'desc' => 'Identidad del sitio, entorno, dominio canónico y tema inicial.'],
      ['id' => 'database', 'icon' => '🗄️', 'label' => 'Base de Datos', 'desc' => 'Driver y credenciales PDO cifradas en el Vault de producción.'],
      ['id' => 'security', 'icon' => '🛡️', 'label' => 'Seguridad y Caché', 'desc' => 'Semilla criptográfica, CSRF, caché, CSS inline y whitelist de tablas.'],
      ['id' => 'i18n', 'icon' => '🌍', 'label' => 'Idiomas (i18n)', 'desc' => 'Soporte multiidioma, locale principal y respaldo.'],
      ['id' => 'smtp', 'icon' => '✉️', 'label' => 'Correo SMTP', 'desc' => 'Credenciales del servidor de salida cifradas en Vault.'],
      ['id' => 'images', 'icon' => '🖼️', 'label' => 'Imágenes y Compresión', 'desc' => 'Rutas de subida, formato WebP/AVIF, dimensiones y calidades.'],
      ['id' => 'geo', 'icon' => '📍', 'label' => 'Geolocalización', 'desc' => 'APIs IP, timeouts y licencia MaxMind GeoLite2.'],
      ['id' => 'seo', 'icon' => '🔎', 'label' => 'SEO y Metadatos', 'desc' => 'Open Graph, rutas/subdominios no indexables y Schema JSON-LD.'],
      ['id' => 'apis', 'icon' => '🤖', 'label' => 'APIs Externas', 'desc' => 'Claves de servicios externos cifradas en Vault.'],
      ['id' => 'deploy', 'icon' => '🚀', 'label' => 'Despliegue', 'desc' => 'Endpoint local, token HMAC, estado y restricción por IPs.'],
    ];
  }

  /**
   * Catálogo completo de variables y constantes editables.
   *
   * @return array<int, array<string, mixed>>
   */
  public static function getFields(): array
  {
    return [
      // --- Aplicación y Dominio ---
      ['key' => 'APP_NAME', 'section' => 'app', 'label' => 'Nombre del Sitio', 'help' => 'Nombre público de la aplicación (NAME_SITE).', 'type' => 'string', 'default' => 'Mi Sitio', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'APP_DESCRIPTION', 'section' => 'app', 'label' => 'Descripción del Sitio', 'help' => 'Descripción global usada en metatags y SEO.', 'type' => 'string', 'default' => '', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'APP_ENV', 'section' => 'app', 'label' => 'Entorno Activo', 'help' => 'En compilación a producción siempre se fuerza fail-closed a production.', 'type' => 'enum', 'options' => ['development', 'testing', 'production'], 'default' => 'development', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'FORCE_DOMAIN', 'section' => 'app', 'label' => 'Dominio Canónico (Producción)', 'help' => 'Redirige tráfico en producción al dominio único (ej: www.mi-sitio.com).', 'type' => 'string', 'default' => '', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'ALLOWED_HOSTS', 'section' => 'app', 'label' => 'Hosts Permitidos', 'help' => 'Lista separada por comas anti Host Header Poisoning.', 'type' => 'string', 'default' => '', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'TRUSTED_PROXIES', 'section' => 'app', 'label' => 'Proxies Confiables (IP/CIDR)', 'help' => 'IPs/CIDRs de proxies o CDN autorizados para X-Forwarded-*.', 'type' => 'string', 'default' => '', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'DEFAULT_THEME', 'section' => 'app', 'label' => 'Tema Visual por Defecto', 'help' => 'Preferencia inicial en navegador.', 'type' => 'enum', 'options' => ['system', 'light', 'dark'], 'default' => 'system', 'storage' => 'compiled', 'target' => 'env'],

      // --- Base de Datos ---
      ['key' => 'DB_CONNECTION', 'section' => 'database', 'label' => 'Driver BD', 'help' => 'Motor de base de datos utilizado.', 'type' => 'enum', 'options' => ['mysql', 'pgsql', 'sqlite'], 'default' => 'mysql', 'storage' => 'vault', 'target' => 'env'],
      ['key' => 'DB_HOST', 'section' => 'database', 'label' => 'Servidor BD', 'help' => 'Host o IP del servidor de base de datos.', 'type' => 'string', 'default' => '127.0.0.1', 'storage' => 'vault', 'target' => 'env'],
      ['key' => 'DB_PORT', 'section' => 'database', 'label' => 'Puerto BD', 'help' => 'MySQL: 3306, PostgreSQL: 5432.', 'type' => 'string', 'default' => '3306', 'storage' => 'vault', 'target' => 'env'],
      ['key' => 'DB_DATABASE', 'section' => 'database', 'label' => 'Nombre BD o Archivo SQLite', 'help' => 'Nombre de la base de datos o ruta relativa (ej: Database/app.sqlite).', 'type' => 'string', 'default' => '', 'storage' => 'vault', 'target' => 'env'],
      ['key' => 'DB_USERNAME', 'section' => 'database', 'label' => 'Usuario BD', 'help' => 'Usuario de conexión a la base de datos.', 'type' => 'secret', 'default' => 'root', 'storage' => 'vault', 'target' => 'env'],
      ['key' => 'DB_PASSWORD', 'section' => 'database', 'label' => 'Contraseña BD', 'help' => 'Contraseña de acceso a la base de datos.', 'type' => 'secret', 'default' => '', 'storage' => 'vault', 'target' => 'env'],
      ['key' => 'DB_CHARSET', 'section' => 'database', 'label' => 'Charset BD', 'help' => 'Codificación recomendada: utf8mb4.', 'type' => 'string', 'default' => 'utf8mb4', 'storage' => 'vault', 'target' => 'env'],
      ['key' => 'DB_POOLING', 'section' => 'database', 'label' => 'Pooling PDO Singleton', 'help' => 'Reutiliza la conexión PDO durante la petición.', 'type' => 'bool', 'default' => true, 'storage' => 'compiled', 'target' => 'env'],

      // --- Seguridad y Caché ---
      ['key' => 'SEED', 'section' => 'security', 'label' => 'Semilla Criptográfica (SEED)', 'help' => 'Clave secreta para JWT, cookies firmadas y TokenModule (mín. 32 caracteres).', 'type' => 'secret', 'default' => '', 'storage' => 'vault', 'target' => 'env', 'generatable' => 'seed'],
      ['key' => 'CSRF_PROTECTION', 'section' => 'security', 'label' => 'Protección CSRF Global', 'help' => 'Valida token CSRF automáticamente en rutas POST/PUT/PATCH/DELETE.', 'type' => 'bool', 'default' => true, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'USE_CACHE', 'section' => 'security', 'label' => 'Caché del Sistema', 'help' => 'Activa caché de vistas y consultas del framework.', 'type' => 'bool', 'default' => true, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'CSS_INLINE', 'section' => 'security', 'label' => 'Inyectar CSS Inline', 'help' => 'Incrusta el CSS minificado directo en <style> dentro del <head>.', 'type' => 'bool', 'default' => false, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'MAX_INPUT_BYTES', 'section' => 'security', 'label' => 'Límite Input JSON/Raw (bytes)', 'help' => 'Tamaño máximo aceptado en php://input (defecto 10 MB = 10485760).', 'type' => 'int', 'min' => 1024, 'max' => 104857600, 'default' => 10485760, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'ALLOWED_TABLES', 'section' => 'security', 'label' => 'Whitelist Estricta de Tablas SQL', 'help' => 'Separadas por coma. Vacío = modo Zero-Config (validación sintáctica estricta).', 'type' => 'list', 'default' => [], 'storage' => 'compiled', 'target' => 'const'],
      ['key' => 'TAGS_VALID_TEXT_INPUT', 'section' => 'security', 'label' => 'Etiquetas HTML Permitidas (Editor)', 'help' => 'Whitelist usada por text_editor.js y sanitización.', 'type' => 'string', 'default' => '<h1><h2><h3><h4><strong><p><blockquote><ul><ol><li>', 'storage' => 'compiled', 'target' => 'const'],

      // --- Idiomas (i18n) ---
      ['key' => 'APP_MULTILANG', 'section' => 'i18n', 'label' => 'Activar Multiidioma', 'help' => 'Habilita detección automática de idioma y prefijos URL.', 'type' => 'bool', 'default' => false, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'APP_LOCALE', 'section' => 'i18n', 'label' => 'Idioma Principal', 'help' => 'Código ISO principal (ej: es, en, es_CL).', 'type' => 'string', 'default' => 'es', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'APP_FALLBACK_LOCALE', 'section' => 'i18n', 'label' => 'Idioma de Respaldo', 'help' => 'Se usa si falta una traducción en el idioma activo.', 'type' => 'string', 'default' => 'es', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'APP_LOCALES', 'section' => 'i18n', 'label' => 'Idiomas Disponibles', 'help' => 'Códigos separados por coma (ej: es,en).', 'type' => 'string', 'default' => 'es,en', 'storage' => 'compiled', 'target' => 'env'],

      // --- Correo SMTP ---
      ['key' => 'SMTP_HOST', 'section' => 'smtp', 'label' => 'Servidor SMTP', 'help' => 'Host SMTP de correo saliente.', 'type' => 'string', 'default' => '', 'storage' => 'vault', 'target' => 'env'],
      ['key' => 'SMTP_PORT', 'section' => 'smtp', 'label' => 'Puerto SMTP', 'help' => 'Común: 587 (STARTTLS) o 465 (SMTPS).', 'type' => 'string', 'default' => '587', 'storage' => 'vault', 'target' => 'env'],
      ['key' => 'SMTP_USER', 'section' => 'smtp', 'label' => 'Usuario SMTP', 'help' => 'Cuenta o usuario remitente SMTP.', 'type' => 'secret', 'default' => '', 'storage' => 'vault', 'target' => 'env'],
      ['key' => 'SMTP_PASS', 'section' => 'smtp', 'label' => 'Contraseña SMTP', 'help' => 'Contraseña o App Password SMTP.', 'type' => 'secret', 'default' => '', 'storage' => 'vault', 'target' => 'env'],

      // --- Imágenes y Compresión ---
      ['key' => 'DIR_UPLOAD_MEDIA', 'section' => 'images', 'label' => 'Carpeta de Medios (Subida y Vista)', 'help' => 'Carpeta única compartida por DIR_UPLOAD_MEDIA (subida) y DIR_SHOW_MEDIA (mostrar en vistas). Vacío usa /Uploads/.', 'type' => 'string', 'default' => '', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'DIR_UPLOAD_GIF', 'section' => 'images', 'label' => 'Directorio de GIFs', 'help' => 'Vacío reutiliza DIR_UPLOAD_MEDIA.', 'type' => 'string', 'default' => '', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'DIR_SHOW_MEDIA_CUSTOM', 'section' => 'images', 'label' => 'Imágenes de Diseño del Proyecto', 'help' => 'Carpeta o URL para mostrar imágenes del diseño del proyecto (por defecto /App/Public/Img).', 'type' => 'string', 'default' => '/App/Public/Img', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'COMPRESS_IMAGE', 'section' => 'images', 'label' => 'Compresión Automática', 'help' => 'Optimiza imágenes subidas con ImgProcessModule.', 'type' => 'bool', 'default' => true, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'IMAGE_FORMAT', 'section' => 'images', 'label' => 'Formato de Salida', 'help' => 'Formato optimizado por defecto.', 'type' => 'enum', 'options' => ['webp', 'avif', 'png', 'jpg'], 'default' => 'webp', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'SMART_COMPRESSION', 'section' => 'images', 'label' => 'Compresión Inteligente', 'help' => 'Ajusta calidad según dimensiones de imagen.', 'type' => 'bool', 'default' => true, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'LAZY_LOAD_IMAGES', 'section' => 'images', 'label' => 'Lazy Load de Imágenes', 'help' => 'Activa carga diferida automática.', 'type' => 'bool', 'default' => true, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'MAX_IMAGE_WIDTH', 'section' => 'images', 'label' => 'Ancho Máximo (px)', 'help' => 'Resolución horizontal máxima.', 'type' => 'int', 'min' => 64, 'max' => 10000, 'default' => 1920, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'MAX_IMAGE_HEIGHT', 'section' => 'images', 'label' => 'Alto Máximo (px)', 'help' => 'Resolución vertical máxima.', 'type' => 'int', 'min' => 64, 'max' => 10000, 'default' => 1080, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'MAX_IMAGE_KB', 'section' => 'images', 'label' => 'Peso Máximo (KB)', 'help' => '0 = sin límite de KB.', 'type' => 'int', 'min' => 0, 'max' => 51200, 'default' => 0, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'MAX_IMAGE_PIXELS', 'section' => 'images', 'label' => 'Límite Total de Píxeles', 'help' => 'Protección contra image bombs (defecto 50 MP).', 'type' => 'int', 'min' => 10000, 'max' => 200000000, 'default' => 50000000, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'MAX_GIF_FRAMES', 'section' => 'images', 'label' => 'Máximo Frames GIF', 'help' => 'Límite de cuadros procesados en GIFs animados.', 'type' => 'int', 'min' => 1, 'max' => 5000, 'default' => 600, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'MIN_QUALITY', 'section' => 'images', 'label' => 'Calidad Mínima (%)', 'help' => 'Límite inferior en compresión iterativa.', 'type' => 'int', 'min' => 1, 'max' => 100, 'default' => 20, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'QUALITY_STEP', 'section' => 'images', 'label' => 'Paso de Reducción (%)', 'help' => 'Decremento por iteración.', 'type' => 'int', 'min' => 1, 'max' => 50, 'default' => 5, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'QUALITY', 'section' => 'images', 'label' => 'Calidad Landscape Base', 'help' => 'Calidad por defecto en orientación horizontal (1-100).', 'type' => 'int', 'min' => 1, 'max' => 100, 'default' => 70, 'storage' => 'compiled', 'target' => 'const'],
      ['key' => 'P_QUALITY', 'section' => 'images', 'label' => 'Calidad Portrait Base', 'help' => 'Calidad por defecto en orientación vertical (1-100).', 'type' => 'int', 'min' => 1, 'max' => 100, 'default' => 70, 'storage' => 'compiled', 'target' => 'const'],

      // --- Geolocalización ---
      ['key' => 'GEO_API_PRIMARY', 'section' => 'geo', 'label' => 'API Primaria IP', 'help' => 'Endpoint principal de geolocalización.', 'type' => 'url', 'default' => 'https://ip.guide/', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'GEO_API_FALLBACK', 'section' => 'geo', 'label' => 'API Respaldo IP', 'help' => 'Endpoint fallback si falla la primaria.', 'type' => 'url', 'default' => 'https://api.ipquery.io/', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'GEO_CONNECT_TIMEOUT', 'section' => 'geo', 'label' => 'Timeout Conexión (s)', 'help' => 'Segundos máximos de conexión.', 'type' => 'int', 'min' => 1, 'max' => 30, 'default' => 3, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'GEO_REQUEST_TIMEOUT', 'section' => 'geo', 'label' => 'Timeout Respuesta (s)', 'help' => 'Segundos máximos de petición.', 'type' => 'int', 'min' => 1, 'max' => 60, 'default' => 5, 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'MAXMIND_LICENSE_KEY', 'section' => 'geo', 'label' => 'License Key MaxMind', 'help' => 'Clave para actualizar GeoLite2-City.mmdb.', 'type' => 'secret', 'default' => '', 'storage' => 'vault', 'target' => 'env'],

      // --- SEO y Metadatos ---
      ['key' => 'OG_LOCALE', 'section' => 'seo', 'label' => 'Open Graph Locale', 'help' => 'Ej: es, es_CL, en_US.', 'type' => 'string', 'default' => 'es', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'NO_INDEX_HOSTS', 'section' => 'seo', 'label' => 'Subdominios No Indexables', 'help' => 'Hosts separados por coma excluidos de motores de búsqueda.', 'type' => 'string', 'default' => '', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'NO_INDEX', 'section' => 'seo', 'label' => 'Rutas No Indexables (NO_INDEX)', 'help' => 'Rutas separadas por coma con robots noindex.', 'type' => 'list', 'default' => ['/test', '/test1', '/sitemap.xml', '/robots.txt', '/terminar-sesion', '/admin*'], 'storage' => 'compiled', 'target' => 'const'],
      ['key' => 'SEO_PERSON_NAME', 'section' => 'seo', 'label' => 'Schema Persona: Nombre', 'help' => 'Usado en JSON-LD personSchema().', 'type' => 'string', 'default' => '', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'SEO_PERSON_URL', 'section' => 'seo', 'label' => 'Schema Persona: URL', 'help' => 'URL canónica del autor/persona.', 'type' => 'string', 'default' => '', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'SEO_PERSON_JOB', 'section' => 'seo', 'label' => 'Schema Persona: Cargo', 'help' => 'Título profesional para JSON-LD.', 'type' => 'string', 'default' => '', 'storage' => 'compiled', 'target' => 'env'],
      ['key' => 'SEO_PERSON_KNOWS', 'section' => 'seo', 'label' => 'Schema Persona: Especialidades', 'help' => 'Temas separados por coma.', 'type' => 'string', 'default' => '', 'storage' => 'compiled', 'target' => 'env'],

      // --- APIs Externas ---
      ['key' => 'GEMINI_API_KEY', 'section' => 'apis', 'label' => 'Google Gemini API Key', 'help' => 'Clave para GeminiModule y traducciones automáticas.', 'type' => 'secret', 'default' => '', 'storage' => 'vault', 'target' => 'env'],

      // --- Despliegue ---
      ['key' => 'DEPLOY_URL', 'section' => 'deploy', 'label' => 'URL Receptor Remoto (Solo Local)', 'help' => 'Ej: https://tudominio.com/deploy.php. Nunca se sube al servidor.', 'type' => 'url', 'default' => '', 'storage' => 'local_only', 'target' => 'env'],
      ['key' => 'DEPLOY_TOKEN', 'section' => 'deploy', 'label' => 'Token de Despliegue (DEPLOY_TOKEN)', 'help' => 'Clave secreta para autenticar frame deploy (mín. 16 caracteres).', 'type' => 'secret', 'default' => '', 'storage' => 'vault', 'target' => 'env', 'generatable' => 'token'],
      ['key' => 'DEPLOY_ENABLED', 'section' => 'deploy', 'label' => 'Habilitar Receptor deploy.php', 'help' => 'Permite desactivar deploy.php en producción sin borrarlo.', 'type' => 'bool', 'default' => false, 'storage' => 'vault', 'target' => 'env'],
      ['key' => 'DEPLOY_ALLOWED_IPS', 'section' => 'deploy', 'label' => 'IPs Autorizadas para Despliegue', 'help' => 'IPs o CIDRs separados por coma (vacío = cualquier IP con token+HMAC).', 'type' => 'string', 'default' => '', 'storage' => 'vault', 'target' => 'env'],
    ];
  }

  /**
   * Índice asociativo de campos por clave.
   *
   * @return array<string, array<string, mixed>>
   */
  public static function getFieldsMap(): array
  {
    $map = [];
    foreach (self::getFields() as $field) {
      $map[$field['key']] = $field;
    }
    return $map;
  }

  /**
   * Valida y normaliza un conjunto de valores de configuración según el esquema y perfil.
   *
   * @return array{values: array<string, mixed>, errors: array<string, string>, blocked: array<int, string>}
   */
  public static function validate(array $rawValues, string $profile = 'local'): array
  {
    $fieldsMap = self::getFieldsMap();
    $clean = [];
    $errors = [];
    $blocked = [];

    foreach ($rawValues as $key => $val) {
      $upper = strtoupper(trim((string)$key));
      if (in_array($upper, self::PROTECTED_KEYS, true) || str_starts_with($upper, 'ROUTE_') || str_starts_with($upper, 'TIME_') || str_starts_with($upper, 'URL_')) {
        $blocked[] = (string)$key;
        continue;
      }
      if (!isset($fieldsMap[$upper])) {
        continue;
      }

      $spec = $fieldsMap[$upper];
      $type = $spec['type'];

      if ($type === 'bool') {
        $clean[$upper] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
      } elseif ($type === 'int') {
        if ($val === '' || $val === null) {
          $clean[$upper] = (int)$spec['default'];
        } elseif (!is_numeric($val)) {
          $errors[$upper] = 'Debe ser un número entero válido.';
        } else {
          $num = (int)$val;
          if (isset($spec['min']) && $num < $spec['min']) {
            $num = (int)$spec['min'];
          }
          if (isset($spec['max']) && $num > $spec['max']) {
            $num = (int)$spec['max'];
          }
          $clean[$upper] = $num;
        }
      } elseif ($type === 'enum') {
        $str = strtolower(trim((string)$val));
        $options = $spec['options'] ?? [];
        if (!in_array($str, $options, true)) {
          $errors[$upper] = 'Valor no permitido. Opciones: ' . implode(', ', $options);
        } else {
          $clean[$upper] = $str;
        }
      } elseif ($type === 'list') {
        if (is_array($val)) {
          $items = $val;
        } else {
          $items = explode(',', (string)$val);
        }
        $cleanList = [];
        foreach ($items as $item) {
          $trimmed = trim(str_replace(["\r", "\n", "\0"], '', (string)$item));
          if ($trimmed !== '' && !in_array($trimmed, $cleanList, true)) {
            $cleanList[] = $trimmed;
          }
        }
        $clean[$upper] = $cleanList;
      } elseif ($type === 'url') {
        $str = trim(str_replace(["\r", "\n", "\0"], '', (string)$val));
        if ($str !== '' && !preg_match('#^https?://#i', $str)) {
          $errors[$upper] = 'Debe comenzar con http:// o https://.';
        } else {
          $clean[$upper] = $str;
        }
      } else {
        // string o secret: eliminar saltos de línea para prevenir inyección en .env
        $clean[$upper] = str_replace(["\r", "\n", "\0"], '', (string)$val);
      }
    }

    if ($profile === 'production') {
      $clean['APP_ENV'] = 'production';
    }

    return [
      'values' => $clean,
      'errors' => $errors,
      'blocked' => $blocked,
    ];
  }

  /**
   * Claves cuyo destino en producción es el Vault cifrado.
   *
   * @return array<int, string>
   */
  public static function getVaultKeys(): array
  {
    $keys = [];
    foreach (self::getFields() as $f) {
      if ($f['storage'] === 'vault') {
        $keys[] = $f['key'];
      }
    }
    return $keys;
  }

  /**
   * Claves compilables como variables de entorno no sensibles.
   *
   * @return array<int, string>
   */
  public static function getCompiledEnvKeys(): array
  {
    $keys = [];
    foreach (self::getFields() as $f) {
      if ($f['storage'] === 'compiled' && $f['target'] === 'env') {
        $keys[] = $f['key'];
      }
    }
    return $keys;
  }

  /**
   * Claves compilables como constantes PHP directas.
   *
   * @return array<int, string>
   */
  public static function getCompiledConstKeys(): array
  {
    $keys = [];
    foreach (self::getFields() as $f) {
      if ($f['storage'] === 'compiled' && $f['target'] === 'const') {
        $keys[] = $f['key'];
      }
    }
    return $keys;
  }
}
