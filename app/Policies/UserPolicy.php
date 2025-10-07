<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Admin dapat melakukan aksi apa pun.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        // BENAR: Admin dan Manager dapat melihat daftar pengguna.
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function view(User $user, User $targetUser): bool
    {
        // ✅ PERUBAHAN: Manager sekarang adalah peran pusat dan dapat melihat semua pengguna.
        if ($user->hasRole('manager')) {
            return true;
        }
        
        // Pengguna biasa hanya dapat melihat profil mereka sendiri.
        return $user->unique_id === $targetUser->unique_id;
    }

    public function create(User $user): bool
    {
        // BENAR: Hanya admin yang dapat membuat pengguna baru.
        return $user->hasRole('admin');
    }

    public function update(User $user, User $targetUser): bool
    {
        // ✅ PERUBAHAN: Hanya admin yang dapat mengubah pengguna lain.
        // Pengguna biasa hanya dapat memperbarui profil mereka sendiri.
        // Manager TIDAK BISA lagi mengubah pengguna.
        if ($user->hasRole('admin')) {
            return true;
        }
        
        return $user->unique_id === $targetUser->unique_id;
    }

    public function delete(User $user, User $targetUser): bool
    {
        // BENAR: Admin dapat menghapus siapa pun kecuali diri mereka sendiri.
        return $user->hasRole('admin') && $user->unique_id !== $targetUser->unique_id;
    }
}
