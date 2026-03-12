<?php
// Root entrypoint for deployments where /api points to php-backend root.

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

// Return clean HTTP errors instead of uncaught fatal stack traces.
$app->addErrorMiddleware(false, true, true);

// Basic CORS headers for frontend API requests.
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
});

$routes = require __DIR__ . '/src/routes.php';
$routes($app);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('YogaTrust PHP Backend is running.');
    return $response;
});

$app->run();
