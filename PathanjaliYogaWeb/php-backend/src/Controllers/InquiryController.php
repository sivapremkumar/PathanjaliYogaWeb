<?php
// src/Controllers/InquiryController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Inquiry;

class InquiryController {
    private function readPayload(Request $request): array {
        $data = (array)$request->getParsedBody();
        if (!empty($data)) {
            return $data;
        }

        $raw = (string)$request->getBody();
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function create(Request $request, Response $response, $args) {
        $data = $this->readPayload($request);

        $name = trim((string)($data['name'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $phone = trim((string)($data['phone'] ?? ''));
        $message = trim((string)($data['message'] ?? ''));

        if ($name === '' || $message === '') {
            $response->getBody()->write(json_encode(['error' => 'Name and message are required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $inquiry = Inquiry::create([
            'name' => $name,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'message' => $message,
            'is_resolved' => false,
        ]);
        $response->getBody()->write($inquiry->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function index(Request $request, Response $response, $args) {
        $inquiries = Inquiry::orderBy('is_resolved', 'asc')->orderBy('id', 'desc')->get();
        $response->getBody()->write($inquiries->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function resolve(Request $request, Response $response, $args) {
        $id = $args['id'] ?? null;
        $inquiry = Inquiry::find($id);
        if (!$inquiry) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $inquiry->is_resolved = true;
        $inquiry->save();

        $response->getBody()->write(json_encode([
            'success' => true,
            'id' => $inquiry->id,
            'is_resolved' => (bool)$inquiry->is_resolved,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
