<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Services extends Model
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

    public function category()
    {
        return $this->belongsTo(ServicesCategories::class);
    }

    public function pricings()
    {
        return $this->hasMany(ServicesPricings::class, "service_id");
    }

    public function staffs()
    {
        return $this->hasMany(StaffServices::class, "service_id");
    }

    public function branches()
    {
        return $this->hasMany(BranchServices::class, "service_id");
    }
}
