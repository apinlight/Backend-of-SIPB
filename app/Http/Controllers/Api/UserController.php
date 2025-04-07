<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\Hash;

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
            'role_id'     => 'required|integer',
            'branch_name' => 'required|string|max:50',
        ]);

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        return response()->json($user, HttpResponse::HTTP_CREATED);
    }

    // PUT/PATCH /api/users/{unique_id}
    public function update(Request $request, $unique_id)
    {
        $user = User::findOrFail($unique_id);
        $data = $request->validate([
            'username'    => 'sometimes|string|max:50',
            'password'   => 'sometimes|string',
            'role_id'     => 'sometimes|integer',
            'branch_name' => 'sometimes|string|max:50',
        ]);

        $data['password'] = Hash::make($data['password']);
        
        $user->update($data);
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
