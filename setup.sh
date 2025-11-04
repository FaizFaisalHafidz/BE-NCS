#!/bin/bash

# NCS Warehouse Management System - Quick Setup Script
# Author: Faiz Faisal Hafidz
# Description: Script untuk setup otomatis project NCS

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# ASCII Art Logo
echo -e "${BLUE}"
cat << "EOF"
███╗   ██╗ ██████╗███████╗    ██╗    ██╗ █████╗ ██████╗ ███████╗██╗  ██╗ ██████╗ ██╗   ██╗███████╗███████╗
████╗  ██║██╔════╝██╔════╝    ██║    ██║██╔══██╗██╔══██╗██╔════╝██║  ██║██╔═══██╗██║   ██║██╔════╝██╔════╝
██╔██╗ ██║██║     ███████╗    ██║ █╗ ██║███████║██████╔╝█████╗  ███████║██║   ██║██║   ██║███████╗█████╗  
██║╚██╗██║██║     ╚════██║    ██║███╗██║██╔══██║██╔══██╗██╔══╝  ██╔══██║██║   ██║██║   ██║╚════██║██╔══╝  
██║ ╚████║╚██████╗███████║    ╚███╔███╔╝██║  ██║██║  ██║███████╗██║  ██║╚██████╔╝╚██████╔╝███████║███████╗
╚═╝  ╚═══╝ ╚═════╝╚══════╝     ╚══╝╚══╝ ╚═╝  ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝ ╚═════╝  ╚═════╝ ╚══════╝╚══════╝
EOF
echo -e "${NC}"

echo -e "${CYAN}================================================${NC}"
echo -e "${CYAN}    NCS Warehouse Management System Setup      ${NC}"
echo -e "${CYAN}================================================${NC}"
echo ""

# Function to print status
print_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $2"
    else
        echo -e "${RED}✗${NC} $2"
        exit 1
    fi
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    print_warning "Jangan menjalankan script ini sebagai root!"
    exit 1
fi

# Check current directory
if [ ! -f "composer.json" ]; then
    echo -e "${RED}Error: Script harus dijalankan dari root directory project${NC}"
    echo "Pastikan Anda berada di directory yang mengandung file composer.json"
    exit 1
fi

echo -e "${YELLOW}Step 1: Checking system requirements...${NC}"

# Check PHP
print_info "Checking PHP version..."
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
if [ $(echo "$PHP_VERSION >= 8.2" | bc -l) -eq 1 ]; then
    print_status 0 "PHP $PHP_VERSION found"
else
    print_status 1 "PHP 8.2+ required, found $PHP_VERSION"
fi

# Check Composer
print_info "Checking Composer..."
if command -v composer >/dev/null 2>&1; then
    COMPOSER_VERSION=$(composer --version | cut -d' ' -f3)
    print_status 0 "Composer $COMPOSER_VERSION found"
else
    print_status 1 "Composer not found. Please install Composer first."
fi

# Check MySQL
print_info "Checking MySQL..."
if command -v mysql >/dev/null 2>&1; then
    MYSQL_VERSION=$(mysql --version | cut -d' ' -f6 | cut -d',' -f1)
    print_status 0 "MySQL $MYSQL_VERSION found"
else
    print_warning "MySQL not found. Make sure MySQL/MariaDB is installed."
fi

# Check Python (optional)
print_info "Checking Python (for optimization)..."
if command -v python3 >/dev/null 2>&1; then
    PYTHON_VERSION=$(python3 --version | cut -d' ' -f2)
    print_status 0 "Python $PYTHON_VERSION found"
else
    print_warning "Python3 not found. Optimization features may not work."
fi

echo ""
echo -e "${YELLOW}Step 2: Installing PHP dependencies...${NC}"

# Install Composer dependencies
print_info "Running composer install..."
composer install --no-dev --optimize-autoloader
print_status $? "Composer dependencies installed"

echo ""
echo -e "${YELLOW}Step 3: Environment configuration...${NC}"

# Copy .env file
if [ ! -f ".env" ]; then
    print_info "Creating .env file..."
    cp .env.example .env
    print_status $? ".env file created"
else
    print_warning ".env file already exists, skipping..."
fi

# Generate app key
print_info "Generating application key..."
php artisan key:generate --force
print_status $? "Application key generated"

echo ""
echo -e "${YELLOW}Step 4: Database configuration...${NC}"

# Prompt for database configuration
echo -e "${CYAN}Please enter your database configuration:${NC}"
read -p "Database name (default: ncs_warehouse): " DB_NAME
DB_NAME=${DB_NAME:-ncs_warehouse}

read -p "Database host (default: 127.0.0.1): " DB_HOST
DB_HOST=${DB_HOST:-127.0.0.1}

read -p "Database port (default: 3306): " DB_PORT
DB_PORT=${DB_PORT:-3306}

read -p "Database username (default: root): " DB_USER
DB_USER=${DB_USER:-root}

read -s -p "Database password: " DB_PASS
echo ""

# Update .env file
print_info "Updating database configuration..."
sed -i.bak "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
sed -i.bak "s/DB_HOST=.*/DB_HOST=$DB_HOST/" .env
sed -i.bak "s/DB_PORT=.*/DB_PORT=$DB_PORT/" .env
sed -i.bak "s/DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
sed -i.bak "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
rm .env.bak
print_status 0 "Database configuration updated"

# Test database connection
print_info "Testing database connection..."
php artisan migrate:status >/dev/null 2>&1
if [ $? -eq 0 ]; then
    print_status 0 "Database connection successful"
else
    print_warning "Database connection failed. Please check your configuration."
    echo -e "${YELLOW}You can manually edit .env file later and run:${NC}"
    echo "php artisan migrate"
fi

echo ""
echo -e "${YELLOW}Step 5: Database setup...${NC}"

# Ask for migration
echo -e "${CYAN}Do you want to run database migrations? (y/N):${NC}"
read -r response
if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    print_info "Running database migrations..."
    php artisan migrate --force
    print_status $? "Database migrations completed"
    
    # Ask for seeding
    echo -e "${CYAN}Do you want to seed the database with sample data? (y/N):${NC}"
    read -r response
    if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        print_info "Seeding database..."
        php artisan db:seed --force
        print_status $? "Database seeding completed"
    fi
else
    print_warning "Skipping database migrations. Run 'php artisan migrate' manually later."
fi

echo ""
echo -e "${YELLOW}Step 6: Python dependencies (optional)...${NC}"

if command -v python3 >/dev/null 2>&1; then
    echo -e "${CYAN}Do you want to install Python dependencies for optimization? (y/N):${NC}"
    read -r response
    if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        if [ -f "script/requirements.txt" ]; then
            print_info "Installing Python dependencies for optimization..."
            cd script
            python3 -m pip install -r requirements.txt
            cd ..
            print_status $? "Python optimization dependencies installed"
        fi
        
        if [ -f "diagram_generator/requirements.txt" ]; then
            print_info "Installing Python dependencies for diagram generator..."
            cd diagram_generator
            python3 -m pip install -r requirements.txt
            cd ..
            print_status $? "Python diagram dependencies installed"
        fi
    fi
else
    print_warning "Python3 not found, skipping Python dependencies"
fi

echo ""
echo -e "${YELLOW}Step 7: Generate API documentation...${NC}"

print_info "Generating Swagger documentation..."
php artisan l5-swagger:generate
print_status $? "API documentation generated"

echo ""
echo -e "${YELLOW}Step 8: Final setup...${NC}"

# Create storage symlink
print_info "Creating storage symlink..."
php artisan storage:link
print_status $? "Storage symlink created"

# Clear and optimize
print_info "Optimizing application..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
print_status $? "Application optimized"

# Set permissions (if not on Windows)
if [[ "$OSTYPE" != "msys" && "$OSTYPE" != "cygwin" ]]; then
    print_info "Setting permissions..."
    chmod -R 775 storage bootstrap/cache
    print_status $? "Permissions set"
fi

echo ""
echo -e "${GREEN}================================================${NC}"
echo -e "${GREEN}          🎉 SETUP COMPLETED! 🎉               ${NC}"
echo -e "${GREEN}================================================${NC}"
echo ""

echo -e "${CYAN}📋 Next Steps:${NC}"
echo ""
echo -e "${YELLOW}1. Start the development server:${NC}"
echo "   php artisan serve"
echo ""
echo -e "${YELLOW}2. Access your application:${NC}"
echo "   🌐 Web: http://localhost:8000"
echo "   📚 API Docs: http://localhost:8000/api/documentation"
echo ""
echo -e "${YELLOW}3. Default login credentials (if seeded):${NC}"
echo "   📧 Email: admin@ncs.com"
echo "   🔑 Password: password123"
echo ""

if [ -f ".env" ]; then
    APP_URL=$(grep APP_URL .env | cut -d'=' -f2)
    if [ ! -z "$APP_URL" ]; then
        echo -e "${YELLOW}4. API Base URL:${NC}"
        echo "   $APP_URL/api"
        echo ""
    fi
fi

echo -e "${CYAN}🔧 Useful Commands:${NC}"
echo ""
echo -e "${YELLOW}Development:${NC}"
echo "   php artisan serve                    # Start development server"
echo "   php artisan migrate                  # Run database migrations"
echo "   php artisan db:seed                  # Seed database"
echo "   php artisan l5-swagger:generate      # Generate API docs"
echo ""
echo -e "${YELLOW}Testing:${NC}"
echo "   ./script/test_auth_api.sh            # Test authentication API"
echo "   ./script/test_analytics_api.sh       # Test analytics API"
echo "   php artisan test                     # Run unit tests"
echo ""
echo -e "${YELLOW}Optimization:${NC}"
echo "   cd script && python3 warehouse_optimization.py"
echo ""
echo -e "${YELLOW}Maintenance:${NC}"
echo "   php artisan optimize:clear           # Clear all cache"
echo "   php artisan queue:work               # Run queue worker"
echo ""

# Create a quick start script
cat > quick-start.sh << 'EOF'
#!/bin/bash
echo "🚀 Starting NCS Warehouse Management System..."
echo ""
echo "📊 Checking system status..."

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "❌ .env file not found. Please run setup.sh first."
    exit 1
fi

# Check database connection
php artisan migrate:status >/dev/null 2>&1
if [ $? -ne 0 ]; then
    echo "❌ Database connection failed. Please check your .env configuration."
    exit 1
fi

echo "✅ System ready!"
echo ""
echo "🌐 Starting development server..."
echo "📍 Server will be available at: http://localhost:8000"
echo "📚 API Documentation: http://localhost:8000/api/documentation"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

php artisan serve
EOF

chmod +x quick-start.sh

echo -e "${PURPLE}💡 Pro Tips:${NC}"
echo "   • Use './quick-start.sh' to start the server quickly"
echo "   • Check README.md for detailed documentation"
echo "   • Use Swagger UI for API testing and exploration"
echo "   • Monitor logs in storage/logs/ for debugging"
echo ""

echo -e "${GREEN}🎯 Setup completed successfully! Happy coding! 🚀${NC}"
echo ""