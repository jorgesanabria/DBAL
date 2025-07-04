<?php
spl_autoload_register(function ($class) {
    $prefixes = [
        'DBAL\\'   => __DIR__ . '/../DBAL/',
        'Psr\\Log\\' => __DIR__ . '/Psr/Log/',
    ];

    foreach ($prefixes as $prefix => $base_dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $relative = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});
