# SeoModule

Módulo unificado para gestión de SEO. Centraliza las funcionalidades de sitemap.xml, robots.txt y cabeceras X-Robots-Tag.

```php
use Base\module\SeoModule;
```

---

## Sitemap.xml

Genera dinámicamente el archivo sitemap.xml basándose en las rutas registradas.

### `SeoModule::sitemap()`

```php
SeoModule::sitemap(
  array $excludeRoutes = [],   // Rutas a excluir
  array $routeOptions = []     // Opciones SEO por ruta
);
```

**Ejemplo completo:**

```php
// En tu controlador
public function sitemap(){
  SeoModule::sitemap(
    // Rutas a excluir del sitemap
    ['/admin', '/login', '/test*'],
    
    // Opciones SEO por ruta
    [
      '/' => [
        'priority' => 1.0,
        'changefreq' => 'daily',
        'lastmod' => '2024-01-15'
      ],
      '/blog' => [
        'priority' => 0.8,
        'changefreq' => 'weekly'
      ]
    ]
  );
}
```

**Características:**
- Solo incluye rutas GET
- Excluye automáticamente rutas dinámicas (con `:`)
- Soporta wildcards (`/admin*`)
- Genera XML válido con header `Content-Type: application/xml`

---

## Robots.txt

Genera dinámicamente el archivo robots.txt sin necesidad de un archivo físico.

### `SeoModule::robots()`

```php
SeoModule::robots(
  array $disallow = [],        // Rutas a bloquear
  array $allow = [],           // Rutas a permitir
  string $userAgent = '*',     // User-agent (default: todos)
  bool $includeSitemap = true  // Incluir referencia al sitemap
);
```

**Ejemplo básico:**

```php
// Permitir todo + sitemap
SeoModule::robots();

// Salida:
// User-agent: *
//
// Sitemap: http://tudominio.com/sitemap.xml
```

**Ejemplo con bloqueos:**

```php
SeoModule::robots(
  ['/admin/', '/private/', '/api/'],  // Disallow
  ['/api/public/'],                    // Allow
  '*',                                  // User-agent
  true                                  // Incluir sitemap
);

// Salida:
// User-agent: *
// Allow: /api/public/
// Disallow: /admin/
// Disallow: /private/
// Disallow: /api/
//
// Sitemap: http://tudominio.com/sitemap.xml
```

### `SeoModule::robotsAdvanced()`

Para configuraciones con múltiples user-agents.

```php
SeoModule::robotsAdvanced(
  array $rules,                // Reglas por user-agent
  bool $includeSitemap = true
);
```

**Ejemplo:**

```php
SeoModule::robotsAdvanced([
  '*' => [
    'disallow' => ['/admin/', '/private/'],
    'allow' => []
  ],
  'Googlebot' => [
    'disallow' => [],
    'allow' => ['/api/']
  ],
  'Bingbot' => [
    'disallow' => ['/internal/']
  ]
]);
```

---

## X-Robots-Tag Headers

Gestiona cabeceras HTTP para indicar a buscadores que no indexen páginas específicas.

### `SeoModule::noindex()`

Registra rutas que deben tener `X-Robots-Tag: noindex`.

```php
SeoModule::noindex(['/admin', '/login', '/test*']);
```

### `SeoModule::applyHeaderIfMatch()`

Aplica la cabecera si la ruta actual coincide con alguna registrada.

```php
// Generalmente usado en middleware
SeoModule::applyHeaderIfMatch();
```

### `SeoModule::customTag()`

Registra rutas con directivas personalizadas.

```php
SeoModule::customTag([
  '/draft' => 'noindex',
  '/archive' => 'noindex, nofollow',
  '/private' => 'none'
]);
```

**Directivas válidas:**
- `noindex`
- `nofollow`
- `noindex, nofollow`
- `noarchive`
- `nosnippet`
- `notranslate`
- `noimageindex`
- `none`

### `SeoModule::applyTag()`

Aplica una cabecera directamente sin verificar rutas.

```php
// En cualquier controlador
SeoModule::applyTag('noindex, nofollow');
```

### Métodos auxiliares

| Método | Descripción |
|--------|-------------|
| `getDirective($uri)` | Obtiene la directiva para una URI |
| `shouldApplyTag($uri)` | Verifica si debe aplicarse tag |
| `getRegisteredRoutes()` | Obtiene todas las rutas registradas |
| `resetTags()` | Limpia todas las rutas registradas |
| `removeTag($route)` | Elimina una ruta específica |

---

## Integración Típica

### 1. Rutas (route.php)

```php
use App\controllers\sitemapController;

Route::get("/sitemap.xml", [sitemapController::class, "sitemap"]);
Route::get("/robots.txt", [sitemapController::class, "robots"]);
```

### 2. Controlador (sitemapController.php)

```php
use Base\module\SeoModule;

class sitemapController {
  
  public function sitemap(){
    SeoModule::sitemap(NO_INDEX, $this->getSeoData());
  }

  public function robots(){
    SeoModule::robots();
  }
}
```

### 3. Middleware (noIndexMiddleware.php)

```php
use Base\module\SeoModule;

class noIndexMiddleware {
  
  public function handle($requestData, callable $next){
    SeoModule::noindex(NO_INDEX);
    SeoModule::applyHeaderIfMatch();
    return $next($requestData);
  }
}
```

### 4. Configuración global (config.php)

```php
// Rutas que NO deben ser indexadas
define('NO_INDEX', [
  '/admin',
  '/login', 
  '/test*',
  '/crear-publicacion'
]);
```
