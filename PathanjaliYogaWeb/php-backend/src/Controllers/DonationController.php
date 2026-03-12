<?php
// src/Controllers/DonationController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Donation;

class DonationController {
    public function index(Request $request, Response $response, $args) {
        $donations = Donation::all();
        $response->getBody()->write($donations->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function createOrder(Request $request, Response $response, $args) {
        $data = (array)$request->getParsedBody();
        // For now, just create a donation record (simulate order creation)
        $donation = Donation::create($data);
        // In production, integrate with payment gateway and return order_id
        $response->getBody()->write(json_encode(['order_id' => $donation->id, 'donation' => $donation]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function verify(Request $request, Response $response, $args) {
        $data = (array)$request->getParsedBody();
        $donation = Donation::find($data['id'] ?? null);
        if ($donation) {
            $donation->payment_status = 'Completed';
            $donation->transaction_id = $data['transaction_id'] ?? $donation->transaction_id;
            $donation->save();
            $response->getBody()->write(json_encode(['verified' => true, 'donation' => $donation]));
        } else {
            $response->getBody()->write(json_encode(['verified' => false, 'error' => 'Donation not found']));
        }
        return $response->withHeader('Content-Type', 'application/json');
    }
}
