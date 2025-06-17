<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property string $id_barang
 * @property int $batas_pengajuan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Barang $barang
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatasPengajuan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatasPengajuan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatasPengajuan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatasPengajuan whereBatasPengajuan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatasPengajuan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatasPengajuan whereIdBarang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatasPengajuan whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BatasPengajuan extends Model
{
    // Specify the table name
    protected $table = 'tb_batas_pengajuan';

    // The primary key is id_barang and it's not auto-incrementing
    protected $primaryKey = 'id_barang';
    public $incrementing = false;
    protected $keyType = 'string';

    // Allow mass assignment for these fields
    protected $fillable = ['id_barang', 'batas_pengajuan'];

    // Optional: Define a relationship to Barang for easy access to item details
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }
}
