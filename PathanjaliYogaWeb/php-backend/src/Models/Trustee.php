<?php
// src/Models/Trustee.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trustee extends Model {
    protected $table = 'trustees';
    protected $fillable = [
        'name', 'role', 'description', 'image_url'
    ];
}
