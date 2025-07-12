#!/bin/bash

# VPS Setup Script for Attendance Management System
# Run this on a fresh Ubuntu 22.04 VPS
# Usage: sudo bash setup-vps.sh

set -e

# Colors
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

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

echo "🚀 Starting VPS setup for Attendance Management System..."

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
   print_error "Please run as root (use sudo)"
   exit 1
fi

# 1. Update system
echo "📦 Updating system packages..."
apt update && apt upgrade -y
print_status "System updated"

# 2. Install required packages
echo "📚 Installing required packages..."
apt install -y nginx postgresql redis-server supervisor git curl wget unzip software-properties-common

# Add PHP repository
add-apt-repository ppa:ondrej/php -y
apt update

# Install PHP and extensions
apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-pgsql php8.2-redis \
    php8.2-mbstring php8.2-xml php8.2-curl php8.2-gd php8.2-bcmath \
    php8.2-zip php8.2-intl php8.2-readline php8.2-msgpack php8.2-igbinary

print_status "Packages installed"

# 3. Install Composer
echo "🎼 Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
print_status "Composer installed"

# 4. Install Node.js
echo "📦 Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs
print_status "Node.js installed"

# 5. Configure PostgreSQL
echo "🐘 Configuring PostgreSQL..."
sudo -u postgres psql <<EOF
CREATE USER attendance_user WITH PASSWORD 'your_secure_password_here';
CREATE DATABASE attendance_production OWNER attendance_user;
GRANT ALL PRIVILEGES ON DATABASE attendance_production TO attendance_user;
EOF
print_status "PostgreSQL configured"

# 6. Create application user
echo "👤 Creating application user..."
useradd -m -s /bin/bash attendance
usermod -aG www-data attendance
print_status "Application user created"

# 7. Create directory structure
echo "📁 Creating directory structure..."
mkdir -p /var/www/attendance-system
mkdir -p /var/log/attendance
mkdir -p /var/backups/attendance-system
chown -R attendance:www-data /var/www/attendance-system
chown -R attendance:www-data /var/log/attendance
print_status "Directories created"

# 8. Configure Nginx
echo "🌐 Configuring Nginx..."
cat > /etc/nginx/sites-available/attendance <<'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;
    root /var/www/attendance-system/public;

    # SSL configuration (update with your certificates)
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    index index.php;

    charset utf-8;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

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
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Max upload size
    client_max_body_size 50M;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml application/atom+xml image/svg+xml;

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|doc|docx|xls|xlsx)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Logs
    access_log /var/log/nginx/attendance_access.log;
    error_log /var/log/nginx/attendance_error.log;
}
EOF

ln -s /etc/nginx/sites-available/attendance /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
print_status "Nginx configured"

# 9. Configure PHP-FPM
echo "⚙️ Configuring PHP-FPM..."
cat > /etc/php/8.2/fpm/pool.d/attendance.conf <<'EOF'
[attendance]
user = www-data
group = www-data
listen = /var/run/php/php8.2-fpm-attendance.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

php_admin_value[error_log] = /var/log/attendance/php-error.log
php_admin_flag[log_errors] = on
php_admin_value[memory_limit] = 256M
php_admin_value[upload_max_filesize] = 50M
php_admin_value[post_max_size] = 50M
php_admin_value[max_execution_time] = 300
EOF
print_status "PHP-FPM configured"

# 10. Configure Supervisor for queue workers
echo "👷 Configuring Supervisor..."
cat > /etc/supervisor/conf.d/attendance-worker.conf <<'EOF'
[program:attendance-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/attendance-system/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=attendance
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/attendance/worker.log
stopwaitsecs=3600
EOF
print_status "Supervisor configured"

# 11. Configure Redis
echo "📮 Configuring Redis..."
sed -i 's/# maxmemory <bytes>/maxmemory 256mb/' /etc/redis/redis.conf
sed -i 's/# maxmemory-policy noeviction/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
systemctl restart redis-server
print_status "Redis configured"

# 12. Configure firewall
echo "🔥 Configuring firewall..."
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
print_status "Firewall configured"

# 13. Create swap file
echo "💾 Creating swap file..."
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
print_status "Swap file created"

# 14. Install Certbot for SSL
echo "🔒 Installing Certbot..."
snap install --classic certbot
ln -s /snap/bin/certbot /usr/bin/certbot
print_status "Certbot installed"

# 15. Create deployment key
echo "🔑 Creating deployment key..."
sudo -u attendance ssh-keygen -t ed25519 -f /home/attendance/.ssh/id_ed25519 -N ""
print_status "Deployment key created"

# 16. Set up log rotation
echo "📋 Setting up log rotation..."
cat > /etc/logrotate.d/attendance <<'EOF'
/var/log/attendance/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 attendance www-data
    sharedscripts
    postrotate
        systemctl reload php8.2-fpm > /dev/null
    endscript
}
EOF
print_status "Log rotation configured"

# 17. Restart services
echo "🔄 Restarting services..."
systemctl restart php8.2-fpm
systemctl restart nginx
systemctl restart redis-server
supervisorctl reread
supervisorctl update
print_status "Services restarted"

echo ""
echo "✅ VPS setup completed!"
echo ""
echo "📋 Next steps:"
echo "1. Add the deployment key to your Git repository:"
echo "   cat /home/attendance/.ssh/id_ed25519.pub"
echo ""
echo "2. Clone your repository:"
echo "   sudo -u attendance git clone git@github.com:your-repo/attendance-system.git /var/www/attendance-system"
echo ""
echo "3. Copy .env.production and configure it:"
echo "   cd /var/www/attendance-system"
echo "   cp .env.production.example .env"
echo "   php artisan key:generate"
echo ""
echo "4. Run initial deployment:"
echo "   composer install --no-dev --optimize-autoloader"
echo "   npm ci --production"
echo "   npm run build"
echo "   php artisan migrate --seed"
echo "   php artisan storage:link"
echo ""
echo "5. Set up SSL certificate:"
echo "   certbot --nginx -d your-domain.com"
echo ""
echo "6. Update Nginx config with your domain and SSL paths"
echo ""
echo "🔒 Security reminder: Change the PostgreSQL password!"