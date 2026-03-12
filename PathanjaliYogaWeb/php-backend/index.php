<?php
// Root entrypoint for deployments where /api points to php-backend root.

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

$routes = require __DIR__ . '/src/routes.php';
$routes($app);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('YogaTrust PHP Backend is running.');
    return $response;
});

$app->run();
