<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected UserService $userService)
    {
        // Otorisasi sekarang ditangani secara manual di setiap metode untuk kejelasan maksimal.
    }

    public function profile(Request $request): JsonResponse
    {
        return (new UserResource($request->user()->load('roles')))->response();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('roles');

        // ✅ PERUBAHAN: Logika query yang kompleks telah dihapus.
        // Policy dan scope akan menangani siapa yang bisa melihat apa.
        // Jika manajer hanya boleh melihat cabang tertentu, kita bisa menambahkan scope di sini.
        // Namun, karena manajer sekarang adalah peran pusat, mereka bisa melihat semua.

        $users = $query->paginate(20);

        return UserResource::collection($users)->response();
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class); // Otorisasi eksplisit
        $user = $this->userService->create($request->validated());

        return (new UserResource($user->load('roles')))->response()->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user); // Otorisasi eksplisit

        return (new UserResource($user->load('roles')))->response();
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user); // Otorisasi eksplisit
        $updatedUser = $this->userService->update($user, $request->validated());

        return (new UserResource($updatedUser->load('roles')))->response();
    }

    public function destroy(User $user, Request $request): JsonResponse
    {
        $this->authorize('delete', $user); // Otorisasi eksplisit
        $this->userService->delete($user, $request->user());

        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        // ✅ PERUBAHAN: Menggunakan 'update' karena kebijakan kita sekarang sudah benar.
        // Policy akan menolak manajer secara otomatis.
        $this->authorize('update', $user);
        $updatedUser = $this->userService->toggleStatus($user, $request->user());

        return (new UserResource($updatedUser->load('roles')))->response();
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user); // Menggunakan izin 'update' yang sama

        // ... (implementasi pemanggilan service untuk reset password)
        return response()->json(['message' => 'Password reset endpoint needs implementation in service.']);
    }
}
