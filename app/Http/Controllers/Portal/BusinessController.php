<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\Helpers;
use App\Jobs\SendEmailJob;
use App\Models\Businesses;
use App\Models\Countries;
use App\Models\SmsTemplates;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller;

class BusinessController extends Controller
{
    public function signup(Request $request)
    {
        if ($request->has("phone_number")) {
            $request->phone_number = preg_replace("/[^0-9]/", '', $request->phone_number);
        }
        
        $rules = [
            "name" => "required|unique:businesses,name",
            "first_name" => "required",
            "last_name" => "required",
            "phone_country_id" => "required",
            "phone_number" => "required",
            "email" => "required",
            "password" => "required",
            "confirm_password" => "same:password",
            "country_id" => "required",
            "timezone" => "required",
            "privacy_policy" => "required"
        ];

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function($validator) use ($request) {
            if (Staff::emailExists($request->email, "owner")) {
                $validator->errors()->add("email", __("This email has already been taken."));
            }

            $country = Countries::find($request->phone_country_id);
            
            if (!preg_match($country->phone_regex, $request->phone_number)) {
                $validator->errors()->add("phone_number", __("Invalid number."));
            }
            
            if (Staff::phoneNumberExists($request->phone_number, "owner")) {
                $validator->errors()->add("phone_number", __("This phone number has already been taken."));
            }
        });

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();

        try {
            $token = Str::random(32);
            $user = User::whereEmail($request->email)->first();

            if (!$user) {
                $user = new User();
                $user->email = $request->email;
                $user->password = Hash::make($request->password);
                $user->is_email_verified = 0;
                $user->email_verification_token = $token;
                $user->email_verification_expires_on = Carbon::now()->addDay();
                $user->save();
            }

            $slug = Str::slug($request->name, '-');

            // if this slug is already taken, we will append a unique number to make it unique.
            if (Businesses::whereSlug($slug, '-')->exists()) {
                $slug .= '-' . uniqid();
            }

            $business = new Businesses();
            $business->name = $request->name;
            $business->slug = $slug;
            $business->country_id = $request->country_id;
            $business->time_zone_id = $request->timezone;
            $business->time_format = "12h";
            $business->week_start = (string) Carbon::SUNDAY;
            $business->is_tax_inclusive = 0;
            $business->staff_commission_logic = 1;
            $business->is_active = 1;
            $business->is_profile_complete = 0;
            $business->sms_limit = 100;
            $business->subscription_expires_at = Carbon::now()->addMonth()->toDateString() . " 23:59:59";
            $business->subscription_package = "premium";
            $business->save();

            $staff = new Staff();
            $staff->business_id = $business->id;
            $staff->user_id = $user->id;
            $staff->role = "owner";
            $staff->first_name = $request->first_name;
            $staff->last_name = $request->last_name;
            $staff->phone_country_id = $request->phone_country_id;
            $staff->phone_number = $request->phone_number;
            $staff->staff_title = "Owner";
            $staff->enable_appointments = 1;
            $staff->appointment_color = "#A5DFF8";
            $staff->is_active = 1;
            $staff->save();

            SmsTemplates::create([
                "business_id" => $business->id,
                "event" => "reminder_1",
                "send" => "before",
                "minutes" => 60,
                "is_active" => 1
            ]);

            SmsTemplates::create([
                "business_id" => $business->id,
                "event" => "reminder_2",
                "send" => "before",
                "minutes" => 720,
                "is_active" => 0
            ]);

            SmsTemplates::create([
                "business_id" => $business->id,
                "event" => "reminder_2",
                "send" => "before",
                "minutes" => 1440,
                "is_active" => 0
            ]);

            SmsTemplates::create([
                "business_id" => $business->id,
                "event" => "invoice_completed",
                "send" => "immediately",
                "minutes" => null,
                "is_active" => 1
            ]);

            SmsTemplates::create([
                "business_id" => $business->id,
                "event" => "appointment_booking",
                "send" => "immediately",
                "minutes" => null,
                "is_active" => 1
            ]);

            dispatch(new SendEmailJob([$request->email], "Confirm Email Address", "<p>Dear {$request->first_name} {$request->last_name},</p><p>We're happy that you've registered your business at ServU. In order to proceed further, please confirm your email address.</p><p style='text-align: center; padding: 20px 0 0;'><a href='" . env("APP_URL") . "verify-email?token={$token}' style='text-decoration: none; background: #0C56C9; color: #FFFFFF; font-size: 20px; padding: 20px; border-radius: 5px; display: block;'>Confirm Email Address</a></p>"));

            DB::commit();
            return ["success" => true, "message" => __("An email has been sent to your email address for verification.")];
        } catch(\Exception $e) {
            DB::rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getSettings()
    {
        return Businesses::find(Helpers::getJWTData("business_id"));
    }

    public function updateSettings(Request $request)
    {
        $this->validate($request, [
            "website" => "nullable|url",
            "facebook" => "nullable|url",
            "instagram" => "nullable|url",
            "linkedin" => "nullable|url"
        ]);

        $business = Businesses::find(Helpers::getJWTData("business_id"));
        
        if ($request->has("name")) {
            $business->name = $request->name;
        }
        
        if ($request->has("time_zone")) {
            $business->time_zone_id = $request->time_zone;
        }

        if ($request->has("time_format")) {
            $business->time_format = $request->time_format;
        }

        if ($request->has("week_start")) {
            $business->week_start = (string) $request->week_start;
        }

        if ($request->has("website")) {
            $business->website = $request->website;
        }

        if ($request->has("facebook")) {
            $business->facebook = $request->facebook;
        }

        if ($request->has("instagram")) {
            $business->instagram = $request->instagram;
        }

        if ($request->has("linkedin")) {
            $business->linkedin = $request->linkedin;
        }

        if ($request->has("is_profile_complete")) {
            $business->is_profile_complete = $request->is_profile_complete;
        }

        if ($request->has("default_branch_id")) {
            $business->default_branch_id = $request->default_branch_id;
        }

        $business->save();

        return ["success" => true, "message" => __("Settings updated successfully.")];
    }

    public function updateTaxSettings(Request $request)
    {
        $this->validate($request, [
            "is_tax_inclusive" => "required"
        ]);
        
        $business = Businesses::find(Helpers::getJWTData("business_id"));
        $business->is_tax_inclusive = $request->is_tax_inclusive;
        $business->save();

        return ["success" => true, "message" => __("Tax calculation logic has been saved successfully.")];
    }

    public function updateSalesSettings(Request $request)
    {
        $this->validate($request, [
            "staff_commission_logic" => "required"
        ]);
        
        $business = Businesses::find(Helpers::getJWTData("business_id"));
        $business->staff_commission_logic = $request->staff_commission_logic;
        $business->save();

        return ["success" => true, "message" => __("Sales settings updated successfully.")];
    }
}