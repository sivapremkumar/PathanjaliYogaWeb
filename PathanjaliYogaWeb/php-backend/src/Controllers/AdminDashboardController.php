<?php
// src/Controllers/AdminDashboardController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminDashboardController {
    public function stats(Request $request, Response $response, $args) {
        // TODO: Return admin dashboard stats
        $stats = [
            'totalTrustees' => 5,
            'totalDonations' => 100,
            'totalInquiries' => 20,
            'totalNews' => 10
        ];
        $response->getBody()->write(json_encode($stats));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
