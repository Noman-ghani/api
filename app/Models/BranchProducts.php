<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchProducts extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "branch_id",
        "product_id",
        "tax_id",
        "stock_on_hand"
    ];

    public function tax()
    {
        return $this->belongsTo(Taxes::class);
    }
}