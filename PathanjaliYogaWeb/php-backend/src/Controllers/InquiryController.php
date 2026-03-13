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
        $inquiries = Inquiry::all();
        $response->getBody()->write($inquiries->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }
}
