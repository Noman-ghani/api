<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class Brands extends Model
{
    protected $table = "inventory_brands";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "business_id"
    ];

    /**
     * The attributes excluded from the model"s JSON form.
     *
     * @var array
     */
    protected $hidden = [
        "business_id"
    ];

    public function products()
    {
        return $this->hasMany(Products::class, "brand_id");
    }
}