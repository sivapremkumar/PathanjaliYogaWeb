<?php
// src/Controllers/InquiryController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Inquiry;

class InquiryController {
    public function create(Request $request, Response $response, $args) {
        $data = (array)$request->getParsedBody();
        $inquiry = Inquiry::create($data);
        $response->getBody()->write($inquiry->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function index(Request $request, Response $response, $args) {
        $inquiries = Inquiry::all();
        $response->getBody()->write($inquiries->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }
}
