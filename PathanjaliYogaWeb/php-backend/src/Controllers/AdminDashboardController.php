<?php
// src/Controllers/AdminDashboardController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Trustee;
use App\Models\Donation;
use App\Models\Inquiry;
use App\Models\NewsEvent;
use App\Models\GalleryItem;
use Illuminate\Database\Capsule\Manager as Capsule;

class AdminDashboardController {
    public function stats(Request $request, Response $response, $args) {
        Capsule::statement("CREATE TABLE IF NOT EXISTS gallery_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_url VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stats = [
            'trusteeCount' => Trustee::count(),
            'totalDonations' => Donation::where('payment_status', 'Completed')->sum('amount'),
            'donationCount' => Donation::count(),
            'newInquiries' => Inquiry::count(),
            'galleryCount' => GalleryItem::count(),
            'totalNews' => NewsEvent::count()
        ];
        $response->getBody()->write(json_encode($stats));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
