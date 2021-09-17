<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealsBranches extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "deal_id",
        "branch_id"
    ];

    /**
     * The attributes excluded from the model"s JSON form.
     *
     * @var array
     */
    protected $hidden = [
        "id",
        "created_at",
        "updated_at"
    ];

    public function branch()
    {
        return $this->belongsTo(Branches::class);
    }

}