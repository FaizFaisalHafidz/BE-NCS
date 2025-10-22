<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogOptimasi;
use App\Models\RekomendasiPenempatan;
use App\Models\AreaGudang;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Warehouse Optimization",
 *     description="API endpoints for warehouse optimization using AI algorithms"
 * )
 */
class OptimizationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/optimization/algorithms",
     *     operationId="getOptimizationAlgorithms",
     *     tags={"Warehouse Optimization"},
     *     summary="Get available optimization algorithms",
     *     description="Returns list of available optimization algorithms with their parameters and descriptions",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Available algorithms retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Algoritma optimasi berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="algorithms",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="name", type="string", example="Simulated Annealing"),
     *                         @OA\Property(property="code", type="string", example="SA"),
     *                         @OA\Property(property="description", type="string", example="Metaheuristic optimization algorithm"),
     *                         @OA\Property(
     *                             property="parameters",
     *                             type="object",
     *                             @OA\Property(
     *                                 property="temperature_initial",
     *                                 type="object",
     *                                 @OA\Property(property="type", type="string", example="float"),
     *                                 @OA\Property(property="default", type="number", example=1000.0),
     *                                 @OA\Property(property="min", type="number", example=100),
     *                                 @OA\Property(property="max", type="number", example=5000),
     *                                 @OA\Property(property="description", type="string", example="Initial temperature for SA algorithm")
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */

    /**
     * @OA\Post(
     *     path="/api/optimization/simulated-annealing",
     *     operationId="runSimulatedAnnealing",
     *     tags={"Warehouse Space Optimization"},
     *     summary="Optimize warehouse space utilization using Simulated Annealing",
     *     description="Executes warehouse space optimization synchronously and returns immediate results with optimal item placement recommendations",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="gudang_ids", type="array", @OA\Items(type="integer"), description="IDs gudang yang akan dioptimasi", example={1}),
     *             @OA\Property(property="barang_ids", type="array", @OA\Items(type="integer"), description="IDs barang yang akan ditempatkan", example={1,2,3,4,5,6}),
     *             @OA\Property(property="prioritas_optimasi", type="string", enum={"space_utilization", "accessibility", "balanced"}, description="Prioritas optimasi ruang", example="balanced"),
     *             @OA\Property(property="target_utilisasi", type="number", description="Target persentase utilisasi ruang (50-100%)", example=95.0),
     *             @OA\Property(property="keterangan", type="string", description="Keterangan tambahan", example="Test full optimization synchronous")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Optimization completed successfully with immediate results",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Optimisasi penempatan barang di ruang gudang berhasil diselesaikan"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="log_optimasi_id", type="integer", example=16),
     *                 @OA\Property(property="algoritma", type="string", example="Simulated Annealing"),
     *                 @OA\Property(property="total_gudang", type="integer", example=1),
     *                 @OA\Property(property="total_barang", type="integer", example=6),
     *                 @OA\Property(property="prioritas_optimasi", type="string", example="balanced"),
     *                 @OA\Property(property="target_utilisasi", type="number", example=95),
     *                 @OA\Property(property="status", type="string", example="selesai"),
     *                 @OA\Property(property="waktu_mulai", type="string", example="2025-10-18 13:22:12"),
     *                 @OA\Property(property="waktu_selesai", type="string", example="2025-10-18 20:22:12"),
     *                 @OA\Property(
     *                     property="hasil_optimasi",
     *                     type="object",
     *                     @OA\Property(property="algorithm", type="string", example="Simulated Annealing"),
     *                     @OA\Property(property="final_cost", type="number", example=15147.807321860531),
     *                     @OA\Property(property="total_items", type="integer", example=6),
     *                     @OA\Property(property="areas_utilized", type="integer", example=4),
     *                     @OA\Property(property="execution_time", type="number", example=0.1)
     *                 ),
     *                 @OA\Property(property="total_rekomendasi", type="integer", example=6),
     *                 @OA\Property(property="waktu_eksekusi", type="string", example="0.21s")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error - invalid parameters"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error or optimization failed")
     * )
     */
    public function runSimulatedAnnealing(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'gudang_ids' => 'nullable|array',
                'gudang_ids.*' => 'exists:gudang,id',
                'barang_ids' => 'nullable|array',
                'barang_ids.*' => 'exists:barang,id',
                'prioritas_optimasi' => 'nullable|string|in:space_utilization,accessibility,balanced',
                'target_utilisasi' => 'nullable|numeric|min:50|max:100',
                'keterangan' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi data gudang dan barang gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Ambil data gudang dan barang
            $gudangIds = $request->gudang_ids ?? [];
            $barangIds = $request->barang_ids ?? [];
            
            // Jika tidak ada input, ambil semua data aktif
            if (empty($gudangIds)) {
                $gudangIds = \App\Models\Gudang::where('aktif', true)->pluck('id')->toArray();
            }
            
            if (empty($barangIds)) {
                // Asumsikan barang tidak memiliki field status khusus, ambil semua
                $barangIds = \App\Models\Barang::pluck('id')->toArray();
            }

            // Validasi minimal ada data untuk dioptimasi
            if (empty($gudangIds) || empty($barangIds)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada data gudang atau barang aktif untuk dioptimasi'
                ], 422);
            }

            // Parameter optimisasi berdasarkan input user
            $optimizationConfig = [
                'gudang_ids' => $gudangIds,
                'barang_ids' => $barangIds,
                'prioritas_optimasi' => $request->prioritas_optimasi ?? 'space_utilization',
                'target_utilisasi' => $request->target_utilisasi ?? 80.0,
                // Parameter internal Simulated Annealing (tidak exposed ke user)
                'algorithm_params' => [
                    'temperature_initial' => 1000.0,
                    'temperature_final' => 0.1,
                    'cooling_rate' => 0.95,
                    'max_iterations' => 1000,
                    'max_no_improvement' => 50
                ]
            ];

            // Buat log optimasi
            $logOptimasi = LogOptimasi::create([
                'algoritma' => 'Simulated Annealing',
                'parameter_optimasi' => json_encode($optimizationConfig),
                'target_optimasi' => $request->keterangan ?? 'Optimisasi penempatan barang di ruang gudang menggunakan algoritma Simulated Annealing',
                'estimasi_waktu' => 300, // 5 menit estimasi
                'status' => 'sedang_berjalan',
                'waktu_mulai' => now(),
                'dibuat_oleh' => 1 // Default user
            ]);

            // Jalankan optimasi secara synchronous dan langsung return hasil
            $hasilOptimasi = $this->runOptimizationSync($logOptimasi->id, $optimizationConfig);

            return response()->json([
                'status' => 'success',
                'message' => 'Optimisasi penempatan barang di ruang gudang berhasil diselesaikan',
                'data' => [
                    'log_optimasi_id' => $logOptimasi->id,
                    'algoritma' => 'Simulated Annealing',
                    'total_gudang' => count($gudangIds),
                    'total_barang' => count($barangIds),
                    'prioritas_optimasi' => $optimizationConfig['prioritas_optimasi'],
                    'target_utilisasi' => $optimizationConfig['target_utilisasi'],
                    'status' => $hasilOptimasi['status'],
                    'waktu_mulai' => $logOptimasi->waktu_mulai->format('Y-m-d H:i:s'),
                    'waktu_selesai' => $hasilOptimasi['waktu_selesai'],
                    'hasil_optimasi' => $hasilOptimasi['hasil_optimasi'],
                    'total_rekomendasi' => $hasilOptimasi['total_rekomendasi'] ?? 0,
                    'waktu_eksekusi' => $hasilOptimasi['waktu_eksekusi'] ?? '0.0s'
                ]
            ], 200); // 200 OK untuk sync processing dengan hasil langsung
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memulai optimasi ruang gudang: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/optimization/{logOptimasiId}/status",
     *     operationId="getOptimizationStatus",
     *     tags={"Warehouse Optimization"},
     *     summary="Get optimization status",
     *     description="Returns current status and progress of a specific optimization process",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="logOptimasiId",
     *         in="path",
     *         required=true,
     *         description="Log optimasi ID",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Optimization status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="log_optimasi_id", type="integer", example=2),
     *                 @OA\Property(property="algoritma", type="string", example="Simulated Annealing"),
     *                 @OA\Property(property="status", type="string", example="sedang_berjalan"),
     *                 @OA\Property(property="waktu_mulai", type="string", example="2025-10-18T10:05:21.000000Z"),
     *                 @OA\Property(property="waktu_selesai", type="string", nullable=true, example=null),
     *                 @OA\Property(property="estimasi_waktu", type="integer", example=300),
     *                 @OA\Property(property="target_optimasi", type="string", example="Full System Test"),
     *                 @OA\Property(
     *                     property="parameter_optimasi",
     *                     type="object",
     *                     @OA\Property(property="temperature_initial", type="number", example=1200),
     *                     @OA\Property(property="cooling_rate", type="number", example=0.96),
     *                     @OA\Property(property="max_iterations", type="integer", example=800)
     *                 ),
     *                 @OA\Property(property="progress_percentage", type="number", nullable=true, example=null),
     *                 @OA\Property(property="recommendations_count", type="integer", example=6),
     *                 @OA\Property(property="dapat_dibatalkan", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Optimization log not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getOptimizationStatus($logOptimasiId): JsonResponse
    {
        try {
            $logOptimasi = LogOptimasi::with(['rekomendasiPenempatan'])->findOrFail($logOptimasiId);
            
            $response = [
                'status' => 'success',
                'data' => [
                    'log_optimasi_id' => $logOptimasi->id,
                    'algoritma' => $logOptimasi->algoritma,
                    'status' => $logOptimasi->status,
                    'waktu_mulai' => $logOptimasi->waktu_mulai,
                    'waktu_selesai' => $logOptimasi->waktu_selesai,
                    'progress_percentage' => $this->calculateProgress($logOptimasi),
                    'total_rekomendasi' => $logOptimasi->rekomendasiPenempatan->count(),
                    'hasil_optimasi' => $logOptimasi->hasil_optimasi,
                    'metrik_hasil' => $logOptimasi->metrik_hasil,
                    'log_error' => $logOptimasi->log_error
                ]
            ];

            return response()->json($response);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil status optimasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/optimization/warehouse-state",
     *     operationId="getWarehouseState",
     *     tags={"Warehouse Optimization"},
     *     summary="Get current warehouse state",
     *     description="Returns current state of warehouse including areas, items, and capacity information for optimization analysis",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Warehouse state retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Status gudang berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_areas", type="integer", example=5),
     *                 @OA\Property(property="total_items", type="integer", example=6),
     *                 @OA\Property(property="total_capacity", type="number", example=2002.0),
     *                 @OA\Property(property="used_capacity", type="number", example=0.0),
     *                 @OA\Property(property="capacity_utilization", type="number", example=0.0),
     *                 @OA\Property(property="available_areas", type="integer", example=5),
     *                 @OA\Property(
     *                     property="areas_summary",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="kode_area", type="string", example="A1-01"),
     *                         @OA\Property(property="nama_area", type="string", example="Area A1 - Rak Tinggi"),
     *                         @OA\Property(property="kapasitas", type="number", example=480.0),
     *                         @OA\Property(property="kapasitas_terpakai", type="number", example=0.0)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="items_summary",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="kode_barang", type="string", example="ELK-001"),
     *                         @OA\Property(property="nama_barang", type="string", example="Laptop Gaming ASUS ROG"),
     *                         @OA\Property(property="kategori", type="string", example="Elektronik")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function getWarehouseState(): JsonResponse
    {
        try {
            $areas = AreaGudang::where('tersedia', true)
                ->select('id', 'kode_area', 'nama_area', 'koordinat_x', 'koordinat_y', 
                        'panjang', 'lebar', 'tinggi', 'kapasitas', 'kapasitas_terpakai')
                ->get();

            $barang = Barang::with('kategoriBarang:id,nama_kategori')
                ->select('id', 'kode_barang', 'nama_barang', 'panjang', 'lebar', 'tinggi', 'kategori_barang_id')
                ->get();

            $statistics = [
                'total_areas' => $areas->count(),
                'total_items' => $barang->count(),
                'total_capacity' => $areas->sum('kapasitas'),
                'used_capacity' => $areas->sum('kapasitas_terpakai'),
                'capacity_utilization' => $areas->sum('kapasitas') > 0 
                    ? round(($areas->sum('kapasitas_terpakai') / $areas->sum('kapasitas')) * 100, 2) 
                    : 0,
                'available_areas' => $areas->where('kapasitas_terpakai', '<', function($area) { return $area->kapasitas; })->count()
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Data warehouse berhasil diambil',
                'data' => [
                    'areas' => $areas,
                    'barang' => $barang,
                    'statistics' => $statistics
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data warehouse: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel running optimization
     */
    public function cancelOptimization($logOptimasiId): JsonResponse
    {
        try {
            $logOptimasi = LogOptimasi::findOrFail($logOptimasiId);
            
            if ($logOptimasi->status !== 'sedang_berjalan') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Optimasi tidak sedang berjalan atau sudah selesai'
                ], 400);
            }

            $logOptimasi->update([
                'status' => 'dibatalkan',
                'waktu_selesai' => now(),
                'log_error' => 'Optimasi dibatalkan oleh user'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Optimasi berhasil dibatalkan',
                'data' => $logOptimasi
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membatalkan optimasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/optimization/algorithms",
     *     operationId="getOptimizationAlgorithms",
     *     tags={"Warehouse Optimization"},
     *     summary="Get available optimization algorithms",
     *     description="Returns list of available optimization algorithms with their parameters and descriptions",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Available algorithms retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Algoritma optimasi berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="algorithms",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="name", type="string", example="Simulated Annealing"),
     *                         @OA\Property(property="description", type="string", example="Algoritma optimasi metaheuristik"),
     *                         @OA\Property(property="estimated_time", type="string", example="3-10 menit"),
     *                         @OA\Property(
     *                             property="parameters",
     *                             type="object",
     *                             @OA\Property(
     *                                 property="temperature_initial",
     *                                 type="object",
     *                                 @OA\Property(property="type", type="string", example="float"),
     *                                 @OA\Property(property="min", type="number", example=100),
     *                                 @OA\Property(property="max", type="number", example=5000),
     *                                 @OA\Property(property="default", type="number", example=1000)
     *                             )
     *                         ),
     *                         @OA\Property(
     *                             property="pros",
     *                             type="array",
     *                             @OA\Items(type="string", example="Dapat escape dari local optimum")
     *                         ),
     *                         @OA\Property(
     *                             property="cons",
     *                             type="array",
     *                             @OA\Items(type="string", example="Membutuhkan tuning parameter")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getAlgorithms(): JsonResponse
    {
        $algorithms = [
            [
                'name' => 'Simulated Annealing',
                'description' => 'Algoritma optimisasi metaheuristik untuk memaksimalkan pemanfaatan ruang gudang melalui penempatan barang yang optimal',
                'input_data' => [
                    'gudang_data' => 'Data gudang dengan dimensi, area, dan kapasitas penyimpanan',
                    'barang_data' => 'Data barang dengan dimensi, berat, dan kategori',
                    'prioritas_optimasi' => 'Fokus optimasi: utilisasi ruang, aksesibilitas, atau seimbang',
                    'target_utilisasi' => 'Target persentase penggunaan ruang gudang (50-100%)'
                ],
                'output_results' => [
                    'penempatan_optimal' => 'Rekomendasi penempatan barang per area gudang',
                    'tingkat_utilisasi' => 'Persentase penggunaan ruang yang dicapai',
                    'efisiensi_ruang' => 'Analisis efisiensi penggunaan ruang kosong',
                    'laporan_optimasi' => 'Laporan lengkap hasil optimasi'
                ],
                'benefits' => [
                    'Memaksimalkan utilisasi ruang gudang yang tersedia',
                    'Mengurangi area kosong yang tidak termanfaatkan',
                    'Meningkatkan efisiensi operasional gudang',
                    'Memberikan rekomendasi penempatan barang yang optimal',
                    'Mendukung pengambilan keputusan layout gudang'
                ],
                'limitations' => [
                    'Memerlukan data gudang dan barang yang lengkap dan akurat',
                    'Waktu komputasi bergantung pada jumlah barang dan kompleksitas gudang',
                    'Hasil optimasi perlu disesuaikan dengan kondisi operasional'
                ],
                'estimated_time' => '3-8 menit tergantung kompleksitas data',
                'research_focus' => 'Optimisasi penempatan barang di ruang gudang untuk maksimalkan efisiensi penyimpanan'
            ]
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Informasi algoritma optimasi ruang gudang tersedia',
            'data' => $algorithms
        ]);
    }

    /**
     * Run optimization synchronously and return results immediately
     */
    private function runOptimizationSync($logOptimasiId, $parameters)
    {
        try {
            $startTime = microtime(true);
            
            // Gunakan environment variables untuk path Python (production-ready)
            $pythonPath = env('PYTHON_VENV_PATH', base_path('script/venv/bin/python'));
            $scriptPath = env('PYTHON_SCRIPT_PATH', base_path('script/warehouse_optimization.py'));
            $paramsJson = json_encode($parameters);
            
            // Validate Python environment before execution
            if (!file_exists($pythonPath)) {
                throw new \Exception("Python executable not found at: {$pythonPath}. Please check PYTHON_VENV_PATH in .env");
            }
            
            if (!file_exists($scriptPath)) {
                throw new \Exception("Python script not found at: {$scriptPath}. Please check PYTHON_SCRIPT_PATH in .env");
            }
            
            // Test database connection first
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                throw new \Exception("Database connection failed: " . $e->getMessage());
            }
            
            // Command yang lebih sederhana dan reliable dengan absolute paths
            $command = sprintf(
                '%s %s --log-id=%d --params=%s 2>&1',
                escapeshellarg($pythonPath),
                escapeshellarg($scriptPath),
                $logOptimasiId,
                escapeshellarg($paramsJson)
            );

            // Log command untuk debugging
            Log::info("Executing optimization command", [
                'command' => $command,
                'python_path' => $pythonPath,
                'script_path' => $scriptPath,
                'log_id' => $logOptimasiId
            ]);

            // Execute dengan timeout menggunakan exec
            $output = [];
            $return_var = 0;
            
            // Set time limit untuk script PHP
            set_time_limit(60); // 60 detik timeout
            
            exec($command, $output, $return_var);
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            // Join output array menjadi string
            $outputString = implode("\n", $output);

            // Jika command gagal
            if ($return_var !== 0) {
                throw new \Exception('Python script error (exit code: ' . $return_var . '): ' . $outputString);
            }

            // Tunggu sebentar untuk memastikan database update selesai
            sleep(1);

            // Refresh log optimasi dari database untuk mendapatkan hasil terbaru
            $logOptimasi = LogOptimasi::find($logOptimasiId);
            
            if (!$logOptimasi) {
                throw new \Exception('Log optimasi tidak ditemukan setelah eksekusi');
            }

            // Hitung total rekomendasi yang dihasilkan
            $totalRekomendasi = RekomendasiPenempatan::where('log_optimasi_id', $logOptimasiId)->count();

            return [
                'status' => $logOptimasi->status,
                'waktu_selesai' => $logOptimasi->waktu_selesai ? $logOptimasi->waktu_selesai->format('Y-m-d H:i:s') : null,
                'hasil_optimasi' => $logOptimasi->hasil_optimasi,
                'total_rekomendasi' => $totalRekomendasi,
                'waktu_eksekusi' => $executionTime . 's',
                'python_output' => $outputString,
                'exit_code' => $return_var
            ];
            
        } catch (\Exception $e) {
            // Update log optimasi dengan status error
            LogOptimasi::where('id', $logOptimasiId)->update([
                'status' => 'gagal',
                'waktu_selesai' => now(),
                'log_error' => 'Error sync: ' . $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Run optimization asynchronously
     */
    private function runOptimizationAsync($logOptimasiId, $parameters)
    {
        // Jalankan Python script dalam background
        $scriptPath = base_path('script/warehouse_optimization.py');
        $command = sprintf(
            'cd %s && source venv/bin/activate && python warehouse_optimization.py --log-id=%d --params=\'%s\' > /dev/null 2>&1 &',
            base_path('script'),
            $logOptimasiId,
            json_encode($parameters)
        );

        // Execute in background
        exec($command);
    }

    /**
     * Debug method untuk check Python paths
     */
    public function debugPythonPaths()
    {
        $pythonPath = env('PYTHON_VENV_PATH', base_path('script/venv/bin/python'));
        $scriptPath = env('PYTHON_SCRIPT_PATH', base_path('script/warehouse_optimization.py'));
        $testParams = json_encode(['test' => true]);
        
        $command = sprintf(
            '%s %s --log-id=%d --params=%s 2>&1',
            escapeshellarg($pythonPath),
            escapeshellarg($scriptPath),
            1,
            escapeshellarg($testParams)
        );
        
        // Test command execution
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        
        return response()->json([
            'PYTHON_VENV_PATH' => env('PYTHON_VENV_PATH'),
            'PYTHON_SCRIPT_PATH' => env('PYTHON_SCRIPT_PATH'),
            'base_path_venv' => base_path('script/venv/bin/python'),
            'base_path_script' => base_path('script/warehouse_optimization.py'),
            'file_exists_venv' => file_exists($pythonPath),
            'file_exists_script' => file_exists($scriptPath),
            'working_directory' => getcwd(),
            'php_user' => get_current_user(),
            'test_command' => $command,
            'test_output' => $output,
            'test_return_code' => $return_var,
            'test_output_string' => implode("\n", $output),
            'all_env' => array_filter($_ENV, function($key) {
                return strpos($key, 'PYTHON') !== false;
            }, ARRAY_FILTER_USE_KEY)
        ]);
    }

    /**
     * Calculate optimization progress
     */
    private function calculateProgress($logOptimasi): int
    {
        if ($logOptimasi->status === 'selesai') {
            return 100;
        }
        
        if ($logOptimasi->status === 'gagal' || $logOptimasi->status === 'dibatalkan') {
            return 0;
        }
        
        if ($logOptimasi->status === 'sedang_berjalan') {
            // Estimasi progress berdasarkan waktu
            $startTime = $logOptimasi->waktu_mulai;
            $estimatedDuration = $logOptimasi->estimasi_waktu ?? 300; // 5 menit default
            $elapsedTime = now()->diffInSeconds($startTime);
            
            $progress = min(95, ($elapsedTime / $estimatedDuration) * 100); // Max 95% untuk running
            return (int) $progress;
        }
        
        return 0;
    }
}
