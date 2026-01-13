<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'invoice_item_id',
        'product_id',
        'user_id',
        'refund_number',
        'quantity_refunded',
        'refund_amount',
        'refund_type',
        'refund_method',
        'status',
        'reason',
        'notes',
        'reference_number',
        'processed_at',
        'completed_at',
        'exchange_product_id',
        'exchange_quantity',
        'is_damaged',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Money accessors (get as currency, set in cents)
    public function getRefundAmountAttribute($value)
    {
        return $value / 100;
    }
    public function setRefundAmountAttribute($value)
    {
        $this->attributes['refund_amount'] = (int) round($value * 100);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exchangeProduct()
    {
        return $this->belongsTo(Product::class, 'exchange_product_id');
    }
}


