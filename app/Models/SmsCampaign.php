<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsCampaign extends Model
{
    protected $table = "sms_campaign";
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "business_id",
        "name",
        "message",
        "short_url_id",
        "status",
        "filter",
    ];
}