<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShortUrls extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "type",
        "type_id",
        "url_code",
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        "url"
    ];

    public function getUrlAttribute()
    {
        return env("APP_URL") . $this->url_code;
    }

    public function branch()
    {
        return $this->belongsTo(Branches::class);
    }

    public function deal()
    {
        return $this->belongsTo(Deals::class);
    }
}