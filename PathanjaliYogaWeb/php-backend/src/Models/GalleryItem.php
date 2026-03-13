<?php
// src/Models/GalleryItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model {
    protected $table = 'gallery_items';
    protected $fillable = [
        'title', 'description', 'image_url'
    ];
}
