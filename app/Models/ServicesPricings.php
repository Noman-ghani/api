<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicesPricings extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "service_id",
        "name",
        "duration",
        "price_type",
        "price",
        "special_price"
    ];

    /**
     * The attributes excluded from the model"s JSON form.
     *
     * @var array
     */
    protected $hidden = [
        "created_at",
        "updated_at"
    ];
}