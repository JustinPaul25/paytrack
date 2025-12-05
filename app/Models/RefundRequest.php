<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class RefundRequest extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

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
        'request_type',
        'exchange_product_id',
        'exchange_quantity',
        'damaged_items_terms',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Media Library: register proof images collection
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('proof_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp']);
    }
}


