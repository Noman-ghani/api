<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffShifts extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "branch_id",
        "staff_id",
        "date_start",
        "date_end",
        "day_of_week",
        "repeats",
        "end_repeat",
        "starts_at",
        "ends_at"
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
