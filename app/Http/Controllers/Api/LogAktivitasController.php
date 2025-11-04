<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LogAktivitasController extends Controller
{
    /**
     * Display a listing of log aktivitas.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = LogAktivitas::with(['user:id,name,email'])
                ->orderBy('created_at', 'desc');

            // Filter by user
            if ($request->has('user_id') && $request->user_id) {
                $query->byUser($request->user_id);
            }

            // Filter by action
            if ($request->has('aksi') && $request->aksi) {
                $query->byAksi($request->aksi);
            }

            // Filter by model type
            if ($request->has('model_type') && $request->model_type) {
                $query->where('model_type', $request->model_type);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->start_date) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $query->where('created_at', '>=', $startDate);
            }

            if ($request->has('end_date') && $request->end_date) {
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                $query->where('created_at', '<=', $endDate);
            }

            // Quick filters
            if ($request->has('filter')) {
                switch ($request->filter) {
                    case 'today':
                        $query->today();
                        break;
                    case 'week':
                        $query->thisWeek();
                        break;
                    case 'month':
                        $query->thisMonth();
                        break;
                }
            }

            // Search in description
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('deskripsi', 'like', "%{$search}%")
                      ->orWhere('aksi', 'like', "%{$search}%");
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $logs = $query->paginate($perPage);

            // Transform data
            $logs->getCollection()->transform(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => [
                        'id' => $log->user?->id,
                        'name' => $log->user?->name,
                        'email' => $log->user?->email,
                    ],
                    'aksi' => $log->aksi,
                    'aksi_formatted' => $log->aksi_formatted,
                    'deskripsi' => $log->deskripsi,
                    'model_type' => $log->model_type,
                    'model_id' => $log->model_id,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'data_lama' => $log->data_lama,
                    'data_baru' => $log->data_baru,
                    'waktu' => $log->waktu,
                    'created_at' => $log->created_at,
                    'updated_at' => $log->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Data log aktivitas berhasil diambil',
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data log aktivitas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified log aktivitas.
     */
    public function show($id): JsonResponse
    {
        try {
            $log = LogAktivitas::with(['user:id,name,email'])->find($id);

            if (!$log) {
                return response()->json([
                    'success' => false,
                    'message' => 'Log aktivitas tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail log aktivitas berhasil diambil',
                'data' => [
                    'id' => $log->id,
                    'user' => [
                        'id' => $log->user?->id,
                        'name' => $log->user?->name,
                        'email' => $log->user?->email,
                    ],
                    'aksi' => $log->aksi,
                    'aksi_formatted' => $log->aksi_formatted,
                    'deskripsi' => $log->deskripsi,
                    'model_type' => $log->model_type,
                    'model_id' => $log->model_id,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'data_lama' => $log->data_lama,
                    'data_baru' => $log->data_baru,
                    'waktu' => $log->waktu,
                    'created_at' => $log->created_at,
                    'updated_at' => $log->updated_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail log aktivitas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created log aktivitas.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'aksi' => 'required|string|max:50',
                'deskripsi' => 'required|string',
                'model_type' => 'nullable|string',
                'model_id' => 'nullable|integer',
                'data_lama' => 'nullable|array',
                'data_baru' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();

            // Set user_id from authenticated user
            $logData = array_merge($validatedData, [
                'timestamp' => now(),
                'user_id' => Auth::id(),
            ]);

            $log = LogAktivitas::create($logData);

            return response()->json([
                'success' => true,
                'message' => 'Log aktivitas berhasil ditambahkan',
                'data' => $log
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan log aktivitas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get activity statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month'); // day, week, month, year

            $query = LogAktivitas::query();

            // Set time range based on period
            switch ($period) {
                case 'day':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->thisWeek();
                    break;
                case 'month':
                    $query->thisMonth();
                    break;
                case 'year':
                    $query->whereYear('created_at', now()->year);
                    break;
            }

            // Total activities
            $totalActivities = $query->count();

            // Activities by action
            $activitiesByAction = $query->selectRaw('aksi, COUNT(*) as total')
                ->groupBy('aksi')
                ->orderBy('total', 'desc')
                ->get();

            // Activities by user (top 10)
            $activitiesByUser = $query->with(['user:id,name'])
                ->selectRaw('user_id, COUNT(*) as total')
                ->groupBy('user_id')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'user_id' => $item->user_id,
                        'user_name' => $item->user?->name ?? 'Unknown',
                        'total' => $item->total
                    ];
                });

            // Daily activities for the period
            $dailyActivities = [];
            if ($period === 'month') {
                $dailyActivities = LogAktivitas::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
            }

            // Most active hours
            $hourlyActivities = $query->selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Statistik aktivitas berhasil diambil',
                'data' => [
                    'period' => $period,
                    'total_activities' => $totalActivities,
                    'activities_by_action' => $activitiesByAction,
                    'activities_by_user' => $activitiesByUser,
                    'daily_activities' => $dailyActivities,
                    'hourly_activities' => $hourlyActivities,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik aktivitas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's own activity log.
     */
    public function myActivities(Request $request): JsonResponse
    {
        try {
            $query = LogAktivitas::byUser(Auth::id())
                ->orderBy('created_at', 'desc');

            // Filter by action
            if ($request->has('aksi') && $request->aksi) {
                $query->byAksi($request->aksi);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->start_date) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $query->where('created_at', '>=', $startDate);
            }

            if ($request->has('end_date') && $request->end_date) {
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                $query->where('created_at', '<=', $endDate);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $logs = $query->paginate($perPage);

            // Transform data
            $logs->getCollection()->transform(function ($log) {
                return [
                    'id' => $log->id,
                    'aksi' => $log->aksi,
                    'aksi_formatted' => $log->aksi_formatted,
                    'deskripsi' => $log->deskripsi,
                    'model_type' => $log->model_type,
                    'model_id' => $log->model_id,
                    'waktu' => $log->waktu,
                    'created_at' => $log->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Data aktivitas saya berhasil diambil',
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data aktivitas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete old log activities (cleanup).
     */
    public function cleanup(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'days' => 'required|integer|min:30', // Minimal 30 hari
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $days = $request->days;
            $cutoffDate = Carbon::now()->subDays($days);
            
            $deletedCount = LogAktivitas::where('created_at', '<', $cutoffDate)->delete();

            LogAktivitas::log(
                'cleanup',
                "Membersihkan log aktivitas lebih dari {$days} hari ({$deletedCount} record dihapus)"
            );

            return response()->json([
                'success' => true,
                'message' => "Berhasil menghapus {$deletedCount} log aktivitas lama",
                'data' => [
                    'deleted_count' => $deletedCount,
                    'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membersihkan log aktivitas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export activity logs.
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'format' => 'required|in:csv,excel',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'user_id' => 'nullable|exists:users,id',
                'aksi' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = LogAktivitas::with(['user:id,name,email']);

            // Apply filters
            if ($request->user_id) {
                $query->byUser($request->user_id);
            }

            if ($request->aksi) {
                $query->byAksi($request->aksi);
            }

            if ($request->start_date) {
                $query->where('created_at', '>=', Carbon::parse($request->start_date)->startOfDay());
            }

            if ($request->end_date) {
                $query->where('created_at', '<=', Carbon::parse($request->end_date)->endOfDay());
            }

            $logs = $query->orderBy('created_at', 'desc')->get();

            // Prepare export data
            $exportData = $logs->map(function ($log) {
                return [
                    'ID' => $log->id,
                    'User' => $log->user?->name ?? 'Unknown',
                    'Email' => $log->user?->email ?? '',
                    'Aksi' => $log->aksi_formatted,
                    'Deskripsi' => $log->deskripsi,
                    'Model Type' => $log->model_type,
                    'Model ID' => $log->model_id,
                    'IP Address' => $log->ip_address,
                    'Waktu' => $log->waktu,
                ];
            });

            // Generate filename
            $filename = 'log_aktivitas_' . now()->format('Y-m-d_H-i-s');

            // Log export activity
            LogAktivitas::log(
                'export',
                "Mengekspor {$logs->count()} data log aktivitas dalam format {$request->format}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Data siap untuk diekspor',
                'data' => [
                    'filename' => $filename,
                    'format' => $request->format,
                    'total_records' => $logs->count(),
                    'export_data' => $exportData
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengekspor data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}