# Distance-Based Delivery Fee Setup Guide

## Overview
The system now calculates delivery fees automatically based on the actual distance between your store and the customer's location using the formula:

**Delivery Fee = Base Fee + (Distance × Rate per KM)**

Example: ₱50.00 + (5.23 km × ₱10.00) = ₱102.30

## Requirements for Accurate Calculation

For the system to calculate delivery fees accurately, **BOTH** of the following must be configured:

### 1. Store/Business Location (Admin Setup)
**Who:** Admin users only  
**Where:** Admin Settings page (`/admin/settings`)

**Steps:**
1. Log in as an Admin
2. Navigate to **Admin Settings**
3. Configure the following:
   - **Delivery Origin Address**: Enter your store/warehouse address
   - **Location on Map**: Click on the map to pinpoint your exact location
   - **Base Delivery Fee**: Set minimum delivery charge (default: ₱50.00)
   - **Rate per Kilometer**: Set additional charge per km (default: ₱10.00)
4. Click **Save Settings**

**Important:** The map location is crucial for accurate distance calculation!

### 2. Customer Location (Customer Profile)
**Who:** Customers or Staff (when creating/editing customer profiles)  
**Where:** Customer Profile Edit page (`/customers/{id}/edit`)

**Steps:**
1. Go to **Customers** menu
2. Click **Edit** on a customer
3. Scroll to the **Location** field
4. Click on the map to set the customer's delivery location
5. Click **Save**

**Note:** Customers can also update their own location in their profile settings.

## How It Works

### When Both Locations Are Set ✓
```
Order Summary
├─ Subtotal: ₱554.40
├─ Delivery Fee (5.23 km): ₱102.30  ← Calculated automatically
└─ Total Amount Due: ₱656.70

✓ Delivery fee calculated: Base fee (₱50.00) + Distance charge (₱52.30) = ₱102.30
```

### When Location Data Is Missing ⚠
```
Order Summary
├─ Subtotal: ₱554.40
├─ Delivery Fee: ₱50.00  ← Base fee only
└─ Total Amount Due: ₱604.40

⚠ Distance-based calculation unavailable
Requirements:
• Store location: ✗ Not set (Go to Admin Settings)
• Your location: ✗ Not set (Update your profile)
```

## Testing the Setup

1. **Verify Store Location:**
   - Go to Admin Settings
   - Check if the map shows a marker at your store location
   - If not, click on the map to set it

2. **Verify Customer Location:**
   - Go to a customer's profile
   - Check if the Location field has coordinates
   - If not, edit the customer and set their location on the map

3. **Test Order Creation:**
   - Log in as a customer (who has location set)
   - Create a new order with delivery type
   - Check if the delivery fee shows distance in kilometers
   - Verify the calculation matches: Base Fee + (Distance × Rate)

## Troubleshooting

### "Distance-based calculation unavailable"
**Cause:** Missing location data  
**Solution:** 
- Check if store location is set in Admin Settings
- Check if customer location is set in their profile
- Both must be set for calculation to work

### Delivery fee shows base fee only
**Cause:** One or both locations are missing  
**Solution:** Follow the setup steps above

### Wrong delivery fee amount
**Cause:** Incorrect base fee or rate per km settings  
**Solution:** 
- Go to Admin Settings
- Verify Base Delivery Fee and Rate per Kilometer values
- Update if needed

## Database Structure

### Settings Table
Stores configuration values:
- `delivery_origin_address` - Store address (text)
- `delivery_origin_location` - Store coordinates (JSON: `{"lat": 14.5995, "lng": 120.9842}`)
- `base_delivery_fee` - Minimum delivery charge (default: 50.00)
- `rate_per_km` - Per-kilometer rate (default: 10.00)

### Customers Table
- `location` - Customer coordinates (JSON: `{"lat": 14.5995, "lng": 120.9842}`)

## Manual Database Setup (Optional)

If you need to set values directly in the database:

```sql
-- Set store location
INSERT INTO settings (key, value, created_at, updated_at) 
VALUES ('delivery_origin_location', '{"lat": 14.5995, "lng": 120.9842}', NOW(), NOW())
ON DUPLICATE KEY UPDATE value = '{"lat": 14.5995, "lng": 120.9842}', updated_at = NOW();

-- Set base delivery fee
INSERT INTO settings (key, value, created_at, updated_at) 
VALUES ('base_delivery_fee', '50.00', NOW(), NOW())
ON DUPLICATE KEY UPDATE value = '50.00', updated_at = NOW();

-- Set rate per km
INSERT INTO settings (key, value, created_at, updated_at) 
VALUES ('rate_per_km', '10.00', NOW(), NOW())
ON DUPLICATE KEY UPDATE value = '10.00', updated_at = NOW();

-- Set customer location (replace 1 with actual customer ID)
UPDATE customers 
SET location = '{"lat": 14.5995, "lng": 120.9842}' 
WHERE id = 1;
```

## API/Technical Details

### Distance Calculation
Uses the **Haversine formula** to calculate great-circle distance between two points on Earth:

```javascript
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in kilometers
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c; // Distance in kilometers
}
```

### Fee Calculation Logic
```javascript
if (distance > 0) {
    deliveryFee = baseDeliveryFee + (distance * ratePerKm);
    deliveryFee = Math.max(deliveryFee, baseDeliveryFee); // Ensure minimum
} else {
    deliveryFee = baseDeliveryFee; // Fallback
}
```

## Support

If you encounter issues:
1. Check this guide's troubleshooting section
2. Verify all requirements are met
3. Check browser console for errors
4. Contact your system administrator
