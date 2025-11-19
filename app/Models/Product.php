<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'name', 'description', 'category_id', 'purchase_price', 'selling_price', 'stock', 'SKU'
    ];

    // Money accessors (get in dollars, set in cents)
    public function getPurchasePriceAttribute($value)
    {
        return $value / 100;
    }
    public function setPurchasePriceAttribute($value)
    {
        $this->attributes['purchase_price'] = (int) round($value * 100);
    }
    public function getSellingPriceAttribute($value)
    {
        return $value / 100;
    }
    public function setSellingPriceAttribute($value)
    {
        $this->attributes['selling_price'] = (int) round($value * 100);
    }

    // Category relationship
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    // Refunds relationship
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    // Order items relationship
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Media Library: register image collection
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('images')
            ->withResponsiveImages();
    }

    // Register conversions (e.g., thumbnail)
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this
            ->addMediaConversion('thumb')
            ->width(368)
            ->height(232)
            ->nonQueued();
    }
}
