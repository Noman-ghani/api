<?php

namespace App\Models\Inventory;

use App\Models\Countries;
use Illuminate\Database\Eloquent\Model;

class Suppliers extends Model
{
    protected $table = "inventory_suppliers";

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
        return $this->hasMany(Products::class, "supplier_id");
    }

    public function phone_country()
    {
        return $this->belongsTo(Countries::class, "phone_country_id");
    }
}