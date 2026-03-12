<?php
// src/Controllers/NewsEventController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\NewsEvent;

class NewsEventController {
    public function index(Request $request, Response $response, $args) {
        $news = NewsEvent::all();
        $response->getBody()->write($news->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response, $args) {
        $data = (array)$request->getParsedBody();
        $news = NewsEvent::create($data);
        $response->getBody()->write($news->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }
}
