<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    /**
     * Get public settings for the frontend.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Get all settings that are not in the 'odoo' or 'email' groups (internal settings)
        $settings = Setting::whereNotIn('group', ['odoo', 'email'])
            ->get()
            ->groupBy('group')
            ->map(function ($groupSettings) {
                return $groupSettings->pluck('value', 'key');
            });

        // Transform to flat structure for easier frontend consumption
        $publicSettings = [];
        foreach ($settings as $group => $values) {
            foreach ($values as $key => $value) {
                $publicSettings[$key] = $value;
            }
        }

        // Add default values if not set
        $defaults = [
            'store_name' => 'Edu Boutique Bookstore',
            'whatsapp_number' => '+263775673510',
            'email' => 'info@eduboutique.co.zw',
            'address' => 'Harare, Zimbabwe',
            'facebook_url' => 'https://facebook.com/eduboutique',
        ];

        $publicSettings = array_merge($defaults, $publicSettings);

        return response()->json([
            'data' => $publicSettings
        ], 200);
    }
}
