#!/bin/bash

# Complete VPS Setup Script for Attendance Management System
# For Ubuntu 22.04 LTS on VPS IP: 168.231.121.98
# Domain: absensi.manufac.id
# Usage: ./setup-vps.sh

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
VPS_IP="168.231.121.98"
DOMAIN="absensi.manufac.id"
APP_DIR="/var/www/attendance-system"
DB_NAME="attendance_system"
DB_USER="attendance_user"
DB_PASSWORD=$(openssl rand -base64 32)
REDIS_PASSWORD=$(openssl rand -base64 32)

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
   print_error "Please run this script as root"
   exit 1
fi

echo "🚀 Starting VPS setup for Attendance Management System..."
echo "📍 VPS IP: $VPS_IP"
echo "🌐 Domain: $DOMAIN"
echo ""

# 1. Update system packages
echo "📦 Updating system packages..."
apt update && apt upgrade -y
print_status "System updated"

# 2. Install essential packages
echo "📚 Installing essential packages..."
apt install -y curl wget unzip software-properties-common apt-transport-https lsb-release ca-certificates gnupg2
apt install -y git htop nano ufw fail2ban
print_status "Essential packages installed"

# 3. Install PHP 8.2
echo "🐘 Installing PHP 8.2..."
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath php8.2-json php8.2-intl php8.2-pgsql php8.2-redis php8.2-sqlite3

# Configure PHP
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 64M/' /etc/php/8.2/fpm/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 64M/' /etc/php/8.2/fpm/php.ini
sed -i 's/max_execution_time = 30/max_execution_time = 300/' /etc/php/8.2/fpm/php.ini
sed -i 's/memory_limit = 128M/memory_limit = 512M/' /etc/php/8.2/fpm/php.ini

systemctl enable php8.2-fpm
systemctl start php8.2-fpm
print_status "PHP 8.2 installed and configured"

# 4. Install Composer
echo "📝 Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
print_status "Composer installed"

# 5. Install Node.js 20
echo "📦 Installing Node.js 20..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
npm install -g npm@latest
print_status "Node.js 20 installed"

# 6. Install PostgreSQL 15
echo "🐘 Installing PostgreSQL 15..."
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -
echo "deb http://apt.postgresql.org/pub/repos/apt/ $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list
apt update
apt install -y postgresql-15 postgresql-client-15 postgresql-contrib-15

# Configure PostgreSQL
systemctl enable postgresql
systemctl start postgresql

# Create database and user
sudo -u postgres psql << EOF
CREATE DATABASE $DB_NAME;
CREATE USER $DB_USER WITH ENCRYPTED PASSWORD '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
ALTER USER $DB_USER CREATEDB;
\q
EOF

print_status "PostgreSQL 15 installed and configured"

# 7. Install Redis
echo "📦 Installing Redis..."
apt install -y redis-server
sed -i 's/# requirepass foobared/requirepass '$REDIS_PASSWORD'/' /etc/redis/redis.conf
sed -i 's/bind 127.0.0.1 ::1/bind 127.0.0.1/' /etc/redis/redis.conf
systemctl enable redis-server
systemctl restart redis-server
print_status "Redis installed and configured"

# 8. Install Nginx
echo "🌐 Installing Nginx..."
apt install -y nginx
systemctl enable nginx
systemctl start nginx

# Create Nginx configuration
cat > /etc/nginx/sites-available/$DOMAIN << 'EOF'
server {
    listen 80;
    server_name absensi.manufac.id 168.231.121.98;
    root /var/www/attendance-system/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    # Security headers
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https:; connect-src 'self' https:;";

    # Handle Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM Configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Security: deny access to sensitive files
    location ~ /\.(ht|git|env) {
        deny all;
    }

    location ~ /storage/.* {
        deny all;
    }

    # Static files caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Logs
    error_log /var/log/nginx/absensi.manufac.id.error.log;
    access_log /var/log/nginx/absensi.manufac.id.access.log;
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx
print_status "Nginx installed and configured"

# 9. Install Supervisor
echo "⚙️ Installing Supervisor..."
apt install -y supervisor
systemctl enable supervisor
systemctl start supervisor
print_status "Supervisor installed"

# 10. Install Certbot for SSL
echo "🔒 Installing Certbot..."
apt install -y certbot python3-certbot-nginx
print_status "Certbot installed"

# 11. Configure Firewall
echo "🛡️ Configuring UFW firewall..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 'Nginx Full'
ufw --force enable
print_status "Firewall configured"

# 12. Configure Fail2ban
echo "🛡️ Configuring Fail2ban..."
cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true
port = ssh
logpath = /var/log/auth.log
maxretry = 3

[nginx-http-auth]
enabled = true
filter = nginx-http-auth
port = http,https
logpath = /var/log/nginx/error.log

[nginx-limit-req]
enabled = true
filter = nginx-limit-req
port = http,https
logpath = /var/log/nginx/error.log
maxretry = 10
EOF

systemctl enable fail2ban
systemctl restart fail2ban
print_status "Fail2ban configured"

# 13. Create application directory and set permissions
echo "📁 Setting up application directory..."
mkdir -p $APP_DIR
mkdir -p /var/log/attendance-system
mkdir -p /var/backups/attendance-system

# Create deploy user
if ! id "deploy" &>/dev/null; then
    useradd -m -s /bin/bash deploy
    usermod -aG www-data deploy
    print_status "Deploy user created"
fi

# Set ownership
chown -R deploy:www-data $APP_DIR
chown -R deploy:www-data /var/log/attendance-system
chmod -R 755 $APP_DIR
print_status "Permissions set"

# 14. Create Laravel Queue supervisor configuration
cat > /etc/supervisor/conf.d/attendance-queue.conf << 'EOF'
[program:attendance-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/attendance-system/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
directory=/var/www/attendance-system
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deploy
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/attendance-system/queue.log
stopwaitsecs=3600
EOF

# 15. Create Laravel Scheduler cron
cat > /etc/cron.d/attendance-scheduler << 'EOF'
* * * * * deploy cd /var/www/attendance-system && php artisan schedule:run >> /dev/null 2>&1
EOF

print_status "Laravel configurations created"

# 16. Create health check endpoint
mkdir -p $APP_DIR/public/api
cat > $APP_DIR/public/api/health << 'EOF'
#!/bin/bash
echo "Content-Type: application/json"
echo ""
echo '{"status":"ok","timestamp":"'$(date -u +%Y-%m-%dT%H:%M:%SZ)'"}'
EOF
chmod +x $APP_DIR/public/api/health

# 17. Install Git and initialize repository
echo "📥 Preparing for application deployment..."
su - deploy -c "cd $APP_DIR && git init"
print_status "Git repository initialized"

# 18. Create environment file with generated passwords
cat > $APP_DIR/.env.production << EOF
APP_NAME="Sistem Absensi Manufac"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://$DOMAIN

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASSWORD

SESSION_DRIVER=redis
SESSION_LIFETIME=43200
SESSION_ENCRYPT=true
SESSION_DOMAIN=.$DOMAIN

CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=$REDIS_PASSWORD
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@manufac.id"
MAIL_FROM_NAME="Sistem Absensi Manufac"

PAYROLL_STANDARD_HOURS_PER_DAY=8
PAYROLL_WORKING_DAYS_PER_MONTH=22
PAYROLL_OVERTIME_MULTIPLIER=1.5
PAYROLL_PAY_DATE_DAY=15

PAYROLL_TAX_BRACKET_1_MAX=5000000
PAYROLL_TAX_BRACKET_1_RATE=5
PAYROLL_TAX_BRACKET_2_MAX=20000000
PAYROLL_TAX_BRACKET_2_RATE=15
PAYROLL_TAX_BRACKET_3_MAX=50000000
PAYROLL_TAX_BRACKET_3_RATE=25
PAYROLL_TAX_BRACKET_4_RATE=30

PAYROLL_MINIMUM_WAGE=3500000
PAYROLL_MAX_HOURS_PER_PERIOD=200
PAYROLL_MAX_OVERTIME_HOURS=60
EOF

chown deploy:www-data $APP_DIR/.env.production
chmod 600 $APP_DIR/.env.production

# 19. Create deployment script
cat > $APP_DIR/deploy.sh << 'DEPLOY_SCRIPT'
#!/bin/bash

# Deployment script for Attendance Management System
# Usage: ./deploy.sh

set -e

echo "🚀 Starting deployment..."

# Variables
APP_DIR="/var/www/attendance-system"
BACKUP_DIR="/var/backups/attendance-system"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Check if running as deploy user
if [ "$USER" != "deploy" ]; then
   print_error "Please run this script as deploy user: su - deploy"
   exit 1
fi

# 1. Create backup
echo "📦 Creating backup..."
if [ -d "$APP_DIR" ] && [ -f "$APP_DIR/composer.json" ]; then
    mkdir -p "$BACKUP_DIR"
    tar -czf "$BACKUP_DIR/backup_${TIMESTAMP}.tar.gz" -C "$APP_DIR" . --exclude=node_modules --exclude=.git
    print_status "Backup created: backup_${TIMESTAMP}.tar.gz"
else
    print_status "No existing installation found, skipping backup"
fi

# 2. Pull latest code
echo "📥 Pulling latest code..."
cd "$APP_DIR"
git pull origin main || print_status "Git pull completed"

# 3. Install/update dependencies
echo "📚 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
print_status "PHP dependencies installed"

npm ci --production
print_status "Node dependencies installed"

# 4. Build frontend assets
echo "🔨 Building frontend assets..."
npm run build
print_status "Assets built"

# 5. Setup production environment
echo "⚙️ Setting up production environment..."
cp .env.production .env
php artisan key:generate --force
print_status "Environment configured"

# 6. Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force
print_status "Database migrated"

# 7. Clear and optimize caches
echo "🧹 Optimizing application..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Optimize autoloader
composer dump-autoloader --optimize --no-dev

print_status "Application optimized"

# 8. Set proper permissions
echo "🔒 Setting permissions..."
sudo chown -R deploy:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
print_status "Permissions set"

# 9. Restart services
echo "🔄 Restarting services..."
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
sudo supervisorctl restart all
print_status "Services restarted"

# 10. Run health check
echo "🏥 Running health check..."
response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/health)
if [ $response -eq 200 ]; then
    print_status "Health check passed"
else
    print_error "Health check failed with status code: $response"
    exit 1
fi

echo ""
echo "✅ Deployment completed successfully!"
echo "🌐 Application URL: https://absensi.manufac.id"
echo "📊 Deployment timestamp: ${TIMESTAMP}"
DEPLOY_SCRIPT

chmod +x $APP_DIR/deploy.sh
chown deploy:www-data $APP_DIR/deploy.sh

# 20. Restart all services
echo "🔄 Restarting all services..."
systemctl restart php8.2-fpm
systemctl restart nginx
systemctl restart postgresql
systemctl restart redis-server
supervisorctl reread
supervisorctl update
print_status "All services restarted"

# 21. Final setup complete
echo ""
echo "✅ VPS setup completed successfully!"
echo ""
echo "📋 Setup Summary:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🌐 Domain: $DOMAIN"
echo "📍 VPS IP: $VPS_IP"
echo "📁 App Directory: $APP_DIR"
echo "🗄️  Database: $DB_NAME"
echo "👤 DB User: $DB_USER"
echo "🔑 DB Password: $DB_PASSWORD"
echo "🔑 Redis Password: $REDIS_PASSWORD"
echo ""
echo "📝 Next Steps:"
echo "1. Upload your code to $APP_DIR"
echo "2. Run: su - deploy"
echo "3. Run: cd $APP_DIR && ./deploy.sh"
echo "4. Configure SSL: certbot --nginx -d $DOMAIN"
echo ""
echo "📚 Important Files:"
echo "• App Config: $APP_DIR/.env.production"
echo "• Deploy Script: $APP_DIR/deploy.sh"
echo "• Nginx Config: /etc/nginx/sites-available/$DOMAIN"
echo "• Queue Config: /etc/supervisor/conf.d/attendance-queue.conf"
echo ""
print_warning "Save the database and Redis passwords in a secure location!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"