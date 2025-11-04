<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use App\Http\Resources\UserResource;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="NCS Warehouse Management API",
 *     version="1.0.0",
 *     description="API untuk manajemen gudang PT. NCS Bandung dengan algoritma Simulated Annealing",
 *     @OA\Contact(
 *         email="dev@ncs.com",
 *         name="NCS Development Team"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="Model user",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="aktif", type="boolean", example=true),
 *     @OA\Property(property="role", type="string", example="admin"),
 *     @OA\Property(property="status_text", type="string", example="Aktif"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Gudang",
 *     type="object",
 *     title="Gudang",
 *     description="Model gudang",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nama_gudang", type="string", example="Gudang Utama"),
 *     @OA\Property(property="alamat", type="string", example="Jl. Sudirman No. 123"),
 *     @OA\Property(property="koordinat_x", type="number", format="float", example=106.8456),
 *     @OA\Property(property="koordinat_y", type="number", format="float", example=-6.2088),
 *     @OA\Property(property="panjang", type="number", format="float", example=100.5),
 *     @OA\Property(property="lebar", type="number", format="float", example=80.0),
 *     @OA\Property(property="tinggi", type="number", format="float", example=15.0),
 *     @OA\Property(property="aktif", type="boolean", example=true),
 *     @OA\Property(property="kapasitas", type="number", format="float", example=120600.0),
 *     @OA\Property(property="kapasitas_terpakai", type="number", format="float", example=45200.0),
 *     @OA\Property(property="sisa_kapasitas", type="number", format="float", example=75400.0),
 *     @OA\Property(property="persentase_kapasitas", type="number", format="float", example=37.5),
 *     @OA\Property(property="jumlah_area", type="integer", example=12),
 *     @OA\Property(property="area_tersedia", type="integer", example=8),
 *     @OA\Property(property="status_text", type="string", example="Aktif"),
 *     @OA\Property(property="dimensi", type="string", example="100.5 x 80.0 x 15.0 m"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="AreaGudang",
 *     type="object",
 *     title="Area Gudang",
 *     description="Model area gudang",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="gudang_id", type="integer", example=1),
 *     @OA\Property(property="kode_area", type="string", example="A1-01"),
 *     @OA\Property(property="nama_area", type="string", example="Area A1 - Rak Utama"),
 *     @OA\Property(property="koordinat_x", type="number", format="float", example=10.5),
 *     @OA\Property(property="koordinat_y", type="number", format="float", example=5.0),
 *     @OA\Property(property="panjang", type="number", format="float", example=8.0),
 *     @OA\Property(property="lebar", type="number", format="float", example=6.0),
 *     @OA\Property(property="tinggi", type="number", format="float", example=4.0),
 *     @OA\Property(property="jenis_area", type="string", enum={"rak","lantai","khusus"}, example="rak"),
 *     @OA\Property(property="tersedia", type="boolean", example=true),
 *     @OA\Property(property="kapasitas", type="number", format="float", example=192.0),
 *     @OA\Property(property="kapasitas_terpakai", type="number", format="float", example=45.0),
 *     @OA\Property(property="sisa_kapasitas", type="number", format="float", example=147.0),
 *     @OA\Property(property="persentase_kapasitas", type="number", format="float", example=23.4),
 *     @OA\Property(property="status_text", type="string", example="Tersedia"),
 *     @OA\Property(property="jenis_text", type="string", example="Rak"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="gudang",
 *         ref="#/components/schemas/Gudang"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="KategoriBarang",
 *     type="object",
 *     title="Kategori Barang",
 *     description="Model kategori barang",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nama_kategori", type="string", example="Elektronik"),
 *     @OA\Property(property="kode_kategori", type="string", example="ELK"),
 *     @OA\Property(property="deskripsi", type="string", example="Kategori untuk barang elektronik"),
 *     @OA\Property(property="aktif", type="boolean", example=true),
 *     @OA\Property(property="jumlah_barang", type="integer", example=25),
 *     @OA\Property(property="status_text", type="string", example="Aktif"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Barang",
 *     type="object",
 *     title="Barang",
 *     description="Model barang",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="kode_barang", type="string", example="BRG001"),
 *     @OA\Property(property="nama_barang", type="string", example="Laptop Acer Aspire 5"),
 *     @OA\Property(property="kategori_barang_id", type="integer", example=1),
 *     @OA\Property(property="panjang", type="number", format="float", example=35.6),
 *     @OA\Property(property="lebar", type="number", format="float", example=25.4),
 *     @OA\Property(property="tinggi", type="number", format="float", example=2.3),
 *     @OA\Property(property="berat", type="number", format="float", example=1.8),
 *     @OA\Property(property="mudah_pecah", type="boolean", example=false),
 *     @OA\Property(property="prioritas", type="string", enum={"rendah","sedang","tinggi"}, example="sedang"),
 *     @OA\Property(property="deskripsi", type="string", example="Laptop untuk kebutuhan kantor"),
 *     @OA\Property(property="barcode", type="string", example="BRG001-QR123456"),
 *     @OA\Property(property="aktif", type="boolean", example=true),
 *     @OA\Property(property="volume", type="number", format="float", example=0.002068),
 *     @OA\Property(property="total_stok", type="integer", example=5),
 *     @OA\Property(property="dimensi", type="string", example="35.6 x 25.4 x 2.3 cm"),
 *     @OA\Property(property="prioritas_text", type="string", example="Sedang"),
 *     @OA\Property(property="status_text", type="string", example="Aktif"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="kategori_barang",
 *         ref="#/components/schemas/KategoriBarang"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Development Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class AuthController extends Controller
{
    use LogsActivity;
    
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Login user",
     *     description="Authenticate user dengan email dan password",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="supervisor@ncs.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login berhasil"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="token", type="string", example="1|abc123def456..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Login gagal",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Email atau password salah")
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
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            if (!$result['success']) {
                // Log failed login attempt
                $this->logSecurity(
                    'login_failed',
                    'Percobaan login gagal - ' . $result['message'],
                    ['email' => $request->email]
                );

                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 401);
            }

            // Log successful login
            $this->logLogin($result['user']->id);

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'data' => [
                    'user' => new UserResource($result['user']),
                    'token' => $result['token'],
                    'token_type' => 'Bearer'
                ]
            ]);

        } catch (\Exception $e) {
            // Log login error
            $this->logSecurity(
                'login_error',
                'Error sistem saat login',
                ['error' => $e->getMessage(), 'email' => $request->email ?? 'unknown']
            );

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="Logout user",
     *     description="Logout user dan hapus token",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logout berhasil")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token tidak valid")
     *         )
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            
            $this->authService->logout($request->user());

            // Log successful logout
            $this->logLogout($userId);

            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil'
            ]);

        } catch (\Exception $e) {
            // Log logout error
            $this->logSecurity(
                'logout_error',
                'Error sistem saat logout',
                ['error' => $e->getMessage()]
            );

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/refresh",
     *     summary="Refresh token",
     *     description="Refresh token yang sudah ada",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token berhasil di-refresh",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token berhasil di-refresh"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="2|xyz789abc456..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token tidak valid")
     *         )
     *     )
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $newToken = $this->authService->refreshToken($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Token berhasil di-refresh',
                'data' => [
                    'token' => $newToken,
                    'token_type' => 'Bearer'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/auth/me",
     *     summary="Get current user",
     *     description="Mendapatkan informasi user yang sedang login",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Data user berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data user berhasil diambil"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token tidak valid")
     *         )
     *     )
     * )
     */
    public function me(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Data user berhasil diambil',
                'data' => new UserResource($request->user())
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/auth/update-profile",
     *     summary="Update user profile",
     *     description="Update profile data user yang sedang login",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email"},
     *             @OA\Property(property="name", type="string", example="John Doe Updated"),
     *             @OA\Property(property="email", type="string", format="email", example="john.updated@example.com"),
     *             @OA\Property(property="phone", type="string", example="081234567890"),
     *             @OA\Property(property="address", type="string", example="Jl. Sudirman No. 123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile berhasil diupdate",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile berhasil diupdate"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validasi gagal"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();
            $oldData = $user->only(['name', 'email', 'phone', 'address']);

            // Update user data
            $user->update($validatedData);

            // Log update profile activity
            $this->logUpdated(
                'Mengupdate profile pengguna',
                $user,
                $oldData,
                $validatedData
            );

            return response()->json([
                'success' => true,
                'message' => 'Profile berhasil diupdate',
                'data' => new UserResource($user->fresh())
            ]);

        } catch (\Exception $e) {
            // Log update profile error
            $this->logSecurity(
                'update_profile_error',
                'Error saat update profile',
                ['error' => $e->getMessage()]
            );

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/auth/change-password",
     *     summary="Change user password",
     *     description="Ubah password user yang sedang login",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password", "new_password", "new_password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password", example="oldpassword123"),
     *             @OA\Property(property="new_password", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="new_password_confirmation", type="string", format="password", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password berhasil diubah",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password berhasil diubah")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Password lama tidak cocok",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Password lama tidak cocok")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validasi gagal"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
                'new_password_confirmation' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                // Log failed password change attempt
                $this->logSecurity(
                    'change_password_failed',
                    'Percobaan ubah password dengan password lama yang salah',
                    ['user_id' => $user->id]
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Password lama tidak cocok'
                ], 400);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            // Log successful password change
            $this->logSecurity(
                'change_password_success',
                'Password berhasil diubah',
                ['user_id' => $user->id]
            );

            // Optionally revoke all tokens to force re-login
            if ($request->input('revoke_all_tokens', false)) {
                $user->tokens()->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Password berhasil diubah. Silakan login kembali',
                    'revoked_tokens' => true
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diubah'
            ]);

        } catch (\Exception $e) {
            // Log change password error
            $this->logSecurity(
                'change_password_error',
                'Error saat mengubah password',
                ['error' => $e->getMessage()]
            );

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
