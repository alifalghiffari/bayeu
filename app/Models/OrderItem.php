<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'menu_id',
        'quantity',
        'created_by',
        'updated_by',
    ];

    public function order() {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function menu() {
        return $this->belongsTo(Menu::class, 'menu_id');
    }
}
