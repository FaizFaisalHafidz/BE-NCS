<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBarangRequest;
use App\Http\Requests\UpdateBarangRequest;
use App\Http\Resources\BarangResource;
use App\Models\Barang;
use App\Services\BarangService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Barang",
 *     description="API endpoints untuk manajemen barang"
 * )
 */
class BarangController extends Controller
{
    protected $barangService;

    public function __construct(BarangService $barangService)
    {
        $this->barangService = $barangService;
    }

    /**
     * @OA\Get(
     *     path="/api/barang",
     *     summary="Daftar barang",
     *     description="Mengambil daftar barang dengan pagination dan filter",
     *     operationId="getBarang",
     *     tags={"Barang"},
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
     *         description="Pencarian berdasarkan nama atau kode barang",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="kategori_barang_id",
     *         in="query",
     *         description="Filter berdasarkan kategori barang",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="aktif",
     *         in="query",
     *         description="Filter berdasarkan status aktif (1/0)",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="prioritas",
     *         in="query",
     *         description="Filter berdasarkan prioritas (rendah/sedang/tinggi)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"rendah","sedang","tinggi"})
     *     ),
     *     @OA\Parameter(
     *         name="mudah_pecah",
     *         in="query",
     *         description="Filter barang mudah pecah (1/0)",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daftar barang berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar barang berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Barang")
     *                 ),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="last_page", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $barang = $this->barangService->getAllBarang($request->all());

        return $this->successResponse(
            'Daftar barang berhasil diambil',
            BarangResource::collection($barang)->response()->getData()
        );
    }

    /**
     * @OA\Post(
     *     path="/api/barang",
     *     summary="Buat barang baru",
     *     description="Membuat barang baru dengan validasi data dan generate QR code",
     *     operationId="storeBarang",
     *     tags={"Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nama_barang", "kategori_barang_id", "panjang", "lebar", "tinggi", "berat"},
     *             @OA\Property(property="kode_barang", type="string", example="BRG001"),
     *             @OA\Property(property="nama_barang", type="string", example="Laptop Acer Aspire 5"),
     *             @OA\Property(property="kategori_barang_id", type="integer", example=1),
     *             @OA\Property(property="panjang", type="number", format="float", example=35.6),
     *             @OA\Property(property="lebar", type="number", format="float", example=25.4),
     *             @OA\Property(property="tinggi", type="number", format="float", example=2.3),
     *             @OA\Property(property="berat", type="number", format="float", example=1.8),
     *             @OA\Property(property="mudah_pecah", type="boolean", example=false),
     *             @OA\Property(property="prioritas", type="string", enum={"rendah","sedang","tinggi"}, example="sedang"),
     *             @OA\Property(property="deskripsi", type="string", example="Laptop untuk kebutuhan kantor"),
     *             @OA\Property(property="aktif", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Barang berhasil dibuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Barang berhasil dibuat"),
     *             @OA\Property(property="data", ref="#/components/schemas/Barang")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation errors"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(StoreBarangRequest $request): JsonResponse
    {
        $barang = $this->barangService->createBarang($request->validated());

        return $this->successResponse(
            'Barang berhasil dibuat',
            new BarangResource($barang),
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/barang/{id}",
     *     summary="Detail barang",
     *     description="Mengambil detail barang berdasarkan ID",
     *     operationId="showBarang",
     *     tags={"Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID barang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detail barang berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Detail barang berhasil diambil"),
     *             @OA\Property(property="data", ref="#/components/schemas/Barang")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Barang tidak ditemukan"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Barang $barang): JsonResponse
    {
        $barang = $this->barangService->getBarangById($barang->id);

        return $this->successResponse(
            'Detail barang berhasil diambil',
            new BarangResource($barang)
        );
    }

    /**
     * @OA\Put(
     *     path="/api/barang/{id}",
     *     summary="Update barang",
     *     description="Mengupdate data barang",
     *     operationId="updateBarang",
     *     tags={"Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID barang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nama_barang", type="string", example="Laptop Acer Aspire 5 Updated"),
     *             @OA\Property(property="kategori_barang_id", type="integer", example=1),
     *             @OA\Property(property="panjang", type="number", format="float", example=36.0),
     *             @OA\Property(property="lebar", type="number", format="float", example=26.0),
     *             @OA\Property(property="tinggi", type="number", format="float", example=2.5),
     *             @OA\Property(property="berat", type="number", format="float", example=2.0),
     *             @OA\Property(property="mudah_pecah", type="boolean", example=false),
     *             @OA\Property(property="prioritas", type="string", enum={"rendah","sedang","tinggi"}, example="tinggi"),
     *             @OA\Property(property="deskripsi", type="string", example="Laptop untuk kebutuhan kantor yang sudah diupdate"),
     *             @OA\Property(property="aktif", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Barang berhasil diupdate",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Barang berhasil diupdate"),
     *             @OA\Property(property="data", ref="#/components/schemas/Barang")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation errors"),
     *     @OA\Response(response=404, description="Barang tidak ditemukan"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(UpdateBarangRequest $request, Barang $barang): JsonResponse
    {
        $barang = $this->barangService->updateBarang($barang->id, $request->validated());

        return $this->successResponse(
            'Barang berhasil diupdate',
            new BarangResource($barang)
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/barang/{id}",
     *     summary="Hapus barang",
     *     description="Menghapus barang (soft delete jika masih ada penempatan)",
     *     operationId="deleteBarang",
     *     tags={"Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID barang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Barang berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Barang berhasil dihapus")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Barang tidak ditemukan"),
     *     @OA\Response(response=409, description="Barang masih memiliki penempatan aktif"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(Barang $barang): JsonResponse
    {
        $this->barangService->deleteBarang($barang->id);

        return $this->successResponse('Barang berhasil dihapus');
    }

    /**
     * @OA\Patch(
     *     path="/api/barang/{id}/toggle-status",
     *     summary="Toggle status aktif barang",
     *     description="Mengubah status aktif/nonaktif barang",
     *     operationId="toggleBarangStatus",
     *     tags={"Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID barang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status barang berhasil diubah",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Status barang berhasil diubah"),
     *             @OA\Property(property="data", ref="#/components/schemas/Barang")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Barang tidak ditemukan"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function toggleStatus(Barang $barang): JsonResponse
    {
        $barang = $this->barangService->toggleStatus($barang->id);

        return $this->successResponse(
            'Status barang berhasil diubah',
            new BarangResource($barang)
        );
    }

    /**
     * @OA\Get(
     *     path="/api/barang/stats",
     *     summary="Statistik barang",
     *     description="Mengambil statistik barang",
     *     operationId="getBarangStats",
     *     tags={"Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistik barang berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statistik barang berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_barang", type="integer", example=150),
     *                 @OA\Property(property="barang_aktif", type="integer", example=120),
     *                 @OA\Property(property="barang_nonaktif", type="integer", example=30),
     *                 @OA\Property(property="barang_mudah_pecah", type="integer", example=25),
     *                 @OA\Property(property="total_volume", type="number", format="float", example=125.75),
     *                 @OA\Property(
     *                     property="by_kategori",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="kategori_id", type="integer"),
     *                         @OA\Property(property="nama_kategori", type="string"),
     *                         @OA\Property(property="jumlah", type="integer")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="by_prioritas",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="prioritas", type="string"),
     *                         @OA\Property(property="jumlah", type="integer")
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
        $stats = $this->barangService->getStats();

        return $this->successResponse(
            'Statistik barang berhasil diambil',
            $stats
        );
    }

    /**
     * @OA\Post(
     *     path="/api/barang/scan",
     *     summary="Scan QR Code barang",
     *     description="Melakukan scan QR code untuk mendapatkan informasi barang",
     *     operationId="scanBarang",
     *     tags={"Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"barcode"},
     *             @OA\Property(property="barcode", type="string", example="BRG001-QR123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Barang ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Barang ditemukan"),
     *             @OA\Property(property="data", ref="#/components/schemas/Barang")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Barang tidak ditemukan"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'barcode' => 'required|string'
        ]);

        $barang = $this->barangService->scanBarang($request->barcode);

        return $this->successResponse(
            'Barang ditemukan',
            new BarangResource($barang)
        );
    }

    /**
     * @OA\Get(
     *     path="/api/barang/search-by-code",
     *     summary="Search barang by kode_barang (manual fallback)",
     *     description="Mencari barang berdasarkan kode barang manual sebagai fallback ketika QR scan bermasalah",
     *     operationId="searchBarangByCode",
     *     tags={"Barang Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="kode_barang",
     *         in="query",
     *         description="Kode barang yang dicari",
     *         required=true,
     *         @OA\Schema(type="string", example="ELK-001")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Barang ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Barang berhasil ditemukan"),
     *             @OA\Property(property="data", ref="#/components/schemas/Barang")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Parameter kode_barang diperlukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Parameter kode_barang diperlukan")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Barang tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Barang dengan kode ELK-001 tidak ditemukan")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function searchByCode(Request $request): JsonResponse
    {
        $kodeBarang = $request->query('kode_barang');
        
        if (!$kodeBarang) {
            return $this->errorResponse(
                'Parameter kode_barang diperlukan',
                [],
                400
            );
        }

        $barang = $this->barangService->searchByCode($kodeBarang);

        if (!$barang) {
            return $this->errorResponse(
                "Barang dengan kode {$kodeBarang} tidak ditemukan",
                [],
                404
            );
        }

        return $this->successResponse(
            'Barang berhasil ditemukan',
            new BarangResource($barang)
        );
    }

    /**
     * @OA\Get(
     *     path="/api/barang/{id}/qr-code",
     *     summary="Generate QR Code barang",
     *     description="Generate QR code untuk barang tertentu",
     *     operationId="generateQrCode",
     *     tags={"Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID barang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="QR Code berhasil digenerate",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="QR Code berhasil digenerate"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="qr_code", type="string", example="data:image/png;base64,iVBORw0KGgoAAAANS..."),
     *                 @OA\Property(property="barcode", type="string", example="BRG001-QR123456")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Barang tidak ditemukan"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function generateQrCode(Barang $barang): JsonResponse
    {
        $qrData = $this->barangService->generateQrCode($barang->id);

        return $this->successResponse(
            'QR Code berhasil digenerate',
            $qrData
        );
    }
}