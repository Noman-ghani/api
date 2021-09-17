<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffBranches extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "staff_id",
        "branch_id"
    ];

    public function staff()
    {
        return $this->hasOne(Staff::class, "id", "staff_id");
    }
}
