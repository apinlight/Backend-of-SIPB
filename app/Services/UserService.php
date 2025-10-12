<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $data['is_active'] ?? true;

        $user = User::create($data);

        $roles = $data['roles'] ?? [\App\Enums\Role::USER];
        $user->assignRole($roles);

        return $user;
    }

    public function update(User $user, array $data): User
    {
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user->fresh(); // Return a fresh instance with updated relations
    }

    public function delete(User $userToDelete, User $currentUser): void
    {
        if ($userToDelete->is($currentUser)) {
            throw new Exception('You cannot delete your own account.');
        }

        $activePengajuan = $userToDelete->pengajuan()
            ->whereIn('status_pengajuan', ['Menunggu Persetujuan', 'Disetujui'])
            ->exists();

        if ($activePengajuan) {
            throw new Exception('Cannot delete a user with active pengajuan.');
        }

        $userToDelete->delete();
    }

    public function toggleStatus(User $userToToggle, User $currentUser): User
    {
        if ($userToToggle->is($currentUser)) {
            throw new Exception('You cannot deactivate your own account.');
        }

        $userToToggle->update(['is_active' => ! $userToToggle->is_active]);

        return $userToToggle;
    }
}
