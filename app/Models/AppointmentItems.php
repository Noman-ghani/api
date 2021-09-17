<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentItems extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "appointment_id",
        "service_id",
        "start_time",
        "end_time",
        "duration",
        "staff_id",
        "price"
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

    public function appointment()
    {
        return $this->belongsTo(Appointments::class);
    }

    public function service()
    {
        return $this->belongsTo(Services::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}