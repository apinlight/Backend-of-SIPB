<?php
// app/Models/GlobalSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalSetting extends Model
{
    protected $table = 'tb_global_settings';
    
    protected $fillable = [
        'setting_key',
        'setting_value', 
        'setting_description'
    ];

    public static function getMonthlyPengajuanLimit(): int
    {
        return (int) self::where('setting_key', 'monthly_pengajuan_limit')
                        ->value('setting_value') ?? 5;
    }

    public static function setMonthlyPengajuanLimit(int $limit): void
    {
        self::updateOrCreate(
            ['setting_key' => 'monthly_pengajuan_limit'],
            ['setting_value' => $limit]
        );
    }
}
