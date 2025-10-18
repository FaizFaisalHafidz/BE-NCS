#!/usr/bin/env python3
"""
Runner script untuk menjalankan optimasi warehouse dengan log_optimasi_id
Khusus untuk integrasi dengan API yang sudah create log_optimasi

Author: Sistem Gudang NCS
Date: 2025-10-18
"""

import sys
import os
import json
import time
import argparse
from warehouse_optimization import WarehouseOptimizer

def run_optimization_with_log(log_optimasi_id, params=None):
    """
    Menjalankan optimasi dengan log_optimasi_id yang sudah ada
    """
    print(f"🚀 Starting warehouse optimization for log_optimasi_id: {log_optimasi_id}...")
    start_time = time.time()
    
    optimizer = WarehouseOptimizer()
    
    # Set log_optimasi_id untuk save results
    optimizer.log_optimasi_id = log_optimasi_id
    
    # Set custom parameters jika diberikan
    if params:
        optimizer.temperature_initial = params.get('temp_initial', 1000.0)
        optimizer.temperature_final = params.get('temp_final', 0.1)
        optimizer.cooling_rate = params.get('cooling_rate', 0.95)
        optimizer.max_iterations = params.get('max_iterations', 1000)
        optimizer.max_no_improvement = params.get('max_no_improvement', 50)
        
        print(f"Using custom parameters:")
        print(f"  - Initial Temperature: {optimizer.temperature_initial}")
        print(f"  - Final Temperature: {optimizer.temperature_final}")
        print(f"  - Cooling Rate: {optimizer.cooling_rate}")
        print(f"  - Max Iterations: {optimizer.max_iterations}")
    
    # Jalankan optimasi
    success = optimizer.run_optimization()
    
    end_time = time.time()
    duration = end_time - start_time
    
    if success:
        print(f"✅ Optimization completed successfully in {duration:.2f} seconds")
        return True
    else:
        print(f"❌ Optimization failed after {duration:.2f} seconds")
        return False

def main():
    """Main function dengan argument parsing"""
    parser = argparse.ArgumentParser(description='Warehouse Optimization with Log ID')
    parser.add_argument('log_id', type=int, help='Log Optimasi ID from API')
    parser.add_argument('--temp_initial', type=float, default=1000.0, help='Initial temperature')
    parser.add_argument('--temp_final', type=float, default=0.1, help='Final temperature')
    parser.add_argument('--cooling_rate', type=float, default=0.95, help='Cooling rate')
    parser.add_argument('--max_iterations', type=int, default=1000, help='Max iterations')
    parser.add_argument('--max_no_improvement', type=int, default=50, help='Max no improvement')
    
    if len(sys.argv) < 2:
        parser.print_help()
        return 1
    
    args = parser.parse_args()
    
    # Prepare parameters
    params = {
        'temp_initial': args.temp_initial,
        'temp_final': args.temp_final,
        'cooling_rate': args.cooling_rate,
        'max_iterations': args.max_iterations,
        'max_no_improvement': args.max_no_improvement
    }
    
    # Run optimization
    success = run_optimization_with_log(args.log_id, params)
    return 0 if success else 1

if __name__ == "__main__":
    exit_code = main()
    sys.exit(exit_code)