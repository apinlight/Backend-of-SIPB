<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengajuan extends Model
{
    protected $table = 'tb_pengajuan';
    public $incrementing = false;
    // Do not define $primaryKey because it's composite

    protected $fillable = ['id_pengajuan', 'unique_id', 'status_pengajuan', 'tipe_pengajuan'];

    // A pengajuan belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class, 'unique_id', 'unique_id');
    }

    // A pengajuan has many detail pengajuan records
    public function details()
    {
        return $this->hasMany(DetailPengajuan::class, 'id_pengajuan', 'id_pengajuan');
    }
}
