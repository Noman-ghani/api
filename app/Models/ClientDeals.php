<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientDeals extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes excluded from the model"s JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    public function deal()
    {
        return $this->belongsTo(Deals::class, "deal_id");
    }
    
    public function items()
    {
        return $this->hasMany(ClientDealsItems::class, "client_deal_id");
    }

    public function invoice()
    {
        return $this->belongsTo(Invoices::class);
    }
}