<?php
// src/Controllers/DonationController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DonationController {
    public function index(Request $request, Response $response, $args) {
        // TODO: Fetch donations from DB
        $donations = [
            ['id' => 1, 'amount' => 100, 'donor' => 'John Doe'],
            ['id' => 2, 'amount' => 200, 'donor' => 'Jane Smith']
        ];
        $response->getBody()->write(json_encode($donations));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function createOrder(Request $request, Response $response, $args) {
        // TODO: Create donation order (payment gateway integration)
        $response->getBody()->write(json_encode(['order_id' => 'demo-order-id']));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function verify(Request $request, Response $response, $args) {
        // TODO: Verify payment
        $response->getBody()->write(json_encode(['verified' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
