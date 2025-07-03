# DBAL
Simple dbal for php

## Installation

Install via [Composer](https://getcomposer.org/):

```bash
composer require jorgesanabria/dbal
```

Example of dynamic filters:

```php
$crud->where(function ($q) {
    $q->name__startWith('Al')->age__ge(21);
});
```

