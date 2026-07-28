# 🧩 Guía de Componentes Autocontenidos

Los **Componentes Autocontenidos** son vistas reutilizables que cargan sus propios datos directamente desde el modelo, sin depender del controlador actual. Esto permite usar un mismo componente en cualquier vista de cualquier controlador.

---

## 📋 Índice

1. [¿Por qué usar componentes?](#por-qué-usar-componentes)
2. [Estructura de archivos](#estructura-de-archivos)
3. [Cómo funciona](#cómo-funciona)
4. [Crear un componente paso a paso](#crear-un-componente-paso-a-paso)
5. [Uso en vistas](#uso-en-vistas)
6. [Ejemplos prácticos](#ejemplos-prácticos)
7. [Referencia de la API](#referencia-de-la-api)

---

## ¿Por qué usar componentes?

### El problema

Cuando usas `_part()` o `_form()`, las vistas dependen de los datos que el controlador pasa a `ViewData`:

```php
// ❌ PROBLEMA: Esta vista necesita $parentCategory del controlador
// Si la usas desde otro controlador, $parentCategory no existe

// En categoryController:
$this->view("admin.pageCategory", [
    'parentCategory' => $this->model->getParentCategories()
]);

// En la vista:
<?php foreach ($parentCategory as $cat): ?>  <!-- ¡ERROR si no viene del controlador! -->
```

### La solución

Los componentes cargan sus propios datos:

```php
// ✅ SOLUCIÓN: El componente carga sus datos internamente
// Funciona en CUALQUIER vista, de CUALQUIER controlador

<?= _component('admin.CategoryForm'); ?>
```

---

## Estructura de archivos

```
📁 App/
├── 📁 Components/              ← Clases de componentes (sin herencia)
│   └── 📁 Admin/
│       └── 📁 Form/
│           ├── 📄 CreateCategoryComponent.php
│           └── 📄 CreatePostComponent.php
│
├── 📁 segment/
│   └── 📁 form/                ← Vistas de formularios
│       └── 📁 admin/
│           └── 📁 category/
│               └── 📄 createCategory.php
│
└── 📁 views/                   ← Vistas parciales
    └── 📁 admin/
        └── 📁 category/
            └── 📄 categoryList.php

📁 Base/
└── 📁 Helpers/
    └── 📄 Part.php             ← Toda la lógica de _component() y vistas
```

---

## Cómo funciona

```
┌─────────────────────────────────────────────────────────────────┐
│                         FLUJO DEL COMPONENTE                     │
└─────────────────────────────────────────────────────────────────┘

    Vista (cualquier controlador)
              │
              ▼
    _component('admin.CategoryForm')
              │
              ▼
    ┌─────────────────────────────────┐
    │  CategoryFormComponent::render()│
    │  ─────────────────────────────  │
    │  1. Llama a data() para obtener │
    │     datos del MODELO            │
    │  2. Carga la VISTA del form     │
    │  3. Pasa los datos a la vista   │
    │  4. Retorna el HTML renderizado │
    └─────────────────────────────────┘
              │
              ▼
    HTML del formulario con datos
```

---

## Crear un componente paso a paso

### Paso 1: Crear la clase del componente

Ubicación: `App/Components/[Modulo]/[Nombre]Component.php`

```php
<?php
// Archivo: App/Components/Admin/Form/ProductFormComponent.php

namespace App\Components\Admin\Form;

use App\Models\Admin\ProductAdminModels;
use App\Models\Admin\CategoryAdminModels;

// Sin herencia: Part.php se encarga de toda la lógica
class ProductFormComponent
{

  // Ruta de la vista (notación de punto)
  public static string $view = 'Admin.Product.CreateProduct';

  // Tipo de vista: 'form', 'part', 'menu', 'template'
  public static string $viewType = 'form';

  /**
   * Método que carga los datos necesarios.
   * Llamado automáticamente por _component().
   * 
   * @param array $params Parámetros opcionales pasados al componente
   * @return array Datos que estarán disponibles en la vista
   */
  public static function data(array $params = []): array
  {

    $productModel = new ProductAdminModels();
    $categoryModel = new CategoryAdminModels();

    return [
      'categories' => $categoryModel->listCategory(),
      'brands' => $productModel->getBrands(),
      'action' => $params['action'] ?? '/admin/producto/crear',
      'submitText' => $params['submitText'] ?? 'Crear Producto',
      'showAdvanced' => $params['showAdvanced'] ?? false,
    ];

  }

}
```

### Paso 2: Crear la vista del componente

Ubicación según `$viewType`:
- `form` → `App/segment/form/admin/product/createProduct.php`
- `part` → `App/views/admin/product/createProduct.php`

```php
<!-- Archivo: App/segment/form/admin/product/createProduct.php -->

<form action="<?= $action ?>" method="post" class="product-form">
    <div class="form-group">
        <label>Nombre del producto</label>
        <input type="text" name="product_name" required class="br10">
    </div>
    
    <div class="form-group">
        <label>Categoría</label>
        <select name="category_id" class="br10">
            <option value="">Seleccionar...</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['category_id'] ?>">
                    <?= $cat['category_name'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="form-group">
        <label>Marca</label>
        <select name="brand_id" class="br10">
            <?php foreach ($brands as $brand): ?>
                <option value="<?= $brand['brand_id'] ?>">
                    <?= $brand['brand_name'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <?php if ($showAdvanced): ?>
    <div class="advanced-options">
        <label>
            <input type="checkbox" name="featured"> Producto destacado
        </label>
    </div>
    <?php endif; ?>
    
    <button type="submit" class="btn color2 br10">
        <?= $submitText ?>
    </button>
</form>
```

### Paso 3: Usar el componente

En **cualquier** vista de **cualquier** controlador:

```php
<!-- Uso básico -->
<?= _component('admin.ProductForm'); ?>

<!-- Con parámetros personalizados -->
<?= _component('admin.ProductForm', [
    'action' => '/admin/producto/editar/5',
    'submitText' => 'Actualizar Producto',
    'showAdvanced' => true
]); ?>
```

---

## Uso en vistas

### Sintaxis básica

```php
<?= _component('modulo.NombreComponente'); ?>
```

El nombre se convierte automáticamente:
- `'admin.CategoryForm'` → `App\components\admin\CategoryFormComponent`
- `'blog.PostCard'` → `App\components\blog\PostCardComponent`

### Con parámetros

```php
<?= _component('admin.CategoryForm', [
    'action' => '/custom/url',
    'showTitle' => true,
    'submitText' => 'Guardar Cambios'
]); ?>
```

Los parámetros:
1. Se pasan al método `data($params)`
2. Se combinan con los datos del componente
3. Los parámetros tienen **prioridad** sobre los datos por defecto

### Dentro de Tabs

```php
<div class="tabs">
    <div class="tabs-nav">
        <button class="tab-btn active" data-tab="tab-form">Formulario</button>
        <button class="tab-btn" data-tab="tab-list">Listado</button>
    </div>
    
    <div class="tab-content active" id="tab-form">
        <?= _component('admin.CategoryForm'); ?>
    </div>
    
    <div class="tab-content hidden" id="tab-list">
        <?= _component('admin.CategoryList'); ?>
    </div>
</div>
```

### Dentro de Accordion

```php
<div class="accordion animated">
    <div class="accordion-item">
        <div class="accordion-header">Crear Categoría</div>
        <div class="accordion-content hidden">
            <?= _component('admin.CategoryForm'); ?>
        </div>
    </div>
    
    <div class="accordion-item">
        <div class="accordion-header">Ver Categorías</div>
        <div class="accordion-content hidden">
            <?= _component('admin.CategoryList', ['compact' => true]); ?>
        </div>
    </div>
</div>
```

---

## Ejemplos prácticos

### Ejemplo 1: Componente de Estadísticas

```php
<?php
// Archivo: App/components/admin/StatsCardComponent.php

namespace App\components\admin;

use Base\components\BaseComponent;
use App\models\admin\statsModels;

class StatsCardComponent extends BaseComponent
{
    protected static string $view = 'admin.dashboard/statsCard';
    protected static string $viewType = 'part';

    protected static function data(array $params = []): array
    {
        $stats = new statsModels();
        
        return [
            'totalUsers' => $stats->countUsers(),
            'totalSales' => $stats->getTotalSales(),
            'pendingOrders' => $stats->getPendingOrders(),
            'period' => $params['period'] ?? 'month',
        ];
    }
}
```

**Uso:**
```php
<?= _component('admin.StatsCard'); ?>
<?= _component('admin.StatsCard', ['period' => 'week']); ?>
```

---

### Ejemplo 2: Componente de Lista con Paginación

```php
<?php
// Archivo: App/components/blog/PostListComponent.php

namespace App\components\blog;

use Base\components\BaseComponent;
use App\models\postModels;

class PostListComponent extends BaseComponent
{
    protected static string $view = 'blog.posts/postList';
    protected static string $viewType = 'part';

    protected static function data(array $params = []): array
    {
        $postModel = new postModels();
        
        $page = $params['page'] ?? 1;
        $perPage = $params['perPage'] ?? 10;
        
        return [
            'posts' => $postModel->getPaginated($page, $perPage),
            'currentPage' => $page,
            'totalPages' => $postModel->getTotalPages($perPage),
            'showExcerpt' => $params['showExcerpt'] ?? true,
        ];
    }
}
```

**Uso:**
```php
<?= _component('blog.PostList', ['page' => 2, 'perPage' => 5]); ?>
```

---

### Ejemplo 3: Componente de Menú Dinámico

```php
<?php
// Archivo: App/components/navigation/SidebarMenuComponent.php

namespace App\components\navigation;

use Base\components\BaseComponent;
use App\models\menuModels;

class SidebarMenuComponent extends BaseComponent
{
    protected static string $view = 'navigation.sidebar/sidebarMenu';
    protected static string $viewType = 'menu';

    protected static function data(array $params = []): array
    {
        $menuModel = new menuModels();
        
        return [
            'menuItems' => $menuModel->getMenuByPosition('sidebar'),
            'activeItem' => $params['active'] ?? null,
            'collapsed' => $params['collapsed'] ?? false,
        ];
    }
}
```

**Uso:**
```php
<?= _component('navigation.SidebarMenu', ['active' => 'dashboard']); ?>
```

---

## Referencia de la API

### Función `_component()`

```php
/**
 * Renderiza un componente autocontenido.
 * 
 * @param string $component Nombre del componente
 * @param array $params Parámetros opcionales
 * @return string HTML renderizado
 */
function _component(string $component, array $params = []): string
```

### Clase del Componente (sin herencia)

| Propiedad/Método | Tipo | Descripción |
|------------------|------|-------------|
| `$view` | `public static string` | Ruta de la vista (notación de punto) |
| `$viewType` | `public static string` | Tipo: 'part', 'form', 'menu', 'template' |
| `data($params)` | `public static function` | Retorna los datos para la vista |

> **Nota:** Toda la lógica de renderizado vive en `_componentToString()` de `Part.php`.
> Los componentes son clases simples que solo definen datos y configuración.

### Resolución de rutas

| `$viewType` | `$view` | Archivo buscado |
|-------------|---------|-----------------|
| `'form'` | `'admin.category/create'` | `App/segment/form/admin/category/create.php` |
| `'part'` | `'admin.category/list'` | `App/views/admin/category/list.php` |
| `'menu'` | `'primary.main'` | `App/segment/menu/primary/main.php` |
| `'template'` | `'footer.main'` | `App/segment/template/footer/main.php` |

---

## Comparativa: `_part()` vs `_component()`

| Característica | `_part()` / `_form()` | `_component()` |
|----------------|----------------------|----------------|
| Depende de ViewData | ✅ Sí | ❌ No |
| Carga sus propios datos | ❌ No | ✅ Sí |
| Reutilizable entre controladores | ⚠️ Limitado | ✅ Totalmente |
| Requiere clase adicional | ❌ No | ✅ Sí |
| Acepta parámetros tipados | ❌ No | ✅ Sí |
| Testeable unitariamente | ⚠️ Difícil | ✅ Fácil |

### ¿Cuándo usar cada uno?

- **Usa `_part()`**: Para vistas simples que solo muestran datos del controlador actual
- **Usa `_component()`**: Para vistas reutilizables que necesitan cargar sus propios datos

---

## Tips y Buenas Prácticas

1. **Nombra los componentes descriptivamente**: `CategoryFormComponent`, no `FormComponent`

2. **Un componente = una responsabilidad**: No mezcles formularios de creación con listados

3. **Usa parámetros para customización**: Permite sobrescribir comportamiento sin crear componentes nuevos

4. **Documenta los parámetros**: Indica en el PHPDoc qué parámetros acepta

5. **Mantén las vistas simples**: La lógica pesada va en el método `data()`, no en la vista

---

## Solución de Problemas

### "Component not found"

Verifica que:
1. El nombre del componente tenga formato `modulo.Nombre`
2. La clase exista en `App/components/modulo/NombreComponent.php`
3. El namespace sea correcto: `App\components\modulo`
4. La clase termine en `Component`

### "Component view not found"

Verifica que:
1. La propiedad `$view` tenga la ruta correcta
2. La propiedad `$viewType` sea la correcta ('part', 'form', etc.)
3. El archivo de vista exista en la ubicación esperada

### Los datos no llegan a la vista

Verifica que:
1. El método `data()` retorne un array con las claves esperadas
2. Los nombres de las variables en la vista coincidan con las claves del array
