<?php

namespace App\Models;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SmsTemplates extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "business_id",
        "event",
        "send",
        "minutes",
        "is_active"
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        "title",
        "description",
        "time_details",
        "template"
    ];

    public function getTitleAttribute()
    {
        return Str::title(str_replace('_', ' ', $this->event));
    }

    public function getDescriptionAttribute()
    {
        if (!$this->minutes) {
            return "Immediately";
        }
        
        $description = CarbonInterval::minutes($this->minutes)->cascade()->forHumans();

        if ($this->send === "before") {
            $description .= " before";
        } else if ($this->send === "after") {
            $description .= " after";
        }
        
        return $description;
    }

    public function getTimeDetailsAttribute()
    {
        return CarbonInterval::minutes($this->minutes)->cascade();
    }

    public function getTemplateAttribute()
    {
        $template = null;
        if (in_array($this->event, ["reminder_1", "reminder_2", "reminder_3"])) {
            $template = "Hi [[CLIENT_FIRST_NAME]],<br />This is to inform you that you have an appointment on [[BOOKING_DATE]] at [[BOOKING_TIME]].<br /><br />To cancel, reply with [[CANCEL_REPLY_CODE]]<br /><br />[[BUSINESS_NAME]]";
        } else if ($this->event === "invoice_completed") {
            $template = "Hi [[CLIENT_FIRST_NAME]],<br /><br />Thank you for visiting [[BUSINESS_NAME]].<br />Your invoice has been generated successfully.";
        } else if ($this->event === "appointment_booking") {
            $template = "Hi [[CLIENT_FIRST_NAME]],<br /><br />We are looking forward to seeing you on [[BOOKING_DATE]] at [[BOOKING_TIME]].<br /><br />Reply with [[CONFIRM_REPLY_CODE]] to confirm or [[CANCEL_REPLY_CODE]] to cancel<br /><br />[[BUSINESS_NAME]]";
        }
        return $template;
    }
}