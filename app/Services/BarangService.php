<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\KategoriBarang;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BarangService
{
    /**
     * Get all barang with filters and pagination
     */
    public function getAllBarang(array $filters = []): LengthAwarePaginator
    {
        $query = Barang::with(['kategoriBarang']);

        // Apply search filter
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        // Apply kategori filter
        if (isset($filters['kategori_barang_id'])) {
            $query->where('kategori_barang_id', $filters['kategori_barang_id']);
        }

        // Apply status filter
        if (isset($filters['aktif'])) {
            $query->where('aktif', filter_var($filters['aktif'], FILTER_VALIDATE_BOOLEAN));
        }

        // Apply prioritas filter
        if (isset($filters['prioritas']) && !empty($filters['prioritas'])) {
            $query->byPrioritas($filters['prioritas']);
        }

        // Apply mudah pecah filter
        if (isset($filters['mudah_pecah'])) {
            $mudahPecah = filter_var($filters['mudah_pecah'], FILTER_VALIDATE_BOOLEAN);
            if ($mudahPecah) {
                $query->mudahPecah();
            } else {
                $query->where('mudah_pecah', false);
            }
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'nama_barang';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        // Apply pagination
        $perPage = $filters['per_page'] ?? 10;
        
        return $query->paginate($perPage);
    }

    /**
     * Create new barang
     */
    public function createBarang(array $data): Barang
    {
        try {
            DB::beginTransaction();

            // Generate kode barang if not provided
            if (!isset($data['kode_barang']) || empty($data['kode_barang'])) {
                $data['kode_barang'] = $this->generateKodeBarang($data['kategori_barang_id']);
            }

            // Set default values
            if (!isset($data['aktif'])) {
                $data['aktif'] = true;
            }

            if (!isset($data['prioritas'])) {
                $data['prioritas'] = 'sedang';
            }

            if (!isset($data['mudah_pecah'])) {
                $data['mudah_pecah'] = false;
            }

            // Generate barcode for QR
            $data['barcode'] = $this->generateBarcode($data['kode_barang']);

            $barang = Barang::create($data);

            DB::commit();

            return $barang->fresh(['kategoriBarang']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get barang by ID
     */
    public function getBarangById(int $id): Barang
    {
        return Barang::with(['kategoriBarang', 'penempatanBarang.areaGudang'])
            ->findOrFail($id);
    }

    /**
     * Update barang
     */
    public function updateBarang(int $id, array $data): Barang
    {
        try {
            DB::beginTransaction();

            $barang = Barang::findOrFail($id);
            
            // If kode_barang changed, regenerate barcode
            if (isset($data['kode_barang']) && $data['kode_barang'] !== $barang->kode_barang) {
                $data['barcode'] = $this->generateBarcode($data['kode_barang']);
            }

            $barang->update($data);

            DB::commit();

            return $barang->fresh(['kategoriBarang']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete barang
     */
    public function deleteBarang(int $id): void
    {
        try {
            DB::beginTransaction();

            $barang = Barang::findOrFail($id);

            // Check if barang has active penempatan
            if ($barang->penempatanBarang()->where('status', 'ditempatkan')->count() > 0) {
                throw new Exception('Barang tidak dapat dihapus karena masih memiliki penempatan aktif.');
            }

            $barang->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Toggle aktif status
     */
    public function toggleStatus(int $id): Barang
    {
        try {
            DB::beginTransaction();

            $barang = Barang::findOrFail($id);
            $barang->update(['aktif' => !$barang->aktif]);

            DB::commit();

            return $barang->fresh(['kategoriBarang']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get barang statistics
     */
    public function getStats(): array
    {
        $totalBarang = Barang::count();
        $barangAktif = Barang::aktif()->count();
        $barangNonaktif = $totalBarang - $barangAktif;
        $barangMudahPecah = Barang::mudahPecah()->count();
        
        // Calculate total volume
        $totalVolume = Barang::all()->sum(function ($barang) {
            return $barang->volume;
        });

        // Group by kategori
        $byKategori = Barang::with('kategoriBarang')
            ->select('kategori_barang_id', DB::raw('count(*) as jumlah'))
            ->groupBy('kategori_barang_id')
            ->get()
            ->map(function ($item) {
                return [
                    'kategori_id' => $item->kategori_barang_id,
                    'nama_kategori' => $item->kategoriBarang->nama_kategori ?? 'N/A',
                    'jumlah' => $item->jumlah
                ];
            });

        // Group by prioritas
        $byPrioritas = Barang::select('prioritas', DB::raw('count(*) as jumlah'))
            ->groupBy('prioritas')
            ->get()
            ->map(function ($item) {
                return [
                    'prioritas' => $item->prioritas,
                    'jumlah' => $item->jumlah
                ];
            });

        return [
            'total_barang' => $totalBarang,
            'barang_aktif' => $barangAktif,
            'barang_nonaktif' => $barangNonaktif,
            'barang_mudah_pecah' => $barangMudahPecah,
            'total_volume' => round($totalVolume, 3),
            'by_kategori' => $byKategori,
            'by_prioritas' => $byPrioritas
        ];
    }

    /**
     * Scan barang by barcode
     */
    public function scanBarang(string $barcode): Barang
    {
        $barang = Barang::with(['kategoriBarang', 'penempatanBarang.areaGudang.gudang'])
            ->where('barcode', $barcode)
            ->first();

        if (!$barang) {
            throw new Exception('Barang dengan barcode tersebut tidak ditemukan.');
        }

        return $barang;
    }

    /**
     * Generate QR Code for barang
     */
    public function generateQrCode(int $id): array
    {
        $barang = Barang::findOrFail($id);
        
        try {
            // Generate QR code as base64
            $qrCode = QrCode::format('png')
                ->size(200)
                ->generate($barang->barcode);
            
            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCode);
        } catch (Exception $e) {
            // Fallback jika QR code generation gagal
            $qrCodeBase64 = null;
        }

        return [
            'qr_code' => $qrCodeBase64,
            'barcode' => $barang->barcode,
            'kode_barang' => $barang->kode_barang,
            'nama_barang' => $barang->nama_barang
        ];
    }

    /**
     * Generate unique kode barang
     */
    private function generateKodeBarang(int $kategoriBarangId): string
    {
        $kategori = KategoriBarang::findOrFail($kategoriBarangId);
        $prefix = $kategori->kode_kategori;
        
        // Get latest number for this kategori
        $lastBarang = Barang::where('kategori_barang_id', $kategoriBarangId)
            ->where('kode_barang', 'like', $prefix . '%')
            ->orderBy('kode_barang', 'desc')
            ->first();

        if ($lastBarang) {
            // Extract number from last kode
            $lastNumber = (int) substr($lastBarang->kode_barang, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique barcode for QR
     */
    private function generateBarcode(string $kodeBarang): string
    {
        return $kodeBarang . '-' . strtoupper(Str::random(8));
    }

    /**
     * Check if kode barang is unique
     */
    public function isKodeBarangUnique(string $kodeBarang, int $excludeId = null): bool
    {
        $query = Barang::where('kode_barang', $kodeBarang);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }

    /**
     * Search barang by kode barang exactly
     */
    public function searchByCode(string $kodeBarang): ?Barang
    {
        return Barang::with(['kategoriBarang'])
            ->where('kode_barang', $kodeBarang)
            ->where('aktif', true)
            ->first();
    }

    /**
     * Search barang
     */
    public function searchBarang(string $search, array $filters = []): array
    {
        $query = Barang::with('kategoriBarang');

        // Apply search
        $query->where(function (Builder $q) use ($search) {
            $q->where('nama_barang', 'like', "%{$search}%")
              ->orWhere('kode_barang', 'like', "%{$search}%");
        });

        // Apply filters
        if (isset($filters['aktif'])) {
            $query->where('aktif', $filters['aktif']);
        }

        if (isset($filters['kategori_barang_id'])) {
            $query->where('kategori_barang_id', $filters['kategori_barang_id']);
        }

        return $query->orderBy('nama_barang')
            ->limit(10)
            ->get(['id', 'kode_barang', 'nama_barang', 'kategori_barang_id'])
            ->toArray();
    }
}