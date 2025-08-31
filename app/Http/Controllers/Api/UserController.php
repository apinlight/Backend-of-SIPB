<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected UserService $userService)
    {
        // Use the 'user' parameter name for route model binding
        $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        // This would be a great place for a dedicated Filter class in a larger app
        $query = User::with('roles'); // Scoping should be handled by the Policy now

        $users = $query->paginate(20);
        return UserResource::collection($users)->response();
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return (new UserResource($user->load('roles')))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(User $user): JsonResponse
    {
        return (new UserResource($user->load('roles')))->response();
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $updatedUser = $this->userService->update($user, $request->validated());
        return (new UserResource($updatedUser->load('roles')))->response();
    }

    public function destroy(User $user, Request $request): JsonResponse
    {
        $this->userService->delete($user, $request->user());
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
    
    // Custom Actions
    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user); // Reuse the update permission
        $updatedUser = $this->userService->toggleStatus($user, $request->user());
        return (new UserResource($updatedUser->load('roles')))->response();
    }
}