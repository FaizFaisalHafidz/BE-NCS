# NCS Warehouse Management System - Backend API

![NCS Logo](https://via.placeholder.com/150x50/blue/white?text=NCS)

**NCS Warehouse Management System** adalah aplikasi manajemen gudang yang menggunakan algoritma **Simulated Annealing** untuk optimasi penempatan barang. Sistem ini dibangun dengan Laravel 11 dan menyediakan REST API lengkap untuk operasi gudang.

## 📋 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Teknologi Stack](#-teknologi-stack)
- [Prerequisites](#-prerequisites)
- [Instalasi & Setup](#-instalasi--setup)
- [Konfigurasi Database](#-konfigurasi-database)
- [Menjalankan Aplikasi](#-menjalankan-aplikasi)
- [API Documentation](#-api-documentation)
- [Testing](#-testing)
- [Optimasi Gudang](#-optimasi-gudang)
- [Struktur Project](#-struktur-project)
- [Troubleshooting](#-troubleshooting)
- [Contributing](#-contributing)

## 🌟 Fitur Utama

### 🏪 **Manajemen Gudang**
- ✅ Kelola data gudang, area, dan kategori barang
- ✅ Sistem penempatan barang dengan tracking lokasi
- ✅ QR Code generator untuk barang
- ✅ Monitoring kapasitas dan utilisasi gudang

### 🧠 **Optimasi Cerdas**
- ✅ Algoritma **Simulated Annealing** untuk optimasi penempatan
- ✅ Rekomendasi penempatan barang otomatis
- ✅ Analisis performa dan efisiensi gudang
- ✅ Log dan tracking semua proses optimasi

### 👥 **Manajemen User & Security**
- ✅ Authentication dengan Laravel Sanctum
- ✅ Role-based access control
- ✅ Log aktivitas lengkap untuk audit trail
- ✅ Update profile dan change password

### 📊 **Analytics & Reporting**
- ✅ Dashboard dengan statistik real-time
- ✅ Analisis utilisasi gudang
- ✅ Performance metrics dan KPI
- ✅ Export data ke berbagai format

### 📱 **Mobile Ready API**
- ✅ RESTful API dengan dokumentasi Swagger
- ✅ Response format JSON yang konsisten
- ✅ Error handling yang comprehensive
- ✅ Pagination dan filtering

## 🛠 Teknologi Stack

### **Backend Framework**
- **Laravel 11.x** - PHP Framework modern
- **PHP 8.2+** - Programming language
- **MySQL 8.0+** - Database management

### **Authentication & Security**
- **Laravel Sanctum** - API authentication
- **Spatie Laravel Permission** - Role & permission management
- **bcrypt** - Password hashing

### **API Documentation**
- **L5-Swagger** - OpenAPI/Swagger documentation
- **OpenAPI 3.0** - API specification

### **Development Tools**
- **Composer** - PHP dependency manager
- **Artisan** - Laravel command-line interface
- **PHPUnit** - Testing framework

### **Python Integration**
- **Python 3.8+** - Untuk algoritma optimasi
- **NumPy** - Scientific computing
- **Matplotlib** - Data visualization

## 📋 Prerequisites

Pastikan sistem Anda memiliki requirements berikut:

### **System Requirements**
```bash
PHP >= 8.2
MySQL >= 8.0 (atau MariaDB >= 10.3)
Composer >= 2.0
Node.js >= 16.x (optional, untuk frontend)
Python >= 3.8 (untuk optimasi)
Git
```

### **PHP Extensions Required**
```bash
php-mysql
php-mbstring
php-xml
php-curl
php-zip
php-gd
php-json
php-tokenizer
php-fileinfo
php-bcmath
php-ctype
php-openssl
```

### **Cek Requirements**
```bash
# Cek versi PHP
php --version

# Cek PHP extensions
php -m | grep -E "(mysql|mbstring|xml|curl|zip|gd|json)"

# Cek Composer
composer --version

# Cek MySQL
mysql --version

# Cek Python (untuk optimasi)
python3 --version
```

## 🚀 Instalasi & Setup

### **1. Clone Repository**
```bash
# Clone dari GitHub
git clone https://github.com/FaizFaisalHafidz/BE-NCS.git

# Masuk ke direktori project
cd BE-NCS

# Atau jika dari zip file
unzip BE-NCS.zip
cd BE-NCS
```

### **2. Install Dependencies**
```bash
# Install PHP dependencies
composer install

# Install Python dependencies (untuk optimasi)
cd script
pip install -r requirements.txt
cd ..

# Install diagram generator dependencies
cd diagram_generator
pip install -r requirements.txt
cd ..
```

### **3. Environment Configuration**
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### **4. Edit file .env**
```bash
# Buka file .env dan sesuaikan konfigurasi
nano .env
```

**Konfigurasi Database:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ncs_warehouse
DB_USERNAME=root
DB_PASSWORD=your_password
```

**Konfigurasi Aplikasi:**
```env
APP_NAME="NCS Warehouse System"
APP_ENV=local
APP_KEY=base64:your_generated_key
APP_DEBUG=true
APP_URL=http://localhost:8000
```

**Konfigurasi Email (optional):**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

## 🗄️ Konfigurasi Database

### **1. Buat Database**
```sql
-- Login ke MySQL
mysql -u root -p

-- Buat database
CREATE DATABASE ncs_warehouse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Buat user (optional)
CREATE USER 'ncs_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON ncs_warehouse.* TO 'ncs_user'@'localhost';
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

### **2. Jalankan Migrations**
```bash
# Jalankan semua migrations
php artisan migrate

# Atau fresh migrate (hati-hati: akan hapus semua data)
php artisan migrate:fresh
```

### **3. Seed Database (Optional)**
```bash
# Jalankan seeders untuk data contoh
php artisan db:seed

# Atau seed specific seeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=GudangSeeder
```

### **4. Verifikasi Database**
```sql
-- Cek tabel yang sudah dibuat
mysql -u root -p ncs_warehouse
SHOW TABLES;

-- Cek data user default
SELECT * FROM users;
```

## ▶️ Menjalankan Aplikasi

### **1. Development Server**
```bash
# Jalankan Laravel development server
php artisan serve

# Atau dengan host dan port spesifik
php artisan serve --host=0.0.0.0 --port=8000
```

### **2. Background Services (Optional)**
```bash
# Jalankan queue worker (untuk background jobs)
php artisan queue:work

# Jalankan scheduler (untuk cron jobs)
# Tambahkan ke crontab: * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### **3. Generate API Documentation**
```bash
# Generate Swagger documentation
php artisan l5-swagger:generate

# Akses dokumentasi di: http://localhost:8000/api/documentation
```

### **4. Clear Cache (jika diperlukan)**
```bash
# Clear semua cache
php artisan optimize:clear

# Atau manual
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 📚 API Documentation

### **Swagger UI**
Setelah menjalankan server, akses dokumentasi API di:
```
http://localhost:8000/api/documentation
```

### **Available API Endpoints**

#### **Authentication**
```
POST   /api/auth/login              - Login user
POST   /api/auth/logout             - Logout user
POST   /api/auth/refresh            - Refresh token
GET    /api/auth/me                 - Get current user
PUT    /api/auth/update-profile     - Update profile
PUT    /api/auth/change-password    - Change password
```

#### **User Management**
```
GET    /api/users                   - List users
POST   /api/users                   - Create user
GET    /api/users/{id}              - Get user detail
PUT    /api/users/{id}              - Update user
DELETE /api/users/{id}              - Delete user
```

#### **Warehouse Management**
```
GET    /api/gudang                  - List warehouses
POST   /api/gudang                  - Create warehouse
GET    /api/area-gudang             - List warehouse areas
POST   /api/barang                  - Create item
GET    /api/penempatan-barang       - List item placements
```

#### **Analytics**
```
GET    /api/analytics/dashboard     - Dashboard statistics
GET    /api/analytics/utilization   - Utilization metrics
GET    /api/analytics/performance   - Performance analysis
```

#### **Log Activities**
```
GET    /api/log-aktivitas           - List activity logs
POST   /api/log-aktivitas           - Create log entry
GET    /api/log-aktivitas/statistics - Log statistics
```

#### **Optimization**
```
POST   /api/optimization/simulated-annealing - Run optimization
GET    /api/optimization/warehouse-state     - Get current state
```

## 🧪 Testing

### **1. Manual Testing dengan Script**
```bash
# Test authentication
./script/test_auth_api.sh

# Test log activities
./script/test_log_aktivitas_api.sh

# Test update profile
./script/test_update_profile_api.sh

# Test analytics
./script/test_analytics_api.sh
```

### **2. Unit Testing**
```bash
# Jalankan semua tests
php artisan test

# Test spesifik feature
php artisan test --filter=AuthTest

# Test dengan coverage
php artisan test --coverage
```

### **3. API Testing dengan Postman**
Import collection Postman dari dokumentasi Swagger atau buat collection sendiri.

## 🎯 Optimasi Gudang

### **Algoritma Simulated Annealing**
Sistem menggunakan algoritma Simulated Annealing untuk optimasi penempatan barang:

```bash
# Jalankan optimasi manual
cd script
python warehouse_optimization.py

# Atau via API
curl -X POST "http://localhost:8000/api/optimization/simulated-annealing" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "algorithm": "simulated_annealing",
    "iterations": 1000,
    "initial_temp": 100,
    "cooling_rate": 0.95
  }'
```

### **Parameter Optimasi**
- **Initial Temperature**: Suhu awal algoritma (default: 100)
- **Cooling Rate**: Tingkat pendinginan (default: 0.95)
- **Max Iterations**: Maksimum iterasi (default: 1000)
- **Min Temperature**: Suhu minimum (default: 0.1)

## 📁 Struktur Project

```
BE-NCS/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/     # API Controllers
│   │   ├── Middleware/          # Custom Middleware
│   │   └── Requests/            # Form Requests
│   ├── Models/                  # Eloquent Models
│   ├── Services/                # Business Logic Services
│   └── Traits/                  # Reusable Traits
├── config/                      # Configuration files
├── database/
│   ├── migrations/              # Database migrations
│   ├── seeders/                 # Database seeders
│   └── factories/               # Model factories
├── routes/
│   ├── api.php                  # API routes
│   └── web.php                  # Web routes
├── script/                      # Python optimization scripts
├── diagram_generator/           # Documentation generators
├── storage/
│   ├── api-docs/                # Swagger documentation
│   ├── app/                     # Application files
│   └── logs/                    # Log files
├── tests/                       # Unit & Feature tests
├── .env.example                 # Environment template
├── composer.json                # PHP dependencies
└── README.md                    # Project documentation
```

## 🔧 Troubleshooting

### **Problem: Database Connection Error**
```bash
# Cek koneksi database
php artisan tinker
>>> DB::connection()->getPdo();

# Cek konfigurasi
php artisan config:show database
```

### **Problem: Permission Denied**
```bash
# Set permissions untuk storage dan cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### **Problem: Composer Dependencies**
```bash
# Update dependencies
composer update

# Reinstall dependencies
rm -rf vendor
composer install
```

### **Problem: Python Script Error**
```bash
# Install Python dependencies
cd script
pip install --upgrade pip
pip install -r requirements.txt

# Test Python script
python warehouse_optimization.py --test
```

### **Problem: Swagger Not Loading**
```bash
# Regenerate API docs
php artisan l5-swagger:generate

# Clear cache
php artisan optimize:clear

# Check config
php artisan config:show l5-swagger
```

### **Common Issues & Solutions**

| Problem | Solution |
|---------|----------|
| "Class not found" | Run `composer dump-autoload` |
| "Route not found" | Run `php artisan route:clear` |
| "Config cached" | Run `php artisan config:clear` |
| "Queue not working" | Check `.env` `QUEUE_CONNECTION` |
| "Storage link missing" | Run `php artisan storage:link` |

## 🔒 Security Considerations

### **Environment Security**
```bash
# Jangan commit file .env
echo ".env" >> .gitignore

# Set APP_DEBUG=false di production
APP_DEBUG=false

# Generate strong APP_KEY
php artisan key:generate --force
```

### **Database Security**
- Gunakan user database dengan privilege terbatas
- Enable SSL connection untuk database
- Regular backup database

### **API Security**
- Rate limiting sudah dikonfigurasi
- CORS policy sesuai kebutuhan
- Input validation di semua endpoint

## 🤝 Contributing

### **Development Workflow**
1. Fork repository
2. Create feature branch: `git checkout -b feature/amazing-feature`
3. Commit changes: `git commit -m 'Add amazing feature'`
4. Push to branch: `git push origin feature/amazing-feature`
5. Open Pull Request

### **Code Standards**
- Follow PSR-12 coding standards
- Write unit tests untuk fitur baru
- Update dokumentasi API
- Gunakan meaningful commit messages

### **Testing Before PR**
```bash
# Run all tests
php artisan test

# Check code style
./vendor/bin/phpcs

# Fix code style
./vendor/bin/phpcbf
```

## 📞 Support & Contact

### **Documentation**
- **API Docs**: http://localhost:8000/api/documentation
- **Project Wiki**: https://github.com/FaizFaisalHafidz/BE-NCS/wiki

### **Issues & Bugs**
- **GitHub Issues**: https://github.com/FaizFaisalHafidz/BE-NCS/issues
- **Bug Reports**: Gunakan template issue yang tersedia

### **Community**
- **Developer**: Faiz Faisal Hafidz
- **Email**: faiz@ncs.com
- **Organization**: PT. NCS Bandung

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🎉 Quick Start Commands

Untuk yang ingin langsung coba, jalankan commands berikut:

```bash
# 1. Clone & setup
git clone https://github.com/FaizFaisalHafidz/BE-NCS.git
cd BE-NCS
composer install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database (sesuaikan .env dulu)
php artisan migrate
php artisan db:seed

# 4. Run server
php artisan serve

# 5. Generate API docs
php artisan l5-swagger:generate

# 6. Akses aplikasi
open http://localhost:8000/api/documentation
```

**🎯 Selamat! NCS Warehouse Management System siap digunakan!**

---

> **💡 Tips**: Bookmark halaman dokumentasi API untuk referensi cepat saat development.

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
