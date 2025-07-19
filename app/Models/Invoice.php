<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = ['customer_id', 'user_id', 'total_amount', 'status', 'payment_method', 'payment_reference', 'notes'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
    
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
    
    // Money accessors (get in dollars, set in cents)
    public function getTotalAmountAttribute($value)
    {
        return $value / 100;
    }
    public function setTotalAmountAttribute($value)
    {
        $this->attributes['total_amount'] = (int) round($value * 100);
    }
    
}
