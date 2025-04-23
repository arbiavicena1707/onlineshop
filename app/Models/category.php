<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class category extends Model
{
    use HasFactory;
    protected $fillable=['name','slug','description','parent_id','image'];

    public function products(){
        return $this->hasMany(product::class);
    }
}
