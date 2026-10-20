# Plan Maestro: Estadísticas Avanzadas y Sistema de Suscripciones (Free & Premium)

---

> [!IMPORTANT]
> ## 0. Instrucciones Obligatorias y Protocolo de Bitácora para Agentes de IA
> Todo agente que participe en este proyecto **DEBE** leer y actualizar obligatoriamente este archivo siguiendo estas directrices:
> 1. **Lectura previa**: Antes de escribir una sola línea de código, revisa la **Bitácora de Ejecución (Sección 3)** para conocer exactamente en qué fase y tarea se encuentra el proyecto.
> 2. **No duplicar trabajo**: Si una tarea figura como `[✅ Completado]`, respeta su implementación y pruebas.
> 3. **Registro obligatorio del avance**: Al concluir cada tarea o fase, el agente debe añadir una entrada en la tabla de la bitácora indicando:
>    * Fecha y hora local.
>    * Fase y tarea realizada.
>    * Archivos modificados o creados.
>    * Resultado de la verificación / pruebas de ejecución.
>    * Estado resultante (`[🟡 En Progreso]` o `[✅ Completado]`).
> 4. **Respeto a las reglas de oro**: Mantener la arquitectura MVC estricta de **Eber Framework**, no tocar `vendor/eber/framework/`, escapar todas las salidas dinámicas (`e()`, `eUrl()`), y utilizar siempre consultas fluidas seguras vía `Builder`.

---

## 1. Auditoría y Análisis Completo de la Base de Datos

Se realizó una inspección física y estructural de las bases de datos SQLite existentes en el proyecto. La base de datos activa configurada en [`.env`](file:///c:/Users/eber/Proyectos/Cuaderno/.env#L35) es **`Database/clikhub.sqlite`** (831 KB en modo WAL).

### 1.1 Clasificación de Tablas

| Tabla | Registros | Estado | Propósito y Decisión |
|---|---|---|---|
| **`users`** | 2 | **Imprescindible** | Almacena las cuentas, credenciales cifradas, tokens de recuperación y estado de los usuarios. |
| **`roles`** | 2 | **Imprescindible** | Define los roles (`admin`, `user`) requeridos por el sistema de control de accesos. |
| **`userroles`** | 0 | **Imprescindible** | Tabla pivote que asocia a los usuarios con sus roles correspondientes. |
| **`user_designs`** | 2 | **Imprescindible** | Guarda la personalización completa del perfil (avatar, enlaces, colores, fondo, rrss, videos). |
| **`user_subscriptions_cache`** | 4 | **Imprescindible** | Caché de alto rendimiento para validar el estado de suscripción sin consultar APIs externas. Ahora aloja el campo `services`. |
| **`profile_views`** | 596 | **Imprescindible** | Registro analítico de visitas a perfiles. Ahora aloja `visitor_user_id` e índices de rendimiento para estadísticas. |
| **`link_clicks`** | 420 | **Imprescindible** | Registro analítico de clics por botón/enlace para el submenú de rendimiento individual. |
| **`active_sessions`** | 0 | **Necesaria** | Control en tiempo real de usuarios activos navegando para el badge *"en línea"* del dashboard. |
| **`ratelimits`** | 0 | **Imprescindible** | Módulo de seguridad contra ataques de fuerza bruta por IP y acción. |
| **`lemon_squeezy_orders`** | 0 | **Necesaria** | Registro histórico y auditoría de compras generadas a través de Lemon Squeezy. |
| **`lemon_squeezy_subscriptions`** | 0 | **Necesaria** | Control de ciclo de vida de pagos recurrentes, renovaciones y cancelaciones. |
| **`analytics_events`** | 0 | **Prescindible / Obsoleta** | Tabla sin uso en controladores ni modelos. No afecta el funcionamiento y puede mantenerse inactiva. |

> [!NOTE]
> Los archivos `Database/cuaderno.sqlite` y `App/DatabaseComponent/cuaderno.sqlite` se encuentran vacíos (0 bytes). Son residuos de versiones preliminares; la aplicación opera exclusivamente sobre `Database/clikhub.sqlite`.

---

## 2. Modelo de Suscripciones y Alcance Actual

### 2.1 Suscripciones Definidas en Producción

Actualmente el sistema comercial manejará únicamente **dos planes activos**:

| ID | Clave | Nombre del Plan | Estado | Asignación y Descripción |
|---|---|---|---|---|
| **1** | `free` | **Plan Free** | **Activo** | **Por defecto al registrarse**. Acceso estándar al perfil, edición de enlaces y métricas básicas (visitas y clics globales del mes). |
| **2** | `premium` | **Plan Premium** | **Activo** | **Suscripción de pago**. Desbloquea la suite completa de analíticas (GeoIP detallada, referrers por plataforma, feed «¿Quién te visitó?», heatmap horario y desglose individual por enlace). |

### 2.2 Campo Reservado para Futuras Mejoras (Add-ons Acumulables)

Para evitar reestructurar la base de datos más adelante, el campo en BD `services` está estructurado como una lista acumulable (ej. `"1"` o `"1,2"`).
* **Al registrarse un usuario**: Se le asigna automáticamente la suscripción Free: `services = '1'`.
* **Al suscribirse a Premium vía Lemon Squeezy**: El webhook actualiza el estado y suma el servicio: `services = '1,2'` (manteniendo `is_premium = 1`).
* **Futuras mejoras adicionales**: Se podrán incorporar como nuevos identificadores (ej. `3`, `4`, `5`) sin alterar la estructura de la base de datos.

---

## 3. Bitácora de Ejecución y Registro de Avances

Esta bitácora es el registro oficial de trabajo para los desarrolladores y agentes de IA.

| Fecha | Fase / Tarea | Archivos Implicados | Descripción del Trabajo | Estado |
|---|---|---|---|---|
| **2026-10-18** | **Fase 0: Auditoría de BD** | `Database/clikhub.sqlite`, `inspect_db.php` | Inspección de todas las tablas activas, conteo de filas, detección de tablas huérfanas y validación de archivos vacíos. | [✅ Completado] |
| **2026-10-18** | **Fase 1: Migración Segura en SQLiteSetup** | [`SQLiteSetup.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/DatabaseComponent/SQLiteSetup.php) | Se incorporó la migración automática e idempotente: DDL actualizado, creación de columna `services` en `user_subscriptions_cache`, `visitor_user_id` en `profile_views` e índices de alto rendimiento (`idx_views_visitor`, `idx_views_geo`, `idx_views_referrer`, `idx_views_created`, `idx_clicks_user_link`, `idx_clicks_created`). Se ejecutó y verificó en BD real. | [✅ Completado] |
| **2026-10-18** | **Fase 2: Asignación Free en Registro & SubscriptionModels** | [`SubscriptionModels.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Models/SubscriptionModels.php), [`LoginControllers.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Controllers/LoginControllers.php), [`LemonSqueezyModels.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Models/LemonSqueezyModels.php) | Se creó el modelo `SubscriptionModels` con constantes (`1`, `2`, `3`, `4`) y métodos `initializeFreeSubscription()`, `getServices()`, `hasService()`, `isPremium()`, `addService()`, `removeService()`. Se conectó el registro en `LoginControllers` para inicializar el plan Free `1`, y `LemonSqueezyModels` para sincronizar `services = '1,2'` en altas y `'1'` en cancelaciones. Pruebas unitarias de flujo completo ejecutadas con éxito. | [✅ Completado] |
| **2026-10-18** | **Fase 3: Captura de Visitas Registradas e Incógnito** | [`VisitModels.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Models/VisitModels.php) | Se actualizó `processVisit()` para capturar `visitor_user_id` en `profile_views` cuando el visitante tiene sesión iniciada. Se blindó la auto-visita (se rechaza si el dueño visita su propio perfil), se captura el `index_user` de visitantes autenticados, y se respeta el Modo Incógnito (Servicio 4 reservado) guardando `visitor_user_id` como `NULL`. Batería de pruebas automatizadas ejecutadas con 100% de éxito. | [✅ Completado] |
| **2026-10-18** | **Fase 4: Consultas de Estadísticas Avanzadas** | [`StatisticsModels.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Models/StatisticsModels.php) | Se implementó la suite completa de analíticas avanzadas: `getRecentVisitors()` (feed de visitantes autenticados con avatar, nombre, tiempo relativo y visitas acumuladas), `getGeoRanking()` (ranking de países con flags/porcentajes y top ciudades), `getReferrersSummary()` (clasificación inteligente en plataformas: Instagram, TikTok, WhatsApp, X, Facebook, YouTube, Buscadores, Directo y Otros), `getHeatmapData()` (matriz 7x24, día y hora pico con recomendación inteligente de publicación) y `getLinksDetailedStats()` (rendimiento por botón/enlace y redes sociales, clics mes y acumulados, móvil vs desktop, CTR y badge de Enlace Estrella). Batería de 6 pruebas unitarias ejecutadas al 100%. | [✅ Completado] |
| **2026-10-18** | **Fase 5: Interfaz de Submenús en Dashboard** | `sideMenu.php`, `sideMenuPhone.php`, `StatisticsPanel/` | Convertir estadísticas en menú desplegable con submenús General y Enlaces. | [✅ Completado] |
| **2026-10-18** | **Estandarización Gráficos Nativos (`GraphicsModule`)** | `general.php`, `links.php`, `card-graphic.css`, `StatisticsModels.php` | Sustitución total de gráficos y gradientes por `GraphicsModule::horizontalBars` y `GraphicsModule::matrixHeatmap` (`color => 'texto'`). Barras de dispositivos planas bicolores (`back-success` y `back8`) idénticas a `cto.php`. Ejecución de `composer min-script`. | [✅ Completado] |
| **2026-10-18** | **Ajustes de Parámetros y Ejes (`yLabelPosition`, `axisLabelSize`, `barWidth`)** | `GraphicsModule.php`, `horizontalBars.js` | Corrección a `yLabelPosition` (eje vertical para barras horizontales), soporte de medidas exactas en píxeles para `barWidth` y `axisLabelSize`, y eliminación de fondo en `matrixHeatmap`. | [✅ Completado] |
| **2026-10-18** | **Normalización Responsiva de Escala 1.0 en `horizontalBars`** | `GraphicsModule.php`, `horizontalBars.js`, `svgChartAnimator.js`, `general.php` | Solución matemática al escalado de `viewBox`: `viewBox` coordinado con ancho del contenedor para fijar escala = 1.0 permanente. Las barras miden exactamente 20px de grosor y los textos 16px exactos en cualquier contenedor (650px, 550px o móvil). Atributos `data-*` y `ResizeObserver` dinámico. Framework actualizado a commit `04249cc` y minificado con `composer min-script`. | [✅ Completado] |
| **Mañana** | **Fase 6: Rediseño Híbrido Responsivo de `horizontalBars` (HTML + CSS Nativo)** | `GraphicsModule.php`, `charts.css`, `card-graphic.css`, `general.php` | Rediseño estructural de `horizontalBars` hacia arquitectura híbrida HTML/CSS Flexbox. Garantiza que `barWidth: 20px` y `axisLabelSize: 16px` sean invariables y pixel-perfect en cualquier pantalla (incluyendo móviles de 348px) sin depender de matrices de transformación ni `ResizeObserver`. | [🟡 Pendiente para Mañana] |
| **Pendiente** | **Fase 7: Página de Pricing y Checkout** | `LemonSqueezyControllers.php`, vistas de planes | Espacios reservados para Free ("Plan actual") y Premium (checkout Lemon Squeezy). | [ ] Pendiente |


---

## 4. Cambios Aplicados en Base de Datos ([`SQLiteSetup.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/DatabaseComponent/SQLiteSetup.php))

Se modificó [`SQLiteSetup.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/DatabaseComponent/SQLiteSetup.php) incorporando:

1. **Definiciones DDL Actualizadas**:
   * `user_subscriptions_cache`: incluye `services TEXT DEFAULT '1'`.
   * `profile_views`: incluye `visitor_user_id VARCHAR(64) NULL`.

2. **Rutina de Migración Segura en Producción**:
   * Inspección con `PRAGMA table_info` antes de aplicar cualquier `ALTER TABLE`.
   * Sincronización automática de registros existentes: los usuarios con `is_premium = 1` se actualizaron a `services = '1,2'` y los Free a `services = '1'`.
   * Creación de índices compuestos para optimizar las consultas analíticas pesadas.

---

## 5. Arquitectura de Submenús en el Panel de Estadísticas

En [`sideMenu.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Views/Dashboard/SideMenu/sideMenu.php) y en la barra móvil [`sideMenuPhone.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Views/Dashboard/SideMenu/sideMenuPhone.php), la opción de estadísticas se transformará en un **grupo colapsable con 2 submenús**:

```
[📊 Estadísticas] ▾
  ├── 📈 General (Métricas del Perfil / Sitio)
  └── 🔗 Enlaces (Desglose individual por Botón)
```

### 5.1 Submenú 1: Estadísticas Generales (`#stats-general-remote`)
* **Vista Free**:
  * Visitas totales del mes y comparativa con el promedio anterior.
  * Teasers con desenfoque (blur) y candado:
    * *"X personas visitaron tu perfil hoy. Desbloquea con Premium para ver quiénes fueron"*.
    * País #1 visible, demás países cubiertos con candado.
    * Fuentes de tráfico ocultas.
* **Vista Premium**:
  * **Feed «¿Quién te visitó?»**: Lista cronológica con avatar, username, fecha relativa y enlace al perfil.
  * **Geolocalización Completa**: Ranking Top 10 países con banderas SVG y porcentaje, más lista de ciudades principales vía MaxMind.
  * **Fuentes de Tráfico (Referrers)**: Instagram, TikTok, WhatsApp, Directo, etc.
  * **Heatmap Horario 7x24**: Gráfica de días y horas con mayores visitas.

---

### 5.2 Submenú 2: Estadísticas por Enlace (`#stats-links-remote`)
* **Vista Free**:
  * Clics globales del mes (suma total).
  * Ratio CTO global (Visitas vs Clics).
  * Lista de botones individuales bloqueada con invitación a Premium.
* **Vista Premium**:
  * **Tabla detallada de cada enlace**: Título del botón, URL, clics del mes, clics acumulados y CTR individual.
  * **Badge «Enlace Estrella»**: Resalta el botón con mejor tasa de conversión.
  * **Desglose de Redes Sociales (RRSS)**: Clics específicos por cada red configurada.
  * **Dispositivos**: Porcentaje de clics provenientes de móvil vs escritorio.

---

## 6. Página de Precios (`/planes` / `/suscripcion`)

En la página de planes gestionada por [`LemonSqueezyControllers.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Controllers/LemonSqueezyControllers.php):
* **Tarjeta Free**: Para usuarios logueados muestra el indicador de **«Tu plan actual» / «Plan activo»**.
* **Tarjeta Premium**: Desglosa las características y servicios analíticos que se desbloquean, con el espacio reservado para conectar el checkout final de Lemon Squeezy.

---

## 7. Resultados de la Fase 4 y Próximos Pasos (Fase 5)

### 7.1 Métodos Implementados en [`StatisticsModels.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Models/StatisticsModels.php)
1. **`getRecentVisitors(string $indexUser, int $limit = 10)`**: Feed de visitantes autenticados (`visitor_user_id IS NOT NULL`), agrupado por usuario para extraer la visita más reciente, avatar normalizado, URL de perfil y tiempo relativo con `DateTimeModule::timeAgo()`. Excluye auto-visitas.
2. **`getGeoRanking(string $indexUser, int $limit = 10)`**: Ranking de países con porcentajes relativos, banderas en minúsculas y ranking de principales ciudades detectadas por MaxMind.
3. **`getReferrersSummary(string $indexUser)`**: Clasificación algorítmica de fuentes de tráfico en categorías estándar: Instagram, TikTok, WhatsApp, X (Twitter), Facebook, YouTube, Buscadores, Tráfico Directo y Otras fuentes, con iconos atómicos SVG y porcentajes.
4. **`getHeatmapData(string $indexUser)`**: Matriz semanal 7x24 de audiencia, cálculo de día pico, hora pico formateada y generación de texto inteligente de recomendación para publicación de contenido.
5. **`getLinksDetailedStats(string $indexUser)`**: Métricas individuales por botón y redes sociales (clics del mes, clics acumulados, CTR individual, proporción móvil vs escritorio y distinción del "Enlace Estrella").
6. **`getStatsData(string $userOrIndex)`**: Integración unificada de las nuevas estructuras (`visitors`, `geo`, `referrers`, `heatmap`, `links_detailed`) preservando retrocompatibilidad total con las vistas existentes.

### 7.2 Próximos Pasos (Fase 5: Interfaz de Submenús en Dashboard)
1. En [`sideMenu.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Views/Dashboard/SideMenu/sideMenu.php) y [`sideMenuPhone.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Views/Dashboard/SideMenu/sideMenuPhone.php), transformar el botón de estadísticas en un desplegable/acordeón con los dos submenús:
   * **General** (`#stats-general-remote`)
   * **Enlaces** (`#stats-links-remote`)
2. Crear/estructurar las vistas en `App/Views/Dashboard/StatisticsPanel/`:
   * `General/general.php`: métricas globales, feed «¿Quién te visitó?», geolocalización, referrers y heatmap 7x24, con candados y desenfoque (blur) para usuarios del plan Free.
   * `Links/links.php`: tabla de enlaces con badge de "Enlace Estrella", CTR individual y desglose de RRSS.
3. Actualizar [`StatisticsControllers.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Controllers/StatisticsControllers.php) y el contenedor de navegación del panel.
4. Ejecutar `composer min-script` tras cualquier cambio de CSS/JS.
---

## 8. Bitácora de Estandarización a Builder del Framework (Completada previa a Fase 5)

### 8.1 Auditoría y Mejoras en el Framework (`C:\Users\eber\Proyectos\frame`)
1. **Resolución dinámica de tablas**:
   * En `Base/Builder/Builder.php`, se reemplazó el nombre hardcodeado `'ratelimits'`/`'RateLimits'` por el método protegido `resolveRateLimitsTable()`, verificando `RATELIMITS_TABLE` o introspección de esquemas sin fallar en motores SQLite/MySQL/PostgreSQL.
2. **Nuevos métodos fluidos y consultas raw seguras**:
   * `orderBy($col, $order = 'ASC'): self`: alias estándar de `order()`.
   * `groupBy($group): self`: alias estándar de `group()`.
   * `rawQuery(string $sql, array $data = []): array`: ejecución preparada y parametrizada retornando array asociativo.
   * `rawQueryOne(string $sql, array $data = []): array|false`: retorno del primer registro.
   * `updateWhere(array $datas, $encoder = null): self`: actualización segura exigiendo cláusulas `where()` previas.
3. **Corrección de resolución de argumentos en `orWhere()` y `where()`**:
   * Se identificó y resolvió un fallo donde invocar `orWhere($col, $val)` con 2 argumentos pasaba 4 parámetros a `where()` con `$val = null`, causando que el valor fuera interpretado como operador SQL. Se mejoró la introspección de argumentos en `where()` y `orWhere()` en `frame`.
4. **Commits en repositorio del framework y sincronización**:
   * Commit 1: `feat(builder): agregar alias orderBy, groupBy, rawQuery, updateWhere y resolver tabla ratelimits dinamicamente` (`941efec`).
   * Commit 2: `fix(builder): corregir resolucion de argumentos en orWhere y clausulas where con valor nulo` (`72ad124`).
   * Actualizado en `Cuaderno` vía `composer update eber/framework`.

### 8.2 Refactorización de Modelos en [`Cuaderno`](file:///c:/Users/eber/Proyectos/Cuaderno/)
1. **[`StatisticsModels.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Models/StatisticsModels.php)**:
   * Los 11 métodos que utilizaban `AnalyticsModule::getPdo()` y sentencias preparadas crudas (`getViewsTimeline`, `getClicksTimeline`, `getClicksByLink`, `getDeviceBreakdown`, `getCountryBreakdown`, `getReferrerBreakdown`, `getRecentVisitors`, `getGeoRanking`, `getReferrersSummary`, `getHeatmapData`, `getLinksDetailedStats`) fueron completamente migrados a `rawQuery` y métodos fluidos de `BuilderSqlite`.
   * Eliminadas todas las dependencias e importaciones de `AnalyticsModule` y `PDO`.
2. **[`VisitModels.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Models/VisitModels.php)**:
   * Migradas las inserciones de `processVisit` y `processClick` a `(new self("profile_views"))->create(...)` y `(new self("link_clicks"))->create(...)`.
   * Creados los métodos propios `hasRecentProfileView()` y `hasRecentLinkClick()` utilizando `->where(...)->where("created_at", ">=", $cutoff)->exists()`.
   * Eliminadas las dependencias e importaciones de `AnalyticsModule` y `PDO`.
3. **[`LemonSqueezyModels.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Models/LemonSqueezyModels.php)**:
   * Eliminada la conexión redundante `private static ?PDO $sqlitePdo` y el método `getSqlitePdo()`.
   * Migradas `updateUserPremiumCache` e `isUserSubscribedFast` a utilizar `SubscriptionModels` mediante `rawQuery()` y `findSubscriptionRow()`.
   * Eliminada la importación de `PDO`.

### 8.3 Verificación y Estado
* Escaneo total en `App/Models`, `App/Components` y `App/Middleware`: **0 referencias a `PDO` o sentencias preparadas crudas**.
* Pruebas de integración automatizadas ejecutadas: **100% OK**.
* Compilación de recursos: `composer min-script` ejecutado con éxito.
* **El proyecto está 100% estandarizado y listo para avanzar con la Fase 5.**

---

## 9. Bitácora de la Fase 5: Interfaz de Submenús y Vistas de Estadísticas (Completada)

### 9.1 Navegación y Submenús en el Dashboard
1. **Barra Lateral de Escritorio ([`sideMenu.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Views/Dashboard/SideMenu/sideMenu.php))**:
   * Se transformó el botón simple de estadísticas en un bloque colapsable acordeón (`vertical-menu-item`) estandarizado con el resto del panel.
   * Contiene los sub-enlaces remotos:
     * **General**: `data-remote="stats-general-remote"`
     * **Enlaces**: `data-remote="stats-links-remote"`
2. **Barra Inferior Móvil ([`sideMenuPhone.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Views/Dashboard/SideMenu/sideMenuPhone.php))**:
   * Botón 3 del menú raíz configurado con `data-bottom-target="submenu-stats"` y `data-root-group="stats"`.
   * Se creó el track `#submenu-stats` con botón de retorno al menú principal (`angle-l`) y los botones remotos `stats-general-remote` y `stats-links-remote`.
3. **Controladores JavaScript ([`bottomNavPhone.js`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Public/Js/bottomNavPhone.js) y [`saveButtonController.js`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Public/Js/saveButtonController.js))**:
   * Sincronización del estado activo en el botón de estadísticas raíz para cualquiera de los dos submenús activos.
   * La función `loadStatisticsIfNeeded()` se configuró para cargar bajo demanda ambos submenús en una sola petición AJAX (`/panel/{user}/estadisticas`), inyectando el contenido en `#stats-general-remote-wrapper` y `#stats-links-remote-wrapper`.

### 9.2 Vistas Modulares Creadas en `App/Views/Dashboard/StatisticsPanel/`
1. **[`General/general.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Views/Dashboard/StatisticsPanel/General/general.php)**:
   * **Visitas del Mes**: Métrica actual, tendencia comparativa frente a meses anteriores y gráfico vectorial Hybrid Spline.
   * **Feed «¿Quién te visitó?»**: En Premium, lista cronológica con avatares, perfiles, conteo y tiempo relativo (`DateTimeModule::timeAgo`); en Free, teaser difuminado (`premium-blur-content`) con candado SVG, conteo real de visitantes y botón para desbloquear con Premium.
   * **Geolocalización**: En Premium, ranking Top 10 países con banderas emoji y lista de ciudades MaxMind; en Free, país #1 desbloqueado y puestos 2+ cubiertos con candado.
   * **Fuentes de Tráfico (Referrers)**: Desglose por plataformas (Instagram, TikTok, WhatsApp, X, etc.) y porcentaje con barra de progreso. Bloqueado con candado para Free.
   * **Actividad Semanal 7x24 (Heatmap)**: Matriz interactiva de 7 días x 24 horas por nivel de intensidad y caja de recomendación inteligente de mejor momento para publicar. Bloqueado con candado para Free.
2. **[`Links/links.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Views/Dashboard/StatisticsPanel/Links/links.php)**:
   * **Métricas Globales de CTO**: Gráfico semicircular Arc Meter, promedio de clics por visita y proporción Enlaces de Contenido vs Redes Sociales.
   * **Destacado «Enlace Estrella»**: Identifica el botón con mayor tracción mediante el badge `star-link-badge` y trofeo, detallando clics del mes, históricos, CTR y preferencia móvil/desktop.
   * **Rendimiento Individual por Botón**: Tarjetas de cada botón configurado con métricas desglosadas (bloqueado para Free con teaser y candado).
   * **Redes Sociales (RRSS)**: Clics específicos por cada red social activa.
   * **Dispositivos de Conversión**: Barra de distribución de clics entre teléfonos móviles y ordenadores de escritorio.
3. **[`statisticsPanel.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Views/Dashboard/StatisticsPanel/statisticsPanel.php)**:
   * Refactorizado como enrutador/delegador directo a `General.general` preservando compatibilidad absoluta.

### 9.3 Estilos y Recursos
* Estilos atómicos agregados en [`card-graphic.css`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Public/Css/card-graphic.css) para `.premium-blur-container`, `.premium-blur-content`, `.premium-lock-overlay`, `.premium-lock-badge`, `.btn-premium-unlock`, `.star-link-badge`, `.progress-bar-bg`, `.progress-bar-fill-accent` y celdas del heatmap `.heatmap-cell`.
* Icono `lock.svg` incorporado en el repositorio del framework (`frame`), en `vendor` y en `App/Rsc/Ico/`.
* Compilación ejecutada con éxito mediante `composer min-script`.


### 9.4 Estandarización de Gráficos Nativos con `GraphicsModule`
1. **Regla de Oro**: Ningún gráfico o barra utiliza gradientes CSS inventados (`linear-gradient`) ni estructuras HTML ajenas. Se adoptó el motor nativo monocromático [`GraphicsModule.php`](file:///c:/Users/eber/Proyectos/Cuaderno/vendor/eber/framework/Base/Module/GraphicsModule.php).
2. **Geolocalización de Audiencia (Países)**:
   * Migrado a `GraphicsModule::horizontalBars()` con `color => 'texto'` y `valueSuffix => '%'`.
   * En Free, muestra el país #1 con `horizontalBars` y detrás del desenfoque (blur) el ranking demo con `horizontalBars`.
3. **Fuentes de Tráfico (Referrers)**:
   * Migrado a `GraphicsModule::horizontalBars()` con `color => 'texto'` y `valueSuffix => '%'`.
   * En Free, teaser desenfocado con `horizontalBars` nativo.
4. **Actividad Semanal (Heatmap)**:
   * Las 24 horas del día se agregaron en 5 franjas horarias canónicas (`00-06h`, `06-12h`, `12-15h`, `15-19h`, `19-24h`) cruzadas con los 7 días de la semana (`Lun` a `Dom`).
   * Renderizado con `GraphicsModule::matrixHeatmap()` con `color => 'texto'`, calculando intensidades de 0.0 a 1.0 para derivación tonal automática vía OKLCH.
5. **Proporción de Dispositivos (Móvil vs Escritorio)**:
   * Barras de distribución en [`links.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Views/Dashboard/StatisticsPanel/Links/links.php) estandarizadas exactamente al patrón oficial de [`cto.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Views/Dashboard/StatisticsPanel/Cto/cto.php): contenedor `hpx12 br10 overflow-hidden` con segmentos planos `back-success` y `back8`.
   * Para tarjetas individuales de enlace: barra plana proporcional `hpx8 br10 overflow-hidden`.
6. **Limpieza y Assets**:
   * Eliminadas clases obsoletas `.progress-bar-fill-accent`, `.heatmap-scroll-wrapper` y `.heatmap-cell` de `card-graphic.css`.
   * Corregidos índices y alias (`name`, `key`, `network`, `mobile_percentage`, `desktop_percentage`) en `StatisticsModels::getLinksDetailedStats()`.
   * Compilado JIT y minificación ejecutados exitosamente con `composer min-script`.

### 9.5 Corrección y Pulido de Gráficos Nativos en Framework (`frame` y `Cuaderno`)
1. **Fondo de Matrix Heatmap**:
   * **Causa raíz**: El fondo oscuro `#111114` no estaba en una clase CSS, sino inyectado como atributo en línea directamente en el generador PHP de [`GraphicsModule.php`](file:///c:/Users/eber/Proyectos/Cuaderno/vendor/eber/framework/Base/Module/GraphicsModule.php): `<rect ... class="mono-matrix-panel" fill="#111114" ... />`.
   * **Solución**: Se eliminó el fondo fijo; el panel `<rect class="mono-matrix-panel">` ahora es transparente y opcional (solo si se pasa `showPanel => true`), permitiendo que el gráfico herede limpiamente el fondo de su tarjeta o contenedor tanto en modo claro como en modo oscuro.
   * En CSS ([`charts.css`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Rsc/Library/Charts/charts.css), [`minimal-charts.css`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Public/Css/minimal-charts.css) y [`card-graphic.css`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Public/Css/card-graphic.css)), se fijó `.mono-chart-svg .mono-matrix-panel { display: none !important; fill: transparent !important; }`.
2. **Ajuste Responsivo y Prevención de Recorte en Horizontal Bars**:
   * **Causa raíz**: El `viewBox` estaba fijado en `0 0 320 180` y el inicio del gráfico `$plotX1` en `64.0`, otorgando apenas ~56px para etiquetas con `text-anchor="end"`. Etiquetas largas como *"Buscadores (Google/Bing)"* (24 caracteres) se posicionaban en `x < 0`, saliendo del viewport del SVG y recortándose a la izquierda.
   * **Solución**: En `GraphicsModule::horizontalBars()`, se implementó cálculo dinámico del ancho del eje Y (`$calcLabelW = max(56.0, ceil($maxLabelChars * 6.6) + 12.0)`) y cálculo dinámico del ancho total del `viewBox` (`$viewBoxW = $plotX1 + $barAreaWidth + 16.0`), añadiendo `width="100%" height="auto"` al SVG y la regla CSS `.mono-chart-svg.mono-hbar-svg { display: block; width: 100%; max-width: 100%; height: auto; }`. Con esto el gráfico se adapta fluidamente al ancho del contenedor sin desbordar ni recortar textos.
3. **Flujo de Trabajo del Framework**:
   * Cambios implementados y verificados en `C:\Users\eber\Proyectos\frame`.
   * Commit realizado en `frame`: `fix(charts): quitar fondo oscuro a matrixHeatmap y autoajustar viewBox en horizontalBars`.
   * Actualización en `Cuaderno` mediante `composer update eber/framework`.
   * Minificación y compilación JIT ejecutadas con éxito vía `composer min-script`.

### 9.6 Motor Avanzado de Posicionamiento de Etiquetas (`yLabelPosition`), Grosor de Barras (`barWidth`) y Tamaño de Fuente (`axisLabelSize`)
1. **Configuración Global y Local en [`GraphicsModule.php`](file:///c:/Users/eber/Proyectos/Cuaderno/vendor/eber/framework/Base/Module/GraphicsModule.php)**:
   * Los parámetros pueden definirse de forma global para toda la aplicación mediante `GraphicsModule::configStyle(['barWidth' => 16, 'axisLabelSize' => 16, 'yLabelPosition' => '[between],[40, 60, start]'])` o individualmente por gráfico mediante `$params['barWidth']`, `$params['axisLabelSize']` y `$params['yLabelPosition']`.
   * Para asegurar máxima resiliencia, se mantiene soporte completo hacia atrás como alias si se provee `xLabelPosition`.
   * El método `GraphicsModule::resetConfig()` restaura la configuración por defecto (`barWidth => null`, `axisLabelSize => null`, `yLabelPosition => 'default'`).
2. **Sintaxis y Comportamiento de `yLabelPosition`**:
   * Sintaxis soportada: `"[rowAlign],[labelPct, chartPct, labelAlign]"` (ej. `"[between],[40, 60, start]"`, `"[default],[50, 50, center]"`) o formato array asociativo `['row' => ..., 'labelPct' => ..., 'chartPct' => ..., 'align' => ...]`.
   * **Alineación de fila / contenedor (`rowAlign`)**: `default`, `between`, `around`, `start`, `center`, `end`.
   * **Modo `default`**:
     * Si la longitud natural de las etiquetas es inferior al 50% del contenedor neto, ocupa 1 línea.
     * Si la etiqueta más larga excede el 50% del contenedor neto, la columna de texto se ajusta automáticamente a 2 líneas (`<tspan>`), calculando los caracteres máximos por línea de forma responsiva para evitar cualquier solapamiento o desbordamiento.
   * **Modos Flex (`between`, `around`, `start`, `center`, `end`)**:
     * El porcentaje `labelPct, chartPct` (ej. `50, 50`, `40, 60`, `10, 90`) reparte el ancho neto del contenedor entre las etiquetas y el área de trazado, garantizando que el gráfico siempre ocupe el 100% del contenedor.
   * **Alineación de Etiquetas (`labelAlign`)**:
     * `start`: `text-anchor="start"` al inicio de la columna de etiquetas.
     * `center`: `text-anchor="middle"` en el centro de la columna de etiquetas.
     * `end`: `text-anchor="end"` al final de la columna de etiquetas.
     * Se inyecta tanto el atributo SVG `text-anchor` como el estilo en línea `style="text-anchor: ...;"` para garantizar precedencia sobre clases CSS.
3. **Control Unificado de Grosor (`barWidth`)**:
   * Permite estandarizar el grosor de barras en toda la suite:
     * Columnas verticales (`simpleBars`, `stackedTones`, `hybridSpline`): ancho de barra X.
     * Columnas dobles (`pillPillars`): grosor de pilares gemelos (`pillarWidth`).
     * Barras horizontales (`horizontalBars`): grosor/altura en Y de las barras cápsula (`barHeight`).
     * Barras de benchmark (`bulletTarget`): grosor de cápsula en Y (`barHeight`).
     * Arcos y roscas circulares (`arcMeter`, `roundedDonut`): grosor de trazo radial (`strokeWidth`).
     * Pirámides jerárquicas (`pyramidStack`): altura de cápsulas por nivel (`barHeight`).
   * **Exclusión explícita por diseño**: `splineDynamic`, `matrixHeatmap` y `tileTreemap` NO utilizan `barWidth` para salvaguardar su estética e identidad gráfica.
4. **Control Unificado de Tamaño de Fuente de Ejes (`axisLabelSize`)**:
   * Permite fijar el tamaño de fuente de las etiquetas de ejes numéricos y descriptivos global o localmente: `axisLabelSize => 16` (se normaliza a `16px`), o con unidades CSS (`'14px'`, `'1rem'`).
   * Se propaga a través de variables CSS `--chart-axis-size: ...;` y estilos en línea directos (`style="font-size: 16px;"`) en los elementos `<text>` de todos los gráficos con ejes (`horizontalBars`, `simpleBars`, `stackedTones`, `hybridSpline`, `pillPillars`, `bulletTarget`, `matrixHeatmap`).
   * En `horizontalBars`, adapta dinámicamente las métricas de cálculo de ancho de columna y salto de 2 líneas (`charWidthFactor = max(4.5, $axisSizeNum * 0.66)`), evitando solapamientos o desbordes cuando la fuente aumenta.
5. **Flujo de Trabajo del Framework**:
   * Pruebas de integración automatizadas ejecutadas y superadas con 100% de éxito en `c:\Users\eber\Proyectos\frame`.
   * Commits en `frame`:
     * `feat(graphics): agregar soporte global y local para xLabelPosition y barWidth` (`173c72e`).
     * `fix(graphics): renombrar xLabelPosition a yLabelPosition manteniendo compatibilidad hacia atras` (`1ebc98a`).
     * `feat(graphics): agregar soporte global y local para axisLabelSize` (`e87e280`).
      * `fix(graphics): asegurar compatibilidad de claves val y soporte asociativo en simpleBars y horizontalBars` (`49cf924`).
   * Dependencia sincronizada en `Cuaderno` vía `composer update eber/framework`.
   * Hojas de estilo sincronizadas en `App/Rsc/Library/Charts/charts.css`.
   * Minificación y JIT ejecutados limpiamente vía `composer min-script`.

### 9.7 Estandarización de Medidas Específicas en Píxeles (ViewBox 320x180, Fuentes y Anchos de Barra Reales)
1. **Diagnóstico del Problema**:
   * Al configurar `axisLabelSize => '16'` y `barWidth => '20'`, se apreciaban variaciones visuales notorias entre diferentes tipos de gráficos montados en cuadrículas idénticas (como en `test2.php` y el panel general).
   * **Causa Raíz #1 (`viewBox` Dispar)**: `horizontalBars` utilizaba `viewBox="0 0 460 180"`, mientras que la casi totalidad de la suite (`simpleBars`, `hybridSpline`, `pillPillars`, `bulletTarget`, `pyramidStack`, `splineDynamics`, `matrixHeatmap`) usaba `viewBox="0 0 320 180"`. Al insertarse en contenedores del mismo ancho, el SVG de 460 se escalaba un 30-44% más pequeño, reduciendo visualmente los textos y barras a la mitad.
   * **Causa Raíz #2 (Alineación de Etiquetas del Eje X)**: En `simpleBars`, `hybridSpline`, `pillPillars` y `stackedTones`, las etiquetas inferiores del eje X utilizaban `$style['yLabelPosition']['align']` (que por defecto es `'start'`), provocando que los textos bajo cada columna se desplazaran hacia la derecha y se descuadraran respecto a sus barras.
   * **Causa Raíz #3 (Estilos CSS Rígidos)**: Clases como `.mono-bullet-label`, `.mono-bullet-val`, `.mono-spline-dyn-axis-text`, `.mono-spline-dyn-x-label`, `.mono-matrix-row-label` y `.mono-donut-legend-text` tenían tamaños fijos (`11px`, `10.5px`) en `charts.css` y `minimal-charts.css`, impidiendo que `axisLabelSize` tomara el control en píxeles reales.
2. **Solución Implementada**:
   * **Normalización de `viewBox`**: Se estandarizó `horizontalBars` a `viewBox="0 0 320 180"`, unificando la escala de coordenadas virtuales con `simpleBars`, `hybridSpline`, `bulletTarget`, etc. Las medidas `barWidth: 20` y `axisLabelSize: 16` ahora corresponden a idénticos píxeles visuales en pantalla en toda la suite.
   * **Centrado de Columnas en Eje X**: En las columnas verticales (`simpleBars`, `hybridSpline`, `pillPillars`, `stackedTones`), las etiquetas del eje X siempre aplican `text-anchor="middle"` estricto. `yLabelPosition` gobierna las filas horizontales y descriptivas.
   * **Desacople en CSS**: En `charts.css` y `minimal-charts.css` se sustituyeron los valores rígidos por `var(--chart-axis-size, ...)`.
   * **Inyección de Atributos SVG**: Se inyectaron `$axisSizeStyle` y `$axisSizeAttr` (`font-size="16px" style="font-size: 16px;"`) en todos los elementos `<text>` de la suite (`splineDynamics`, `roundedDonut`, `bulletTarget`, `pyramidStack`, `matrixHeatmap`, `horizontalBars`).
3. **Flujo de Trabajo del Framework**:
   * Commit en `frame`: `fix(charts): estandarizar viewBox a 320x180, centrar etiquetas X y desacoplar tamano de fuente de CSS rigido` (`e4a6a9f`).
   * Sincronizado en `Cuaderno` vía `composer update eber/framework`.
   * Hojas de estilo sincronizadas en `App/Rsc/Library/Charts/charts.css` y `App/Public/Css/minimal-charts.css`.
   * Minificación y JIT ejecutados exitosamente con `composer min-script`.

### 9.8 Normalización de Escala 1.0 en horizontalBars (Commit `04249cc`)
* Se coordinó el ancho del `viewBox` con el ancho de renderizado en desktop (650px y 550px) para fijar el factor de escala en 1.0000, inyectando atributos `data-*` y un `ResizeObserver`.
* **Identificación del Reto en Dispositivos Móviles**: En móviles (donde el contenedor se reduce a ~348px), cualquier sistema SVG cerrado con `viewBox` tiende a comprimir uniformemente tanto el ancho como la altura de las barras y el tamaño tipográfico, a menos que se abandone la matriz de escala de SVG en favor de un enfoque desacoplado.

---

## 10. Fase 6: Rediseño Híbrido Responsivo de Gráficos (`horizontalBars` Nativo con HTML + CSS Flexbox/Grid) — [✅ Completado]

### 10.1 Objetivo y Fundamento Arquitectónico
Garantizar que las barras horizontales (`horizontalBars`) respeten **de forma matemática y estricta las medidas en píxeles configuradas** (`barWidth => 20`, `axisLabelSize => 16`), sin importar si el usuario visualiza el dashboard en un monitor de 1920px (contenedor de 650px), en una laptop (550px), en una tablet o en un teléfono móvil (contenedor responsivo de 348px).

### 10.2 Arquitectura Híbrida Implementada (HTML + CSS Grid / Flexbox)
1. **Tipografía Invariable (Eje Y y Eje X)**:
   * Las etiquetas descriptivas del eje Y se renderizan en elementos nativos HTML (`<div class="mono-svg-axis-text mono-svg-axis-text-y mono-hbar-label ..." style="font-size: 16px;">`) y las marcas del eje X en `<span class="mono-svg-axis-text mono-svg-axis-text-x mono-hbar-tick-x ..." style="font-size: 16px;">`.
   * El tamaño tipográfico es **100% invariable en píxeles reales** (`16px`) en cualquier tamaño de contenedor o resolución móvil, eliminando por completo la deformación de `viewBox`.
   * Cuando la etiqueta es muy larga en móvil (superando el 50% del contenedor), `.mono-hbar-label-text` aplica automáticamente salto de línea hasta un máximo de 2 líneas (`-webkit-line-clamp: 2`).
2. **Grosor de Barras Invariable (`barWidth` / `barHeight`)**:
   * El área de trazado `.mono-hbar-plot`, la pista `.mono-hbar-track` y la barra activa `.mono-hbar-rect` aplican `height: {$barHeight}px; border-radius: {$ry}px;` en píxeles CSS puros (`height: 20.00px;`).
   * El largo de cada barra se expresa en porcentaje fluido (`width: {$pct}%;`, `data-target-w="{$pct}"`), expandiéndose o contrayéndose de forma instantánea según el espacio horizontal disponible sin alterar jamás los `20px` de grosor vertical.
3. **Tres Modos de Distribución (`yLabelPosition` y `showAxisY`)**:
   * **Modo `default` (`mono-hbar-mode-default`)**: Utiliza CSS Grid (`grid-template-columns: fit-content(calc(50% - 6px)) minmax(0, 1fr);`) con `display: contents` en las filas, de modo que la columna de etiquetas se auto-ajusta a la etiqueta más larga sin superar el 50% del contenedor y todas las barras y el eje X comparten exactamente la misma línea base vertical.
   * **Modo personalizado (`mono-hbar-mode-custom`)**: Cuando `yLabelPosition` define alineación Flex (`between`, `around`, `start`, `center`, `end`) o porcentajes explícitos (ej. `[between],[40, 60, end]`), emplea Flexbox por fila con `justify-content` y anchos exactos `calc({$labelPct}% - 6px)` y `calc({$chartPct}% - 6px)`.
   * **Modo sin Eje Y (`mono-hbar-no-axis-y`)**: Cuando `showAxisY => false`, las barras ocupan el 100% del contenedor (`grid-template-columns: minmax(0, 1fr)`).
4. **Animación JS, Tooltips y Cero `ResizeObserver`**:
   * [`horizontalBars.js`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Rsc/Library/Charts/horizontalBars.js) interpola `bar.style.width` desde `0%` hasta `${targetW}%` con curva `easeInOutCubic`.
   * [`tooltip.js`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Rsc/Library/Charts/tooltip.js) detecta `.mono-chart-svg` tanto en `<svg>` como en contenedores híbridos `<div>`, conservando los tooltips interactivos en `.mono-hbar-item`.
   * Se eliminó el `ResizeObserver` de [`svgChartAnimator.js`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Public/Js/svgChartAnimator.js); el navegador recalcula el layout en 0ms por CSS nativo.
5. **Flujo de Trabajo del Framework**:
   * Commit en `frame`: `feat(charts): redisenar horizontalBars con arquitectura hibrida HTML/CSS flexbox para medidas pixel-perfect invariables` (`735891e`).
   * Sincronizado en `Cuaderno` vía `composer update eber/framework`.
   * Recursos sincronizados en `App/Rsc/Library/Charts/`, `App/Public/Css/minimal-charts.css` y `App/Public/Js/svgChartAnimator.js`.
   * Compilación JIT y minificación ejecutadas con éxito mediante `composer min-script`.

---

## 11. Fase 7: Página de Precios y Suscripción (`/planes` / `/suscripcion`) — [Pendiente]

1. Actualizar [`LemonSqueezyControllers.php`](file:///c:/Users/eber/Proyectos/Cuaderno/App/Controllers/LemonSqueezyControllers.php) y las vistas de planes/precios (`/planes` / `/suscripcion`).
2. **Tarjeta Free**: Para usuarios con sesión activa, indicar «Tu plan actual» / «Plan activo».
3. **Tarjeta Premium**: Desglosar todas las ventajas analíticas desbloqueables («¿Quién te visitó?», Heatmap semanal, Ranking de Países y Ciudades, Enlace Estrella, Fuentes de Tráfico) con espacio reservado para el checkout final de Lemon Squeezy.
