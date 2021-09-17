<?php

namespace App\Models;

use App\Helpers\Helpers;
use Illuminate\Database\Eloquent\Model;

class Clients extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "business_id"
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
        "full_name"
    ];

    public function getFullNameAttribute()
    {
       return $this->first_name . ' ' . $this->last_name;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function phone_country()
    {
        return $this->belongsTo(Countries::class, "phone_country_id");
    }

    public function state()
    {
        return $this->belongsTo(States::class);
    }

    public function customers()
    {
        return $this->belongsTo(Customers::class);
    }

    public function city()
    {
        return $this->belongsTo(Cities::class);
    }

    public function deals()
    {
        return $this->hasMany(ClientDeals::class, "client_id");
    }

    public function invoices()
    {
        return $this->hasMany(Invoices::class, "client_id");
    }

    public function appointments()
    {
        return $this->hasMany(Appointments::class, "client_id");
    }

    public static function emailExists($email, $id = null)
    {
        if (!$email) {
            return false;
        }

        $staff = self::where("id", "<>", $id)->whereEmail($email);

        if (request()->header("authorization")) {
            $staff = $staff->whereBusinessId(Helpers::getJWTData("business_id"));
        }
        
        return $staff->exists();
    }

    public static function phoneNumberExists($phoneNumber, $id = null)
    {
        if (!$phoneNumber) {
            return false;
        }
        
        $staff = self::where("id", "<>", $id)->wherePhoneNumber($phoneNumber);

        if (request()->header("authorization")) {
            $staff = $staff->whereBusinessId(Helpers::getJWTData("business_id"));
        }
        
        return $staff->exists();
    }
}