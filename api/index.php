
<?php

$path = parse_url(
    $_SERVER['REQUEST_URI'],
    PHP_URL_PATH
);

// Route the website checker to check.php.
if ($path === '/check.php') {
    require __DIR__ . '/../check.php';
    exit;
}

// Display the main website.
require __DIR__ . '/../index.php';
