<?php

namespace App\Models;

use App\Models\Inventory\Products;
use Illuminate\Database\Eloquent\Model;

class InvoiceItems extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "invoice_id",
        "title",
        "price",
        "quantity"
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        "item_type"
    ];

    public function getItemTypeAttribute()
    {
        $itemType = null;

        if ($this->service_id) {
            $itemType = "Service";
        } else if ($this->product_id) {
            $itemType = "Product";
        } else if ($this->deal_id) {
            $itemType = "Deal";
        }

        return $itemType;
    }

    public function discounts()
    {
        return $this->hasMany(InvoiceItemsDiscounts::class, "invoice_item_id");
    }

    public function invoice()
    {
        return $this->belongsTo(Invoices::class);
    }

    public function product()
    {
        return $this->belongsTo(Products::class);
    }

    public function service()
    {
        return $this->belongsTo(Services::class);
    }

    public function deal()
    {
        return $this->belongsTo(Deals::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function taxes()
    {
        return $this->hasMany(InvoiceItemsTaxes::class, "invoice_item_id");
    }

    public function clientDeal()
    {
        return $this->belongsTo(ClientDeals::class);
    }
}
