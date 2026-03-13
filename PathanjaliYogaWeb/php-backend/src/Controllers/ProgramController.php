<?php
// src/Controllers/ProgramController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Program;
use Illuminate\Database\Capsule\Manager as Capsule;

class ProgramController {
    private function ensureProgramsTableAndSeed(): void {
        Capsule::statement("CREATE TABLE IF NOT EXISTS programs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            type VARCHAR(100) DEFAULT 'Program',
            schedule VARCHAR(255),
            image_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        if (Program::count() > 0) {
            return;
        }

        Program::insert([
            [
                'title' => 'Traditional Padhanjali Yoga',
                'description' => 'Daily morning sessions focused on Surya Namaskar and Pranayama.',
                'type' => 'Yoga',
                'schedule' => '6:00 AM - 7:30 AM',
                'image_url' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&q=80&w=800',
            ],
            [
                'title' => 'Yoga for Children',
                'description' => 'Fun and interactive sessions to improve concentration and posture in kids.',
                'type' => 'Yoga',
                'schedule' => '4:30 PM - 5:30 PM',
                'image_url' => 'https://images.unsplash.com/photo-1552196564-972d46387347?auto=format&fit=crop&q=80&w=800',
            ],
            [
                'title' => 'Social Welfare Awareness',
                'description' => 'Monthly workshops about health, hygiene, and community development.',
                'type' => 'Welfare',
                'schedule' => 'Monthly Weekends',
                'image_url' => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&q=80&w=800',
            ],
        ]);
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

    private function isProgramUploadUrl(?string $imageUrl): bool {
        if (!$imageUrl) {
            return false;
        }
        $path = parse_url($imageUrl, PHP_URL_PATH);
        if (!$path) {
            $path = $imageUrl;
        }
        return strpos($path, '/api/uploads/programs/') === 0;
    }

    private function normalizeImageUrl(?string $imageUrl): ?string {
        if (!$imageUrl) {
            return null;
        }
        if ($this->isProgramUploadUrl($imageUrl)) {
            return $imageUrl;
        }
        return filter_var($imageUrl, FILTER_VALIDATE_URL) ? $imageUrl : null;
    }

    private function cleanupProgramUploadIfUnused(?string $imageUrl, ?int $excludeId = null): string {
        if (!$this->isProgramUploadUrl($imageUrl)) {
            return 'not_applicable';
        }

        $query = Program::where('image_url', $imageUrl);
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

        $fullPath = __DIR__ . '/../../uploads/programs/' . $fileName;
        if (!file_exists($fullPath)) {
            return 'file_missing';
        }

        return @unlink($fullPath) ? 'deleted' : 'cleanup_failed';
    }

    public function index(Request $request, Response $response, $args) {
        $this->ensureProgramsTableAndSeed();

        $items = Program::orderBy('id', 'asc')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'type' => $item->type,
                'schedule' => $item->schedule,
                'imageUrl' => $this->normalizeImageUrl($item->image_url),
                'image_url' => $item->image_url,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

        $response->getBody()->write($items->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response, $args) {
        $this->ensureProgramsTableAndSeed();
        $data = $this->readPayload($request);

        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            $response->getBody()->write(json_encode(['error' => 'Title is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $item = Program::create([
            'title' => $title,
            'description' => (string)($data['description'] ?? ''),
            'type' => (string)($data['type'] ?? 'Program'),
            'schedule' => (string)($data['schedule'] ?? ''),
            'image_url' => (string)($data['imageUrl'] ?? ($data['image_url'] ?? '')),
        ]);

        $response->getBody()->write(json_encode([
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'type' => $item->type,
            'schedule' => $item->schedule,
            'imageUrl' => $this->normalizeImageUrl($item->image_url),
            'image_url' => $item->image_url,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, $args) {
        $this->ensureProgramsTableAndSeed();
        $id = $args['id'] ?? null;
        $item = Program::find($id);
        if (!$item) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $oldImageUrl = (string)($item->image_url ?? '');
        $data = $this->readPayload($request);
        $item->fill([
            'title' => $data['title'] ?? $item->title,
            'description' => $data['description'] ?? $item->description,
            'type' => $data['type'] ?? $item->type,
            'schedule' => $data['schedule'] ?? $item->schedule,
            'image_url' => $data['imageUrl'] ?? ($data['image_url'] ?? $item->image_url),
        ]);
        $item->save();

        $cleanup = 'not_applicable';
        if ($oldImageUrl !== '' && $oldImageUrl !== (string)$item->image_url) {
            $cleanup = $this->cleanupProgramUploadIfUnused($oldImageUrl, (int)$item->id);
        }

        $response->getBody()->write(json_encode([
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'type' => $item->type,
            'schedule' => $item->schedule,
            'imageUrl' => $this->normalizeImageUrl($item->image_url),
            'image_url' => $item->image_url,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'imageCleanup' => $cleanup,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, $args) {
        $this->ensureProgramsTableAndSeed();
        $id = $args['id'] ?? null;
        $item = Program::find($id);
        if (!$item) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $oldImageUrl = (string)($item->image_url ?? '');
        $item->delete();
        $cleanup = $this->cleanupProgramUploadIfUnused($oldImageUrl, null);

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
        $uploadDir = __DIR__ . '/../../uploads/programs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $file->moveTo($uploadDir . $safeName);
        $response->getBody()->write(json_encode(['url' => '/api/uploads/programs/' . $safeName]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
