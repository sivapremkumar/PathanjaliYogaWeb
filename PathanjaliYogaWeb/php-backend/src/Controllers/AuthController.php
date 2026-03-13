<?php
// src/Controllers/AuthController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\AdminUser;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController {
    private function readPayload(Request $request): array {
        $body = (string)$request->getBody();
        $data = json_decode($body, true);
        return is_array($data) ? $data : [];
    }

    private function getUsernameFromToken(Request $request): ?string {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $m)) {
            return null;
        }

        $token = trim((string)$m[1]);
        if ($token === '') {
            return null;
        }

        $secret = (string)(getenv('JWT_SECRET') ?: '');
        if ($secret === '') {
            return null;
        }

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            $username = (string)($decoded->username ?? '');
            return $username !== '' ? $username : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

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
                $response->getBody()->write(json_encode([
                    'error' => 'Username and password required',
                    'debug' => [
                        'received_username' => $username,
                        'password_length' => strlen($password),
                        'body_raw' => $body
                    ]
                ]));
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

    public function changePassword(Request $request, Response $response, $args) {
        $data = $this->readPayload($request);

        $tokenUsername = $this->getUsernameFromToken($request);
        $payloadUsername = trim((string)($data['username'] ?? ''));
        $username = $tokenUsername ?: $payloadUsername;

        $currentPassword = (string)($data['currentPassword'] ?? ($data['current_password'] ?? ''));
        $newPassword = (string)($data['newPassword'] ?? ($data['new_password'] ?? ''));
        $confirmPassword = (string)($data['confirmPassword'] ?? ($data['confirm_password'] ?? ''));

        if ($username === '' || $currentPassword === '' || $newPassword === '') {
            $response->getBody()->write(json_encode(['error' => 'Username, current password and new password are required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if (strlen($newPassword) < 6) {
            $response->getBody()->write(json_encode(['error' => 'New password must be at least 6 characters']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if ($confirmPassword !== '' && $confirmPassword !== $newPassword) {
            $response->getBody()->write(json_encode(['error' => 'New password and confirm password do not match']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $user = AdminUser::where('username', $username)->first();
        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Admin user not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if (!password_verify($currentPassword, (string)$user->password_hash)) {
            $response->getBody()->write(json_encode(['error' => 'Current password is incorrect']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        if (password_verify($newPassword, (string)$user->password_hash)) {
            $response->getBody()->write(json_encode(['error' => 'New password must be different from current password']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $user->password_hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $user->save();

        $response->getBody()->write(json_encode(['success' => true, 'message' => 'Password changed successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
