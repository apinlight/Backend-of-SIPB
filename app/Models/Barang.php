<?php
// app/Models/Barang.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'tb_barang';
    protected $primaryKey = 'id_barang';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id_barang', 'nama_barang', 'id_jenis_barang', 'harga_barang'];

    // ✅ FIX: Frontend expects 'jenis_barang' relationship name
    public function jenis_barang()
    {
        return $this->belongsTo(JenisBarang::class, 'id_jenis_barang', 'id_jenis_barang');
    }

    // ✅ Keep old method for backward compatibility
    public function jenisBarang()
    {
        return $this->jenis_barang();
    }

    // ✅ One-to-one: a barang has one batas barang
    public function batasBarang()
    {
        return $this->hasOne(BatasBarang::class, 'id_barang', 'id_barang');
    }

    // ✅ Many-to-many: a barang is stored in many users' gudang records
    public function gudang()
    {
        return $this->belongsToMany(User::class, 'tb_gudang', 'id_barang', 'unique_id')
                    ->using(Gudang::class)
                    ->withPivot('jumlah_barang', 'created_at', 'updated_at');
    }

    // ✅ Get current stock across all gudang
    public function getTotalStockAttribute()
    {
        return $this->gudang()->sum('jumlah_barang');
    }

    // ✅ Get current stock for specific user/branch
    public function getStockForUser(string $uniqueId): int
    {
        return Gudang::where('unique_id', $uniqueId)
                    ->where('id_barang', $this->id_barang)
                    ->value('jumlah_barang') ?? 0;
    }

    // ✅ Check if stock is below minimum threshold
    public function isLowStock(): bool
    {
        $batas = $this->batasBarang->batas_barang ?? 5;
        return $this->total_stock <= $batas;
    }

    // ✅ One-to-many: a barang can have many penggunaan barang
    public function penggunaanBarang()
    {
        return $this->hasMany(PenggunaanBarang::class, 'id_barang', 'id_barang');
    }

    // ✅ Get actual current stock (procurement - usage)
    public function getActualStockForUser(string $uniqueId): int
    {
        $procured = Gudang::where('unique_id', $uniqueId)
            ->where('id_barang', $this->id_barang)
            ->value('jumlah_barang') ?? 0;
        
        return $procured; // Gudang already reflects actual stock after usage
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id_barang)) {
                $model->id_barang = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }
}
