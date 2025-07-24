<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image',
        'is_active',
        'created_by',
        'updated_by',
    ];

    public function menu() {
        return $this->hasMany(Menu::class);
    }

    public function userTenant() {
        return $this->hasMany(UserTenant::class);
    }
}
