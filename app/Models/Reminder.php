<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reminder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'title',
        'description',
        'due_date',
        'amount',
        'currency',
        'priority',
        'status',
        'is_read',
        'remindable_type',
        'remindable_id',
        'customer_id',
        'invoice_id',
        'order_id',
        'expense_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'is_read' => 'boolean',
    ];

    /**
     * Get the parent remindable model (Invoice, Order, Expense, etc.).
     */
    public function remindable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the customer associated with the reminder.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the invoice associated with the reminder.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the order associated with the reminder.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the expense associated with the reminder.
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Scope a query to only include pending reminders.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include unread reminders.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to include overdue reminders.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->where('status', 'pending');
    }

    /**
     * Scope a query to include upcoming reminders (within next 7 days).
     */
    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->whereBetween('due_date', [
            now()->toDateString(),
            now()->addDays($days)->toDateString()
        ])->where('status', 'pending');
    }
}
