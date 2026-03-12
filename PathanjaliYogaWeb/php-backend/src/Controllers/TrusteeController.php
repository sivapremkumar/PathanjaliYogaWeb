<?php
// src/Controllers/TrusteeController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Trustee;

class TrusteeController {
    public function index(Request $request, Response $response, $args) {
        $trustees = Trustee::all();
        $response->getBody()->write($trustees->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response, $args) {
        $data = (array)$request->getParsedBody();
        $trustee = Trustee::create($data);
        $response->getBody()->write($trustee->toJson());
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
}
