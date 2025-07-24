<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'is_admin',
        'created_by',
        'updated_by',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }
}
