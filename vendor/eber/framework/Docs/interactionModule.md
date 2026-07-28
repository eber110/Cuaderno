# InteractionModule

Módulo para gestionar interacciones de usuarios (likes, favoritos, guardados, shares) de manera unificada.

## Características

- **Tabla unificada**: Una sola tabla para todos los tipos de interacción
- **Soporte dual**: Usuarios autenticados y visitantes anónimos
- **Toggle automático**: Añade/elimina con una sola llamada
- **Prevención de duplicados**: UNIQUE KEYs en la base de datos
- **Conteo y resumen**: Métodos optimizados para estadísticas

---

## Instalación

### 1. Crear la tabla en la base de datos

Ejecuta el script de inicialización correspondiente:

```php
// Para MySQL
require_once 'base/scriptComposer/configInitBdMySql.php';
configInitBd();

// Para PostgreSQL
require_once 'base/scriptComposer/configInitBdPostgreSql.php';
configInitBdPostgreSql();
```

### 2. Estructura de la tabla

```sql
CREATE TABLE `Interactions` (
  `id_interaction` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `guest_identifier` VARCHAR(255) NULL,
  `interaction_type` ENUM('like', 'favorite', 'save', 'share') NOT NULL,
  `created_at_interaction` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_interaction`),
  UNIQUE KEY `uq_user_interaction` (`post_id`, `user_id`, `interaction_type`),
  UNIQUE KEY `uq_guest_interaction` (`post_id`, `guest_identifier`, `interaction_type`)
);
```

---

## Uso Básico

```php
use Base\module\interactionModule;

$interaction = new interactionModule();
```

### Toggle (Añadir/Eliminar)

El método `toggle` añade la interacción si no existe, o la elimina si ya existe:

```php
// Toggle like
$result = $interaction->toggleLike($postId, $userId);
// Retorna: ['action' => 'added', 'success' => true]
// O:       ['action' => 'removed', 'success' => true]

// Toggle favorito
$result = $interaction->toggleFavorite($postId, $userId);

// Toggle guardado
$result = $interaction->toggleSave($postId, $userId);
```

### Añadir Interacciones

```php
$interaction->addLike($postId, $userId);
$interaction->addFavorite($postId, $userId);
$interaction->addSave($postId, $userId);
$interaction->addShare($postId, $userId);
```

### Verificar Interacciones

```php
if ($interaction->hasLiked($postId, $userId)) {
    echo "El usuario ya dio like";
}

$interaction->hasFavorited($postId, $userId);
$interaction->hasSaved($postId, $userId);
```

### Contar Interacciones

```php
$likeCount = $interaction->countLikes($postId);
$favoriteCount = $interaction->countFavorites($postId);
$saveCount = $interaction->countSaves($postId);
$shareCount = $interaction->countShares($postId);
```

---

## Resumen de Post

Obtén todas las estadísticas de interacción de un post en una sola llamada:

```php
$summary = $interaction->getPostSummary($postId, $userId);

// Retorna:
[
    'likes' => 125,
    'favorites' => 42,
    'saves' => 18,
    'shares' => 7,
    'user_has_liked' => true,
    'user_has_favorited' => false,
    'user_has_saved' => true
]
```

---

## Visitantes Anónimos

El módulo soporta visitantes sin cuenta usando un identificador basado en IP:

```php
// El sistema genera automáticamente el identificador
$interaction->toggleLike($postId, null);

// O puedes proporcionar uno personalizado
$guestId = md5($_SERVER['REMOTE_ADDR']);
$interaction->toggleLike($postId, null, $guestId);
```

---

## Interacciones del Usuario

```php
// Obtener posts que el usuario ha dado like
$likedPosts = $interaction->getUserLiked($userId, 20, 0);

// Obtener favoritos del usuario
$favorites = $interaction->getUserFavorites($userId, 20, 0);

// Obtener guardados del usuario
$saved = $interaction->getUserSaved($userId, 20, 0);
```

---

## Posts Populares

```php
// Obtener los 10 posts con más likes
$popular = $interaction->getMostPopular('like', 10);

// Retorna:
[
    ['post_id' => 5, 'total' => 150],
    ['post_id' => 12, 'total' => 98],
    ...
]
```

---

## Ejemplo de Implementación con AJAX

### Controller (PHP)

```php
// app/controllers/interactionController.php
namespace App\controllers;

use Base\control\Control;
use Base\module\interactionModule;

class interactionController extends control
{
    public function toggle()
    {
        header('Content-Type: application/json');
        
        $postId = (int)$_POST['post_id'];
        $type = $_POST['type'] ?? 'like';
        $userId = $_SESSION['user_id'] ?? null;
        
        $interaction = new interactionModule();
        $result = $interaction->toggle($postId, $userId, $type);
        
        echo json_encode([
            'success' => $result['success'],
            'action' => $result['action'],
            'count' => $interaction->countInteractions($postId, $type)
        ]);
    }
}
```

### Route (PHP)

```php
// route/route.php
Route::post('/interaction/toggle', 'interactionController@toggle');
```

### JavaScript (Frontend)

```javascript
async function toggleInteraction(postId, type = 'like') {
    const response = await fetch('/interaction/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `post_id=${postId}&type=${type}`
    });
    
    const data = await response.json();
    
    if (data.success) {
        // Actualizar UI
        const button = document.querySelector(`[data-post="${postId}"][data-type="${type}"]`);
        button.classList.toggle('active', data.action === 'added');
        button.querySelector('.count').textContent = data.count;
    }
    
    return data;
}
```

### HTML

```html
<button 
    class="like-btn" 
    data-post="123" 
    data-type="like"
    onclick="toggleInteraction(123, 'like')"
>
    <span class="icon">❤️</span>
    <span class="count">45</span>
</button>
```

---

## Tipos de Interacción Disponibles

| Constante | Valor | Uso típico |
|-----------|-------|------------|
| `TYPE_LIKE` | `'like'` | Me gusta / corazón |
| `TYPE_FAVORITE` | `'favorite'` | Añadir a favoritos |
| `TYPE_SAVE` | `'save'` | Guardar para después |
| `TYPE_SHARE` | `'share'` | Registrar compartido |

---

## Métodos Disponibles

### Acciones

| Método | Descripción |
|--------|-------------|
| `add($postId, $userId, $type)` | Añade una interacción |
| `remove($postId, $userId, $type)` | Elimina una interacción |
| `toggle($postId, $userId, $type)` | Toggle (añade/elimina) |
| `toggleLike($postId, $userId)` | Toggle like |
| `toggleFavorite($postId, $userId)` | Toggle favorito |
| `toggleSave($postId, $userId)` | Toggle guardado |

### Verificación

| Método | Descripción |
|--------|-------------|
| `hasInteraction($postId, $userId, $type)` | Verifica si existe |
| `hasLiked($postId, $userId)` | Verifica like |
| `hasFavorited($postId, $userId)` | Verifica favorito |
| `hasSaved($postId, $userId)` | Verifica guardado |

### Conteo

| Método | Descripción |
|--------|-------------|
| `countInteractions($postId, $type)` | Cuenta por tipo |
| `countLikes($postId)` | Cuenta likes |
| `countFavorites($postId)` | Cuenta favoritos |
| `countSaves($postId)` | Cuenta guardados |
| `countShares($postId)` | Cuenta shares |

### Consultas

| Método | Descripción |
|--------|-------------|
| `getPostSummary($postId, $userId)` | Resumen completo de un post |
| `getUserInteractions($userId, $type)` | Interacciones de un usuario |
| `getUserLiked($userId)` | Posts con like del usuario |
| `getUserFavorites($userId)` | Favoritos del usuario |
| `getUserSaved($userId)` | Guardados del usuario |
| `getMostPopular($type, $limit)` | Posts más populares por tipo |

### Limpieza

| Método | Descripción |
|--------|-------------|
| `removeAllForPost($postId)` | Elimina todas las interacciones de un post |
| `removeAllForUser($userId)` | Elimina todas las interacciones de un usuario |
