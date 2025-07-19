<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = [
        'customer_id', 'invoice_id', 'delivery_address', 'contact_person', 
        'contact_phone', 'delivery_date', 'delivery_time', 'status', 
        'notes', 'delivery_fee'
    ];

    protected $casts = [
        'delivery_date' => 'date',
    ];

    // Money accessors (get in dollars, set in cents)
    public function getDeliveryFeeAttribute($value)
    {
        return $value / 100;
    }
    
    public function setDeliveryFeeAttribute($value)
    {
        $this->attributes['delivery_fee'] = (int) round($value * 100);
    }

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
