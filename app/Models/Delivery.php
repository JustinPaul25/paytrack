<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Delivery extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'customer_id', 'invoice_id', 'delivery_address', 'contact_person', 
        'contact_phone', 'delivery_date', 'delivery_time', 'status', 
        'notes', 'delivery_fee', 'type'
    ];

    protected $casts = [
        'delivery_date' => 'date',
    ];

    protected $appends = ['proof_of_delivery_url'];

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

    /**
     * Get the proof of delivery URL.
     */
    public function getProofOfDeliveryUrlAttribute()
    {
        $media = $this->getFirstMedia('proof_of_delivery');
        return $media ? $media->getUrl() : null;
    }

    /**
     * Register the media collections for the delivery.
     */
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('proof_of_delivery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/webp']);
    }
}
