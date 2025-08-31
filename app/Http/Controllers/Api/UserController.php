<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        // ✅ Add authorization check
        $this->authorize('viewAny', User::class);
        
        $user = Auth::user();
        $query = User::with(['roles']);
        
        // ✅ CRITICAL: Apply role-based scope filtering
        if ($user->hasRole('admin')) {
            // ✅ Admin: Can see all users
            // Optional branch filter
            if ($request->filled('branch')) {
                $query->where('branch_name', $request->branch);
            }
            
            // Optional role filter
            if ($request->filled('role')) {
                $query->whereHas('roles', fn($q) => 
                    $q->where('name', $request->role)
                );
            }
            
        } elseif ($user->hasRole('manager')) {
            // ✅ Manager: Only their branch users
            $query->where('branch_name', $user->branch_name);
            
        } else {
            // ✅ Regular users cannot list other users
            return response()->json([
                'status' => false, 
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        // ✅ Additional filters
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('username', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('branch_name', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        $users = $query->paginate(20);
        return UserResource::collection($users);
    }

    public function store(Request $request)
    {
        // ✅ Add authorization check
        $this->authorize('create', User::class);
        
        $user = Auth::user();
        
        $data = $request->validate([
            'unique_id'    => 'required|string|unique:tb_users,unique_id',
            'username'     => 'required|string|max:255|unique:tb_users,username',
            'email'        => 'required|email|max:255|unique:tb_users,email',
            'password'     => 'required|string|min:6|confirmed',
            'branch_name'  => 'required|string|max:255',
            'is_active'    => 'sometimes|boolean',
            'roles'        => 'sometimes|array',
            'roles.*'      => 'string|exists:roles,name',
        ]);

        // ✅ SECURITY: Manager can only create users in their branch
        if ($user->hasRole('manager') && $data['branch_name'] !== $user->branch_name) {
            return response()->json([
                'status' => false,
                'message' => 'Manager can only create users in their own branch'
            ], 403);
        }

        // ✅ SECURITY: Manager cannot assign admin role
        if ($user->hasRole('manager') && isset($data['roles']) && in_array('admin', $data['roles'])) {
            return response()->json([
                'status' => false,
                'message' => 'Manager cannot assign admin role'
            ], 403);
        }

        // ✅ Hash password
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $data['is_active'] ?? true;

        // ✅ Create user
        $newUser = User::create($data);

        // ✅ Assign roles if provided
        if (isset($data['roles'])) {
            $newUser->assignRole($data['roles']);
        } else {
            // ✅ Default role is 'user'
            $newUser->assignRole('user');
        }

        return (new UserResource($newUser->load('roles')))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show($unique_id)
    {
        $user = Auth::user();
        $query = User::with(['roles'])->where('unique_id', $unique_id);

        // ✅ CRITICAL: Apply scope filtering before finding
        if ($user->hasRole('admin')) {
            // Admin can see any user
        } elseif ($user->hasRole('manager')) {
            // Manager: Only their branch users or themselves
            $query->where(function($q) use ($user) {
                $q->where('branch_name', $user->branch_name)
                  ->orWhere('unique_id', $user->unique_id);
            });
        } else {
            // Regular user: Only themselves
            $query->where('unique_id', $user->unique_id);
        }

        $targetUser = $query->firstOrFail();
        $this->authorize('view', $targetUser);
        
        return new UserResource($targetUser);
    }

    public function update(Request $request, $unique_id)
    {
        $user = Auth::user();
        $query = User::where('unique_id', $unique_id);

        // ✅ CRITICAL: Apply scope filtering before finding
        if ($user->hasRole('admin')) {
            // Admin can update any user
        } elseif ($user->hasRole('manager')) {
            // Manager: Only their branch users or themselves
            $query->where(function($q) use ($user) {
                $q->where('branch_name', $user->branch_name)
                  ->orWhere('unique_id', $user->unique_id);
            });
        } else {
            // Regular user: Only themselves
            $query->where('unique_id', $user->unique_id);
        }

        $targetUser = $query->firstOrFail();
        $this->authorize('update', $targetUser);

        $data = $request->validate([
            'username'     => ['sometimes', 'required', 'string', 'max:255', 
                              Rule::unique('tb_users', 'username')->ignore($targetUser->unique_id, 'unique_id')],
            'email'        => ['sometimes', 'required', 'email', 'max:255', 
                              Rule::unique('tb_users', 'email')->ignore($targetUser->unique_id, 'unique_id')],
            'password'     => 'sometimes|nullable|string|min:6|confirmed',
            'branch_name'  => 'sometimes|required|string|max:255',
            'is_active'    => 'sometimes|boolean',
            'roles'        => 'sometimes|array',
            'roles.*'      => 'string|exists:roles,name',
        ]);

        // ✅ SECURITY: Manager can only update users in their branch
        if ($user->hasRole('manager') && isset($data['branch_name']) && 
            $data['branch_name'] !== $user->branch_name && $targetUser->unique_id !== $user->unique_id) {
            return response()->json([
                'status' => false,
                'message' => 'Manager can only update users in their own branch'
            ], 403);
        }

        // ✅ SECURITY: Manager cannot assign admin role
        if ($user->hasRole('manager') && isset($data['roles']) && in_array('admin', $data['roles'])) {
            return response()->json([
                'status' => false,
                'message' => 'Manager cannot assign admin role'
            ], 403);
        }

        // ✅ SECURITY: Users cannot change their own roles or branch
        if (!$user->hasRole(['admin', 'manager']) && $targetUser->unique_id === $user->unique_id) {
            unset($data['roles'], $data['branch_name'], $data['is_active']);
        }

        // ✅ Hash password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // ✅ Update user
        $targetUser->update($data);

        // ✅ Update roles if provided and user has permission
        if (isset($data['roles']) && ($user->hasRole('admin') || 
            ($user->hasRole('manager') && !in_array('admin', $data['roles'])))) {
            $targetUser->syncRoles($data['roles']);
        }

        return new UserResource($targetUser->fresh(['roles']));
    }

    public function destroy($unique_id)
    {
        $user = Auth::user();
        $query = User::where('unique_id', $unique_id);

        // ✅ CRITICAL: Apply scope filtering before finding
        if ($user->hasRole('admin')) {
            // Admin can delete any user (except themselves)
            if ($unique_id === $user->unique_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete your own account'
                ], 422);
            }
        } elseif ($user->hasRole('manager')) {
            // Manager: Only their branch users (but not themselves)
            $query->where('branch_name', $user->branch_name)
                  ->where('unique_id', '!=', $user->unique_id);
        } else {
            // Regular users cannot delete accounts
            return response()->json([
                'status' => false, 
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        $targetUser = $query->firstOrFail();
        $this->authorize('delete', $targetUser);

        // ✅ SECURITY: Cannot delete admin users unless you're admin
        if ($targetUser->hasRole('admin') && !$user->hasRole('admin')) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete admin users'
            ], 403);
        }

        // ✅ Check if user has active pengajuan
        $activePengajuan = $targetUser->pengajuan()
            ->whereIn('status_pengajuan', ['Menunggu Persetujuan', 'Disetujui'])
            ->count();

        if ($activePengajuan > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete user with active pengajuan'
            ], 422);
        }

        $targetUser->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    public function toggleStatus(Request $request, $unique_id)
    {
        $user = Auth::user();
        
        // ✅ Only admin and manager can toggle user status
        if (!$user->hasRole(['admin', 'manager'])) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        $query = User::where('unique_id', $unique_id);

        // ✅ Apply scope filtering
        if ($user->hasRole('manager')) {
            $query->where('branch_name', $user->branch_name)
                  ->where('unique_id', '!=', $user->unique_id);
        } elseif ($user->hasRole('admin')) {
            // Admin cannot deactivate themselves
            if ($unique_id === $user->unique_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot deactivate your own account'
                ], 422);
            }
        }

        $targetUser = $query->firstOrFail();
        
        // ✅ Toggle status
        $targetUser->is_active = !$targetUser->is_active;
        $targetUser->save();

        return new UserResource($targetUser->load('roles'));
    }

    public function resetPassword(Request $request, $unique_id)
    {
        $user = Auth::user();
        
        // ✅ Only admin and manager can reset passwords
        if (!$user->hasRole(['admin', 'manager'])) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        $query = User::where('unique_id', $unique_id);

        // ✅ Apply scope filtering
        if ($user->hasRole('manager')) {
            $query->where('branch_name', $user->branch_name);
        }

        $targetUser = $query->firstOrFail();

        $data = $request->validate([
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $targetUser->password = Hash::make($data['new_password']);
        $targetUser->save();

        return response()->json([
            'status' => true,
            'message' => 'Password reset successfully'
        ]);
    }

    public function profile()
    {
        $user = Auth::user();
        return new UserResource($user->load('roles'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'username' => ['sometimes', 'required', 'string', 'max:255', 
                          Rule::unique('tb_users', 'username')->ignore($user->unique_id, 'unique_id')],
            'email'    => ['sometimes', 'required', 'email', 'max:255', 
                          Rule::unique('tb_users', 'email')->ignore($user->unique_id, 'unique_id')],
            'password' => 'sometimes|nullable|string|min:6|confirmed',
        ]);

        // ✅ Hash password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        return new UserResource($user->fresh(['roles']));
    }
}
