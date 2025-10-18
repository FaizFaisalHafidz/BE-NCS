<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RekomendasiPenempatan;
use App\Models\LogOptimasi;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Rekomendasi Penempatan",
 *     description="API endpoints for managing warehouse placement recommendations"
 * )
 */
class RekomendasiPenempatanController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/rekomendasi-penempatan",
     *     operationId="getRecommendations",
     *     tags={"Rekomendasi Penempatan"},
     *     summary="Get placement recommendations",
     *     description="Returns paginated list of placement recommendations with filtering options",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by recommendation status",
     *         @OA\Schema(type="string", enum={"menunggu", "disetujui", "ditolak"})
     *     ),
     *     @OA\Parameter(
     *         name="log_optimasi_id",
     *         in="query",
     *         required=false,
     *         description="Filter by optimization log ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="prioritas",
     *         in="query",
     *         required=false,
     *         description="Filter by priority level",
     *         @OA\Schema(type="string", enum={"tinggi", "sedang", "rendah"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recommendations retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Rekomendasi penempatan berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="total", type="integer", example=8),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="log_optimasi_id", type="integer", example=2),
     *                         @OA\Property(property="barang_id", type="integer", example=1),
     *                         @OA\Property(property="area_gudang_rekomendasi", type="integer", example=3),
     *                         @OA\Property(property="koordinat_x_spesifik", type="number", example=42.69),
     *                         @OA\Property(property="koordinat_y_spesifik", type="number", example=10.01),
     *                         @OA\Property(property="alasan", type="string", example="Optimasi SA: Laptop Gaming optimal"),
     *                         @OA\Property(property="confidence_score", type="number", example=0.85),
     *                         @OA\Property(property="algoritma", type="string", example="Simulated Annealing"),
     *                         @OA\Property(property="prioritas", type="string", example="tinggi"),
     *                         @OA\Property(property="status", type="string", example="menunggu"),
     *                         @OA\Property(property="tanggal_persetujuan", type="string", nullable=true, example=null)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = RekomendasiPenempatan::with(['barang', 'areaGudangRekomendasi', 'logOptimasi']);
            
            // Filter berdasarkan status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            // Filter berdasarkan log optimasi
            if ($request->has('log_optimasi_id')) {
                $query->where('log_optimasi_id', $request->log_optimasi_id);
            }
            
            // Filter berdasarkan algoritma
            if ($request->has('algoritma')) {
                $query->whereHas('logOptimasi', function($q) use ($request) {
                    $q->where('algoritma', $request->algoritma);
                });
            }
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $rekomendasi = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Rekomendasi penempatan berhasil diambil',
                'data' => $rekomendasi
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil rekomendasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/rekomendasi-penempatan",
     *     operationId="storeRecommendations",
     *     tags={"Rekomendasi Penempatan"},
     *     summary="Store placement recommendations",
     *     description="Creates multiple placement recommendations from optimization results",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"log_optimasi_id", "rekomendasi"},
     *             @OA\Property(property="log_optimasi_id", type="integer", example=2, description="ID log optimasi"),
     *             @OA\Property(
     *                 property="rekomendasi",
     *                 type="array",
     *                 description="Array of recommendations",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"barang_id", "area_gudang_rekomendasi", "alasan"},
     *                     @OA\Property(property="barang_id", type="integer", example=1),
     *                     @OA\Property(property="area_gudang_rekomendasi", type="integer", example=3),
     *                     @OA\Property(property="koordinat_x_spesifik", type="number", example=28.50),
     *                     @OA\Property(property="koordinat_y_spesifik", type="number", example=8.25),
     *                     @OA\Property(property="alasan", type="string", example="Optimal placement for efficiency"),
     *                     @OA\Property(property="prioritas", type="string", enum={"rendah", "sedang", "tinggi"}, example="tinggi"),
     *                     @OA\Property(property="confidence_score", type="number", example=0.89),
     *                     @OA\Property(property="algoritma", type="string", example="Simulated Annealing")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recommendations created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Rekomendasi penempatan berhasil disimpan"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_rekomendasi", type="integer", example=2),
     *                 @OA\Property(property="log_optimasi_id", type="integer", example=2),
     *                 @OA\Property(
     *                     property="rekomendasi",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="barang_id", type="integer", example=1),
     *                         @OA\Property(property="status", type="string", example="menunggu"),
     *                         @OA\Property(property="created_at", type="string", example="2025-10-18T10:06:19.000000Z")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'log_optimasi_id' => 'required|exists:log_optimasi,id',
                'rekomendasi' => 'required|array|min:1',
                'rekomendasi.*.barang_id' => 'required|exists:barang,id',
                'rekomendasi.*.area_gudang_rekomendasi' => 'required|exists:area_gudang,id',
                'rekomendasi.*.koordinat_x_spesifik' => 'nullable|numeric',
                'rekomendasi.*.koordinat_y_spesifik' => 'nullable|numeric',
                'rekomendasi.*.alasan' => 'required|string',
                'rekomendasi.*.prioritas' => 'sometimes|in:rendah,sedang,tinggi',
                'rekomendasi.*.confidence_score' => 'nullable|numeric|between:0,1',
                'rekomendasi.*.algoritma' => 'nullable|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $rekomendasi_list = [];
            
            foreach ($request->rekomendasi as $rekomendasi_data) {
                $rekomendasi_list[] = RekomendasiPenempatan::create([
                    'log_optimasi_id' => $request->log_optimasi_id,
                    'barang_id' => $rekomendasi_data['barang_id'],
                    'area_gudang_rekomendasi' => $rekomendasi_data['area_gudang_rekomendasi'],
                    'koordinat_x_spesifik' => $rekomendasi_data['koordinat_x_spesifik'] ?? null,
                    'koordinat_y_spesifik' => $rekomendasi_data['koordinat_y_spesifik'] ?? null,
                    'alasan' => $rekomendasi_data['alasan'],
                    'prioritas' => $rekomendasi_data['prioritas'] ?? 'sedang',
                    'confidence_score' => $rekomendasi_data['confidence_score'] ?? 0.5,
                    'algoritma' => $rekomendasi_data['algoritma'] ?? 'Manual',
                    'status' => 'menunggu'
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Rekomendasi penempatan berhasil disimpan',
                'data' => [
                    'total_rekomendasi' => count($rekomendasi_list),
                    'log_optimasi_id' => $request->log_optimasi_id,
                    'rekomendasi' => $rekomendasi_list
                ]
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan rekomendasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/rekomendasi-penempatan/{rekomendasiPenempatan}",
     *     operationId="getRecommendationDetail",
     *     tags={"Rekomendasi Penempatan"},
     *     summary="Get specific placement recommendation details",
     *     description="Returns detailed information about a specific placement recommendation",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="rekomendasiPenempatan",
     *         in="path",
     *         required=true,
     *         description="Recommendation ID",
     *         @OA\Schema(type="integer", example=30)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recommendation details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Detail rekomendasi berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=30),
     *                 @OA\Property(property="log_optimasi_id", type="integer", example=12),
     *                 @OA\Property(property="barang_id", type="integer", example=3),
     *                 @OA\Property(property="koordinat_x_spesifik", type="number", example=30.76),
     *                 @OA\Property(property="koordinat_y_spesifik", type="number", example=9.65),
     *                 @OA\Property(property="alasan", type="string", example="Optimasi SA: Dokumen Kontrak di Area B1 - Lantai"),
     *                 @OA\Property(property="confidence_score", type="number", example=0.85),
     *                 @OA\Property(property="algoritma", type="string", example="Simulated Annealing"),
     *                 @OA\Property(property="prioritas", type="string", example="sedang"),
     *                 @OA\Property(property="status", type="string", example="menunggu"),
     *                 @OA\Property(property="catatan", type="string", nullable=true),
     *                 @OA\Property(property="tanggal_persetujuan", type="string", nullable=true),
     *                 @OA\Property(
     *                     property="barang",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="kode_barang", type="string", example="DOK-001"),
     *                     @OA\Property(property="nama_barang", type="string", example="Dokumen Kontrak")
     *                 ),
     *                 @OA\Property(
     *                     property="area_gudang_rekomendasi",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="kode_area", type="string", example="B1-01"),
     *                     @OA\Property(property="nama_area", type="string", example="Area B1 - Lantai")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Recommendation not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function show(RekomendasiPenempatan $rekomendasiPenempatan): JsonResponse
    {
        try {
            $rekomendasiPenempatan->load([
                'barang', 
                'areaGudangRekomendasi', 
                'areaGudangSaatIni',
                'logOptimasi',
                'disetujuiOleh'
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Detail rekomendasi berhasil diambil',
                'data' => $rekomendasiPenempatan
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil detail rekomendasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/rekomendasi-penempatan/{id}/status",
     *     operationId="updateRecommendationStatus",
     *     tags={"Rekomendasi Penempatan"},
     *     summary="Update recommendation status",
     *     description="Updates the status of a placement recommendation (approve, reject, etc.)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Recommendation ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"menunggu", "disetujui", "ditolak", "diimplementasi"},
     *                 example="disetujui",
     *                 description="New status for the recommendation"
     *             ),
     *             @OA\Property(property="catatan", type="string", example="Approved - optimal location", description="Manager notes"),
     *             @OA\Property(property="catatan_manager", type="string", example="Good placement for efficiency", description="Manager comments"),
     *             @OA\Property(property="target_penerapan", type="string", format="date", example="2025-10-25", description="Target implementation date"),
     *             @OA\Property(property="disetujui_oleh", type="integer", example=1, description="Approver user ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Status rekomendasi berhasil diperbarui"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="disetujui"),
     *                 @OA\Property(property="tanggal_persetujuan", type="string", example="2025-10-18T10:09:42.000000Z"),
     *                 @OA\Property(
     *                     property="disetujui_oleh",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nama", type="string", example="Supervisor NCS"),
     *                     @OA\Property(property="email", type="string", example="supervisor@ncs.com")
     *                 ),
     *                 @OA\Property(
     *                     property="barang",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nama_barang", type="string", example="Laptop Gaming ASUS ROG"),
     *                     @OA\Property(property="kode_barang", type="string", example="ELK-001")
     *                 ),
     *                 @OA\Property(
     *                     property="area_gudang_rekomendasi",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="kode_area", type="string", example="B1-01"),
     *                     @OA\Property(property="nama_area", type="string", example="Area B1 - Lantai")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Recommendation not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function updateStatus(Request $request, RekomendasiPenempatan $rekomendasiPenempatan): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:menunggu,disetujui,ditolak,diimplementasi',
                'catatan' => 'nullable|string',
                'disetujui_oleh' => 'nullable|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [
                'status' => $request->status,
                'catatan' => $request->catatan
            ];

            // Set approval info jika disetujui atau ditolak
            if (in_array($request->status, ['disetujui', 'ditolak'])) {
                $updateData['disetujui_oleh'] = $request->disetujui_oleh ?? 1;
                $updateData['tanggal_persetujuan'] = now();
            }

            $rekomendasiPenempatan->update($updateData);

            return response()->json([
                'status' => 'success',
                'message' => 'Status rekomendasi berhasil diperbarui',
                'data' => $rekomendasiPenempatan->load(['barang', 'areaGudangRekomendasi', 'disetujuiOleh'])
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/rekomendasi-penempatan/bulk-approve",
     *     operationId="bulkApproveRecommendations",
     *     tags={"Rekomendasi Penempatan"},
     *     summary="Bulk approve placement recommendations",
     *     description="Approves multiple placement recommendations at once",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rekomendasi_ids"},
     *             @OA\Property(
     *                 property="rekomendasi_ids",
     *                 type="array",
     *                 description="Array of recommendation IDs to approve",
     *                 @OA\Items(type="integer", example=30)
     *             ),
     *             @OA\Property(property="catatan", type="string", nullable=true, example="Approved for implementation", description="Optional approval notes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recommendations approved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="6 rekomendasi berhasil disetujui"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="approved_count", type="integer", example=6),
     *                 @OA\Property(property="catatan", type="string", nullable=true, example="Approved for implementation"),
     *                 @OA\Property(property="tanggal_persetujuan", type="string", example="2025-10-18T20:25:30.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rekomendasi_ids' => 'required|array|min:1',
                'rekomendasi_ids.*' => 'exists:rekomendasi_penempatan,id',
                'catatan' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updated = RekomendasiPenempatan::whereIn('id', $request->rekomendasi_ids)
                ->update([
                    'status' => 'disetujui',
                    'catatan' => $request->catatan,
                    'disetujui_oleh' => 1,
                    'tanggal_persetujuan' => now()
                ]);

            return response()->json([
                'status' => 'success',
                'message' => "Berhasil menyetujui {$updated} rekomendasi",
                'data' => [
                    'total_approved' => $updated,
                    'rekomendasi_ids' => $request->rekomendasi_ids
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyetujui rekomendasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/rekomendasi-penempatan/statistics",
     *     operationId="getRecommendationStatistics",
     *     tags={"Rekomendasi Penempatan"},
     *     summary="Get placement recommendations statistics",
     *     description="Returns comprehensive statistics about placement recommendations",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="log_optimasi_id",
     *         in="query",
     *         required=false,
     *         description="Filter statistics by optimization log ID",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recommendation statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Statistik rekomendasi berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_rekomendasi", type="integer", example=42, description="Total recommendations"),
     *                 @OA\Property(property="menunggu", type="integer", example=15, description="Pending recommendations"),
     *                 @OA\Property(property="disetujui", type="integer", example=20, description="Approved recommendations"),
     *                 @OA\Property(property="ditolak", type="integer", example=5, description="Rejected recommendations"),
     *                 @OA\Property(property="diimplementasi", type="integer", example=2, description="Implemented recommendations"),
     *                 @OA\Property(property="tingkat_persetujuan", type="number", example=47.62, description="Approval rate percentage"),
     *                 @OA\Property(
     *                     property="rekomendasi_per_prioritas",
     *                     type="array",
     *                     description="Recommendations by priority level",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="prioritas", type="string", example="tinggi"),
     *                         @OA\Property(property="total", type="integer", example=15)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="rekomendasi_per_algoritma",
     *                     type="array",
     *                     description="Recommendations by algorithm",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="algoritma", type="string", example="Simulated Annealing"),
     *                         @OA\Property(property="total", type="integer", example=42)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $query = RekomendasiPenempatan::query();
            
            // Filter berdasarkan log optimasi jika ada
            if ($request->has('log_optimasi_id')) {
                $query->where('log_optimasi_id', $request->log_optimasi_id);
            }
            
            $stats = [
                'total_rekomendasi' => $query->count(),
                'menunggu' => (clone $query)->where('status', 'menunggu')->count(),
                'disetujui' => (clone $query)->where('status', 'disetujui')->count(),
                'ditolak' => (clone $query)->where('status', 'ditolak')->count(),
                'diimplementasi' => (clone $query)->where('status', 'diimplementasi')->count(),
                'tingkat_persetujuan' => $query->count() > 0 
                    ? round(($query->where('status', 'disetujui')->count() / $query->count()) * 100, 2)
                    : 0,
                'rekomendasi_per_prioritas' => $query->selectRaw('prioritas, COUNT(*) as total')
                    ->groupBy('prioritas')
                    ->get()
            ];
            
            return response()->json([
                'status' => 'success',
                'message' => 'Statistik rekomendasi berhasil diambil',
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil statistik: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/rekomendasi-penempatan/{rekomendasiPenempatan}",
     *     operationId="deleteRecommendation",
     *     tags={"Rekomendasi Penempatan"},
     *     summary="Delete placement recommendation",
     *     description="Deletes a specific placement recommendation",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="rekomendasiPenempatan",
     *         in="path",
     *         required=true,
     *         description="Recommendation ID",
     *         @OA\Schema(type="integer", example=30)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recommendation deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Rekomendasi berhasil dihapus")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Recommendation not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function destroy(RekomendasiPenempatan $rekomendasiPenempatan): JsonResponse
    {
        try {
            $rekomendasiPenempatan->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Rekomendasi berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus rekomendasi: ' . $e->getMessage()
            ], 500);
        }
    }
}
