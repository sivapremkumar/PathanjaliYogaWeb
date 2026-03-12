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

// Fallback mode: keep login endpoint alive even if vendor dependencies are missing.
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    $sendJson = function (int $status, array $data): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    };

    if ($method === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    if ($method === 'GET' && ($path === '/api/auth/login' || $path === '/auth/login')) {
        $sendJson(200, ['message' => 'Use POST /api/auth/login']);
        exit;
    }

    if ($method === 'POST' && ($path === '/api/auth/login' || $path === '/auth/login')) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '{}', true);
        if (!is_array($data)) {
            $data = [];
        }

        $username = trim((string)($data['username'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($username === '' || $password === '') {
            $sendJson(400, ['error' => 'Username and password required']);
            exit;
        }

        $dbHost = getenv('DB_HOST') ?: 'localhost';
        $dbName = getenv('DB_DATABASE') ?: '';
        $dbUser = getenv('DB_USERNAME') ?: '';
        $dbPass = getenv('DB_PASSWORD') ?: '';

        $mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        if ($mysqli->connect_error) {
            $sendJson(500, ['error' => 'Database connection failed']);
            exit;
        }

        $stmt = $mysqli->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1');
        if (!$stmt) {
            $mysqli->close();
            $sendJson(500, ['error' => 'Query preparation failed']);
            exit;
        }

        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        $mysqli->close();

        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            $sendJson(401, ['error' => 'Invalid credentials']);
            exit;
        }

        $token = base64_encode(random_bytes(24));
        $sendJson(200, ['token' => $token, 'username' => $user['username']]);
        exit;
    }

    $sendJson(503, ['error' => 'Backend dependencies not installed']);
    exit;
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
