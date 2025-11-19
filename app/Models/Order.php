<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'invoice_id',
        'reference_number',
        'status',
        'notes',
        'subtotal_amount',
        'vat_amount',
        'vat_rate',
        'total_amount',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            if (empty($order->reference_number)) {
                $order->reference_number = self::generateReferenceNumber();
            }
        });
    }

    public static function generateReferenceNumber()
    {
        $prefix = 'ORD';
        $year = date('Y');
        $month = date('m');
        
        // Get the last order number for this month
        $lastOrder = self::where('reference_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('reference_number', 'desc')
            ->first();
        
        if ($lastOrder) {
            // Extract the sequence number and increment it
            $parts = explode('-', $lastOrder->reference_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }
        
        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function comments()
    {
        return $this->hasMany(OrderComment::class)->orderBy('created_at', 'asc');
    }

    // Money accessors (get in dollars, set in cents)
    public function getSubtotalAmountAttribute($value)
    {
        return $value / 100;
    }

    public function setSubtotalAmountAttribute($value)
    {
        $this->attributes['subtotal_amount'] = (int) round($value * 100);
    }

    public function getVatAmountAttribute($value)
    {
        return $value / 100;
    }

    public function setVatAmountAttribute($value)
    {
        $this->attributes['vat_amount'] = (int) round($value * 100);
    }

    public function getTotalAmountAttribute($value)
    {
        return $value / 100;
    }

    public function setTotalAmountAttribute($value)
    {
        $this->attributes['total_amount'] = (int) round($value * 100);
    }
}
