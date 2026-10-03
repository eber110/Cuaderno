# Sistema de Animaciones para Texto y Números (Vanilla JS & CSS)

Guía técnica, plan de implementación y bitácora de avance para la librería de animaciones de texto y números.

> **Regla de Oro:** **CERO LIBRERÍAS EXTERNAS**. Todo se construye con JavaScript moderno (Vanilla ES Modules) y CSS nativo (Custom Properties, Transiciones y Keyframes).

---

## 1. Reglas Obligatorias para el Agente IA

1. **Sin dependencias externas:** No utilizar librerías de terceros (ni GSAP, ni Anime.js, ni CountUp.js, etc.).
2. **PROHIBIDO EL USO DE IDs PARA ANIMAR:** Nunca enlazar animaciones de texto o números mediante identificadores (`id="..."`). El sistema opera 100% mediante clases CSS:
   ```html
   <p class="text-animation text-scramble">Texto a decodificar</p>
   <span class="text-animation text-counter" data-target="78" data-suffix="%">0%</span>
   ```
3. **Descubrimiento y ejecución masiva con `querySelectorAll()`:** La librería debe usar `querySelectorAll()` para permitir que múltiples textos compartan la misma animación en el DOM y se animen en paralelo de manera independiente.
4. **Ejecución de compilador obligatoria:** Tras modificar cualquier archivo en `App/Public/Js/` o `App/Public/Css/`, es **obligatorio** ejecutar:
   ```bash
   composer min-script
   ```
   para procesar la minificación y el JIT en `App/Public/Min/`.
5. **Registro continuo de avance:** Cada vez que el usuario apruebe un diseño, animación o efecto, el Agente IA **debe actualizar la sección de Avance y Estado** al final de este archivo antes de pasar al siguiente paso.
6. **Convenciones del proyecto:**
   - Código, comentarios y JSDoc en **español**.
   - Modularidad ES: `export function ...`.
   - Controladores limpios, datos en modelos o mocks de prueba estructurados, vistas en `App/Views/Test/test2.php`.

---

## 2. Archivos del Sistema

| Tipo | Archivo | Propósito |
|---|---|---|
| **JS** | `App/Public/Js/textAnimator.js` | Funciones exportables para números (odometer/lerp) y textos con `querySelectorAll()`. |
| **CSS** | `App/Public/Css/text-animator.css` | Clases base `.text-animation`, keyframes (blur, typewriter cursor, shimmer, slide, etc.). |
| **Controlador** | `App/Controllers/TestControllers.php` | Controlador del entorno de prueba con datos mock para testear. |
| **Vista** | `App/Views/Test/test2.php` | Sandbox visual con tarjetas oscuras, botones de control (Entrada, Loops, Salida, Reset). |
| **Config** | `jsConfig.json` | Registro de la función en el pipeline del framework si aplica defer. |

---

## 3. Catálogo de Clases CSS de Animación

### Clases de Entrada
- `text-animation text-scramble`: Decodificación estilo hacker/terminal resolviendo caracteres al azar.
- `text-animation text-typewriter`: Máquina de escribir con cursor parpadeante dinámico.
- `text-animation text-fade-blur`: Entrada con desenfoque progresivo y curva de frenado suave.
- `text-animation text-slide-up`: Deslizamiento vertical hacia arriba.
- `text-animation text-split-chars`: Letras separadas apareciendo una a una con rebote suave.
- `text-animation text-counter`: Contador numérico animado LERP/Easing (sube desde 0 hasta `data-target` o baja a 0 en salida).

### Clases de Bucles (Loops)
- `text-animation text-loop-pulse` (o `text-pulse`): Pulsación rítmica sutil para estados activos o en vivo.
- `text-animation text-loop-shimmer` (o `text-shimmer`): Barrido de brillo metálico continuo a través del texto.
- `text-animation text-loop-breathing` (o `text-breathing`): Respiración lumínica de sombra y contraste.

### Clases de Salida
- `text-animation is-exiting`: Desvanecimiento con desenfoque hacia arriba.
- `text-animation text-exit-slide-down`: Desvanecimiento deslizándose hacia abajo.
- `text-animation text-exit-collapse`: Contracción suave hacia el centro.

---

## 4. Bitácora de Avance y Estado

> **Instrucción para el Agente:** Actualizar esta tabla en cada aprobación.

| Fecha | Componente / Animación | Estado | Observaciones y Detalles Aprobados |
|---|---|---|---|
| 2026-10-02 | Efectos visuales de texto (scramble, typewriter, fade-blur, split-chars, loops) |  Aprobado por el usuario | El usuario confirmó que le gustaron todos los textos y efectos visuales. |
| 2026-10-02 | Refactorización de arquitectura a Clases y `querySelectorAll` |  Completado y validado | Se eliminó por completo el uso de IDs para invocar textos. Ahora se utiliza estrictamente `class="text-animation text-<nombre>"` y procesamiento masivo con `querySelectorAll()`. |
| 2026-10-02 | Corrección efecto Scramble (Layout Shift / Salto de tamaño) |  Corregido y estabilizado | Se eliminó el cambio dinámico a tipografía monospace. Ahora conserva la fuente, tamaño, peso y altura originales, y fija dimensiones durante la animación para evitar saltos en el contenedor. |
| 2026-10-02 | Desacoplamiento de `scrollObserver.js` y corrección Landing Page / Sandbox |  Resuelto y verificado | Se resolvió colisión de identificadores en `js.min.js` (`extractThreshold`). Se desacopló el observer del framework en su módulo autónomo `scrollObserver.js` restaurando el cálculo proporcional para `.observer` (`ob-40`, `ob-30`, etc.), permitiendo que la landing page y todos los textos animados funcionen sin colisiones. |
