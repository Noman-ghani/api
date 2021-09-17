<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class States extends Model
{
    public function cities()
    {
        return $this->hasMany(Cities::class, "state_id");
    }
}
