# Documentación del Query Builder

Guía completa de todos los métodos disponibles en `Base\builder\Builder.php`.

---

## Índice

1. [Inicialización](#inicialización)
2. [Métodos SELECT](#métodos-select)
3. [Condiciones WHERE](#condiciones-where)
4. [JOINs](#joins)
5. [Ordenamiento y Límites](#ordenamiento-y-límites)
6. [Agregaciones](#agregaciones)
7. [CRUD (Create, Read, Update, Delete)](#crud)
8. [Transacciones](#transacciones)
9. [Utilidades](#utilidades)
10. [Autenticación](#autenticación)

---

## Inicialización

### Constructor
```php
$builder = new Builder('nombre_tabla');
```
Crea una instancia del builder asociada a una tabla específica.

### `reset()`
Reinicia el estado del builder para reutilizarlo.
```php
$builder->where('id', 1)->get_all();
$builder->reset()->where('status', 'active')->get_all(); // Nueva consulta limpia
```

---

## Métodos SELECT

### `select(...$columnas)`
Especifica las columnas a recuperar.
```php
// Seleccionar columnas específicas
$builder->select('id', 'name', 'email')->get_all();
// SQL: SELECT id, name, email FROM tabla
```

### `non_select(...$columnas)`
Excluye columnas de la selección.
```php
// Seleccionar todas las columnas EXCEPTO password y token
$builder->non_select('password', 'token')->get_all();
```

### `selectRaw($expresion)`
Agrega expresiones SQL crudas al SELECT.
```php
$builder->selectRaw('YEAR(created_at) as year')->get_all();
// SQL: SELECT YEAR(created_at) as year FROM tabla
```

### `distinct()`
Selecciona solo valores únicos.
```php
$builder->select('category')->distinct()->get_all();
// SQL: SELECT DISTINCT category FROM tabla
```

### `get_all()`
Ejecuta la consulta y devuelve todos los registros.
```php
$users = $builder->select('id', 'name')->get_all();
// Retorna: [['id' => 1, 'name' => 'Juan'], ['id' => 2, 'name' => 'María'], ...]
// Retorna: false si no hay registros
```

### `get_one()`
Ejecuta la consulta y devuelve un único registro.
```php
$user = $builder->where('id', 1)->get_one();
// Retorna: ['id' => 1, 'name' => 'Juan']
// Retorna: false si no hay registro
```

### `first()`
Obtiene el primer registro de la consulta.
```php
$user = $builder->where('active', 1)->first();
// Retorna: ['id' => 1, 'name' => 'Juan']
// Retorna: false si no hay resultados
```

### `find($columna, $valor)`
Busca registros donde la columna coincide con el valor.
```php
$users = $builder->find('email', 'juan@email.com');
// SQL: SELECT * FROM tabla WHERE email = 'juan@email.com'
// Retorna: array con registros o false si no hay resultados
```

### `exists()`
Verifica si existen registros que coincidan.
```php
if ($builder->where('email', 'juan@email.com')->exists()) {
    echo "El email ya existe";
}
// Retorna: true o false
```

### `pluck($columna)`
Extrae los valores de una columna como un array plano.
```php
$emails = $builder->pluck('email');
// Retorna: ['juan@email.com', 'maria@email.com', 'pedro@email.com']
// Retorna: false si no hay registros
```

---

## Condiciones WHERE

### `where($columna, $operador, $valor)`
Agrega una condición WHERE.
```php
// Igualdad simple
$builder->where('status', 'active')->get_all();
// SQL: WHERE status = 'active'

// Con operador
$builder->where('age', '>', 18)->get_all();
// SQL: WHERE age > 18

// Operadores disponibles: =, !=, <, >, <=, >=, LIKE
$builder->where('name', 'LIKE', '%Juan%')->get_all();
// SQL: WHERE name LIKE '%Juan%'
```

### `orWhere($columna, $operador, $valor)`
Agrega una condición WHERE con OR.
```php
$builder->where('role', 'admin')
        ->orWhere('role', 'superadmin')
        ->get_all();
// SQL: WHERE role = 'admin' OR role = 'superadmin'
```

### `whereNull($columna)`
Busca registros donde la columna es NULL.
```php
$builder->whereNull('deleted_at')->get_all();
// SQL: WHERE deleted_at IS NULL
```

### `whereNotNull($columna)`
Busca registros donde la columna NO es NULL.
```php
$builder->whereNotNull('verified_at')->get_all();
// SQL: WHERE verified_at IS NOT NULL
```

### `whereIn($columna, $valores)`
Busca registros donde la columna está en la lista.
```php
$builder->whereIn('status', ['active', 'pending', 'review'])->get_all();
// SQL: WHERE status IN ('active', 'pending', 'review')
```

### `whereNotIn($columna, $valores)`
Busca registros donde la columna NO está en la lista.
```php
$builder->whereNotIn('id', [1, 2, 3])->get_all();
// SQL: WHERE id NOT IN (1, 2, 3)
```

### `whereBetween($columna, [$min, $max])`
Busca registros donde el valor está entre dos límites.
```php
$builder->whereBetween('price', [100, 500])->get_all();
// SQL: WHERE price BETWEEN 100 AND 500
```

### `whereNotBetween($columna, [$min, $max])`
Busca registros donde el valor NO está entre dos límites.
```php
$builder->whereNotBetween('age', [0, 17])->get_all();
// SQL: WHERE age NOT BETWEEN 0 AND 17
```

### `whereRaw($sql, $bindings)`
Agrega condiciones con SQL crudo.
```php
$builder->whereRaw('YEAR(created_at) = ? AND MONTH(created_at) = ?', [2024, 12])->get_all();
// SQL: WHERE YEAR(created_at) = 2024 AND MONTH(created_at) = 12
```

### `orWhereRaw($sql, $bindings)`
Igual que whereRaw pero con OR.
```php
$builder->where('active', 1)
        ->orWhereRaw('created_at > NOW() - INTERVAL 7 DAY')
        ->get_all();
```

### Agrupación de condiciones
Usa closures para agrupar condiciones.
```php
$builder->where('status', 'active')
        ->where(function($query) {
            $query->where('role', 'admin')
                  ->orWhere('role', 'moderator');
        })
        ->get_all();
// SQL: WHERE status = 'active' AND (role = 'admin' OR role = 'moderator')
```

---

## JOINs

### `join($tabla, $columna1, $columna2)` (LEFT JOIN)
```php
$builder = new Builder('posts');
$builder->join('users', 'user_id', 'id')
        ->select('posts.*', 'users.name as author')
        ->get_all();
// SQL: SELECT posts.*, users.name as author FROM posts 
//      LEFT JOIN users ON posts.user_id = users.id
```

### `innerJoin($tabla, $columna1, $columna2)`
Solo retorna registros con coincidencias en ambas tablas.
```php
$builder->innerJoin('categories', 'category_id', 'id')->get_all();
// SQL: INNER JOIN categories ON posts.category_id = categories.id
```

### `rightJoin($tabla, $columna1, $columna2)`
Retorna todos los registros de la tabla derecha.
```php
$builder->rightJoin('comments', 'id', 'post_id')->get_all();
// SQL: RIGHT JOIN comments ON posts.id = comments.post_id
```

### `full_join($tabla, $columna1, $columna2)`
Retorna todos los registros de ambas tablas.
```php
$builder->full_join('tags', 'id', 'post_id')->get_all();
// SQL: FULL JOIN tags ON posts.id = tags.post_id
```

---

## Ordenamiento y Límites

### `order($columna, $direccion)`
Ordena los resultados.
```php
// Orden descendente
$builder->order('created_at', 'DESC')->get_all();

// Múltiples órdenes
$builder->order('category', 'ASC')
        ->order('name', 'ASC')
        ->get_all();
// SQL: ORDER BY category ASC, name ASC
```

### `limit($cantidad)` o `limit($offset, $cantidad)`
Limita el número de resultados.
```php
// Primeros 10 registros
$builder->limit(10)->get_all();

// 10 registros saltando los primeros 20 (paginación)
$builder->limit(20, 10)->get_all();
// SQL (MySQL): LIMIT 20, 10
// SQL (PostgreSQL): LIMIT 10 OFFSET 20
```

### `group($columna)`
Agrupa los resultados.
```php
$builder->select('category')
        ->count('id', 'total')
        ->group('category')
        ->get_all();
// SQL: SELECT category, COUNT(id) as total FROM tabla GROUP BY category
```

### `having($columna, $operador, $valor)`
Filtra grupos después del GROUP BY.
```php
$builder->group('category')
        ->count('id', 'total')
        ->having('total', '>', 5)
        ->get_all();
// SQL: GROUP BY category HAVING COUNT(id) > 5
```

### `pag($registros_por_pagina)`
Sistema de paginación completo.
```php
$result = $builder->order('created_at', 'DESC')->pag(10);

// Retorna:
// [
//   'total_post' => 150,           // Total de registros
//   'post_per_page' => 10,         // Registros por página
//   'cant_pag' => 15,              // Total de páginas
//   'current_pag' => 1,            // Página actual
//   'prev_pag' => null,            // URL página anterior
//   'next_pag' => '/ruta?pag=2',   // URL página siguiente
//   'register' => [...]            // Los registros
// ]
```

---

## Agregaciones

### `count($columna, $alias)`
Cuenta registros.
```php
$builder->count('id', 'total')->get_one();
// Retorna: [['total' => 150]]
```

### `count_reg()`
Añade el conteo total para paginación.
```php
$builder->count_reg()->limit(10)->get_all();
// Cada registro incluye 'total_reg_in_table'
```

### `sum($columna, $alias)`
Suma valores de una columna.
```php
$builder->sum('price', 'total_ventas')->get_one();
// SQL: SELECT SUM(price) AS total_ventas
```

### `avg($columna, $alias)`
Calcula el promedio.
```php
$builder->avg('rating', 'rating_promedio')->get_one();
// SQL: SELECT AVG(rating) AS rating_promedio
```

### `min($columna, $alias)`
Obtiene el valor mínimo.
```php
$builder->min('price', 'precio_minimo')->get_one();
// SQL: SELECT MIN(price) AS precio_minimo
```

### `max($columna, $alias)`
Obtiene el valor máximo.
```php
$builder->max('price', 'precio_maximo')->get_one();
// SQL: SELECT MAX(price) AS precio_maximo
```

### `group_concat($columna, $separador, $alias)`
Concatena valores de un grupo.
```php
$builder->group('category_id')
        ->group_concat('name', ', ', 'productos')
        ->get_all();
// MySQL: GROUP_CONCAT(name SEPARATOR ', ') AS productos
// PostgreSQL: STRING_AGG(name::text, ', ') AS productos
```

---

## CRUD

### `create($datos, $encoder = null)`
Inserta un nuevo registro.
```php
$id = $builder->create([
    'name' => 'Juan Pérez',
    'email' => 'juan@email.com',
    'status' => 'active'
]);
// Retorna el ID del registro insertado

// Con encoder (genera ID único hexadecimal)
$id = $builder->create($datos, 'uuid_column');
```

### `update($columna, $valor, $datos, $encoder = null)`
Actualiza un registro existente.
```php
$builder->update('id', 5, [
    'name' => 'Juan Actualizado',
    'status' => 'inactive'
]);
// SQL: UPDATE tabla SET name = ?, status = ? WHERE id = 5
```

### `delete($columna, $valor)`
Elimina registros.
```php
// Eliminar un registro
$builder->delete('id', 5);

// Eliminar múltiples registros
$builder->delete('id', [1, 2, 3]);
```

### `increment($columna, $cantidad = 1)`
Incrementa el valor de una columna.
```php
$builder->where('id', 1)->increment('views');
// SQL: UPDATE tabla SET views = views + 1 WHERE id = 1

$builder->where('id', 1)->increment('balance', 100);
// SQL: UPDATE tabla SET balance = balance + 100 WHERE id = 1
```

### `decrement($columna, $cantidad = 1)`
Decrementa el valor de una columna.
```php
$builder->where('id', 1)->decrement('stock', 5);
// SQL: UPDATE tabla SET stock = stock - 5 WHERE id = 1
```

### `truncate()`
Vacía la tabla completamente. **¡CUIDADO!**
```php
$builder->truncate();
// SQL: TRUNCATE TABLE nombre_tabla
```

---

## Transacciones

### `beginTransaction()`, `commit()`, `rollback()`
Control manual de transacciones.
```php
$builder->beginTransaction();

try {
    $builder->create(['name' => 'Producto A']);
    $builder->where('id', 1)->decrement('stock');
    $builder->commit();
} catch (Exception $e) {
    $builder->rollback();
    throw $e;
}
```

### `transaction($callback)`
Ejecuta un callback dentro de una transacción (recomendado).
```php
$result = $builder->transaction(function($db) {
    $id = $db->create(['name' => 'Nuevo producto']);
    $db->where('category_id', 1)->increment('product_count');
    return $id;
});
// Si hay error, hace rollback automáticamente
```

---

## Utilidades

### `table_exist($tabla)`
Verifica si una tabla existe.
```php
if ($builder->table_exist('users')) {
    echo "La tabla users existe";
}
```

### `toSql()`
Obtiene el SQL sin ejecutarlo (para debugging).
```php
$debug = $builder
    ->select('id', 'name')
    ->where('active', 1)
    ->order('name', 'ASC')
    ->toSql();

// Retorna:
// [
//   'sql' => 'SELECT id, name FROM tabla WHERE active = ? ORDER BY name ASC',
//   'bindings' => [1]
// ]
```

### `query_foreign($sql, $bindings)`
Ejecuta SQL crudo.
```php
$builder->query_foreign('UPDATE tabla SET visits = visits + 1 WHERE id = ?', [5]);
```

### `last_id()`
Obtiene el último ID insertado.
```php
$builder->create(['name' => 'Nuevo']);
$id = $builder->last_id();
```

---

## Autenticación

### `register($datos, $password_column, $encoder = null)`
Registra un usuario con contraseña encriptada.
```php
$id = $builder->register([
    'name' => 'Juan',
    'email' => 'juan@email.com',
    'password' => 'mi_password123'
], 'password');
// La contraseña se encripta automáticamente con password_hash()
```

### `register_update($columna, $valor, $datos, $password_column, $encoder = null)`
Actualiza un usuario re-encriptando la contraseña.
```php
$builder->register_update('id', 5, [
    'name' => 'Juan Actualizado',
    'password' => 'nuevo_password456'
], 'password');
```

### `login($password_column, $password, $valor_busqueda, $columnas_busqueda, $excluir_session)`
Valida credenciales y crea sesión.
```php
$result = $builder->login(
    'password',           // Columna de contraseña
    'mi_password123',     // Contraseña ingresada
    'juan@email.com',     // Valor a buscar
    ['email', 'username'], // Columnas donde buscar
    ['password', 'token']  // Columnas a excluir de la sesión
);

// Retorna:
// [true, 'encrypted' => true]   - Éxito, clave hasheada
// [true, 'encrypted' => false]  - Éxito, clave texto plano
// [false, 0]                    - Usuario no existe
// [false, 1]                    - Contraseña incorrecta
```

---

## Ejemplo Completo

```php
use Base\builder\Builder;

// Consulta compleja
$builder = new Builder('products');

$products = $builder
    ->select('products.id', 'products.name', 'products.price', 'categories.name as category')
    ->join('categories', 'category_id', 'id')
    ->where('products.active', 1)
    ->whereNotNull('products.stock')
    ->whereBetween('products.price', [50, 500])
    ->whereIn('categories.id', [1, 2, 3])
    ->order('products.price', 'DESC')
    ->limit(20)
    ->get_all();

// Transacción para venta
$builder->reset();
$builder->transaction(function($db) use ($productId, $quantity) {
    // Reducir stock
    $db->where('id', $productId)->decrement('stock', $quantity);
    
    // Registrar venta
    $db->reset();
    $db->table = 'sales';
    $db->create([
        'product_id' => $productId,
        'quantity' => $quantity,
        'date' => date('Y-m-d H:i:s')
    ]);
});
```
