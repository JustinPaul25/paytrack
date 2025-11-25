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
        
        // Parse location if it's a JSON string
        if (is_string($deliveryOriginLocation)) {
            $deliveryOriginLocation = json_decode($deliveryOriginLocation, true);
        }
        
        return Inertia::render('admin/settings/Index', [
            'deliveryOriginAddress' => $deliveryOriginAddress,
            'deliveryOriginLocation' => $deliveryOriginLocation ?: null,
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
        ]);

        // Store delivery origin address
        Setting::set('delivery_origin_address', $validated['delivery_origin_address'] ?? '');

        // Store delivery origin location as JSON
        if (isset($validated['delivery_origin_location']) && !empty($validated['delivery_origin_location'])) {
            Setting::set('delivery_origin_location', json_encode($validated['delivery_origin_location']));
        } else {
            Setting::set('delivery_origin_location', null);
        }

        return redirect()->route('admin.settings.edit')->with('success', 'Settings updated successfully!');
    }
}
