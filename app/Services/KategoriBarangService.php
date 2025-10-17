<?php

namespace App\Services;

use App\Models\KategoriBarang;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class KategoriBarangService
{
    /**
     * Get all kategori barang with filters and pagination
     */
    public function getAllKategoriBarang(array $filters = []): LengthAwarePaginator
    {
        $query = KategoriBarang::query();

        // Apply search filter
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('nama_kategori', 'like', "%{$search}%")
                  ->orWhere('kode_kategori', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if (isset($filters['aktif'])) {
            $query->where('aktif', filter_var($filters['aktif'], FILTER_VALIDATE_BOOLEAN));
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'nama_kategori';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        // Apply pagination
        $perPage = $filters['per_page'] ?? 10;
        
        return $query->withCount('barang')->paginate($perPage);
    }

    /**
     * Create new kategori barang
     */
    public function createKategoriBarang(array $data): KategoriBarang
    {
        try {
            DB::beginTransaction();

            // Set default aktif if not provided
            if (!isset($data['aktif'])) {
                $data['aktif'] = true;
            }

            $kategoriBarang = KategoriBarang::create($data);

            DB::commit();

            return $kategoriBarang->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get kategori barang by ID
     */
    public function getKategoriBarangById(int $id): KategoriBarang
    {
        return KategoriBarang::withCount('barang')
            ->findOrFail($id);
    }

    /**
     * Update kategori barang
     */
    public function updateKategoriBarang(int $id, array $data): KategoriBarang
    {
        try {
            DB::beginTransaction();

            $kategoriBarang = KategoriBarang::findOrFail($id);
            $kategoriBarang->update($data);

            DB::commit();

            return $kategoriBarang->fresh(['barang']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete kategori barang
     */
    public function deleteKategoriBarang(int $id): void
    {
        try {
            DB::beginTransaction();

            $kategoriBarang = KategoriBarang::findOrFail($id);

            // Check if kategori has barang
            if ($kategoriBarang->barang()->count() > 0) {
                throw new Exception('Kategori barang tidak dapat dihapus karena masih digunakan oleh barang lain.');
            }

            $kategoriBarang->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Toggle aktif status
     */
    public function toggleStatus(int $id): KategoriBarang
    {
        try {
            DB::beginTransaction();

            $kategoriBarang = KategoriBarang::findOrFail($id);
            $kategoriBarang->update(['aktif' => !$kategoriBarang->aktif]);

            DB::commit();

            return $kategoriBarang->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get kategori barang statistics
     */
    public function getStats(): array
    {
        $totalKategori = KategoriBarang::count();
        $kategoriAktif = KategoriBarang::aktif()->count();
        $kategoriNonaktif = $totalKategori - $kategoriAktif;

        // Get total barang across all categories
        $totalBarangPerKategori = KategoriBarang::withCount('barang')
            ->get()
            ->sum('barang_count');

        // Get most popular categories (with most barang)
        $kategoriTerpopuler = KategoriBarang::withCount('barang')
            ->orderBy('barang_count', 'desc')
            ->limit(5)
            ->get(['id', 'nama_kategori', 'kode_kategori'])
            ->map(function ($kategori) {
                return [
                    'id' => $kategori->id,
                    'nama_kategori' => $kategori->nama_kategori,
                    'kode_kategori' => $kategori->kode_kategori,
                    'jumlah_barang' => $kategori->barang_count
                ];
            });

        return [
            'total_kategori' => $totalKategori,
            'kategori_aktif' => $kategoriAktif,
            'kategori_nonaktif' => $kategoriNonaktif,
            'total_barang_per_kategori' => $totalBarangPerKategori,
            'kategori_terpopuler' => $kategoriTerpopuler
        ];
    }

    /**
     * Get active kategori barang for dropdown
     */
    public function getActiveKategoriBarang(): array
    {
        return KategoriBarang::aktif()
            ->orderBy('nama_kategori')
            ->get(['id', 'nama_kategori', 'kode_kategori'])
            ->toArray();
    }

    /**
     * Check if kode kategori is unique
     */
    public function isKodeKategoriUnique(string $kodeKategori, int $excludeId = null): bool
    {
        $query = KategoriBarang::where('kode_kategori', $kodeKategori);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }

    /**
     * Search kategori barang by name or code
     */
    public function searchKategoriBarang(string $search, bool $onlyActive = true): array
    {
        $query = KategoriBarang::query();

        if ($onlyActive) {
            $query->aktif();
        }

        $results = $query->where(function (Builder $q) use ($search) {
                $q->where('nama_kategori', 'like', "%{$search}%")
                  ->orWhere('kode_kategori', 'like', "%{$search}%");
            })
            ->orderBy('nama_kategori')
            ->limit(10)
            ->get(['id', 'nama_kategori', 'kode_kategori']);

        return $results->toArray();
    }
}