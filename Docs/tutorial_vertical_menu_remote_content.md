# Tutorial: Componentes VerticalMenu y RemoteContent

Este tutorial explica detalladamente cómo estructurar y utilizar los componentes **`verticalMenu.js`** y **`remoteContent.js`** de forma individual, y cómo combinarlos para crear un panel de administración (Dashboard) dinámico y fluido con transiciones animadas y persistencia de estado local, tal como se implementó en [panel.php](file:///c:/Users/eber/Proyectos/Cuaderno/App/Views/Dashboard/Panel/panel.php).

---

## 1. Estructura y Funcionamiento de los Componentes JS

Ambos componentes están diseñados en formato de módulos de JavaScript moderno (ES6) y hacen uso de **delegación de eventos**, **GreenSock Animation Platform (GSAP)** opcional y almacenamiento en el navegador (**localStorage**).

### A. Componente `verticalMenu.js`
*   **Archivo fuente:** [verticalMenu.js](file:///c:/Users/eber/Proyectos/Cuaderno/vendor/eber/framework/Resources/Js/Components/verticalMenu.js)
*   **Propósito:** Controlar menús de navegación en acordeón (sidebar), colapsando y expandiendo grupos de forma automática, administrando clases activas de cabeceras/sub-enlaces y persistiendo el estado del menú tras recargar la página.

#### Estructura del HTML requerida:
```html
<!-- Contenedor Principal -->
<div id="mi-menu" class="vertical-menu animated w100" active-principal="active-header" active-item="active-link">
  <div class="flex-column gap10 w100">

    <!-- Grupo Colapsable (Acordeón) -->
    <div class="vertical-menu-item flex-column w100">
      <!-- Botón Cabecera -->
      <div class="vertical-menu-header w100 pointer">
        <p>Grupo de Menú</p>
        <span class="arrow-icon">▼</span>
      </div>
      <!-- Contenido / Enlaces Internos -->
      <div class="vertical-menu-content flex-column w100 hidden">
        <p class="vertical-menu-link active">Sub-elemento 1</p>
        <p class="vertical-menu-link">Sub-elemento 2</p>
      </div>
    </div>

    <!-- Enlace Raíz Directo (Sin colapsar) -->
    <p class="vertical-menu-link">Enlace Directo</p>

  </div>
</div>
```

#### Atributos configurables en `.vertical-menu`:
*   `class="animated"`: Activa las transiciones animadas de despliegue mediante GSAP.
*   `class="multi"`: Permite abrir múltiples acordeones a la vez sin colapsar los otros. (Por defecto, abrir uno cierra el resto).
*   `active-principal="clase-css"`: Especifica la clase CSS aplicada a la cabecera activa cuando se selecciona uno de sus hijos.
*   `active-item="clase-css"`: Especifica la clase CSS aplicada al sub-elemento o enlace raíz seleccionado.

---

### B. Componente `remoteContent.js`
*   **Archivo fuente:** [remoteContent.js](file:///c:/Users/eber/Proyectos/Cuaderno/vendor/eber/framework/Resources/Js/Components/remoteContent.js)
*   **Propósito:** Habilitar un sistema de pestañas desacoplado donde presionar un botón actualiza un contenedor dinámico con efectos de desvanecimiento (fade-in/fade-out).

#### Estructura del HTML requerida:
```html
<!-- Botones Gatillo (Pueden estar en cualquier parte del DOM, ej. Sidebar) -->
<button class="remote-btn active" data-remote="panel-1">Pestaña 1</button>
<button class="remote-btn" data-remote="panel-2">Pestaña 2</button>

<!-- Contenedor de Contenidos (Ej. Sección Principal) -->
<div class="remote-container animated">
  <!-- Paneles de Contenido -->
  <div id="panel-1" class="remote-content active">
    <h3>Contenido 1</h3>
  </div>
  <div id="panel-2" class="remote-content hidden">
    <h3>Contenido 2</h3>
  </div>
</div>
```

#### Clases clave en `.remote-container` y `.remote-content`:
*   `class="animated"` en el contenedor: Habilita la transición animada de desvanecimiento (GSAP).
*   `class="active"`: Elemento visible actualmente.
*   `class="hidden"`: Elemento oculto (aplicado automáticamente a los demás).

---

## 2. Tutorial: Cómo utilizar cada componente por separado

### Paso 1: Importar y Registrar en tu Inicializador JS
Para inicializar cualquiera de los dos módulos de forma separada, debes importarlos e invocarlos en tu archivo JavaScript principal:

```javascript
import { verticalMenu } from "./Components/verticalMenu.js";
import { remoteContent } from "./Components/remoteContent.js";

document.addEventListener("DOMContentLoaded", () => {
  // Inicializa el menú vertical de acordeón
  verticalMenu();

  // Inicializa el cargador de contenido remoto
  remoteContent();
});
```

### Paso 2: Uso exclusivo de `verticalMenu`
Ideal para barras laterales (sidebars) que navegan de forma nativa recargando páginas.
1. Define las URLs de destino en etiquetas `<a>` tradicionales agregando la clase `vertical-menu-link`.
2. Asigna la clase `active` al enlace que corresponda a la página actual en el backend.
3. El componente se encargará automáticamente de expandir el acordeón que contenga el enlace activo y persistir su apertura.

### Paso 3: Uso exclusivo de `remoteContent`
Ideal para cambiar de pestañas simples en la misma página (sin acordeones).
1. Crea los botones gatillo agregando la clase `remote-btn` y el atributo `data-remote="ID_DEL_CONTENIDO"`.
2. Crea el contenedor con clase `remote-container` y define dentro los divs con sus respectivos identificadores (`id`) y la clase `remote-content`.

---

## 3. Tutorial: Uso Combinado (Dashboard Profesional)

En un Dashboard moderno es común tener una barra lateral de acordeones (`verticalMenu`) y querer que las pestañas carguen de forma asíncrona a la derecha (`remoteContent`) sin recargar toda la página y manteniendo el estado de navegación.

### Cómo estructurar la combinación:

Para lograrlo, combinamos las clases en un mismo elemento. Cada enlace del menú lateral actuará tanto de enlace en el menú como de botón gatillo remoto:
`class="remote-btn vertical-menu-link"`

#### Estructura Completa del Dashboard:
```html
<div class="dashboard-layout">
  
  <!-- BARRA LATERAL (MENU + ACORDEÓN) -->
  <aside class="sidebar">
    <div class="vertical-menu animated" active-item="bg-active-link" active-principal="bg-active-header">
      <div class="flex-column gap10">

        <!-- Módulo Diseño (Desplegable) -->
        <div class="vertical-menu-item flex-column">
          <!-- Cabecera de Acordeón -->
          <div class="vertical-menu-header pointer">
            <span>Ajustes de Diseño</span>
          </div>
          <!-- Contenido de Enlaces Combinados -->
          <div class="vertical-menu-content flex-column hidden">
            <!-- Combinación: remote-btn + vertical-menu-link + data-remote -->
            <p class="remote-btn vertical-menu-link active" data-remote="tab-cabecera">Cabecera</p>
            <p class="remote-btn vertical-menu-link" data-remote="tab-fondo">Fondo</p>
          </div>
        </div>

        <!-- Enlace Directo Combinado -->
        <p class="remote-btn vertical-menu-link" data-remote="tab-contenido">Contenido</p>

      </div>
    </div>
  </aside>

  <!-- PANEL DE CONTENIDO PRINCIPAL (REMOTO) -->
  <main class="main-content">
    <div class="remote-container animated">
      
      <!-- Panel 1: Cabecera (Activo por defecto) -->
      <div id="tab-cabecera" class="remote-content active">
        <h2>Ajustes de Cabecera</h2>
        <!-- Formulario o contenido aquí -->
      </div>

      <!-- Panel 2: Fondo -->
      <div id="tab-fondo" class="remote-content hidden">
        <h2>Ajustes de Fondo</h2>
      </div>

      <!-- Panel 3: Contenido -->
      <div id="tab-contenido" class="remote-content hidden">
        <h2>Edición de Contenido</h2>
      </div>

    </div>
  </main>

</div>
```

---

## 4. Ventajas de la Combinación en el Framework

1.  **Prevención de FOUC (Flash of Unstyled Content):**
    *   Ambos componentes inyectan atributos (`data-menu-ready="true"` y `data-container-ready="true"`) una vez inicializados. Esto te permite usar estilos CSS para esconder los paneles u ocultar los acordeones mientras carga JavaScript, evitando saltos visuales molestos al usuario.
2.  **Persistencia tras refrescar (LocalStorage):**
    *   `verticalMenu.js` guarda automáticamente el índice del enlace seleccionado en el almacenamiento del navegador. Al refrescar la pantalla, el menú se expandirá exactamente en la misma sección y simulará un click asíncrono para volver a cargar la última pestaña activa.
3.  **Transición de Fluidos (GSAP):**
    *   Si tienes integrada la librería GSAP en el proyecto, tanto el acordeón al abrirse como el panel de contenidos al cambiar de pestaña realizarán transiciones suaves (animación de altura y desvanecimientos del panel).
