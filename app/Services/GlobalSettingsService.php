<?php

namespace App\Services;

use App\Models\GlobalSetting;

class GlobalSettingsService
{
    const MONTHLY_LIMIT_KEY = 'monthly_pengajuan_limit';

    const DEFAULT_MONTHLY_LIMIT = 5;

    /**
     * Retrieves all settings as a key-value pair.
     */
    public function getAllSettings(): \Illuminate\Support\Collection
    {
        return GlobalSetting::all()->pluck('setting_value', 'setting_key');
    }

    /**
     * Gets the monthly pengajuan limit from the database.
     */
    public function getMonthlyLimit(): int
    {
        return (int) GlobalSetting::where('setting_key', self::MONTHLY_LIMIT_KEY)
            ->value('setting_value') ?? self::DEFAULT_MONTHLY_LIMIT;
    }

    /**
     * Updates or creates the monthly pengajuan limit.
     */
    public function setMonthlyLimit(int $limit): void
    {
        GlobalSetting::updateOrCreate(
            ['setting_key' => self::MONTHLY_LIMIT_KEY],
            ['setting_value' => $limit]
        );
    }
}
