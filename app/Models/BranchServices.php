<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchServices extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "branch_id",
        "service_id",
        "tax_id"
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

    public function tax()
    {
        return $this->belongsTo(Taxes::class);
    }

    public function branch()
    {
        return $this->hasOne(Branches::class,'id','branch_id');
    }
}
