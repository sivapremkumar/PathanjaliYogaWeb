<?php
// src/Controllers/GalleryController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\GalleryItem;
use Illuminate\Database\Capsule\Manager as Capsule;

class GalleryController {
    private function ensureGalleryTable(): void {
        Capsule::statement("CREATE TABLE IF NOT EXISTS gallery_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_url VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function readPayload(Request $request): array {
        $data = (array)$request->getParsedBody();
        if (!empty($data)) {
            return $data;
        }

        $raw = (string)$request->getBody();
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function isGalleryUploadUrl(?string $imageUrl): bool {
        if (!$imageUrl) {
            return false;
        }
        $path = parse_url($imageUrl, PHP_URL_PATH);
        if (!$path) {
            $path = $imageUrl;
        }
        return strpos($path, '/api/uploads/gallery/') === 0;
    }

    private function normalizeImageUrl(?string $imageUrl): ?string {
        if (!$imageUrl) {
            return null;
        }
        if ($this->isGalleryUploadUrl($imageUrl)) {
            return $imageUrl;
        }
        return filter_var($imageUrl, FILTER_VALIDATE_URL) ? $imageUrl : null;
    }

    private function cleanupGalleryUploadIfUnused(?string $imageUrl, ?int $excludeId = null): string {
        if (!$this->isGalleryUploadUrl($imageUrl)) {
            return 'not_applicable';
        }

        $query = GalleryItem::where('image_url', $imageUrl);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        if ($query->count() > 0) {
            return 'kept_referenced';
        }

        $path = parse_url($imageUrl, PHP_URL_PATH);
        if (!$path) {
            $path = $imageUrl;
        }
        if (strpos($path, '..') !== false) {
            return 'skipped';
        }

        $fileName = basename($path);
        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            return 'skipped';
        }

        $fullPath = __DIR__ . '/../../uploads/gallery/' . $fileName;
        if (!file_exists($fullPath)) {
            return 'file_missing';
        }

        return @unlink($fullPath) ? 'deleted' : 'cleanup_failed';
    }

    public function index(Request $request, Response $response, $args) {
        $this->ensureGalleryTable();

        $items = GalleryItem::orderBy('id', 'desc')->get()->map(function ($item) {
            $imageUrl = $this->normalizeImageUrl($item->image_url);
            return [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'imageUrl' => $imageUrl,
                'image_url' => $item->image_url,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

        $response->getBody()->write($items->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response, $args) {
        $this->ensureGalleryTable();

        $data = $this->readPayload($request);
        $title = trim((string)($data['title'] ?? 'Gallery Item'));
        $description = (string)($data['description'] ?? '');
        $imageUrl = (string)($data['imageUrl'] ?? ($data['image_url'] ?? ''));

        if ($imageUrl === '') {
            $response->getBody()->write(json_encode(['error' => 'Image URL is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $item = GalleryItem::create([
            'title' => $title,
            'description' => $description,
            'image_url' => $imageUrl,
        ]);

        $response->getBody()->write(json_encode([
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'imageUrl' => $this->normalizeImageUrl($item->image_url),
            'image_url' => $item->image_url,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, $args) {
        $this->ensureGalleryTable();

        $id = $args['id'] ?? null;
        $item = GalleryItem::find($id);
        if (!$item) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $oldImageUrl = (string)($item->image_url ?? '');
        $data = $this->readPayload($request);
        $item->fill([
            'title' => $data['title'] ?? $item->title,
            'description' => $data['description'] ?? $item->description,
            'image_url' => $data['imageUrl'] ?? ($data['image_url'] ?? $item->image_url),
        ]);
        $item->save();

        $cleanup = 'not_applicable';
        if ($oldImageUrl !== '' && $oldImageUrl !== (string)$item->image_url) {
            $cleanup = $this->cleanupGalleryUploadIfUnused($oldImageUrl, (int)$item->id);
        }

        $response->getBody()->write(json_encode([
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'imageUrl' => $this->normalizeImageUrl($item->image_url),
            'image_url' => $item->image_url,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'imageCleanup' => $cleanup,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, $args) {
        $this->ensureGalleryTable();

        $id = $args['id'] ?? null;
        $item = GalleryItem::find($id);
        if (!$item) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $oldImageUrl = (string)($item->image_url ?? '');
        $item->delete();
        $cleanup = $this->cleanupGalleryUploadIfUnused($oldImageUrl, null);

        $response->getBody()->write(json_encode(['success' => true, 'imageCleanup' => $cleanup]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function upload(Request $request, Response $response, $args) {
        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['image'] ?? null;
        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode(['error' => 'No valid file uploaded']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        if ($file->getSize() > 10 * 1024 * 1024) {
            $response->getBody()->write(json_encode(['error' => 'File exceeds 10 MB limit']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime = $file->getClientMediaType();
        if (!in_array($mime, $allowed, true)) {
            $response->getBody()->write(json_encode(['error' => 'Unsupported file type. Use JPEG, PNG, WEBP or GIF.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $ext = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
        $uploadDir = __DIR__ . '/../../uploads/gallery/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $file->moveTo($uploadDir . $safeName);

        $response->getBody()->write(json_encode(['url' => '/api/uploads/gallery/' . $safeName]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
