<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuCategories extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image',
        'created_by',
        'updated_by',
    ];

    public function menu() {
        return $this->hasMany(Menu::class);
    }
}
