# Bitácora y Directivas: Sistema de Estadísticas y Métricas (Eber Framework)

Documento oficial de especificación, arquitectura y registro de avance continuo para la implementación del sistema de estadísticas y métricas del creador en el panel de administración (`/panel/:user/estadisticas`).

---

## 1. Directivas de Arquitectura y Reglas del Proyecto

### 1.1 Modelo (`App\Models\StatisticsModels.php`) — SOLO Lógica de Datos
- **Responsabilidad única:** Procesamiento, extracción y suministro de datos puros.
- **Herencia:** Extiende `Base\Builder\BuilderSqlite` (heredero directo de `Base\Builder\Builder`).
- **Construcción de consultas:** Todos los datos se estructuran bajo los métodos fluidos del Builder:
  - `select()`, `where()`, `whereBetween()`, `whereIn()`, `whereNull()`, `groupBy()`, `orderBy()`, `limit()`, `get()`, `get_one()`, etc.
  - Cero consultas SQL crudas o concatenadas manualmente sin pasar por el Builder.
- **Identificador de Seguridad (`index_user`):**
  - **Toda consulta** a las tablas de analíticas (`profile_views`, `link_clicks`, etc.) se referencia estrictamente por el `index_user` del usuario autenticado.
  - Ninguna métrica se consulta por identificadores secuenciales ni cadenas arbitrarias sin resolver su `index_user`, previniendo IDOR y asegurando aislamiento absoluto entre creadores.

### 1.2 Controlador (`App\Controllers\StatisticsControllers.php`) — SOLO Reglas de Negocio
- **Responsabilidad única:** Orquestación HTTP, validación de permisos de sesión, ejecución de reglas de negocio y selección de respuesta.
- **Herencia:** Extiende `Base\Control\Control`.
- **Uso prioritario del Framework:** **Prohibido reinventar código.** Antes de escribir nuevas funciones o utilidades, se deben inspeccionar y utilizar los métodos existentes en los módulos y helpers del framework (`vendor/eber/framework/Base/`):
  - `Base\Module\GraphicsModule`: Motor nativo de gráficos SVG vectoriales monocromáticos y animados (cero librerías externas).
  - `Base\Module\Session`: Gestión de sesión activa y lectura de `Session::session_data("index_user")`.
  - `Base\Module\ResponseModule`: Respuestas JSON y redirecciones seguras.
  - `Base\Module\DateTimeModule`: Cálculos y transformaciones de rangos temporales.
  - `Base\Module\TextModule`: Formateo de números, porcentajes y textos.
  - `App\Models\LemonSqueezyModels`: Verificación ultra-rápida de suscripción activa con `LemonSqueezyModels::isUserSubscribedFast()`.
- **Diferenciación de Acceso:**
  - El controlador determina si el usuario cuenta con plan activo para habilitar la visualización de paneles comunes o paneles premium avanzados.

### 1.3 Vistas (`App/Views/Dashboard/StatisticsPanel/`) — Arquitectura Modular por Sección
- **Plantilla Base:** `App/Views/Dashboard/StatisticsPanel/statisticsPanel.php`.
  - Define la estructura container (`div.container`, `container-xl-mid`) responsiva del panel.
  - Orquesta la inclusión de las partes mediante helpers oficiales:
    - `_part("StatisticsPanel.<Seccion>.<seccion>", $data)`
    - `_if($condicion, "StatisticsPanel.<Seccion>.<seccion>", $data)`
    - `_each("StatisticsPanel.<Seccion>.<item>", $lista, "item")`
- **Subcarpetas por cada sección:** Siguiendo la convención estricta MVC del Eber Framework, cada sección o tarjeta de métrica se implementa en una subcarpeta dedicada con su archivo `.php` homónimo:
  - `Summary/summary.php` (Métricas de cabecera: Vistas Totales, Visitantes Únicos, Clics, CTR).
  - `Timeline/timeline.php` (Gráfico temporal interactivo de evolución de visitas y clics).
  - `Links/links.php` (Rendimiento por enlace individual del perfil).
  - `Devices/devices.php` (Desglose de dispositivos: móvil, escritorio, tablet).
  - `Countries/countries.php` (Desglose geográfico de audiencia por país).
  - `Referrers/referrers.php` (Fuentes de tráfico y canales de referencia).
  - `Premium/premiumInsights.php` (Métricas avanzadas exclusivas para suscriptores o invitación a suscribirse).
- **Seguridad en Salida (Anti-XSS):**
  - Toda salida dinámica en plantillas debe escaparse con `e()` para texto y `eUrl()` para URLs.

---

## 2. Mapa de Fases de Implementación

```
[FASE 0] Preparación BD, index_user y limpieza de huérfanos (COMPLETADA)
   │
[FASE 1] Creación de BITACORA_ESTADISTICAS.md y Especificación (COMPLETADA)
   │
[FASE 2] Modelado de Consultas en StatisticsModels con Builder / BuilderSqlite
   │
[FASE 3] Reglas de Negocio y Orquestación en StatisticsControllers con módulos Base
   │
[FASE 4] Estructuración Modular de Vistas en App/Views/Dashboard/StatisticsPanel/
   │
[FASE 5] Integración de Gráficos SVG Nativos (GraphicsModule) y Paneles Premium
   │
[FASE 6] Pruebas de Integración (run_site_tests.php) y Minificación (min-script)
```

---

## 3. Bitácora de Acciones Realizadas

| Fecha / Hito | Componente / Archivo | Acción Realizada | Estado |
|---|---|---|---|
| 2026-10-04 | Base de Datos (`clikhub.sqlite`) | Adición de columna e índice `index_user` en todas las tablas (`users`, `user_designs`, `profile_views`, `link_clicks`, `user_subscriptions_cache`, `active_sessions`, etc.). Respaldo físico creado en `clikhub.sqlite.bak`. | **Aprobado** |
| 2026-10-04 | `App\DatabaseComponent\DatabaseMaintenance.php` | Creación del sistema integral de auditoría, sincronización y depuración de registros huérfanos. Comando `composer db-maintenance`. | **Aprobado** |
| 2026-10-04 | Limpieza de Huérfanos | Purga de 25 registros huérfanos identificados (1 diseño temporal, 19 vistas obsoletas, 2 clics huérfanos, 1 suscripción inválida, 2 sesiones de prueba). Auditoría posterior arrojó 0 huérfanos. | **Aprobado** |
| 2026-10-04 | `App\Models\UserModels.php` | Métodos `getIndexUserByUsername`, `getUserByIndex`, `userExistsByIndex`. | **Aprobado** |
| 2026-10-04 | `App\Models\VisitModels.php` | Bloqueo de creación de registros hacia usuarios inexistentes y registro estricto con `index_user`. | **Aprobado** |
| 2026-10-04 | `App\Models\LemonSqueezyModels.php` | Soporte de `index_user` en `user_subscriptions_cache` y consulta de estado rápido `isUserSubscribedFast`. | **Aprobado** |
| 2026-10-04 | `tests/run_site_tests.php` | Suite de pruebas de integración con 25 aserciones superadas con éxito (0 fallos). | **Aprobado** |
| 2026-10-04 | `BITACORA_ESTADISTICAS.md` | Creación de la bitácora oficial y especificación de directivas para desarrollo de métricas y gráficos. | **Completado** |
| 2026-10-04 | `App\Models\StatisticsModels.php` | Herencia de `BuilderSqlite`. Métodos `getCurrentMonthViews`, `getPreviousMonthsViews` y `getMonthlyViewsData` consultando estrictamente por `index_user` y calculando el promedio histórico de meses anteriores. Migración de `getSummaryByIndexUser` a métodos fluidos del Builder. | **Aprobado** |
| 2026-10-04 | `App\Controllers\StatisticsControllers.php` | Regla de negocio `evaluateMonthlyTrend`: evalúa si el mes actual aumentó (> promedio -> flecha verde `arrow-up`), bajó (< promedio -> flecha roja `arrow-down`) o quedó igual (== promedio o sin historial -> sin flecha). Integración con `LemonSqueezyModels::isUserSubscribedFast`. | **Aprobado** |
| 2026-10-04 | `App/Views/Dashboard/StatisticsPanel/MonthlyViews/monthlyViews.php` | Componente modular de métrica mensual: diseño responsivo con `.back-card-graphic` y `.shadow-card-graphic`, valor destacado en gran formato, indicador de flecha condicional al lado del total, detalle comparativo en porcentaje y desglose de meses anteriores que conforman el promedio. | **Aprobado** |
| 2026-10-04 | `App/Views/Dashboard/StatisticsPanel/statisticsPanel.php` | Plantilla base modular que conecta la sección `MonthlyViews/monthlyViews.php` mediante `_part("Dashboard.monthlyViews")`, con cabecera unificada y soporte para paneles premium. | **Aprobado** |
| 2026-10-04 | `tests/run_site_tests.php` | Nuevas aserciones para `getMonthlyViewsData`, todas las variantes de `evaluateMonthlyTrend` (aumento, descenso, igual, primer mes) y renderizado de la parte modular. 32 aserciones superadas (0 fallos). | **Aprobado** |
| 2026-10-04 | Compilación JIT (`composer min-script`) | Compilación de utilidades CSS JIT y minificación completa de JS y CSS de la plataforma. | **Aprobado** |
| 2026-10-04 | `App\Models\StatisticsModels.php` | Métodos `getCurrentMonthClicks`, `getCurrentMonthClicksByType`, `getAllTimeClicksByType`, `getUserConfiguredLinksCount` y `getCtoData` con `BuilderSqlite` por `index_user`. Clasificación de clics mediante `link_id NOT LIKE 'rrss_%'` (enlaces) y `LIKE 'rrss_%'` (redes sociales). | **Aprobado** |
| 2026-10-04 | `App\Controllers\StatisticsControllers.php` | Regla de negocio `evaluateCtoMetrics`: cálculo de tasa de conversión CTO mensual, promedio de clics por visita, promedio enlaces activos / visitas, índices porcentuales Enlaces vs RRSS y determinación de canal predominante. | **Aprobado** |
| 2026-10-04 | `App/Views/Dashboard/StatisticsPanel/Cto/cto.php` | Componente modular de métrica de CTO: tarjetas de tasa mensual, enlaces configurados vs visitas y clics acumulados; barra de proporción visual bicolor e índice de distribución entre Enlaces de contenido y Redes Sociales. | **Aprobado** |
| 2026-10-04 | `App/Views/Dashboard/StatisticsPanel/statisticsPanel.php` | Conexión del componente modular de CTO mediante `_part("Dashboard.cto", ["cto" => $cto])`. | **Aprobado** |
| 2026-10-04 | `tests/run_site_tests.php` | Nuevas pruebas para `getCtoData`, `evaluateCtoMetrics` (cálculo de tasa, promedios e índices) y renderizado de `Dashboard.cto`. 38 aserciones superadas con éxito (0 fallos). | **Aprobado** |
| 2026-10-04 | Compilación JIT (`composer min-script`) | Recompilación del motor JIT con las nuevas clases utilitarias del componente CTO y minificación de CSS/JS. | **Aprobado** |
| 2026-10-04 | `App\DatabaseComponent\DatabaseMaintenance.php` | Método `spreadAnalyticsDatesAcrossYear`: redistribución temporal de los 591 registros de `profile_views` y 420 registros de `link_clicks` a lo largo de los 10 meses de 2026 (Enero a Octubre), creando un histórico realista y continuo para métricas y gráficos premium. Respaldo previo creado en `clikhub.sqlite.bak_before_spreading`. | **Aprobado** |
| 2026-10-04 | `animations.js` & `animation-select.css` (Framework) | **Diagnóstico y corrección de `.hover-scale-soft`**: Se identificó que `initGsapHoverAnimations` no fallaba por `querySelector` (utilizaba `querySelectorAll`), sino porque vinculaba listeners estáticos individuales en `DOMContentLoaded`. Los elementos inyectados dinámicamente vía fetch/AJAX (`statisticsPanel`, `monthlyViews`, `cto`) o re-renderizados por reemplazo de `innerHTML` (`designDraftManager`, `autoSubmitForm`, `saveButtonController`) perdían o nunca recibían los listeners, sobreviviendo únicamente "Vista previa" por estar fuera de `remoteContainer`. Se migró a **Delegación Global de Eventos Reactiva** sobre `document` (`mouseover`/`mouseout` con `e.relatedTarget`) que cubre el 100% de elementos presentes y futuros. Se ajustó `--hover-scale-soft: 1.02` (de 1.005 imperceptible a escala suave de 2-3px) en `animation-select.css` y `card-graphic.css`. 43 pruebas unitarias aprobadas. | **Aprobado** |
| 2026-10-04 | `GraphicsModule::hybridSpline` & `monthlyViews.php` | **Integración de Gráfico HYBRID SPLINE**: Se reemplazó el sector estático de badges de meses anteriores por el gráfico SVG nativo `GraphicsModule::hybridSpline`. Se potenciaron tanto en el repositorio framework (`C:\Users\eber\Proyectos\frame`) como en Cuaderno los métodos de `hybridSpline` y su tooltip para soportar `autoScale` con 15% de holgura superior, `unit` ('Visitas'), `displayLabels` (nombres completos para tooltip) y `displayValues`. `StatisticsControllers::evaluateMonthlyTrend` construye la cronología completa de meses evaluados (Ene-Oct), y `monthlyViews.php` renderiza la curva spline continua con barras en cápsula translúcidas y vértices interactivos con tooltips, preservando además un `<details>` accesible con el desglose numérico. Se incorporó re-inicialización reactiva en `saveButtonController.js` y `autoSubmitForm.js` con `window.initCharts(wrapper)`. 45 pruebas unitarias aprobadas. | **Aprobado** |


