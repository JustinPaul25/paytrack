# Order Totals Analysis - Delivery Fee & VAT

## Current State Analysis

### 1. Delivery Fee Status

**❌ ISSUE: Delivery fee is NOT included in order/invoice totals**

**Current Implementation:**
- Delivery fees are stored separately in the `deliveries` table
- Delivery fee is NOT added to `order.total_amount` or `invoice.total_amount`
- Delivery fee is only displayed separately in invoice views
- When an order is approved and converted to an invoice, the delivery fee is not included in the total

**Code Evidence:**
- `OrderController.php` (lines 167-172): Order total = subtotal only (no delivery fee)
- `InvoiceController.php` (lines 163-168): Invoice total = subtotal only (no delivery fee)
- `Delivery` model: Has `delivery_fee` field but it's not linked to order/invoice totals
- Invoice Show view: Delivery fee is shown separately, not in the total calculation

**Impact:**
- Customers see incorrect totals (missing delivery fee)Invoice status:
- If the status is cancelled, payment satus will be "Cancelled Order"

- Financial reports may be inaccurate
- Invoice totals don't reflect the actual amount due

---

### 2. VAT Status

**⚠️ POTENTIAL ISSUE: VAT calculation and display**

**Current Implementation:**
- System assumes "VAT is already included in product prices"
- `vat_amount` is set to `0` in orders and invoices
- `vat_rate` is stored as `12%` but not actually calculated
- Comments throughout codebase say "VAT already included in product prices"

**Code Evidence:**
- `OrderController.php` (line 169): `$vatAmount = 0; // VAT already included in product prices`
- `InvoiceController.php` (line 165): `$vatAmount = 0; // VAT already included in product prices`
- Order Create view: Shows "12% VAT is included in price" message
- Invoice Show view: Shows "VAT (12%) included in prices" but displays "—" for VAT amount

**Questions to Verify:**
1. Are product `selling_price` values actually VAT-inclusive?
2. Should VAT be calculated separately and shown as a line item?
3. Should the total show: Subtotal + VAT + Delivery Fee?

**Current Display:**
```
Subtotal: ₱1,000.00
VAT (12%): — (included in prices)
Total: ₱1,000.00
```

**If VAT should be calculated separately:**
```
Subtotal (ex-VAT): ₱892.86
VAT (12%): ₱107.14
Delivery Fee: ₱50.00
Total: ₱1,050.00
```

---

## Recommendations

### Option 1: VAT-Inclusive (Current Approach - Needs Delivery Fee Fix)

If product prices are VAT-inclusive:
1. ✅ Keep VAT calculation as is (VAT included in prices)
2. ❌ **FIX: Add delivery fee to total**
   - When order is created with `delivery_type = 'delivery'`, add delivery fee to total
   - When invoice is created from order, include delivery fee in total
   - When delivery is created for invoice, update invoice total to include delivery fee

**Calculation:**
```
Subtotal (VAT-inclusive): ₱1,000.00
Delivery Fee: ₱50.00
Total: ₱1,050.00
```

### Option 2: VAT-Exclusive (Requires Full Refactor)

If product prices should be VAT-exclusive:
1. ❌ **FIX: Calculate VAT separately**
   - Extract VAT from product prices: `vat = subtotal * (vat_rate / (100 + vat_rate))`
   - Or: `subtotal_ex_vat = subtotal / (1 + vat_rate/100)`
   - Display VAT as separate line item
2. ❌ **FIX: Add delivery fee to total**
   - Same as Option 1

**Calculation:**
```
Subtotal (ex-VAT): ₱892.86
VAT (12%): ₱107.14
Delivery Fee: ₱50.00
Total: ₱1,050.00
```

---

## Required Changes

### For Delivery Fee (Regardless of VAT approach):

1. **Order Creation** (`OrderController.php::store`):
   - Add `delivery_fee` field to order (or calculate it)
   - Include delivery fee in `total_amount` calculation
   - Note: Delivery fee may not be known at order creation time (only when delivery is scheduled)

2. **Order Approval** (`OrderController.php::approve`):
   - When creating invoice from order, check if delivery exists
   - Add delivery fee to invoice total if delivery type is 'delivery'

3. **Delivery Creation** (`DeliveryController.php::store`):
   - When delivery is created for an invoice, update invoice total
   - Add delivery fee to invoice `total_amount`

4. **Database Schema**:
   - Consider adding `delivery_fee` field to `orders` table (optional, can calculate from deliveries)
   - Or ensure delivery fee is always added to invoice total when delivery is created

5. **Frontend Display**:
   - Update order/invoice views to show delivery fee in total breakdown
   - Show: Subtotal + Delivery Fee = Total

### For VAT (If needs to be calculated separately):

1. **Product Prices**:
   - Verify if `selling_price` is VAT-inclusive or VAT-exclusive
   - If VAT-exclusive: Calculate VAT separately
   - If VAT-inclusive: Extract VAT amount for display

2. **Order/Invoice Calculation**:
   - Calculate `vat_amount` properly
   - Update `total_amount` to include VAT

3. **Frontend Display**:
   - Show VAT as separate line item
   - Update total calculation display

---

## Files That Need Changes

### For Delivery Fee:
1. `app/Http/Controllers/OrderController.php` - Add delivery fee to order total
2. `app/Http/Controllers/InvoiceController.php` - Add delivery fee to invoice total
3. `app/Http/Controllers/DeliveryController.php` - Update invoice total when delivery created
4. `app/Models/Order.php` - Add delivery fee accessor/calculation
5. `app/Models/Invoice.php` - Add delivery fee to total calculation
6. `resources/js/pages/orders/Create.vue` - Show delivery fee in total (if known)
7. `resources/js/pages/orders/Show.vue` - Show delivery fee in breakdown
8. `resources/js/pages/invoices/Show.vue` - Include delivery fee in total calculation

### For VAT (if needed):
1. `app/Http/Controllers/OrderController.php` - Calculate VAT properly
2. `app/Http/Controllers/InvoiceController.php` - Calculate VAT properly
3. `resources/js/pages/orders/Create.vue` - Update VAT display
4. `resources/js/pages/invoices/Show.vue` - Update VAT display

---

## Next Steps

1. **Verify VAT approach**: Confirm if product prices are VAT-inclusive or VAT-exclusive
2. **Decide on delivery fee timing**: When is delivery fee known? (at order creation or later?)
3. **Implement delivery fee inclusion**: Add delivery fee to totals
4. **Update VAT calculation** (if needed): If VAT should be separate, refactor calculation
5. **Update frontend displays**: Show proper breakdowns with delivery fee and VAT

---

## Questions to Answer

1. **VAT**: Are product `selling_price` values VAT-inclusive or VAT-exclusive?
2. **Delivery Fee Timing**: When is the delivery fee determined?
   - At order creation?
   - When delivery is scheduled?
   - When delivery is completed?
3. **Delivery Fee Calculation**: Should delivery fee be:
   - Fixed amount?
   - Calculated based on distance?
   - Manually entered by staff?
4. **Display Preference**: Should totals show:
   - Subtotal + Delivery Fee = Total (VAT included in subtotal)?
   - Subtotal + VAT + Delivery Fee = Total?
