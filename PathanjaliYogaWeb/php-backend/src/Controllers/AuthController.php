<?php
// src/Controllers/AuthController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\AdminUser;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController {
    public function login(Request $request, Response $response, $args) {
        $data = (array)$request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $user = AdminUser::where('username', $username)->first();
        if ($user && password_verify($password, $user->password_hash)) {
            $payload = [
                'sub' => $user->id,
                'username' => $user->username,
                'iat' => time(),
                'exp' => time() + 86400 // 1 day
            ];
            $jwt = JWT::encode($payload, getenv('JWT_SECRET'), 'HS256');
            $response->getBody()->write(json_encode(['token' => $jwt, 'username' => $user->username]));
        } else {
            $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function logout(Request $request, Response $response, $args) {
        // For JWT, logout is handled client-side by deleting the token
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
