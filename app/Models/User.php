<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasRoles;
    // Specify the table name
    protected $table = 'tb_users';

    // The primary key is 'unique_id' (a string)
    protected $primaryKey = 'unique_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $hidden = ['password'];
    public $timestamps = false;


    // Allow mass assignment for these fields (adjust as needed)
    protected $fillable = ['unique_id', 'username', 'password', 'role_id', 'branch_name'];

    // A user has many Pengajuan records
    public function pengajuan()
    {
        return $this->hasMany(Pengajuan::class, 'unique_id', 'unique_id');
    }

    // Many-to-many: a user has many Barang via tb_gudang pivot (with extra attribute jumlah_barang)
    public function gudangItem()
    {
        return $this->belongsToMany(Barang::class, 'tb_gudang', 'unique_id', 'id_barang')
                    ->using(Gudang::class)
                    ->withPivot('jumlah_barang');
    }
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }
}
