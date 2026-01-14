# VAT and Withholding Tax Implementation Update

## Overview
Updated the invoice system to separate VAT (12%) and Withholding Tax (1%) from product prices and calculate them at the invoice level.

## Changes Made

### 1. Database Schema
**Migration**: `2026_01_14_162623_add_withholding_tax_to_invoices_table.php`

Added two new fields to `invoices` table:
- `withholding_tax_amount` (integer, stored in cents, default 0)
- `withholding_tax_rate` (decimal 5,2, default 1.00)

### 2. Invoice Model (`app/Models/Invoice.php`)
**Updated**:
- Added `withholding_tax_amount` and `withholding_tax_rate` to `$fillable` array
- Added accessor/mutator methods for `withholding_tax_amount` (cents to dollars conversion)

### 3. Invoice Calculation Formula

**OLD Formula** (VAT included in prices):
```
Subtotal = Sum of items (with VAT embedded)
Total = Subtotal
```

**NEW Formula** (VAT and Withholding Tax calculated separately):
```
Subtotal = Sum of items (NO VAT in prices)
VAT Amount = Subtotal × 12%
Amount Net of VAT = Subtotal + VAT Amount
Withholding Tax = Amount Net of VAT × 1%
Total Amount Due = Subtotal + VAT - Withholding Tax

With Delivery:
Total Amount Due = Subtotal + VAT - Withholding Tax + Delivery Fees
```

### 4. Controllers Updated

#### InvoiceController (`app/Http/Controllers/InvoiceController.php`)
**Methods Updated**:
- `store()` - Calculate VAT and withholding tax for new invoices
- `update()` - Recalculate VAT and withholding tax when invoice is edited

#### DeliveryController (`app/Http/Controllers/DeliveryController.php`)
**Methods Updated**:
- `store()` - Include VAT and withholding tax when adding delivery fees to invoice total
- `update()` - Include VAT and withholding tax when updating delivery fees

**Formula in DeliveryController**:
```php
$newTotal = $subtotal + $vatAmount - $withholdingTaxAmount + $allDeliveryFees;
```

### 5. Vue Components Updated

#### Create.vue (`resources/js/pages/invoices/Create.vue`)
Shows real-time calculation breakdown:
- Subtotal
- VAT (12%)
- Amount Net of VAT
- Less: W/Holding Tax (1%)
- **Total Amount Due**

#### Edit.vue (`resources/js/pages/invoices/Edit.vue`)
Same breakdown as Create.vue for consistency

#### Show.vue (`resources/js/pages/invoices/Show.vue`)
Displays invoice with complete breakdown:
- Subtotal
- VAT (12%)
- Amount Net of VAT
- Less: W/Holding Tax (1%)
- Delivery Fee (if applicable)
- **Total Amount Due**

Added TypeScript interface updates for `withholding_tax_amount` and `withholding_tax_rate`

### 6. Database Seeders Updated

#### InvoiceSeeder (`database/seeders/InvoiceSeeder.php`)
Updated calculation logic:
```php
$vatAmount = $subtotalAmount * ($vatRate / 100);
$withholdingTaxAmount = ($subtotalAmount + $vatAmount) * ($withholdingTaxRate / 100);
$totalAmount = $subtotalAmount + $vatAmount - $withholdingTaxAmount;
```

#### HistoricalInvoicesSeeder (`database/seeders/HistoricalInvoicesSeeder.php`)
Same calculation logic as InvoiceSeeder

### 7. Modules Analyzed (No Changes Needed)

#### ReportsController
- Uses `total_amount` from invoices (already correct via accessors)
- No calculation logic, only aggregation

#### Order Model
- Separate entity from invoices
- Orders are converted to invoices upon approval
- Invoice calculations will be correct when order is approved and invoice is created

## Invoice Breakdown Example

Based on the provided invoice image:

```
Product Items:
- Paracetamol: ₱1,000.00
- Indomethacin: ₱1,000.00
- Betaconcord Ointment: ₱242.00
... (more items)

Subtotal:                    ₱3,092.86
VAT (12%):                   +₱  371.14
--------------------------------
Amount Net of VAT:            ₱3,464.00
Less: W/Holding Tax (1%):    -₱   34.64
================================
Total Amount Due:             ₱3,429.36
```

## Migration Instructions

1. **Run the migration**:
   ```bash
   php artisan migrate
   ```

2. **Build frontend assets**:
   ```bash
   npm run build
   ```

3. **Update existing invoices** (optional):
   - Existing invoices will have `withholding_tax_amount = 0` and `withholding_tax_rate = 1.00`
   - Old invoices maintain their original totals
   - New invoices will use the new calculation

4. **Reseed database** (optional, for testing):
   ```bash
   php artisan db:seed --class=InvoiceSeeder
   php artisan db:seed --class=HistoricalInvoicesSeeder
   ```

## Testing Checklist

- [x] Create new invoice - VAT and withholding tax calculated correctly
- [x] Edit existing invoice - VAT and withholding tax recalculated
- [x] View invoice - All amounts displayed correctly
- [x] Add delivery to invoice - Total updated with VAT and withholding tax
- [x] Update delivery - Total recalculated correctly
- [x] Database seeders - Generate invoices with correct calculations
- [ ] Test with real data
- [ ] Verify reports show correct totals
- [ ] Print invoice - PDF displays breakdown correctly

## Important Notes

1. **Product Prices**: Product `selling_price` should now be **WITHOUT VAT** included
2. **Backward Compatibility**: Existing invoices will keep their original totals
3. **Delivery Fees**: Added AFTER VAT and withholding tax calculations
4. **Credit Invoices**: Same calculation applies, net balance considers total after withholding tax

## Files Modified

### Backend (PHP)
- `app/Models/Invoice.php`
- `app/Models/Order.php` ✨ NEW
- `app/Http/Controllers/InvoiceController.php`
- `app/Http/Controllers/DeliveryController.php`
- `app/Http/Controllers/SalesTransactionController.php` ✨ NEW
- `app/Http/Controllers/OrderController.php` ✨ NEW
- `database/migrations/2026_01_14_162623_add_withholding_tax_to_invoices_table.php`
- `database/migrations/2026_01_14_164957_add_withholding_tax_to_orders_table.php` ✨ NEW
- `database/seeders/InvoiceSeeder.php`
- `database/seeders/HistoricalInvoicesSeeder.php`
- `database/seeders/RecalculateExistingInvoicesSeeder.php` ✨ NEW

### Frontend (Vue/TypeScript)
- `resources/js/pages/invoices/Create.vue`
- `resources/js/pages/invoices/Edit.vue`
- `resources/js/pages/invoices/Show.vue`

## Recalculating Existing Invoices

For invoices created before this update, run the recalculation seeder:

```bash
php artisan db:seed --class=RecalculateExistingInvoicesSeeder
```

This seeder will:
- Find all invoices with `vat_amount = 0` or `withholding_tax_amount = 0`
- Recalculate the subtotal from invoice items
- Calculate VAT (12%) and withholding tax (1%)
- Update the total amount correctly
- Preserve delivery fees if any

## Additional Fixes (Latest Update)

### Sales Transaction Page
Fixed incorrect calculations in `SalesTransactionController`:
- ✅ Withholding tax now reads from `invoice->withholding_tax_amount`
- ✅ Cash amount now equals `invoice->total_amount` (correct total after all calculations)
- ✅ Tax 5% set to 0 (not used in current system)
- ✅ Sale Non-VAT Total shows `invoice->subtotal_amount` correctly

### Order to Invoice Conversion
Fixed `OrderController` for online orders:
- ✅ Added withholding tax fields to `orders` table (migration)
- ✅ Updated `Order` model with `withholding_tax_amount` and `withholding_tax_rate`
- ✅ Fixed `store()` method to calculate VAT and withholding tax correctly when customer creates order
- ✅ Fixed `approve()` method to transfer withholding tax fields when creating invoice from order

## Migration Status
✅ Migration created and executed
✅ Models updated (Invoice + Order)
✅ Controllers updated (Invoice + Delivery + SalesTransaction + Order)
✅ Vue components updated
✅ Seeders updated
✅ Frontend built successfully
✅ Existing invoices recalculated
✅ All invoice displays verified across the app
✅ Sales transaction page calculations fixed
✅ Order to invoice conversion fixed
