<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoices extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes excluded from the model"s JSON form.
     *
     * @var array
     */
    protected $hidden = [
        "business_id"
    ];

    public function business()
    {
        return $this->belongsTo(Businesses::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branches::class);
    }
    
    public function client()
    {
        return $this->belongsTo(Clients::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItems::class, "invoice_id");
    }

    public function taxes()
    {
        return $this->hasMany(InvoiceItemsTaxes::class, "invoice_id");
    }

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethods::class, "payment_method_id");
    }

    public function original_invoice()
    {
        return $this->belongsTo(Invoices::class, "original_invoice_id");
    }

    public function refund_invoice()
    {
        return $this->hasOne(Invoices::class, "original_invoice_id");
    }

    public function transactions()
    {
        return $this->hasOne(Transactions::class, "invoice_id");
    }


}
