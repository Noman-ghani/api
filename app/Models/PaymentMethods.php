<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class PaymentMethods extends Model
{
    /**
     * The attributes excluded from the model"s JSON form.
     *
     * @var array
     */
    protected $hidden = [
        "is_active",
        "created_at",
        "updated_at"
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        "image"
    ];

   public function getImageAttribute()
    {
        $name = strtolower($this->title);
        $path = "images/payment_methods/{$name}.png";

        if($this->title == "EasyPaisa"){
            $path = "images/payment_methods/easypaisa-wallet.png";
        }


        if (File::exists(base_path("public/{$path}"))) {
            return config("app.url") . $path;
        }
        return null;
        
    }

    public function countries()
    {
        return $this->hasMany(PaymentMethodsCountries::class, "payment_method_id");
    }
}
