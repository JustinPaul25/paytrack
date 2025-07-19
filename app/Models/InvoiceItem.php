<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceItem extends Model
{
    protected $fillable = ['invoice_id', 'product_id', 'quantity', 'price', 'total'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
    
    // Money accessors (get in dollars, set in cents)
    public function getPriceAttribute($value)
    {
        return $value / 100;
    }
    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = (int) round($value * 100);
    }
    public function getTotalAttribute($value)
    {
        return $value / 100;
    }
    public function setTotalAttribute($value)
    {
        $this->attributes['total'] = (int) round($value * 100);
    }
}
