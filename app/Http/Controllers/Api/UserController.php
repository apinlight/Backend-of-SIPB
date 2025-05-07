<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class UserController extends Controller
{
    use AuthorizesRequests;
    // GET /api/users
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);
        return UserResource::collection(User::all());
    }

    // GET /api/users/{unique_id}
    public function show(Request $request, $unique_id)
    {
        $user = User::findOrFail($unique_id);
        $this->authorize('view', $user);
        return new UserResource($user);
    }

    // POST /api/users
    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'unique_id'   => 'required|string',
            'username'    => 'required|string|max:50',
            'password'    => 'required|string',
            'branch_name' => 'required|string|max:50',
            'role'        => 'required|string|in:admin,manager,user',
        ]);

        $user = User::create([
            'unique_id'   => $data['unique_id'],
            'username'    => $data['username'],
            'password'    => $data['password'],
            'branch_name' => $data['branch_name'],
        ]);

        $user->assignRole($data['role']);

        return (new UserResource($user))->response()->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    // PUT/PATCH /api/users/{unique_id}
    public function update(Request $request, $unique_id)
    {
        $user = User::findOrFail($unique_id);
        $this->authorize('update', $user);

        $data = $request->validate([
            'username'    => 'sometimes|string|max:50',
            'password'    => 'sometimes|string',
            'branch_name' => 'sometimes|string|max:50',
            'role'        => 'sometimes|string|exists:roles,name',
        ]);

        $user->update($data);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return new UserResource($user);
    }

    // DELETE /api/users/{unique_id}
    public function destroy(Request $request, $unique_id)
    {
        $user = User::findOrFail($unique_id);
        $this->authorize('delete', $user);

        $user->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
