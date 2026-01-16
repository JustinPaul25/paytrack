# Quick Setup: Distance-Based Delivery Fee

## 🚀 Quick Start (3 Steps)

### Step 1: Initialize Default Settings
Run this command to set up default delivery fee settings:

```bash
php artisan db:seed --class=DeliverySettingsSeeder
```

This sets:
- Base Delivery Fee: ₱50.00
- Rate per Kilometer: ₱10.00

### Step 2: Set Store Location (Admin Required)
1. Log in as **Admin**
2. Go to **Admin Settings** (`/admin/settings`)
3. Fill in:
   - **Delivery Origin Address**: Your store address
   - **Location on Map**: Click to set your exact location
4. Click **Save Settings**

### Step 3: Set Customer Locations
For each customer who will use delivery:
1. Go to **Customers** → Click **Edit** on a customer
2. Scroll to **Location** field
3. Click on the map to set their delivery address
4. Click **Save**

## ✅ Verify It's Working

1. Log in as a customer (who has location set)
2. Create a new order
3. Select "Delivery" as delivery type
4. Check the Order Summary:
   - Should show: `Delivery Fee (X.XX km): ₱XXX.XX`
   - Should show green message: "✓ Delivery fee calculated"

## ⚠️ If It's Not Working

Check the Order Summary message:
- **"Store location: ✗ Not set"** → Go to Admin Settings and set store location
- **"Your location: ✗ Not set"** → Update customer profile with location

## 📖 Full Documentation

See `DELIVERY_FEE_SETUP.md` for complete details.
