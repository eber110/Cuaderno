# AGENTS.md — Eber Framework

Guía de trabajo para agentes de IA y desarrolladores. **Léelo siempre antes de programar.**

> Framework PHP moderno para sitios web dinámicos y de alto rendimiento. Arquitectura MVC en 3 capas (`Core` / `Base` / `App`), sin dependencias frontend (JS/CSS vanilla) y con sistema de minificación + JIT propios.

---

## 1. Stack y requisitos

| Tecnología | Detalle |
|---|---|
| **PHP** | 8.0+ (el código usa tipos unión `array\|callable\|string`, `str_starts_with`…; entorno 8.3) |
| **Base de datos** | MySQL / MariaDB / PostgreSQL (Driver vía `DB_CONNECTION` en `.env`) |
| **JS** | Vanilla, ES Modules (`export function`), sin frameworks |
| **CSS** | CSS custom properties + utilidades atómicas propias + JIT/compilación |
| **Composer** | Autoload PSR-4 + scripts CLI |
| **Servidor** | Apache/Nginx, entry point único `index.php` |

---

## 2. Estructura del proyecto

```
frame/                         # Repositorio del framework (también es un proyecto demo)
├── index.php                  # Front controller (NO mover; único entry point)
├── config.php                 # Constantes globales del framework (define ROUTE_*, URL_*, TIME_*)
├── composer.json              # Dependencias + scripts CLI
├── providers.json             # Lista de Service Providers
├── jsConfig.json              # Config de minificación JS (priority/exclude/defer/async)
├── .env / .env.example        # Variables de entorno (NUNCA comitear .env)
│
├── Core/                      # Núcleo (PSR-4: Core\)
│   ├── Route.php              # Router. API estática: Route::get/post/put/patch/delete
│   ├── Conexion.php           # Conexión BD
│   ├── ErrorHandler.php       # Renderiza vistas de error (ROUTE_ERROR_VIEW)
│   ├── ConfigLoader/          # Load, LoadViewStyle, ProviderLoader, RouteLoader
│   └── Load/                  # LoadEnv, LoadRoutes, LoadStyle
│
├── Base/                      # Framework (PSR-4: Base\)
│   ├── Control/Control.php    # Controlador base (view(), viewPart(), etc.)
│   ├── Builder/Builder.php    # Query Builder MySQL/PostgreSQL (fluido, anti-inyección)
│   ├── Module/                # 29 módulos reutilizables (ver tabla en §4)
│   ├── LibraryCssJit/         # Reglas JIT CSS (JitRule interface + reglas)
│   ├── Helpers/               # Part.php (vistas parciales), SvgModule.php (svg())
│   ├── Cookie/                # CookieConfiguration
│   ├── Error/HandlerError.php # Plantilla de error (se copia a App/errorViews/)
│   ├── Providers/ServiceProvider.php  # Clase base de providers
│   └── ScriptComposer/        # Scripts CLI → ver §5
│
├── App/                       # CAPA DE APLICACIÓN (lo que cambia por proyecto)
│   ├── Controllers/           # Controladores del proyecto
│   ├── Models/                # Modelos
│   ├── Views/                 # Vistas (dummy_w.php ejemplo)
│   ├── errorViews/            # Vistas de error (HandlerError.php)
│   ├── Route/                 # <- DEFINICIÓN DE RUTAS del proyecto
│   ├── Middleware/            # (con subcapa MiddlewareInterface/)
│   ├── Segment/               # Components reutilizables: Form/, Menu/, Template/
│   ├── Providers/             # Service Providers del proyecto
│   ├── Config/config.php      # Config hereda del framework (App/Config/config.php)
│   ├── Public/                # Assets finales del proyecto
│   │   ├── Css/               # theme.css, _configTheme.css (+ generados jit/compilado)
│   │   ├── Js/               # JS propio del proyecto (se minifica a Min/Js)
│   │   └── Min/               # ⚠ GENERADO por min-script (gitignored)
│   └── Rsc/                   # Recursos copiados del framework (Fonts, Ico, Library)
│       ├── Ico/               # Iconos SVG runtime (svg() los lee de aquí)
│       └── Library/           # Librerías (Gsap/…; carga config en App/Config/loadLibraryJsConfiguration.php)
│
├── Resources/                # FUENTE canónica de assets del framework ⚠ NO BORRAR
│   ├── Css/                   # CSS del framework (styles, components, dark-mode…)
│   ├── Js/                    # JS del framework (12 + 27 componentes en Js/Components/)
│   ├── Ico/                   # Iconos SVG Master (se copian a App/Rsc/Ico en cada proyecto)
│   ├── Fonts/                 # Fuentes (se copian a App/Rsc/Fonts)
│   ├── Library/Gsap/           # GSAP base (se copia a App/Rsc/Library)
│   ├── Img/                   # Imágenes por defecto
│   └── dbLocation/             # GeoLite2 mmdb (gitignored, descargable)
│
├── Cache/Views/               # Caché de vistas
├── Logs/                      # Logs en tiempo de ejecución (gitignored)
├── Docs/                      # Documentación por módulo (Route, Builder, SEO, Security…)
└── vendor/                    # Composer (gitignored)
```

---

## 3. Reglas críticas (IMPORTANTE)

1. **`Resources/` es la fuente de los scaffolds.** `Base/ScriptComposer/InitAppStructure.php` copia `Resources/{Ico,Fonts,Library}` a `App/Rsc/` en cada proyecto nuevo. **No elimines ni renombres nada ahí** sin avisar.
2. **Iconos:** la función global `svg($name_icon, $class, $transform)` (autoload `Base/Helpers/SvgModule.php`) lee de `ROUTE_ICON` (`App/Rsc/Ico/`). Para añadir un icono: agréguelo a `Resources/Ico` y **luego** a `App/Rsc/Ico`. Uso: `svg('heart-fill', 'color2 x25')`.
3. **No editar archivos generados:** `App/Public/Min/*`, `App/Public/Css/jit-compiled.css`, `preloadFonts.json`. Se regeneran con `composer min-script`.
4. **`.env` nunca se commitea** (está en `.gitignore`). Usa `.env.example` como plantilla.
5. **Constantes de ruta:** usa las definidas en `config.php` (`ROUTE_VIEW`, `ROUTE_CONTROLLER`, `ROUTE_ICO`, `TIME_DAY`, `TIME_HOUR`…) — no rutas literales.
6. **Escribir en español** (código, docblocks, commits y docs). El framework está en español.
7. **Sesión y auth:** usar `Base\Module\Session` (`session_active()`, `session_data()`, `create_user_session()`, `role()`, `admin()`).
8. **Seguridad:** no uses valores de `$_ENV`/`$_SERVER` sin sanitizarlos. El Builder ya escapa; NO concatenar SQL directamente.
9. **Permisos de tablas:** `ALLOWED_TABLES` en `config.php`; cualquier tabla nueva debe registrarse ahí.
10. **No comitear** archivos: `.env`, `Logs/`, `Cache/`, `*.mmdb`, `App/Public/Min/`.

---

## 4. Convenciones de código

### PHP
- Namespaces: `Core\` → `Core/`, `Base\` → `Base/`, `App\...` → `App/...` (PSR-4 en `composer.json`).
- **Clases y métodos:** `PascalCase` clases, `camelCase` métodos con visibilidad explícita (`public`/`private`/`protected`).
- **Estilos:** indentación de **2 espacios**, comillas dobles, sin cerrar etiqueta `?>`.
- **Docblocks PHPDoc en español** describen cada método con `@param` y `@return`. Ver ejemplo `Base/Module/SeoModule.php`.
- **Helpers:** funciones globales autoloaded vía `composer.json` "files" (`svg()`, `Part`, config) — accesibles desde cualquier vista.
- **Type hints + return types** (PHP 8): `public static function title(string $override = ''): string`.
- Preferir métodos **estáticos** para módulos (`Base\Module\X::metodo()`).
- Constantes favorecen `TIME_*` y `ROUTE_*` de `config.php`.

### JavaScript
- Vanilla, **ES Modules**: cada componente expone `export function nombre() { ... }`.
- **JSDoc en español** con tags: `@function`, `@description`, `@example`, `@css`, `@requires`, `@returns`. Ver `Resources/Js/Components/modal.js`.
- Archivos: `Resources/Js/*.js` (utilidades + 1 archivo por componente en `Js/Components/`).
- No añadir dependencias: si necesitas GSAP usa el incluido en `App/Rsc/Library/Gsap`.
- El orden/carga/async se controla en `jsConfig.json` (no en el HTML).

### CSS
- **Utilidades atómicas** propias: `center`, `flex-row`, `gap10`, `w50`, `x25`, `bold600`, `br50`, `back8`, `color2`… Compuestas por JIT (`LibraryCssJit`) y disponibles tras `min-script` → en `jit-compiled.css`.
- **Variables de tema** en `App/Public/Css/_configTheme.css`: `--back-color`, `--text-primary`, `--success`, `--danger`, `--font`, etc. El tema oscuro en `[data-theme="dark"]`.
- Reusar `Resources/Css/*.css` (components, animation, form…) antes de escribir CSS nuevo.

---

## 5. Comandos (scripts de Composer)

```bash
composer min-script          # Compila JIT + minifica CSS/JS → regenera App/Public/Min + jit-compiled.css
composer install-font        # Instalador interactivo de fuentes (woff2/ttf/otf)
composer reset-font          # Gestor/reset de fuentes instaladas
composer create-table-mysql   # Inicializa tablas BD MySQL
composer create-table-pgsql   # Inicializa tablas BD PostgreSQL
composer update-geoip        # Descarga GeoLite2 actualizado
```

⚠ Tras cambiar CSS/JS siempre ejecutar `composer min-script`. `InitAppStructure` (estructura /App) se ejecuta con `composer install/update`.

---

## 6. Flujo de trabajo habitual

1. **Ruta:** se agrega una entrada en un archivo dentro de `App/Route/` (`Route::get('/x', [Controlador::class,'metodo'])`). Los archivos se cargan dinámicamente con `RouteLoader`.
2. **Controlador:** clase en `App/Controllers/` que extiende `Base\Control\Control` → usa `$this->view('ruta.vista', $data)`.
3. **Vista:** archivos en `App/Views/` (las `.` se convierten en `/`). Variables disponibles vía `extract`.
4. **Datos:** modelos o consultas con `Base\Builder\Builder`.
5. **SEO/Metas:** `Base\Module\SeoModule` (`setTitle`, `setMetaDescription`, `setOpenGraph`, `sitemap`, `robots`, `noindex`…).
6. **Respuestas JSON/redirects:** `Base\Module\ResponseModule`.
7. **Etc.** — módulos de `Base/Module` resolver el problema o crear `App/`-módulo dentro.

---

## 7. Commits: Conventional Commits

Usar prefijo + español (o inglés si el mensaje queda más claro), en pasado/imperativo:

| Prefijo | Uso |
|---|---|
| `feat:` | Nueva funcionalidad |
| `fix:` | Corrección de bug |
| `perf:` | Optimización de rendimiento |
| `refactor:` | Refactor sin cambiar comportamiento |
| `docs:` | Documentación |
| `style:` | Formato (sin cambio de lógica) |
| `test:` | Pruebas |

Ejemplos reales de la repo:
```
feat(fonts): add wawoff2 modern WebAssembly compressor and auto-conversion
fix(jit): scan css files for theme variables and utility classes
perf(head): clean redundant preloads, ensure DOMAIN consistency
```

---

## 8. Documentación

La documentación por módulo está en `Docs/*.md`: `Route.md` (rutas), `grid`, `interactionModule.md`, `SecurityModule.md`, `SeoModule.md`, `HttpPostModule.md`, `ImgProcessModule.md`, `BUILDER_DOCS.md`, `GSAP_GUIDE.md`, `hostProtocol.md`, `PART_COMPONENTS_GUIDE.md`, `componentes-ui-js.md`.

Mantener la documentación actualizada cuando cambies módulos.

> **Proyectos nuevos:** `InitAppStructure.php` copia `AGENTS_PROYECTO.md` como `AGENTS.md` a la raíz de cada proyecto derivado. Esa guía contiene las convenciones MVC estrictas (controladores → modelos → vistas + componentes + middlewares) y los ejemplos de uso. Mantenerla sincronizada con este archivo.

---

## 9. Antipatrones a evitar

- ❌ Añadir frameworks/libs frontend sin avisar (mantener vanilla JS).
- ❌ Escribir `$_GET`/`$_POST` temporales directamente — usar `HttpPostModule`.
- ❌ Duplicar funcionalidades entre módulos — extender módulo existente.
- ❌ Comitear `.env` o artefactos generados (`Min/`, mmdb, logs).
- ❌ Usar `echo` fuera de vistas/`ResponseModule`.