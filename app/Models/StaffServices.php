<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffServices extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "staff_id",
        "service_id"
    ];

    /**
     * The attributes excluded from the model"s JSON form.
     *
     * @var array
     */
    protected $hidden = [
        "id",
        "created_at",
        "updated_at"
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, "staff_id");
    }
}
