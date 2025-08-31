<?php
// app/Http/Controllers/Api/GlobalSettingsController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GlobalSetting;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class GlobalSettingsController extends Controller
{
    use AuthorizesRequests;

    // GET /api/v1/global-settings
    public function index()
    {
        $this->authorize('viewAny', GlobalSetting::class);
        
        $settings = GlobalSetting::all()->pluck('setting_value', 'setting_key');
        
        return response()->json([
            'status' => true,
            'data' => $settings
        ]);
    }

    // GET /api/v1/global-settings/monthly-limit
    public function getMonthlyLimit()
    {
        return response()->json([
            'status' => true,
            'data' => ['monthly_limit' => GlobalSetting::getMonthlyPengajuanLimit()]
        ]);
    }

    // PUT /api/v1/global-settings/monthly-limit
    public function setMonthlyLimit(Request $request)
    {
        $this->authorize('create', GlobalSetting::class);
        
        $data = $request->validate([
            'monthly_limit' => 'required|integer|min:1|max:50'
        ]);

        GlobalSetting::setMonthlyPengajuanLimit($data['monthly_limit']);

        return response()->json([
            'status' => true,
            'message' => 'Monthly pengajuan limit updated successfully',
            'data' => ['monthly_limit' => $data['monthly_limit']]
        ]);
    }
}
