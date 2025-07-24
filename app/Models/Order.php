<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_id',
        'area_id',
        'waiters_id',
        'cashier_id',
        'payment_id',
        'no_order',
        'customer',
        'total_price',
        'is_paid',
        'remarks',
        'created_by',
        'updated_by',
    ];

    public function waiter()
    {
        return $this->belongsTo(User::class, 'waiters_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_id');
    }

    public function table()
    {
        return Table::where('id', $this->table_id)
            ->where('area_id', $this->area_id)
            ->first();
    }


    public function payment()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getTableAttribute()
    {
        return \App\Models\Table::where('id', $this->table_id)
            ->where('area_id', $this->area_id)
            ->first();
    }
}
