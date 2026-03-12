<?php
// src/Controllers/TrusteeController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TrusteeController {
    public function index(Request $request, Response $response, $args) {
        // TODO: Fetch trustees from DB
        $trustees = [
            ['id' => 1, 'name' => 'Trustee 1'],
            ['id' => 2, 'name' => 'Trustee 2']
        ];
        $response->getBody()->write(json_encode($trustees));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function create(Request $request, Response $response, $args) {
        // TODO: Create trustee in DB
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function delete(Request $request, Response $response, $args) {
        // TODO: Delete trustee from DB
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
