# Eber Framework

[![Estado del Desarrollo](https://img.shields.io/badge/Estado-50%25-green.svg)]()
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-blue.svg)]()

Un framework PHP moderno y eficiente diseñado para optimizar el rendimiento y la carga de páginas web. Ideal para construir sitios web dinámicos y aplicaciones web de alto rendimiento.

## 🚀 Características Principales

- ⚡ **Optimización de Carga**: Sistema avanzado de carga asíncrona y diferida de recursos
- 🎨 **Sistema de Vistas Modular**: Arquitectura MVC clara y flexible
- 📱 **Diseño Responsivo**: Soporte completo para desarrollo móvil
- 🔒 **Seguridad Integrada**: Middleware de seguridad y manejo de sesiones
- 📊 **Sistema de Monitoreo**: Seguimiento de interacciones y visitas
- 🖼️ **Procesamiento de Medios**: Manejo optimizado de imágenes y recursos
- ❤️ **Sistema de Interacciones**: Likes, favoritos, guardados y compartidos

## 💡 Casos de Uso

El framework es ideal para:
- Sitios web corporativos
- Blogs y portales de contenido
- Redes sociales personalizadas
- Aplicaciones web interactivas
- Próximamente: E-commerce (en desarrollo)

## 🛠️ Estructura del Proyecto

```
app/
├── controllers/     # Controladores de la aplicación
├── middleware/      # Middleware de seguridad y logging
├── models/          # Modelos de datos
├── segment/         # Componentes reutilizables
└── views/           # Vistas y templates

base/
├── builder/         # Query Builder para MySQL/PostgreSQL
├── control/         # Clase base de controladores
├── helpers/         # Funciones helper (svg, etc.)
├── module/          # Módulos del framework
└── scriptComposer/  # Minificación JS/CSS

app/
├── route/           # Definición de rutas
└── ...

core/                # Componentes core (Route, Conexion, etc.)
public/              # Recursos públicos
resources/           # Assets del framework
```

## 📦 Módulos Principales

### Sistema de Rutas (`Core\Route`)
- Rutas GET, POST, PUT, DELETE
- Grupos de rutas con prefijos
- Middleware global y por grupo
- Excepciones de middleware
- Parámetros dinámicos en URLs

### Query Builder (`Base\builder\Builder`)
- Soporte para MySQL y PostgreSQL
- Consultas fluidas encadenables
- JOINs, WHERE, GROUP BY, HAVING, ORDER BY
- Sistema de paginación integrado
- Protección contra SQL Injection

### Módulo de Sesiones (`Base\module\Session`)
- `session_active()` - Verificar sesión activa
- `session_data($key)` - Obtener datos de sesión
- `create_user_session()` - Crear sesión de usuario
- `role()` / `admin()` - Sistema de roles

### Módulo de Interacciones (`Base\module\interactionModule`)
- **Tipos soportados**: like, favorite, save, share
- **Toggle automático**: Añade o elimina con un solo método
- **Soporte dual**: Usuarios autenticados y visitantes anónimos
- **Métodos principales**:
  - `toggleSave()`, `toggleLike()`, `toggleFavorite()`
  - `hasSaved()`, `hasLiked()`, `hasFavorited()`
  - `countSaves()`, `countLikes()`, `countFavorites()`
  - `getPostSummary()` - Resumen completo de interacciones

### Módulo de Visitas (`Base\module\visitModule`)
- Registro automático de visitas
- Geolocalización de visitantes
- Clasificación de visitantes vs usuarios

### Módulo de Imágenes (`Base\module\imgProcessModule`)
- Procesamiento y compresión de imágenes
- Múltiples formatos soportados
- Optimización para web

### Otros Módulos
- � `emailModule` - Servicio de Email
- 🔍 `validateModule` - Validación de Datos
- 📱 `deviceModule` - Detector de Dispositivos
- ⏰ `DateTimeModule` - Manejo de fechas y "time ago"
- 📤 `ResponseModule` - Respuestas JSON y redirecciones

### 🔒 Enmascaramiento de Enlaces Sin Pestañeo (`Resources\Js\hideNavigationPath.js`)
*   **On-Demand Href Restoration:** Oculta las URLs de la barra de estado inferior de forma 100% limpia en hover (evitando mostrar `javascript:void(0)` o cualquier ruta en reposo).
*   **Cero Pestañeo en Animaciones:** Remueve el `href` en tiempo de carga, lo que previene mutaciones del DOM al pasar el cursor. Esto asegura transiciones y animaciones (CSS/GSAP) 100% fluidas y libres de parpadeos.
*   **Acciones Nativas Preservadas:** Restaura de forma instantánea y síncrona el `href` real durante eventos de clic derecho (`contextmenu`), clic de rueda (`mousedown`), arrastre (`dragstart`) y teclado (`focus`). Esto mantiene el comportamiento nativo de copiar dirección, abrir en nueva pestaña, arrastrar y la accesibilidad de lectores de pantalla.

### 🔤 Ecosistema de Fuentes: Instalador, Gestor y Precarga Inteligente
*   **Instalador Interactivo (`composer install-font`):** Herramienta por consola para instalar fuentes (WOFF2, TTF, OTF).
    *   **Detección Automática Variable vs Estática:** Analiza la estructura binaria de la fuente (`fvar`). Si es variable, genera un `@font-face` único con rango amplio (`font-weight: 100 900`) y todas las clases utilitarias (`.fontName100` a `.fontName900`). Si es estática, genera reglas por peso individual.
    *   **Compresión Optimizada:** Convierte automáticamente fuentes TTF/OTF a formato web ultraligero WOFF2.
    *   **Generación de Clases CSS:** Registra las fuentes en `App/Public/Css/font-project.css`.
*   **Gestor y Reseteador (`composer reset-font`):**
    *   Muestra un listado interactivo numerado de todas las fuentes instaladas en el proyecto.
    *   Permite eliminar fuentes individuales o múltiples por índice (ej: `1, 3`), o resetear todas escribiendo `all`.
    *   Elimina los archivos físicos del disco (`App/Rsc/Fonts/...`), limpia las carpetas vacías y purga las definiciones en `font-project.css`.
    *   Ejecuta `min-script` automáticamente para recompilar el CSS y la configuración de precarga.
*   **Precarga Dinámica Inteligente (JIT):**
    *   Escanea el uso de fuentes en templates, scripts y CSS (`--font: 'FamilyName'`).
    *   Para fuentes variables, precarga únicamente el archivo único variable.
    *   Para fuentes estáticas, precarga todas las variantes activas de la familia.
    *   Si no se usan fuentes personalizadas, el sistema usa la tipografía sans-serif del navegador con cero peticiones de precarga.

### 🌓 Gestión del Tema y Prevención del FOUC
*   **Control Centralizado en .env:** Permite definir un tema inicial (`system` para tomar el del sistema, `dark` o `light`) mediante la variable `DEFAULT_THEME`.
*   **Carga Sincrónica Anti-FOUC:** Inyecta un script inline ultrarrápido en el `<head>` antes de renderizar el `<body>`, el cual aplica la preferencia guardada en `localStorage` o el `.env`, evitando cualquier parpadeo o flash de color no deseado (*Flash of Unstyled Content*).
*   **Frontend Dinámico:** El componente frontend `darkMode.js` escucha cambios de tema en vivo a nivel de sistema operativo sin requerir refrescar la pestaña.

### 📈 Motor de SEO Avanzado y Datos Estructurados
*   **Canonical Link Limpio:** Inyecta dinámicamente `<link rel="canonical">` basándose en la URI actual, limpiando de forma automática cualquier parámetro de consulta (query parameters) para prevenir penalizaciones por contenido duplicado.
*   **JSON-LD Person Schema Enriquecido:** Genera datos estructurados para perfiles profesionales autocompletando campos desde el `.env`.
*   **Consistencia Pixel-Perfect:** Normaliza la barra diagonal final (*trailing slash*) para garantizar consistencia milimétrica entre la etiqueta canónica y la URL del esquema JSON-LD.

## 🔜 Próximas Características

- 💳 Integración de Pasarelas de Pago
- 🛒 Módulo E-commerce
- 📦 Sistema de Productos
- 🔐 Mejoras en Autenticación
- 🔔 Sistema de Notificaciones

## 🚀 Comenzando

1. Clona el repositorio
2. Configura tu servidor web para PHP 7.4+
3. Configura la base de datos en `config.php`
4. Ejecuta los scripts de inicialización de BD
5. ¡Listo para desarrollar!

## 📝 Estado del Proyecto

El framework se encuentra actualmente en un **65% de desarrollo**, con funcionalidades base estables y operativas.

### ✅ Funcionalidades Completadas
- Sistema de rutas con middleware
- Query Builder (MySQL/PostgreSQL)
- Sistema de sesiones y autenticación
- Módulo de interacciones (likes, saves, favorites)
- Procesamiento de imágenes
- Minificación de JS/CSS
- Sistema de vistas modular
- Registro de visitas con geolocalización
- Enmascaramiento de enlaces avanzado (On-Demand Href Restoration) sin parpadeos
- Instalador interactivo de fuentes físicas y precargador inteligente de tipografías crìticas
- Inicialización de tema dinámico y control sincrónico anti-FOUC desde `.env`
- Inyección automatizada de URLs canónicas y JSON-LD de Persona (SEO Semántico)

### 🔄 En Desarrollo
- E-commerce
- Pasarelas de pago
- Sistema de notificaciones

## 💻 Requisitos

- PHP 7.4 o superior
- MySQL/MariaDB o PostgreSQL
- Servidor web (Apache/Nginx)
- Composer

## 📖 Documentación

La documentación detallada está disponible en la carpeta `.agent/workflows/` con guías específicas para:
- Sistema de rutas
- Módulo de interacciones
- Query Builder

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor, lee las guías de contribución antes de enviar pull requests.

## 📄 Licencia

Este proyecto está bajo desarrollo activo. Licencia pendiente de definir.

---
Desarrollado con ❤️ por Eber
