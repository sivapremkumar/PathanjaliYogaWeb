<?php
// src/Controllers/InquiryController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class InquiryController {
    public function create(Request $request, Response $response, $args) {
        // TODO: Save inquiry to DB
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function index(Request $request, Response $response, $args) {
        // TODO: Fetch inquiries from DB
        $inquiries = [
            ['id' => 1, 'name' => 'Alice', 'message' => 'Interested in yoga.'],
            ['id' => 2, 'name' => 'Bob', 'message' => 'How to donate?']
        ];
        $response->getBody()->write(json_encode($inquiries));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
