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
        if ($user->hasRole('manager')) {
            return $user->branch_name === $pengajuan->user->branch_name;
        }

        return $user->unique_id === $pengajuan->unique_id;
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can attempt to create a request.
    }

    /**
     * ✅ FIX: This is the corrected update logic.
     * It only allows Managers to update requests from their own branch.
     * Admins are handled by the before() method.
     * Regular users are now correctly forbidden from updating (approving/rejecting).
     */
    public function update(User $user, Pengajuan $pengajuan): Response
    {
        if ($user->hasRole('manager')) {
            return $user->branch_name === $pengajuan->user->branch_name
                ? Response::allow()
                : Response::deny('You can only update requests from your own branch.');
        }

        // Admins are handled by before(). All other users are denied.
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
