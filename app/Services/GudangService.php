<?php

namespace App\Services;

use App\Models\Gudang;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class GudangService
{
    /**
     * Get gudang with filters and pagination
     */
    public function getGudang(array $filters = []): LengthAwarePaginator
    {
        $query = Gudang::query()->with(['areaGudang']);

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama_gudang', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        // Status filter
        if (isset($filters['status'])) {
            $query->where('aktif', (bool) $filters['status']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'nama_gudang';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = min($filters['per_page'] ?? 10, 100);
        
        return $query->paginate($perPage);
    }

    /**
     * Create new gudang
     */
    public function createGudang(array $data): Gudang
    {
        DB::beginTransaction();

        try {
            // Set default values
            $data['kapasitas_terpakai'] = 0;
            $data['aktif'] = $data['aktif'] ?? true;

            $gudang = Gudang::create($data);

            DB::commit();

            return $gudang->load(['areaGudang']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get gudang detail with relationships
     */
    public function getGudangDetail(Gudang $gudang): Gudang
    {
        return $gudang->load([
            'areaGudang' => function ($query) {
                $query->orderBy('nama_area');
            },
            'penempatanBarang.barang',
            'logOptimasi' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(5);
            }
        ]);
    }

    /**
     * Update gudang
     */
    public function updateGudang(Gudang $gudang, array $data): Gudang
    {
        DB::beginTransaction();

        try {
            // Validate kapasitas if updating total_kapasitas
            if (isset($data['total_kapasitas'])) {
                if ($data['total_kapasitas'] < $gudang->kapasitas_terpakai) {
                    throw new Exception('Total kapasitas tidak boleh kurang dari kapasitas yang sudah terpakai (' . $gudang->kapasitas_terpakai . ')');
                }
            }

            $gudang->update($data);

            DB::commit();

            return $gudang->fresh(['areaGudang']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete gudang
     */
    public function deleteGudang(Gudang $gudang): void
    {
        DB::beginTransaction();

        try {
            // Check if gudang has related data
            if ($gudang->areaGudang()->count() > 0) {
                throw new Exception('Tidak dapat menghapus gudang yang memiliki area gudang');
            }

            if ($gudang->penempatanBarang()->count() > 0) {
                throw new Exception('Tidak dapat menghapus gudang yang memiliki penempatan barang');
            }

            $gudang->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get gudang statistics
     */
    public function getGudangStats(): array
    {
        $stats = Gudang::selectRaw('
            COUNT(*) as total_gudang,
            SUM(CASE WHEN aktif = 1 THEN 1 ELSE 0 END) as gudang_aktif,
            SUM(CASE WHEN aktif = 0 THEN 1 ELSE 0 END) as gudang_tidak_aktif,
            SUM(total_kapasitas) as total_kapasitas,
            SUM(kapasitas_terpakai) as kapasitas_terpakai
        ')->first();

        $persentase_penggunaan = 0;
        if ($stats->total_kapasitas > 0) {
            $persentase_penggunaan = round(($stats->kapasitas_terpakai / $stats->total_kapasitas) * 100, 2);
        }

        return [
            'total_gudang' => (int) $stats->total_gudang,
            'gudang_aktif' => (int) $stats->gudang_aktif,
            'gudang_tidak_aktif' => (int) $stats->gudang_tidak_aktif,
            'total_kapasitas' => (float) $stats->total_kapasitas,
            'kapasitas_terpakai' => (float) $stats->kapasitas_terpakai,
            'sisa_kapasitas' => (float) ($stats->total_kapasitas - $stats->kapasitas_terpakai),
            'persentase_penggunaan' => $persentase_penggunaan
        ];
    }

    /**
     * Toggle gudang status
     */
    public function toggleGudangStatus(Gudang $gudang): Gudang
    {
        DB::beginTransaction();

        try {
            $gudang->update(['aktif' => !$gudang->aktif]);

            DB::commit();

            return $gudang->fresh(['areaGudang']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if gudang can be deleted
     */
    public function canDelete(Gudang $gudang): bool
    {
        return $gudang->areaGudang()->count() === 0 
            && $gudang->penempatanBarang()->count() === 0;
    }

    /**
     * Get active gudang only
     */
    public function getActiveGudang(): Collection
    {
        return Gudang::aktif()
            ->orderBy('nama_gudang')
            ->get(['id', 'nama_gudang', 'alamat', 'total_kapasitas', 'kapasitas_terpakai']);
    }

    /**
     * Update kapasitas terpakai
     */
    public function updateKapasitasTerpakai(Gudang $gudang): void
    {
        $totalKapasitas = $gudang->penempatanBarang()->sum('volume_penempatan');
        $gudang->update(['kapasitas_terpakai' => $totalKapasitas]);
    }
}