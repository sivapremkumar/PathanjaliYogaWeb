<?php
// src/Models/NewsEvent.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsEvent extends Model {
    protected $table = 'news';
    protected $fillable = [
        'title', 'content', 'is_event', 'date', 'location'
    ];
}
