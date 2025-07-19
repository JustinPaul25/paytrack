<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Refund extends Model
{
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
        'completed_at'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'refund_amount' => 'integer',
        'quantity_refunded' => 'integer',
    ];

    // Money accessors (get in dollars, set in cents)
    public function getRefundAmountAttribute($value)
    {
        return $value / 100;
    }

    public function setRefundAmountAttribute($value)
    {
        $this->attributes['refund_amount'] = (int) round($value * 100);
    }

    // Relationships
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes for filtering
    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('status', 'approved');
    }

    public function scopeProcessed(Builder $query): void
    {
        $query->where('status', 'processed');
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', 'completed');
    }

    public function scopeCancelled(Builder $query): void
    {
        $query->where('status', 'cancelled');
    }

    public function scopeByStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    public function scopeByRefundType(Builder $query, string $type): void
    {
        $query->where('refund_type', $type);
    }

    public function scopeByRefundMethod(Builder $query, string $method): void
    {
        $query->where('refund_method', $method);
    }

    public function scopeByDateRange(Builder $query, Carbon $startDate, Carbon $endDate): void
    {
        $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    public function scopeByProduct(Builder $query, int $productId): void
    {
        $query->where('product_id', $productId);
    }

    // Business logic methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeProcessed(): bool
    {
        return in_array($this->status, ['pending', 'approved']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'approved']);
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'processed';
    }

    // Status transition methods
    public function approve(): bool
    {
        if ($this->isPending()) {
            $this->update(['status' => 'approved']);
            return true;
        }
        return false;
    }

    public function process(): bool
    {
        if ($this->canBeProcessed()) {
            $this->update([
                'status' => 'processed',
                'processed_at' => now()
            ]);
            return true;
        }
        return false;
    }

    public function complete(): bool
    {
        if ($this->canBeCompleted()) {
            $this->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
            return true;
        }
        return false;
    }

    public function cancel(): bool
    {
        if ($this->canBeCancelled()) {
            $this->update(['status' => 'cancelled']);
            return true;
        }
        return false;
    }

    // Generate unique refund number
    public static function generateRefundNumber(): string
    {
        $prefix = 'REF';
        $year = date('Y');
        $month = date('m');
        
        // Get the last refund number for this month
        $lastRefund = self::where('refund_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('refund_number', 'desc')
            ->first();

        if ($lastRefund) {
            $lastNumber = (int) substr($lastRefund->refund_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s%s%s%04d', $prefix, $year, $month, $newNumber);
    }

    // Generate unique reference number
    public static function generateReferenceNumber(): string
    {
        $prefix = 'RRN';
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        
        // Get the last reference number for today
        $lastRefund = self::where('reference_number', 'like', "{$prefix}{$year}{$month}{$day}%")
            ->orderBy('reference_number', 'desc')
            ->first();

        if ($lastRefund) {
            $lastNumber = (int) substr($lastRefund->reference_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s%s%s%s%04d', $prefix, $year, $month, $day, $newNumber);
    }

    // Boot method to auto-generate refund number and reference number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($refund) {
            if (empty($refund->refund_number)) {
                $refund->refund_number = self::generateRefundNumber();
            }
            if (empty($refund->reference_number)) {
                $refund->reference_number = self::generateReferenceNumber();
            }
        });
    }
}
