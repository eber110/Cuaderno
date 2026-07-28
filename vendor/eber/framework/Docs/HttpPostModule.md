# HttpPostModule

Módulo para enviar datos POST programáticamente entre controladores sin necesidad de formularios HTML.

## Descripción

`HttpPostModule` permite enviar arrays de datos a rutas internas de la aplicación usando cURL. Los datos llegan al controlador destino a través de la variable `$_POST`, exactamente como si provinieran de un formulario HTML.

---

## Métodos Disponibles

| Método | Descripción | Retorno |
|--------|-------------|---------|
| `postData($data, $route, $options)` | Envío completo con respuesta detallada | `array` |
| `postDataJson($data, $route, $options)` | Envío con respuesta JSON decodificada | `array` |
| `enviar($data, $route)` | Envío simple | `bool` |
| `setBaseUrl($url)` | Establece URL base personalizada | `void` |

---

## Uso Básico

```php
use Base\module\HttpPostModule;

// Enviar datos a una ruta
$resultado = HttpPostModule::postData(
    ['nombre' => 'Juan', 'edad' => 25], 
    '/mi-ruta'
);

// Verificar éxito
if ($resultado['success']) {
    echo $resultado['response'];
}
```

---

## Ejemplos de Comunicación entre Controladores

### Ejemplo 1: PostController envía datos a CommentController

**En `postController.php`:**

```php
use Base\module\HttpPostModule;

class postController extends control
{
    public function procesarComentario()
    {
        // Datos del comentario
        $datosComentario = [
            'post_id' => 15,
            'user_id' => $_SESSION['user_id'],
            'contenido' => 'Este es mi comentario',
            'fecha' => date('Y-m-d H:i:s')
        ];

        // Enviar a commentController
        $resultado = HttpPostModule::postData($datosComentario, '/comentar/post');

        if ($resultado['success']) {
            // Comentario procesado correctamente
            ResponseModule::redirect('/publicacion/mi-post', 'Comentario agregado', 0);
        }
    }
}
```

**En `commentController.php` (recibe los datos):**

```php
class commentController extends control
{
    public function commentPostForm()
    {
        // Los datos llegan en $_POST
        $postId = $_POST['post_id'];
        $userId = $_POST['user_id'];
        $contenido = $_POST['contenido'];
        $fecha = $_POST['fecha'];

        // Procesar el comentario...
    }
}
```

---

### Ejemplo 2: UserController notifica a otro servicio

```php
use Base\module\HttpPostModule;

class userController extends control
{
    public function registrarUsuario()
    {
        // Después de crear el usuario, notificar al sistema de emails
        $datosNotificacion = [
            'email' => $_POST['email'],
            'nombre' => $_POST['nombre'],
            'tipo' => 'bienvenida'
        ];

        // Envío simple - solo nos interesa si funcionó
        if (HttpPostModule::enviar($datosNotificacion, '/enviar-email')) {
            echo "Notificación enviada";
        }
    }
}
```

---

### Ejemplo 3: Respuesta JSON entre controladores

**Controlador que envía:**

```php
use Base\module\HttpPostModule;

class apiController extends control
{
    public function obtenerEstadisticas()
    {
        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-12-31',
            'tipo' => 'visitas'
        ];

        // postDataJson decodifica automáticamente la respuesta JSON
        $resultado = HttpPostModule::postDataJson($filtros, '/api/estadisticas');

        if ($resultado['success']) {
            $estadisticas = $resultado['data'];
            // $estadisticas ya es un array PHP
            echo "Total visitas: " . $estadisticas['total'];
        }
    }
}
```

**Controlador que responde con JSON:**

```php
use Base\module\ResponseModule;

class estadisticasController extends control
{
    public function obtener()
    {
        $fechaInicio = $_POST['fecha_inicio'];
        $fechaFin = $_POST['fecha_fin'];

        // Procesar y obtener datos...
        $datos = [
            'total' => 1500,
            'promedio_diario' => 45
        ];

        // Responder con JSON
        ResponseModule::json($datos);
    }
}
```

---

## Estructura de Respuesta

### `postData()` retorna:

```php
[
    'success'  => bool,    // true si código HTTP 2xx y sin errores
    'response' => string,  // Contenido de la respuesta
    'httpCode' => int,     // Código HTTP (200, 404, 500, etc.)
    'error'    => ?string  // Mensaje de error de cURL o null
]
```

### `postDataJson()` retorna:

```php
[
    'success'  => bool,
    'response' => string,  // Respuesta original
    'data'     => mixed,   // Respuesta decodificada como array/objeto
    'httpCode' => int,
    'error'    => ?string
]
```

---

## Opciones Avanzadas

```php
// Con timeout personalizado y headers adicionales
$resultado = HttpPostModule::postData(
    ['dato' => 'valor'],
    '/mi-ruta',
    [
        'timeout' => 60,  // segundos
        'headers' => [
            'X-Custom-Header: valor',
            'Authorization: Bearer token123'
        ]
    ]
);
```

---

## Notas Importantes

> [!NOTE]
> El módulo construye automáticamente la URL completa usando `$_SERVER['HTTP_HOST']`.

> [!TIP]
> Usa `enviar()` cuando solo necesites verificar si la petición fue exitosa, sin procesar la respuesta.

> [!WARNING]
> Las peticiones son síncronas. Para peticiones largas, considera aumentar el timeout.
