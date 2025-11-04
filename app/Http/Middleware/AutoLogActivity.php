<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

class AutoLogActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Hanya log jika request berhasil (status 200-299)
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $this->logActivity($request, $response);
        }

        return $response;
    }

    /**
     * Log the activity based on request method and route.
     */
    private function logActivity(Request $request, $response)
    {
        $route = Route::current();
        $method = $request->method();
        $routeName = $route->getName();
        $uri = $request->getRequestUri();

        // Skip logging untuk route tertentu
        $skipRoutes = [
            'auth.me',
            'auth.refresh',
            'log-aktivitas.index',
            'log-aktivitas.show',
            'log-aktivitas.statistics',
            'log-aktivitas.my-activities'
        ];

        if (in_array($routeName, $skipRoutes)) {
            return;
        }

        // Tentukan aksi berdasarkan HTTP method
        $aksi = $this->getActionFromMethod($method);
        
        if (!$aksi) {
            return;
        }

        // Ambil informasi model dari route parameter
        $modelInfo = $this->getModelInfoFromRoute($route, $request);
        
        // Generate deskripsi
        $deskripsi = $this->generateDescription($aksi, $routeName, $modelInfo, $request);

        // Ambil data untuk logging
        $dataLama = null;
        $dataBaru = null;

        if ($method === 'POST') {
            $dataBaru = $this->sanitizeData($request->all());
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            $dataBaru = $this->sanitizeData($request->all());
            // Data lama bisa diambil dari model jika diperlukan
        }

        try {
            LogAktivitas::create([
                'aksi' => $aksi,
                'deskripsi' => $deskripsi,
                'user_id' => Auth::id(),
                'model_type' => $modelInfo['type'] ?? null,
                'model_id' => $modelInfo['id'] ?? null,
                'data_lama' => $dataLama,
                'data_baru' => $dataBaru,
                'timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            // Silent fail - jangan sampai logging error mengganggu aplikasi
            Log::error('Failed to log activity: ' . $e->getMessage());
        }
    }

    /**
     * Get action from HTTP method.
     */
    private function getActionFromMethod(string $method): ?string
    {
        $actions = [
            'POST' => 'create',
            'PUT' => 'update',
            'PATCH' => 'update',
            'DELETE' => 'delete',
        ];

        return $actions[$method] ?? null;
    }

    /**
     * Get model information from route parameters.
     */
    private function getModelInfoFromRoute($route, Request $request): array
    {
        $parameters = $route->parameters();
        $routeName = $route->getName();

        // Mapping route ke model
        $routeModelMapping = [
            'users' => 'App\\Models\\User',
            'gudang' => 'App\\Models\\Gudang',
            'area-gudang' => 'App\\Models\\AreaGudang',
            'kategori-barang' => 'App\\Models\\KategoriBarang',
            'barang' => 'App\\Models\\Barang',
            'penempatan-barang' => 'App\\Models\\PenempatanBarang',
            'log-optimasi' => 'App\\Models\\LogOptimasi',
            'rekomendasi-penempatan' => 'App\\Models\\RekomendasiPenempatan',
        ];

        foreach ($routeModelMapping as $prefix => $modelClass) {
            if (str_contains($routeName, $prefix)) {
                // Cari parameter yang mungkin berisi ID
                foreach ($parameters as $key => $value) {
                    if (is_numeric($value) || (is_object($value) && method_exists($value, 'getKey'))) {
                        return [
                            'type' => $modelClass,
                            'id' => is_object($value) ? $value->getKey() : $value
                        ];
                    }
                }
                break;
            }
        }

        return [];
    }

    /**
     * Generate description based on action and context.
     */
    private function generateDescription(string $aksi, ?string $routeName, array $modelInfo, Request $request): string
    {
        $descriptions = [
            'create' => [
                'users' => 'Menambahkan user baru',
                'gudang' => 'Menambahkan gudang baru',
                'area-gudang' => 'Menambahkan area gudang baru',
                'kategori-barang' => 'Menambahkan kategori barang baru',
                'barang' => 'Menambahkan barang baru',
                'penempatan-barang' => 'Menambahkan penempatan barang baru',
                'log-optimasi' => 'Memulai proses optimasi',
                'rekomendasi-penempatan' => 'Membuat rekomendasi penempatan',
                'default' => 'Menambahkan data baru'
            ],
            'update' => [
                'users' => 'Mengubah data user',
                'gudang' => 'Mengubah data gudang',
                'area-gudang' => 'Mengubah data area gudang',
                'kategori-barang' => 'Mengubah data kategori barang',
                'barang' => 'Mengubah data barang',
                'penempatan-barang' => 'Mengubah penempatan barang',
                'log-optimasi' => 'Mengubah data optimasi',
                'rekomendasi-penempatan' => 'Mengubah rekomendasi penempatan',
                'default' => 'Mengubah data'
            ],
            'delete' => [
                'users' => 'Menghapus user',
                'gudang' => 'Menghapus gudang',
                'area-gudang' => 'Menghapus area gudang',
                'kategori-barang' => 'Menghapus kategori barang',
                'barang' => 'Menghapus barang',
                'penempatan-barang' => 'Menghapus penempatan barang',
                'log-optimasi' => 'Menghapus data optimasi',
                'rekomendasi-penempatan' => 'Menghapus rekomendasi penempatan',
                'default' => 'Menghapus data'
            ]
        ];

        // Cari prefix yang cocok
        $prefix = 'default';
        if ($routeName) {
            foreach (array_keys($descriptions[$aksi]) as $key) {
                if ($key !== 'default' && str_contains($routeName, $key)) {
                    $prefix = $key;
                    break;
                }
            }
        }

        $baseDescription = $descriptions[$aksi][$prefix];

        // Tambahkan detail jika ada
        if (isset($modelInfo['id'])) {
            $baseDescription .= " (ID: {$modelInfo['id']})";
        }

        // Tambahkan informasi khusus untuk aksi tertentu
        if ($aksi === 'create' && $request->has('nama')) {
            $baseDescription .= " - {$request->input('nama')}";
        } elseif ($aksi === 'create' && $request->has('kode_barang')) {
            $baseDescription .= " - {$request->input('kode_barang')}";
        }

        return $baseDescription;
    }

    /**
     * Sanitize data for logging (remove sensitive information).
     */
    private function sanitizeData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'token',
            'access_token',
            'refresh_token',
            '_token',
            '_method'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[HIDDEN]';
            }
        }

        return $data;
    }
}