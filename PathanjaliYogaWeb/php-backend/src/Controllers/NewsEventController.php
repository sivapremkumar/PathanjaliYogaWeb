<?php
// src/Controllers/NewsEventController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NewsEventController {
    public function index(Request $request, Response $response, $args) {
        // TODO: Fetch news/events from DB
        $news = [
            ['id' => 1, 'title' => 'Yoga Workshop', 'isEvent' => true],
            ['id' => 2, 'title' => 'Donation Drive', 'isEvent' => false]
        ];
        $response->getBody()->write(json_encode($news));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function create(Request $request, Response $response, $args) {
        // TODO: Create news/event in DB
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
