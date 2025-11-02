<?php

namespace App\Policies;

use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PengajuanPolicy
{
    /**
     * Admins can perform any action.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true; // The controller's query scope will handle filtering.
    }

    public function view(User $user, Pengajuan $pengajuan): bool
    {
        // ✅ FIX: Manager dapat view semua pengajuan (global oversight)
        if ($user->hasRole('manager')) {
            return true;
        }

        // User hanya dapat view miliknya
        return $user->unique_id === $pengajuan->unique_id;
    }

    public function create(User $user): bool
    {
        // ✅ FIX: Manager tidak boleh create pengajuan (hanya view/oversight)
        return $user->hasAnyRole(['admin', 'user']);
    }

    /**
     * ✅ FIX: Hanya admin yang dapat approve/update pengajuan.
     * Manager tidak memiliki wewenang untuk approve pengajuan (read-only/oversight).
     * Admins are handled by the before() method.
     */
    public function update(User $user, Pengajuan $pengajuan): Response
    {
        // Manager tidak boleh update/approve
        // Admins handled by before()
        // Users tidak boleh update
        return Response::deny('You do not have permission to update this request.');
    }

    public function delete(User $user, Pengajuan $pengajuan): Response
    {
        if (! $pengajuan->canBeDeleted()) {
            return Response::deny('You cannot delete a request that has already been processed.');
        }

        return $user->unique_id === $pengajuan->unique_id
            ? Response::allow()
            : Response::deny('You do not own this request.');
    }
}
