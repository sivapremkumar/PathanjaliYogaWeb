<?php
// src/Controllers/AuthController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController {
    public function login(Request $request, Response $response, $args) {
        // TODO: Implement login logic (JWT/session)
        $response->getBody()->write(json_encode(['token' => 'demo-token', 'username' => 'admin']));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function logout(Request $request, Response $response, $args) {
        // TODO: Implement logout logic
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
