<?php
spl_autoload_register(function ($class) {
    $prefix = 'DBAL\\';
    $base_dir = __DIR__ . '/../DBAL/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
