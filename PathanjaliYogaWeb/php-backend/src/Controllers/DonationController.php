<?php
// src/Controllers/DonationController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Donation;

class DonationController {
    private function readPayload(Request $request): array {
        $data = (array)$request->getParsedBody();
        if (!empty($data)) {
            return $data;
        }

        $raw = (string)$request->getBody();
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeDonationPayload(array $data): array {
        $donorName = trim((string)($data['donor_name'] ?? ($data['donorName'] ?? '')));
        $email = trim((string)($data['email'] ?? ''));
        $phone = trim((string)($data['phone'] ?? ''));
        $amount = (float)($data['amount'] ?? 0);

        return [
            'donor_name' => $donorName,
            'email' => $email,
            'phone' => $phone,
            'amount' => $amount,
            'pan_number' => trim((string)($data['pan_number'] ?? ($data['panNumber'] ?? ''))),
            'address' => trim((string)($data['address'] ?? '')),
            'payment_status' => (string)($data['payment_status'] ?? 'Pending'),
            'transaction_id' => (string)($data['transaction_id'] ?? ($data['transactionId'] ?? '')),
        ];
    }

    // Return Razorpay public key
    public function getRazorpayKey(Request $request, Response $response, $args) {
        $key = (string)(getenv('RAZORPAY_KEY_ID') ?: '');
        if ($key === 'your_razorpay_key_id') {
            $key = '';
        }

        $response->getBody()->write(json_encode([
            'key' => $key,
            'configured' => $key !== '',
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function index(Request $request, Response $response, $args) {
        $donations = Donation::orderBy('id', 'desc')->get()->map(function ($donation) {
            return [
                'id' => $donation->id,
                'donor_name' => $donation->donor_name,
                'email' => $donation->email,
                'phone' => $donation->phone,
                'amount' => $donation->amount,
                'payment_status' => $donation->payment_status,
                'transaction_id' => $donation->transaction_id,
                'created_at' => $donation->created_at,
                'updated_at' => $donation->updated_at,
            ];
        });
        $response->getBody()->write($donations->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function createOrder(Request $request, Response $response, $args) {
        $payload = $this->normalizeDonationPayload($this->readPayload($request));
        if ($payload['donor_name'] === '') {
            $response->getBody()->write(json_encode(['error' => 'Donor name is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        if ($payload['amount'] <= 0) {
            $response->getBody()->write(json_encode(['error' => 'Amount must be greater than zero']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Simulate order creation by persisting pending donation.
        $donation = Donation::create($payload);

        // In production, integrate with payment gateway and return order_id
        $response->getBody()->write(json_encode([
            'orderId' => (string)$donation->id,
            'order_id' => (string)$donation->id,
            'id' => (int)$donation->id,
            'paymentStatus' => (string)$donation->payment_status,
            'donation' => $donation,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function verify(Request $request, Response $response, $args) {
        $data = $this->readPayload($request);
        $id = (int)($data['id'] ?? ($data['orderId'] ?? ($data['order_id'] ?? 0)));
        $txn = (string)($data['transaction_id'] ?? ($data['transactionId'] ?? ($data['paymentId'] ?? ($data['razorpay_payment_id'] ?? ''))));

        $donation = Donation::find($id);
        if ($donation) {
            $donation->payment_status = 'Completed';
            $donation->transaction_id = $txn !== '' ? $txn : $donation->transaction_id;
            $donation->save();
            $response->getBody()->write(json_encode(['verified' => true, 'donation' => $donation]));
        } else {
            $response->getBody()->write(json_encode(['verified' => false, 'error' => 'Donation not found']));
        }
        return $response->withHeader('Content-Type', 'application/json');
    }
}
