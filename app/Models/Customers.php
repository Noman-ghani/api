<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\File;

class Customers extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "first_name",
        "last_name",
        "phone_country_id",
        "phone_number",
        "email",
        "birthday",
        "gender",
        "from_facebook",
        "from_google",
        "is_email_verified",
        "password",
        "email_verification_token",
        "email_verification_expires_on"
    ];
    
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        "full_name",
        "profile_image",
        "is_completed"
    ];

    public function getIsCompletedAttribute()
    {
        if(!empty($this->first_name) && !empty($this->last_name) && !empty($this->email) && !empty($this->phone_number) && !empty($this->birthday) && !empty($this->gender)){
            return true;
        }
        return false;
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getprofileImageAttribute()
    {
        $path = "uploads/customers/customer-{$this->id}/profile.jpeg";

        if (File::exists(base_path("public/{$path}"))) {
            return config("app.url") . $path;
        }
        
        return null;
    }
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */

    public function timezone()
    {
        return $this->belongsTo(Timezones::class, "time_zone_id");
    }
    
    public function getJWTCustomClaims()
    {
        return [];
    }

    public static function emailExists($email)
    {
        $user = Customers::where("email", $email)->first();
    
        if ($user) {
            $customer = self::where("id", $user->id);
            
            return $customer->exists();
        }

        return false;
    }
}
