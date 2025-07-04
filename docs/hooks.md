# Hook Helpers

The `DBAL\Hooks` namespace provides utility functions that wrap common
middlewares. They allow quickly configuring a `Crud` instance without
manually instantiating each middleware.

## Example

```php
use function DBAL\Hooks\{useCrud, useCache, useTransaction, useUnitOfWork};

$pdo  = new PDO('sqlite::memory:');
$crud = useCrud($pdo, 'items');
$crud = useCache($crud);
[$crud, $tx] = useTransaction($crud);
[$crud, $uow] = useUnitOfWork($crud);

$crud->registerNew('items', ['name' => 'A']);
$crud->commit();
```

Refer to the function signatures in the source for the full list of
available helpers.

