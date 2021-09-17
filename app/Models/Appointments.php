<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointments extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "business_id",
        "branch_id",
        "booking_date"
    ];

    /**
     * The attributes excluded from the model"s JSON form.
     *
     * @var array
     */
    protected $hidden = [
        "business_id"
    ];

    public function items()
    {
        return $this->hasMany(AppointmentItems::class, "appointment_id");
    }

    public function history()
    {
        return $this->hasMany(AppointmentHistory::class, "appointment_id");
    }

    public function branch()
    {
        return $this->belongsTo(Branches::class);
    }

    public function business()
    {
        return $this->belongsTo(Businesses::class);
    }

    public function client()
    {
        return $this->belongsTo(Clients::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoices::class);
    }
}