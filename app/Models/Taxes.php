<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Taxes extends Model
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

    public function tax_1()
    {
        return $this->belongsTo(Taxes::class, "tax_1");
    }

    public function tax_2()
    {
        return $this->belongsTo(Taxes::class, "tax_2");
    }
    
    public function tax_3()
    {
        return $this->belongsTo(Taxes::class, "tax_3");
    }
}
