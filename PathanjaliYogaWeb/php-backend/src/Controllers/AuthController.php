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
        try {
            // Debug: Log the request
            $body = $request->getBody()->getContents();
            error_log("Login Request Body: " . $body);
            
            $data = json_decode($body, true) ?: [];
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';
            
            error_log("Parsed - Username: $username, Password length: " . strlen($password));
            
            if (!$username || !$password) {
                $response->getBody()->write(json_encode(['error' => 'Username and password required', 'received' => ['username' => $username, 'password' => strlen($password) > 0 ? 'provided' : 'missing']]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            error_log("Querying for user: $username");
            $user = AdminUser::where('username', $username)->first();
            error_log("User found: " . ($user ? 'yes' : 'no'));
            
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
        } catch (\Exception $e) {
            error_log("Login Exception: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Login failed: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function logout(Request $request, Response $response, $args) {
        // For JWT, logout is handled client-side by deleting the token
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
