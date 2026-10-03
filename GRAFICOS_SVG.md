# Sistema de Gráficos SVG Minimalistas Animados (Vanilla SVG & CSS)

Guía técnica, plan de implementación y bitácora de avance para la creación de gráficos vectoriales minimalistas nativos con animaciones de entrada, estados y salida.

> **Regla de Oro:** **CERO LIBRERÍAS EXTERNAS** (sin D3, sin Chart.js). Geometría matemática SVG pura combinada con CSS nativo y JavaScript Vanilla para manipulación de atributos y coordenadas.

> **Nota de Planificación:** Esta fase se desarrollará **inmediatamente después** de completar y aprobar la librería de animaciones de texto y números ([`ANIMACIONES_TEXTO.md`](file:///c:/Users/eber/Proyectos/Cuaderno/ANIMACIONES_TEXTO.md)).

---

## 1. Reglas Obligatorias para el Agente IA

1. **Secuencia estricta:** No iniciar la codificación final de los gráficos SVG hasta que el usuario haya aprobado y cerrado las animaciones de texto.
2. **Cero dependencias:** Todo SVG debe generarse de forma vectorial nativa (`<svg>`, `<path>`, `<rect>`, `<circle>`, `<g>`).
3. **Ejecución de compilador obligatoria:** Tras modificar CSS o JS relacionados con los gráficos, ejecutar:
   ```bash
   composer min-script
   ```
4. **Registro continuo de avance:** Al validar y aprobar cada tipo de gráfico (Arc Meter, Stacked Tones, Tile Treemap) con sus respectivas animaciones de salida, el Agente IA **debe registrar el avance en la tabla de este documento**.
5. **Estética y Paleta monocromática:**
   - Fondo de tarjeta oscuro (`#18181b` / `#1e1e1e`).
   - Escala tonal: Blanco puro (`#ffffff`), Gris medio (`#888888` - `#9ca3af`), Gris oscuro (`#3f3f46` - `#52525b`) y Pista/Borde (`#27272a`).

---

## 2. Archivos a Crear y Modificar

| Tipo | Archivo | Propósito |
|---|---|---|
| **CSS** | `App/Public/Css/minimal-charts.css` | Estilos para contenedores de gráficos, tracks, rejillas punteadas, keyframes y clases de salida (`.is-exiting`, `.arc-collapse`, etc.). |
| **JS** | `App/Public/Js/svgChartAnimator.js` | Funciones para generar o animar dinámicamente los gráficos SVG (interpolación de arcos, alturas de barras apiladas y escalado de tiles). |
| **Vistas / Componentes** | `App/Views/Test/test2.php` | Maquetación visual de las 3 tarjetas de referencia idénticas a las imágenes del usuario. |

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
  - **Entrada:** Escala desde `scale(0.85)` y revelado por opacidad en cascada.
  - **Salida:** Implosión suave (`scale(0.92)` y `opacity: 0`) o deslizamiento lateral coordinado.

---

## 4. Bitácora de Avance y Estado

> **Instrucción para el Agente:** Actualizar esta tabla en cada aprobación.

| Fecha | Componente de Gráfico | Estado | Observaciones y Detalles Aprobados |
|---|---|---|---|
| 2026-10-02 | Definición de especificación | 📋 Planificado | En espera de la finalización de las animaciones de texto/números. |
