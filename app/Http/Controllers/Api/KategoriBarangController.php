<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKategoriBarangRequest;
use App\Http\Requests\UpdateKategoriBarangRequest;
use App\Http\Resources\KategoriBarangResource;
use App\Models\KategoriBarang;
use App\Services\KategoriBarangService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Kategori Barang",
 *     description="API endpoints untuk manajemen kategori barang"
 * )
 */
class KategoriBarangController extends Controller
{
    protected $kategoriBarangService;

    public function __construct(KategoriBarangService $kategoriBarangService)
    {
        $this->kategoriBarangService = $kategoriBarangService;
    }

    /**
     * @OA\Get(
     *     path="/api/kategori-barang",
     *     summary="Daftar kategori barang",
     *     description="Mengambil daftar kategori barang dengan pagination dan filter",
     *     operationId="getKategoriBarang",
     *     tags={"Kategori Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Nomor halaman",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Jumlah data per halaman",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Pencarian berdasarkan nama atau kode kategori",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="aktif",
     *         in="query",
     *         description="Filter berdasarkan status aktif (1/0)",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daftar kategori barang berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar kategori barang berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/KategoriBarang")
     *                 ),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="to", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $kategoriBarang = $this->kategoriBarangService->getAllKategoriBarang($request->all());

        return $this->successResponse(
            'Daftar kategori barang berhasil diambil',
            KategoriBarangResource::collection($kategoriBarang)->response()->getData()
        );
    }

    /**
     * @OA\Post(
     *     path="/api/kategori-barang",
     *     summary="Buat kategori barang baru",
     *     description="Membuat kategori barang baru dengan validasi data",
     *     operationId="storeKategoriBarang",
     *     tags={"Kategori Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nama_kategori", "kode_kategori"},
     *             @OA\Property(property="nama_kategori", type="string", example="Elektronik"),
     *             @OA\Property(property="kode_kategori", type="string", example="ELK"),
     *             @OA\Property(property="deskripsi", type="string", example="Kategori untuk barang elektronik"),
     *             @OA\Property(property="aktif", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Kategori barang berhasil dibuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Kategori barang berhasil dibuat"),
     *             @OA\Property(property="data", ref="#/components/schemas/KategoriBarang")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation errors"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(StoreKategoriBarangRequest $request): JsonResponse
    {
        $kategoriBarang = $this->kategoriBarangService->createKategoriBarang($request->validated());

        return $this->successResponse(
            'Kategori barang berhasil dibuat',
            new KategoriBarangResource($kategoriBarang),
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/kategori-barang/{id}",
     *     summary="Detail kategori barang",
     *     description="Mengambil detail kategori barang berdasarkan ID",
     *     operationId="showKategoriBarang",
     *     tags={"Kategori Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID kategori barang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detail kategori barang berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Detail kategori barang berhasil diambil"),
     *             @OA\Property(property="data", ref="#/components/schemas/KategoriBarang")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Kategori barang tidak ditemukan"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(KategoriBarang $kategoriBarang): JsonResponse
    {
        $kategoriBarang = $this->kategoriBarangService->getKategoriBarangById($kategoriBarang->id);

        return $this->successResponse(
            'Detail kategori barang berhasil diambil',
            new KategoriBarangResource($kategoriBarang)
        );
    }

    /**
     * @OA\Put(
     *     path="/api/kategori-barang/{id}",
     *     summary="Update kategori barang",
     *     description="Mengupdate data kategori barang",
     *     operationId="updateKategoriBarang",
     *     tags={"Kategori Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID kategori barang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nama_kategori", type="string", example="Elektronik Updated"),
     *             @OA\Property(property="kode_kategori", type="string", example="ELK-UPD"),
     *             @OA\Property(property="deskripsi", type="string", example="Kategori untuk barang elektronik yang sudah diupdate"),
     *             @OA\Property(property="aktif", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kategori barang berhasil diupdate",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Kategori barang berhasil diupdate"),
     *             @OA\Property(property="data", ref="#/components/schemas/KategoriBarang")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation errors"),
     *     @OA\Response(response=404, description="Kategori barang tidak ditemukan"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(UpdateKategoriBarangRequest $request, KategoriBarang $kategoriBarang): JsonResponse
    {
        $kategoriBarang = $this->kategoriBarangService->updateKategoriBarang($kategoriBarang->id, $request->validated());

        return $this->successResponse(
            'Kategori barang berhasil diupdate',
            new KategoriBarangResource($kategoriBarang)
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/kategori-barang/{id}",
     *     summary="Hapus kategori barang",
     *     description="Menghapus kategori barang (soft delete jika masih digunakan)",
     *     operationId="deleteKategoriBarang",
     *     tags={"Kategori Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID kategori barang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kategori barang berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Kategori barang berhasil dihapus")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Kategori barang tidak ditemukan"),
     *     @OA\Response(response=409, description="Kategori barang masih digunakan"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(KategoriBarang $kategoriBarang): JsonResponse
    {
        $this->kategoriBarangService->deleteKategoriBarang($kategoriBarang->id);

        return $this->successResponse('Kategori barang berhasil dihapus');
    }

    /**
     * @OA\Patch(
     *     path="/api/kategori-barang/{id}/toggle-status",
     *     summary="Toggle status aktif kategori barang",
     *     description="Mengubah status aktif/nonaktif kategori barang",
     *     operationId="toggleKategoriBarangStatus",
     *     tags={"Kategori Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID kategori barang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status kategori barang berhasil diubah",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Status kategori barang berhasil diubah"),
     *             @OA\Property(property="data", ref="#/components/schemas/KategoriBarang")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Kategori barang tidak ditemukan"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function toggleStatus(KategoriBarang $kategoriBarang): JsonResponse
    {
        $kategoriBarang = $this->kategoriBarangService->toggleStatus($kategoriBarang->id);

        return $this->successResponse(
            'Status kategori barang berhasil diubah',
            new KategoriBarangResource($kategoriBarang)
        );
    }

    /**
     * @OA\Get(
     *     path="/api/kategori-barang/stats",
     *     summary="Statistik kategori barang",
     *     description="Mengambil statistik kategori barang",
     *     operationId="getKategoriBarangStats",
     *     tags={"Kategori Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistik kategori barang berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statistik kategori barang berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_kategori", type="integer", example=15),
     *                 @OA\Property(property="kategori_aktif", type="integer", example=12),
     *                 @OA\Property(property="kategori_nonaktif", type="integer", example=3),
     *                 @OA\Property(property="total_barang_per_kategori", type="integer", example=150),
     *                 @OA\Property(
     *                     property="kategori_terpopuler",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="nama_kategori", type="string"),
     *                         @OA\Property(property="jumlah_barang", type="integer")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function stats(): JsonResponse
    {
        $stats = $this->kategoriBarangService->getStats();

        return $this->successResponse(
            'Statistik kategori barang berhasil diambil',
            $stats
        );
    }

    /**
     * @OA\Get(
     *     path="/api/kategori-barang/aktif",
     *     summary="Daftar kategori barang aktif",
     *     description="Mengambil daftar kategori barang yang aktif untuk dropdown",
     *     operationId="getActiveKategoriBarang",
     *     tags={"Kategori Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Daftar kategori barang aktif berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar kategori barang aktif berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="nama_kategori", type="string"),
     *                     @OA\Property(property="kode_kategori", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getActive(): JsonResponse
    {
        $kategoriBarang = $this->kategoriBarangService->getActiveKategoriBarang();

        return $this->successResponse(
            'Daftar kategori barang aktif berhasil diambil',
            $kategoriBarang
        );
    }
}