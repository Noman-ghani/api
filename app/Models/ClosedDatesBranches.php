<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClosedDatesBranches extends Model
{
    protected $fillable = [
        "closed_dates_id",
        "branch_id"
    ];

    public function branch()
    {
        return $this->belongsTo(Branches::class);
    }
}