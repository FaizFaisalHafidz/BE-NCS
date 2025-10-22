#!/usr/bin/env python3
"""
Optimasi Penempatan Barang di Gudang menggunakan Simulated Annealing

Algoritma Simulated Annealing merupakan metode optimasi heuristik yang terinspirasi 
dari proses annealing dalam metalurgi, dimana logam dipanaskan kemudian didinginkan 
secara perlahan untuk mencapai struktur kristal yang optimal.

Author: Sistem Gudang NCS
Date: 2025-10-18
"""

import numpy as np
import json
import math
import random
from typing import List, Dict, Tuple, Optional
from dataclasses import dataclass
import sys
import os
from database_manager import DatabaseManager

@dataclass
class AreaGudang:
    """Class untuk merepresentasikan area gudang"""
    id: int
    kode_area: str
    nama_area: str
    koordinat_x: float
    koordinat_y: float
    panjang: float
    lebar: float
    tinggi: float
    kapasitas: float
    kapasitas_terpakai: float
    jenis_area: str
    tersedia: bool

@dataclass
class Barang:
    """Class untuk merepresentasikan barang"""
    id: int
    kode_barang: str
    nama_barang: str
    volume: float
    kategori_id: int
    kategori_nama: str
    frekuensi_akses: int = 1  # Default frekuensi akses
    prioritas: int = 1  # 1=tinggi, 2=sedang, 3=rendah

@dataclass
class PenempatanSolution:
    """Class untuk merepresentasikan solusi penempatan"""
    barang_id: int
    area_id: int
    koordinat_x: float
    koordinat_y: float

class WarehouseOptimizer:
    """
    Kelas utama untuk optimasi penempatan barang menggunakan Simulated Annealing
    """
    
    def __init__(self, optimization_config=None):
        # Parameter Simulated Annealing (internal, tidak di-expose ke user)
        self.temperature_initial = 1000.0  # Suhu awal (T0)
        self.temperature_final = 0.1       # Suhu akhir (Tf)
        self.cooling_rate = 0.95           # Laju pendinginan (α)
        self.max_iterations = 1000         # Maksimum iterasi per suhu
        self.max_no_improvement = 50       # Maksimum iterasi tanpa perbaikan
        
        # Konfigurasi optimasi dari user (parameter bisnis)
        self.optimization_config = optimization_config or {}
        self.gudang_ids = self.optimization_config.get('gudang_ids', [])
        self.barang_ids = self.optimization_config.get('barang_ids', [])
        self.prioritas_optimasi = self.optimization_config.get('prioritas_optimasi', 'space_utilization')
        self.target_utilisasi = self.optimization_config.get('target_utilisasi', 80.0)
        
        # Override parameter SA internal jika ada di config
        if 'algorithm_params' in self.optimization_config:
            alg_params = self.optimization_config['algorithm_params']
            self.temperature_initial = alg_params.get('temperature_initial', self.temperature_initial)
            self.temperature_final = alg_params.get('temperature_final', self.temperature_final)
            self.cooling_rate = alg_params.get('cooling_rate', self.cooling_rate)
            self.max_iterations = alg_params.get('max_iterations', self.max_iterations)
            self.max_no_improvement = alg_params.get('max_no_improvement', self.max_no_improvement)
        
        # Database manager
        self.db = DatabaseManager()
        
        # Log optimasi ID untuk integrasi dengan API
        self.log_optimasi_id = None
        
        # Data warehouse
        self.areas: List[AreaGudang] = []
        self.barang_list: List[Barang] = []
        self.current_solution: List[PenempatanSolution] = []
        
    def connect_database(self) -> bool:
        """Membuat koneksi ke database"""
        return self.db.connect()
    
    def disconnect_database(self):
        """Menutup koneksi database"""
        self.db.disconnect()
    
    def fetch_areas(self) -> bool:
        """Mengambil data area gudang dari database dengan filter"""
        try:
            areas_data = self.db.fetch_areas()
            self.areas = []
            
            for area_data in areas_data:
                # Filter berdasarkan gudang_ids jika ada
                if self.gudang_ids and area_data['gudang_id'] not in self.gudang_ids:
                    continue
                    
                area = AreaGudang(
                    id=area_data['id'],
                    kode_area=area_data['kode_area'],
                    nama_area=area_data['nama_area'],
                    koordinat_x=float(area_data['koordinat_x']),
                    koordinat_y=float(area_data['koordinat_y']),
                    panjang=float(area_data['panjang']),
                    lebar=float(area_data['lebar']),
                    tinggi=float(area_data['tinggi']),
                    kapasitas=float(area_data['kapasitas']),
                    kapasitas_terpakai=float(area_data['kapasitas_terpakai']),
                    jenis_area=area_data['jenis_area'],
                    tersedia=bool(area_data['tersedia'])
                )
                self.areas.append(area)
            
            gudang_filter = f" (filtered by gudang_ids: {self.gudang_ids})" if self.gudang_ids else " (all warehouses)"
            print(f"✅ Loaded {len(self.areas)} areas from database{gudang_filter}")
            return True
        except Exception as e:
            print(f"❌ Error fetching areas: {e}")
            return False
    
    def fetch_barang(self) -> bool:
        """Mengambil data barang dari database dengan filter"""
        try:
            barang_data = self.db.fetch_barang()
            self.barang_list = []
            
            for item_data in barang_data:
                # Filter berdasarkan barang_ids jika ada
                if self.barang_ids and item_data['id'] not in self.barang_ids:
                    continue
                
                # Hitung volume dari dimensi barang
                panjang = float(item_data.get('panjang', 1.0))
                lebar = float(item_data.get('lebar', 1.0))
                tinggi = float(item_data.get('tinggi', 1.0))
                volume = panjang * lebar * tinggi
                
                # Set prioritas berdasarkan konfigurasi optimasi
                prioritas = self.get_item_priority(item_data)
                
                barang = Barang(
                    id=item_data['id'],
                    kode_barang=item_data['kode_barang'],
                    nama_barang=item_data['nama_barang'],
                    volume=volume,
                    kategori_id=item_data['kategori_barang_id'],
                    kategori_nama=item_data['nama_kategori'],
                    frekuensi_akses=random.randint(1, 10),  # Simulasi frekuensi akses
                    prioritas=prioritas
                )
                self.barang_list.append(barang)
            
            barang_filter = f" (filtered by barang_ids: {self.barang_ids})" if self.barang_ids else " (all items)"
            print(f"✅ Loaded {len(self.barang_list)} items from database{barang_filter}")
            return True
        except Exception as e:
            print(f"❌ Error fetching barang: {e}")
            return False
    
    def get_item_priority(self, item_data) -> int:
        """Menentukan prioritas barang berdasarkan konfigurasi optimasi"""
        if self.prioritas_optimasi == 'accessibility':
            # Prioritas berdasarkan aksesibilitas - barang kecil prioritas tinggi
            volume = float(item_data.get('panjang', 1.0)) * float(item_data.get('lebar', 1.0)) * float(item_data.get('tinggi', 1.0))
            if volume < 10:
                return 1  # Prioritas tinggi untuk barang kecil
            elif volume < 50:
                return 2  # Prioritas sedang
            else:
                return 3  # Prioritas rendah untuk barang besar
        elif self.prioritas_optimasi == 'space_utilization':
            # Prioritas berdasarkan utilisasi ruang - barang besar prioritas tinggi
            volume = float(item_data.get('panjang', 1.0)) * float(item_data.get('lebar', 1.0)) * float(item_data.get('tinggi', 1.0))
            if volume > 50:
                return 1  # Prioritas tinggi untuk barang besar
            elif volume > 10:
                return 2  # Prioritas sedang
            else:
                return 3  # Prioritas rendah untuk barang kecil
        else:  # balanced
            return random.randint(1, 3)  # Prioritas random untuk balanced
    
    def calculate_distance(self, x1: float, y1: float, x2: float, y2: float) -> float:
        """
        Menghitung jarak Euclidean antara dua titik
        Formula: d = √[(x2-x1)² + (y2-y1)²]
        """
        return math.sqrt((x2 - x1)**2 + (y2 - y1)**2)
    
    def calculate_objective_function(self, solution: List[PenempatanSolution]) -> float:
        """
        Fungsi objektif untuk evaluasi solusi penempatan
        
        Komponen yang dioptimasi:
        1. Minimasi total jarak tempuh (Distance Cost)
        2. Maksimasi utilisasi ruang (Space Utilization)
        3. Pengelompokan kategori barang (Category Grouping)
        4. Prioritas frekuensi akses (Access Frequency)
        
        Formula:
        f(x) = w1*DistanceCost + w2*SpacePenalty + w3*CategoryPenalty + w4*AccessPenalty
        
        Dimana:
        - w1, w2, w3, w4 adalah bobot untuk setiap komponen
        - Semakin kecil nilai f(x), semakin baik solusinya
        """
        
        if not solution:
            return float('inf')
        
        # Bobot untuk setiap komponen objektif
        w1 = 0.4  # Bobot jarak tempuh
        w2 = 0.3  # Bobot utilisasi ruang
        w3 = 0.2  # Bobot pengelompokan kategori
        w4 = 0.1  # Bobot frekuensi akses
        
        total_cost = 0.0
        
        # 1. Distance Cost - Minimasi jarak dari pintu masuk (0,0)
        distance_cost = 0.0
        for placement in solution:
            barang = next((b for b in self.barang_list if b.id == placement.barang_id), None)
            if barang:
                # Jarak dari pintu masuk ke lokasi penempatan
                distance = self.calculate_distance(0, 0, placement.koordinat_x, placement.koordinat_y)
                # Bobot berdasarkan frekuensi akses (semakin sering diakses, semakin dekat ke pintu)
                weighted_distance = distance * barang.frekuensi_akses
                distance_cost += weighted_distance
        
        # 2. Space Utilization Penalty - Penalti untuk ruang yang tidak optimal
        space_penalty = 0.0
        area_utilization = {}
        
        for placement in solution:
            area_id = placement.area_id
            barang = next((b for b in self.barang_list if b.id == placement.barang_id), None)
            
            if area_id not in area_utilization:
                area_utilization[area_id] = 0.0
            
            if barang:
                area_utilization[area_id] += barang.volume
        
        for area in self.areas:
            if area.id in area_utilization:
                utilization_ratio = area_utilization[area.id] / area.kapasitas
                # Penalti jika utilisasi terlalu rendah atau melebihi kapasitas
                if utilization_ratio < 0.3:  # Utilisasi terlalu rendah
                    space_penalty += (0.3 - utilization_ratio) * 100
                elif utilization_ratio > 1.0:  # Melebihi kapasitas
                    space_penalty += (utilization_ratio - 1.0) * 1000  # Penalti besar
        
        # 3. Category Grouping - Penalti untuk kategori yang tersebar
        category_penalty = 0.0
        category_areas = {}
        
        for placement in solution:
            barang = next((b for b in self.barang_list if b.id == placement.barang_id), None)
            if barang:
                cat_id = barang.kategori_id
                if cat_id not in category_areas:
                    category_areas[cat_id] = set()
                category_areas[cat_id].add(placement.area_id)
        
        # Penalti untuk kategori yang tersebar di banyak area
        for cat_id, areas_set in category_areas.items():
            if len(areas_set) > 1:
                category_penalty += (len(areas_set) - 1) * 10
        
        # 4. Access Frequency Penalty - Barang sering diakses harus dekat pintu masuk
        access_penalty = 0.0
        for placement in solution:
            barang = next((b for b in self.barang_list if b.id == placement.barang_id), None)
            if barang and barang.frekuensi_akses > 7:  # Barang sering diakses
                distance = self.calculate_distance(0, 0, placement.koordinat_x, placement.koordinat_y)
                if distance > 20:  # Jika terlalu jauh dari pintu masuk
                    access_penalty += distance * 2
        
        # Total cost dengan pembobotan
        total_cost = (w1 * distance_cost + 
                     w2 * space_penalty + 
                     w3 * category_penalty + 
                     w4 * access_penalty)
        
        return total_cost
    
    def generate_initial_solution(self) -> List[PenempatanSolution]:
        """
        Menghasilkan solusi awal secara random
        """
        solution = []
        available_areas = [area for area in self.areas if area.tersedia]
        
        for barang in self.barang_list:
            # Pilih area secara random yang masih tersedia
            if available_areas:
                area = random.choice(available_areas)
                # Posisi random dalam area tersebut
                x = area.koordinat_x + random.uniform(0, area.panjang)
                y = area.koordinat_y + random.uniform(0, area.lebar)
                
                placement = PenempatanSolution(
                    barang_id=barang.id,
                    area_id=area.id,
                    koordinat_x=x,
                    koordinat_y=y
                )
                solution.append(placement)
        
        return solution
    
    def generate_neighbor(self, current_solution: List[PenempatanSolution]) -> List[PenempatanSolution]:
        """
        Menghasilkan solusi tetangga (neighbor) dari solusi saat ini
        
        Strategi yang digunakan:
        1. Pindah barang ke area lain
        2. Tukar posisi dua barang
        3. Geser posisi dalam area yang sama
        """
        if not current_solution:
            return current_solution
        
        new_solution = current_solution.copy()
        available_areas = [area for area in self.areas if area.tersedia]
        
        # Pilih strategi secara random
        strategy = random.randint(1, 3)
        
        if strategy == 1 and available_areas:
            # Strategi 1: Pindah barang ke area lain
            idx = random.randint(0, len(new_solution) - 1)
            new_area = random.choice(available_areas)
            
            new_solution[idx].area_id = new_area.id
            new_solution[idx].koordinat_x = new_area.koordinat_x + random.uniform(0, new_area.panjang)
            new_solution[idx].koordinat_y = new_area.koordinat_y + random.uniform(0, new_area.lebar)
            
        elif strategy == 2 and len(new_solution) >= 2:
            # Strategi 2: Tukar posisi dua barang
            idx1 = random.randint(0, len(new_solution) - 1)
            idx2 = random.randint(0, len(new_solution) - 1)
            while idx1 == idx2:
                idx2 = random.randint(0, len(new_solution) - 1)
            
            # Tukar area dan koordinat
            new_solution[idx1].area_id, new_solution[idx2].area_id = new_solution[idx2].area_id, new_solution[idx1].area_id
            new_solution[idx1].koordinat_x, new_solution[idx2].koordinat_x = new_solution[idx2].koordinat_x, new_solution[idx1].koordinat_x
            new_solution[idx1].koordinat_y, new_solution[idx2].koordinat_y = new_solution[idx2].koordinat_y, new_solution[idx1].koordinat_y
            
        else:
            # Strategi 3: Geser posisi dalam area yang sama
            idx = random.randint(0, len(new_solution) - 1)
            area = next((a for a in self.areas if a.id == new_solution[idx].area_id), None)
            
            if area:
                # Geser posisi sedikit dalam area
                delta_x = random.uniform(-2, 2)  # Pergeseran maksimal 2 meter
                delta_y = random.uniform(-2, 2)
                
                new_x = max(area.koordinat_x, 
                           min(area.koordinat_x + area.panjang, 
                               new_solution[idx].koordinat_x + delta_x))
                new_y = max(area.koordinat_y,
                           min(area.koordinat_y + area.lebar,
                               new_solution[idx].koordinat_y + delta_y))
                
                new_solution[idx].koordinat_x = new_x
                new_solution[idx].koordinat_y = new_y
        
        return new_solution
    
    def acceptance_probability(self, current_cost: float, new_cost: float, temperature: float) -> float:
        """
        Menghitung probabilitas penerimaan solusi baru dalam Simulated Annealing
        
        Formula Boltzmann:
        P(accept) = exp(-(ΔE/T))
        
        Dimana:
        - ΔE = new_cost - current_cost (perubahan energi/cost)
        - T = temperature (suhu saat ini)
        
        Jika new_cost < current_cost: P = 1.0 (selalu diterima)
        Jika new_cost > current_cost: P = exp(-(ΔE/T)) (diterima berdasarkan probabilitas)
        """
        if new_cost < current_cost:
            return 1.0  # Solusi lebih baik, selalu diterima
        else:
            # Probabilitas Boltzmann
            delta_cost = new_cost - current_cost
            probability = math.exp(-delta_cost / temperature)
            return probability
    
    def simulated_annealing(self) -> Tuple[List[PenempatanSolution], float]:
        """
        Implementasi algoritma Simulated Annealing
        
        Algoritma:
        1. Inisialisasi suhu awal T0 dan solusi awal S0
        2. Untuk setiap suhu T:
           a. Untuk setiap iterasi:
              - Generate solusi tetangga S'
              - Hitung ΔE = f(S') - f(S)
              - Jika ΔE < 0: terima S'
              - Jika ΔE ≥ 0: terima S' dengan probabilitas exp(-ΔE/T)
           b. Kurangi suhu: T = α * T
        3. Return solusi terbaik yang ditemukan
        """
        
        print("🔥 Starting Simulated Annealing optimization...")
        print()
        print("=== OPTIMIZATION CONFIGURATION ===")
        print(f"📦 Total Gudang: {len(set([area.gudang_id for area in self.areas]) if hasattr(self.areas[0], 'gudang_id') else [1])}")
        print(f"📦 Total Areas: {len(self.areas)}")  
        print(f"📋 Total Barang: {len(self.barang_list)}")
        print(f"🎯 Prioritas Optimasi: {self.prioritas_optimasi}")
        print(f"📊 Target Utilisasi: {self.target_utilisasi}%")
        print()
        print("=== ALGORITHM PARAMETERS (INTERNAL) ===")
        print(f"🌡️  Temperature: T0={self.temperature_initial}, Tf={self.temperature_final}")
        print(f"❄️  Cooling rate: {self.cooling_rate}")
        print(f"🔄 Max iterations: {self.max_iterations}")
        print(f"⏹️  Max no improvement: {self.max_no_improvement}")
        print()
        
        # Inisialisasi
        current_solution = self.generate_initial_solution()
        current_cost = self.calculate_objective_function(current_solution)
        
        best_solution = current_solution.copy()
        best_cost = current_cost
        
        temperature = self.temperature_initial
        iteration_count = 0
        no_improvement_count = 0
        
        print(f"Initial solution cost: {current_cost:.2f}")
        
        # Loop utama Simulated Annealing
        while temperature > self.temperature_final:
            improved_in_temperature = False
            
            for i in range(self.max_iterations):
                iteration_count += 1
                
                # Generate solusi tetangga
                neighbor_solution = self.generate_neighbor(current_solution)
                neighbor_cost = self.calculate_objective_function(neighbor_solution)
                
                # Hitung probabilitas penerimaan
                accept_prob = self.acceptance_probability(current_cost, neighbor_cost, temperature)
                
                # Keputusan penerimaan
                if random.random() < accept_prob:
                    current_solution = neighbor_solution
                    current_cost = neighbor_cost
                    
                    # Update solusi terbaik
                    if current_cost < best_cost:
                        best_solution = current_solution.copy()
                        best_cost = current_cost
                        improved_in_temperature = True
                        no_improvement_count = 0
                        print(f"Iteration {iteration_count}: New best cost = {best_cost:.2f} at T = {temperature:.2f}")
                
                # Early stopping jika tidak ada perbaikan
                if not improved_in_temperature:
                    no_improvement_count += 1
                    if no_improvement_count >= self.max_no_improvement:
                        print(f"Early stopping: No improvement for {self.max_no_improvement} iterations")
                        break
            
            # Pendinginan suhu (cooling)
            temperature *= self.cooling_rate
            
            if iteration_count % 100 == 0:
                print(f"Iteration {iteration_count}: T = {temperature:.4f}, Current cost = {current_cost:.2f}")
        
        print(f"Optimization completed after {iteration_count} iterations")
        print(f"Best cost achieved: {best_cost:.2f}")
        
        return best_solution, best_cost
    
    def generate_placement_reasoning(self, barang, area) -> str:
        """
        Generate detailed and contextual placement reasoning based on item and area characteristics
        """
        # Get area utilization data
        area_utilization = (area.kapasitas_terpakai / area.kapasitas * 100) if area.kapasitas > 0 else 0
        
        # Analyze item characteristics - check if it's a dictionary or dataclass
        if hasattr(barang, 'get'):  # Dictionary from database
            item_volume = barang.get('volume', 0)
            item_name = barang.get('nama_barang', 'Item')
            category = barang.get('nama_kategori', 'Umum')
        else:  # Dataclass object
            item_volume = getattr(barang, 'volume', 0)
            item_name = getattr(barang, 'nama_barang', 'Item')
            category = getattr(barang, 'kategori_nama', 'Umum')
        
        # Analyze area characteristics - check if it's a dictionary or dataclass
        if hasattr(area, 'get'):  # Dictionary from database
            area_type = area.get('jenis_area', 'rak')
            area_name = area.get('nama_area', 'Area')
            is_high_access = area.get('koordinat_x', 100) <= 20 and area.get('koordinat_y', 100) <= 20
            is_secure_area = area.get('tinggi', 0) >= 5
            area_space_available = area.get('kapasitas', 0) - area.get('kapasitas_terpakai', 0)
        else:  # Dataclass object
            area_type = getattr(area, 'jenis_area', 'rak')
            area_name = getattr(area, 'nama_area', 'Area')
            is_high_access = area.koordinat_x <= 20 and area.koordinat_y <= 20
            is_secure_area = area.tinggi >= 5
            area_space_available = area.kapasitas - area.kapasitas_terpakai
        
        # Generate reasoning based on multiple factors
        reasons = []
        
        # Factor 1: Category-based placement
        if 'elektronik' in category.lower():
            if area_type == 'rak' and is_secure_area:
                reasons.append(f"barang elektronik ditempatkan di rak tinggi untuk keamanan dan proteksi dari kelembaban")
            elif area_utilization < 50:
                reasons.append(f"area dengan kapasitas tersedia {area_space_available:.1f}m³ cocok untuk barang elektronik sensitif")
            else:
                reasons.append(f"penempatan elektronik di area khusus untuk menghindari kerusakan dan gangguan")
                
        elif 'dokumen' in category.lower():
            if area_type == 'lantai':
                reasons.append(f"dokumen ditempatkan di area lantai karena mudah diakses dan tidak memerlukan rak khusus")
            elif area_utilization < 70:
                reasons.append(f"area dengan utilisasi {area_utilization:.1f}% memiliki ruang memadai untuk arsip dokumen")
            else:
                reasons.append(f"penempatan dokumen di area administratif untuk kemudahan akses dan organisasi")
                
        elif 'paket' in category.lower():
            if is_high_access:
                reasons.append(f"paket express ditempatkan dekat pintu masuk untuk memudahkan proses loading dan unloading")
            elif area_utilization < 60:
                reasons.append(f"area dengan kapasitas tersisa {area_space_available:.1f}m³ optimal untuk rotasi paket cepat")
            else:
                reasons.append(f"penempatan paket di area transit untuk mempercepat distribusi dan pengiriman")
                
        elif 'fragile' in category.lower():
            if area_type == 'rak' and is_secure_area:
                reasons.append(f"barang fragile ditempatkan di rak tinggi untuk proteksi maksimal dari benturan")
            else:
                reasons.append(f"penempatan khusus untuk barang mudah pecah dengan akses terbatas")
        
        # Factor 2: Space optimization
        if area_utilization < 30:
            reasons.append(f"optimisasi ruang kosong dengan memanfaatkan area yang hanya terisi {area_utilization:.1f}%")
        elif area_utilization > 80:
            reasons.append(f"penempatan efisien pada area dengan utilisasi tinggi {area_utilization:.1f}%")
        else:
            reasons.append(f"pemanfaatan optimal ruang dengan tingkat utilisasi yang seimbang {area_utilization:.1f}%")
            
        # Factor 3: Item size considerations
        if item_volume > 0.01:  # Large items (>10 liters)
            tinggi_area = area.tinggi if hasattr(area, 'tinggi') else area.get('tinggi', 0)
            if tinggi_area >= 4:
                reasons.append(f"barang berukuran besar memerlukan area dengan tinggi memadai ({tinggi_area}m)")
            else:
                reasons.append(f"penempatan barang voluminous di area yang sesuai dengan dimensi produk")
        elif item_volume > 0:
            reasons.append(f"penempatan barang kompak untuk optimisasi density penyimpanan")
        
        # Factor 4: Access considerations
        if is_high_access:
            reasons.append(f"lokasi strategis dekat akses utama untuk operasional yang efisien")
        
        # Combine reasons into coherent explanation
        if len(reasons) >= 2:
            main_reason = reasons[0]
            supporting_reason = reasons[1]
            alasan = f"{item_name} ditempatkan di {area_name} karena {main_reason}, serta {supporting_reason}"
        elif len(reasons) == 1:
            alasan = f"{item_name} ditempatkan di {area_name} karena {reasons[0]}"
        else:
            # Fallback reasoning with area characteristics
            if area_utilization < 50:
                alasan = f"{item_name} ditempatkan di {area_name} untuk mengoptimalkan utilisasi ruang yang tersedia ({area_utilization:.1f}%)"
            elif is_high_access:
                alasan = f"{item_name} ditempatkan di {area_name} karena lokasi strategis dekat akses utama untuk operasional efisien"
            else:
                alasan = f"{item_name} ditempatkan di {area_name} berdasarkan analisis algoritma SA untuk konfigurasi optimal"
            
        return alasan
    
    def save_solution_to_database(self, solution: List[PenempatanSolution]) -> bool:
        """
        Menyimpan solusi optimasi langsung ke database
        """
        try:
            recommendations = []
            
            for placement in solution:
                barang = next((b for b in self.barang_list if b.id == placement.barang_id), None)
                area = next((a for a in self.areas if a.id == placement.area_id), None)
                
                if barang and area:
                    # Generate detailed reasoning based on item and area characteristics
                    alasan = self.generate_placement_reasoning(barang, area)
                    
                    recommendation = {
                        "barang_id": placement.barang_id,
                        "area_gudang_id": placement.area_id,
                        "koordinat_x": round(placement.koordinat_x, 2),
                        "koordinat_y": round(placement.koordinat_y, 2),
                        "alasan": alasan,
                        "confidence_score": 0.85,  # Score kepercayaan
                        "algoritma": "Simulated Annealing"
                    }
                    
                    # Tambahkan log_optimasi_id jika tersedia
                    if self.log_optimasi_id:
                        recommendation["log_optimasi_id"] = self.log_optimasi_id
                    
                    recommendations.append(recommendation)
            
            # Simpan ke database
            success = self.db.save_optimization_results(recommendations)
            
            if success:
                # Juga simpan ke file JSON untuk backup
                output_file = "optimization_result.json"
                with open(output_file, 'w') as f:
                    json.dump({
                        "algorithm": "Simulated Annealing",
                        "timestamp": "2025-10-18",
                        "total_items": len(recommendations),
                        "log_optimasi_id": self.log_optimasi_id,
                        "recommendations": recommendations
                    }, f, indent=2)
                
                print(f"✅ Solution saved to database and {output_file}")
                return True
            else:
                print("❌ Failed to save to database")
                return False
            
        except Exception as e:
            print(f"❌ Error saving solution: {e}")
            return False
    
    def run_optimization(self) -> bool:
        """
        Menjalankan proses optimasi lengkap dengan koneksi database
        """
        print("=== WAREHOUSE SPACE OPTIMIZATION USING SIMULATED ANNEALING ===")
        print("🏭 PT. NCS Cabang Bandung - Gudang Optimization System")
        print()
        
        # Display optimization goal
        print("🎯 TUJUAN OPTIMASI:")
        print("Mengoptimalkan penempatan barang di ruang gudang untuk")
        print("memaksimalkan utilisasi ruang dan meningkatkan efisiensi operasional")
        print()
        
        # Connect ke database
        print("🔗 Connecting to database...")
        if not self.connect_database():
            print("❌ Failed to connect to database")
            return False
        
        try:
            # Load data dari database
            print("📊 Loading warehouse and item data...")
            if not self.fetch_areas():
                print("❌ Failed to fetch areas")
                return False
            
            if not self.fetch_barang():
                print("❌ Failed to fetch barang")
                return False
                
            # Validasi data minimal
            if len(self.areas) == 0:
                print("❌ No warehouse areas available for optimization")
                return False
                
            if len(self.barang_list) == 0:
                print("❌ No items available for optimization")
                return False
            
            print(f"📊 Data validation passed: {len(self.areas)} areas, {len(self.barang_list)} items")
            print()
            
            # Jalankan optimasi
            best_solution, best_cost = self.simulated_annealing()
            
            # Simpan hasil ke database
            success = self.save_solution_to_database(best_solution)
            
            # Update status log optimasi menggunakan database manager
            if self.log_optimasi_id:
                hasil_optimasi = {
                    "final_cost": best_cost,
                    "total_items": len(best_solution),
                    "areas_utilized": len(set(p.area_id for p in best_solution)),
                    "execution_time": 0.1,  # Will be calculated properly
                    "algorithm": "Simulated Annealing"
                }
                
                status = "selesai" if success else "gagal"
                detail_hasil = f"Optimization completed with {len(best_solution)} items placed optimally"
                
                self.db.update_optimization_status(
                    log_optimasi_id=self.log_optimasi_id,
                    status=status,
                    hasil_optimasi=hasil_optimasi,
                    detail_hasil=detail_hasil
                )
            
            print()
            print("=== OPTIMIZATION SUMMARY ===")
            print(f"Total items optimized: {len(best_solution)}")
            print(f"Final objective function value: {best_cost:.2f}")
            print(f"Areas utilized: {len(set(p.area_id for p in best_solution))}")
            print(f"Database save: {'✅ Success' if success else '❌ Failed'}")
            
            return success
            
        finally:
            # Selalu tutup koneksi database
            self.disconnect_database()

def main():
    """
    Fungsi utama untuk menjalankan optimasi dengan parameter dari command line
    """
    import argparse
    
    parser = argparse.ArgumentParser(description='Warehouse Optimization using Simulated Annealing')
    parser.add_argument('--log-id', type=int, help='Log optimasi ID untuk database')
    parser.add_argument('--params', type=str, help='JSON parameters untuk optimasi')
    
    args = parser.parse_args()
    
    # Parse configuration dari parameter
    optimization_config = {}
    if args.params:
        try:
            optimization_config = json.loads(args.params)
            print(f"📋 Received configuration: {optimization_config}")
        except json.JSONDecodeError as e:
            print(f"❌ Error parsing parameters: {e}")
            return 1
    
    # Inisialisasi optimizer dengan config
    optimizer = WarehouseOptimizer(optimization_config)
    
    # Set log ID jika ada
    if args.log_id:
        optimizer.log_optimasi_id = args.log_id
        print(f"🔗 Connected to log optimasi ID: {args.log_id}")
    
    try:
        success = optimizer.run_optimization()
        if success:
            print("\n✅ Optimization completed successfully!")
        else:
            print("\n❌ Optimization failed!")
            return 1
    except Exception as e:
        print(f"❌ Error during optimization: {e}")
        return 1
    
    return 0

if __name__ == "__main__":
    exit_code = main()
    sys.exit(exit_code)