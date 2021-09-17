<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Businesses extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes excluded from the model"s JSON form.
     *
     * @var array
     */
    
     /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        "profile_image"
    ];

   public function getprofileImageAttribute()
    {
        $businessImage = "uploads/business/{$this->id}/profile.jpeg";

        if (File::exists(base_path("public/{$businessImage}"))) {
            return config("app.url") . $businessImage;
        }
        
        return config("app.url") . "/images/business-placeholder.png";
    }

    public function country()
    {
        return $this->belongsTo(Countries::class);
    }

    public function timezone()
    {
        return $this->belongsTo(Timezones::class, "time_zone_id");
    }

    public function services()
    {
        return $this->hasMany(Services::class, "business_id");
    }
}
