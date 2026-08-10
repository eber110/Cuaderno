# Arquitectura de Seguridad: Escaneo de Rutas, Anti-Scraping, Intrusiones y Bloqueo de Accesos

Este sistema proporciona la arquitectura de seguridad para Eber Framework, permitiendo a cada proyecto escanear sus rutas válidas (`/App/Route`), exportar el mapa a `App/Safety/routes_security.json`, registrar intrusiones en `App/Safety/intrusions.json` / `App/Safety/intrusions.log`, prevenir el scraping automatizado y guardar la persistencia de IPs bloqueadas en `App/Safety/blocked_ips.json`.

Por su parte, la carpeta `/Database` queda reservada exclusivamente para bases de datos de usuario/aplicación. Las bases de datos de componentes de sistema (como MaxMind GeoIP `GeoLite2-City.mmdb`) se ubican en `/App/DatabaseComponent/`.

---

## 1. Mapeo de Directorios de Seguridad y Componentes

| Directorio | Propósito | Contenido |
|---|---|---|
| `App/Safety/` | Archivos de configuración y registros de seguridad del proyecto | `routes_security.json`, `blocked_ips.json`, `intrusions.json`, `intrusions.log`, `scraping_rate.json` |
| `App/DatabaseComponent/` | Bases de datos de componentes de sistema del proyecto | `GeoLite2-City.mmdb` |
| `Database/` | Exclusivo para bases de datos de usuario/aplicación | `visitor_log`, SQLite del usuario |

---

## 2. Escaneo y Generación del Mapa de Rutas por Proyecto

Cada proyecto derivado gestiona sus rutas en la carpeta `App/Route/` y almacena su propio mapa de seguridad en `App/Safety/routes_security.json`.

### Generación vía CLI (Composer)
```bash
composer scan-routes
```

### Generación Programática
```php
use Base\Module\SecurityModule;

// Escanear /App/Route del proyecto actual y guardar en App/Safety/routes_security.json
SecurityModule::scanRoutes();
```

---

## 3. Estructura de `App/Safety/routes_security.json`

El archivo JSON diferencia entre rutas estáticas y dinámicas (rutas con variables en su URL):

```json
{
    "generated_at": "2026-08-09 21:15:00",
    "total_routes": 2,
    "static_routes": [
        {
            "method": "GET",
            "uri": "/",
            "has_variables": false,
            "protect_at_controller": false
        }
    ],
    "dynamic_routes": [
        {
            "method": "GET",
            "uri": "/usuario/:id",
            "pattern": "#^/usuario/(?P<id>[^/]+)$#u",
            "has_variables": true,
            "variables": ["id"],
            "protect_at_controller": true,
            "controller_notice": "Esta ruta contiene variables dinámicas en la URL. Se debe validar y sanitizar el tipo y formato de los parámetros a nivel de controlador."
        }
    ]
}
```

---

## 4. Uso del Módulo de Seguridad SOLID (`SecurityModule`)

La fachada `SecurityModule` unifica los servicios de seguridad respetando el principio SOLID:

```php
use Base\Module\SecurityModule;

// 1. Verificar si una IP está bloqueada (App/Safety/blocked_ips.json)
if (SecurityModule::isAccessBlocked($_SERVER['REMOTE_ADDR'])) {
    die('Acceso bloqueado por seguridad');
}

// 2. Bloquear una IP sospechosa
SecurityModule::blockAccess('192.168.1.100', 'Múltiples inyecciones SQL', 86400);

// 3. Registrar un evento de intrusión (App/Safety/intrusions.json)
SecurityModule::logIntrusion('Intento de escaneo de .env', ['ip' => $_SERVER['REMOTE_ADDR']]);

// 4. Detectar scrapers y bots de extracción automatizada
if (SecurityModule::isScraper()) {
    die('Solicitudes automatizadas no permitidas');
}

// 5. Detectar wrappers PHP maliciosos (php://filter, data://)
if (SecurityModule::containsMaliciousWrapper($_GET['file'])) {
    die('Stream wrapper no permitido');
}
```

---

## 5. Creación de Middlewares en el Proyecto

Los proyectos pueden definir sus propios Middlewares en `App/Middleware/` haciendo uso de la fachada `SecurityModule` o los servicios especializados de `Base\Module\Security\*`.
