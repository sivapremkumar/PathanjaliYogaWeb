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
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

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

    $ensureGalleryTable = function (mysqli $mysqli): void {
        $mysqli->query("CREATE TABLE IF NOT EXISTS gallery_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_url VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    };

    $isTrusteeUploadUrl = function (?string $imageUrl): bool {
        if (!$imageUrl) {
            return false;
        }
        $pathPart = parse_url($imageUrl, PHP_URL_PATH);
        if (!$pathPart) {
            $pathPart = $imageUrl;
        }
        return strpos($pathPart, '/api/uploads/trustees/') === 0;
    };

    $cleanupTrusteeUploadIfUnused = function (mysqli $mysqli, ?string $imageUrl, ?int $excludeId = null) use ($isTrusteeUploadUrl): string {
        if (!$isTrusteeUploadUrl($imageUrl)) {
            return 'not_applicable';
        }

        $countSql = 'SELECT COUNT(*) AS c FROM trustees WHERE image_url = ?';
        if ($excludeId !== null) {
            $countSql .= ' AND id <> ?';
        }
        $countStmt = $mysqli->prepare($countSql);
        if (!$countStmt) {
            return 'cleanup_failed';
        }
        if ($excludeId !== null) {
            $countStmt->bind_param('si', $imageUrl, $excludeId);
        } else {
            $countStmt->bind_param('s', $imageUrl);
        }
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult ? $countResult->fetch_assoc() : null;
        if ($countResult) {
            $countResult->free();
        }
        $countStmt->close();
        $referenceCount = (int)($countRow['c'] ?? 0);
        if ($referenceCount > 0) {
            return 'kept_referenced';
        }

        $pathPart = parse_url($imageUrl, PHP_URL_PATH);
        if (!$pathPart) {
            $pathPart = $imageUrl;
        }
        if (strpos($pathPart, '..') !== false) {
            return 'skipped';
        }
        $fileName = basename($pathPart);
        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            return 'skipped';
        }
        $fullPath = __DIR__ . '/uploads/trustees/' . $fileName;
        if (!file_exists($fullPath)) {
            return 'file_missing';
        }
        return @unlink($fullPath) ? 'deleted' : 'cleanup_failed';
    };

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
        $chk = $mysqli->query('SELECT id, image_url FROM trustees WHERE id = ' . $id . ' LIMIT 1');
        if (!$chk || $chk->num_rows === 0) {
            if ($chk) { $chk->free(); }
            $mysqli->close();
            $sendJson(404, ['success' => false, 'error' => 'Not found']);
            exit;
        }
        $existing = $chk->fetch_assoc();
        $oldImageUrl = (string)($existing['image_url'] ?? '');
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
        $newImageUrl = (string)($row['image_url'] ?? '');
        $cleanup = 'not_applicable';
        if ($oldImageUrl !== '' && $oldImageUrl !== $newImageUrl) {
            $cleanup = $cleanupTrusteeUploadIfUnused($mysqli, $oldImageUrl, $id);
        }
        if ($row) { $row['imageUrl'] = $row['image_url']; }
        if ($row) { $row['imageCleanup'] = $cleanup; }
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
        $imageUrl = '';
        $pre = $mysqli->query('SELECT image_url FROM trustees WHERE id = ' . $id . ' LIMIT 1');
        if ($pre && $pre->num_rows > 0) {
            $preRow = $pre->fetch_assoc();
            $imageUrl = (string)($preRow['image_url'] ?? '');
        }
        if ($pre) {
            $pre->free();
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
        $cleanup = 'not_applicable';
        if ($affected > 0 && $imageUrl !== '') {
            $cleanup = $cleanupTrusteeUploadIfUnused($mysqli, $imageUrl, null);
        }
        $mysqli->close();
        $sendJson(200, $affected > 0 ? ['success' => true, 'imageCleanup' => $cleanup] : ['success' => false, 'error' => 'Not found']);
        exit;
    }

    $isNewsUploadUrl = function (?string $imageUrl): bool {
        if (!$imageUrl) {
            return false;
        }
        $pathPart = parse_url($imageUrl, PHP_URL_PATH);
        if (!$pathPart) {
            $pathPart = $imageUrl;
        }
        return strpos($pathPart, '/api/uploads/news_event_clips/') === 0;
    };

    $cleanupNewsUploadIfUnused = function (mysqli $mysqli, ?string $imageUrl, ?int $excludeId = null) use ($isNewsUploadUrl): string {
        if (!$isNewsUploadUrl($imageUrl)) {
            return 'not_applicable';
        }

        $countSql = 'SELECT COUNT(*) AS c FROM news WHERE location = ?';
        if ($excludeId !== null) {
            $countSql .= ' AND id <> ?';
        }
        $countStmt = $mysqli->prepare($countSql);
        if (!$countStmt) {
            return 'cleanup_failed';
        }
        if ($excludeId !== null) {
            $countStmt->bind_param('si', $imageUrl, $excludeId);
        } else {
            $countStmt->bind_param('s', $imageUrl);
        }
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult ? $countResult->fetch_assoc() : null;
        if ($countResult) {
            $countResult->free();
        }
        $countStmt->close();
        $referenceCount = (int)($countRow['c'] ?? 0);
        if ($referenceCount > 0) {
            return 'kept_referenced';
        }

        $pathPart = parse_url($imageUrl, PHP_URL_PATH);
        if (!$pathPart) {
            $pathPart = $imageUrl;
        }
        if (strpos($pathPart, '..') !== false) {
            return 'skipped';
        }
        $fileName = basename($pathPart);
        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            return 'skipped';
        }
        $fullPath = __DIR__ . '/uploads/news_event_clips/' . $fileName;
        if (!file_exists($fullPath)) {
            return 'file_missing';
        }
        return @unlink($fullPath) ? 'deleted' : 'cleanup_failed';
    };

    $isGalleryUploadUrl = function (?string $imageUrl): bool {
        if (!$imageUrl) {
            return false;
        }
        $pathPart = parse_url($imageUrl, PHP_URL_PATH);
        if (!$pathPart) {
            $pathPart = $imageUrl;
        }
        return strpos($pathPart, '/api/uploads/gallery/') === 0;
    };

    $cleanupGalleryUploadIfUnused = function (mysqli $mysqli, ?string $imageUrl, ?int $excludeId = null) use ($isGalleryUploadUrl): string {
        if (!$isGalleryUploadUrl($imageUrl)) {
            return 'not_applicable';
        }

        $countSql = 'SELECT COUNT(*) AS c FROM gallery_items WHERE image_url = ?';
        if ($excludeId !== null) {
            $countSql .= ' AND id <> ?';
        }
        $countStmt = $mysqli->prepare($countSql);
        if (!$countStmt) {
            return 'cleanup_failed';
        }
        if ($excludeId !== null) {
            $countStmt->bind_param('si', $imageUrl, $excludeId);
        } else {
            $countStmt->bind_param('s', $imageUrl);
        }
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult ? $countResult->fetch_assoc() : null;
        if ($countResult) {
            $countResult->free();
        }
        $countStmt->close();
        $referenceCount = (int)($countRow['c'] ?? 0);
        if ($referenceCount > 0) {
            return 'kept_referenced';
        }

        $pathPart = parse_url($imageUrl, PHP_URL_PATH);
        if (!$pathPart) {
            $pathPart = $imageUrl;
        }
        if (strpos($pathPart, '..') !== false) {
            return 'skipped';
        }
        $fileName = basename($pathPart);
        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            return 'skipped';
        }
        $fullPath = __DIR__ . '/uploads/gallery/' . $fileName;
        if (!file_exists($fullPath)) {
            return 'file_missing';
        }
        return @unlink($fullPath) ? 'deleted' : 'cleanup_failed';
    };

    if ($method === 'POST' && ($path === '/api/news/upload' || $path === '/news/upload')) {
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
        $uploadDir = __DIR__ . '/uploads/news_event_clips/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0775, true); }
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $safeName)) {
            $sendJson(500, ['error' => 'Failed to save file']);
            exit;
        }
        $sendJson(200, ['url' => '/api/uploads/news_event_clips/' . $safeName]);
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
                $location = (string)($row['location'] ?? '');
                $imageUrl = '';
                if ($isNewsUploadUrl($location) || filter_var($location, FILTER_VALIDATE_URL)) {
                    $imageUrl = $location;
                }
                $row['description'] = $row['content'] ?? '';
                $row['imageUrl'] = $imageUrl;
                if ($imageUrl !== '') {
                    $row['location'] = null;
                }
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
        $locationOut = (string)($row['location'] ?? '');
        $imageUrl = ($isNewsUploadUrl($locationOut) || filter_var($locationOut, FILTER_VALIDATE_URL)) ? $locationOut : '';
        $row['description'] = $row['content'] ?? '';
        $row['imageUrl'] = $imageUrl;
        if ($imageUrl !== '') {
            $row['location'] = null;
        }
        $mysqli->close();
        $sendJson(200, $row);
        exit;
    }

    if (in_array($method, ['PUT', 'PATCH']) && preg_match('#^/(api/)?news/(\d+)$#', $path, $m)) {
        $id = (int)$m[2];
        $data = $readJson();
        $mysqli = $connectDb();
        if (!$mysqli) { exit; }

        $chk = $mysqli->query('SELECT id, location FROM news WHERE id = ' . $id . ' LIMIT 1');
        if (!$chk || $chk->num_rows === 0) {
            if ($chk) { $chk->free(); }
            $mysqli->close();
            $sendJson(404, ['success' => false, 'error' => 'Not found']);
            exit;
        }
        $existing = $chk->fetch_assoc();
        $oldLocation = (string)($existing['location'] ?? '');
        $chk->free();

        $fields = [];
        $types = '';
        $vals = [];
        $location = $data['location'] ?? ($data['imageUrl'] ?? null);

        if (isset($data['title'])) { $fields[] = 'title = ?'; $types .= 's'; $vals[] = $data['title']; }
        if (isset($data['content']) || isset($data['description'])) {
            $fields[] = 'content = ?';
            $types .= 's';
            $vals[] = ($data['content'] ?? $data['description']);
        }
        if (isset($data['is_event'])) { $fields[] = 'is_event = ?'; $types .= 'i'; $vals[] = (!empty($data['is_event']) ? 1 : 0); }
        if (isset($data['date'])) { $fields[] = 'date = ?'; $types .= 's'; $vals[] = $data['date']; }
        if ($location !== null) { $fields[] = 'location = ?'; $types .= 's'; $vals[] = $location; }

        if (!empty($fields)) {
            $vals[] = $id;
            $types .= 'i';
            $stmt = $mysqli->prepare('UPDATE news SET ' . implode(', ', $fields) . ' WHERE id = ?');
            $stmt->bind_param($types, ...$vals);
            $stmt->execute();
            $stmt->close();
        }

        $rowResult = $mysqli->query('SELECT id, title, content, is_event, date, location, created_at, updated_at FROM news WHERE id = ' . $id . ' LIMIT 1');
        $row = $rowResult ? $rowResult->fetch_assoc() : ['id' => $id];
        if ($rowResult) { $rowResult->free(); }

        $newLocation = (string)($row['location'] ?? '');
        $cleanup = 'not_applicable';
        if ($oldLocation !== '' && $oldLocation !== $newLocation) {
            $cleanup = $cleanupNewsUploadIfUnused($mysqli, $oldLocation, $id);
        }

        $imageUrl = ($isNewsUploadUrl($newLocation) || filter_var($newLocation, FILTER_VALIDATE_URL)) ? $newLocation : '';
        $row['description'] = $row['content'] ?? '';
        $row['imageUrl'] = $imageUrl;
        if ($imageUrl !== '') {
            $row['location'] = null;
        }
        $row['imageCleanup'] = $cleanup;

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

        $location = '';
        $pre = $mysqli->query('SELECT location FROM news WHERE id = ' . $id . ' LIMIT 1');
        if ($pre && $pre->num_rows > 0) {
            $preRow = $pre->fetch_assoc();
            $location = (string)($preRow['location'] ?? '');
        }
        if ($pre) {
            $pre->free();
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

        $cleanup = 'not_applicable';
        if ($affected > 0 && $location !== '') {
            $cleanup = $cleanupNewsUploadIfUnused($mysqli, $location, null);
        }

        $mysqli->close();
        $sendJson(200, $affected > 0 ? ['success' => true, 'imageCleanup' => $cleanup] : ['success' => false, 'error' => 'Not found']);
        exit;
    }

    if ($method === 'POST' && ($path === '/api/gallery/upload' || $path === '/gallery/upload')) {
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
        $uploadDir = __DIR__ . '/uploads/gallery/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0775, true); }
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $safeName)) {
            $sendJson(500, ['error' => 'Failed to save file']);
            exit;
        }
        $sendJson(200, ['url' => '/api/uploads/gallery/' . $safeName]);
        exit;
    }

    if ($method === 'GET' && ($path === '/api/gallery' || $path === '/gallery')) {
        $mysqli = $connectDb();
        if (!$mysqli) {
            exit;
        }
        $ensureGalleryTable($mysqli);
        $rows = [];
        $result = $mysqli->query('SELECT id, title, description, image_url, created_at, updated_at FROM gallery_items ORDER BY id DESC');
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $image = (string)($row['image_url'] ?? '');
                $row['imageUrl'] = ($isGalleryUploadUrl($image) || filter_var($image, FILTER_VALIDATE_URL)) ? $image : '';
                $rows[] = $row;
            }
            $result->free();
        }
        $mysqli->close();
        $sendJson(200, $rows);
        exit;
    }

    if ($method === 'POST' && ($path === '/api/gallery' || $path === '/gallery')) {
        $data = $readJson();
        $title = trim((string)($data['title'] ?? 'Gallery Item'));
        $description = (string)($data['description'] ?? '');
        $imageUrl = (string)($data['imageUrl'] ?? ($data['image_url'] ?? ''));
        if ($imageUrl === '') {
            $sendJson(400, ['error' => 'Image URL is required']);
            exit;
        }

        $mysqli = $connectDb();
        if (!$mysqli) {
            exit;
        }
        $ensureGalleryTable($mysqli);

        $stmt = $mysqli->prepare('INSERT INTO gallery_items (title, description, image_url) VALUES (?, ?, ?)');
        if (!$stmt) {
            $mysqli->close();
            $sendJson(500, ['error' => 'Query preparation failed']);
            exit;
        }
        $stmt->bind_param('sss', $title, $description, $imageUrl);
        $ok = $stmt->execute();
        $newId = (int)$mysqli->insert_id;
        $stmt->close();
        if (!$ok) {
            $mysqli->close();
            $sendJson(500, ['error' => 'Failed to create gallery item']);
            exit;
        }
        $rowResult = $mysqli->query('SELECT id, title, description, image_url, created_at, updated_at FROM gallery_items WHERE id = ' . $newId . ' LIMIT 1');
        $row = $rowResult ? $rowResult->fetch_assoc() : ['id' => $newId, 'title' => $title, 'description' => $description, 'image_url' => $imageUrl];
        if ($rowResult) {
            $rowResult->free();
        }
        $image = (string)($row['image_url'] ?? '');
        $row['imageUrl'] = ($isGalleryUploadUrl($image) || filter_var($image, FILTER_VALIDATE_URL)) ? $image : '';
        $mysqli->close();
        $sendJson(200, $row);
        exit;
    }

    if (in_array($method, ['PUT', 'PATCH']) && preg_match('#^/(api/)?gallery/(\d+)$#', $path, $m)) {
        $id = (int)$m[2];
        $data = $readJson();
        $mysqli = $connectDb();
        if (!$mysqli) { exit; }
        $ensureGalleryTable($mysqli);

        $chk = $mysqli->query('SELECT id, image_url FROM gallery_items WHERE id = ' . $id . ' LIMIT 1');
        if (!$chk || $chk->num_rows === 0) {
            if ($chk) { $chk->free(); }
            $mysqli->close();
            $sendJson(404, ['success' => false, 'error' => 'Not found']);
            exit;
        }
        $existing = $chk->fetch_assoc();
        $oldImageUrl = (string)($existing['image_url'] ?? '');
        $chk->free();

        $fields = [];
        $types = '';
        $vals = [];
        if (isset($data['title'])) { $fields[] = 'title = ?'; $types .= 's'; $vals[] = $data['title']; }
        if (isset($data['description'])) { $fields[] = 'description = ?'; $types .= 's'; $vals[] = $data['description']; }
        $imageUrl = $data['imageUrl'] ?? ($data['image_url'] ?? null);
        if ($imageUrl !== null) { $fields[] = 'image_url = ?'; $types .= 's'; $vals[] = $imageUrl; }

        if (!empty($fields)) {
            $vals[] = $id;
            $types .= 'i';
            $stmt = $mysqli->prepare('UPDATE gallery_items SET ' . implode(', ', $fields) . ' WHERE id = ?');
            $stmt->bind_param($types, ...$vals);
            $stmt->execute();
            $stmt->close();
        }

        $rowResult = $mysqli->query('SELECT id, title, description, image_url, created_at, updated_at FROM gallery_items WHERE id = ' . $id . ' LIMIT 1');
        $row = $rowResult ? $rowResult->fetch_assoc() : ['id' => $id];
        if ($rowResult) { $rowResult->free(); }
        $newImage = (string)($row['image_url'] ?? '');
        $cleanup = 'not_applicable';
        if ($oldImageUrl !== '' && $oldImageUrl !== $newImage) {
            $cleanup = $cleanupGalleryUploadIfUnused($mysqli, $oldImageUrl, $id);
        }
        $row['imageUrl'] = ($isGalleryUploadUrl($newImage) || filter_var($newImage, FILTER_VALIDATE_URL)) ? $newImage : '';
        $row['imageCleanup'] = $cleanup;

        $mysqli->close();
        $sendJson(200, $row);
        exit;
    }

    if ($method === 'DELETE' && preg_match('#^/(api/)?gallery/(\d+)$#', $path, $m)) {
        $id = (int)$m[2];
        $mysqli = $connectDb();
        if (!$mysqli) {
            exit;
        }
        $ensureGalleryTable($mysqli);

        $imageUrl = '';
        $pre = $mysqli->query('SELECT image_url FROM gallery_items WHERE id = ' . $id . ' LIMIT 1');
        if ($pre && $pre->num_rows > 0) {
            $preRow = $pre->fetch_assoc();
            $imageUrl = (string)($preRow['image_url'] ?? '');
        }
        if ($pre) {
            $pre->free();
        }

        $stmt = $mysqli->prepare('DELETE FROM gallery_items WHERE id = ?');
        if (!$stmt) {
            $mysqli->close();
            $sendJson(500, ['error' => 'Query preparation failed']);
            exit;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        $cleanup = 'not_applicable';
        if ($affected > 0 && $imageUrl !== '') {
            $cleanup = $cleanupGalleryUploadIfUnused($mysqli, $imageUrl, null);
        }

        $mysqli->close();
        $sendJson(200, $affected > 0 ? ['success' => true, 'imageCleanup' => $cleanup] : ['success' => false, 'error' => 'Not found']);
        exit;
    }

    if ($method === 'GET' && ($path === '/api/inquiries' || $path === '/inquiries')) {
        $mysqli = $connectDb();
        if (!$mysqli) {
            exit;
        }
        $rows = [];
        $result = $mysqli->query('SELECT id, name, email, phone, message, is_resolved, created_at, updated_at FROM inquiries ORDER BY is_resolved ASC, id DESC');
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

    if (in_array($method, ['PUT', 'PATCH']) && preg_match('#^/(api/)?inquiries/(\d+)/resolve$#', $path, $m)) {
        $id = (int)$m[2];
        $mysqli = $connectDb();
        if (!$mysqli) {
            exit;
        }

        $stmt = $mysqli->prepare('UPDATE inquiries SET is_resolved = 1 WHERE id = ?');
        if (!$stmt) {
            $mysqli->close();
            $sendJson(500, ['error' => 'Query preparation failed']);
            exit;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected <= 0) {
            $check = $mysqli->query('SELECT id, is_resolved FROM inquiries WHERE id = ' . $id . ' LIMIT 1');
            $exists = $check && $check->num_rows > 0;
            if ($check) {
                $check->free();
            }
            $mysqli->close();
            if ($exists) {
                $sendJson(200, ['success' => true, 'id' => $id, 'is_resolved' => true]);
            } else {
                $sendJson(404, ['success' => false, 'error' => 'Not found']);
            }
            exit;
        }

        $mysqli->close();
        $sendJson(200, ['success' => true, 'id' => $id, 'is_resolved' => true]);
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
            'trusteeCount' => 0,
            'totalDonations' => 0,
            'donationCount' => 0,
            'newInquiries' => 0,
            'galleryCount' => 0,
            'totalNews' => 0,
        ];
        $q1 = $mysqli->query('SELECT COUNT(*) AS c FROM trustees');
        if ($q1) { $stats['trusteeCount'] = (int)($q1->fetch_assoc()['c'] ?? 0); $q1->free(); }
        $q2 = $mysqli->query("SELECT COALESCE(SUM(amount),0) AS s FROM donations WHERE payment_status = 'Completed'");
        if ($q2) { $stats['totalDonations'] = (float)($q2->fetch_assoc()['s'] ?? 0); $q2->free(); }
        $q3 = $mysqli->query('SELECT COUNT(*) AS c FROM donations');
        if ($q3) { $stats['donationCount'] = (int)($q3->fetch_assoc()['c'] ?? 0); $q3->free(); }
        $q4 = $mysqli->query('SELECT COUNT(*) AS c FROM inquiries WHERE is_resolved = 0');
        if ($q4) { $stats['newInquiries'] = (int)($q4->fetch_assoc()['c'] ?? 0); $q4->free(); }
        $ensureGalleryTable($mysqli);
        $qg = $mysqli->query('SELECT COUNT(*) AS c FROM gallery_items');
        if ($qg) { $stats['galleryCount'] = (int)($qg->fetch_assoc()['c'] ?? 0); $qg->free(); }
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
