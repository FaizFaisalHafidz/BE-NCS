# 🏭 Warehouse Optimization System
## Optimasi Penempatan Barang menggunakan Simulated Annealing

Sistem optimasi ini menggunakan algoritma **Simulated Annealing** untuk menentukan penempatan barang yang optimal di gudang, dengan mempertimbangkan berbagai faktor seperti jarak tempuh, utilisasi ruang, pengelompokan kategori, dan frekuensi akses.

---

## 📊 Tentang Simulated Annealing

**Simulated Annealing** adalah algoritma optimasi metaheuristik yang terinspirasi dari proses annealing dalam metalurgi. Algoritma ini sangat efektif untuk menyelesaikan masalah optimasi kombinatorial seperti warehouse placement optimization.

### 🔬 Prinsip Kerja Algoritma

```
1. Mulai dengan solusi awal dan suhu tinggi (T₀)
2. Untuk setiap iterasi:
   a. Generate solusi tetangga (neighbor solution)
   b. Hitung perubahan cost: ΔE = f(new) - f(current)
   c. Jika ΔE < 0: terima solusi baru (lebih baik)
   d. Jika ΔE ≥ 0: terima dengan probabilitas P = e^(-ΔE/T)
3. Kurangi suhu: T = α × T (cooling)
4. Ulangi sampai suhu mencapai minimum
```

### 📐 Formula Matematika

**Fungsi Objektif:**
```
f(x) = w₁×DistanceCost + w₂×SpacePenalty + w₃×CategoryPenalty + w₄×AccessPenalty
```

**Probabilitas Penerimaan (Boltzmann):**
```
P(accept) = e^(-ΔE/T)
```

**Pendinginan Suhu:**
```
T(t+1) = α × T(t)
```

Dimana:
- `w₁, w₂, w₃, w₄` = bobot untuk setiap komponen (0.4, 0.3, 0.2, 0.1)
- `ΔE` = perubahan energi/cost
- `T` = suhu saat ini
- `α` = cooling rate (0.95)

---

## 📁 Struktur File

```
script/
├── warehouse_optimization.py    # Main optimization algorithm
├── optimization_analyzer.py     # Analysis and visualization tools
├── run_optimization.py         # CLI runner with multiple modes
├── requirements.txt           # Python dependencies
└── README.md                 # Documentation (this file)
```

---

## 🚀 Cara Penggunaan

### 1. Aktivasi Virtual Environment
```bash
source venv/bin/activate
```

### 2. Running Optimasi

#### Single Optimization (Optimasi Tunggal)
```bash
python run_optimization.py single
```

#### Parameter Tuning (Pencarian Parameter Terbaik)
```bash
python run_optimization.py tune
```

#### Batch Optimization (Multiple Runs untuk Konsistensi)
```bash
python run_optimization.py batch 10    # 10 kali running
```

#### Analisis Hasil
```bash
python run_optimization.py analyze
```

### 3. Direct Running
```bash
# Jalankan langsung file utama
python warehouse_optimization.py

# Atau analisis hasil existing
python optimization_analyzer.py
```

---

## ⚙️ Parameter Konfigurasi

| Parameter | Default | Deskripsi |
|-----------|---------|-----------|
| `temperature_initial` | 1000.0 | Suhu awal algoritma |
| `temperature_final` | 0.1 | Suhu akhir (stopping condition) |
| `cooling_rate` | 0.95 | Laju pendinginan (α) |
| `max_iterations` | 1000 | Maksimum iterasi per suhu |
| `max_no_improvement` | 50 | Early stopping threshold |

### 🎯 Parameter Tuning Presets

| Preset | Temp Initial | Cooling Rate | Max Iterations | Use Case |
|--------|-------------|--------------|----------------|----------|
| **Fast** | 500 | 0.95 | 500 | Quick results |
| **Balanced** | 1000 | 0.95 | 1000 | Default setting |
| **Thorough** | 2000 | 0.99 | 2000 | Best quality |
| **Aggressive** | 1500 | 0.90 | 1500 | Fast convergence |

---

## 📈 Fungsi Objektif Detail

### 1. Distance Cost (40% bobot)
```python
# Minimasi jarak dari entry point (0,0)
distance_cost = Σ(distance × frequency_weight)
```

### 2. Space Utilization Penalty (30% bobot)
```python
# Penalti untuk utilisasi ruang tidak optimal
if utilization < 30%: penalty += (30% - utilization) × 100
if utilization > 100%: penalty += (utilization - 100%) × 1000
```

### 3. Category Grouping Penalty (20% bobot)
```python
# Penalti untuk kategori yang tersebar
category_penalty = (num_areas - 1) × 10 per kategori
```

### 4. Access Frequency Penalty (10% bobot)
```python
# Barang sering diakses harus dekat pintu masuk
if frequency > 7 and distance > 20m:
    access_penalty += distance × 2
```

---

## 📊 Output dan Hasil

### 1. File Output
- `optimization_result.json` - Hasil optimasi utama
- `optimization_analysis.json` - Analisis performa detail
- `parameter_tuning_results.json` - Hasil parameter tuning
- `batch_optimization_results.json` - Hasil batch optimization

### 2. Metrik Evaluasi
- **Space Utilization**: Tingkat penggunaan ruang per area
- **Travel Distance**: Jarak tempuh untuk picking
- **Category Clustering**: Tingkat pengelompokan kategori
- **Access Efficiency**: Efisiensi akses barang prioritas

### 3. Sample Output
```json
{
  "algorithm": "Simulated Annealing",
  "total_items": 25,
  "recommendations": [
    {
      "barang_id": 1,
      "area_gudang_id": 3,
      "koordinat_x": 15.50,
      "koordinat_y": 8.25,
      "confidence_score": 0.85,
      "algoritma": "Simulated Annealing"
    }
  ]
}
```

---

## 🔧 Troubleshooting

### Common Issues

#### 1. Token Authentication Error
```bash
# Pastikan file token.txt ada dan valid
cat ../token.txt
```

#### 2. API Connection Error
```bash
# Test koneksi ke API
curl -X GET "http://127.0.0.1:8000/api/areas" -H "Authorization: Bearer $(cat ../token.txt)"
```

#### 3. Import Error
```bash
# Install dependencies
pip install -r requirements.txt
```

#### 4. Empty Dataset
- Pastikan ada data area gudang dan barang di database
- Check API response tidak kosong

---

## 📚 Dependencies

```txt
numpy==2.3.4          # Numerical computing
pandas==2.3.3         # Data manipulation
scikit-learn==1.7.2   # Machine learning utilities
requests==2.32.5      # HTTP requests
matplotlib==3.x       # Plotting (optional)
seaborn==0.x          # Statistical visualization (optional)
```

---

## 🧪 Testing & Validation

### 1. Algorithm Validation
```python
# Test dengan dataset kecil
optimizer = WarehouseOptimizer()
optimizer.max_iterations = 100  # Reduced for testing
success = optimizer.run_optimization()
```

### 2. Performance Benchmarking
```bash
# Compare multiple runs
python run_optimization.py batch 10
```

### 3. Parameter Sensitivity Analysis
```bash
# Test different parameter combinations
python run_optimization.py tune
```

---

## 🔮 Future Enhancements

### Planned Features:
1. **Multi-objective Optimization** - Pareto optimal solutions
2. **Real-time Optimization** - Dynamic reoptimization
3. **Machine Learning Integration** - Demand forecasting
4. **3D Warehouse Modeling** - Height optimization
5. **IoT Integration** - Real-time monitoring
6. **Advanced Visualization** - 3D warehouse layout

### Algorithm Improvements:
1. **Hybrid Approaches** - SA + Genetic Algorithm
2. **Adaptive Parameters** - Self-tuning parameters
3. **Parallel Processing** - Multi-threaded optimization
4. **Memory Management** - Large-scale datasets

---

## 📞 Support & Contact

Untuk pertanyaan atau issues:
1. Check troubleshooting section di atas
2. Review log files untuk error details
3. Test dengan dataset kecil terlebih dahulu
4. Pastikan semua dependencies terinstall dengan benar

---

## 📄 License & Credits

**Warehouse Optimization System**  
Developed for NCS Warehouse Management  
Using Simulated Annealing Algorithm  

*Author: Sistem Gudang NCS*  
*Date: 18 Oktober 2025*

---

*Happy Optimizing! 🚀📦*