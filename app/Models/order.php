<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'grand_total',
        'payment_method',
        'payment_status',
        'status',
        'currency',
        'shipping_amount',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(user::class);
    }
    public function items()
    {
        return $this->hasMany(orderitem::class);
    }
    public function address()
    {
        return $this->hasOne(address::class);
    }
}
