# Practical Use Cases

The following scenarios show how DBAL could be applied in different domains. These snippets are only examples—DBAL is a general‑purpose library that can work with any database engine.

## Online Book Store
This scenario simulates a small book-selling app built with SQLite. It shows how to create tables for authors and books, modify them and perform common operations.

### Creating tables
```php
$schema = new DBAL\SchemaMiddleware($pdo);
$crud   = (new DBAL\Crud($pdo))->withMiddleware($schema);

$crud->createTable('authors')
    ->column('id', 'INTEGER PRIMARY KEY AUTOINCREMENT')
    ->column('name', 'TEXT')
    ->execute();

$crud->createTable('books')
    ->column('id', 'INTEGER PRIMARY KEY AUTOINCREMENT')
    ->column('title', 'TEXT')
    ->column('author_id', 'INTEGER')
    ->execute();
```

### Modifying tables
```php
$crud->alterTable('books')
    ->addColumn('price', 'REAL')
    ->execute();
```

### Basic operations (CRUD)
```php
$books = (new DBAL\Crud($pdo))->from('books');

$bookId = $books->insert([
    'title'     => 'Dune',
    'author_id' => 1,
]);

$books->where(['id' => $bookId])->update(['title' => 'Dune (Revised)']);
$books->where(['id' => $bookId])->delete();
```

### Queries with the standard API
```php
$rows = $books
    ->where(['author_id' => 1])
    ->order('ASC', ['title'])
    ->select('id', 'title');
```

### Queries with the dynamic API
```php
use DBAL\QueryBuilder\FilterOp;
$rows = $books->where(function ($q) {
    $q->condition('title', FilterOp::LIKE, '%dune%')
      ->andNext()
      ->condition('price', FilterOp::LT, 20);
})->select('id', 'title');
```

### Manual joins
```php
$rows = $books
    ->leftJoin('authors a', function ($on) {
        $on->condition('books.author_id', FilterOp::EQF, 'a.id');
    })
    ->select('books.title', 'a.name AS author');
```

### Joins based on relations
```php
use DBAL\Attributes\{BelongsTo, Table};

#[Table('books')]
class Book {
    #[BelongsTo('authors', 'author_id', 'id')]
    public $author;
}

$validation = (new DBAL\EntityValidationMiddleware())
    ->register(Book::class);

$books = (new DBAL\Crud($pdo))
    ->from('books')
    ->withMiddleware($validation);

foreach ($books->with('author')->select() as $book) {
    echo $book['title'].' - '.$book['author']['name'];
}
```

### Validation rules
```php
$validation = (new DBAL\EntityValidationMiddleware())
    ->table('books')
        ->field('title')->required()->string()->maxLength(100)
        ->field('author_id')->required()->integer();
```

### Lazy and eager loading
```php
// eager
foreach ($books->with('author')->select() as $book) {
    echo $book['author']['name'];
}

// lazy
$book   = iterator_to_array($books->where(['id' => 1])->select())[0];
$author = $book['author'];
```

### Active Record
```php
$ar = (new DBAL\ActiveRecordMiddleware())->attach($books);

$record = iterator_to_array($ar->where(['id' => [FilterOp::EQ, 1]])->select())[0];
$record->title = 'New Title';
$record->update();
```

### Entity Classes and Bulk Insert
```php
class BookEntity {
    use DBAL\ActiveRecordTrait;
    public $id;
    public $title;
    public $author_id;
}

$caster = (new DBAL\EntityCastMiddleware())
    ->register('books', BookEntity::class);
$books = $caster->attach($books, 'books');

$a = new BookEntity();
$a->title = 'Book A';
$a->author_id = 1;
$b = new BookEntity();
$b->title = 'Book B';
$b->author_id = 2;
$books->bulkInsertObjects([$a, $b]);

$a->title = 'Updated';
$a->update();
```

## Cinema Ticketing
This example manages movie screenings and reservations. It demonstrates altering tables and loading related screening information.

### Creating tables
```php
$schema = new DBAL\SchemaMiddleware($pdo);
$crud   = (new DBAL\Crud($pdo))->withMiddleware($schema);

$crud->createTable('screenings')
    ->column('id', 'INTEGER PRIMARY KEY AUTOINCREMENT')
    ->column('movie', 'TEXT')
    ->column('starts_at', 'DATETIME')
    ->execute();

$crud->createTable('reservations')
    ->column('id', 'INTEGER PRIMARY KEY AUTOINCREMENT')
    ->column('screening_id', 'INTEGER')
    ->column('seat', 'TEXT')
    ->execute();
```

### Modifying tables
```php
$crud->alterTable('reservations')
    ->addColumn('price', 'REAL')
    ->execute();
```

### Basic operations (CRUD)
```php
$reservations = (new DBAL\Crud($pdo))->from('reservations');

$resId = $reservations->insert([
    'screening_id' => 1,
    'seat'         => 'A1',
]);

$reservations->where(['id' => $resId])->update(['seat' => 'A2']);
$reservations->where(['id' => $resId])->delete();
```

### Queries with the standard API
```php
$rows = $reservations
    ->where(['screening_id' => 1])
    ->select('id', 'seat');
```

### Queries with the dynamic API
```php
$rows = $reservations->where(function ($q) {
    $q->seat__like('A%')->andNext()->price__gt(10);
})->select('id', 'seat', 'price');
```

### Manual joins
```php
$rows = $reservations
    ->innerJoin('screenings s', function ($on) {
        $on->condition('reservations.screening_id', FilterOp::EQF, 's.id');
    })
    ->select('s.movie', 'reservations.seat');
```

### Joins based on relations
```php
use DBAL\Attributes\{BelongsTo, Table};

#[Table('reservations')]
class Reservation {
    #[BelongsTo('screenings', 'screening_id', 'id')]
    public $screening;
}

$validation = (new DBAL\EntityValidationMiddleware())
    ->register(Reservation::class);

$reservations = (new DBAL\Crud($pdo))
    ->from('reservations')
    ->withMiddleware($validation);

foreach ($reservations->with('screening')->select() as $res) {
    echo $res['seat'].' for '.$res['screening']['movie'];
}
```

### Validation rules
```php
$validation = (new DBAL\EntityValidationMiddleware())
    ->table('reservations')
        ->field('screening_id')->required()->integer()
        ->field('seat')->required()->string();
```

### Lazy and eager loading
```php
// eager
foreach ($reservations->with('screening')->select() as $res) {
    echo $res['screening']['movie'];
}

// lazy
$res       = iterator_to_array($reservations->where(['id' => 1])->select())[0];
$screening = $res['screening'];
```

### Active Record
```php
$ar  = (new DBAL\ActiveRecordMiddleware())->attach($reservations);
$rec = iterator_to_array($ar->where(['id' => [FilterOp::EQ, 1]])->select())[0];
$rec->seat = 'B1';
$rec->update();
```

## Logistics API in Microservices
Here we mimic a tiny parcel tracking service. The same queries would work with MySQL, PostgreSQL or any other database.

### Creating tables
```php
$schema = new DBAL\SchemaMiddleware($pdo);
$crud   = (new DBAL\Crud($pdo))->withMiddleware($schema);

$crud->createTable('warehouses')
    ->column('id', 'INTEGER PRIMARY KEY AUTOINCREMENT')
    ->column('name', 'TEXT')
    ->execute();

$crud->createTable('packages')
    ->column('id', 'INTEGER PRIMARY KEY AUTOINCREMENT')
    ->column('code', 'TEXT')
    ->column('warehouse_id', 'INTEGER')
    ->execute();
```

### Modifying tables
```php
$crud->alterTable('packages')
    ->addColumn('status', 'TEXT')
    ->execute();
```

### Basic operations (CRUD)
```php
$packages = (new DBAL\Crud($pdo))->from('packages');

$pkgId = $packages->insert([
    'code'         => 'PKG001',
    'warehouse_id' => 1,
]);

$packages->where(['id' => $pkgId])->update(['status' => 'dispatched']);
$packages->where(['id' => $pkgId])->delete();
```

### Queries with the standard API
```php
$rows = $packages
    ->where(['warehouse_id' => 1])
    ->select('id', 'code', 'status');
```

### Queries with the dynamic API
```php
$rows = $packages->where(function ($q) {
    $q->condition('status', FilterOp::EQ, 'in_transit')
      ->orNext()
      ->code__startWith('PKG');
})->select('id', 'code');
```

### Manual joins
```php
$rows = $packages
    ->join('warehouses w', function ($on) {
        $on->condition('packages.warehouse_id', FilterOp::EQF, 'w.id');
    })
    ->select('packages.code', 'w.name AS warehouse');
```

### Joins based on relations
```php
use DBAL\Attributes\{BelongsTo, Table};

#[Table('packages')]
class Package {
    #[BelongsTo('warehouses', 'warehouse_id', 'id')]
    public $warehouse;
}

$validation = (new DBAL\EntityValidationMiddleware())
    ->register(Package::class);

$packages = (new DBAL\Crud($pdo))
    ->from('packages')
    ->withMiddleware($validation);

foreach ($packages->with('warehouse')->select() as $pkg) {
    echo $pkg['code'].' from '.$pkg['warehouse']['name'];
}
```

### Validation rules
```php
$validation = (new DBAL\EntityValidationMiddleware())
    ->table('packages')
        ->field('code')->required()->string()->maxLength(40)
        ->field('warehouse_id')->required()->integer();
```

### Lazy and eager loading
```php
// eager
foreach ($packages->with('warehouse')->select() as $pkg) {
    echo $pkg['warehouse']['name'];
}

// lazy
$pkg       = iterator_to_array($packages->where(['id' => 1])->select())[0];
$warehouse = $pkg['warehouse'];
```

### Active Record
```php
$ar  = (new DBAL\ActiveRecordMiddleware())->attach($packages);
$rec = iterator_to_array($ar->where(['id' => [FilterOp::EQ, 1]])->select())[0];
$rec->status = 'delivered';
$rec->update();
```

Even though these scenarios use SQLite for brevity, the same code works with any relational database supported by PDO.
