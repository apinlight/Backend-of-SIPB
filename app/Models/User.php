<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property-read \Laravel\Sanctum\PersonalAccessToken|null $currentAccessToken
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasRoles, HasFactory, Notifiable, HasUlids;
    // Specify the table name
    protected $table = 'tb_users';

    // The primary key is 'unique_id' (a string)
    protected $primaryKey = 'unique_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $hidden = ['password'];
    public $timestamps = true;


    // Allow mass assignment for these fields (adjust as needed)
    protected $fillable = ['unique_id', 'username','email', 'password', 'branch_name'];

    // A user has many Pengajuan records
    public function pengajuan()
    {
        return $this->hasMany(Pengajuan::class, 'unique_id', 'unique_id');
    }

    // Many-to-many: a user has many Barang via tb_gudang pivot (with extra attribute jumlah_barang)
    public function gudangBarang()
    {
        return $this->belongsToMany(Barang::class, 'tb_gudang', 'unique_id', 'id_barang')
                    ->using(Gudang::class)
                    ->withPivot('jumlah_barang');
    }
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    // Custom method to get the unique identifier for the user
    public function getAuthIdentifierName()
    {
        return 'unique_id';
    }

    public function penggunaanBarang()
    {
        return $this->hasMany(PenggunaanBarang::class, 'unique_id', 'unique_id');
    }
    // Automatically generate a ULID for 'unique_id' when creating a new user
    protected static function booted()
    {
        static::creating(function ($user) {
            // Generate a ULID if 'unique_id' is not set
            if (empty($user->unique_id)) {
                $user->unique_id = (string) Str::ulid();
            }
        });
    }
}
