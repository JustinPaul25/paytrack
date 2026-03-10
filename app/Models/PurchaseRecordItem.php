<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRecordItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_record_id',
        'qty',
        'unit',
        'description',
        'unit_price',
        'amount',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function purchaseRecord(): BelongsTo
    {
        return $this->belongsTo(PurchaseRecord::class);
    }
}
