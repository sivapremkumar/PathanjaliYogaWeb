<?php
// src/Models/Donation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model {
    protected $table = 'donations';
    protected $fillable = [
        'donor_name', 'email', 'phone', 'amount', 'pan_number', 'address', 'payment_status', 'transaction_id', 'receipt_path'
    ];
}
