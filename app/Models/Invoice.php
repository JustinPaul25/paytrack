<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Invoice extends Model
{
    protected $fillable = [
        'customer_id', 
        'user_id', 
        'reference_number',
        'total_amount', 
        'subtotal_amount',
        'vat_amount',
        'vat_rate',
        'status', 
        'payment_method', 
        'payment_reference', 
        'notes'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($invoice) {
            if (empty($invoice->reference_number)) {
                $invoice->reference_number = self::generateReferenceNumber();
            }
        });
    }

    public static function generateReferenceNumber()
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');
        
        // Get the last invoice number for this month
        $lastInvoice = self::where('reference_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('reference_number', 'desc')
            ->first();
        
        if ($lastInvoice) {
            // Extract the sequence number and increment it
            $parts = explode('-', $lastInvoice->reference_number);
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
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
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
    
}
