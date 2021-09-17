<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpensesSummary extends Model
{
    protected $table = "expenses_summary";
    
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

    public function branch()
    {
        return $this->belongsTo(Branches::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
