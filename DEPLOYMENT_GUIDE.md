# 🚀 DigitalOcean Deployment Guide

## 📋 Environment Setup

### 1. Update .env untuk Production

```bash
# Laravel App Configuration
APP_NAME="Warehouse Optimization API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thoriq_warehouse
DB_USERNAME=thoriq_user
DB_PASSWORD=your_secure_password

# Python Script Configuration (Production Paths)
PYTHON_VENV_PATH=/var/www/BE-NCS/script/venv/bin/python
PYTHON_SCRIPT_PATH=/var/www/BE-NCS/script/warehouse_optimization.py
```

### 2. Server Setup Commands

```bash
# Connect to DigitalOcean server
ssh root@your-server-ip

# Update system packages
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y nginx mysql-server php8.2-fpm php8.2-cli php8.2-mysql php8.2-xml php8.2-curl php8.2-mbstring php8.2-zip php8.2-gd python3 python3-pip python3-venv git composer

# Create project directory
sudo mkdir -p /var/www/BE-NCS
cd /var/www/BE-NCS

# Clone repository
git clone https://github.com/FaizFaisalHafidz/BE-NCS.git .

# Set proper ownership
sudo chown -R www-data:www-data /var/www/BE-NCS
```

### 3. Python Environment Setup

```bash
# Navigate to project directory
cd /var/www/BE-NCS

# Create Python virtual environment inside script directory
cd script
python3 -m venv venv

# Activate virtual environment
source venv/bin/activate

# Install Python requirements
pip install -r requirements.txt

# Test Python script
python warehouse_optimization.py --help

# Deactivate virtual environment
deactivate

# Navigate back to project root
cd /var/www/BE-NCS

# Set executable permissions
sudo chmod +x script/venv/bin/python
sudo chmod +x script/warehouse_optimization.py
```

### 4. Laravel Setup

```bash
# Laravel Setup

```bash
# Install Composer dependencies
cd /var/www/BE-NCS
composer install --no-dev --optimize-autoloader

# Copy and configure environment file
cp .env.example .env
nano .env
# Update with production values above

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Seed database (if needed)
php artisan db:seed

# Cache configurations for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate Swagger documentation
php artisan l5-swagger:generate

# Set proper permissions
sudo chown -R www-data:www-data /var/www/BE-NCS
sudo chmod -R 755 /var/www/BE-NCS
sudo chmod -R 775 /var/www/BE-NCS/storage
sudo chmod -R 775 /var/www/BE-NCS/bootstrap/cache
```

### 5. Nginx Configuration

```bash
# Create Nginx site configuration
sudo nano /etc/nginx/sites-available/BE-NCS

# Add this configuration:
```

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/BE-NCS/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Enable site and restart services
sudo ln -s /etc/nginx/sites-available/BE-NCS /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

### 6. Test Deployment

```bash
# Test Python script with absolute path
/var/www/BE-NCS/script/venv/bin/python /var/www/BE-NCS/script/warehouse_optimization.py --log-id 1 --params '{"gudang_ids":[1],"barang_ids":[1,2,3]}'

# Test Laravel application
curl http://your-domain.com/api/optimization/algorithms

# Check logs
tail -f /var/www/BE-NCS/storage/logs/laravel.log
sudo tail -f /var/log/nginx/error.log
```

### 7. SSL Certificate (Optional but Recommended)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d your-domain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

### 8. Monitoring & Maintenance

```bash
# Create deployment script
sudo nano /var/www/thoriq/deploy.sh
```

```bash
#!/bin/bash
# Deployment script for Thoriq Warehouse API

echo "🚀 Starting deployment..."

# Pull latest changes
git pull origin main

# Update Python dependencies
source venv/bin/activate
pip install -r script/requirements.txt
deactivate

# Update Laravel
composer install --no-dev --optimize-autoloader

# Clear and cache Laravel configurations
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Update API documentation
php artisan l5-swagger:generate

# Set proper permissions
sudo chown -R www-data:www-data /var/www/thoriq
sudo chmod -R 775 storage bootstrap/cache
sudo chmod +x venv/bin/python
sudo chmod +x script/warehouse_optimization.py

# Restart services
sudo systemctl reload nginx
sudo systemctl restart php8.2-fpm

echo "✅ Deployment completed successfully!"
echo "📊 API Documentation: https://your-domain.com/api/documentation"
echo "🧪 Test endpoint: https://your-domain.com/api/optimization/algorithms"
```

```bash
# Make deployment script executable
sudo chmod +x /var/www/thoriq/deploy.sh

# Run deployment
cd /var/www/thoriq && ./deploy.sh
```

### 9. Troubleshooting

#### Python Script Issues:
```bash
# Check Python environment
/var/www/thoriq/venv/bin/python --version
/var/www/thoriq/venv/bin/python -c "import mysql.connector, numpy, requests; print('All packages OK')"

# Check script permissions
ls -la /var/www/thoriq/script/warehouse_optimization.py

# Test script manually
sudo -u www-data /var/www/thoriq/venv/bin/python /var/www/thoriq/script/warehouse_optimization.py --help
```

#### Laravel Issues:
```bash
# Check Laravel logs
tail -f /var/www/thoriq/storage/logs/laravel.log

# Check PHP-FPM logs
sudo tail -f /var/log/php8.2-fpm.log

# Test API endpoint
curl -H "Accept: application/json" http://your-domain.com/api/optimization/algorithms
```

#### Database Issues:
```bash
# Check MySQL status
sudo systemctl status mysql

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### 10. Security Checklist

- [ ] `.env` file permissions: `chmod 600 .env`
- [ ] Database user has minimal required permissions
- [ ] Firewall configured (UFW): `sudo ufw allow 22,80,443`
- [ ] Regular security updates: `sudo apt update && sudo apt upgrade`
- [ ] SSL certificate installed and auto-renewal configured
- [ ] API rate limiting configured in Laravel
- [ ] Strong database passwords
- [ ] Regular database backups

### 11. Performance Optimization

```bash
# Install Redis for caching (optional)
sudo apt install redis-server

# Update .env for Redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Install PHP Redis extension
sudo apt install php8.2-redis
sudo systemctl restart php8.2-fpm
```

---

## 🎉 Deployment Complete!

Your Warehouse Optimization API is now live at:
- **Main API**: `https://your-domain.com/api`
- **Documentation**: `https://your-domain.com/api/documentation`
- **Health Check**: `https://your-domain.com/api/optimization/algorithms`

The system now supports both local development and production deployment with configurable Python paths! 🚀