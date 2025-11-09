<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'expense_type',
        'description',
        'date',
        'user_id',
        'branch_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    /**
     * Get the user that created the expense.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the branch associated with the expense.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
