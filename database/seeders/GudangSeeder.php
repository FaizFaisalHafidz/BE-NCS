<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Gudang;
use App\Models\AreaGudang;
use App\Models\KategoriBarang;
use App\Models\Barang;

class GudangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Gudang PT. NCS Bandung
        $gudang = Gudang::create([
            'nama_gudang' => 'Gudang Utama PT. NCS Bandung',
            'alamat' => 'Jl. Mutiara No.2 A, Cijagra, Kec. Lengkong, Kota Bandung, Jawa Barat',
            'panjang' => 50.00, // 50 meter
            'lebar' => 30.00,   // 30 meter
            'tinggi' => 8.00,   // 8 meter
            'total_kapasitas' => 12000.00, // 12,000 m3
            'kapasitas_terpakai' => 0.00,
            'aktif' => true,
        ]);

        // Create Area Gudang
        $areas = [
            [
                'kode_area' => 'A1-01',
                'nama_area' => 'Area A1 - Rak Tinggi',
                'koordinat_x' => 5.00,
                'koordinat_y' => 5.00,
                'panjang' => 10.00,
                'lebar' => 8.00,
                'tinggi' => 6.00,
                'kapasitas' => 480.00,
                'jenis_area' => 'rak'
            ],
            [
                'kode_area' => 'A1-02',
                'nama_area' => 'Area A1 - Rak Sedang',
                'koordinat_x' => 15.00,
                'koordinat_y' => 5.00,
                'panjang' => 10.00,
                'lebar' => 8.00,
                'tinggi' => 4.00,
                'kapasitas' => 320.00,
                'jenis_area' => 'rak'
            ],
            [
                'kode_area' => 'B1-01',
                'nama_area' => 'Area B1 - Lantai',
                'koordinat_x' => 25.00,
                'koordinat_y' => 5.00,
                'panjang' => 15.00,
                'lebar' => 10.00,
                'tinggi' => 3.00,
                'kapasitas' => 450.00,
                'jenis_area' => 'lantai'
            ],
            [
                'kode_area' => 'A2-01',
                'nama_area' => 'Area A2 - Rak Tinggi',
                'koordinat_x' => 5.00,
                'koordinat_y' => 15.00,
                'panjang' => 10.00,
                'lebar' => 8.00,
                'tinggi' => 6.00,
                'kapasitas' => 480.00,
                'jenis_area' => 'rak'
            ],
            [
                'kode_area' => 'C1-01',
                'nama_area' => 'Area C1 - Khusus Fragile',
                'koordinat_x' => 35.00,
                'koordinat_y' => 5.00,
                'panjang' => 8.00,
                'lebar' => 6.00,
                'tinggi' => 4.00,
                'kapasitas' => 192.00,
                'jenis_area' => 'khusus'
            ],
        ];

        foreach ($areas as $area) {
            AreaGudang::create(array_merge($area, [
                'gudang_id' => $gudang->id,
                'kapasitas_terpakai' => 0.00,
                'tersedia' => true,
            ]));
        }

        // Create Kategori Barang
        $kategoris = [
            [
                'kode_kategori' => 'ELK',
                'nama_kategori' => 'Elektronik',
                'deskripsi' => 'Perangkat elektronik dan gadget'
            ],
            [
                'kode_kategori' => 'PKT',
                'nama_kategori' => 'Paket Express',
                'deskripsi' => 'Paket pengiriman express dan urgent'
            ],
            [
                'kode_kategori' => 'DOK',
                'nama_kategori' => 'Dokumen',
                'deskripsi' => 'Dokumen penting dan surat-surat'
            ],
            [
                'kode_kategori' => 'FRG',
                'nama_kategori' => 'Barang Fragile',
                'deskripsi' => 'Barang mudah pecah dan rusak'
            ],
            [
                'kode_kategori' => 'GEN',
                'nama_kategori' => 'General',
                'deskripsi' => 'Barang umum lainnya'
            ],
        ];

        foreach ($kategoris as $kategori) {
            KategoriBarang::create(array_merge($kategori, [
                'aktif' => true,
            ]));
        }

        // Create Sample Barang
        $barangs = [
            [
                'kode_barang' => 'ELK-001',
                'nama_barang' => 'Laptop Gaming ASUS ROG',
                'kategori_barang_id' => 1, // Elektronik
                'panjang' => 35.00,
                'lebar' => 25.00,
                'tinggi' => 5.00,
                'berat' => 2.50,
                'mudah_pecah' => true,
                'prioritas' => 'tinggi',
                'deskripsi' => 'Laptop gaming high-end',
                'barcode' => '1234567890123'
            ],
            [
                'kode_barang' => 'PKT-001',
                'nama_barang' => 'Paket Express Jakarta',
                'kategori_barang_id' => 2, // Paket Express
                'panjang' => 30.00,
                'lebar' => 20.00,
                'tinggi' => 15.00,
                'berat' => 1.20,
                'mudah_pecah' => false,
                'prioritas' => 'tinggi',
                'deskripsi' => 'Paket urgent ke Jakarta',
                'barcode' => '1234567890124'
            ],
            [
                'kode_barang' => 'DOK-001',
                'nama_barang' => 'Dokumen Kontrak',
                'kategori_barang_id' => 3, // Dokumen
                'panjang' => 25.00,
                'lebar' => 18.00,
                'tinggi' => 2.00,
                'berat' => 0.30,
                'mudah_pecah' => false,
                'prioritas' => 'sedang',
                'deskripsi' => 'Dokumen penting kontrak bisnis',
                'barcode' => '1234567890125'
            ],
            [
                'kode_barang' => 'FRG-001',
                'nama_barang' => 'Keramik Artisan',
                'kategori_barang_id' => 4, // Barang Fragile
                'panjang' => 20.00,
                'lebar' => 20.00,
                'tinggi' => 25.00,
                'berat' => 1.80,
                'mudah_pecah' => true,
                'prioritas' => 'tinggi',
                'deskripsi' => 'Keramik handmade limited edition',
                'barcode' => '1234567890126'
            ],
            [
                'kode_barang' => 'GEN-001',
                'nama_barang' => 'Buku Novel',
                'kategori_barang_id' => 5, // General
                'panjang' => 15.00,
                'lebar' => 10.00,
                'tinggi' => 3.00,
                'berat' => 0.50,
                'mudah_pecah' => false,
                'prioritas' => 'rendah',
                'deskripsi' => 'Novel fiksi terbaru',
                'barcode' => '1234567890127'
            ],
        ];

        foreach ($barangs as $barang) {
            Barang::create(array_merge($barang, [
                'aktif' => true,
            ]));
        }

        $this->command->info('Sample Gudang, Area, Kategori, dan Barang created successfully!');
    }
}
