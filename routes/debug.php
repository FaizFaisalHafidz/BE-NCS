<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug/python-paths', function () {
    return response()->json([
        'PYTHON_VENV_PATH' => env('PYTHON_VENV_PATH'),
        'PYTHON_SCRIPT_PATH' => env('PYTHON_SCRIPT_PATH'),
        'base_path_venv' => base_path('script/venv/bin/python'),
        'base_path_script' => base_path('script/warehouse_optimization.py'),
        'file_exists_venv' => file_exists(env('PYTHON_VENV_PATH', base_path('script/venv/bin/python'))),
        'file_exists_script' => file_exists(env('PYTHON_SCRIPT_PATH', base_path('script/warehouse_optimization.py'))),
        'env_all' => $_ENV,
    ]);
});