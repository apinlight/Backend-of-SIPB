<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property string $id_pengajuan
 * @property string $id_barang
 * @property int $jumlah
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Barang $barang
 * @property-read \App\Models\Pengajuan $pengajuan
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailPengajuan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailPengajuan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailPengajuan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailPengajuan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailPengajuan whereIdBarang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailPengajuan whereIdPengajuan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailPengajuan whereJumlah($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailPengajuan whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DetailPengajuan extends Model
{
    protected $table = 'tb_detail_pengajuan';
    protected $primaryKey = null;
    public $incrementing = false;
    
    // Composite key: id_pengajuan and id_barang
    protected $fillable = ['id_pengajuan', 'id_barang', 'jumlah'];

    // Belongs to a Pengajuan
    public function pengajuan()
    {
        return $this->belongsTo(Pengajuan::class, 'id_pengajuan', 'id_pengajuan');
    }

    // Belongs to a Barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    public function setKeysForSaveQuery($query)
    {
        $query->where('id_pengajuan', '=', $this->getAttribute('id_pengajuan'))
              ->where('id_barang', '=', $this->getAttribute('id_barang'));
        return $query;
    }
}
