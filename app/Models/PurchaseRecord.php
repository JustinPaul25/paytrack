<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'supplier_name',
        'supplier_tin',
        'supplier_address',
        'receipt_number',
        'date',
        'payment_type',
        'buyer_name',
        'vatable_sales',
        'vat_amount',
        'total_due',
        'withholding_tax',
        'total_amount_due',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'date' => 'date',
        'vatable_sales' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_due' => 'decimal:2',
        'withholding_tax' => 'decimal:2',
        'total_amount_due' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (PurchaseRecord $record) {
            if (empty($record->reference_number)) {
                $record->reference_number = static::generateReferenceNumber();
            }
        });
    }

    public static function generateReferenceNumber(): string
    {
        $prefix = 'PRC-' . now()->format('Ym') . '-';
        $last = static::where('reference_number', 'like', $prefix . '%')
            ->orderByDesc('reference_number')
            ->value('reference_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRecordItem::class);
    }
}
