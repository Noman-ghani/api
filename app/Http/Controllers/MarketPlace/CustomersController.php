<?php

namespace App\Http\Controllers\MarketPlace;

use App\Helpers\Helpers;
use App\Models\Customers;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomersController extends Controller
{
    
    public function __construct(Customers $customers)
    {
        $this->customers = $customers;
    }

    public function login(Request $request)
    {
        $TMP_validation = [
            "email" => "required|email"
        ];
        
        if (empty($request->strategy) || $request->strategy == null) {
            $TMP_validation['password'] = 'required';
        }
        
        $validator = Validator::make($request->all(), $TMP_validation);

        if ($validator->fails()) {
            
            return response()->json(['errors' => $validator->messages()], 422);

        } else {
            //check strategy for login
            if ($request->strategy == 'facebook' || $request->strategy == 'google') {
                $user = $this->customers->where('email',$request->email)->first();
                if (!$user || empty($user)) {
                    if($request->strategy == 'facebook'){
                        $user = $this->customers->create([
                            "first_name" => $request->first_name,
                            "last_name" => $request->last_name,
                            "email" => $request->email,
                            "birthday" => date('Y-m-d H:i:s' , strtotime($request->birthday)),
                            "gender" => $request->gender,
                            "from_facebook" => 1,
                            "is_email_verified" => 1
                        ]);
                    }
                    if($request->strategy == 'google'){
                        $user = $this->customers->create([
                            "first_name" => $request->given_name,
                            "last_name" => $request->family_name,
                            "email" => $request->email,
                            "birthday" => date('Y-m-d H:i:s' , strtotime($request->birthday)),
                            "gender" => $request->gender,
                            "from_google" => 1,
                            "is_email_verified" => 1
                        ]);
                    }
                }
                // set token for user
                $token = Auth::guard('marketPlace')->login($user);
            
            }
             else {
                
                if (! $token = Auth::guard('marketPlace')->attempt(["email" => $request->email, "password" => $request->password])) {
                    return response(__("Invalid email or password."), 422);
                }
            }

            $user = Auth::guard('marketPlace')->user();

            if (!$user->is_email_verified) {
                return response(__("You have not verified your email yet."), 422);
            }

            $customers = $this->customers->where("id", $user->id)->first();

            return [
                "token" => $token,
                "token_type" => "bearer",
                "expires_in" => Auth::guard('marketPlace')->factory()->getTTL() * 1,
                "message" => __("Welcome, " . $customers->first_name . " " . $customers->last_name)
            ];
        }
    }

    public function forgotPassword(Request $request)
    {
        $this->validate($request, [
            "email" => "required|email|exists:customers"
        ]);
        $user = $this->customers->whereEmail($request->email)->firstOrFail();

        try {
            $token = Str::random(32);
            $user->reset_password_token = $token;
            $user->reset_password_expires_on = Carbon::now()->addMinutes(30);
            $user->save();

            Mail::send([], [], function ($message) use($request, $user, $token) {
                $message->to($request->email);
                $message->subject(__("ServU - Reset Password"));
                $message->setBody("<table align='center' width='600' style='border-spacing: 0; border-collapse: collapse; border: solid 1px #D5D7DA; line-height: 1.5em; font-family: Arial;'><tr><td style='padding: 30px 24px;'><table width='100%' style='border-spacing: 0; border-collapse: collapse;'><tr><td><p style='margin: 0; font-size: 1.4em; color: #000000; font-weight: bold;'>Hi {$user->full_name}, reset your log in password</p></td></tr><tr><td style='padding: 16px 0;'><p style='margin: 0; font-size: 1em; color: #000000; line-height: 1.5em;'>There was a request to securely reset your login password, click the button below to continue.</p></td></tr><tr><td><a href='" . env('MARKETPLACE_URL') . "login/reset-password?token={$token}' style='font-size: 1.05em; line-height: 1.4em; background-color: #0C56C9; display: inline-block; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold; padding: 13px 24px 15px;'>Reset Password</a></td></tr></table></td></tr></table>", "text/html");
            });
        } catch (\Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }

        return ["success" => true, "message" => __("An e-mail has been sent on your e-mail address with the reset password instructions.")];
    }

    public function resetPassword($token)
    {
        $user = $this->customers->whereResetPasswordToken($token)->whereRaw("NOW() < reset_password_expires_on")->first();

        if (!$user) {
            throw new NotFoundHttpException();
        }

        return redirect(env("MARKETPLACE_URL") . "reset-password?token={$token}");
    }

    public function checkTokenExpiry($type, $token)
    {
        $user = null;

        if ($type === "reset-password") {
            $user = $this->customers->whereResetPasswordToken($token)->whereRaw("NOW() < reset_password_expires_on")->first();
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
        
        $user = $this->customers->whereResetPasswordToken($token)->whereRaw("NOW() < reset_password_expires_on")->first();

        if (!$user) {
            return ["success" => false, "message" => __("This link has expired. Please request a new one.")];
        }

        $user->reset_password_token = null;
        $user->reset_password_expires_on = null;
        $user->password = Hash::make($request->new_password);
        $user->save();

        return ["success" => true, "message" => __("Password changed successfully. Please login to continue.")];
    }

    public function redirectToGoogle()
    {
        return response()->json([
            'redirectUrl' => Socialite::driver('google')->stateless()->redirect()->getTargetUrl()
        ]);
    }
      
    public function handleGoogleCallback(Request $request)
    {   
        try{
            
            if($request->has("error")){
                return redirect(env("MARKETPLACE_URL").'signup/form?googlepermissionerr='.$request->error_description);
            }
            
            $user = Socialite::driver('google')->stateless()->user();


            $finduser = $this->customers::where('email', $user->email)->first();
                
            if($finduser){
     
                $token = Auth::guard('marketPlace')->login($finduser);
     
            }else{

                $newUser = $this->customers->create([
                    "first_name" => $user->user["given_name"],
                    "last_name" => $user->user["family_name"],
                    "email" => $user->user["email"],
                    "birthday" => Arr::exists($user->user, 'birthday') ? date('Y-m-d H:i:s' , strtotime($user->user["birthday"])) : null,
                    "from_google" => 1,
                    "is_email_verified" => 1
                ]);
    
                $token = Auth::guard('marketPlace')->login($newUser);
    
            }

            $user = Auth::guard('marketPlace')->user();

            // $customers = $this->customers->where("id", $user->id)->first();
            if(!empty($user)){
                return redirect(env("MARKETPLACE_URL")."?token=". $token."&token_type=bearer&expires_in=".Auth::guard('marketPlace')->factory()->getTTL() * 9999999 . "&login=".true);
            }

        }catch(\Exception $e){

            $err_msg = str_replace(" ","-",$e->getMessage());
            return redirect(env("MARKETPLACE_URL").'signup/form?googleerr='.$err_msg);
        }
        
    }

    public function redirectToFacebook()
    {
        return response()->json([
            'redirectUrl' => Socialite::driver('facebook')->fields([
                'first_name', 'last_name', 'email', 'gender', 'birthday'
            ])->scopes([
                'email', 'user_birthday'
            ])->stateless()->redirect()->getTargetUrl()
        ]);
    }
      
    public function handleFacebookCallback(Request $request)
    {
        try{

            if($request->has("error")){
                return redirect(env("MARKETPLACE_URL").'signup/form?fbpermissionerr='.$request->error_description);
            }

            $user = Socialite::driver('facebook')->fields([
                'first_name', 'last_name', 'email', 'gender', 'birthday'
            ])->stateless()->user();

            $finduser = $this->customers::where('email', $user->email)->first();
     
            if($finduser){
     
                $token = Auth::guard('marketPlace')->login($finduser);
     
            }else{
                $newUser = $this->customers->create([
                    "first_name" => $user->user["first_name"],
                    "last_name" => $user->user["last_name"],
                    "email" => $user->user["email"],
                    "birthday" => Arr::exists($user->user, 'birthday') ? date('Y-m-d H:i:s' , strtotime($user->user["birthday"])) : null,
                    "from_facebook" => 1,
                    "is_email_verified" => 1
                ]);
    
                $token = Auth::guard('marketPlace')->login($newUser);
    
            }

            $user = Auth::guard('marketPlace')->user();
            if(!empty($user)){
                return redirect(env("MARKETPLACE_URL")."?token=". $token."&token_type=bearer&expires_in=".Auth::guard('marketPlace')->factory()->getTTL() * 9999999 . "&login=".true);
            }

        }catch(\Exception $e){
            $err_msg = str_replace(" ","-",$e->getMessage());
            return redirect(env("MARKETPLACE_URL").'signup/form?fberr='.$err_msg);
        }
        
    }
    
    public function user()
    {
        $user = Auth::guard('marketPlace')->user();
        $customers = $this->customers->with("timezone")->where("id", $user->id)->first();
        $customers["durations"] = Helpers::getDurations();

        return [
            "user" => $customers
        ];
    }

    public function store(Request $request, $id = null)
    {
        if ($request->has("phone_number")) {
            $request->phone_number = preg_replace("/[^0-9]/", '', $request->phone_number);
        }
        
        $rules = [
            "first_name" => "required",
            "last_name" => "required",
            "phone_country_id" => "required",
            "phone_number" => "required"
        ];

        if(empty($id)){
            $rules['email'] = "email";
            $rules['password'] = "required";
            $rules['privacy_policy'] = "required";
        }
        
        $validator = Validator::make($request->all(), $rules);
        
        if(empty($id)){
            $validator->after(function($validator) use ($request) {
                if ($this->customers->emailExists($request->email)) {
                    $validator->errors()->add("email", __("This email has already been taken."));
                }
            });
        }

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $token = Str::random(32);
        
        $customer = $this->customers->firstOrNew(["id" => $id]);
        
        $customer->first_name = $request->first_name;
        $customer->last_name = $request->last_name;
        $customer->email = $request->email;
        $customer->phone_country_id = $request->phone_country_id;
        $customer->phone_number = $request->phone_number;
        $customer->birthday = $request->birthday;
        $customer->gender = $request->gender;

        if($request->has('password')){
            $customer->password = Hash::make($request->password);
            $customer->is_email_verified = 0;
            $customer->email_verification_token = $token;
            $customer->email_verification_expires_on = Carbon::now()->addDay();
        }

        $customer->save();
        if(!empty($id)){
            return [
                "success" => true,
                "message" => __("Profile has been updated."),
                "user" => $customer
            ]; 
        }
        try {
            Mail::send([], [], function ($message) use ($token, $request) {
                $message->to($request->email);
                $message->subject("ServU - Email Verification");
                $message->setBody("<table style='margin: auto; width: 600px;'><tbody><tr><td style='background: #FFFFFF; font-family: arial; font-size: 20px; line-height: 30px; border: 1px solid #DDDDDD; padding: 20px;'><p>Dear " . $request->first_name . " " . $request->last_name . ",</p><p>We're happy that you've registered at ServU. In order to proceed further, please confirm your email address.</p><p style='text-align: center; padding: 20px 0;'><a href='" . env("APP_URL") . "verify-marketplace-email?token=" . $token . "' style='text-decoration: none; background: #3c8dbc; color: #FFFFFF; font-size: 20px; padding: 20px; border-radius: 5px; display: block;'>Verify Now</a></p><p style='padding-bottom: 20px;'>Welcome to ServU!<br />The ServU Team</p><p style='padding-top: 40px; text-align: center; border-top: 1px solid #AAAAAA;'>Did you receive this email without signing up? <a href='javascript:;'>Click Here</a><br />This verification link will expire in 24 hours.</p><p style='padding-top: 40px; text-align: center; font-size: 12px;'>Copyright &copy; " . date("Y") . " <a href='https://www.servuapp.com'>Servu</a><br /><span style='text-align: center; font-size: 12px; color: #AAAAAA;'>This is an automatically generated message; please do not reply to this email.</span></p></td></tr></tbody></table>", "text/html");
            });

            return [
                "success" => true,
                "message" => __("An email has been sent to your email address for verification.")
            ];
        } catch (\Exception $e) {
            return [
                "success" => false,
                "message" => __("An error occurred while sending email. Please contact administrator.")
            ];
        }
    }

    public function addphone(Request $request, $id)
    {
        if ($request->has("phone_number")) {
            $request->phone_number = preg_replace("/[^0-9]/", '', $request->phone_number);
        }

        $rules = [
            "phone_country_id" => "required",
            "phone_number" => "required"
        ];
        
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        $customer = $this->customers->firstOrNew(["id" => $id]);
        $customer->time_zone_id = $request->timezone;
        $customer->phone_country_id = $request->phone_country_id;
        $customer->phone_number = $request->phone_number;
        $customer->save();
        
        return ["success" => true, "message" => __("Phone number have been successfully added."), "user" => $customer];

    }

    public function updateProfileImage(Request $request, $id = null){
        $rules = [
            "profile_image" => "required"
        ];
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }


        if($request->has('profile_image') && $id){
            Helpers::createDirectoryAndUploadMedia("customers/customer-{$id}", $request->profile_image, "profile"); 
        }

        return ["success" => true, "message" => __("Profile Image has been Updated Successfully.")];
    }

    public function verifyEmail(Request $request)
    {
        $this->validate($request, [
            "token" => "required"
        ]);

        $user = $this->customers->whereEmailVerificationToken($request->token)->first();

        if (!$user || Carbon::now()->gte($user->email_verification_expires_on)) {
            return redirect(env("MARKETPLACE_URL") . "login?status=2");
        }

        $user->is_email_verified = 1;
        $user->email_verified_on = Carbon::now();
        $user->email_verification_token = null;
        $user->email_verification_expires_on = null;

        $user->save();

        return redirect(env("MARKETPLACE_URL") . "login?status=1");
    }
}