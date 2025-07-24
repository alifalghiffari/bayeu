<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'tenant_id',
        'name',
        'image',
        'price',
        'tax',
        'is_percent_tax',
        'created_by',
        'updated_by',
    ];

    public function category() {
        return $this->belongsTo(MenuCategories::class);
    }

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }

    public function orderItems() {
        return $this->hasOne(OrderItems::class);
    }
}
