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
| **Pendiente** | **Fase 5: Interfaz de Submenús en Dashboard** | `sideMenu.php`, `sideMenuPhone.php`, `StatisticsPanel/` | Convertir estadísticas en menú desplegable con submenús General y Enlaces. | [ ] Pendiente |
| **Pendiente** | **Fase 6: Página de Pricing y Checkout** | `LemonSqueezyControllers.php`, vistas de planes | Espacios reservados para Free ("Plan actual") y Premium (checkout Lemon Squeezy). | [ ] Pendiente |

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

