# AGENTS.md — Proyecto nuevo (Eber Framework)

Guía de trabajo para agentes de IA y desarrolladores en **proyectos derivados del Eber Framework**. Léelo siempre antes de programar.

> El framework vive en `vendor/eber/framework/` (Core, Base, Resources, config). Todo el trabajo del día a día ocurre dentro de `App/`. Arquitectura **MVC estricta** en 3 capas: Controladores (lógica de negocio), Modelos (datos) y Vistas (presentación por módulo).

---

## 1. Stack y requisitos

| Tecnología | Detalle |
|---|---|
| **PHP** | 8.0+ (tipos unión, `str_starts_with`, typed properties; entorno 8.3) |
| **Base de datos** | MySQL / MariaDB / PostgreSQL (`DB_CONNECTION` en `.env`) |
| **JS** | Vanilla, ES Modules (`export function`), sin frameworks |
| **CSS** | Custom properties + utilidades atómicas propias + JIT (`composer min-script`) |
| **Servidor** | Apache/Nginx, entry point único `index.php` |

---

## 2. Estructura del proyecto

```
tu-proyecto/
├── index.php                  # Front controller (NO tocar)
├── composer.json              # Dependencia: eber/framework + scripts CLI
├── .env / .env.example        # Variables de entorno (NUNCA comitear .env)
├── providers.json             # Service Providers
├── jsConfig.json              # Config minificación JS
├── .htaccess                  # Rewrites + caché
│
├── App/                       # ⬅ TODO TU CÓDIGO VIVE AQUÍ
│   ├── Controllers/           # Lógica de negocio (HomeControllers.php, …)
│   ├── Models/                # Procesamiento y suministro de datos (UserModels.php, …)
│   ├── Views/                 # Vista = carpeta por módulo + base + partes
│   │   └── Home/
│   │       ├── home.php       # Base/plantilla de la vista (estructura container)
│   │       ├── Hero/hero.php  # Parte enlazada con _part("Hero.hero")
│   │       └── ...
│   ├── Segment/               # Piezas reutilizables entre vistas
│   │   ├── Form/              # _form("Login.login")
│   │   ├── Menu/              # _menu("Home.menuHome")
│   │   └── Template/          # _template("Footer.footerUser")
│   ├── Components/            # Componentes autocontenidos con sus datos
│   │   └── Menu/menuHomeComponent.php
│   ├── Middleware/            # Interceptores de peticiones (auth, permisos)
│   │   └── MiddlewareInterface/MiddlewareInterface.php
│   ├── Route/                 # web.php, test.php… (se cargan solos)
│   ├── errorViews/            # Vistas de error (HandlerError.php)
│   ├── Providers/             # Service Providers del proyecto
│   ├── Config/                # config.php, CookieConfiguration, TokenConfiguration…
│   ├── Public/                # Css/, Js/ (proyecto), Min/ (generado), Img/, Uploads/
│   └── Rsc/                   # Ico/, Fonts/, Library/ (copiados del framework)
│
├── Bootstrap/App.php          # Ciclo de vida de providers (no tocar)
├── Cache/                     # Caché (gitignored)
├── Logs/                      # Logs de ejecución (gitignored)
├── Uploads/                   # Subidas (gitignored)
└── vendor/eber/framework/     # ⚠ EL FRAMEWORK: Core/, Base/, Resources/ — NO EDITAR
```

---

## 3. Arquitectura MVC estricta (LA REGLA DE ORO)

```
Request → Route (App/Route/) → Middlewares → Controlador → Modelos → Vista (base + partes + componentes)
```

### 3.1 Controladores (`App/Controllers/`) — SOLO lógica de negocio

- Extienden `Base\Control\Control`.
- Reciben la petición, orquestan el flujo, **llaman a los modelos** para obtener/procesar datos y responden (vista, JSON o redirect).
- **Prohibido**: SQL directo, consultas al Builder y HTML. Eso es de Modelos y Vistas.
- Nomenclatura: `<Vista>Controllers.php` (plural) → `HomeControllers.php`, `LoginControllers.php`.
- Métodos: una acción por método, `camelCase`.

```php
<?php

namespace App\Controllers;

use Base\Control\Control;

class HomeControllers extends Control
{
  public function home()
  {
    $posts = HomeModels::getRecentPosts(6);
    return $this->view("Home.home", ["posts" => $posts]);
  }
}
```

### 3.2 Modelos (`App/Models/`) — procesamiento y suministro de datos

- Procesan datos, consultan BD y **sirven los datos** a controladores, middlewares y componentes.
- Extienden `Base\Builder\Builder` y definen `protected $table`.
- Nomenclatura: `<Dominio>Models.php` → `UserModels.php`, `VisitModels.php`.
- Métodos estáticos para consultas de solo lectura; instancia para operaciones con `$table`.

```php
<?php

namespace App\Models;

use Base\Builder\Builder;

class HomeModels extends Builder
{
  protected $table = "posts";

  /**
   * Obtiene los posts recientes publicados.
   *
   * @param int $limit Cantidad máxima de posts.
   * @return array Lista de posts.
   */
  public static function getRecentPosts(int $limit = 6): array
  {
    return (new self())->select()->where("state", "published")
      ->orderBy("created_at", "DESC")->limit($limit)->get();
  }
}
```

### 3.3 Vistas (`App/Views/`) — una carpeta por vista

**Estructura de una vista:**

```
App/Views/
└── Home/                  # Carpeta de la vista (PascalCase)
    ├── home.php           # ⭐ BASE: plantilla que conecta las partes
    ├── Hero/              # Subcarpeta por parte (PascalCase)
    │   └── hero.php       # Parte (archivo en camelCase)
    ├── Description/
    │   └── description.php
    └── Product/
        └── product.php
```

**Base (`home.php`):** es la estructura/plantilla de la vista. Conecta las partes en orden y define el **`div.container`** con sus variantes responsivas (`container-xl-mid`, `container-sml`) para estandarizar el tamaño/ancho que tendrá la vista en cada breakpoint.

```php
<?php
  /** @var array $posts */
?>

<div class="container container-xl-mid container-sml flex-column gap20 w100">

  <?php
    _component("Menu.menuHome");   // componente autocontenido
    _part("Home.hero", ["posts" => $posts]);
    _part("Home.description");
    _part("Home.product");
  ?>

</div>
```

**Partes (`hero.php`):** se enlazan desde la base con `_part("Home.hero")`. La ruta es `Carpeta.nombre` y el framework la resuelve recursivamente. Reciben los datos de la vista vía `ViewData` (variables en `$` directas).

**Helpers de vistas disponibles** (autoload de `Base/Helpers/Part.php`):

| Función | Uso |
|---|---|
| `_part("Home.hero", $data)` | Incluye una parte de `App/Views/` |
| `_form("Login.login")` | Incluye un formulario de `App/Segment/Form/` |
| `_menu("Home.menuHome")` | Incluye un menú de `App/Segment/Menu/` |
| `_template("Footer.footerUser")` | Incluye un template de `App/Segment/Template/` |
| `_component("Menu.menuHome")` | Renderiza un componente autocontenido |
| `_each("Home.postCard", $posts, "post")` | Repite la parte por cada item (da `$post`, `$index`, `$first`, `$last`…) |
| `_if($condicion, "Home.banner")` | Incluye la parte solo si se cumple la condición |
| `_partToString(...)` / `_componentToString(...)` | Version que retorna string en vez de imprimir |
| `e()` / `ee()` | Escape HTML anti-XSS |

### 3.4 Componentes (`App/Components/`) — piezas de código con sus datos

- Piezas autocontenidas que **se alimentan solas** (de modelos, sesión o parámetros) sin depender del controlador.
- Cada componente = clase estática con `data()` + una vista que lo renderiza.
- Nomenclatura: `App/Components/<Carpeta>/<Nombre>Component.php` → clase `NombreComponent`.
- Uso en cualquier vista: `_component("Menu.menuHome")` o `_component("UserPreview.userPreview", $id)` (si pasas un escalar se convierte en `['id' => $id]`).

```php
<?php

namespace App\Components\Menu;

use Base\Module\Session;

class menuHomeComponent
{
  /**
   * Datos del componente.
   *
   * @param string $view Ruta de la vista a renderizar.
   * @param string $viewType Tipo: part|form|menu|template.
   * @param array $params Parámetros recibidos desde _component().
   * @return array Datos para la vista.
   */
  public static function data($view = "Home.menuHome", $viewType = "menu", $params = [])
  {
    return [
      "connect"  => Session::session_active(),
      "username" => Session::session_data("username"),
    ];
  }
}
```

### 3.5 Middlewares (`App/Middleware/`) — interceptores de peticiones

- Interceptan la petición antes del controlador: auth, permisos, visitas, validaciones.
- Implementan `App\Middleware\MiddlewareInterface\MiddlewareInterface` (`handle($requestData, callable $next)`).
- **Se alimentan de los modelos** para validar reglas de negocio; nunca consultan BD directo.
- Si la petición no pasa, responden con `ResponseModule::redirect(...)`; si pasa, devuelven `$next($requestData)`.

```php
<?php

namespace App\Middleware;

use App\Middleware\MiddlewareInterface\MiddlewareInterface;
use App\Models\UserModels;
use Base\Module\ResponseModule;
use Base\Module\Session;

class DashboardMiddleware implements MiddlewareInterface
{
  public function handle($requestData, callable $next)
  {
    if (!Session::session_active()) {
      return ResponseModule::redirect("/ingresar");
    }

    $user = Session::session_data("username");
    if (!UserModels::canAccessDashboard($user, $urlUser)) {
      return ResponseModule::redirect("/panel/" . $user, "Sin permisos.", 1);
    }

    return $next($requestData);
  }
}
```

### 3.6 Rutas (`App/Route/web.php`)

- Se cargan solas (recursivo con `RouteLoader`), sin registrarlas en ningún lado.
- Usa grupos con `prefix()` y `middleware()` para agrupar permisos.

```php
<?php

use Core\Route;
use App\Controllers\HomeControllers;
use App\Controllers\Dashboard\DashboardControllers;
use App\Middleware\AuthMiddleware;
use App\Middleware\DashboardMiddleware;

// Grupo con prefijo y middleware (solo usuarios logueados con permiso)
Route::prefix("/panel/:user")->middleware([DashboardMiddleware::class])->group(function () {
  Route::get("/", [DashboardControllers::class, "panel"]);
});

// Grupo con middleware (no visible si estás logueado)
Route::middleware([AuthMiddleware::class])->group(function () {
  Route::get("/", [HomeControllers::class, "home"]);
  Route::get("/ingresar", [LoginControllers::class, "login"]);
  Route::post("/ingresar", [LoginControllers::class, "processLogin"]);
});
```

---

## 4. Módulos del framework más usados (`Base\Module\`)

| Módulo | Uso |
|---|---|
| `Session` | `session_active()`, `session_data()`, `create_user_session()`, `role()`, `admin()` |
| `ResponseModule` | `redirect()`, respuestas JSON, `sendContent()` |
| `SeoModule` | `setTitle()`, `setMetaDescription()`, `setOpenGraph()`, `sitemap()`, `robots()` |
| `HttpPostModule` | Entrada de `$_GET`/`$_POST` sanitizada (nunca superglobales directas) |
| `Builder` | `Base\Builder\Builder` — query builder fluido anti-inyección |
| `ImgProcessModule` | Procesamiento/compresión de imágenes |
| `ValidatorModule` | Validación de datos |
| `DateTimeModule` | Fechas y "time ago" |
| `TextModule` | Utilidades de texto |

---

## 5. Comandos (scripts de Composer)

```bash
composer min-script          # JIT + minificación CSS/JS → App/Public/Min + jit-compiled.css
composer install-font        # Instalar fuentes (woff2/ttf/otf)
composer reset-font          # Gestor/reset de fuentes instaladas
composer create-table-mysql   # Inicializar tablas BD MySQL
composer create-table-pgsql   # Inicializar tablas BD PostgreSQL
composer update-geoip        # Descargar GeoLite2 actualizado
```

⚠ Tras cambiar CSS/JS siempre ejecutar `composer min-script`.

---

## 6. Reglas críticas

1. **NUNCA escribir código en el framework (`vendor/eber/framework/`)**: Todos los módulos, helpers, modelos y utilidades adicionales del proyecto deben residir dentro de `App/` (como en `/App/Rsc/Helper/`). Si se requiere una funcionalidad o módulo a nivel de framework, **NUNCA debes modificar `vendor/eber/framework/` directamente**; debes **SOLICITARLO AL USUARIO** para que sea escrito directamente en el repositorio del framework y luego poder utilizarlo.
2. **MVC estricto:** controladores sin SQL ni HTML, modelos sin HTML, vistas sin lógica de negocio.
3. **Iconos:** `svg("nombre-icono", "clases")` lee de `App/Rsc/Ico/` (copiado del framework). Para un icono nuevo: añadirlo en el framework (`Resources/Ico`) y copiarlo a `App/Rsc/Ico/`.
4. **No editar generados:** `App/Public/Min/*`, `App/Public/Css/jit-compiled.css`, `App/Config/preloadFonts.json` → se regeneran con `composer min-script`.
5. **`.env` nunca se commitea** (gitignored). Usar `.env.example` como plantilla.
6. **Constantes:** usar las de `config.php` del framework (`ROUTE_VIEW`, `ROUTE_ICO`, `TIME_DAY`, `URL_IMG`, `NAME_SITE`, `DOMAIN`…), nunca rutas literales.
7. **Seguridad:** no usar valores de `$_ENV`/`$_SERVER`/`$_GET`/`$_POST` sin sanitizar. El Builder ya escapa; NO concatenar SQL.
8. **Tablas nuevas:** registrarlas en `ALLOWED_TABLES` (`config.php` del framework o `App/Config/config.php`).
9. **Escribir en español** (código, docblocks, commits y docs).

---

## 7. Convenciones de código

### PHP
- Namespaces: `App\Controllers\`, `App\Models\`, `App\Middleware\`, `App\Components\` (PSR-4).
- Clases `PascalCase` (`HomeControllers`, `UserModels`), métodos `camelCase` con visibilidad explícita.
- **Indentación de 2 espacios**, comillas dobles, sin cerrar etiqueta `?>`.
- **Docblocks PHPDoc en español** con `@param` y `@return` en cada método público.
- Type hints + return types (PHP 8): `public static function getRecentPosts(int $limit = 6): array`.
- Modelos extienden `Builder` con `protected $table`; consultas solo con el query builder.

### JavaScript
- Vanilla, ES Modules: `export function nombre() { ... }`.
- **JSDoc en español** (`@function`, `@description`, `@example`, `@css`, `@requires`, `@returns`).
- JS del proyecto en `App/Public/Js/` (se minifica a `Min/Js`).
- Sin dependencias; si necesitas GSAP está en `App/Rsc/Library/Gsap`.

### CSS
- Utilidades atómicas propias (`center`, `flex-row`, `gap10`, `w50`, `x25`, `bold600`, `br50`, `back8`, `color2`…) → compiladas por JIT a `jit-compiled.css`.
- **`container`, `container-xl-mid`, `container-sml`** → estandarizan el ancho de cada vista en sus breakpoints (usar en la base de cada vista).
- Variables de tema en `App/Public/Css/_configTheme.css` (`--back-color`, `--text-primary`, `--success`…); tema oscuro en `[data-theme="dark"]`.

---

## 8. Flujo de trabajo habitual

1. **Ruta** en `App/Route/web.php` (con middleware si aplica).
2. **Controlador**: valida la petición, llama al modelo, decide la respuesta.
3. **Modelo**: procesa y sirve los datos.
4. **Vista**: carpeta en `App/Views/<Vista>/`, base `<vista>.php` con `div.container`, partes en subcarpetas enlazadas con `_part()`; piezas reutilizables a `App/Segment/` y componentes autocontenidos a `App/Components/`.
5. **SEO/Metas** con `SeoModule` en el controlador.
6. **JS/CSS**: después de cambios, `composer min-script`.

---

## 9. Commits: Conventional Commits

Prefijo + español (o inglés si queda más claro), en pasado/imperativo:

| Prefijo | Uso |
|---|---|
| `feat:` | Nueva funcionalidad |
| `fix:` | Corrección de bug |
| `perf:` | Optimización de rendimiento |
| `refactor:` | Refactor sin cambio de comportamiento |
| `docs:` | Documentación |
| `style:` | Formato (sin cambio de lógica) |
| `test:` | Pruebas |

Ejemplo: `feat(home): agregar hero con datos de HomeModels`, `fix(middleware): redirigir panel sin permisos`.

---

## 10. Antipatrones a evitar

- ❌ SQL directo o `$_GET`/`$_POST` crudos en controladores — usar modelos y `HttpPostModule`.
- ❌ Lógica de negocio o consultas dentro de vistas o componentes — van a modelos.
- ❌ Duplicar piezas entre vistas — mover a `App/Segment/` o componente.
- ❌ Añadir frameworks/libs frontend sin avisar.
- ❌ Comitear `.env`, `Logs/`, `Cache/`, `App/Public/Min/`, mmdb.
- ❌ Escribir o editar código en los archivos del framework (`vendor/eber/framework/`). Si se necesita un módulo del framework, solicitarlo al usuario para que se escriba en el repositorio del framework primero.