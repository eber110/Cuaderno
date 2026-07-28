<?php

/**
 * ============================================
 * SISTEMA DE VISTAS Y COMPONENTES
 * ============================================
 * 
 * Helper centralizado para la gestión de vistas del framework.
 * Organizado en las siguientes secciones:
 * 
 * 1. VIEWDATA          - Gestión de variables de vista globales
 * 2. RESOLUCIÓN        - Resolución de rutas de archivos
 * 3. PARTES            - Inclusión e impresión de vistas (funciones _)
 * 4. COMPONENTES       - Sistema de componentes autocontenidos
 * 5. UTILIDADES HTML   - Helpers para generar HTML seguro
 * 6. UTILIDADES VISTA  - Slots, iteradores, condicionales
 * 7. DEBUG             - Herramientas de depuración
 * 
 * @package Base\Helpers
 */

// ╔════════════════════════════════════════════╗
// ║  1. VIEWDATA - GESTIÓN DE VARIABLES       ║
// ╚════════════════════════════════════════════╝

/**
 * Almacén global de variables de vista.
 * Permite que las funciones de inclusión accedan a los datos del controlador.
 * Soporta stack para vistas anidadas (push/pop).
 */
class ViewData
{

  /** @var array Almacén de datos de vista */
  private static array $data = [];

  /** @var array Historial de datos para restauración */
  private static array $stack = [];

  /**
   * Establece múltiples variables de vista (merge con las existentes).
   * @param array $data Array asociativo de variables
   */
  public static function set(array $data): void
  {
    self::$data = array_merge(self::$data, $data);
  }

  /**
   * Establece una variable específica.
   * @param string $key Nombre de la variable
   * @param mixed $value Valor de la variable
   */
  public static function setValue(string $key, mixed $value): void
  {
    self::$data[$key] = $value;
  }

  /**
   * Obtiene todas las variables de vista.
   * @return array
   */
  public static function get(): array
  {
    return self::$data;
  }

  /**
   * Obtiene una variable específica.
   * @param string $key Nombre de la variable
   * @param mixed $default Valor por defecto si no existe
   * @return mixed
   */
  public static function getValue(string $key, mixed $default = null): mixed
  {
    return self::$data[$key] ?? $default;
  }

  /**
   * Verifica si una variable existe.
   * @param string $key Nombre de la variable
   * @return bool
   */
  public static function has(string $key): bool
  {
    return array_key_exists($key, self::$data);
  }

  /**
   * Elimina una variable específica.
   * @param string $key Nombre de la variable
   */
  public static function remove(string $key): void
  {
    unset(self::$data[$key]);
  }

  /**
   * Limpia todas las variables de vista.
   */
  public static function clear(): void
  {
    self::$data = [];
  }

  /**
   * Guarda el estado actual en el stack (útil para vistas anidadas).
   */
  public static function push(): void
  {
    self::$stack[] = self::$data;
  }

  /**
   * Restaura el estado anterior desde el stack.
   */
  public static function pop(): void
  {

    if (!empty(self::$stack)) {
      self::$data = array_pop(self::$stack);
    }
  }
}

// ╔════════════════════════════════════════════╗
// ║  2. RESOLUCIÓN DE RUTAS DE ARCHIVOS       ║
// ╚════════════════════════════════════════════╝

/**
 * Busca un archivo recursivamente en un directorio y sus subdirectorios.
 * Mantiene un caché estático para mejorar el rendimiento.
 * 
 * @param string $baseDir Directorio base de búsqueda
 * @param string $module Módulo/carpeta a buscar
 * @param string $filename Nombre del archivo (sin extensión)
 * @return string|null Ruta completa del archivo o null
 */
function findFile(string $baseDir, string $module, string $filename): ?string
{

  static $cache = [];

  $cacheKey = $baseDir . '|' . $module;

  if (!isset($cache[$cacheKey])) {
    $cache[$cacheKey] = [];
    $dir = $baseDir . $module;

    if (is_dir($dir)) {
      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
      );

      foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
          $name = pathinfo($file->getFilename(), PATHINFO_FILENAME);
          $cache[$cacheKey][$name] = str_replace('\\', '/', $file->getPathname());
        }
      }
    }
  }

  return $cache[$cacheKey][$filename] ?? null;
}

/**
 * Resuelve la ruta de un archivo de vista.
 * Intenta primero ruta directa, luego búsqueda recursiva con caché.
 * 
 * @param string $rute Ruta en formato "modulo.archivo" o "modulo.subcarpeta/archivo"
 * @param string $baseDir Directorio base (ROUTE_VIEW, ROUTE_FORM, etc.)
 * @return string|null Ruta completa del archivo
 */
function resolvePath(string $rute, string $baseDir): ?string
{

  $parts = explode('.', $rute, 2);
  if (count($parts) < 2) {
    return null;
  }

  [$module, $filename] = $parts;

  // 1. Intento directo (estructura plana o con subcarpetas)
  $directPath = $baseDir . str_replace('.', '/', $rute) . '.php';
  if (file_exists($directPath)) {
    return $directPath;
  }

  // 2. Si tiene "/" es una ruta con subcarpetas, extraer solo el filename
  if (str_contains($filename, '/')) {
    $filename = basename($filename);
  }

  // 3. Búsqueda recursiva con caché
  $foundPath = findFile($baseDir, $module, $filename);

  return $foundPath ?? $directPath;
}

/**
 * Resuelve ruta de vista según el tipo.
 * Despacha a la constante de directorio correcta.
 * 
 * @param string $rute Ruta de la vista
 * @param string $type Tipo: 'part', 'form', 'menu', 'template'
 * @return string|null Ruta completa del archivo
 */
function resolveByType(string $rute, string $type = 'part'): ?string
{

  return match ($type) {
    'form' => form($rute),
    'menu' => menu($rute),
    'template' => template($rute),
    default => part($rute),
  };
}

// --- Resolvers por tipo de vista ---

/**
 * Obtiene la ruta de una vista parcial (App/Views/).
 * @param string $rute Ruta (ej: "Home.AllPost")
 * @return string|null
 */
function part(string $rute): ?string
{
  return resolvePath($rute, ROUTE_VIEW);
}

/**
 * Obtiene la ruta de un formulario (App/segment/form/).
 * @param string $rute Ruta (ej: "Admin.CreatePost")
 * @return string|null
 */
function form(string $rute): ?string
{
  return resolvePath($rute, ROUTE_FORM);
}

/**
 * Obtiene la ruta de un menú (App/segment/menu/).
 * @param string $rute Ruta (ej: "Primary.MainMenu")
 * @return string|null
 */
function menu(string $rute): ?string
{
  return resolvePath($rute, ROUTE_MENU);
}

/**
 * Obtiene la ruta de un template (App/segment/template/).
 * @param string $rute Ruta (ej: "Footer.Main")
 * @return string|null
 */
function template(string $rute): ?string
{
  return resolvePath($rute, ROUTE_TEMPLATE);
}

// ╔════════════════════════════════════════════╗
// ║  3. PARTES - INCLUSIÓN DE VISTAS          ║
// ╚════════════════════════════════════════════╝
//
// Funciones con prefijo "_" para incluir vistas directamente.
// Se usan dentro de las vistas PHP.
//
// Incluir (imprime directo):  _part(), _form(), _menu(), _template()
// Renderizar (retorna string): _partToString(), _formToString(), etc.
//

/**
 * Incluye una vista con datos (función base interna).
 * 
 * @param string|null $path Ruta del archivo
 * @param array $extraData Datos adicionales a pasar
 * @param bool $useViewData Si combinar con ViewData global
 * @return bool True si se incluyó correctamente
 */
function includeView(?string $path, array $extraData = [], bool $useViewData = true): bool
{

  if (!$path || !file_exists($path)) {
    return false;
  }

  $data = $useViewData ? array_merge(ViewData::get(), $extraData) : $extraData;
  extract($data);
  include $path;

  return true;
}

/**
 * Renderiza una vista como string (función base interna).
 * 
 * @param string|null $path Ruta del archivo
 * @param array $extraData Datos adicionales
 * @param bool $useViewData Si combinar con ViewData global
 * @return string|null HTML renderizado o null si falla
 */
function renderView(?string $path, array $extraData = [], bool $useViewData = true): ?string
{

  if (!$path || !file_exists($path)) {
    return null;
  }

  $data = $useViewData ? array_merge(ViewData::get(), $extraData) : $extraData;

  ob_start();
  extract($data);
  include $path;
  return ob_get_clean();
}

// --- Incluir vistas (imprime directo, usa ViewData) ---

/**
 * Incluye una parte de vista.
 * @param string $rute Ruta de la parte
 * @param array $extraData Datos adicionales a pasar (opcional)
 * @example <?php _part("Home.Sidebar"); ?>
 */
function _part(string $rute, array $extraData = []): void
{

  includeView(part($rute), $extraData);
}

/**
 * Incluye un formulario.
 * @param string $rute Ruta del formulario
 * @param array $extraData Datos adicionales a pasar (opcional)
 * @example <?php _form("Admin.CreatePost"); ?>
 */
function _form(string $rute, array $extraData = []): void
{

  includeView(form($rute), $extraData);
}

/**
 * Incluye un menú.
 * @param string $rute Ruta del menú
 * @param array $extraData Datos adicionales a pasar (opcional)
 * @example <?php _menu("Primary.MainMenu"); ?>
 */
function _menu(string $rute, array $extraData = []): void
{

  includeView(menu($rute), $extraData);
}

/**
 * Incluye un template.
 * @param string $rute Ruta del template
 * @param array $extraData Datos adicionales a pasar (opcional)
 * @example <?php _template("Pagination.Pagination"); ?>
 */
function _template(string $rute, array $extraData = []): void
{

  includeView(template($rute), $extraData);
}

// --- Renderizar vistas (retorna string, usa ViewData) ---

/**
 * Renderiza una parte de vista como string.
 * @param string $rute Ruta de la parte
 * @param array $data Datos adicionales (opcional)
 * @return string|null
 * @example <?= _partToString("Home.Sidebar") ?>
 */
function _partToString(string $rute, array $data = []): ?string
{
  return renderView(part($rute), $data);
}

/**
 * Renderiza un formulario como string.
 * @param string $rute Ruta del formulario
 * @param array $data Datos adicionales (opcional)
 * @return string|null
 */
function _formToString(string $rute, array $data = []): ?string
{
  return renderView(form($rute), $data);
}

/**
 * Renderiza un menú como string.
 * @param string $rute Ruta del menú
 * @param array $data Datos adicionales (opcional)
 * @return string|null
 */
function _menuToString(string $rute, array $data = []): ?string
{
  return renderView(menu($rute), $data);
}

/**
 * Renderiza un template como string.
 * @param string $rute Ruta del template
 * @param array $data Datos adicionales (opcional)
 * @return string|null
 */
function _templateToString(string $rute, array $data = []): ?string
{
  return renderView(template($rute), $data);
}

// ╔════════════════════════════════════════════╗
// ║  4. COMPONENTES AUTOCONTENIDOS            ║
// ╚════════════════════════════════════════════╝
//
// Los componentes son clases simples en App/Components/ que cargan
// sus propios datos sin depender de ViewData ni del controlador.
//
// Estructura de un componente:
//   class MiComponent {
//     public static function data($view = 'ruta', $viewType = 'form', $params = []): array {
//       return [ ...datos... ];
//     }
//   }
//
// Uso en vistas:
//   _component('Admin.Form.CreatePost');
//   _componentToString('Admin.Form.CreatePost', ['key' => 'value']);
//

/**
 * Normaliza la ruta de vista de un componente.
 * Convierte notaciones mixtas de puntos y slashes al formato "modulo.sub/archivo".
 *
 * @param string $view Ruta (ej: "Admin.Post.CreatePost" o "Admin/Post/CreatePost")
 * @param string $type Tipo: 'part', 'form', 'menu', 'template'
 * @return string|null Ruta completa del archivo
 */
function resolveComponentViewPath(string $view, string $type = 'part'): ?string
{

  // Normalizar: convertir "/" a "." para tener todo uniforme
  $view = str_replace('/', '.', $view);
  $parts = explode('.', $view);

  // Reconstruir como "modulo.subcarpeta/archivo"
  if (count($parts) >= 2) {
    $module = array_shift($parts);
    $view = $module . '.' . implode('/', $parts);
  }

  return resolveByType($view, $type);
}

/**
 * Renderiza un componente autocontenido e imprime directamente.
 *
 * @param string $component Nombre del componente (ej: "Admin.Form.CreatePost")
 * Se convierte a: App\Components\Admin\Form\CreatePostComponent
 * @param mixed $params Parámetros opcionales para el componente.
 *                      Si se pasa un valor no array (ej: ID), se convierte a ['id' => $valor].
 *
 * @example
 * <?php _component('Admin.Form.CreatePost'); ?>
 * <?php _component('Admin.Form.CreatePost', ['showTitle' => true]); ?>
 * <?php _component('Admin.Form.UpdatePost', 35); // Pasa ['id' => 35] ?>
 */
function _component(string $component, mixed $params = []): void
{
  echo _componentToString($component, $params);
}

/**
 * Renderiza un componente autocontenido y retorna el HTML como string.
 *
 * Lee la configuración ($view, $viewType) desde los valores por defecto
 * de la firma de data() usando ReflectionMethod. Luego resuelve la ruta,
 * obtiene los datos y renderiza la vista aislada del ViewData.
 *
 * @param string $component Nombre del componente (ej: "Admin.Form.CreatePost")
 * @param mixed $params Parámetros opcionales para el componente
 * @return string HTML renderizado del componente
 *
 * @example
 * $html = _componentToString('Admin.Form.CreatePost');
 * <?= _componentToString('Admin.Form.CreatePost', ['showTitle' => true]) ?>
 * <?= _componentToString('Admin.Form.UpdatePost', 35) ?>
 */
function _componentToString(string $component, mixed $params = []): string
{
  // Normalizar params: si no es array, asumir que es un ID
  if (!is_array($params)) {
    $params = ['id' => $params];
  }

  $parts = explode('.', $component);

  if (count($parts) < 2) {
    return "<!-- Component error: Invalid format. Use 'Folder.ComponentName' -->";
  }

  // Construir el nombre de clase completo
  $namespace = 'App\\Components\\' . implode('\\', array_slice($parts, 0, -1));
  $className = $namespace . '\\' . end($parts) . 'Component';

  if (!class_exists($className)) {
    return "<!-- Component not found: {$className} -->";
  }

  if (!method_exists($className, 'data')) {
    return "<!-- Component error: {$className} must have a data() method -->";
  }

  try {

    // Extraer $view y $viewType de los valores por defecto de data()
    $ref = new ReflectionMethod($className, 'data');
    $parameters = $ref->getParameters();

    $view = $parameters[0]->getDefaultValue() ?? '';
    $viewType = $parameters[1]->getDefaultValue() ?? 'part';

    // Resolver ruta usando la función centralizada
    $path = resolveComponentViewPath($view, $viewType);

    if (!$path || !file_exists($path)) {
      return "<!-- Component view not found: {$view} -->";
    }

    // Obtener datos del componente y combinar con parámetros (parámetros tienen prioridad)
    $data = array_merge($className::data($view, $viewType, $params), $params);

    // Renderizar aislado del ViewData
    return renderView($path, $data, false);
  } catch (\Throwable $e) {
    return "<!-- Component error: " . e($e->getMessage()) . " -->";
  }
}

  // ╔════════════════════════════════════════════╗
  // ║ 5. UTILIDADES HTML ║
  // ╚════════════════════════════════════════════╝

/**
 * Escapa HTML para prevenir XSS.
 * Alias corto de htmlspecialchars.
 *
 * @param string|null $string Texto a escapar
 * @return string
 * @example <p><?= e($userInput) ?></p>
 */
function e(?string $string): string
{
  return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Imprime texto escapado directamente.
 * @param string|null $string Texto a imprimir
 * @example <p><?php ee($userInput); ?></p>
 */
function ee(?string $string): void
{
  echo e($string);
}

/**
 * Genera atributos HTML desde un array.
 *
 * @param array $attributes Array de atributos
 * @return string
 * @example <div <?= attrs(['class' => 'box', 'id' => 'main', 'disabled' => true]) ?>>
 */
function attrs(array $attributes): string
{

  $result = [];

  foreach ($attributes as $key => $value) {
    if ($value === true) {
      $result[] = e($key);
    } elseif ($value !== false && $value !== null) {
      $result[] = e($key) . '="' . e((string)$value) . '"';
    }
  }

  return implode(' ', $result);
}

/**
 * Genera clases CSS condicionalmente.
 *
 * @param array $classes Array de clases (clave => condición o valor directo)
 * @return string
 * @example <div class="<?= classes(['btn', 'active' => $isActive]) ?>">
 */
function classes(array $classes): string
{

  $result = [];

  foreach ($classes as $key => $value) {
    if (is_int($key)) {
      // Valor directo: ['btn', 'primary']
      $result[] = $value;
    } elseif ($value) {
      // Condicional: ['active' => true]
      $result[] = $key;
    }
  }

  return implode(' ', $result);
}

/**
 * Genera estilos CSS inline desde un array.
 *
 * @param array $styles Array de estilos
 * @return string
 * @example <div style="<?= styles(['color' => 'red', 'margin' => '10px']) ?>">
 */
function styles(array $styles): string
{

  $result = [];

  foreach ($styles as $property => $value) {
    if ($value !== null && $value !== '') {
      $result[] = e($property) . ': ' . e((string)$value);
    }
  }

  return implode('; ', $result);
}

/**
 * Retorna un valor condicionalmente.
 *
 * @param bool $condition Condición a evaluar
 * @param string $value Valor si es true
 * @param string $else Valor si es false (opcional)
 * @return string
 * @example <button <?= when($isDisabled, 'disabled') ?>>
 */
function when(bool $condition, string $value, string $else = ''): string
{
  return $condition ? $value : $else;
}

          // ╔════════════════════════════════════════════╗
          // ║ 6. UTILIDADES DE VISTA ║
          // ╚════════════════════════════════════════════╝

/**
 * Incluye una vista múltiples veces iterando un array.
 * Cada iteración recibe: $item, $index, $key, $first, $last, $even, $odd.
 *
 * @param string $rute Ruta de la vista parcial
 * @param array $items Array de items a iterar
 * @param string $itemKey Nombre de la variable para cada item
 * @param string $type Tipo de vista: 'part', 'form', 'menu', 'template'
 *
 * @example
 * <?php _each('Blog.PostCard', $posts, 'post'); ?>
 * // En Blog/PostCard.php tendrás: $post, $index, $first, $last, etc.
 */
function _each(string $rute, array $items, string $itemKey = 'item', string $type = 'part'): void
{

  $path = resolveByType($rute, $type);

  if (!$path || !file_exists($path)) {
    return;
  }

  $total = count($items);
  $index = 0;

  foreach ($items as $key => $item) {
    $data = array_merge(ViewData::get(), [
      $itemKey => $item,
      'index' => $index,
      'key' => $key,
      'first' => $index === 0,
      'last' => $index === $total - 1,
      'even' => $index % 2 === 0,
      'odd' => $index % 2 !== 0,
    ]);

    extract($data);
    include $path;
    $index++;
  }
}

/**
 * Renderiza una vista solo si una condición es verdadera.
 *
 * @param bool $condition Condición a evaluar
 * @param string $rute Ruta de la vista
 * @param string $type Tipo de vista
 *
 * @example <?php _if($user->isAdmin(), 'Admin.AdminPanel'); ?>
 */
function _if(bool $condition, string $rute, string $type = 'part'): void
{

  if (!$condition) {
    return;
  }

  $path = resolveByType($rute, $type);
  includeView($path);
}

/**
 * Captura el output de un bloque de código como string.
 *
 * @param callable $callback Función a ejecutar
 * @return string Output capturado
 *
 * @example
 * $html = capture(function() {
 * _part('Header');
 * echo '<main>Content</main>';
 * _part('Footer');
 * });
 */
function capture(callable $callback): string
{

  ob_start();
  $callback();
  return ob_get_clean();
}

          // --- Sistema de Slots (secciones de layout) ---

/**
 * Guarda contenido en un slot para uso posterior.
 * Útil para layouts con múltiples secciones.
 *
 * @param string $name Nombre del slot
 * @param string|callable $content Contenido o callback
 *
 * @example
 * slot('sidebar', function() { _part('Widgets.RecentPosts'); });
 * slot('title', 'Mi Página');
 */
function slot(string $name, string|callable $content): void
{

  static $slots = [];

  if (is_callable($content)) {
    ob_start();
    $content();
    $slots[$name] = ob_get_clean();
  } else {
    $slots[$name] = $content;
  }

  ViewData::setValue('__slots', $slots);
}

/**
 * Obtiene el contenido de un slot.
 *
 * @param string $name Nombre del slot
 * @param string $default Valor por defecto
 * @return string
 * @example <aside><?= getSlot('sidebar', '<p>Sin sidebar</p>') ?></aside>
 */
function getSlot(string $name, string $default = ''): string
{
  $slots = ViewData::getValue('__slots', []);
  return $slots[$name] ?? $default;
}

/**
 * Verifica si un slot tiene contenido.
 *
 * @param string $name Nombre del slot
 * @return bool
 */
function hasSlot(string $name): bool
{
  $slots = ViewData::getValue('__slots', []);
  return isset($slots[$name]) && $slots[$name] !== '';
}

          // ╔════════════════════════════════════════════╗
          // ║ 7. DEBUG ║
          // ╚════════════════════════════════════════════╝

/**
 * Dump de una variable para debug (solo en desarrollo).
 *
 * @param mixed $var Variable a inspeccionar
 * @param bool $die Si terminar la ejecución después
 */
function dd(mixed $var, bool $die = true): void
{

  echo '
          <pre style="background:#1e1e1e;color:#dcdcdc;padding:15px;border-radius:8px;font-size:12px;overflow:auto;">';
  var_dump($var);
  echo '</pre>';

  if ($die) {
    die();
  }
}

/**
 * Dump de todas las variables de ViewData.
 */
function ddViewData(): void
{
  dd(ViewData::get());
}

/**
 * Muestra información de debug en un comentario HTML invisible.
 * Útil para debug sin romper el layout.
 *
 * @param mixed $var Variable a inspeccionar
 * @param string $label Etiqueta opcional
 */
function debugComment(mixed $var, string $label = 'Debug'): void
{
  echo "\n<!-- {$label}: " . e(print_r($var, true)) . " -->\n";
}
