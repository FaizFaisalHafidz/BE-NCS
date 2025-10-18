#!/usr/bin/env python3
"""
Runner script untuk menjalankan optimasi warehouse dengan mudah
Includes parameter tuning and batch optimization capabilities

Author: Sistem Gudang NCS
Date: 2025-10-18
"""

import sys
import os
import json
import time
from warehouse_optimization import WarehouseOptimizer

def run_single_optimization(params=None):
    """
    Menjalankan satu kali optimasi dengan parameter tertentu
    """
    print("🚀 Starting warehouse optimization...")
    start_time = time.time()
    
    optimizer = WarehouseOptimizer()
    
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

def run_parameter_tuning():
    """
    Menjalankan tuning parameter untuk menemukan kombinasi terbaik
    
    Test berbagai kombinasi parameter Simulated Annealing:
    - Temperature initial: [500, 1000, 2000]
    - Cooling rate: [0.90, 0.95, 0.99]
    - Max iterations: [500, 1000, 2000]
    """
    print("🔧 Starting parameter tuning...")
    
    # Parameter combinations untuk testing
    param_combinations = [
        {'temp_initial': 500, 'cooling_rate': 0.95, 'max_iterations': 500, 'name': 'Fast'},
        {'temp_initial': 1000, 'cooling_rate': 0.95, 'max_iterations': 1000, 'name': 'Balanced'},
        {'temp_initial': 2000, 'cooling_rate': 0.99, 'max_iterations': 2000, 'name': 'Thorough'},
        {'temp_initial': 1500, 'cooling_rate': 0.90, 'max_iterations': 1500, 'name': 'Aggressive'},
    ]
    
    results = []
    
    for i, params in enumerate(param_combinations, 1):
        print(f"\n📊 Running configuration {i}/4: {params['name']}")
        print("-" * 50)
        
        start_time = time.time()
        
        optimizer = WarehouseOptimizer()
        optimizer.temperature_initial = params['temp_initial']
        optimizer.cooling_rate = params['cooling_rate']
        optimizer.max_iterations = params['max_iterations']
        
        # Load data
        if not optimizer.load_token_from_file():
            print("Failed to load token")
            continue
            
        if not optimizer.fetch_areas() or not optimizer.fetch_barang():
            print("Failed to load data")
            continue
        
        # Run optimization
        try:
            best_solution, best_cost = optimizer.simulated_annealing()
            duration = time.time() - start_time
            
            result = {
                'config_name': params['name'],
                'parameters': params,
                'best_cost': best_cost,
                'duration': duration,
                'solution_count': len(best_solution)
            }
            results.append(result)
            
            print(f"✅ {params['name']} completed: Cost = {best_cost:.2f}, Time = {duration:.2f}s")
            
        except Exception as e:
            print(f"❌ {params['name']} failed: {e}")
        finally:
            optimizer.disconnect_database()
    
    # Analisis hasil tuning
    if results:
        print("\n" + "="*70)
        print("             PARAMETER TUNING RESULTS")
        print("="*70)
        
        # Sort by best cost
        results.sort(key=lambda x: x['best_cost'])
        
        print(f"{'Config':<12} {'Cost':<12} {'Time(s)':<10} {'Temp':<8} {'Cool':<6} {'Iter':<6}")
        print("-" * 70)
        
        for result in results:
            params = result['parameters']
            print(f"{result['config_name']:<12} "
                  f"{result['best_cost']:<12.2f} "
                  f"{result['duration']:<10.2f} "
                  f"{params['temp_initial']:<8} "
                  f"{params['cooling_rate']:<6} "
                  f"{params['max_iterations']:<6}")
        
        # Save results
        with open('parameter_tuning_results.json', 'w') as f:
            json.dump(results, f, indent=2)
        
        print(f"\n🏆 Best configuration: {results[0]['config_name']} with cost {results[0]['best_cost']:.2f}")
        print("📄 Detailed results saved to parameter_tuning_results.json")
        
        return results[0]['parameters']  # Return best parameters
    
    return None

def run_batch_optimization(num_runs=5):
    """
    Menjalankan optimasi beberapa kali untuk analisis konsistensi
    """
    print(f"📈 Running batch optimization ({num_runs} runs)...")
    
    results = []
    
    for run in range(1, num_runs + 1):
        print(f"\n🔄 Run {run}/{num_runs}")
        print("-" * 30)
        
        start_time = time.time()
        
        optimizer = WarehouseOptimizer()
        
        # Connect ke database
        if not optimizer.connect_database():
            print("Failed to connect to database")
            continue
            
        if not optimizer.fetch_areas() or not optimizer.fetch_barang():
            print("Failed to load data from database")
            optimizer.disconnect_database()
            continue
        
        try:
            best_solution, best_cost = optimizer.simulated_annealing()
            duration = time.time() - start_time
            
            result = {
                'run': run,
                'best_cost': best_cost,
                'duration': duration,
                'solution_count': len(best_solution)
            }
            results.append(result)
            
            print(f"✅ Run {run} completed: Cost = {best_cost:.2f}")
            
        except Exception as e:
            print(f"❌ Run {run} failed: {e}")
            continue
    
    # Analisis hasil batch
    if results:
        costs = [r['best_cost'] for r in results]
        durations = [r['duration'] for r in results]
        
        print("\n" + "="*50)
        print("         BATCH OPTIMIZATION RESULTS")
        print("="*50)
        print(f"Number of successful runs: {len(results)}")
        print(f"Best cost: {min(costs):.2f}")
        print(f"Worst cost: {max(costs):.2f}")
        print(f"Average cost: {sum(costs)/len(costs):.2f}")
        print(f"Cost standard deviation: {(sum((x - sum(costs)/len(costs))**2 for x in costs) / len(costs))**0.5:.2f}")
        print(f"Average duration: {sum(durations)/len(durations):.2f} seconds")
        
        # Save results
        with open('batch_optimization_results.json', 'w') as f:
            json.dump(results, f, indent=2)
        
        print("📄 Detailed results saved to batch_optimization_results.json")
        
        return results
    
    return None

def main():
    """
    Main function dengan command line options
    """
    if len(sys.argv) < 2:
        print("🏭 Warehouse Optimization Tool")
        print("Usage:")
        print("  python run_optimization.py single     - Run single optimization")
        print("  python run_optimization.py tune       - Run parameter tuning")
        print("  python run_optimization.py batch [n]  - Run batch optimization (default n=5)")
        print("  python run_optimization.py analyze    - Analyze existing results")
        return 1
    
    command = sys.argv[1].lower()
    
    if command == 'single':
        return 0 if run_single_optimization() else 1
        
    elif command == 'tune':
        best_params = run_parameter_tuning()
        if best_params:
            print("\n🎯 Running final optimization with best parameters...")
            return 0 if run_single_optimization(best_params) else 1
        return 1
        
    elif command == 'batch':
        num_runs = 5
        if len(sys.argv) > 2:
            try:
                num_runs = int(sys.argv[2])
            except ValueError:
                print("Invalid number of runs specified")
                return 1
        
        results = run_batch_optimization(num_runs)
        return 0 if results else 1
        
    elif command == 'analyze':
        # Import dan jalankan analyzer
        try:
            from optimization_analyzer import analyze_optimization_results
            return 0 if analyze_optimization_results() else 1
        except ImportError as e:
            print(f"Error importing analyzer: {e}")
            print("Make sure optimization_analyzer.py is available")
            return 1
            
    else:
        print(f"Unknown command: {command}")
        return 1

if __name__ == "__main__":
    exit_code = main()
    sys.exit(exit_code)