<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        // $this->middleware(['auth:sanctum']);
        // $this->middleware('permission:users.view')->only(['index', 'show']);
        // $this->middleware('permission:users.create')->only(['store']);
        // $this->middleware('permission:users.update')->only(['update']);
        // $this->middleware('permission:users.delete')->only(['destroy']);
    }

    /**
     * @OA\Get(
     *     path="/users",
     *     summary="Get list of users",
     *     description="Mendapatkan daftar semua users dengan pagination",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name or email",
     *         required=false,
     *         @OA\Schema(type="string", example="supervisor")
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filter by role",
     *         required=false,
     *         @OA\Schema(type="string", example="supervisor")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daftar users berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar users berhasil diambil"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Tidak memiliki permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tidak memiliki akses")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $params = [
                'per_page' => $request->get('per_page', 10),
                'search' => $request->get('search'),
                'role' => $request->get('role'),
            ];

            $users = $this->userService->getUsers($params);

            return response()->json([
                'success' => true,
                'message' => 'Daftar users berhasil diambil',
                'data' => [
                    'data' => UserResource::collection($users->items()),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar users',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/users",
     *     summary="Create new user",
     *     description="Membuat user baru dengan role",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nama","email","password","role"},
     *             @OA\Property(property="nama", type="string", example="User Baru"),
     *             @OA\Property(property="email", type="string", format="email", example="user@ncs.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123"),
     *             @OA\Property(property="nomor_telepon", type="string", example="081234567890"),
     *             @OA\Property(property="role", type="string", enum={"supervisor", "leader-paket", "gudang"}, example="gudang"),
     *             @OA\Property(property="aktif", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User berhasil dibuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User berhasil dibuat"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Data tidak valid"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'User berhasil dibuat',
                'data' => new UserResource($user)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat user',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/users/{id}",
     *     summary="Get user by ID",
     *     description="Mendapatkan detail user berdasarkan ID",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detail user berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Detail user berhasil diambil"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User tidak ditemukan")
     *         )
     *     )
     * )
     */
    public function show(User $user): JsonResponse
    {
        try {
            $userWithRelations = $this->userService->getUserById($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Detail user berhasil diambil',
                'data' => new UserResource($userWithRelations)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail user',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/users/{id}",
     *     summary="Update user",
     *     description="Update data user berdasarkan ID",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nama", type="string", example="User Updated"),
     *             @OA\Property(property="email", type="string", format="email", example="user-updated@ncs.com"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="nomor_telepon", type="string", example="081234567899"),
     *             @OA\Property(property="role", type="string", enum={"supervisor", "leader-paket", "gudang"}, example="leader-paket"),
     *             @OA\Property(property="aktif", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User berhasil diupdate",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User berhasil diupdate"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $updatedUser = $this->userService->updateUser($user, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'User berhasil diupdate',
                'data' => new UserResource($updatedUser)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate user',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/users/{id}",
     *     summary="Delete user",
     *     description="Hapus user berdasarkan ID",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User berhasil dihapus")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot delete user",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tidak dapat menghapus user sendiri")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        try {
            $this->userService->deleteUser($user, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'User berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null
            ], $e->getMessage() === 'Tidak dapat menghapus user sendiri' ? 400 : 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/users/roles",
     *     summary="Get available roles",
     *     description="Mendapatkan daftar role yang tersedia",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Daftar roles berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar roles berhasil diambil"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="name", type="string", example="supervisor"),
     *                 @OA\Property(property="display_name", type="string", example="Supervisor")
     *             ))
     *         )
     *     )
     * )
     */
    public function roles(): JsonResponse
    {
        try {
            $roles = $this->userService->getRoles();

            return response()->json([
                'success' => true,
                'message' => 'Daftar roles berhasil diambil',
                'data' => $roles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar roles',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/users/stats",
     *     summary="Get user statistics",
     *     description="Mendapatkan statistik users",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistik users berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statistik users berhasil diambil"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_users", type="integer", example=10),
     *                 @OA\Property(property="active_users", type="integer", example=8),
     *                 @OA\Property(property="inactive_users", type="integer", example=2),
     *                 @OA\Property(property="by_role", type="array", @OA\Items(
     *                     @OA\Property(property="role", type="string", example="supervisor"),
     *                     @OA\Property(property="count", type="integer", example=1)
     *                 ))
     *             )
     *         )
     *     )
     * )
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->userService->getUserStats();

            return response()->json([
                'success' => true,
                'message' => 'Statistik users berhasil diambil',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik users',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/users/{id}/toggle-status",
     *     summary="Toggle user active status",
     *     description="Mengaktifkan/menonaktifkan user",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status user berhasil diubah",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Status user berhasil diubah"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function toggleStatus(User $user): JsonResponse
    {
        try {
            $updatedUser = $this->userService->toggleUserStatus($user);

            return response()->json([
                'success' => true,
                'message' => 'Status user berhasil diubah',
                'data' => new UserResource($updatedUser)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status user',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}