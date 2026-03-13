<?php
// src/Controllers/TrusteeController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Trustee;

class TrusteeController {

    private function readPayload(Request $request): array {
        $data = (array)$request->getParsedBody();
        if (empty($data)) {
            $raw = (string)$request->getBody();
            $decoded = json_decode($raw ?: '{}', true);
            $data = is_array($decoded) ? $decoded : [];
        }
        return $data;
    }

    private function mapImageUrl(array &$data): void {
        if (isset($data['imageUrl']) && !isset($data['image_url'])) {
            $data['image_url'] = $data['imageUrl'];
        }
        unset($data['imageUrl']);
    }

    public function index(Request $request, Response $response, $args) {
        $trustees = Trustee::orderBy('id')->get();
        $rows = $trustees->map(function ($t) {
            $arr = $t->toArray();
            $arr['imageUrl'] = $arr['image_url'] ?? null;
            return $arr;
        })->values();
        $response->getBody()->write($rows->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response, $args) {
        $data = $this->readPayload($request);
        $this->mapImageUrl($data);
        $trustee = Trustee::create($data);
        $arr = $trustee->toArray();
        $arr['imageUrl'] = $arr['image_url'] ?? null;
        $response->getBody()->write(json_encode($arr));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, $args) {
        $id = $args['id'] ?? null;
        $trustee = Trustee::find($id);
        if (!$trustee) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $data = $this->readPayload($request);
        $this->mapImageUrl($data);
        $trustee->fill($data);
        $trustee->save();
        $arr = $trustee->toArray();
        $arr['imageUrl'] = $arr['image_url'] ?? null;
        $response->getBody()->write(json_encode($arr));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, $args) {
        $id = $args['id'] ?? null;
        $trustee = Trustee::find($id);
        if ($trustee) {
            $trustee->delete();
            $response->getBody()->write(json_encode(['success' => true]));
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
        $uploadDir = __DIR__ . '/../../uploads/trustees/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $file->moveTo($uploadDir . $safeName);
        $url = '/api/uploads/trustees/' . $safeName;
        $response->getBody()->write(json_encode(['url' => $url]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
