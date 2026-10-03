# Sistema de Gráficos SVG Minimalistas Animados (Vanilla SVG & CSS)

Guía técnica, plan de implementación y bitácora de avance para la creación de gráficos vectoriales minimalistas nativos con animaciones de entrada, estados y salida.

> **Regla de Oro:** **CERO LIBRERÍAS EXTERNAS** (sin D3, sin Chart.js). Geometría matemática SVG pura combinada con CSS nativo y JavaScript Vanilla para manipulación de atributos y coordenadas.

> **Nota de Planificación:** Esta fase se desarrollará **inmediatamente después** de completar y aprobar la librería de animaciones de texto y números ([`ANIMACIONES_TEXTO.md`](file:///c:/Users/eber/Proyectos/Cuaderno/ANIMACIONES_TEXTO.md)).

---

## 1. Reglas Obligatorias para el Agente IA

1. **Tiempo de animación normalizado (400 ms):** TODOS los gráficos deben cumplir con una duración estricta de animación de **400 ms**, tanto en su ciclo de entrada como en su ciclo de salida (`.is-exiting`).
2. **Animación simultánea (Sin retardos escalonados):** Todos los elementos que componen el gráfico (todas las barras, todas las cajas treemap, el arco de velocímetro, la línea de spline Bézier, los puntos de vértice y los contadores numéricos) **deben mostrarse y animarse al mismo tiempo**, con `delay: 0` (cero retardos secuenciales o *stagger*).
3. **Curva de suavizado obligatoria:** Utilizar siempre la curva matemática **`easeInOutCubic`** (en CSS: `cubic-bezier(0.42, 0, 0.58, 1)`), garantizando un movimiento suave, reactivo y pulido sin cortes bruscos.
4. **Arquitectura modular (Una librería para cada gráfico):** Cada tipo de gráfico se empaquetará como una **librería independiente y autocontenida**. Cada módulo dispondrá de sus propios métodos de inicialización (`init`), animación de entrada (`entry`), actualización de valores dinámicos (`updateValue`), animación de salida (`exit`) y eventos de interacción (tooltips, toggles), permitiendo utilizarlos de forma aislada sin acoplamientos innecesarios.
5. **Cero dependencias externas:** Todo SVG debe generarse de forma vectorial nativa (`<svg>`, `<path>`, `<rect>`, `<circle>`, `<g>`). Prohibido terminantemente el uso de D3, Chart.js o librerías pesadas.
6. **Ejecución de compilador obligatoria:** Tras modificar CSS o JS relacionados con los gráficos, ejecutar:
   ```bash
   composer min-script
   ```
7. **Registro continuo de avance:** Al validar y aprobar cada tipo de gráfico con sus respectivas animaciones, el Agente IA **debe registrar el avance en la tabla de bitácora de este documento**.
8. **Estética y Paleta monocromática:**
   - Fondo de tarjeta oscuro (`#18181b` / `#1e1e1e` / `#0c0c0e`).
   - Escala tonal: Blanco puro (`#ffffff`), Gris claro (`#a1a1aa`), Gris medio (`#888888` - `#939398`), Gris oscuro (`#52525b` - `#3f3f46`) y Pista/Borde (`#27272a`).

---

## 2. Arquitectura de Librerías Independientes por Gráfico

Cada gráfico se estructurará como un módulo Vanilla JS (ES Modules) independiente con API estandarizada:

| Librería / Módulo | Archivo previsto | Responsabilidad |
|---|---|---|
| **1. ArcMeter** | `App/Public/Js/Charts/arcMeter.js` | Semicírculo de 180° con recorrido dinámico (0% a N%), extremos redondeados, contador sincronizado y selector de porcentajes. |
| **2. StackedTones** | `App/Public/Js/Charts/stackedTones.js` | Columnas multicapa en cápsula con máscara clipPath, rejilla Y punteada y crecimiento simultáneo desde la línea base. |
| **3. TileTreemap** | `App/Public/Js/Charts/tileTreemap.js` | Partición de mosaicos asimétricos con fade in simultáneo y tooltips glassmorphic. |
| **4. HybridSpline** | `App/Public/Js/Charts/hybridSpline.js` | Barras translúcidas + cálculo matemático de Spline Bézier continuo passing-through + botón toggle Spline On/Off. |
| **5. PillPillars** | `App/Public/Js/Charts/pillPillars.js` | Columnas emparejadas en cápsula con conmutador bidireccional Col / Row y animación simultánea. |
| **Orquestador (Opcional)** | `App/Public/Js/svgChartAnimator.js` | Suite completa unificada para inicializar todos los gráficos o ejecutar salidas en bloque. |

---

## 3. Plan Detallado de Implementación

### Gráfico 1: Arc Meter (Speedometer / Gauge)
- **Estructura SVG:**
  - Arco base o track semicircular de 180° con fondo tenue y borde oscuro.
  - Arco principal blanco con `stroke-linecap="round"` y grosor proporcionado.
  - Centro con valor numérico grande (conectado a `animateCounter`) y subtítulo "Optimal Load".
- **Animaciones:**
  - **Entrada:** Dibujado progresivo del arco mediante `stroke-dashoffset` desde 0% hasta el valor final (ej. 78%).
  - **Salida:** Retracción del arco a 0 (`stroke-dashoffset` inverso) sincronizada con el descenso del contador numérico o desvanecimiento con contracción.

### Gráfico 2: Stacked Tones (Barras Apiladas Monocromáticas)
- **Estructura SVG:**
  - Rejilla Y con líneas discontinuas sutiles (`stroke-dasharray="3,3"`) y valores de referencia (0, 40, 80, 120, 160).
  - 4 columnas (Q1, Q2, Q3, Q4) con forma de cápsula redondeada.
  - Cada columna dividida en 3 capas apiladas: Base blanca, Cuerpo gris medio, Cima gris oscuro.
- **Animaciones:**
  - **Entrada:** Crecimiento vertical escalonado (`stagger`) desde la base `y=0` hacia arriba con curva de amortiguación.
  - **Salida:** Colapso descendente hacia la base y desvanecimiento de las capas.

### Gráfico 3: Tile Treemap (Particiones de Bloques)
- **Estructura SVG:**
  - Cuadrícula de 4 mosaicos con esquinas redondeadas (`rx="14"`):
    - Storage (45%, Blanco puro).
    - Compute (30%, Gris medio).
    - Network (15%, Gris oscuro intermedio).
    - Cache (10%, Gris carbón).
  - Tipografías y porcentajes posicionados limpiamente en cada bloque.
- **Animaciones:**
### Gráfico 4: Hybrid Spline + Bar (Barras Translúcidas con Curva Spline Continua)
- **Estructura SVG:**
  - 5 barras redondeadas en cápsula con fondo translúcido (`rgba(255, 255, 255, 0.1)`) y borde tenue.
  - Curva Spline Bézier cúbica continua passing-through calculada matemáticamente sin librerías externas.
  - Puntos circulares (`<circle>`) en cada cúspide o vértice.
  - Botón interactivo en cabecera: "Spline On / Off" para encender/apagar la curva con animación suave.
- **Animaciones:**
  - **Entrada:** Barras crecen desde la base; la curva spline se dibuja de izquierda a derecha con `stroke-dashoffset`; los puntos de cúspide emergen con escala.
  - **Salida:** Repliegue de la curva a 0, implosión de puntos a escala 0 y colapso descendente de barras.

### Gráfico 5: Rounded Pill Pillars (Columnas Emparejadas con Conmutador Col / Row)
- **Estructura SVG:**
  - 4 grupos de columnas con cápsulas totalmente redondeadas (`rx="8" ry="8"`).
  - Pares de contraste: Barra Principal (Blanco puro) y Barra Secundaria (Gris oscuro `#424246`).
  - Conmutador interactivo Segmented Pill: "Col | Row".
    - Modo Col: Columnas verticales con etiquetas X y rejilla horizontal.
    - Modo Row: Columnas horizontales con etiquetas Y y rejilla vertical.
- **Animaciones:**
  - **Entrada:** Crecimiento en escala desde la base según orientación activa.
  - **Salida:** Colapso a escala 0 con desvanecimiento.

---

## 4. Bitácora de Avance y Estado

> **Instrucción para el Agente:** Actualizar esta tabla en cada aprobación.

| Fecha | Componente de Gráfico | Estado | Observaciones y Detalles Aprobados |
|---|---|---|---|
| 2026-10-02 | Definición de especificación | 📋 Planificado | En espera de la finalización de las animaciones de texto/números. |
| 2026-10-03 | Arc Meter Gauge | ✅ Implementado | Arco semicircular de 180° (`R=80`), `stroke-linecap="round"`, lectura central y animación coordinada. |
| 2026-10-03 | Stacked Tones Bar | ✅ Implementado | 4 columnas con clipPath en cápsula redondeada (`rx="16"`), 3 capas monocromáticas (blanco, gris claro, gris oscuro) y rejilla Y punteada. |
| 2026-10-03 | Tile Treemap | ✅ Implementado | Partición asimétrica 2x2 redondeada (Storage 45%, Compute 30%, Network 15%, Cache 10%) con tooltips glassmorphic. |
| 2026-10-03 | Hybrid Spline + Bar | ✅ Implementado | 5 barras en cápsula translúcida + curva spline cúbica matemática continua Bézier + botón interactivo Spline On/Off. |
| 2026-10-03 | Rounded Pill Pillars | ✅ Implementado | Columnas agrupadas en parejas monocromáticas (blanco / gris) con conmutador funcional interactivo Col / Row. |
| 2026-10-03 | Utilidades Transversales | ✅ Implementado | Botón "Copiar SVG" en cada tarjeta, tooltips dinámicos flotantes y ciclo completo `.is-exiting`. |
| 2026-10-03 | Normalización 400 ms & Modularización | ✅ Aprobado | Duración unificada a **400 ms** para todos los gráficos sin retardos escalonados (todo simultáneo). Se acuerda empaquetar cada gráfico en una **librería independiente**. |
| 2026-10-03 | Migración a Eber Framework (GraphicsModule & InitAppStructure) | 🚀 Completado | Creado `Base\Module\GraphicsModule.php`, helpers `_chart()` / `_chartToString()` en `Part.php`, estilos OKLCH en `graphics-module.css`, librerías independientes en `Resources/Library/Charts/` (`arcMeter.js`, `stackedTones.js`, `tileTreemap.js`, `hybridSpline.js`, `pillPillars.js`, `charts.js`, `charts.css`) y automatización en `InitAppStructure.php` (`App/Rsc/Library/Charts` + registro automático en `loadLibraryJsConfiguration.php`). |
| 2026-10-03 | Soporte colorLabel y clases sin punto | ✅ Completado | Configuración global y local con `GraphicsModule::configStyle(["colorLabel" => "textw", "color" => "texto", "transition" => 400])`. Resolución automática de nombres de clase sin requerir punto (`"texto"`, `"textw"`). Alto contraste garantizado para etiquetas y lecturas internas de todos los gráficos (ejes Y/X, lecturas de arco y mosaicos de Treemap). |
| 2026-10-03 | Soporte axisLabel y contraste total en Treemap | ✅ Completado | Añadido `axisLabel` en `configStyle()` y métodos individuales (`"axisLabel" => "color1"`). Se asigna a números de eje Y (Stacked Tones) y etiquetas de desglose de eje X (Stacked Tones, Hybrid Spline, Pill Pillars). En **Tile Treemap**, se unificó para que todos los elementos usen `colorLabel` (`textw`), eliminando el override oscuro en Tile 1 para mantener el 100% de contraste sobre cualquier fondo. |
| 2026-10-03 | Fade en puntos de Hybrid Spline | ✅ Corregido | Se eliminó el escalado/recorrido lateral de los puntos (`.mono-spline-dot`). Ahora los vértices se mantienen en su posición exacta (`cx`, `cy`) e inician su aparición al final del trazado de la curva (`fadeDelay = duration`), desvaneciéndose suavemente con fade (`opacity: 0 -> 1`). Se agregó `transform-box: fill-box` en CSS para un escalado hover concéntrico perfecto sin interferir en la animación. |
| 2026-10-03 | Layout Dinámico y Fallbacks de Vista Previa | ✅ Completado | Eliminadas las coordenadas rígidas en PHP. **Stacked Tones** distribuye dinámicamente cualquier cantidad de columnas (ej. 5 días Lunes-Viernes, 7 días, etc.) recalculando anchos, radios y rejilla sin desbordar el contenedor. **Tile Treemap** incorpora partición adaptativa multidimensional (`computeTreemapLayout`) que soporta cualquier cantidad de bloques (1 a 8+) con modo compacto en línea para tarjetas de menor altura. **Hybrid Spline** y **Pill Pillars** recalculan automáticamente sus coordenadas X y anchos. Se documentaron todos los arrays estáticos como datos de vista previa/fallback cuando `$params` no suministra datos. |
| 2026-10-03 | Soporte nativo para ES Modules en LoadViewStyle (Corrección SyntaxError consola) | ✅ Resuelto | Se solucionaron los 6 errores de consola `SyntaxError: Cannot use import statement outside a module` y `Unexpected token 'export'`. En `Core\ConfigLoader\LoadViewStyle.php`, se implementó detección inteligente de módulos ES (`isEsModuleFile`) y detección de orquestadores con dependencias locales (`hasRelativeImports`). Al cargar una librería modular como `Charts`, el framework inyecta únicamente el archivo de entrada principal (`charts.js`) con `type="module"`, permitiendo que el navegador resuelva sus submódulos de forma nativa sin generar scripts duplicados ni ejecutar módulos en modo clásico (`defer`). |
| 2026-10-03 | Soporte nativo para colores HEX con y sin almohadilla (#) | ✅ Completado | Se implementó reconocimiento de valores hexadecimales (3, 4, 6 y 8 dígitos) en `GraphicsModule::resolveColorToken()`. Ahora tanto `GraphicsModule::configStyle()` como las llamadas individuales aceptan indistintamente clases atómicas (`texto`, `textw`), clases con punto (`.texto`), variables CSS (`var(--back-color5)`) y colores hexadecimales directos con `#` (`#3b82f6`, `#ffffff`) o sin `#` (`3b82f6`, `ffffff`, `a1a1aa`, anteponiendo `#` automáticamente). Se inyectó `color: <hex>;` en el SVG y `color: var(--chart-base-color)` en CSS para garantizar herencia fiel de `currentColor` y cálculo perfecto de las derivaciones monocromáticas OKLCH/color-mix. |







