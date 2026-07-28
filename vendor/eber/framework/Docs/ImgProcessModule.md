# Documentación del Módulo ImgProcessModule

Este documento detalla el funcionamiento del `ImgProcessModule`, encargado del procesamiento, subida, optimización y eliminación de imágenes en la aplicación.

## 1. Configuración Inicial (Constructor)

Para utilizar el módulo, se debe instanciar la clase `ImgProcessModule`.

```php
$imgModule = new ImgProcessModule($table, $uploadImg);
```

### Parámetros del Constructor

*   **`$table`** (string): El nombre de la tabla en la base de datos donde se registrará la información de la imagen (por ejemplo, `"media"`).
*   **`$uploadImg`** (string, opcional): La ruta del directorio donde se guardarán las imágenes. Si no se especifica, usa la constante `DIR_UPLOAD_MEDIA`.

---

## 2. Métodos Principales

### A. Referencia: `record_img_disk` (Crear/Subir Imagen)

Este método procesa las imágenes enviadas a través de `$_FILES`, las optimiza, las guarda en el disco y registra la información en la base de datos.

**Parámetros:**

1.  **`$name_img_bd`** (string): El nombre de la columna en tu base de datos que almacenará el nombre del archivo de la imagen (ej. `"original_filename"`).
2.  **`$col_bd`** (array): Un array asociativo con los datos adicionales que se deben insertar en la tabla de imágenes.
    *   Clave: Nombre de la columna.
    *   Valor: Valor a insertar via bind param.
3.  **`$maxKb`** (int|null, opcional): Límite de tamaño en KB para la compresión. Si es `null`, usa la configuración por defecto.

**Retorno:**

*   **`array`**: Retorna un array con los IDs de las filas insertadas en la base de datos si la operación fue exitosa.
*   **`false`**: Si ocurrió un error en la validación, subida o inserción.

### B. Referencia: `delete_img_disk` (Borrar Imagen)

Elimina los archivos de imagen del disco. **Nota:** Este método no elimina el registro de la base de datos, eso debe hacerse manualmente con el modelo correspondiente.

**Parámetros:**

1.  **`$route_img`** (string): La ruta base donde se encuentran las imágenes (ej. `DIR_UPLOAD_MEDIA` o la ruta que pasaste al constructor).
2.  **`$image`** (string|array): El nombre del archivo (string) o una lista de nombres de archivos (array) a eliminar.

**Retorno:**

*   **`true`**: Si se ejecutó el proceso de eliminación (incluso si no encontró algunos archivos, devuelve true si intentó).
*   **`false`**: Si el parámetro `$image` estaba vacío o nulo.

---

## 3. Ejemplos de Uso

A continuación se presentan ejemplos para las operaciones CRUD básicas.

### Ejemplo 1: Crear una Publicación (Subir Imagen)

Este ejemplo muestra cómo instanciar el módulo y guardar una imagen asociada a un nuevo post.

```php
use Base\Module\ImgProcessModule;
use App\Models\PostModels;

public function createPost(array $dataPostCreate)
{
  // 1. Instanciar el módulo apuntando a la tabla 'media'
  $imgRecord = new ImgProcessModule("media");
  
  $postModel = new PostModels();

  // ... lógica para crear el post ...
  $slug = $postModel->create($dataPostCreate, "index_post");
  $postId = $postModel->last_id(); // Obtenemos el ID del post recién creado

  if ($postId) {
    // 2. Guardar la imagen en disco y BD
    // 'original_filename' es la columna donde irá el nombre del archivo generado
    $imgRegister = $imgRecord->record_img_disk(
      "original_filename", 
      [
        // Columnas adicionales de la tabla 'media'
        "uploader_user_id" => $dataPostCreate["author_user_id"],
        "referenced_post_id" => (int)$postId,
        "referenced_table" => "blogpost",
        "server_file_path" => $slug
      ]
    );

    // 3. Verificar si falló la subida de imagen
    if (!$imgRegister) {
      // Si falla la imagen, hacemos rollback del post para no dejar datos huérfanos
      $postModel->rollback(); 
      return false;
    }
  }

  return $slug;
}
```

### Ejemplo 2: Modificar una Publicación (Actualizar Imagen)

Al editar, si el usuario sube una nueva imagen, debemos borrar la anterior del disco y subir la nueva.

```php
public function updatePost($postId, array $dataPostUpdate)
{
  $imgRecord = new ImgProcessModule("media");
  $postModel = new PostModels();

  // 1. Verificar si se subió una nueva imagen en el formulario
  // ImgProcessModule detecta automáticamente $_FILES, pero podemos verificar antes
  if (!empty($_FILES['image']['name'][0])) {
    
    // a. Obtener la información de la imagen actual desde la BD
    // Suponiendo que tienes un método para obtener la imagen por post_id
    $currentImage = $this->getCurrentImageNameByPostId($postId); 
    
    if ($currentImage) {
      // b. Borrar la imagen anterior del disco
      $imgRecord->delete_img_disk(DIR_UPLOAD_MEDIA, $currentImage);
      
      // c. Podrías borrar el registro de la tabla 'media' aquí si es necesario
      // $mediaModel->delete(...)
    }

    // d. Subir la nueva imagen
    $imgRegister = $imgRecord->record_img_disk(
      "original_filename",
      [
        "uploader_user_id" => $dataPostUpdate["author_user_id"],
        "referenced_post_id" => (int)$postId,
        "referenced_table" => "blogpost",
        "server_file_path" => $dataPostUpdate['post_slug']
      ]
    );
     
    if (!$imgRegister) {
       return false; // Error al subir la nueva imagen
    }
  }

  // ... proceder con la actualización de los datos textuales del post ...
  $postModel->update($dataPostUpdate, "post_id", $postId);
  return true;
}
```

### Ejemplo 3: Borrar una Publicación (Eliminar Imagen)

Al borrar un post, debemos asegurarnos de limpiar la imagen asociada del disco.

```php
public function deletePost($postId)
{
  $imgRecord = new ImgProcessModule("media");
  $postModel = new PostModels();

  // 1. Obtener datos del post o la imagen para saber el nombre del archivo
  // Supongamos que esta consulta trae el campo 'original_filename' de la tabla media unida
  $postData = $postModel->getPostWithImage($postId); 

  if ($postData && !empty($postData['original_filename'])) {
    
    // 2. Eliminar el archivo físico del disco
    // Se pasa la ruta base y el nombre del archivo
    $imgRecord->delete_img_disk(DIR_UPLOAD_MEDIA, $postData['original_filename']);
    
    // Nota: Si tienes configurado ON DELETE CASCADE en tu base de datos,
    // al borrar el post se borrará el registro en la tabla 'media'.
    // Si no, debes borrarlo manualmente:
    // $mediaModel->delete("referenced_post_id", $postId);
  }

  // 3. Eliminar el post
  $postModel->delete("post_id", $postId);
  
  return true;
}
```
