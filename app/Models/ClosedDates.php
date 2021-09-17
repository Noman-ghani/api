<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ClosedDates extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "business_id"
    ];

    public function getStartsAtAttribute($value)
    {
        if (request()->has("business")) {
            return Carbon::parse($value)->setTimezone(request()->business->timezone->timezone)->toDateTimeString();
        }
        return $value;
    }

    public function getEndsAtAttribute($value)
    {
        if (request()->has("business")) {
            return Carbon::parse($value)->setTimezone(request()->business->timezone->timezone)->toDateTimeString();
        }
        return $value;
    }

    public function branches()
    {
        return $this->hasMany(ClosedDatesBranches::class, "closed_dates_id");
    }
}