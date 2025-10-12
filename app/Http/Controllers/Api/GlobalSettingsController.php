<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetMonthlyLimitRequest;
use App\Models\GlobalSetting;
use App\Services\GlobalSettingsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class GlobalSettingsController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected GlobalSettingsService $settingsService) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', GlobalSetting::class);
        $settings = $this->settingsService->getAllSettings();

        return response()->json(['status' => true, 'data' => $settings]);
    }

    public function getMonthlyLimit(): JsonResponse
    {
        $this->authorize('viewAny', GlobalSetting::class);
        $limit = $this->settingsService->getMonthlyLimit();

        return response()->json(['status' => true, 'data' => ['monthly_limit' => $limit]]);
    }

    public function setMonthlyLimit(SetMonthlyLimitRequest $request): JsonResponse
    {
        $this->authorize('create', GlobalSetting::class);
        $limit = $request->validated()['monthly_limit'];
        $this->settingsService->setMonthlyLimit($limit);

        return response()->json([
            'status' => true,
            'message' => 'Monthly pengajuan limit updated successfully',
            'data' => ['monthly_limit' => $limit],
        ]);
    }
}
