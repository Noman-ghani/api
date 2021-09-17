<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsCampaignClients extends Model
{
    protected $table = "sms_campaign_clients";
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "campaign_id",
        "client_id",
    ];

    public function client()
    {
        return $this->hasOne(Clients::class,"id", "client_id");
    }
}