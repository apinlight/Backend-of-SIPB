<?php

namespace App\Policies;

use App\Models\PenggunaanBarang;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PenggunaanBarangPolicy
{
    /**
     * ✅ FIX: Admins can VIEW/DELETE but NOT CREATE/UPDATE usage (they don't have Gudang records)
     * Only users tied to specific branches can record usage.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            // Admin can view and delete, but cannot create/update
            if (in_array($ability, ['create', 'update'])) {
                return false; // Deny create/update for admin
            }
            // Allow view/delete for admin
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
        // ✅ FIX: Only regular Users can create penggunaan barang
        // Admin and Manager are read-only monitors/managers
        return $user->hasRole('user');
    }

    public function update(User $user, PenggunaanBarang $penggunaanBarang): Response
    {
        // ✅ FIX: Only the user who owns the record can update (not admin/manager)
        // Checked in before() - admin cannot reach here
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
