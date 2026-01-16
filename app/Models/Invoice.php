<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;


class Invoice extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $fillable = [
        'customer_id', 
        'user_id', 
        'reference_number',
        'total_amount', 
        'subtotal_amount',
        'vat_amount',
        'vat_rate',
        'withholding_tax_amount',
        'withholding_tax_rate',
        'status', 
        'payment_method', 
        'payment_status',
        'payment_reference', 
        'invoice_type',
        'notes',
        'credit_term_days',
        'due_date'
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($invoice) {
            if (empty($invoice->reference_number)) {
                $invoice->reference_number = self::generateReferenceNumber();
            }
            
            // Calculate due_date based on credit_term_days
            if (empty($invoice->due_date) && !empty($invoice->credit_term_days)) {
                $invoice->due_date = now()->addDays($invoice->credit_term_days)->toDateString();
            } elseif (empty($invoice->due_date) && empty($invoice->credit_term_days)) {
                // Default to 30 days if no credit term specified
                $invoice->credit_term_days = 30;
                $invoice->due_date = now()->addDays(30)->toDateString();
            }
        });
        
        static::updating(function ($invoice) {
            // Recalculate due_date if credit_term_days changed
            if ($invoice->isDirty('credit_term_days') && !empty($invoice->credit_term_days)) {
                $baseDate = $invoice->getOriginal('created_at') 
                    ? \Carbon\Carbon::parse($invoice->getOriginal('created_at'))
                    : now();
                $invoice->due_date = $baseDate->copy()->addDays($invoice->credit_term_days)->toDateString();
            }
            
            // If status is being changed to cancelled, set payment_status to 'Cancelled Order'
            // Also check if status is already cancelled but payment_status is not set correctly
            if (($invoice->isDirty('status') && $invoice->status === 'cancelled') 
                || ($invoice->status === 'cancelled' && $invoice->payment_status !== 'Cancelled Order')) {
                $invoice->payment_status = 'Cancelled Order';
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
    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class, 'invoice_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'invoice_id');
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

    public function getWithholdingTaxAmountAttribute($value)
    {
        return $value / 100;
    }
    public function setWithholdingTaxAmountAttribute($value)
    {
        $this->attributes['withholding_tax_amount'] = (int) round($value * 100);
    }

    /**
     * Calculate net amount owed after refunds
     * For credit invoices, this is the actual amount the customer owes
     * Only counts approved, processed, or completed refunds
     * Excludes exchange refunds (replacements) - they don't reduce the amount owed
     */
    public function getNetBalanceAttribute(): float
    {
        $totalRefunded = $this->refunds()
            ->whereIn('status', ['approved', 'processed', 'completed'])
            ->where('refund_type', '!=', 'exchange') // Exclude exchange refunds
            ->sum('refund_amount');
        
        // Convert from cents to currency
        $totalRefunded = $totalRefunded / 100;
        
        return max(0, $this->total_amount - $totalRefunded);
    }

    /**
     * Check if invoice is fully settled (including refunds)
     */
    public function isFullySettled(): bool
    {
        return $this->payment_status === 'paid' || $this->net_balance <= 0;
    }

    /**
     * Get the payment proof URL.
     */
    public function getPaymentProofUrlAttribute()
    {
        $media = $this->getFirstMedia('payment_proof');
        return $media ? $media->getUrl() : null;
    }

    /**
     * Register the media collections for the invoice.
     */
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('payment_proof')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/webp', 'application/pdf']);
    }
    
}
