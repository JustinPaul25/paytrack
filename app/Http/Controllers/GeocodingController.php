<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class GeocodingController extends Controller
{
    /**
     * Proxy geocoding search requests to Nominatim API
     * This avoids CORS issues by making requests from the server
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q');
        $limit = $request->input('limit', 5);

        if (!$query) {
            return response()->json([]);
        }

        try {
            // Make request to Nominatim with proper headers
            // Include User-Agent as required by Nominatim usage policy
            $response = Http::withHeaders([
                'User-Agent' => config('app.name') . ' (' . config('app.url') . ')',
                'Accept' => 'application/json',
            ])
            ->timeout(10)
            ->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'json',
                'q' => $query,
                'limit' => $limit,
                'addressdetails' => 1,
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([], 200);
        } catch (\Exception $e) {
            \Log::error('Geocoding search failed: ' . $e->getMessage());
            return response()->json([], 200);
        }
    }
}

