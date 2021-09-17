<?php

namespace App\Models;

use App\Helpers\Helpers;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AppointmentHistory extends Model
{
    protected $table = "appointment_history";
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "appointment_id",
        "staff_id",
        "status"
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        "description"
    ];

    public function getDescriptionAttribute()
    {
        $description = null;
        $createdAt = Carbon::parse($this->created_at)->setTimezone("Asia/Karachi");
        $staff = $this->staff;
        $time = $createdAt->format("H:i");
        
        if (request()->business->time_format === "12h") {
            $time = $createdAt->format("h:ia");
        }

        switch ($this->status) {
            case "new":
                if ($this->staff_id) {
                    $description = "Booked by {$staff->full_name} at {$createdAt->format("D")}, {$createdAt->format("d")} {$createdAt->format("M")} {$createdAt->format("Y")} at {$time}";
                } else {
                    $description = "Booked online at {$createdAt->format("D")}, {$createdAt->format("d")} {$createdAt->format("M")} {$createdAt->format("Y")} at {$time}";
                }
                break;
            case "reschedule":
                $description = "Rescheduled by {$staff->full_name} at {$createdAt->format("D")}, {$createdAt->format("d")} {$createdAt->format("M")} {$createdAt->format("Y")} at {$time}";
                break;
            case "confirmed":
                $description = "Confirmed by {$staff->full_name} at {$createdAt->format("D")}, {$createdAt->format("d")} {$createdAt->format("M")} {$createdAt->format("Y")} at {$time}";
                break;
            case "arrived":
                $description = "Arrived by {$staff->full_name} at {$createdAt->format("D")}, {$createdAt->format("d")} {$createdAt->format("M")} {$createdAt->format("Y")} at {$time}";
                break;
            case "started":
                $description = "Started by {$staff->full_name} at {$createdAt->format("D")}, {$createdAt->format("d")} {$createdAt->format("M")} {$createdAt->format("Y")} at {$time}";
                break;
            case "cancelled":
                $cancelReason = Helpers::getAppointmentCancellationReasons($this->appointment->cancel_reason_id);
                $description = "Cancelled by {$staff->full_name} at {$createdAt->format("D")}, {$createdAt->format("d")} {$createdAt->format("M")} {$createdAt->format("Y")} at {$time}<br /><strong>Reason:</strong> {$cancelReason}";
                break;
        }

        return $description;
    }

    public function appointment()
    {
        return $this->belongsTo(Appointments::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}