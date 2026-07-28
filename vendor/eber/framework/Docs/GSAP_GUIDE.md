# 🎬 Guía de Integración GSAP

GSAP (GreenSock Animation Platform) es una biblioteca de animaciones JavaScript de alto rendimiento.

## 📦 Instalación

Los archivos ya están instalados en:
```
resources/library/gsap/
├── gsap.min.js           # Core de GSAP
└── ScrollTrigger.min.js  # Plugin para animaciones con scroll
```

## 🚀 Carga Básica

### En `load-style.php`:

```php
<?php
use Core\ConfigLoader\loadViewStyle;
$load_style = new loadViewStyle();

// Solo GSAP core
$load_style->gsapLoad();

// GSAP + ScrollTrigger
$load_style->gsapLoad(['ScrollTrigger']);
```

> [!IMPORTANT]
> Llama a `gsapLoad()` **ANTES** de `ruteStyle()` para que GSAP esté disponible para tus scripts.

---

## 🎯 Uso en Componentes JS

### Animación Básica

```javascript
// Animar un elemento al cargar la página
gsap.from('.hero-title', {
  duration: 1,
  y: 50,
  opacity: 0,
  ease: 'power2.out'
});

// Animar hacia un estado final
gsap.to('.button', {
  duration: 0.3,
  scale: 1.1,
  ease: 'back.out'
});
```

### Integración con tus Componentes

Ejemplo integrando GSAP en tu función `modal`:

```javascript
// En resources/js/components/modal.js
export function modal() {
  const modals = document.querySelectorAll('[data-modal]');
  
  modals.forEach(modal => {
    modal.addEventListener('show', () => {
      // Animación de entrada con GSAP
      gsap.fromTo(modal, 
        { opacity: 0, scale: 0.9 },
        { duration: 0.3, opacity: 1, scale: 1, ease: 'power2.out' }
      );
    });
    
    modal.addEventListener('hide', () => {
      // Animación de salida
      gsap.to(modal, {
        duration: 0.2,
        opacity: 0,
        scale: 0.95,
        onComplete: () => modal.classList.add('hidden')
      });
    });
  });
}
```

---

## 🔄 Animaciones con ScrollTrigger

```javascript
// Fade-in al hacer scroll
gsap.from('.card', {
  scrollTrigger: {
    trigger: '.card',
    start: 'top 80%',    // Inicia cuando el elemento está al 80% del viewport
    toggleActions: 'play none none reverse'
  },
  y: 60,
  opacity: 0,
  duration: 0.8,
  stagger: 0.2  // Delay entre elementos
});
```

### Parallax Simple

```javascript
gsap.to('.background-image', {
  scrollTrigger: {
    trigger: '.hero-section',
    scrub: true  // Sincroniza con el scroll
  },
  y: 100
});
```

---

## ⚡ Timelines (Secuencias)

```javascript
// Crear una secuencia de animaciones
const tl = gsap.timeline({ defaults: { ease: 'power2.out' } });

tl.from('.logo', { duration: 0.5, y: -30, opacity: 0 })
  .from('.nav-item', { duration: 0.4, x: 20, opacity: 0, stagger: 0.1 }, '-=0.2')
  .from('.hero-content', { duration: 0.6, y: 40, opacity: 0 }, '-=0.3');
```

---

## 🎨 Easings Populares

| Easing | Efecto |
|--------|--------|
| `power2.out` | Suave, natural (recomendado) |
| `back.out(1.7)` | Rebote al final |
| `elastic.out` | Efecto elástico |
| `bounce.out` | Rebote como pelota |
| `expo.inOut` | Muy dramático |

---

## 📱 Ejemplo Completo: Componente Accordion con GSAP

```javascript
// En resources/js/components/accordion.js
export function accordion() {
  const items = document.querySelectorAll('.accordion-item');
  
  items.forEach(item => {
    const header = item.querySelector('.accordion-header');
    const content = item.querySelector('.accordion-content');
    
    // Ocultar contenido inicialmente
    gsap.set(content, { height: 0, opacity: 0 });
    
    header.addEventListener('click', () => {
      const isOpen = item.classList.contains('active');
      
      if (isOpen) {
        // Cerrar
        gsap.to(content, {
          duration: 0.3,
          height: 0,
          opacity: 0,
          ease: 'power2.inOut',
          onComplete: () => item.classList.remove('active')
        });
      } else {
        // Abrir
        item.classList.add('active');
        gsap.to(content, {
          duration: 0.4,
          height: 'auto',
          opacity: 1,
          ease: 'power2.out'
        });
      }
    });
  });
}
```

---

## 🔧 Plugins Adicionales

Para agregar más plugins, descárgalos de [cdnjs.com/libraries/gsap](https://cdnjs.com/libraries/gsap):

```powershell
# Ejemplo: descargar TextPlugin
Invoke-WebRequest -Uri "https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/TextPlugin.min.js" `
  -OutFile "resources/library/gsap/TextPlugin.min.js"
```

Luego cárgalos:
```php
$load_style->gsapLoad(['ScrollTrigger', 'TextPlugin']);
```

---

## 🎬 Sistema de Animación Híbrido (CSS / GSAP)

El framework incluye un sistema unificado en `Resources/Js/Components/animations.js` y `Resources/Css/animation.css` que permite usar animaciones fluidas tanto a través de clases de CSS tradicionales como de la API programática de GSAP de forma 100% transparente.

### 🌟 Efectos Disponibles

Puedes usar cualquiera de los siguientes efectos mediante CSS o JS:

| Nombre del Efecto | Clase CSS | Comportamiento |
|---|---|---|
| `fadeIn` / `fadeOut` | `.fade-in` / `.fade-out` | Desvanecimiento suave |
| `slideInLeft` / `slideOutLeft` | `.slide-in-left` / `.slide-out-left` | Desplazamiento desde/hacia la izquierda con opacidad |
| `slideInRight` / `slideOutRight` | `.slide-in-right` / `.slide-out-right` | Desplazamiento desde/hacia la derecha con opacidad |
| `slideInTop` / `slideOutTop` | `.slide-in-top` / `.slide-out-top` | Desplazamiento desde/hacia arriba con opacidad |
| `slideInBottom` / `slideOutBottom` | `.slide-in-bottom` / `.slide-out-bottom` | Desplazamiento desde/hacia abajo con opacidad |
| `zoomIn` / `zoomOut` | `.zoom-in` / `.zoom-out` | Escalado de zoom suave con opacidad |
| `scaleIn` / `scaleOut` | `.scale-in` / `.scale-out` | Equivalente a zoom |
| `bounceIn` / `bounceOut` | `.bounce-in` / `.bounce-out` | Rebote elástico premium (`elastic.out` / `cubic-bezier`) |
| `spin` | `.spin` | Rotación de 360 grados (soporta infinito) |
| `pulse` | `.pulse` | Latido suave de escala (infinito por defecto) |

---

### 💻 Uso programático: `window.animate()`

El helper global `window.animate(element, effectName, options)` decide inteligentemente qué motor usar:
1. **Si GSAP está disponible:** Ejecuta la animación mediante GSAP logrando la máxima fluidez de fotogramas posible.
2. **Si GSAP no está disponible:** Aplica la clase de CSS correspondiente utilizando transiciones aceleradas por hardware en el navegador (`will-change`, transformaciones 3D).

```javascript
// Uso básico
animate('#mi-elemento', 'fadeIn');

// Con opciones avanzadas (compatibles en ambos motores)
animate('.tarjeta', 'slideInLeft', {
  duration: 0.8,              // Duración en segundos
  delay: 0.2,                 // Retraso en segundos
  ease: 'power2.out',         // Curva de flexibilización (GSAP o cubic-bezier de CSS)
  onComplete: () => {
    console.log('¡Animación terminada!');
  }
});

// Animación de salida con limpieza de estilos al terminar
animate('.modal', 'zoomOut', {
  duration: 0.3,
  clearProps: 'all'           // Remueve los estilos inline aplicados tras finalizar
});
```

---

### 👁️ Animación automática al hacer Scroll

Puedes animar elementos del DOM automáticamente cuando entran en la pantalla usando atributos `data-*`. El sistema integra `ScrollTrigger` de GSAP si está cargado o utiliza `IntersectionObserver` de forma nativa como fallback.

```html
<!-- Animación básica al hacer scroll -->
<div data-animate="fadeIn">
  Contenido animado...
</div>

<!-- Animación con duración y retraso específicos -->
<div data-animate="slideInBottom" 
     data-animate-duration="0.8" 
     data-animate-delay="0.3" 
     data-animate-ease="cubic-bezier(0.25, 1, 0.5, 1)">
  Deslizar desde abajo...
</div>

<!-- Animación que se dispara al cargar en lugar del scroll -->
<div data-animate="bounceIn" data-animate-trigger="load">
  ¡Hola, soy un rebote de carga!
</div>

<!-- Animaciones repetitivas o infinitas -->
<div class="animate-infinite" data-animate="pulse">
  Efecto latido continuo...
</div>
```

---

## ✅ Verificar Instalación

Abre la consola del navegador y ejecuta:
```javascript
console.log(gsap.version);  // Debería mostrar: "3.12.5"
console.log(typeof window.animate); // Debería mostrar: "function"
```

---

## 📚 Recursos

- [Documentación oficial de GSAP](https://gsap.com/docs/)
- [GSAP Cheat Sheet](https://gsap.com/cheatsheet/)
- [Ejemplos interactivos](https://codepen.io/GreenSock)
