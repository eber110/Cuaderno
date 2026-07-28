# Componentes UI - Framework FME

Esta documentación describe todos los componentes JavaScript disponibles en `component.js` y cómo utilizarlos en tu HTML.

---

## Tabla de Contenidos

1. [Navegación](#1-componentes-de-navegación)
   - [modalMenu](#modalmenu)
   - [toUp](#toup)
2. [Layout](#2-componentes-de-layout)
   - [sidebar](#sidebar)
3. [Overlays/Dialogs](#3-componentes-de-overlaysdialogs)
   - [modal](#modal)
4. [Formularios](#4-componentes-de-formularios)
   - [repeat_camp](#repeat_camp)
   - [loadImgModern](#loadimgmodern)
5. [Feedback/UX](#5-componentes-de-feedbackux)
   - [notification](#notification)
   - [toast](#toast)
   - [toastTrigger](#toasttrigger)
   - [tooltip](#tooltip)
6. [Componentes Adicionales](#6-componentes-adicionales)
   - [accordion](#accordion)
   - [tabs](#tabs)
   - [carousel](#carousel)
   - [dropdown](#dropdown)
   - [copyToClipboard](#copytoclipboard)

---

## 1. Componentes de Navegación

### modalMenu

Sistema de menús dropdown con soporte para hover y click.

```html
<!-- Menú con click -->
<div class="btn-modal-menu">
  <button>Abrir Menú</button>
  <div class="content-modal-menu hidden">
    <a href="#">Opción 1</a>
    <a href="#">Opción 2</a>
    <a href="#">Opción 3</a>
  </div>
</div>

<!-- Menú con hover -->
<div class="btn-modal-menu on-mouse">
  <button>Hover aquí</button>
  <div class="content-modal-menu hidden">
    <a href="#">Opción 1</a>
    <a href="#">Opción 2</a>
  </div>
</div>
```

**Clases disponibles:**
| Clase | Descripción |
|-------|-------------|
| `.btn-modal-menu` | Contenedor principal |
| `.content-modal-menu` | Contenedor del menú |
| `.hidden` | Oculta el menú inicialmente |
| `.on-mouse` | Activa modo hover en lugar de click |
| `.left` / `.right` / `.bottom` | Posición del menú |

---

### toUp

Botón flotante para volver al inicio de la página.

```json
// En jsConfig.json
{
  "toUp": ["color2 flex row-direction center-center shine-hover"]
}
```

El botón se genera automáticamente y aparece cuando el usuario scrollea más de una pantalla de altura.

---

## 2. Componentes de Layout

### sidebar

Sidebar sticky bidireccional que se mantiene visible durante el scroll.

```html
<div class="grid-layout">
  <main class="main-content">
    <!-- Contenido principal -->
  </main>
  
  <aside class="sidebar-sticky" data-mobile-width="768">
    <!-- Contenido del sidebar -->
  </aside>
</div>
```

**Atributos:**
| Atributo | Descripción |
|----------|-------------|
| `data-mobile-width` | Ancho mínimo para activar sticky (en px) |

---

## 3. Componentes de Overlays/Dialogs

### modal

Sistema de modales con overlay.

```html
<!-- Botón que abre el modal (con fondo blur por defecto) -->
<button class="modal-btn">Abrir Modal</button>

<!-- Botón que abre el modal con fondo oscuro semitransparente (sin blur) -->
<button class="modal-btn darken">Abrir Modal Oscuro</button>

<!-- Contenido del modal (debe estar inmediatamente después del botón) -->
<div class="hidden">
  <h2>Título del Modal</h2>
  <p>Contenido del modal aquí...</p>
  <button>Acción</button>
</div>
```

El modal se genera dinámicamente copiando el contenido del elemento siguiente al botón.

**Clases adicionales en el botón activador:**
| Clase | Descripción |
|-------|-------------|
| `.animated` | Activa animaciones de apertura y cierre usando GSAP (si está disponible) |
| `.darken` | Reemplaza el filtro de desenfoque (`backdrop-filter: blur`) por un fondo oscuro semitransparente (`rgba(0, 0, 0, 0.5)`) |

---

## 4. Componentes de Formularios

### repeat_camp

Campos de formulario repetibles para agregar múltiples entradas.

```html
<div id="create-repeat">
  <input class="repeat-camp" name="option[]" placeholder="Opción 1">
</div>
```

```json
// En jsConfig.json - Límite de campos
{
  "repeat_camp": [5]
}
```

---

### loadImgModern

Input de archivos con UI mejorada, vistas previas de imágenes, reordenación drag & drop y eliminación.

El input de archivos original se oculta de forma automática y todas sus clases CSS y el atributo `placeholder` se copian directamente al botón de máscara visible. Si no se especifica un `placeholder`, se usará el texto por defecto "Agregar una imagen".

El contador de archivos seleccionados al lado del botón de subir archivo se muestra **únicamente si el input HTML tiene el atributo `data-note`**. El valor de este atributo especifica las clases CSS que se le aplicarán al contador.

**Ejemplo HTML:**
```html
<!-- Con contador de archivos personalizado (ej. fondo naranja y texto turquesa) -->
<input type="file" class="loadImgModern p10 br15 back2 color2" multiple accept="image/*" placeholder="Subir imagen" data-note="back2 color4 br15 p20">

<!-- Sin contador (solo el botón de subir archivos y la cuadrícula de vistas previas) -->
<input type="file" class="loadImgModern p10 br15 back2 color2" multiple accept="image/*" placeholder="Subir imagen">
```

**Configuración en `jsConfig.json`:**
Se activa sin argumentos:
```json
{
  "loadImgModern": []
}
```

---

## 5. Componentes de Feedback/UX

### notification

Notificaciones automáticas desde parámetros URL.

```
// URLs de ejemplo
http://tudominio.com?success=Operación%20exitosa
http://tudominio.com?error=Error%20en%20la%20operación
http://tudominio.com?warning=Advertencia%20importante
http://tudominio.com?danger=Acción%20peligrosa
```

Las notificaciones se muestran automáticamente al cargar la página si existen estos parámetros.

---

### toast

Notificaciones temporales programáticas.

```javascript
// Desde JavaScript
toast('Guardado correctamente', 'success');
toast('Error al guardar', 'error', 5000);
toast('Información importante', 'info');
toast('Ten cuidado', 'warning');
```

**Parámetros:**
| Parámetro | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `message` | string | - | Texto a mostrar |
| `type` | string | `'info'` | Tipo: `'success'`, `'error'`, `'warning'`, `'info'` |
| `duration` | number | `3000` | Duración en milisegundos |

---

### toastTrigger

Disparar toasts desde HTML sin JavaScript.

```html
<button class="toast-trigger" 
        data-message="¡Guardado exitosamente!" 
        data-type="success" 
        data-duration="3000">
  Guardar
</button>
```

**Atributos:**
| Atributo | Descripción |
|----------|-------------|
| `data-message` | Mensaje a mostrar (requerido) |
| `data-type` | Tipo: `'success'`, `'error'`, `'warning'`, `'info'` |
| `data-duration` | Duración en ms (default: 3000) |

---

### tooltip

Información adicional al hacer hover.

```html
<button class="tooltip" data-tooltip="Esto es un tooltip">
  Hover aquí
</button>

<!-- Con posición específica -->
<span class="tooltip top" data-tooltip="Arriba">Top</span>
<span class="tooltip bottom" data-tooltip="Abajo">Bottom</span>
<span class="tooltip left" data-tooltip="Izquierda">Left</span>
<span class="tooltip right" data-tooltip="Derecha">Right</span>
```

---

## 6. Componentes Adicionales

### accordion

Paneles colapsables.

```html
<div class="accordion">
  <div class="accordion-item">
    <div class="accordion-header">Sección 1</div>
    <div class="accordion-content">
      <p>Contenido de la sección 1</p>
    </div>
  </div>
  <div class="accordion-item">
    <div class="accordion-header">Sección 2</div>
    <div class="accordion-content">
      <p>Contenido de la sección 2</p>
    </div>
  </div>
  <div class="accordion-item">
    <div class="accordion-header">Sección 3</div>
    <div class="accordion-content">
      <p>Contenido de la sección 3</p>
    </div>
  </div>
</div>
```

---

### tabs

Sistema de pestañas.

```html
<div class="tabs">
  <div class="tabs-nav">
    <button class="tab-btn active" data-tab="tab1">Tab 1</button>
    <button class="tab-btn" data-tab="tab2">Tab 2</button>
    <button class="tab-btn" data-tab="tab3">Tab 3</button>
  </div>
  
  <div class="tab-content active" id="tab1">
    <p>Contenido del Tab 1</p>
  </div>
  <div class="tab-content" id="tab2">
    <p>Contenido del Tab 2</p>
  </div>
  <div class="tab-content" id="tab3">
    <p>Contenido del Tab 3</p>
  </div>
</div>
```

---

### carousel

Slider con múltiples modos de transición.

#### Modo Básico (sin animación)
```html
<div class="carousel" data-autoplay="5000">
  <div class="carousel-inner">
    <div class="carousel-item active">Slide 1</div>
    <div class="carousel-item">Slide 2</div>
    <div class="carousel-item">Slide 3</div>
  </div>
  <button class="carousel-prev">❮</button>
  <button class="carousel-next">❯</button>
  <div class="carousel-dots"></div>
</div>
```

#### Modo Slide (deslizamiento con rebobine)
```html
<div class="carousel carousel-slide" data-autoplay="4000">
  <div class="carousel-inner">
    <div class="carousel-item active">Slide 1</div>
    <div class="carousel-item">Slide 2</div>
    <div class="carousel-item">Slide 3</div>
  </div>
  <button class="carousel-prev">❮</button>
  <button class="carousel-next">❯</button>
</div>
```

#### Modo Fade (desvanecimiento)
```html
<div class="carousel carousel-fade" data-autoplay="4000">
  <div class="carousel-inner">
    <div class="carousel-item active">Slide 1</div>
    <div class="carousel-item">Slide 2</div>
    <div class="carousel-item">Slide 3</div>
  </div>
  <button class="carousel-prev">❮</button>
  <button class="carousel-next">❯</button>
</div>
```

#### Modo Slide Continuo (loop infinito)
```html
<div class="carousel carousel-slide-continuous" 
     data-autoplay="4000" 
     data-spacing="20">
  <div class="carousel-inner">
    <div class="carousel-item active p20 back2 br10">
      <h3>Slide 1</h3>
      <p>Contenido del slide</p>
    </div>
    <div class="carousel-item p20 back-success br10">
      <h3>Slide 2</h3>
      <p>Contenido del slide</p>
    </div>
    <div class="carousel-item p20 br10" style="background: linear-gradient(135deg, #667eea, #764ba2);">
      <h3>Slide 3</h3>
      <p>Contenido del slide</p>
    </div>
  </div>
  <p class="carousel-prev absolute left pointer">❮</p>
  <p class="carousel-next absolute right pointer">❯</p>
  <div class="carousel-dots flex center-center gap5 mt15"></div>
</div>
```

**Clases del carousel:**
| Clase | Descripción |
|-------|-------------|
| `.carousel` | Contenedor principal |
| `.carousel-slide` | Activa efecto de deslizamiento |
| `.carousel-fade` | Activa efecto de desvanecimiento |
| `.carousel-slide-continuous` | Activa loop infinito sin rebobine |
| `.carousel-inner` | Contenedor de slides |
| `.carousel-item` | Cada slide individual |
| `.carousel-prev` / `.carousel-next` | Botones de navegación |
| `.carousel-dots` | Contenedor de indicadores |

**Atributos del carousel:**
| Atributo | Descripción |
|----------|-------------|
| `data-autoplay` | Intervalo de autoplay en milisegundos |
| `data-spacing` | Separación entre slides en píxeles |

---

### dropdown

Select mejorado con búsqueda.

```html
<div class="dropdown-select" data-name="pais">
  <div class="dropdown-selected">Seleccionar país...</div>
  <div class="dropdown-options hidden">
    <input type="text" class="dropdown-search" placeholder="Buscar...">
    <div class="dropdown-option" data-value="ar">Argentina</div>
    <div class="dropdown-option" data-value="cl">Chile</div>
    <div class="dropdown-option" data-value="mx">México</div>
    <div class="dropdown-option" data-value="es">España</div>
    <div class="dropdown-option" data-value="co">Colombia</div>
  </div>
</div>
```

**Atributos:**
| Atributo | Descripción |
|----------|-------------|
| `data-name` | Nombre del campo hidden generado |
| `data-value` | Valor de cada opción |

---

### copyToClipboard

Copiar texto al portapapeles.

```html
<!-- Copiar texto directo -->
<button class="copy-btn" data-copy="Texto a copiar">
  Copiar texto
</button>

<!-- Copiar contenido de otro elemento -->
<pre id="codigo">const greeting = "Hola Mundo!";</pre>
<button class="copy-btn" data-copy-target="#codigo">
  Copiar código
</button>
```

**Atributos:**
| Atributo | Descripción |
|----------|-------------|
| `data-copy` | Texto a copiar directamente |
| `data-copy-target` | Selector CSS del elemento cuyo texto copiar |

### protectedMenuImage

Protege todas las imágenes del sitio contra el menú contextual del navegador (clic derecho) y, opcionalmente, evita que se puedan arrastrar (drag).

Se activa configurándolo en el archivo `jsConfig.json` bajo la sección `functions.defer`:

```json
"protectedMenuImage": []
```

**Parámetros en `jsConfig.json`:**
El primer parámetro define si las imágenes se pueden arrastrar (`true` por defecto) o no (`false`).

*   **Bloquear clic derecho, permitir arrastre (por defecto):**
    ```json
    "protectedMenuImage": []
    // o
    "protectedMenuImage": [true]
    ```

*   **Bloquear clic derecho y bloquear arrastre (drag):**
    ```json
    "protectedMenuImage": [false]
    ```

---

### cropImage

Componente interactivo para seleccionar y recortar una imagen antes de subirla (útil para fotos de perfil, avatares, etc.).

Abre un modal con fondo de desenfoque de vidrio (glassmorphism) y controles de zoom y arrastre interactivo de la imagen sobre un visor de máscara de recorte. Una vez recortada, el script genera el archivo recortado y reemplaza el archivo seleccionado en el input de forma nativa.

**Ejemplo HTML:**
```html
<input type="file" class="selectAndCropImage" accept="image/*" cropping-size="400x400" box-image="br15 back1" box-btn-image="p10 back5 br15">
```

**Atributos configurables:**
*   `cropping-size`: Define las dimensiones del recorte final en píxeles, en formato `ANCHO.x.ALTO` (ej: `400x400`, `300x450`). Por defecto es `400x400`. El aspecto del cuadro guía de recorte en la interfaz se adaptará de forma proporcional y automática según esta relación de aspecto.
*   `box-image`: Clases CSS que se aplicarán al cuadro/tarjeta del modal de recorte (ej: `br15 back1`). Permite definir su color de fondo, bordes, radios y sombras personalizadas de tu framework CSS.
*   `box-btn-image`: Clases CSS que se aplicarán a los botones dentro de la tarjeta modal (ej: `p10 back5 br15`). Aplica el padding, bordes y radio a ambos botones, y utiliza la clase de fondo (`back5`) en el botón principal ("Recortar y Guardar") mientras mantiene el botón "Cancelar" con un fondo gris semitransparente neutral para balancear el diseño.

**Configuración en `jsConfig.json`:**
Se activa sin argumentos:
```json
{
  "cropImage": []
}
```

---

### cutPhrase

Componente para ajustar dinámicamente un texto largo dentro de un contenedor (`div`, `p`, etc.) que tenga una altura fija o altura máxima definida por CSS.

Calcula en tiempo real cuántas líneas de texto caben de manera entera dentro de la altura interna útil del elemento (restando paddings y usando el `line-height` real del elemento) y aplica la propiedad CSS `-webkit-line-clamp` para cortar el texto y añadir los puntos suspensivos (`...`) de manera nativa sin desbordar el contenedor.

Se ejecuta automáticamente al cargar la página y recalcula el límite al redimensionar la ventana (`resize`).

**Ejemplo HTML:**
```html
<!-- Div con altura de 65px que contendrá un párrafo largo truncado en las líneas que quepan -->
<div class="cut-phrase" style="height: 65px; width: 500px;">
  Lorem ipsum dolor sit amet, consectetur adipiscing elit. Itaque quos consequatur necessitatibus atque maxime laboriosam.
</div>
```

**Configuración en `jsConfig.json`:**
Se activa sin argumentos en la sección `defer`:
```json
{
  "cutPhrase": []
}
```

---

## Configuración en jsConfig.json

Algunos componentes requieren configuración en `jsconfig.json`:

```json
{
  "defer": ["toUp", "sidebar", "tabs", "accordion", "tooltip", 
            "carousel", "dropdown", "loadImgModern", "repeat_camp", 
            "modal", "modalMenu", "copyToClipboard", "notification",
            "toastTrigger"],
  "toUp": ["color2 flex row-direction center-center shine-hover"],
  "loadImgModern": ["btn back-success color7"],
  "repeat_camp": [10]
}
```

El array `defer` indica qué componentes se inicializan automáticamente al cargar la página.

---

## Notas Importantes

1. **Clases CSS**: Todas las clases mencionadas deben estar definidas en tu archivo CSS.
2. **Orden de carga**: Asegúrate de que `component.js` se cargue después del DOM.
3. **Inicialización**: Los componentes en `defer` se inicializan automáticamente.
4. **Estilos inline**: Algunos componentes aplican estilos inline para funcionar correctamente.

---

*Documentación generada para el Framework FME - Última actualización: Enero 2026*
