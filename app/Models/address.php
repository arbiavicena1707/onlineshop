<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class address extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'first_name',
        'last_name',
        'phone',
        'stress_adress',
        'city',
        'state',
        'zip_code',
    ];

    public function order(){
        return $this->belongsTo(Order::class);
    }

    public function getfullnameattribut(){
        return "{this->first_name}{this->last_name}";
    }
}
