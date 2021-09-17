<?php

namespace App\Models;

use App\Models\Inventory\Products;
use Illuminate\Database\Eloquent\Model;

class ClientDealsItems extends Model
{
    public function service()
    {
        return $this->belongsTo(Services::class);
    }

    public function product()
    {
        return $this->belongsTo(Products::class);
    }
}