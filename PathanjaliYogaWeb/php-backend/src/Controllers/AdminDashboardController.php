<?php
// src/Controllers/AdminDashboardController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Trustee;
use App\Models\Donation;
use App\Models\Inquiry;
use App\Models\NewsEvent;

class AdminDashboardController {
    public function stats(Request $request, Response $response, $args) {
        $stats = [
            'totalTrustees' => Trustee::count(),
            'totalDonations' => Donation::where('payment_status', 'Completed')->sum('amount'),
            'donationCount' => Donation::count(),
            'totalInquiries' => Inquiry::count(),
            'totalNews' => NewsEvent::count()
        ];
        $response->getBody()->write(json_encode($stats));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
