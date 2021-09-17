<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\Helpers;
use App\Jobs\SendEmailJob;
use App\Jobs\SendSmsJob;
use App\Models\Businesses;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserController extends Controller
{
    public function test()
    {
        dispatch(new SendSmsJob(1, 923222353116, "Hi John, we are looking forward to seeing you on 15/09 at 04:00 pm. To Confirm, reply with SU5481, or SU5480 to cancel, or call 923222353116."));
    }
    
    public function login(Request $request)
    {
        $this->validate($request, [
            "email" => "required|email",
            "password" => "required"
        ]);
        
        if (! $token = Auth::attempt(["email" => $request->email, "password" => $request->password])) {
            return response(__("Invalid email or password."), 422);
        }
        
        $user = Auth::user();
        if (!$user->is_email_verified) {
            return response(__("You have not verified your email yet."), 422);
        }
        $staff = Staff::where("role", "owner")->whereUserId($user->id)->firstOrFail();

        return [
            "token" => $token,
            "token_type" => "bearer",
            "expires_in" => Auth::factory()->getTTL() * 1,
            "message" => __("Welcome, " . $staff->first_name . " " . $staff->last_name)
        ];
    }

    public function user()
    {
        $user = Auth::user();
        $staff = Staff::where("role", "owner")->whereUserId($user->id)->firstOrFail();
        $business = Businesses::with(["country", "timezone"])->find(Helpers::getJWTData("business_id"));
        $colorList = Helpers::getColorList();
        $durations = Helpers::getDurations();
        
        return [
            "staff" => $staff,
            "business" => $business,
            "settings" => [
                "colors" => $colorList,
                "durations" => $durations
            ]
        ];
    }

    public function forgotPassword(Request $request)
    {
        $this->validate($request, [
            "email" => "required|email|exists:users"
        ]);
        $user = User::whereEmail($request->email)->firstOrFail();

        try {
            $token = Str::random(32);
            $user->reset_password_token = $token;
            $user->reset_password_expires_on = Carbon::now()->addMinutes(30);
            $user->save();

            $staff = Staff::whereUserId($user->id)->whereRole("owner")->firstOrFail();
            dispatch(new SendEmailJob([$request->email], "Reset Password", "<p style='margin: 0; font-size: 1.4em; color: #000000; font-weight: bold;'>Hi {$staff->full_name}, reset your log in password</p></td></tr><tr><td style='padding: 16px 0;'><p style='margin: 15px 0; font-size: 1em; color: #000000; line-height: 1.5em;'>There was a request to securely reset your login password, click the button below to continue.</p></td></tr><tr><td><a href='" . env('PORTAL_URL') . "auth/reset-password/{$token}' style='font-size: 1.05em; line-height: 1.4em; background-color: #0C56C9; display: inline-block; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold; padding: 13px 24px 15px;'>Reset Password</a>"));
        } catch (\Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }

        return ["success" => true, "message" => __("An e-mail has been sent on your e-mail address with the reset password instructions.")];
    }

    public function resetPassword($token)
    {
        $user = User::whereResetPasswordToken($token)->whereRaw("NOW() < reset_password_expires_on")->first();

        if (!$user) {
            throw new NotFoundHttpException();
        }

        return redirect(env("PORTAL_URL") . "reset-password?token={$token}");
    }

    public function checkTokenExpiry($type, $token)
    {
        $user = null;

        if ($type === "reset-password") {
            $user = User::whereResetPasswordToken($token)->whereRaw("NOW() < reset_password_expires_on")->first();
        }

        if (!$user) {
            return ["success" => false, "message" => "Token has expired. Please request a new one."];
        }

        return ["success" => true];
    }

    public function changePassword(Request $request, $token)
    {
        $this->validate($request, [
            "new_password" => "required",
            "confirm_new_password" => "same:new_password"
        ]);
        
        $user = User::whereResetPasswordToken($token)->whereRaw("NOW() < reset_password_expires_on")->first();

        if (!$user) {
            return ["success" => false, "message" => __("This link has expired. Please request a new one.")];
        }

        $user->reset_password_token = null;
        $user->reset_password_expires_on = null;
        $user->password = Hash::make($request->new_password);
        $user->save();

        return ["success" => true, "message" => __("Password changed successfully. Please login to continue.")];
    }

    public function profile()
    {
        $user = Auth::user();
        $staff = Staff::whereBusinessId(Helpers::getJWTData("business_id"))->whereUserId($user->id)->firstOrFail();
        
        return [
            "user" => $user,
            "staff" => $staff
        ];
    }

    public function updateProfile(Request $request)
    {
        $rules = [
            "first_name" => "required",
            "last_name" => "required",
            "email" => "required|email",
            "phone_country_id" => "required",
            "phone_number" => "required",
            "password" => "required_with:new_password",
            "new_password" => ["required_with:password"],
            "confirm_password" => "required_with:password|same:new_password"
        ];

        $validator = Validator::make($request->all(), $rules);
        $staff = Staff::whereBusinessId(Helpers::getJWTData("business_id"))->whereUserId(Auth::user()->id)->firstOrFail();

        $validator->after(function($validator) use ($request, $staff) {
            if (Staff::phoneNumberExists($request->phone_number, "owner", $staff->id)) {
                $validator->errors()->add("phone_number", __("Phone number already used"));
            }
            
            if (Staff::emailExists($request->email, "owner", $staff->id)) {
                $validator->errors()->add("email", __("Email address already used"));
            }
            
            if ($request->password) {
                $auth = Auth::user();

                if (!Hash::check($request->password, $auth->password)) {
                    $validator->errors()->add("password", __("Password is incorrect"));
                } else if ($request->password == $request->new_password) {
                    $validator->errors()->add("new_password", __("New Password cannot be the same as old password."));
                }
            }
        });
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $staff->first_name = $request->first_name;
        $staff->last_name = $request->last_name;

        if ($request->password) {
            $user = User::find(Auth::user()->id);
            $user->password = Hash::make($request->new_password);
            $user->save();
        }

        $staff->save();
        
        return ["success" => true, "message" => __("Profile updated successfully.")];
    }

    public function verifyEmail(Request $request)
    {
        $this->validate($request, [
            "token" => "required"
        ]);

        $user = User::whereEmailVerificationToken($request->token)->first();

        if (!$user || Carbon::now()->gte($user->email_verification_expires_on)) {
            return redirect(env("PORTAL_URL") . "auth/login?status=2");
        }

        $user->is_email_verified = 1;
        $user->email_verified_on = Carbon::now();
        $user->email_verification_token = null;
        $user->email_verification_expires_on = null;

        $user->save();

        return redirect(env("PORTAL_URL") . "auth/login?status=1");
    }

    public function contactSupport(Request $request)
    {
        $this->validate($request, [
            "subject" => "required",
            "message" => "required"
        ]);

        $staff = Staff::whereUserId(Auth::user()->id)->firstOrFail();

        $html = "<p style='margin: 0; font-size: 28px; font-weight: 800; line-height: 35px;'>New Support Ticket</p>
        <div style='background: #DDDDDD; height: 1px; margin: 20px 0;'></div>
        <p style='margin: 0;'>You have received a new support ticket from {$staff->full_name}.</p>
        <p style='margin: 30px 0 0 0;'><strong>{$request->subject}</strong></p>
        <p style='margin: 15px 0 0 0; line-height: 27px;'>" . nl2br($request->message) . "</p>";

        dispatch(new SendEmailJob(explode(',', env("SUPPORT_EMAIL_ADDRESSES")), "Issue reported by {$request->business->name}", $html));
        return ["success" => true, "message" => __("Email sent to support successfully. Your query will be answered soon.")];
    }
}