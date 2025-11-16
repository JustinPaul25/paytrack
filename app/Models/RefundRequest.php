<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_number',
        'customer_name',
        'email',
        'phone',
        'invoice_reference',
        'invoice_id',
        'invoice_item_id',
        'product_id',
        'quantity',
        'amount_requested',
        'reason',
        'notes',
        'media_link',
        'status',
        'review_notes',
        'converted_refund_id',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}


