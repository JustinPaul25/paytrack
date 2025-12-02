# Credit Invoice Refund Handling - Best Approach

## Problem Statement
When a credit invoice (payment_method = 'credit', payment_status = 'pending') has an approved refund, we need to handle the financial reconciliation properly.

## Current Situation
- Credit invoices remain with `payment_status = 'pending'` until manually marked as paid by staff
- When refund is approved, a Refund record is created but doesn't affect the invoice's payment status
- This can lead to confusion: customer owes full amount but has received a refund

## Recommended Approach: Net Balance Calculation

### Core Concept
Calculate the **net amount owed** by subtracting approved/completed refunds from the invoice total. This preserves the audit trail while showing the actual amount due.

### Implementation Strategy

#### 1. Add Net Balance Calculation to Invoice Model
```php
// In app/Models/Invoice.php

/**
 * Calculate net amount owed after refunds
 * For credit invoices, this is the actual amount the customer owes
 */
public function getNetBalanceAttribute(): float
{
    $totalRefunded = $this->refunds()
        ->whereIn('status', ['approved', 'processed', 'completed'])
        ->sum('refund_amount');
    
    // Convert from cents to currency
    $totalRefunded = $totalRefunded / 100;
    
    return max(0, $this->total_amount - $totalRefunded);
}

/**
 * Check if invoice is fully settled (including refunds)
 */
public function isFullySettled(): bool
{
    return $this->payment_status === 'paid' || $this->net_balance <= 0;
}
```

#### 2. Auto-Update Payment Status on Refund Approval
When a refund is approved for a credit invoice:
- Calculate net balance
- If net balance <= 0, automatically mark `payment_status = 'paid'`
- If net balance > 0, keep `payment_status = 'pending'` but show net balance

#### 3. Display Net Balance in UI
- Show both original amount and net balance
- Highlight when net balance differs from total
- For credit invoices, prominently display "Amount Due: [net balance]"

### Benefits
✅ **Preserves Audit Trail**: Original invoice amounts remain unchanged
✅ **Automatic Reconciliation**: No manual intervention needed for full refunds
✅ **Flexible**: Works for partial refunds
✅ **Clear Communication**: Customers see actual amount owed
✅ **Accounting Compliant**: Maintains proper records

### Edge Cases Handled
1. **Full Refund**: Net balance = 0, auto-mark as paid
2. **Partial Refund**: Net balance = remaining amount, still pending
3. **Multiple Refunds**: Sum all refunds correctly
4. **Refund Status**: Only count approved/processed/completed refunds

### Alternative Approaches Considered

#### Option 2: Adjust Invoice Amount
❌ **Not Recommended**: Changes historical data, breaks audit trail

#### Option 3: Separate Credit Note System
⚠️ **More Complex**: Requires additional tables and logic, overkill for this use case

#### Option 4: Manual Adjustment Only
⚠️ **Less Efficient**: Requires staff to manually calculate and update, error-prone

## Implementation Priority
1. **High**: Add net balance calculation method
2. **High**: Auto-update payment_status when net balance <= 0
3. **Medium**: Update UI to show net balance prominently
4. **Low**: Add notifications when credit invoice is auto-settled








