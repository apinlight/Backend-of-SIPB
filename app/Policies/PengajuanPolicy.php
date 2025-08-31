<?php
// app/Policies/PengajuanPolicy.php
namespace App\Policies;

use App\Models\User;
use App\Models\Pengajuan;

class PengajuanPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'user']);
    }

    public function view(User $user, Pengajuan $pengajuan)
    {
        if ($user->hasRole('admin')) {
            return true; // Admin can view all pengajuan
        }
        
        if ($user->hasRole('manager')) {
            // Manager can view pengajuan from same branch
            return $user->branch_name === $pengajuan->user->branch_name;
        }
        
        // User can only view their own pengajuan
        return $user->unique_id === $pengajuan->unique_id;
    }

    public function create(User $user)
    {
        // ✅ FIX: Users can create pengajuan
        return $user->hasRole('user');
    }

    public function update(User $user, Pengajuan $pengajuan)
    {
        if ($user->hasRole('admin')) {
            return true; // Admin can update any pengajuan
        }
        
        if ($user->hasRole('user')) {
            // ✅ FIX: Use correct status
            return $user->unique_id === $pengajuan->unique_id && 
                   $pengajuan->status_pengajuan === 'Menunggu Persetujuan';
        }
        
        return false;
    }

    public function delete(User $user, Pengajuan $pengajuan)
    {
        if ($user->hasRole('admin')) {
            return true; // Admin can delete any pengajuan
        }
        
        // ✅ FIX: Users can only delete their own pending pengajuan
        return $user->unique_id === $pengajuan->unique_id && 
               $pengajuan->status_pengajuan === 'Menunggu Persetujuan';
    }

    // ✅ FIX: Use correct status values
    public function approve(User $user, Pengajuan $pengajuan)
    {
        return $user->hasRole('admin') && 
               $pengajuan->status_pengajuan === 'Menunggu Persetujuan';
    }

    public function reject(User $user, Pengajuan $pengajuan)
    {
        return $user->hasRole('admin') && 
               $pengajuan->status_pengajuan === 'Menunggu Persetujuan';
    }

    // ✅ ADD: Scope-based viewing policies
    public function viewOwn(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'user']);
    }

    public function viewBranch(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function viewAll(User $user)
    {
        return $user->hasRole('admin');
    }

    // ✅ ADD: Approval workflow policies
    public function viewPending(User $user)
    {
        return $user->hasRole('admin');
    }

    public function viewApproved(User $user)
    {
        return $user->hasRole('admin');
    }

    // ✅ ADD: Manual pengajuan policy
    public function createManual(User $user)
    {
        return $user->hasRole('admin');
    }
}
