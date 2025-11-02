<?php

namespace App\Policies;

use App\Models\PenggunaanBarang;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PenggunaanBarangPolicy
{
    /**
     * Admins can do anything.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null; // let other methods decide
    }

    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view the list (scope handles filtering)
    }

    public function view(User $user, PenggunaanBarang $penggunaanBarang): bool
    {
        // ✅ FIX: Manager dapat view semua penggunaan barang (global oversight)
        if ($user->hasRole('manager')) {
            return true;
        }

        // User hanya dapat view miliknya
        return $user->unique_id === $penggunaanBarang->unique_id;
    }

    public function create(User $user): bool
    {
        // ✅ FIX: Manager tidak boleh create penggunaan barang (hanya view/oversight)
        return $user->hasAnyRole(['admin', 'user']);
    }

    public function update(User $user, PenggunaanBarang $penggunaanBarang): Response
    {
        // ✅ FIX: Admin selalu bisa (handled by before()); user hanya miliknya
        // Tidak ada status check karena auto-approve (tidak ada pending state)
        return $user->unique_id === $penggunaanBarang->unique_id
            ? Response::allow()
            : Response::deny('You do not own this record.');
    }

    public function delete(User $user, PenggunaanBarang $penggunaanBarang): Response
    {
        // ✅ FIX: Hanya admin yang dapat delete (handled by before())
        // User tidak boleh delete even if owned
        return Response::deny('You cannot delete usage records.');
    }
}
