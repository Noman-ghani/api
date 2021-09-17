<?php

namespace App\Models;

use App\Models\Inventory\Products;
use Illuminate\Database\Eloquent\Model;

class DealsInclusions extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "deal_id",
        "service_id",
        "product_id",
        "quantity",
        "price"
    ];

    /**
     * The attributes excluded from the model"s JSON form.
     *
     * @var array
     */
    protected $hidden = [
        "id",
        "created_at",
        "updated_at"
    ];

    public function service()
    {
        return $this->belongsTo(Services::class, "service_id");
    }

    public function product()
    {
        return $this->belongsTo(Products::class, "product_id");
    }
}