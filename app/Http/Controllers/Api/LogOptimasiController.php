<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogOptimasi;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Log Optimasi",
 *     description="API endpoints for managing optimization logs and history"
 * )
 */
class LogOptimasiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/log-optimasi",
     *     operationId="getOptimizationLogs",
     *     tags={"Log Optimasi"},
     *     summary="Get optimization logs",
     *     description="Returns paginated list of optimization logs with filtering options",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by optimization status",
     *         @OA\Schema(type="string", enum={"sedang_berjalan", "selesai", "gagal", "dibatalkan"})
     *     ),
     *     @OA\Parameter(
     *         name="algoritma",
     *         in="query",
     *         required=false,
     *         description="Filter by algorithm type",
     *         @OA\Schema(type="string", example="Simulated Annealing")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Optimization logs retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Log optimasi berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="total", type="integer", example=12),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=12),
     *                         @OA\Property(property="algoritma", type="string", example="Simulated Annealing"),
     *                         @OA\Property(property="parameter_optimasi", type="string", example="{""gudang_ids"":[1],""barang_ids"":[1,2,3]}"),
     *                         @OA\Property(property="target_optimasi", type="string", example="Optimisasi penempatan barang di ruang gudang"),
     *                         @OA\Property(property="estimasi_waktu", type="integer", example=300),
     *                         @OA\Property(property="status", type="string", example="selesai"),
     *                         @OA\Property(property="waktu_mulai", type="string", example="2025-10-18T13:15:31.000000Z"),
     *                         @OA\Property(property="waktu_selesai", type="string", nullable=true, example="2025-10-18T20:15:31.000000Z"),
     *                         @OA\Property(property="hasil_optimasi", type="object", nullable=true),
     *                         @OA\Property(property="dibuat_oleh", type="integer", example=1)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = LogOptimasi::with(['user']);
            
            // Filter berdasarkan status jika ada
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            // Filter berdasarkan algoritma jika ada
            if ($request->has('algoritma')) {
                $query->where('algoritma', $request->algoritma);
            }
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Log optimasi berhasil diambil',
                'data' => $logs
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil log optimasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/log-optimasi",
     *     operationId="createOptimizationLog",
     *     tags={"Log Optimasi"},
     *     summary="Create new optimization log",
     *     description="Creates a new optimization log entry",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"algoritma", "parameter_optimasi", "target_optimasi"},
     *             @OA\Property(property="algoritma", type="string", example="Simulated Annealing", description="Algorithm name"),
     *             @OA\Property(property="parameter_optimasi", type="string", example="{\""gudang_ids\"":[1],\""barang_ids\"":[1,2,3]}", description="JSON string of optimization parameters"),
     *             @OA\Property(property="target_optimasi", type="string", example="Optimisasi penempatan barang di ruang gudang", description="Optimization objective description"),
     *             @OA\Property(property="estimasi_waktu", type="integer", example=300, description="Estimated execution time in seconds")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Optimization log created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Log optimasi berhasil dibuat"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=13),
     *                 @OA\Property(property="algoritma", type="string", example="Simulated Annealing"),
     *                 @OA\Property(property="parameter_optimasi", type="string", example="{\""gudang_ids\"":[1]}"),
     *                 @OA\Property(property="target_optimasi", type="string", example="Optimisasi penempatan barang"),
     *                 @OA\Property(property="status", type="string", example="sedang_berjalan"),
     *                 @OA\Property(property="waktu_mulai", type="string", example="2025-10-18T13:22:01.000000Z"),
     *                 @OA\Property(property="dibuat_oleh", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'algoritma' => 'required|string|max:100',
                'parameter_optimasi' => 'required|json',
                'target_optimasi' => 'required|string',
                'estimasi_waktu' => 'nullable|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $logOptimasi = LogOptimasi::create([
                'algoritma' => $request->algoritma,
                'parameter_optimasi' => $request->parameter_optimasi,
                'target_optimasi' => $request->target_optimasi,
                'estimasi_waktu' => $request->estimasi_waktu,
                'status' => 'sedang_berjalan',
                'waktu_mulai' => now(),
                'dibuat_oleh' => 1 // Default user
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Log optimasi berhasil dibuat',
                'data' => $logOptimasi->load('user')
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat log optimasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/log-optimasi/{logOptimasi}",
     *     operationId="getOptimizationLog",
     *     tags={"Log Optimasi"},
     *     summary="Get specific optimization log details",
     *     description="Returns detailed information about a specific optimization log including related recommendations",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="logOptimasi",
     *         in="path",
     *         required=true,
     *         description="Optimization log ID",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Optimization log details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Detail log optimasi berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=12),
     *                 @OA\Property(property="algoritma", type="string", example="Simulated Annealing"),
     *                 @OA\Property(property="parameter_optimasi", type="string", example="{\""gudang_ids\"":[1],\""barang_ids\"":[1,2,3]}"),
     *                 @OA\Property(property="target_optimasi", type="string", example="Optimisasi penempatan barang di ruang gudang"),
     *                 @OA\Property(property="status", type="string", example="selesai"),
     *                 @OA\Property(property="waktu_mulai", type="string", example="2025-10-18T13:15:31.000000Z"),
     *                 @OA\Property(property="waktu_selesai", type="string", nullable=true, example="2025-10-18T20:15:31.000000Z"),
     *                 @OA\Property(property="hasil_optimasi", type="object", nullable=true),
     *                 @OA\Property(property="metrik_hasil", type="object", nullable=true),
     *                 @OA\Property(
     *                     property="rekomendasi_penempatan",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=30),
     *                         @OA\Property(property="barang_id", type="integer", example=3),
     *                         @OA\Property(property="area_gudang_rekomendasi", type="integer", example=3),
     *                         @OA\Property(property="koordinat_x_spesifik", type="number", example=30.76),
     *                         @OA\Property(property="koordinat_y_spesifik", type="number", example=9.65),
     *                         @OA\Property(property="status", type="string", example="menunggu")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Optimization log not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function show(LogOptimasi $logOptimasi): JsonResponse
    {
        try {
            $logOptimasi->load(['user', 'rekomendasiPenempatan.barang', 'rekomendasiPenempatan.areaGudang']);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Detail log optimasi berhasil diambil',
                'data' => $logOptimasi
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil detail log optimasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/log-optimasi/{logOptimasi}",
     *     operationId="updateOptimizationLog",
     *     tags={"Log Optimasi"},
     *     summary="Update optimization log",
     *     description="Updates optimization log status and results",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="logOptimasi",
     *         in="path",
     *         required=true,
     *         description="Optimization log ID",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"sedang_berjalan", "selesai", "gagal", "dibatalkan"}, example="selesai"),
             @OA\Property(property="hasil_optimasi", type="string", example="{\""final_cost\"":15047.02,\""total_items\"":6}", description="JSON string of optimization results"),
             @OA\Property(property="metrik_hasil", type="string", example="{\""execution_time\"":0.1,\""areas_utilized\"":4}", description="JSON string of optimization metrics"),
     *             @OA\Property(property="waktu_selesai", type="string", format="date-time", example="2025-10-18T20:15:31Z"),
     *             @OA\Property(property="log_error", type="string", example="Error message if any", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Optimization log updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Log optimasi berhasil diperbarui"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=12),
     *                 @OA\Property(property="algoritma", type="string", example="Simulated Annealing"),
     *                 @OA\Property(property="status", type="string", example="selesai"),
     *                 @OA\Property(property="waktu_selesai", type="string", example="2025-10-18T20:15:31.000000Z"),
     *                 @OA\Property(property="hasil_optimasi", type="object"),
     *                 @OA\Property(property="metrik_hasil", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Optimization log not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function update(Request $request, LogOptimasi $logOptimasi): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|in:sedang_berjalan,selesai,gagal,dibatalkan',
                'hasil_optimasi' => 'sometimes|json',
                'metrik_hasil' => 'sometimes|json',
                'waktu_selesai' => 'sometimes|date',
                'log_error' => 'sometimes|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Auto set waktu_selesai jika status berubah ke selesai/gagal
            if ($request->has('status') && in_array($request->status, ['selesai', 'gagal', 'dibatalkan'])) {
                $request->merge(['waktu_selesai' => now()]);
            }

            $logOptimasi->update($request->only([
                'status', 'hasil_optimasi', 'metrik_hasil', 'waktu_selesai', 'log_error'
            ]));

            return response()->json([
                'status' => 'success',
                'message' => 'Log optimasi berhasil diperbarui',
                'data' => $logOptimasi->load('user')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui log optimasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/log-optimasi/{logOptimasi}",
     *     operationId="deleteOptimizationLog",
     *     tags={"Log Optimasi"},
     *     summary="Delete optimization log",
     *     description="Deletes a specific optimization log and its related data",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="logOptimasi",
     *         in="path",
     *         required=true,
     *         description="Optimization log ID",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Optimization log deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Log optimasi berhasil dihapus")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Optimization log not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function destroy(LogOptimasi $logOptimasi): JsonResponse
    {
        try {
            $logOptimasi->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Log optimasi berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus log optimasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/log-optimasi/statistics",
     *     operationId="getOptimizationStatistics",
     *     tags={"Log Optimasi"},
     *     summary="Get optimization statistics",
     *     description="Returns comprehensive statistics about optimization logs and performance",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Optimization statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Statistik optimasi berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_optimasi", type="integer", example=16, description="Total number of optimizations"),
     *                 @OA\Property(property="optimasi_berhasil", type="integer", example=14, description="Number of successful optimizations"),
     *                 @OA\Property(property="optimasi_gagal", type="integer", example=1, description="Number of failed optimizations"),
     *                 @OA\Property(property="optimasi_berjalan", type="integer", example=1, description="Number of running optimizations"),
     *                 @OA\Property(
     *                     property="algoritma_populer",
     *                     type="array",
     *                     description="Most popular algorithms",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="algoritma", type="string", example="Simulated Annealing"),
     *                         @OA\Property(property="total", type="integer", example=16)
     *                     )
     *                 ),
     *                 @OA\Property(property="rata_rata_waktu", type="number", nullable=true, example=0.15, description="Average execution time in seconds")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_optimasi' => LogOptimasi::count(),
                'optimasi_berhasil' => LogOptimasi::where('status', 'selesai')->count(),
                'optimasi_gagal' => LogOptimasi::where('status', 'gagal')->count(),
                'optimasi_berjalan' => LogOptimasi::where('status', 'sedang_berjalan')->count(),
                'algoritma_populer' => LogOptimasi::selectRaw('algoritma, COUNT(*) as total')
                    ->groupBy('algoritma')
                    ->orderBy('total', 'desc')
                    ->limit(5)
                    ->get(),
                'rata_rata_waktu' => LogOptimasi::whereNotNull('waktu_selesai')
                    ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, waktu_mulai, waktu_selesai)) as avg_seconds')
                    ->value('avg_seconds')
            ];
            
            return response()->json([
                'status' => 'success',
                'message' => 'Statistik optimasi berhasil diambil',
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil statistik: ' . $e->getMessage()
            ], 500);
        }
    }
}
