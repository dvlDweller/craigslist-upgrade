<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function index(): JsonResponse
    {
        $locations = [];

        foreach (config('marketplace_locations.municipalities', []) as $municipality => $data) {
            $locations[] = [
                'municipality' => $municipality,
                'official_name' => $data['official_name'] ?? $municipality,
                'psgc_code' => $data['psgc_code'] ?? null,
                'barangays' => $data['barangays'] ?? [],
            ];
        }

        return response()->json(['data' => $locations]);
    }
}
