<?php

namespace App\Models\Inventory;

use App\Models\Branches;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    protected $table = "inventory_history";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        
    ];

    /**
     * The attributes excluded from the model"s JSON form.
     *
     * @var array
     */
    protected $hidden = [
        "business_id"
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branches::class);
    }
}