<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchTimings extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "branch_id",
        "day_of_week",
        "is_closed",
        "starts_at",
        "ends_at"
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        "weekday_text"
    ];

    public function getWeekdayTextAttribute()
    {
        switch ($this->day_of_week) {
            case 0:
                return "Sunday";
            case 1:
                return "Monday";
            case 2:
                return "Tuesday";
            case 3:
                return "Wednesday";
            case 4:
                return "Thursday";
            case 5:
                return "Friday";
            case 6:
                return "Saturday";
        }
    }
}