<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Pengajuan;
use Illuminate\Auth\Access\Response;

class PengajuanPolicy
{
    /**
     * Admins can perform any action.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return null; // Let other methods decide
    }

    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view the list (the query scope will filter)
    }

    public function view(User $user, Pengajuan $pengajuan): bool
    {
        if ($user->hasRole('manager')) {
            // Manager can view requests from their own branch
            return $user->branch_name === $pengajuan->user->branch_name;
        }
        // User can only view their own request
        return $user->unique_id === $pengajuan->unique_id;
    }

    public function create(User $user): bool
    {
        // Any authenticated user can attempt to create a request.
        return true;
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

        // Admins are handled by the before() method.
        // All other users are denied permission to update (which includes changing status).
        return Response::deny('You do not have permission to update this request.');
    }

    public function delete(User $user, Pengajuan $pengajuan): Response
    {
        // First, check if the request is in a state where it can be deleted at all.
        if (!$pengajuan->canBeDeleted()) {
            return Response::deny('You cannot delete a request that has already been processed.');
        }

        // Then, check if the current user is the owner (Admins are handled by before()).
        return $user->unique_id === $pengajuan->unique_id
            ? Response::allow()
            : Response::deny('You do not own this request.');
    }
}