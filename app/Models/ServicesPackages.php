<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicesPackages extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "package_id",
        "service_id",
        "pricing_id"
    ];
    
    public function service()
    {
        return $this->belongsTo(Services::class, "service_id");
    }

    public function pricing()
    {
        return $this->belongsTo(ServicesPricings::class, "pricing_id");
    }
}
