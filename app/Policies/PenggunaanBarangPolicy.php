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
    public function before(User $user, string $ability): bool|null
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
        // A user can view if it's theirs, or if they are a manager of that branch
        if ($user->unique_id === $penggunaanBarang->unique_id) {
            return true;
        }
        return $user->hasRole('manager') && $user->branch_name === $penggunaanBarang->user->branch_name;
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create a request
    }

    public function update(User $user, PenggunaanBarang $penggunaanBarang): Response
    {
        if ($penggunaanBarang->status !== 'pending') {
            return Response::deny('Cannot update a request that has already been processed.');
        }
        return $user->unique_id === $penggunaanBarang->unique_id
            ? Response::allow()
            : Response::deny('You do not own this request.');
    }

    public function delete(User $user, PenggunaanBarang $penggunaanBarang): Response
    {
        if ($penggunaanBarang->status === 'approved') {
            return Response::deny('Cannot delete an approved request.');
        }
        return $user->unique_id === $penggunaanBarang->unique_id
            ? Response::allow()
            : Response::deny('You do not own this request.');
    }

    public function approve(User $user, PenggunaanBarang $penggunaanBarang): Response
    {
        if (!$user->hasRole('manager')) {
            return Response::deny('You do not have permission to approve requests.');
        }
        if ($penggunaanBarang->status !== 'pending') {
            return Response::deny('This request has already been processed.');
        }
        return $user->branch_name === $penggunaanBarang->user->branch_name
            ? Response::allow()
            : Response::deny('You can only approve requests from your own branch.');
    }
}