<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsCampaignLogs extends Model
{
    protected $table = "sms_campaign_logs";
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "campaign_id",
    ];

    public function client()
    {
        return $this->hasOne(SmsCampaign::class,"id", "campaign_id");
    }
}