<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\Helpers;
use App\Models\SmsTemplates;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;

class SmsTemplatesController extends Controller
{
    public function browse()
    {
        return SmsTemplates::whereBusinessId(Helpers::getJWTData("business_id"))->get();
    }

    public function store(Request $request, $id = null)
    {
        $this->validate($request, [
            "event" => "required",
            "send" => "required",
            "time_variant" => "required",
            "minutes" => "required"
        ]);
        
        $smsTemplate = SmsTemplates::firstOrNew(["business_id" => Helpers::getJWTData("business_id"), "event" => $request->event, "id" => $id]);
        
        if ($request->time_variant === "minutes") {
            $smsTemplate->minutes = $request->duration;
        } else if ($request->time_variant === "hours") {
            $smsTemplate->minutes = $request->duration * 60;
        } else if ($request->time_variant === "days") {
            $smsTemplate->minutes = $request->duration * 1440;
        }

        $smsTemplate->is_active = $request->is_active;
        $smsTemplate->save();

        return ["success" => true, "message" => __("Sms template saved successfully")];
    }
}