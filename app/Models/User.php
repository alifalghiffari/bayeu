<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'profile_url',
        'role',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'profile_url' => $this->profile_url,
            'role' => $this->role,
        ];
    }

    public function waitingOrders()
    {
        return $this->hasMany(Order::class, 'waiters_id');
    }

    public function cashierOrders()
    {
        return $this->hasMany(Order::class, 'cashier_id');
    }

    public function userTenants()
    {
        return $this->hasMany(UserTenant::class);
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'user_tenants', 'user_id', 'tenant_id')
            ->withPivot('is_admin')
            ->withTimestamps();
    }

    public function hasRole($role)
    {
        return $this->role === $role;
    }
}
