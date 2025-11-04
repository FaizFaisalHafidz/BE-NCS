<?php

namespace App\Traits;

use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait LogsActivity
{
    /**
     * Log an activity with simplified parameters.
     */
    protected function logActivity(
        string $aksi,
        string $deskripsi,
        $model = null,
        array $dataLama = null,
        array $dataBaru = null,
        int $userId = null
    ): void {
        try {
            $logData = [
                'aksi' => $aksi,
                'deskripsi' => $deskripsi,
                'user_id' => $userId ?? Auth::id(),
                'timestamp' => now(),
            ];

            if ($model) {
                if (is_object($model)) {
                    $logData['model_type'] = get_class($model);
                    $logData['model_id'] = $model->getKey();
                } elseif (is_array($model) && isset($model['type'], $model['id'])) {
                    $logData['model_type'] = $model['type'];
                    $logData['model_id'] = $model['id'];
                }
            }

            if ($dataLama !== null) {
                $logData['data_lama'] = $this->sanitizeLogData($dataLama);
            }

            if ($dataBaru !== null) {
                $logData['data_baru'] = $this->sanitizeLogData($dataBaru);
            }

            LogAktivitas::create($logData);
        } catch (\Exception $e) {
            // Silent fail untuk mencegah error logging mengganggu flow utama
            Log::error('Failed to log activity in trait: ' . $e->getMessage());
        }
    }

    /**
     * Log creation activity.
     */
    protected function logCreated(string $deskripsi, $model = null, array $data = null): void
    {
        $this->logActivity('create', $deskripsi, $model, null, $data);
    }

    /**
     * Log update activity.
     */
    protected function logUpdated(string $deskripsi, $model = null, array $dataLama = null, array $dataBaru = null): void
    {
        $this->logActivity('update', $deskripsi, $model, $dataLama, $dataBaru);
    }

    /**
     * Log deletion activity.
     */
    protected function logDeleted(string $deskripsi, $model = null, array $dataLama = null): void
    {
        $this->logActivity('delete', $deskripsi, $model, $dataLama);
    }

    /**
     * Log custom activity.
     */
    protected function logCustom(string $aksi, string $deskripsi, $model = null, array $data = null): void
    {
        $this->logActivity($aksi, $deskripsi, $model, null, $data);
    }

    /**
     * Log login activity.
     */
    protected function logLogin(int $userId = null): void
    {
        $this->logActivity(
            'login',
            'User berhasil login',
            ['type' => 'App\\Models\\User', 'id' => $userId ?? Auth::id()],
            null,
            ['login_time' => now()->toDateTimeString()],
            $userId
        );
    }

    /**
     * Log logout activity.
     */
    protected function logLogout(int $userId = null): void
    {
        $this->logActivity(
            'logout',
            'User melakukan logout',
            ['type' => 'App\\Models\\User', 'id' => $userId ?? Auth::id()],
            null,
            ['logout_time' => now()->toDateTimeString()],
            $userId
        );
    }

    /**
     * Log bulk operation activity.
     */
    protected function logBulkOperation(string $aksi, string $deskripsi, array $modelIds, string $modelType = null): void
    {
        $this->logActivity(
            $aksi,
            $deskripsi,
            null,
            null,
            [
                'affected_models' => $modelIds,
                'model_type' => $modelType,
                'total_affected' => count($modelIds)
            ]
        );
    }

    /**
     * Log import/export activity.
     */
    protected function logImportExport(string $aksi, string $deskripsi, array $stats = []): void
    {
        $this->logActivity(
            $aksi,
            $deskripsi,
            null,
            null,
            array_merge([
                'timestamp' => now()->toDateTimeString()
            ], $stats)
        );
    }

    /**
     * Log optimization activity.
     */
    protected function logOptimization(string $deskripsi, array $parameters = [], array $results = []): void
    {
        $this->logActivity(
            'optimization',
            $deskripsi,
            null,
            ['parameters' => $parameters],
            ['results' => $results]
        );
    }

    /**
     * Log security-related activity.
     */
    protected function logSecurity(string $aksi, string $deskripsi, array $details = []): void
    {
        $this->logActivity(
            $aksi,
            $deskripsi,
            null,
            null,
            array_merge([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toDateTimeString()
            ], $details)
        );
    }

    /**
     * Sanitize data for logging by removing sensitive information.
     */
    private function sanitizeLogData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'token',
            'access_token',
            'refresh_token',
            '_token',
            '_method',
            'current_password',
            'new_password',
            'api_key',
            'secret',
            'private_key'
        ];

        $sanitized = $data;

        foreach ($sensitiveFields as $field) {
            if (isset($sanitized[$field])) {
                $sanitized[$field] = '[HIDDEN]';
            }
        }

        // Recursively sanitize nested arrays
        foreach ($sanitized as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeLogData($value);
            }
        }

        return $sanitized;
    }

    /**
     * Generate automatic description based on model and action.
     */
    protected function generateAutoDescription(string $aksi, $model, array $additionalInfo = []): string
    {
        $modelName = 'data';
        
        if (is_object($model)) {
            $modelName = $this->getModelDisplayName(get_class($model));
        } elseif (is_array($model) && isset($model['type'])) {
            $modelName = $this->getModelDisplayName($model['type']);
        }

        $descriptions = [
            'create' => "Menambahkan {$modelName} baru",
            'update' => "Mengubah {$modelName}",
            'delete' => "Menghapus {$modelName}",
            'view' => "Melihat detail {$modelName}",
            'list' => "Melihat daftar {$modelName}",
            'restore' => "Mengembalikan {$modelName}",
            'archive' => "Mengarsipkan {$modelName}",
        ];

        $baseDescription = $descriptions[$aksi] ?? "Melakukan aksi {$aksi} pada {$modelName}";

        // Add additional info if provided
        if (!empty($additionalInfo)) {
            $infoString = implode(', ', array_map(function($key, $value) {
                return "{$key}: {$value}";
            }, array_keys($additionalInfo), $additionalInfo));
            
            $baseDescription .= " ({$infoString})";
        }

        return $baseDescription;
    }

    /**
     * Get user-friendly model name for logging.
     */
    private function getModelDisplayName(string $modelClass): string
    {
        $modelNames = [
            'App\\Models\\User' => 'pengguna',
            'App\\Models\\Gudang' => 'gudang',
            'App\\Models\\AreaGudang' => 'area gudang',
            'App\\Models\\KategoriBarang' => 'kategori barang',
            'App\\Models\\Barang' => 'barang',
            'App\\Models\\PenempatanBarang' => 'penempatan barang',
            'App\\Models\\LogOptimasi' => 'log optimasi',
            'App\\Models\\RekomendasiPenempatan' => 'rekomendasi penempatan',
            'App\\Models\\LogAktivitas' => 'log aktivitas',
        ];

        return $modelNames[$modelClass] ?? strtolower(class_basename($modelClass));
    }
}