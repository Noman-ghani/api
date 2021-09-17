<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Deals extends Model
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
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        "profile_image",
        "servu_url"
    ];

    public function getServuUrlAttribute()
    {
        return env("MARKETPLACE_URL") . "deals/" . $this->slug;
    }

    public function getprofileImageAttribute()
    {
        $path = "uploads/business/deal-{$this->id}/profile.jpeg";

        if (File::exists(base_path("public/{$path}"))) {
            return config("app.url") . $path;
        }
        
        return config("app.url") . "/images/deal-placeholder.png";
    }

    public function getAvailableFromAttribute($value)
    {
        if (request()->has("business")) {
            return Carbon::parse($value)->setTimezone(request()->business->timezone->timezone)->toDateTimeString();
        }
        return $value;
    }

    public function getAvailableUntilAttribute($value)
    {
        if (request()->has("business")) {
            return Carbon::parse($value)->setTimezone(request()->business->timezone->timezone)->toDateTimeString();
        }
        return $value;
    }

    public function business()
    {
        return $this->belongsTo(Businesses::class);
    }

    public function branches()
    {
        return $this->hasMany(DealsBranches::class, "deal_id");
    }

    public function inclusions()
    {
        return $this->hasMany(DealsInclusions::class, "deal_id");
    }

    public function shorturl()
    {
        return $this->hasOne(ShortUrls::class, "type_id")->whereType("deals");
    }

    public function tax()
    {
        return $this->belongsTo(Taxes::class);
    }
}