<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalSetting extends Model
{
    protected $table = 'tb_global_settings';
    
    // Use the default primary key 'id' if it exists. If your primary key is
    // 'setting_key', you would uncomment the lines below.
    // protected $primaryKey = 'setting_key'; 
    // public $incrementing = false;
    // protected $keyType = 'string';

    protected $fillable = [
        'setting_key',
        'setting_value', 
        'setting_description'
    ];

    // The static business logic methods (`get...` and `set...`) have been
    // successfully moved to the GlobalSettingsService. The model is now a
    // clean representation of the data in the tb_global_settings table.
}