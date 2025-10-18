#!/usr/bin/env python3
"""
Warehouse Database Connection Manager
Mengelola koneksi langsung ke MySQL database untuk optimasi warehouse

Author: Sistem Gudang NCS
Date: 2025-10-18
"""

import os
import json
import mysql.connector
import pymysql
from dotenv import load_dotenv
from typing import List, Dict, Optional, Tuple
import sys

class DatabaseManager:
    """
    Class untuk mengelola koneksi database MySQL
    """
    
    def __init__(self, env_path: str = "../.env"):
        """
        Inisialisasi connection dengan membaca .env file
        """
        # Load environment variables
        env_file_path = os.path.join(os.path.dirname(__file__), env_path)
        if not os.path.exists(env_file_path):
            raise FileNotFoundError(f".env file not found at {env_file_path}")
        
        load_dotenv(env_file_path)
        
        # Database configuration dari .env
        self.db_config = {
            'host': os.getenv('DB_HOST', '127.0.0.1'),
            'port': int(os.getenv('DB_PORT', 3306)),
            'database': os.getenv('DB_DATABASE', 'thoriq'),
            'user': os.getenv('DB_USERNAME', 'root'),
            'password': os.getenv('DB_PASSWORD', ''),
        }
        
        self.connection = None
        self.cursor = None
        
        print(f"Database config loaded:")
        print(f"  Host: {self.db_config['host']}:{self.db_config['port']}")
        print(f"  Database: {self.db_config['database']}")
        print(f"  User: {self.db_config['user']}")
    
    def connect(self) -> bool:
        """
        Membuat koneksi ke database MySQL
        """
        try:
            # Coba mysql-connector-python terlebih dahulu
            self.connection = mysql.connector.connect(**self.db_config)
            self.cursor = self.connection.cursor(dictionary=True)
            print("✅ Connected to MySQL database using mysql-connector-python")
            return True
            
        except mysql.connector.Error as e:
            print(f"❌ mysql-connector-python failed: {e}")
            
            # Fallback ke PyMySQL
            try:
                self.connection = pymysql.connect(
                    host=self.db_config['host'],
                    port=self.db_config['port'],
                    user=self.db_config['user'],
                    password=self.db_config['password'],
                    database=self.db_config['database'],
                    cursorclass=pymysql.cursors.DictCursor
                )
                self.cursor = self.connection.cursor()
                print("✅ Connected to MySQL database using PyMySQL")
                return True
                
            except Exception as e:
                print(f"❌ PyMySQL also failed: {e}")
                return False
    
    def disconnect(self):
        """
        Menutup koneksi database
        """
        if self.cursor:
            self.cursor.close()
        if self.connection:
            self.connection.close()
        print("🔌 Database connection closed")
    
    def fetch_areas(self) -> List[Dict]:
        """
        Mengambil data area gudang dari database
        
        SQL Query:
        SELECT * FROM area_gudang WHERE tersedia = 1
        """
        query = """
        SELECT 
            id,
            gudang_id,
            kode_area,
            nama_area,
            koordinat_x,
            koordinat_y,
            panjang,
            lebar,
            tinggi,
            kapasitas,
            kapasitas_terpakai,
            jenis_area,
            tersedia
        FROM area_gudang 
        WHERE tersedia = 1
        ORDER BY kode_area
        """
        
        try:
            self.cursor.execute(query)
            areas = self.cursor.fetchall()
            print(f"📦 Loaded {len(areas)} available areas from database")
            return areas
        except Exception as e:
            print(f"❌ Error fetching areas: {e}")
            return []
    
    def fetch_barang(self) -> List[Dict]:
        """
        Mengambil data barang dengan join ke kategori
        
        SQL Query dengan JOIN untuk mendapatkan informasi kategori
        """
        query = """
        SELECT 
            b.id,
            b.kode_barang,
            b.nama_barang,
            b.panjang,
            b.lebar,
            b.tinggi,
            (b.panjang * b.lebar * b.tinggi) as volume,
            b.kategori_barang_id,
            kb.nama_kategori,
            b.created_at,
            b.updated_at
        FROM barang b
        INNER JOIN kategori_barang kb ON b.kategori_barang_id = kb.id
        ORDER BY b.kode_barang
        """
        
        try:
            self.cursor.execute(query)
            barang_list = self.cursor.fetchall()
            print(f"📋 Loaded {len(barang_list)} items from database")
            return barang_list
        except Exception as e:
            print(f"❌ Error fetching barang: {e}")
            return []
    
    def fetch_existing_placements(self) -> List[Dict]:
        """
        Mengambil penempatan barang yang sudah ada
        Koordinat diambil dari area_gudang, bukan dari penempatan_barang
        """
        query = """
        SELECT 
            pb.id,
            pb.barang_id,
            pb.area_gudang_id,
            pb.jumlah,
            pb.tanggal_penempatan,
            pb.status,
            b.nama_barang,
            ag.nama_area,
            ag.koordinat_x,
            ag.koordinat_y,
            ag.panjang,
            ag.lebar
        FROM penempatan_barang pb
        INNER JOIN barang b ON pb.barang_id = b.id
        INNER JOIN area_gudang ag ON pb.area_gudang_id = ag.id
        ORDER BY pb.tanggal_penempatan DESC
        """
        
        try:
            self.cursor.execute(query)
            placements = self.cursor.fetchall()
            print(f"📍 Loaded {len(placements)} existing placements from database")
            return placements
        except Exception as e:
            print(f"❌ Error fetching placements: {e}")
            return []
    
    def save_optimization_results(self, recommendations: List[Dict]) -> bool:
        """
        Menyimpan hasil optimasi ke tabel rekomendasi_penempatan
        Dengan koordinat spesifik dalam area
        """
        if not recommendations:
            print("⚠️ No recommendations to save")
            return False
        
        # Query untuk insert rekomendasi dengan koordinat
        insert_query = """
        INSERT INTO rekomendasi_penempatan 
        (log_optimasi_id, barang_id, area_gudang_rekomendasi, koordinat_x_spesifik, koordinat_y_spesifik, alasan, confidence_score, algoritma, created_at, updated_at)
        VALUES (%(log_optimasi_id)s, %(barang_id)s, %(area_gudang_id)s, %(koordinat_x)s, %(koordinat_y)s, %(alasan)s, %(confidence_score)s, %(algoritma)s, NOW(), NOW())
        """
        
        try:
            # Cek apakah kolom koordinat spesifik ada, jika tidak create terlebih dahulu
            check_columns_query = """
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = 'rekomendasi_penempatan' 
            AND COLUMN_NAME IN ('koordinat_x_spesifik', 'koordinat_y_spesifik', 'confidence_score', 'algoritma')
            """
            
            self.cursor.execute(check_columns_query, (self.db_config['database'],))
            existing_columns = [row['COLUMN_NAME'] for row in self.cursor.fetchall()]
            
            # Jika kolom belum ada, tambahkan
            if 'koordinat_x_spesifik' not in existing_columns:
                alter_query = """
                ALTER TABLE rekomendasi_penempatan 
                ADD COLUMN koordinat_x_spesifik DECIMAL(10,6) NULL AFTER area_gudang_rekomendasi,
                ADD COLUMN koordinat_y_spesifik DECIMAL(10,6) NULL AFTER koordinat_x_spesifik
                """
                self.cursor.execute(alter_query)
                print("✅ Added coordinate columns to rekomendasi_penempatan")
            
            if 'confidence_score' not in existing_columns:
                alter_query2 = """
                ALTER TABLE rekomendasi_penempatan 
                ADD COLUMN confidence_score DECIMAL(5,4) DEFAULT 0.5000 AFTER alasan,
                ADD COLUMN algoritma VARCHAR(100) DEFAULT 'Manual' AFTER confidence_score
                """
                self.cursor.execute(alter_query2)
                print("✅ Added algorithm tracking columns to rekomendasi_penempatan")
            
            # Hapus rekomendasi lama dari algoritma yang sama
            delete_query = "DELETE FROM rekomendasi_penempatan WHERE algoritma = 'Simulated Annealing'"
            self.cursor.execute(delete_query)
            print(f"🗑️ Cleared previous Simulated Annealing recommendations")
            
            # Insert rekomendasi baru
            self.cursor.executemany(insert_query, recommendations)
            self.connection.commit()
            
            print(f"💾 Saved {len(recommendations)} recommendations to database")
            return True
            
        except Exception as e:
            print(f"❌ Error saving recommendations: {e}")
            self.connection.rollback()
            return False
    
    def update_optimization_status(self, log_optimasi_id: int, status: str, 
                                 hasil_optimasi: Dict = None, 
                                 detail_hasil: str = None) -> bool:
        """
        Update status log optimasi setelah selesai
        
        Args:
            log_optimasi_id: ID log optimasi
            status: Status baru ('selesai', 'gagal', 'dibatalkan')
            hasil_optimasi: Dictionary hasil optimasi (opsional)
            detail_hasil: Detail hasil dalam format string (opsional)
        """
        try:
            # Data yang akan diupdate
            update_data = {
                'status': status,
                'waktu_selesai': 'NOW()',
                'updated_at': 'NOW()'
            }
            
            # Tambahkan hasil optimasi jika ada
            if hasil_optimasi:
                update_data['hasil_optimasi'] = json.dumps(hasil_optimasi)
                
                # Extract metrics dari hasil optimasi
                if 'final_cost' in hasil_optimasi:
                    update_data['biaya_akhir'] = hasil_optimasi['final_cost']
                if 'initial_cost' in hasil_optimasi:
                    update_data['biaya_awal'] = hasil_optimasi['initial_cost']
                    if hasil_optimasi['initial_cost'] > 0:
                        improvement = ((hasil_optimasi['initial_cost'] - hasil_optimasi['final_cost']) / hasil_optimasi['initial_cost']) * 100
                        update_data['persentase_perbaikan'] = round(improvement, 2)
                if 'iterations' in hasil_optimasi:
                    update_data['iterasi'] = hasil_optimasi['iterations']
                if 'execution_time' in hasil_optimasi:
                    update_data['waktu_eksekusi'] = hasil_optimasi['execution_time']
            
            if detail_hasil:
                update_data['detail_hasil'] = detail_hasil
            
            # Build query dinamis
            set_clause = []
            params = {'log_optimasi_id': log_optimasi_id}
            
            for key, value in update_data.items():
                if value == 'NOW()':
                    set_clause.append(f"{key} = NOW()")
                else:
                    set_clause.append(f"{key} = %({key})s")
                    params[key] = value
            
            update_query = f"""
            UPDATE log_optimasi 
            SET {', '.join(set_clause)}
            WHERE id = %(log_optimasi_id)s
            """
            
            self.cursor.execute(update_query, params)
            self.connection.commit()
            
            print(f"✅ Updated optimization status to '{status}' for log_optimasi_id: {log_optimasi_id}")
            return True
            
        except Exception as e:
            print(f"❌ Error updating optimization status: {e}")
            self.connection.rollback()
            return False
    
    def get_database_stats(self) -> Dict:
        """
        Mendapatkan statistik database untuk validation
        """
        stats = {}
        
        try:
            # Count tables
            queries = {
                'areas': "SELECT COUNT(*) as count FROM area_gudang WHERE tersedia = 1",
                'total_areas': "SELECT COUNT(*) as count FROM area_gudang",
                'barang': "SELECT COUNT(*) as count FROM barang",
                'kategori': "SELECT COUNT(*) as count FROM kategori_barang",
                'placements': "SELECT COUNT(*) as count FROM penempatan_barang",
                'recommendations': "SELECT COUNT(*) as count FROM rekomendasi_penempatan"
            }
            
            for key, query in queries.items():
                self.cursor.execute(query)
                result = self.cursor.fetchone()
                stats[key] = result['count'] if result else 0
            
            # Calculate capacity utilization
            capacity_query = """
            SELECT 
                SUM(kapasitas) as total_capacity,
                SUM(kapasitas_terpakai) as used_capacity
            FROM area_gudang 
            WHERE tersedia = 1
            """
            
            self.cursor.execute(capacity_query)
            capacity_result = self.cursor.fetchone()
            
            if capacity_result and capacity_result['total_capacity']:
                total_cap = float(capacity_result['total_capacity'])
                used_cap = float(capacity_result['used_capacity'] or 0)
                stats['capacity_utilization'] = (used_cap / total_cap) * 100
            else:
                stats['capacity_utilization'] = 0
            
            return stats
            
        except Exception as e:
            print(f"❌ Error getting database stats: {e}")
            return {}

def test_database_connection():
    """
    Test function untuk memastikan koneksi database berfungsi
    """
    print("🧪 Testing database connection...")
    
    try:
        db = DatabaseManager()
        
        if not db.connect():
            print("❌ Failed to connect to database")
            return False
        
        # Test basic queries
        stats = db.get_database_stats()
        
        print("\n📊 Database Statistics:")
        print(f"  Available Areas: {stats.get('areas', 0)}")
        print(f"  Total Areas: {stats.get('total_areas', 0)}")
        print(f"  Items (Barang): {stats.get('barang', 0)}")
        print(f"  Categories: {stats.get('kategori', 0)}")
        print(f"  Existing Placements: {stats.get('placements', 0)}")
        print(f"  Recommendations: {stats.get('recommendations', 0)}")
        print(f"  Capacity Utilization: {stats.get('capacity_utilization', 0):.1f}%")
        
        # Test data fetching
        areas = db.fetch_areas()
        barang = db.fetch_barang()
        placements = db.fetch_existing_placements()
        
        print(f"\n✅ Data fetch test successful:")
        print(f"  Areas fetched: {len(areas)}")
        print(f"  Items fetched: {len(barang)}")
        print(f"  Placements fetched: {len(placements)}")
        
        db.disconnect()
        return True
        
    except Exception as e:
        print(f"❌ Database test failed: {e}")
        return False

if __name__ == "__main__":
    # Run database connection test
    success = test_database_connection()
    sys.exit(0 if success else 1)