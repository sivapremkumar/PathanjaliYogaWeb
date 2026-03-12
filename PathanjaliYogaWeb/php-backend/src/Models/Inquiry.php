<?php
// src/Models/Inquiry.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model {
    protected $table = 'inquiries';
    protected $fillable = [
        'name', 'email', 'phone', 'message', 'is_resolved'
    ];
}
