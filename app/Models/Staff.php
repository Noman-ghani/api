<?php

namespace App\Models;

use App\Helpers\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Staff extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "business_id",
        "user_id"
    ];

    /**
     * The attributes excluded from the model"s JSON form.
     *
     * @var array
     */
    protected $hidden = [
        "business_id"
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
     protected $appends = [
        "short_name",
        "full_name",
        "profile_image"
    ];

    public function getShortNameAttribute()
    {
        return $this->first_name[0];
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getprofileImageAttribute()
    {
        $profileImage = "uploads/business/staff/{$this->id}.jpeg";

        if (File::exists(base_path("public/{$profileImage}"))) {
            return config("app.url") . $profileImage;
        }
        
        return null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function phone_country()
    {
        return $this->belongsTo(Countries::class, "phone_country_id");
    }

    public function branches()
    {
        return $this->hasMany(StaffBranches::class);
    }

    public function services()
    {
        return $this->hasMany(StaffServices::class);
    }

    public static function emailExists($email, $role, $id = null)
    {
        $user = User::where("email", $email)->first();

        if ($user) {
            $staff = self::where("user_id", $user->id);
            
            if (request()->header("authorization")) {
                $staff = $staff->where("business_id", Helpers::getJWTData("business_id"));
            }

            $staff = $staff->where("role", $role);
            $staff = $staff->where("id", "<>", $id);
            
            return $staff->exists();
        }

        return false;
    }

    public static function phoneNumberExists($phone_number, $role, $id = null)
    {
        $staff = self::where("phone_number", $phone_number);
            
        if (request()->header("authorization")) {
            $staff = $staff->where("business_id", Helpers::getJWTData("business_id"));
        }

        $staff = $staff->where("role", $role);
        $staff = $staff->where("id", "<>", $id);
        
        return $staff->exists();
    }
}
