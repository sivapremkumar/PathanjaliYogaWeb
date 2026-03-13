<?php
// src/Controllers/NewsEventController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\NewsEvent;

class NewsEventController {
    private function readPayload(Request $request): array {
        $data = (array)$request->getParsedBody();
        if (!empty($data)) {
            return $data;
        }

        $raw = (string)$request->getBody();
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function isNewsUploadUrl(?string $imageUrl): bool {
        if (!$imageUrl) {
            return false;
        }
        $path = parse_url($imageUrl, PHP_URL_PATH);
        if (!$path) {
            $path = $imageUrl;
        }
        return strpos($path, '/api/uploads/news_event_clips/') === 0;
    }

    private function normalizeImageUrl(?string $location): ?string {
        if (!$location) {
            return null;
        }
        if ($this->isNewsUploadUrl($location)) {
            return $location;
        }
        return filter_var($location, FILTER_VALIDATE_URL) ? $location : null;
    }

    private function cleanupNewsUploadIfUnused(?string $imageUrl, ?int $excludeNewsId = null): string {
        if (!$this->isNewsUploadUrl($imageUrl)) {
            return 'not_applicable';
        }

        $query = NewsEvent::where('location', $imageUrl);
        if ($excludeNewsId !== null) {
            $query->where('id', '!=', $excludeNewsId);
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
        $fullPath = __DIR__ . '/../../uploads/news_event_clips/' . $fileName;
        if (!file_exists($fullPath)) {
            return 'file_missing';
        }
        return @unlink($fullPath) ? 'deleted' : 'cleanup_failed';
    }

    public function index(Request $request, Response $response, $args) {
        $news = NewsEvent::orderBy('id', 'desc')->get()->map(function ($item) {
            $imageUrl = $this->normalizeImageUrl($item->location);

            return [
                'id' => $item->id,
                'title' => $item->title,
                'content' => $item->content,
                'description' => $item->content,
                'imageUrl' => $imageUrl,
                'location' => $imageUrl ? null : $item->location,
                'is_event' => $item->is_event,
                'date' => $item->date,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

        $response->getBody()->write($news->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response, $args) {
        $data = $this->readPayload($request);

        $news = NewsEvent::create([
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? ($data['description'] ?? ''),
            'is_event' => $data['is_event'] ?? false,
            'date' => $data['date'] ?? null,
            'location' => $data['location'] ?? ($data['imageUrl'] ?? ''),
        ]);

        $imageUrl = $this->normalizeImageUrl($news->location);
        $response->getBody()->write(json_encode([
            'id' => $news->id,
            'title' => $news->title,
            'content' => $news->content,
            'description' => $news->content,
            'imageUrl' => $imageUrl,
            'location' => $imageUrl ? null : $news->location,
            'is_event' => $news->is_event,
            'date' => $news->date,
            'created_at' => $news->created_at,
            'updated_at' => $news->updated_at,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, $args) {
        $id = $args['id'] ?? null;
        $news = NewsEvent::find($id);
        if (!$news) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $oldLocation = (string)($news->location ?? '');
        $data = $this->readPayload($request);
        $news->fill([
            'title' => $data['title'] ?? $news->title,
            'content' => $data['content'] ?? ($data['description'] ?? $news->content),
            'is_event' => $data['is_event'] ?? $news->is_event,
            'date' => $data['date'] ?? $news->date,
            'location' => $data['location'] ?? ($data['imageUrl'] ?? $news->location),
        ]);
        $news->save();

        $cleanup = 'not_applicable';
        if ($oldLocation !== '' && $oldLocation !== (string)$news->location) {
            $cleanup = $this->cleanupNewsUploadIfUnused($oldLocation, (int)$news->id);
        }

        $imageUrl = $this->normalizeImageUrl($news->location);
        $response->getBody()->write(json_encode([
            'id' => $news->id,
            'title' => $news->title,
            'content' => $news->content,
            'description' => $news->content,
            'imageUrl' => $imageUrl,
            'location' => $imageUrl ? null : $news->location,
            'is_event' => $news->is_event,
            'date' => $news->date,
            'created_at' => $news->created_at,
            'updated_at' => $news->updated_at,
            'imageCleanup' => $cleanup,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, $args) {
        $id = $args['id'] ?? null;
        $news = NewsEvent::find($id);
        if ($news) {
            $oldLocation = (string)($news->location ?? '');
            $news->delete();
            $cleanup = $this->cleanupNewsUploadIfUnused($oldLocation, null);
            $response->getBody()->write(json_encode(['success' => true, 'imageCleanup' => $cleanup]));
        } else {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Not found']));
        }
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
        $uploadDir = __DIR__ . '/../../uploads/news_event_clips/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $file->moveTo($uploadDir . $safeName);
        $url = '/api/uploads/news_event_clips/' . $safeName;
        $response->getBody()->write(json_encode(['url' => $url]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
