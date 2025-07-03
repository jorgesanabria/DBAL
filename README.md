# DBAL
Simple dbal for php

## Instalación

Instala la biblioteca vía [Composer](https://getcomposer.org/):

```bash
composer require jorgesanabria/dbal
```

## Uso básico

```php
$pdo = new \PDO('mysql:host=localhost;dbname=test', 'user', 'pass');
$crud = (new DBAL\Crud($pdo))->from('usuarios');
```

### Insertar

```php
$id = $crud->insert([
    'nombre' => 'Juan',
    'correo' => 'juan@example.com'
]);
```

### Consultar con `select` y `where`

```php
$resultados = $crud
    ->select('id', 'nombre')
    ->where(['id__gt' => 10]);

foreach ($resultados as $fila) {
    echo $fila['nombre'];
}
```

### Actualizar y eliminar

```php
$crud->where(['id' => $id])->update(['nombre' => 'Pedro']);

$crud->where(['id' => $id])->delete();
```

### Joins

```php
$resultado = $crud
    ->from('usuarios u')
    ->leftJoin('perfiles p', function ($on) {
        $on->{'u.id__eqf'}('p.usuario_id');
    })
    ->where(['p.activo__eq' => 1])
    ->select('u.id', 'p.foto');
```

### Filtros dinámicos

```php
$crud->where(function ($q) {
    $q->nombre__startWith('Al')->edad__ge(21);
});
```

### Extender filtros

```php
use DBAL\QueryBuilder\Node\FilterNode;

FilterNode::filter('startWith', function ($campo, $valor, $msg) {
    return $msg->insertAfter(sprintf('%s LIKE ?', $campo))
               ->addValues([$valor . '%']);
});

$crud->where(['nombre__startWith' => 'Al']);
```

### Mappers

```php
$crudConMapper = $crud->map(function (array $fila) {
    return (object) $fila;
});

foreach ($crudConMapper->select() as $fila) {
    echo $fila->nombre;
}
```

