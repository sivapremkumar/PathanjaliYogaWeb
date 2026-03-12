<?php
// src/Controllers/NewsEventController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\NewsEvent;

class NewsEventController {
    public function index(Request $request, Response $response, $args) {
        $news = NewsEvent::orderBy('id', 'desc')->get()->map(function ($item) {
            $imageUrl = filter_var($item->location, FILTER_VALIDATE_URL) ? $item->location : null;

            return [
                'id' => $item->id,
                'title' => $item->title,
                'content' => $item->content,
                'description' => $item->content,
                'imageUrl' => $imageUrl,
                'location' => $imageUrl ? null : $item->location,
                'is_event' => $item->is_event,
                'date' => $item->date,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

        $response->getBody()->write($news->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response, $args) {
        $data = (array)$request->getParsedBody();

        $news = NewsEvent::create([
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? ($data['description'] ?? ''),
            'is_event' => $data['is_event'] ?? false,
            'date' => $data['date'] ?? null,
            'location' => $data['location'] ?? ($data['imageUrl'] ?? ''),
        ]);

        $imageUrl = filter_var($news->location, FILTER_VALIDATE_URL) ? $news->location : null;
        $response->getBody()->write(json_encode([
            'id' => $news->id,
            'title' => $news->title,
            'content' => $news->content,
            'description' => $news->content,
            'imageUrl' => $imageUrl,
            'location' => $imageUrl ? null : $news->location,
            'is_event' => $news->is_event,
            'date' => $news->date,
            'created_at' => $news->created_at,
            'updated_at' => $news->updated_at,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, $args) {
        $id = $args['id'] ?? null;
        $news = NewsEvent::find($id);
        if ($news) {
            $news->delete();
            $response->getBody()->write(json_encode(['success' => true]));
        } else {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Not found']));
        }
        return $response->withHeader('Content-Type', 'application/json');
    }
}
