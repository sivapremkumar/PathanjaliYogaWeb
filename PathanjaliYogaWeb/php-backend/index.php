<?php
// Root entrypoint for deployments where /api points to php-backend root.

// Load .env file if it exists (for local development)
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($name, $value) = explode('=', $line, 2);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap.php';

use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;
use Psr\Log\LogLevel;

$app = AppFactory::create();

// Custom error handler that returns JSON
$errorMiddleware = $app->addErrorMiddleware(false, true, true);
$errorMiddleware->setDefaultErrorHandler(function ($request, $exception, $displayErrorDetails) {
    $response = new \Slim\Psr7\Response();
    $statusCode = 500;
    
    if ($exception instanceof HttpNotFoundException) {
        $statusCode = 404;
        $error = 'Route not found';
    } else if ($exception instanceof \Slim\Exception\HttpMethodNotAllowedException) {
        $statusCode = 405;
        $error = 'Method not allowed';
    } else {
        $error = $displayErrorDetails ? $exception->getMessage() : 'Application error';
    }
    
    $response->getBody()->write(json_encode([
        'error' => $error,
        'status' => $statusCode
    ]));
    
    return $response
        ->withStatus($statusCode)
        ->withHeader('Content-Type', 'application/json');
});

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
    $response->getBody()->write('YogaTrust PHP Backend API is running. Version: 2.0');
    return $response;
});

$app->run();
