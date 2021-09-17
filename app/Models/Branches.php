<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Branches extends Model
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

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        "profile_image",
        "servu_url"
    ];

    public function getprofileImageAttribute()
    {
        $branchImage = "uploads/business/branch-{$this->id}/profile.jpeg";
        $businessImage = "uploads/business/{$this->business_id}/profile.jpeg";

        if (File::exists(base_path("public/{$branchImage}"))) {
            return config("app.url") . $branchImage;
        } else if (File::exists(base_path("public/{$businessImage}"))) {
            return config("app.url") . $businessImage;
        }
        
        return config("app.url") . "/images/business-placeholder.png";
    }

    public function getServuUrlAttribute()
    {
        $business = Businesses::find($this->business_id);
        return env("MARKETPLACE_URL") . "b/" . $business->slug . '/' . $this->slug;
    }

    public function business()
    {
        return $this->belongsTo(Businesses::class);
    }

    public function phone_country()
    {
        return $this->belongsTo(Countries::class, "phone_country_id");
    }

    public function state()
    {
        return $this->belongsTo(States::class);
    }

    public function city()
    {
        return $this->belongsTo(Cities::class);
    }

    public function product_tax()
    {
        return $this->belongsTo(Taxes::class, "product_tax_id");
    }

    public function service_tax()
    {
        return $this->belongsTo(Taxes::class, "service_tax_id");
    }

    public function getInvoiceNumber()
    {
        return ($this->invoice_prefix ? $this->invoice_prefix . '-' : '') . $this->next_invoice_number;
    }

    public function staff()
    {
        return $this->hasMany(StaffBranches::class, "branch_id");
    }

    public function timings()
    {
        return $this->hasMany(BranchTimings::class, "branch_id");
    }

    public function products()
    {
        return $this->hasMany(BranchProducts::class, "branch_id");
    }

    public function services()
    {
        return $this->hasMany(BranchServices::class, "branch_id");
    }

    public function shorturl()
    {
        return $this->hasOne(ShortUrls::class, "type_id")->whereType("branch");
    }
}
