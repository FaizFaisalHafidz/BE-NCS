<?php

namespace App\Services;

use App\Models\AreaGudang;
use App\Models\Gudang;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class AreaGudangService
{
    /**
     * Get area gudang with filters and pagination
     */
    public function getAreaGudang(array $filters = []): LengthAwarePaginator
    {
        $query = AreaGudang::query()->with(['gudang']);

        // Filter by gudang
        if (!empty($filters['gudang_id'])) {
            $query->where('gudang_id', $filters['gudang_id']);
        }

        // Filter by jenis area
        if (!empty($filters['jenis_area'])) {
            $query->where('jenis_area', $filters['jenis_area']);
        }

        // Filter by availability
        if (isset($filters['tersedia'])) {
            $query->where('tersedia', (bool) $filters['tersedia']);
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama_area', 'like', "%{$search}%")
                  ->orWhere('kode_area', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'kode_area';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = min($filters['per_page'] ?? 10, 100);
        
        return $query->paginate($perPage);
    }

    /**
     * Create new area gudang
     */
    public function createAreaGudang(array $data): AreaGudang
    {
        DB::beginTransaction();

        try {
            // Validate gudang exists and is active
            $gudang = Gudang::find($data['gudang_id']);
            if (!$gudang) {
                throw new Exception('Gudang tidak ditemukan');
            }

            if (!$gudang->aktif) {
                throw new Exception('Tidak dapat membuat area di gudang yang tidak aktif');
            }

            // Check if kode_area is unique within the gudang
            $existingArea = AreaGudang::where('gudang_id', $data['gudang_id'])
                ->where('kode_area', $data['kode_area'])
                ->first();
            
            if ($existingArea) {
                throw new Exception('Kode area sudah digunakan dalam gudang ini');
            }

            // Calculate area volume if not provided
            if (!isset($data['kapasitas'])) {
                $data['kapasitas'] = $data['panjang'] * $data['lebar'] * $data['tinggi'];
            }

            // Set default values
            $data['kapasitas_terpakai'] = 0;
            $data['tersedia'] = $data['tersedia'] ?? true;

            // Validate coordinates don't overlap with existing areas
            $this->validateAreaCoordinates($data);

            $areaGudang = AreaGudang::create($data);

            DB::commit();

            return $areaGudang->load(['gudang']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get area gudang detail with relationships
     */
    public function getAreaGudangDetail(AreaGudang $areaGudang): AreaGudang
    {
        return $areaGudang->load([
            'gudang',
            'penempatanBarang.barang',
            'rekomendasiSaatIni',
            'rekomendasiTujuan'
        ]);
    }

    /**
     * Update area gudang
     */
    public function updateAreaGudang(AreaGudang $areaGudang, array $data): AreaGudang
    {
        DB::beginTransaction();

        try {
            // Check if kode_area is unique within the gudang (excluding current area)
            if (isset($data['kode_area'])) {
                $existingArea = AreaGudang::where('gudang_id', $areaGudang->gudang_id)
                    ->where('kode_area', $data['kode_area'])
                    ->where('id', '!=', $areaGudang->id)
                    ->first();
                
                if ($existingArea) {
                    throw new Exception('Kode area sudah digunakan dalam gudang ini');
                }
            }

            // Recalculate kapasitas if dimensions changed
            if (isset($data['panjang']) || isset($data['lebar']) || isset($data['tinggi'])) {
                $panjang = $data['panjang'] ?? $areaGudang->panjang;
                $lebar = $data['lebar'] ?? $areaGudang->lebar;
                $tinggi = $data['tinggi'] ?? $areaGudang->tinggi;
                
                $newKapasitas = $panjang * $lebar * $tinggi;
                
                // Validate new capacity is not less than currently used
                if ($newKapasitas < $areaGudang->kapasitas_terpakai) {
                    throw new Exception('Kapasitas baru tidak boleh kurang dari kapasitas yang sudah terpakai (' . $areaGudang->kapasitas_terpakai . ')');
                }
                
                $data['kapasitas'] = $newKapasitas;
            }

            // Validate coordinates if changed
            if (isset($data['koordinat_x']) || isset($data['koordinat_y']) || 
                isset($data['panjang']) || isset($data['lebar'])) {
                $this->validateAreaCoordinates($data, $areaGudang->id);
            }

            $areaGudang->update($data);

            DB::commit();

            return $areaGudang->fresh(['gudang']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete area gudang
     */
    public function deleteAreaGudang(AreaGudang $areaGudang): void
    {
        DB::beginTransaction();

        try {
            // Check if area has related data
            if ($areaGudang->penempatanBarang()->count() > 0) {
                throw new Exception('Tidak dapat menghapus area yang memiliki penempatan barang');
            }

            if ($areaGudang->rekomendasiSaatIni()->count() > 0 || $areaGudang->rekomendasiTujuan()->count() > 0) {
                throw new Exception('Tidak dapat menghapus area yang memiliki rekomendasi penempatan');
            }

            $areaGudang->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get area gudang by gudang
     */
    public function getAreaByGudang(Gudang $gudang, array $filters = []): Collection
    {
        $query = $gudang->areaGudang();

        // Filter by jenis area
        if (!empty($filters['jenis_area'])) {
            $query->where('jenis_area', $filters['jenis_area']);
        }

        // Filter by availability
        if (isset($filters['tersedia'])) {
            $query->where('tersedia', (bool) $filters['tersedia']);
        }

        return $query->orderBy('kode_area')->get();
    }

    /**
     * Get area gudang statistics
     */
    public function getAreaGudangStats(array $filters = []): array
    {
        $query = AreaGudang::query();

        // Filter by gudang if specified
        if (!empty($filters['gudang_id'])) {
            $query->where('gudang_id', $filters['gudang_id']);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_area,
            SUM(CASE WHEN tersedia = 1 THEN 1 ELSE 0 END) as area_tersedia,
            SUM(CASE WHEN tersedia = 0 THEN 1 ELSE 0 END) as area_tidak_tersedia,
            SUM(kapasitas) as total_kapasitas,
            SUM(kapasitas_terpakai) as kapasitas_terpakai
        ')->first();

        // Get statistics by jenis area
        $byJenis = $query->selectRaw('jenis_area, COUNT(*) as count')
            ->groupBy('jenis_area')
            ->get()
            ->map(function ($item) {
                return [
                    'jenis' => $item->jenis_area,
                    'count' => (int) $item->count
                ];
            });

        $persentase_penggunaan = 0;
        if ($stats->total_kapasitas > 0) {
            $persentase_penggunaan = round(($stats->kapasitas_terpakai / $stats->total_kapasitas) * 100, 2);
        }

        return [
            'total_area' => (int) $stats->total_area,
            'area_tersedia' => (int) $stats->area_tersedia,
            'area_tidak_tersedia' => (int) $stats->area_tidak_tersedia,
            'total_kapasitas' => (float) $stats->total_kapasitas,
            'kapasitas_terpakai' => (float) $stats->kapasitas_terpakai,
            'sisa_kapasitas' => (float) ($stats->total_kapasitas - $stats->kapasitas_terpakai),
            'persentase_penggunaan' => $persentase_penggunaan,
            'by_jenis' => $byJenis
        ];
    }

    /**
     * Toggle area gudang status
     */
    public function toggleAreaGudangStatus(AreaGudang $areaGudang): AreaGudang
    {
        DB::beginTransaction();

        try {
            $areaGudang->update(['tersedia' => !$areaGudang->tersedia]);

            DB::commit();

            return $areaGudang->fresh(['gudang']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if area can be deleted
     */
    public function canDelete(AreaGudang $areaGudang): bool
    {
        return $areaGudang->penempatanBarang()->count() === 0 
            && $areaGudang->rekomendasiSaatIni()->count() === 0
            && $areaGudang->rekomendasiTujuan()->count() === 0;
    }

    /**
     * Get available areas for placement
     */
    public function getAvailableAreas(int $gudangId): Collection
    {
        return AreaGudang::where('gudang_id', $gudangId)
            ->tersedia()
            ->orderBy('kode_area')
            ->get(['id', 'kode_area', 'nama_area', 'jenis_area', 'kapasitas', 'kapasitas_terpakai']);
    }

    /**
     * Update kapasitas terpakai
     */
    public function updateKapasitasTerpakai(AreaGudang $areaGudang): void
    {
        $totalKapasitas = $areaGudang->penempatanBarang()->sum('volume_penempatan');
        $areaGudang->update(['kapasitas_terpakai' => $totalKapasitas]);
    }

    /**
     * Validate area coordinates don't overlap
     */
    private function validateAreaCoordinates(array $data, int $excludeId = null): void
    {
        $koordinatX = $data['koordinat_x'];
        $koordinatY = $data['koordinat_y'];
        $panjang = $data['panjang'];
        $lebar = $data['lebar'];
        $gudangId = $data['gudang_id'] ?? null;
        
        // If gudang_id not in data (update case), get from existing record
        if (!$gudangId && $excludeId) {
            $existingArea = AreaGudang::find($excludeId);
            $gudangId = $existingArea->gudang_id;
        }

        // Calculate boundaries
        $x1 = $koordinatX;
        $y1 = $koordinatY;
        $x2 = $koordinatX + $panjang;
        $y2 = $koordinatY + $lebar;

        // Check for overlapping areas
        $query = AreaGudang::where('gudang_id', $gudangId)
            ->whereRaw('NOT (
                koordinat_x + panjang <= ? OR 
                koordinat_x >= ? OR 
                koordinat_y + lebar <= ? OR 
                koordinat_y >= ?
            )', [$x1, $x2, $y1, $y2]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new Exception('Koordinat area tumpang tindih dengan area yang sudah ada');
        }
    }
}