<?php

namespace App\Models\Inventory;

use App\Models\BranchProducts;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    protected $table = "inventory_products";
    
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

    public function category()
    {
        return $this->belongsTo(Categories::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brands::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Suppliers::class);
    }

    public function branches()
    {
        return $this->hasMany(BranchProducts::class, "product_id");
    }
}