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

// Fallback mode: keep core APIs alive if Composer vendor is missing or inconsistent.
$autoloadPath = __DIR__ . '/vendor/autoload.php';
$autoloadRealPath = __DIR__ . '/vendor/composer/autoload_real.php';
$frameworkReady = false;
foreach ([
    __DIR__ . '/vendor/composer/ClassLoader.php',
    __DIR__ . '/vendor/composer/autoload_static.php',
    __DIR__ . '/vendor/ralouphie/getallheaders/src/getallheaders.php',
] as $requiredFile) {
    if (!file_exists($requiredFile)) {
        $frameworkReady = false;
        $autoloadPath = '';
        break;
    }
}
if ($autoloadPath !== '' && file_exists($autoloadPath) && file_exists($autoloadRealPath)) {
    $autoloadContents = @file_get_contents($autoloadPath);
    $autoloadRealContents = @file_get_contents($autoloadRealPath);
    if ($autoloadContents !== false && $autoloadRealContents !== false) {
        if (preg_match('/ComposerAutoloaderInit[0-9a-f]+/', $autoloadContents, $m) === 1) {
            $frameworkReady = strpos($autoloadRealContents, $m[0]) !== false;
        }
    }
}

if (!$frameworkReady) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');

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

    $connectDb = function () use ($sendJson): ?mysqli {
        $dbHost = getenv('DB_HOST') ?: 'localhost';
        $dbName = getenv('DB_DATABASE') ?: '';
        $dbUser = getenv('DB_USERNAME') ?: '';
        $dbPass = getenv('DB_PASSWORD') ?: '';

        $mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        if ($mysqli->connect_error) {
            $sendJson(500, ['error' => 'Database connection failed']);
            return null;
        }
        $mysqli->set_charset('utf8mb4');
        return $mysqli;
    };

    $readJson = function (): array {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '{}', true);
        return is_array($data) ? $data : [];
    };

    if ($method === 'GET' && ($path === '/api/trustees/seed' || $path === '/trustees/seed')) {
        $defaults = [
            ['name' => 'Jeyaram',     'role' => 'President', 'description' => '', 'image_url' => 'https://www.sripathanjalitrust.com/jeyaram.jpeg'],
            ['name' => 'Kasimani',    'role' => 'Trustee',   'description' => '', 'image_url' => 'https://www.sripathanjalitrust.com/kasimani.jpeg'],
            ['name' => 'Esakki',      'role' => 'Trustee',   'description' => '', 'image_url' => 'https://www.sripathanjalitrust.com/Esakki-Durai_01.jpeg'],
            ['name' => 'Venkatraman', 'role' => 'Trustee',   'description' => '', 'image_url' => 'https://www.sripathanjalitrust.com/Venkatraman.jpeg'],
            ['name' => 'Marimuthu',   'role' => 'Trustee',   'description' => '', 'image_url' => 'https://www.sripathanjalitrust.com/marimuthu.jpeg'],
            ['name' => 'Murugan',     'role' => 'Trustee',   'description' => '', 'image_url' => 'https://www.sripathanjalitrust.com/Murugan.jpeg'],
            ['name' => 'Murugesen',   'role' => 'Trustee',   'description' => '', 'image_url' => 'https://www.sripathanjalitrust.com/Murugesen.jpeg'],
        ];
        $mysqli = $connectDb();
        if (!$mysqli) { exit; }
        $inserted = 0;
        foreach ($defaults as $d) {
            $chk = $mysqli->prepare('SELECT id FROM trustees WHERE name = ? LIMIT 1');
            $chk->bind_param('s', $d['name']);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows === 0) {
                $ins = $mysqli->prepare('INSERT INTO trustees (name, role, description, image_url) VALUES (?, ?, ?, ?)');
                $ins->bind_param('ssss', $d['name'], $d['role'], $d['description'], $d['image_url']);
                $ins->execute();
                $ins->close();
                $inserted++;
            }
            $chk->close();
        }
        $mysqli->close();
        $sendJson(200, ['inserted' => $inserted, 'total' => count($defaults)]);
        exit;
    }

    if ($method === 'GET' && ($path === '/api/trustees' || $path === '/trustees')) {
        $mysqli = $connectDb();
        if (!$mysqli) {
            exit;
        }
        $rows = [];
        $result = $mysqli->query('SELECT id, name, role, description, image_url, created_at, updated_at FROM trustees ORDER BY id ASC');
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['imageUrl'] = $row['image_url'];
                $rows[] = $row;
            }
            $result->free();
        }
        $mysqli->close();
        $sendJson(200, $rows);
        exit;
    }

    if ($method === 'POST' && ($path === '/api/trustees/upload' || $path === '/trustees/upload')) {
        $file = $_FILES['image'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $sendJson(400, ['error' => 'No valid file uploaded']);
            exit;
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            $sendJson(400, ['error' => 'File exceeds 10 MB limit']);
            exit;
        }
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            $sendJson(400, ['error' => 'Unsupported file type']);
            exit;
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
        $uploadDir = __DIR__ . '/uploads/trustees/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0775, true); }
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $safeName)) {
            $sendJson(500, ['error' => 'Failed to save file']);
            exit;
        }
        $sendJson(200, ['url' => '/api/uploads/trustees/' . $safeName]);
        exit;
    }

    if ($method === 'POST' && ($path === '/api/trustees' || $path === '/trustees')) {
        $data = $readJson();
        $name = trim((string)($data['name'] ?? ''));
        $role = trim((string)($data['role'] ?? 'Trustee'));
        $description = (string)($data['description'] ?? '');
        $imageUrl = (string)($data['imageUrl'] ?? $data['image_url'] ?? '');
        if ($name === '') {
            $sendJson(400, ['error' => 'Name is required']);
            exit;
        }

        $mysqli = $connectDb();
        if (!$mysqli) {
            exit;
        }
        $stmt = $mysqli->prepare('INSERT INTO trustees (name, role, description, image_url) VALUES (?, ?, ?, ?)');
        if (!$stmt) {
            $mysqli->close();
            $sendJson(500, ['error' => 'Query preparation failed']);
            exit;
        }
        $stmt->bind_param('ssss', $name, $role, $description, $imageUrl);
        $ok = $stmt->execute();
        $newId = (int)$mysqli->insert_id;
        $stmt->close();
        if (!$ok) {
            $mysqli->close();
            $sendJson(500, ['error' => 'Failed to create trustee']);
            exit;
        }
        $rowResult = $mysqli->query('SELECT id, name, role, description, image_url, created_at, updated_at FROM trustees WHERE id = ' . $newId . ' LIMIT 1');
        $row = $rowResult ? $rowResult->fetch_assoc() : ['id' => $newId, 'name' => $name, 'role' => $role, 'description' => $description, 'image_url' => $imageUrl];
        if ($rowResult) { $rowResult->free(); }
        if ($row) { $row['imageUrl'] = $row['image_url']; }
        $mysqli->close();
        $sendJson(200, $row);
        exit;
    }

    if (in_array($method, ['PUT', 'PATCH']) && preg_match('#^/(api/)?trustees/(\d+)$#', $path, $m)) {
        $id = (int)$m[2];
        $data = $readJson();
        $mysqli = $connectDb();
        if (!$mysqli) { exit; }
        $chk = $mysqli->query('SELECT id FROM trustees WHERE id = ' . $id . ' LIMIT 1');
        if (!$chk || $chk->num_rows === 0) {
            if ($chk) { $chk->free(); }
            $mysqli->close();
            $sendJson(404, ['success' => false, 'error' => 'Not found']);
            exit;
        }
        $chk->free();
        $fields = [];
        $types = '';
        $vals = [];
        $imageUrl = $data['imageUrl'] ?? $data['image_url'] ?? null;
        if (isset($data['name']))        { $fields[] = 'name = ?';        $types .= 's'; $vals[] = $data['name']; }
        if (isset($data['role']))        { $fields[] = 'role = ?';        $types .= 's'; $vals[] = $data['role']; }
        if (isset($data['description'])) { $fields[] = 'description = ?'; $types .= 's'; $vals[] = $data['description']; }
        if ($imageUrl !== null)           { $fields[] = 'image_url = ?';   $types .= 's'; $vals[] = $imageUrl; }
        if (!empty($fields)) {
            $vals[] = $id;
            $types .= 'i';
            $stmt = $mysqli->prepare('UPDATE trustees SET ' . implode(', ', $fields) . ' WHERE id = ?');
            $stmt->bind_param($types, ...$vals);
            $stmt->execute();
            $stmt->close();
        }
        $rowResult = $mysqli->query('SELECT id, name, role, description, image_url, created_at, updated_at FROM trustees WHERE id = ' . $id . ' LIMIT 1');
        $row = $rowResult ? $rowResult->fetch_assoc() : ['id' => $id];
        if ($rowResult) { $rowResult->free(); }
        if ($row) { $row['imageUrl'] = $row['image_url']; }
        $mysqli->close();
        $sendJson(200, $row);
        exit;
    }

    if ($method === 'DELETE' && preg_match('#^/(api/)?trustees/(\d+)$#', $path, $m)) {
        $id = (int)$m[2];
        $mysqli = $connectDb();
        if (!$mysqli) {
            exit;
        }
        $stmt = $mysqli->prepare('DELETE FROM trustees WHERE id = ?');
        if (!$stmt) {
            $mysqli->close();
            $sendJson(500, ['error' => 'Query preparation failed']);
            exit;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        $mysqli->close();
        $sendJson(200, $affected > 0 ? ['success' => true] : ['success' => false, 'error' => 'Not found']);
        exit;
    }

    if ($method === 'GET' && ($path === '/api/news' || $path === '/news')) {
        $mysqli = $connectDb();
        if (!$mysqli) {
            exit;
        }
        $rows = [];
        $result = $mysqli->query('SELECT id, title, content, is_event, date, location, created_at, updated_at FROM news ORDER BY id DESC');
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
        }
        $mysqli->close();
        $sendJson(200, $rows);
        exit;
    }

    if ($method === 'POST' && ($path === '/api/news' || $path === '/news')) {
        $data = $readJson();
        $title = trim((string)($data['title'] ?? ''));
        $content = (string)($data['content'] ?? ($data['description'] ?? ''));
        $isEvent = !empty($data['is_event']) ? 1 : 0;
        $date = (string)($data['date'] ?? null);
        $location = (string)($data['location'] ?? ($data['imageUrl'] ?? ''));
        if ($title === '') {
            $sendJson(400, ['error' => 'Title is required']);
            exit;
        }

        $mysqli = $connectDb();
        if (!$mysqli) {
            exit;
        }
        $stmt = $mysqli->prepare('INSERT INTO news (title, content, is_event, date, location) VALUES (?, ?, ?, ?, ?)');
        if (!$stmt) {
            $mysqli->close();
            $sendJson(500, ['error' => 'Query preparation failed']);
            exit;
        }
        $stmt->bind_param('ssiss', $title, $content, $isEvent, $date, $location);
        $ok = $stmt->execute();
        $newId = (int)$mysqli->insert_id;
        $stmt->close();
        if (!$ok) {
            $mysqli->close();
            $sendJson(500, ['error' => 'Failed to create news item']);
            exit;
        }
        $rowResult = $mysqli->query('SELECT id, title, content, is_event, date, location, created_at, updated_at FROM news WHERE id = ' . $newId . ' LIMIT 1');
        $row = $rowResult ? $rowResult->fetch_assoc() : ['id' => $newId, 'title' => $title, 'content' => $content, 'is_event' => $isEvent, 'date' => $date, 'location' => $location];
        if ($rowResult) {
            $rowResult->free();
        }
        $mysqli->close();
        $sendJson(200, $row);
        exit;
    }

    if ($method === 'DELETE' && preg_match('#^/(api/)?news/(\d+)$#', $path, $m)) {
        $id = (int)$m[2];
        $mysqli = $connectDb();
        if (!$mysqli) {
            exit;
        }
        $stmt = $mysqli->prepare('DELETE FROM news WHERE id = ?');
        if (!$stmt) {
            $mysqli->close();
            $sendJson(500, ['error' => 'Query preparation failed']);
            exit;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        $mysqli->close();
        $sendJson(200, $affected > 0 ? ['success' => true] : ['success' => false, 'error' => 'Not found']);
        exit;
    }

    if ($method === 'GET' && ($path === '/api/inquiries' || $path === '/inquiries')) {
        $mysqli = $connectDb();
        if (!$mysqli) {
            exit;
        }
        $rows = [];
        $result = $mysqli->query('SELECT id, name, email, phone, message, is_resolved, created_at, updated_at FROM inquiries ORDER BY id DESC');
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
        }
        $mysqli->close();
        $sendJson(200, $rows);
        exit;
    }

    if ($method === 'POST' && ($path === '/api/inquiries' || $path === '/inquiries')) {
        $data = $readJson();
        $name = trim((string)($data['name'] ?? ''));
        $email = (string)($data['email'] ?? '');
        $phone = (string)($data['phone'] ?? '');
        $message = trim((string)($data['message'] ?? ''));
        if ($name === '' || $message === '') {
            $sendJson(400, ['error' => 'Name and message are required']);
            exit;
        }
        $mysqli = $connectDb();
        if (!$mysqli) {
            exit;
        }
        $stmt = $mysqli->prepare('INSERT INTO inquiries (name, email, phone, message, is_resolved) VALUES (?, ?, ?, ?, 0)');
        if (!$stmt) {
            $mysqli->close();
            $sendJson(500, ['error' => 'Query preparation failed']);
            exit;
        }
        $stmt->bind_param('ssss', $name, $email, $phone, $message);
        $ok = $stmt->execute();
        $newId = (int)$mysqli->insert_id;
        $stmt->close();
        $mysqli->close();
        if (!$ok) {
            $sendJson(500, ['error' => 'Failed to submit inquiry']);
            exit;
        }
        $sendJson(200, ['success' => true, 'id' => $newId]);
        exit;
    }

    if ($method === 'GET' && ($path === '/api/donations' || $path === '/donations')) {
        $mysqli = $connectDb();
        if (!$mysqli) {
            exit;
        }
        $rows = [];
        $result = $mysqli->query('SELECT id, donor_name, email, phone, amount, pan_number, address, payment_status, transaction_id, receipt_path, created_at, updated_at FROM donations ORDER BY id DESC');
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
        }
        $mysqli->close();
        $sendJson(200, $rows);
        exit;
    }

    if ($method === 'GET' && ($path === '/api/admin/stats' || $path === '/admin/stats')) {
        $mysqli = $connectDb();
        if (!$mysqli) {
            exit;
        }
        $stats = [
            'totalTrustees' => 0,
            'totalDonations' => 0,
            'donationCount' => 0,
            'totalInquiries' => 0,
            'totalNews' => 0,
        ];
        $q1 = $mysqli->query('SELECT COUNT(*) AS c FROM trustees');
        if ($q1) { $stats['totalTrustees'] = (int)($q1->fetch_assoc()['c'] ?? 0); $q1->free(); }
        $q2 = $mysqli->query("SELECT COALESCE(SUM(amount),0) AS s FROM donations WHERE payment_status = 'Completed'");
        if ($q2) { $stats['totalDonations'] = (float)($q2->fetch_assoc()['s'] ?? 0); $q2->free(); }
        $q3 = $mysqli->query('SELECT COUNT(*) AS c FROM donations');
        if ($q3) { $stats['donationCount'] = (int)($q3->fetch_assoc()['c'] ?? 0); $q3->free(); }
        $q4 = $mysqli->query('SELECT COUNT(*) AS c FROM inquiries');
        if ($q4) { $stats['totalInquiries'] = (int)($q4->fetch_assoc()['c'] ?? 0); $q4->free(); }
        $q5 = $mysqli->query('SELECT COUNT(*) AS c FROM news');
        if ($q5) { $stats['totalNews'] = (int)($q5->fetch_assoc()['c'] ?? 0); $q5->free(); }
        $mysqli->close();
        $sendJson(200, $stats);
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
