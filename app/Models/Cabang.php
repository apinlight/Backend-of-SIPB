<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cabang extends Model
{
    protected $table = 'tb_cabang';

    protected $primaryKey = 'id_cabang';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id_cabang',
        'nama_cabang',
        'is_pusat',
    ];

    protected $casts = [
        'is_pusat' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_cabang', 'id_cabang');
    }

    public function gudang(): HasMany
    {
        return $this->hasMany(Gudang::class, 'id_cabang', 'id_cabang');
    }
}
