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
 * ✅ THE FIX: A complete docblock (blueprint) for your User model.
 *
 * @property string $unique_id
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string $branch_name
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Permission\Models\Role[] $roles
 * @property-read \Laravel\Sanctum\PersonalAccessToken|null $currentAccessToken
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasRoles, HasFactory, Notifiable, HasUlids;

    protected $table = 'tb_users';
    protected $primaryKey = 'unique_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $hidden = ['password'];
    public $timestamps = true;

    protected $fillable = [
        'unique_id',
        'username',
        'email',
        'password',
        'branch_name',
        'is_active', // Assuming this is in your fillable array based on the error
        'last_login_at', // Assuming this is also fillable
        'last_login_ip', // Assuming this is also fillable
    ];

    public function pengajuan()
    {
        return $this->hasMany(Pengajuan::class, 'unique_id', 'unique_id');
    }

    public function gudangBarang()
    {
        return $this->belongsToMany(Barang::class, 'tb_gudang', 'unique_id', 'id_barang')
                       ->using(Gudang::class)
                       ->withPivot('jumlah_barang');
    }
    
    public function setPasswordAttribute($value)
    {
        // Check if the value is already hashed to avoid double-hashing
        if (Hash::needsRehash($value)) {
            $this->attributes['password'] = Hash::make($value);
        } else {
            $this->attributes['password'] = $value;
        }
    }

    public function getAuthIdentifierName()
    {
        return 'unique_id';
    }

    public function penggunaanBarang()
    {
        return $this->hasMany(PenggunaanBarang::class, 'unique_id', 'unique_id');
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            if (empty($user->unique_id)) {
                $user->unique_id = (string) Str::ulid();
            }
        });
    }
}