<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'x',
        'y',
        'w',
        'h',
        'area_id',
        'created_by',
        'updated_by',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function order()
    {
        return $this->hasMany(Order::class);
    }
}
