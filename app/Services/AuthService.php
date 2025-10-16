<?php

namespace App\Services;

use App\Models\User;
use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function login(array $credentials): array
    {
        if (!Auth::attempt($credentials)) {
            return [
                'success' => false,
                'message' => 'Email atau password salah'
            ];
        }

        $user = Auth::user();

        // Check if user is active
        if (!$user->aktif) {
            Auth::logout();
            return [
                'success' => false,
                'message' => 'Akun Anda tidak aktif'
            ];
        }

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Log activity
        LogAktivitas::log('login', 'User melakukan login');

        return [
            'success' => true,
            'user' => $user,
            'token' => $token
        ];
    }

    public function logout(User $user): void
    {
        // Log activity
        LogAktivitas::log('logout', 'User melakukan logout');

        // Delete current token
        $user->currentAccessToken()->delete();
    }

    public function refreshToken(User $user): string
    {
        // Delete current token
        $user->currentAccessToken()->delete();

        // Create new token
        $newToken = $user->createToken('auth-token')->plainTextToken;

        // Log activity
        LogAktivitas::log('refresh', 'User melakukan refresh token');

        return $newToken;
    }
}
