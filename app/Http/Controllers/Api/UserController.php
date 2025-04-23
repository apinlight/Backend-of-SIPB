<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class UserController extends Controller
{
    // GET /api/users
    public function index()
    {
        return response()->json(User::all(), HttpResponse::HTTP_OK);
    }

    // GET /api/users/{unique_id}
    public function show($unique_id)
    {
        $user = User::findOrFail($unique_id);
        return response()->json($user, HttpResponse::HTTP_OK);
    }    

    // POST /api/users
    public function store(Request $request)
    {
        // Only admin can add users
        if (!$request->user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], HttpResponse::HTTP_FORBIDDEN);
        }
        
        $data = $request->validate([
            'unique_id'   => 'required|string',
            'username'    => 'required|string|max:50',
            'password'   => 'required|string',
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
        
        return response()->json($user, HttpResponse::HTTP_CREATED);
    }

    // PUT/PATCH /api/users/{unique_id}
    public function update(Request $request, $unique_id)
    {
        $user = User::findOrFail($unique_id);

        $data = $request->validate([
            'username'    => 'sometimes|string|max:50',
            'password'   => 'sometimes|string',
            'branch_name' => 'sometimes|string|max:50',
            'role'        => 'sometimes|string|exists:roles,name',
        ]);

        $user->update($data);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return response()->json($user, HttpResponse::HTTP_OK);
    }

    // DELETE /api/users/{unique_id}
    public function destroy(Request $request, $unique_id)
    {
        // Only admin can delete users
        if (!$request->user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], HttpResponse::HTTP_FORBIDDEN);
        }

        $user = User::findOrFail($unique_id);
        $user->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
