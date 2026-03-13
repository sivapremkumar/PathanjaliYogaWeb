<?php
// src/Models/Program.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model {
    protected $table = 'programs';
    protected $fillable = [
        'title', 'description', 'type', 'schedule', 'image_url'
    ];
}
