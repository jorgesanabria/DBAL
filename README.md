# DBAL
Simple dbal for php

Example of dynamic filters:

```php
$crud->where(function ($q) {
    $q->name__startWith('Al')->age__ge(21);
});
```

