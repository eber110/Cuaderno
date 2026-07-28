# SecurityModule - Documentación

Módulo de seguridad centralizado para el framework.

## Uso Rápido

### Sanitización de Inputs

```php
use Base\module\SecurityModule;

// Sanitizar string individual
$name = SecurityModule::sanitize($_POST['name']);

// Obtener valor sanitizado de POST/GET
$email = SecurityModule::post('email');
$page = SecurityModule::get('page', 1);

// Sanitizar array completo
$data = SecurityModule::sanitizeArray($_POST);
```

### Protección CSRF

```php
// En el formulario HTML
echo SecurityModule::csrfField();
// <input type="hidden" name="_token" value="...">

// Para AJAX, agregar meta tag en <head>
echo SecurityModule::csrfMeta();

// Validar CSRF en el controlador
if (!SecurityModule::verifyCsrf()) {
    die('Token CSRF inválido');
}

// O lanzar excepción
SecurityModule::verifyCsrf(true); // throws RuntimeException
```

### Validación de Tablas SQL

```php
// Verificar si tabla está permitida
if (SecurityModule::isAllowedTable('users')) {
    // OK
}

// Validar y obtener nombre normalizado
$table = SecurityModule::validateTableName('Users'); // retorna 'users'
```

### Configuración de Errores

```php
// Al inicio de la aplicación (en bootstrap)
SecurityModule::configureErrorHandling();
// En producción: oculta errores
// En desarrollo: muestra todos los errores
```

## Constantes Relacionadas (config.php)

| Constante | Descripción |
|-----------|-------------|
| `ENVIRONMENT` | 'development' o 'production' |
| `CSRF_PROTECTION` | Activa/desactiva CSRF |
| `ALLOWED_TABLES` | Array de tablas SQL permitidas |
