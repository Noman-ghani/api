<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicesCategories extends Model
{
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

    public function services()
    {
        return $this->hasMany(Services::class, "category_id");
    }
}
