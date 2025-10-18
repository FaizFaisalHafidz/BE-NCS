#!/usr/bin/env python3
"""
Utility untuk analisis dan visualisasi hasil optimasi Simulated Annealing

File ini berisi fungsi-fungsi helper untuk:
1. Analisis performa algoritma
2. Visualisasi penempatan barang
3. Perhitungan metrik evaluasi
4. Perbandingan sebelum dan sesudah optimasi

Author: Sistem Gudang NCS
Date: 2025-10-18
"""

import numpy as np
import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns
import json
from typing import List, Dict, Tuple
from warehouse_optimization import WarehouseOptimizer, PenempatanSolution, AreaGudang, Barang

class OptimizationAnalyzer:
    """
    Kelas untuk analisis hasil optimasi Simulated Annealing
    """
    
    def __init__(self, optimizer: WarehouseOptimizer):
        self.optimizer = optimizer
        self.analysis_results = {}
    
    def calculate_space_utilization(self, solution: List[PenempatanSolution]) -> Dict:
        """
        Menghitung tingkat utilisasi ruang untuk setiap area
        
        Formula:
        Utilisasi Area = (Total Volume Barang di Area) / (Kapasitas Area) × 100%
        """
        area_utilization = {}
        area_volumes = {}
        
        # Hitung total volume per area
        for placement in solution:
            area_id = placement.area_id
            barang = next((b for b in self.optimizer.barang_list if b.id == placement.barang_id), None)
            
            if area_id not in area_volumes:
                area_volumes[area_id] = 0.0
            
            if barang:
                area_volumes[area_id] += barang.volume
        
        # Hitung persentase utilisasi
        for area in self.optimizer.areas:
            volume_used = area_volumes.get(area.id, 0.0)
            utilization_percent = (volume_used / area.kapasitas) * 100 if area.kapasitas > 0 else 0
            
            area_utilization[area.id] = {
                'area_name': area.nama_area,
                'kapasitas': area.kapasitas,
                'volume_used': volume_used,
                'utilization_percent': utilization_percent,
                'available_space': area.kapasitas - volume_used
            }
        
        return area_utilization
    
    def calculate_travel_distance_metrics(self, solution: List[PenempatanSolution]) -> Dict:
        """
        Menghitung metrik jarak tempuh untuk picking
        
        Metrik yang dihitung:
        1. Total jarak dari entry point (0,0)
        2. Rata-rata jarak per item
        3. Jarak terjauh
        4. Distribusi jarak berdasarkan frekuensi akses
        """
        distances = []
        high_frequency_distances = []  # Barang sering diakses
        medium_frequency_distances = []
        low_frequency_distances = []
        
        for placement in solution:
            barang = next((b for b in self.optimizer.barang_list if b.id == placement.barang_id), None)
            if barang:
                # Hitung jarak dari entry point (0,0)
                distance = self.optimizer.calculate_distance(0, 0, placement.koordinat_x, placement.koordinat_y)
                distances.append(distance)
                
                # Kategorikan berdasarkan frekuensi akses
                if barang.frekuensi_akses >= 7:
                    high_frequency_distances.append(distance)
                elif barang.frekuensi_akses >= 4:
                    medium_frequency_distances.append(distance)
                else:
                    low_frequency_distances.append(distance)
        
        metrics = {
            'total_distance': sum(distances),
            'average_distance': np.mean(distances) if distances else 0,
            'max_distance': max(distances) if distances else 0,
            'min_distance': min(distances) if distances else 0,
            'std_distance': np.std(distances) if distances else 0,
            'high_freq_avg_distance': np.mean(high_frequency_distances) if high_frequency_distances else 0,
            'medium_freq_avg_distance': np.mean(medium_frequency_distances) if medium_frequency_distances else 0,
            'low_freq_avg_distance': np.mean(low_frequency_distances) if low_frequency_distances else 0
        }
        
        return metrics
    
    def calculate_category_clustering(self, solution: List[PenempatanSolution]) -> Dict:
        """
        Menghitung seberapa baik pengelompokan kategori barang
        
        Metrik:
        1. Jumlah area yang digunakan per kategori
        2. Koefisien clustering
        3. Dispersi geografis kategori
        """
        category_areas = {}
        category_positions = {}
        
        for placement in solution:
            barang = next((b for b in self.optimizer.barang_list if b.id == placement.barang_id), None)
            if barang:
                cat_id = barang.kategori_id
                cat_name = barang.kategori_nama
                
                if cat_id not in category_areas:
                    category_areas[cat_id] = {
                        'name': cat_name,
                        'areas': set(),
                        'positions': []
                    }
                
                category_areas[cat_id]['areas'].add(placement.area_id)
                category_areas[cat_id]['positions'].append((placement.koordinat_x, placement.koordinat_y))
        
        clustering_metrics = {}
        for cat_id, data in category_areas.items():
            positions = np.array(data['positions'])
            
            # Hitung dispersi geografis (standard deviation of positions)
            if len(positions) > 1:
                dispersi_x = np.std(positions[:, 0])
                dispersi_y = np.std(positions[:, 1])
                total_dispersi = np.sqrt(dispersi_x**2 + dispersi_y**2)
            else:
                total_dispersi = 0
            
            clustering_metrics[cat_id] = {
                'category_name': data['name'],
                'num_areas_used': len(data['areas']),
                'num_items': len(positions),
                'geographic_dispersion': total_dispersi,
                'clustering_score': 1 / (1 + total_dispersi) if total_dispersi > 0 else 1.0
            }
        
        return clustering_metrics
    
    def generate_performance_report(self, solution: List[PenempatanSolution], 
                                  optimization_cost: float) -> Dict:
        """
        Menghasilkan laporan performa lengkap
        """
        print("Generating performance analysis report...")
        
        report = {
            'optimization_summary': {
                'algorithm': 'Simulated Annealing',
                'total_items': len(solution),
                'total_areas': len(self.optimizer.areas),
                'objective_function_value': optimization_cost
            },
            'space_utilization': self.calculate_space_utilization(solution),
            'travel_metrics': self.calculate_travel_distance_metrics(solution),
            'category_clustering': self.calculate_category_clustering(solution)
        }
        
        # Hitung summary statistics
        utilizations = [data['utilization_percent'] for data in report['space_utilization'].values()]
        
        report['summary_statistics'] = {
            'average_space_utilization': np.mean(utilizations),
            'max_space_utilization': np.max(utilizations),
            'min_space_utilization': np.min(utilizations),
            'areas_over_80_percent': sum(1 for u in utilizations if u > 80),
            'areas_under_30_percent': sum(1 for u in utilizations if u < 30),
            'total_categories': len(report['category_clustering']),
            'well_clustered_categories': sum(1 for data in report['category_clustering'].values() 
                                           if data['num_areas_used'] <= 2)
        }
        
        return report
    
    def save_analysis_report(self, report: Dict, filename: str = "optimization_analysis.json"):
        """
        Menyimpan laporan analisis ke file JSON
        """
        try:
            with open(filename, 'w') as f:
                json.dump(report, f, indent=2, default=str)
            print(f"Analysis report saved to {filename}")
            return True
        except Exception as e:
            print(f"Error saving analysis report: {e}")
            return False
    
    def print_summary_report(self, report: Dict):
        """
        Mencetak ringkasan laporan ke console
        """
        print("\n" + "="*80)
        print("           WAREHOUSE OPTIMIZATION ANALYSIS REPORT")
        print("="*80)
        
        # Summary
        summary = report['summary_statistics']
        print(f"\n📊 OVERALL PERFORMANCE:")
        print(f"   • Average Space Utilization: {summary['average_space_utilization']:.1f}%")
        print(f"   • Areas over 80% utilized: {summary['areas_over_80_percent']}")
        print(f"   • Areas under 30% utilized: {summary['areas_under_30_percent']}")
        print(f"   • Well-clustered categories: {summary['well_clustered_categories']}/{summary['total_categories']}")
        
        # Travel metrics
        travel = report['travel_metrics']
        print(f"\n🚚 TRAVEL EFFICIENCY:")
        print(f"   • Average travel distance: {travel['average_distance']:.2f} meters")
        print(f"   • High-frequency items avg distance: {travel['high_freq_avg_distance']:.2f} meters")
        print(f"   • Maximum travel distance: {travel['max_distance']:.2f} meters")
        
        # Space utilization details
        print(f"\n📦 SPACE UTILIZATION BY AREA:")
        util_data = report['space_utilization']
        for area_id, data in sorted(util_data.items(), 
                                   key=lambda x: x[1]['utilization_percent'], 
                                   reverse=True)[:5]:  # Top 5 most utilized
            print(f"   • {data['area_name']}: {data['utilization_percent']:.1f}% "
                  f"({data['volume_used']:.1f}/{data['kapasitas']:.1f} m³)")
        
        # Category clustering
        print(f"\n📂 CATEGORY CLUSTERING:")
        cluster_data = report['category_clustering']
        for cat_id, data in sorted(cluster_data.items(), 
                                  key=lambda x: x[1]['clustering_score'], 
                                  reverse=True)[:5]:  # Top 5 best clustered
            print(f"   • {data['category_name']}: Score {data['clustering_score']:.3f} "
                  f"({data['num_items']} items in {data['num_areas_used']} areas)")
        
        print("\n" + "="*80)

def analyze_optimization_results(solution_file: str = "optimization_result.json"):
    """
    Fungsi utama untuk menganalisis hasil optimasi dari file JSON
    """
    try:
        # Load hasil optimasi
        with open(solution_file, 'r') as f:
            optimization_data = json.load(f)
        
        print(f"Loaded optimization results from {solution_file}")
        print(f"Algorithm used: {optimization_data.get('algorithm', 'Unknown')}")
        print(f"Total recommendations: {optimization_data.get('total_items', 0)}")
        
        # Inisialisasi optimizer untuk akses ke data
        optimizer = WarehouseOptimizer()
        if not optimizer.load_token_from_file():
            print("Warning: Could not load token, analysis might be limited")
            return False
        
        # Load data dari API
        optimizer.fetch_areas()
        optimizer.fetch_barang()
        
        # Convert recommendations to PenempatanSolution objects
        solution = []
        for rec in optimization_data.get('recommendations', []):
            placement = PenempatanSolution(
                barang_id=rec['barang_id'],
                area_id=rec['area_gudang_id'],
                koordinat_x=rec['koordinat_x'],
                koordinat_y=rec['koordinat_y']
            )
            solution.append(placement)
        
        # Jalankan analisis
        analyzer = OptimizationAnalyzer(optimizer)
        
        # Hitung objective function untuk solusi ini
        optimization_cost = optimizer.calculate_objective_function(solution)
        
        # Generate full report
        report = analyzer.generate_performance_report(solution, optimization_cost)
        
        # Print summary
        analyzer.print_summary_report(report)
        
        # Save detailed analysis
        analyzer.save_analysis_report(report)
        
        return True
        
    except Exception as e:
        print(f"Error analyzing optimization results: {e}")
        return False

if __name__ == "__main__":
    # Jalankan analisis hasil optimasi
    analyze_optimization_results()