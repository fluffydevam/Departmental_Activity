<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{

    protected $fillable = [
        'plant_name',
        'category',
        'unit_price',
        'description',
        'stock_status',
    ];
    public function user()
{
    return $this->belongsTo(User::class);
}
}
