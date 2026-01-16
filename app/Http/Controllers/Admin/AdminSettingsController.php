<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSettingsController extends Controller
{
    /**
     * Show the admin settings page.
     */
    public function edit(): Response
    {
        $deliveryOriginAddress = Setting::get('delivery_origin_address', '');
        $deliveryOriginLocation = Setting::get('delivery_origin_location', null);
        $baseDeliveryFee = Setting::get('base_delivery_fee', '50.00');
        $ratePerKm = Setting::get('rate_per_km', '10.00');
        
        // Parse location if it's a JSON string
        if (is_string($deliveryOriginLocation)) {
            $deliveryOriginLocation = json_decode($deliveryOriginLocation, true);
        }
        
        return Inertia::render('admin/settings/Index', [
            'deliveryOriginAddress' => $deliveryOriginAddress,
            'deliveryOriginLocation' => $deliveryOriginLocation ?: null,
            'baseDeliveryFee' => $baseDeliveryFee,
            'ratePerKm' => $ratePerKm,
        ]);
    }

    /**
     * Update the admin settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_origin_address' => 'nullable|string|max:500',
            'delivery_origin_location' => 'nullable|array',
            'delivery_origin_location.lat' => 'nullable|numeric|between:-90,90',
            'delivery_origin_location.lng' => 'nullable|numeric|between:-180,180',
            'base_delivery_fee' => 'nullable|numeric|min:0|max:99999.99',
            'rate_per_km' => 'nullable|numeric|min:0|max:9999.99',
        ]);

        // Store delivery origin address
        Setting::set('delivery_origin_address', $validated['delivery_origin_address'] ?? '');

        // Store delivery origin location as JSON
        if (isset($validated['delivery_origin_location']) && !empty($validated['delivery_origin_location'])) {
            Setting::set('delivery_origin_location', json_encode($validated['delivery_origin_location']));
        } else {
            Setting::set('delivery_origin_location', null);
        }

        // Store base delivery fee
        Setting::set('base_delivery_fee', $validated['base_delivery_fee'] ?? '50.00');
        
        // Store rate per km
        Setting::set('rate_per_km', $validated['rate_per_km'] ?? '10.00');

        return redirect()->route('admin.settings.edit')->with('success', 'Settings updated successfully!');
    }
}
