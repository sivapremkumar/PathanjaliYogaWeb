<?php
// public/index.php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap.php';

use Slim\Factory\AppFactory;


$app = AppFactory::create();


// Load routes
$routes = require __DIR__ . '/../src/routes.php';
$routes($app);

// Example route
$app->get('/', function ($request, $response, $args) {
    $response->getBody()->write("YogaTrust PHP Backend is running.");
    return $response;
});

$app->run();
