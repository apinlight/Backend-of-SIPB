<?php

// database/seeders/GlobalSettingSeeder.php

namespace Database\Seeders;

use App\Models\GlobalSetting;
use Illuminate\Database\Seeder;

class GlobalSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'setting_key' => 'monthly_pengajuan_limit',
                'setting_value' => '5',
                'setting_description' => 'Maximum number of pengajuan per user per month',
            ],
            [
                'setting_key' => 'auto_approve_below_amount',
                'setting_value' => '100000',
                'setting_description' => 'Auto approve pengajuan below this amount (in IDR)',
            ],
            [
                'setting_key' => 'max_pengajuan_amount',
                'setting_value' => '10000000',
                'setting_description' => 'Maximum total amount per pengajuan (in IDR)',
            ],
            [
                'setting_key' => 'require_approval_above_items',
                'setting_value' => '10',
                'setting_description' => 'Require admin approval if total items exceed this number',
            ],
            [
                'setting_key' => 'low_stock_threshold',
                'setting_value' => '5',
                'setting_description' => 'Alert when stock falls below this number',
            ],
            [
                'setting_key' => 'system_maintenance_mode',
                'setting_value' => 'false',
                'setting_description' => 'Enable/disable system maintenance mode',
            ],
            [
                'setting_key' => 'notification_email',
                'setting_value' => 'admin@company.com',
                'setting_description' => 'Email for system notifications',
            ],
        ];

        foreach ($settings as $setting) {
            GlobalSetting::updateOrCreate(
                ['setting_key' => $setting['setting_key']],
                $setting
            );
        }

        $this->command->info('Global settings seeded successfully!');
    }
}
