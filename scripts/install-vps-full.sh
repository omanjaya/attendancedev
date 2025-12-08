#!/bin/bash

#===============================================================================
# 🚀 ATTENDANCE SYSTEM - COMPLETE VPS INSTALLATION SCRIPT
#===============================================================================
#
# Script ini akan menginstall dan mengkonfigurasi SELURUH sistem:
# - PHP 8.2 + Extensions
# - PostgreSQL 15 (optimized)
# - Redis 7
# - Nginx
# - Node.js 20
# - Python 3 + DeepFace
# - Supervisor
# - SSL (Let's Encrypt)
# - Firewall (UFW)
# - Fail2ban
#
# Usage:
#   sudo bash install-vps.sh
#
# Tested on: Ubuntu 22.04 LTS
#
#===============================================================================

set -e

#-------------------------------------------------------------------------------
# CONFIGURATION - EDIT SESUAI KEBUTUHAN
#-------------------------------------------------------------------------------

# Domain dan IP (ganti sesuai VPS kamu)
DOMAIN="${DOMAIN:-absensi.yourdomain.com}"
VPS_IP="${VPS_IP:-$(curl -s ifconfig.me)}"

# Directory paths
APP_DIR="/var/www/attendance-system"
LOG_DIR="/var/log/attendance-system"
BACKUP_DIR="/var/backups/attendance-system"

# Database
DB_NAME="attendance_system"
DB_USER="attendance_user"
DB_PASSWORD=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9' | head -c 32)

# Redis
REDIS_PASSWORD=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9' | head -c 32)

# App key will be generated
APP_KEY=""

#-------------------------------------------------------------------------------
# COLORS
#-------------------------------------------------------------------------------

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

#-------------------------------------------------------------------------------
# HELPER FUNCTIONS
#-------------------------------------------------------------------------------

print_header() {
    echo ""
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  $1${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

print_step() {
    echo -e "${BLUE}▶${NC} $1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "Please run this script as root: sudo bash install-vps.sh"
        exit 1
    fi
}

#-------------------------------------------------------------------------------
# MAIN INSTALLATION
#-------------------------------------------------------------------------------

main() {
    check_root

    print_header "🚀 ATTENDANCE SYSTEM VPS INSTALLATION"
    echo ""
    echo "  Domain: $DOMAIN"
    echo "  VPS IP: $VPS_IP"
    echo "  App Directory: $APP_DIR"
    echo ""
    
    read -p "Press Enter to continue or Ctrl+C to cancel..."
    
    install_system_packages
    install_php
    install_postgresql
    install_redis
    install_nodejs
    install_nginx
    install_python_deepface
    install_supervisor
    install_certbot
    configure_firewall
    configure_fail2ban
    setup_directories
    deploy_application
    configure_supervisor
    configure_cron
    final_setup
    
    print_summary
}

#-------------------------------------------------------------------------------
# STEP 1: System Packages
#-------------------------------------------------------------------------------

install_system_packages() {
    print_header "📦 Step 1: Installing System Packages"
    
    print_step "Updating system..."
    apt update && apt upgrade -y
    
    print_step "Installing essential packages..."
    apt install -y \
        curl wget unzip git htop nano \
        software-properties-common apt-transport-https \
        lsb-release ca-certificates gnupg2 \
        build-essential libssl-dev libffi-dev
    
    print_success "System packages installed"
}

#-------------------------------------------------------------------------------
# STEP 2: PHP 8.2
#-------------------------------------------------------------------------------

install_php() {
    print_header "🐘 Step 2: Installing PHP 8.2"
    
    print_step "Adding PHP repository..."
    add-apt-repository ppa:ondrej/php -y
    apt update
    
    print_step "Installing PHP and extensions..."
    apt install -y \
        php8.2-fpm php8.2-cli php8.2-common \
        php8.2-pgsql php8.2-mysql php8.2-sqlite3 \
        php8.2-mbstring php8.2-xml php8.2-curl \
        php8.2-gd php8.2-intl php8.2-zip \
        php8.2-bcmath php8.2-redis php8.2-opcache
    
    print_step "Configuring PHP..."
    
    # PHP-FPM config
    cat > /etc/php/8.2/fpm/conf.d/99-attendance.ini << 'EOF'
; Performance
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.fast_shutdown=1

; Limits
upload_max_filesize=64M
post_max_size=64M
max_execution_time=300
memory_limit=512M
max_input_time=300

; Timezone
date.timezone=Asia/Jakarta
EOF

    # PHP-FPM pool config
    cat > /etc/php/8.2/fpm/pool.d/attendance.conf << 'EOF'
[attendance]
user = www-data
group = www-data
listen = /run/php/php8.2-fpm-attendance.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500

; Slow log for debugging
slowlog = /var/log/php-slow.log
request_slowlog_timeout = 10s

; Environment
env[HOSTNAME] = $HOSTNAME
env[PATH] = /usr/local/bin:/usr/bin:/bin
env[TMP] = /tmp
env[TMPDIR] = /tmp
env[TEMP] = /tmp
EOF

    systemctl enable php8.2-fpm
    systemctl restart php8.2-fpm
    
    # Install Composer
    print_step "Installing Composer..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    
    print_success "PHP 8.2 installed and configured"
}

#-------------------------------------------------------------------------------
# STEP 3: PostgreSQL 15
#-------------------------------------------------------------------------------

install_postgresql() {
    print_header "🐘 Step 3: Installing PostgreSQL 15"
    
    print_step "Adding PostgreSQL repository..."
    wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -
    echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list
    apt update
    
    print_step "Installing PostgreSQL..."
    apt install -y postgresql-15 postgresql-client-15 postgresql-contrib-15
    
    print_step "Configuring PostgreSQL for performance..."
    
    # Get total RAM in MB
    TOTAL_RAM=$(free -m | awk '/^Mem:/{print $2}')
    SHARED_BUFFERS=$((TOTAL_RAM / 4))MB
    EFFECTIVE_CACHE=$((TOTAL_RAM * 3 / 4))MB
    WORK_MEM=$((TOTAL_RAM / 100))MB
    
    cat > /etc/postgresql/15/main/conf.d/performance.conf << EOF
# Connection Settings
max_connections = 200
superuser_reserved_connections = 3

# Memory Settings (based on ${TOTAL_RAM}MB RAM)
shared_buffers = ${SHARED_BUFFERS}
effective_cache_size = ${EFFECTIVE_CACHE}
maintenance_work_mem = 256MB
work_mem = ${WORK_MEM}

# Write Ahead Log
wal_buffers = 16MB
checkpoint_completion_target = 0.9
max_wal_size = 2GB
min_wal_size = 1GB

# Query Planner
random_page_cost = 1.1
effective_io_concurrency = 200
default_statistics_target = 100

# Logging
log_min_duration_statement = 1000
log_checkpoints = on
log_connections = on
log_disconnections = on
log_lock_waits = on
log_statement = 'ddl'

# Locale
timezone = 'Asia/Jakarta'
EOF

    systemctl enable postgresql
    systemctl restart postgresql
    
    print_step "Creating database and user..."
    sudo -u postgres psql << EOF
CREATE DATABASE ${DB_NAME};
CREATE USER ${DB_USER} WITH ENCRYPTED PASSWORD '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};
ALTER USER ${DB_USER} CREATEDB;
\c ${DB_NAME}
GRANT ALL ON SCHEMA public TO ${DB_USER};
EOF
    
    print_success "PostgreSQL 15 installed and configured"
}

#-------------------------------------------------------------------------------
# STEP 4: Redis 7
#-------------------------------------------------------------------------------

install_redis() {
    print_header "📦 Step 4: Installing Redis"
    
    print_step "Installing Redis..."
    apt install -y redis-server
    
    print_step "Configuring Redis..."
    
    # Backup original config
    cp /etc/redis/redis.conf /etc/redis/redis.conf.backup
    
    # Get RAM for maxmemory (use 25% of RAM)
    TOTAL_RAM=$(free -m | awk '/^Mem:/{print $2}')
    MAX_MEMORY=$((TOTAL_RAM / 4))mb
    
    cat > /etc/redis/redis.conf << EOF
# Network
bind 127.0.0.1
port 6379
protected-mode yes

# Security
requirepass ${REDIS_PASSWORD}

# Memory
maxmemory ${MAX_MEMORY}
maxmemory-policy allkeys-lru

# Persistence (AOF for durability)
appendonly yes
appendfilename "appendonly.aof"
appendfsync everysec
auto-aof-rewrite-percentage 100
auto-aof-rewrite-min-size 64mb

# RDB snapshots
save 900 1
save 300 10
save 60 10000

# Logging
loglevel notice
logfile /var/log/redis/redis-server.log

# Performance
tcp-keepalive 300
timeout 0
databases 16
EOF

    systemctl enable redis-server
    systemctl restart redis-server
    
    print_success "Redis installed and configured"
}

#-------------------------------------------------------------------------------
# STEP 5: Node.js 20
#-------------------------------------------------------------------------------

install_nodejs() {
    print_header "📦 Step 5: Installing Node.js 20"
    
    print_step "Installing Node.js..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt install -y nodejs
    
    print_step "Installing npm packages..."
    npm install -g npm@latest
    npm install -g pm2
    
    print_success "Node.js $(node -v) installed"
}

#-------------------------------------------------------------------------------
# STEP 6: Nginx
#-------------------------------------------------------------------------------

install_nginx() {
    print_header "🌐 Step 6: Installing Nginx"
    
    print_step "Installing Nginx..."
    apt install -y nginx
    
    print_step "Configuring Nginx..."
    
    # Main Nginx config optimizations
    cat > /etc/nginx/conf.d/performance.conf << 'EOF'
# Performance
worker_rlimit_nofile 65535;

# Gzip
gzip on;
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml application/rss+xml application/atom+xml image/svg+xml;

# Buffer sizes
client_body_buffer_size 10K;
client_header_buffer_size 1k;
client_max_body_size 64m;
large_client_header_buffers 4 32k;

# Timeouts
client_body_timeout 12;
client_header_timeout 12;
keepalive_timeout 15;
send_timeout 10;

# Security headers
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
EOF

    # Site configuration
    cat > /etc/nginx/sites-available/attendance << EOF
server {
    listen 80;
    server_name ${DOMAIN} ${VPS_IP};
    root ${APP_DIR}/backend/public;
    
    index index.php;
    charset utf-8;
    
    # Security
    location ~ /\.(ht|git|env) {
        deny all;
    }
    
    # Laravel API
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    # PHP-FPM
    location ~ \.php\$ {
        fastcgi_pass unix:/run/php/php8.2-fpm-attendance.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        # Timeouts for long requests (face verification)
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
    }
    
    # Static files caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    
    # Storage files
    location /storage {
        alias ${APP_DIR}/backend/storage/app/public;
    }
    
    # Logs
    error_log /var/log/nginx/attendance.error.log;
    access_log /var/log/nginx/attendance.access.log;
}
EOF

    # Enable site
    ln -sf /etc/nginx/sites-available/attendance /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    nginx -t
    systemctl enable nginx
    systemctl restart nginx
    
    print_success "Nginx installed and configured"
}

#-------------------------------------------------------------------------------
# STEP 7: Python + DeepFace
#-------------------------------------------------------------------------------

install_python_deepface() {
    print_header "🐍 Step 7: Installing Python & DeepFace"
    
    print_step "Installing Python..."
    apt install -y python3 python3-pip python3-venv python3-dev
    
    print_step "Setting up DeepFace service..."
    
    # Create venv for DeepFace
    if [ -d "${APP_DIR}/python-services/face-recognition" ]; then
        cd ${APP_DIR}/python-services/face-recognition
        python3 -m venv venv
        source venv/bin/activate
        pip install --upgrade pip
        pip install -r requirements.txt 2>/dev/null || pip install flask deepface opencv-python-headless gunicorn
        deactivate
        print_success "DeepFace service configured"
    else
        print_warning "DeepFace directory not found, will be configured after deployment"
    fi
}

#-------------------------------------------------------------------------------
# STEP 8: Supervisor
#-------------------------------------------------------------------------------

install_supervisor() {
    print_header "⚙️ Step 8: Installing Supervisor"
    
    print_step "Installing Supervisor..."
    apt install -y supervisor
    
    systemctl enable supervisor
    systemctl start supervisor
    
    print_success "Supervisor installed"
}

#-------------------------------------------------------------------------------
# STEP 9: Certbot (SSL)
#-------------------------------------------------------------------------------

install_certbot() {
    print_header "🔒 Step 9: Installing Certbot"
    
    print_step "Installing Certbot..."
    apt install -y certbot python3-certbot-nginx
    
    print_warning "SSL will be configured after DNS is pointing to this server"
    print_warning "Run: sudo certbot --nginx -d ${DOMAIN}"
    
    print_success "Certbot installed"
}

#-------------------------------------------------------------------------------
# STEP 10: Firewall (UFW)
#-------------------------------------------------------------------------------

configure_firewall() {
    print_header "🛡️ Step 10: Configuring Firewall"
    
    print_step "Setting up UFW..."
    ufw --force reset
    ufw default deny incoming
    ufw default allow outgoing
    ufw allow ssh
    ufw allow 'Nginx Full'
    ufw --force enable
    
    print_success "Firewall configured"
}

#-------------------------------------------------------------------------------
# STEP 11: Fail2ban
#-------------------------------------------------------------------------------

configure_fail2ban() {
    print_header "🛡️ Step 11: Configuring Fail2ban"
    
    print_step "Installing Fail2ban..."
    apt install -y fail2ban
    
    cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5
ignoreip = 127.0.0.1/8

[sshd]
enabled = true
port = ssh
logpath = /var/log/auth.log
maxretry = 3
bantime = 86400

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
    
    print_success "Fail2ban configured"
}

#-------------------------------------------------------------------------------
# STEP 12: Setup Directories
#-------------------------------------------------------------------------------

setup_directories() {
    print_header "📁 Step 12: Setting Up Directories"
    
    print_step "Creating directories..."
    mkdir -p ${APP_DIR}
    mkdir -p ${LOG_DIR}
    mkdir -p ${BACKUP_DIR}
    
    # Create deploy user
    if ! id "deploy" &>/dev/null; then
        useradd -m -s /bin/bash deploy
        usermod -aG www-data deploy
        print_success "Deploy user created"
    fi
    
    # Set ownership
    chown -R deploy:www-data ${APP_DIR}
    chown -R deploy:www-data ${LOG_DIR}
    chmod -R 755 ${APP_DIR}
    
    print_success "Directories configured"
}

#-------------------------------------------------------------------------------
# STEP 13: Deploy Application
#-------------------------------------------------------------------------------

deploy_application() {
    print_header "🚀 Step 13: Deploying Application"
    
    # Check if we're in git repo (cloned from GitHub)
    if [ -d ".git" ] && [ -f "package.json" ]; then
        print_step "Copying application files..."
        
        # Copy current directory to APP_DIR
        rsync -av --exclude='.git' --exclude='node_modules' --exclude='vendor' \
              --exclude='*.log' --exclude='.env' \
              ./ ${APP_DIR}/
        
        chown -R deploy:www-data ${APP_DIR}
    fi
    
    cd ${APP_DIR}
    
    if [ -f "package.json" ]; then
        print_step "Installing dependencies..."
        
        # Backend
        if [ -f "backend/composer.json" ]; then
            cd ${APP_DIR}/backend
            sudo -u deploy composer install --no-dev --optimize-autoloader
        fi
        
        # Frontend
        if [ -f "${APP_DIR}/frontend/package.json" ]; then
            cd ${APP_DIR}/frontend
            sudo -u deploy npm ci
            sudo -u deploy npm run build
        fi
        
        # Setup environment
        print_step "Configuring environment..."
        create_env_file
        
        # Generate app key
        cd ${APP_DIR}/backend
        sudo -u deploy php artisan key:generate --force
        
        # Run migrations
        print_step "Running database migrations..."
        sudo -u deploy php artisan migrate --force
        
        # Cache configs
        print_step "Optimizing application..."
        sudo -u deploy php artisan config:cache
        sudo -u deploy php artisan route:cache
        sudo -u deploy php artisan view:cache
        sudo -u deploy php artisan event:cache
        
        # Storage link
        sudo -u deploy php artisan storage:link
        
        # Permissions
        chown -R deploy:www-data ${APP_DIR}
        chmod -R 775 ${APP_DIR}/backend/storage
        chmod -R 775 ${APP_DIR}/backend/bootstrap/cache
        
        print_success "Application deployed"
    else
        print_warning "Application not found. Please clone the repository first."
        print_warning "Run: cd ${APP_DIR} && git clone your-repo ."
    fi
}

#-------------------------------------------------------------------------------
# CREATE ENV FILE
#-------------------------------------------------------------------------------

create_env_file() {
    cat > ${APP_DIR}/backend/.env << EOF
APP_NAME="Sistem Absensi"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta
APP_URL=https://${DOMAIN}

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID

LOG_CHANNEL=daily
LOG_LEVEL=error

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}

# Session & Cache
SESSION_DRIVER=redis
SESSION_LIFETIME=43200
SESSION_ENCRYPT=true
SESSION_DOMAIN=.${DOMAIN}

CACHE_STORE=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=${REDIS_PASSWORD}
REDIS_PORT=6379

# Mail (configure as needed)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@${DOMAIN}
MAIL_FROM_NAME="\${APP_NAME}"

# Payroll
PAYROLL_STANDARD_HOURS_PER_DAY=8
PAYROLL_WORKING_DAYS_PER_MONTH=22
PAYROLL_OVERTIME_MULTIPLIER=1.5
PAYROLL_PAY_DATE_DAY=25

# DeepFace
DEEPFACE_ENABLED=true
DEEPFACE_BASE_URL=http://127.0.0.1
DEEPFACE_PORTS=8001,8002,8003
DEEPFACE_TIMEOUT=30
DEEPFACE_THRESHOLD=0.68
DEEPFACE_LIVENESS_ENABLED=true
DEEPFACE_CACHE_EMBEDDINGS=true
DEEPFACE_CACHE_TTL=3600

# Attendance
ATTENDANCE_STRICT_MODE=true
ATTENDANCE_BYPASS_ROLES=super_admin
ATTENDANCE_LOG_BYPASS=true
ATTENDANCE_VALIDATE_TIME_WINDOWS=true
ATTENDANCE_VALIDATE_WORKING_DAYS=true
EOF

    chown deploy:www-data ${APP_DIR}/backend/.env
    chmod 600 ${APP_DIR}/backend/.env
}

#-------------------------------------------------------------------------------
# STEP 14: Configure Supervisor
#-------------------------------------------------------------------------------

configure_supervisor() {
    print_header "⚙️ Step 14: Configuring Supervisor Workers"
    
    # Laravel Queue Worker
    cat > /etc/supervisor/conf.d/attendance-queue.conf << EOF
[program:attendance-queue]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
directory=${APP_DIR}/backend
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deploy
numprocs=2
redirect_stderr=true
stdout_logfile=${LOG_DIR}/queue.log
stopwaitsecs=3600
EOF

    # DeepFace Workers
    cat > /etc/supervisor/conf.d/deepface.conf << EOF
[program:deepface-8001]
command=${APP_DIR}/python-services/face-recognition/venv/bin/gunicorn -w 2 -b 127.0.0.1:8001 app:app
directory=${APP_DIR}/python-services/face-recognition
autostart=true
autorestart=true
user=deploy
redirect_stderr=true
stdout_logfile=${LOG_DIR}/deepface-8001.log

[program:deepface-8002]
command=${APP_DIR}/python-services/face-recognition/venv/bin/gunicorn -w 2 -b 127.0.0.1:8002 app:app
directory=${APP_DIR}/python-services/face-recognition
autostart=true
autorestart=true
user=deploy
redirect_stderr=true
stdout_logfile=${LOG_DIR}/deepface-8002.log

[program:deepface-8003]
command=${APP_DIR}/python-services/face-recognition/venv/bin/gunicorn -w 2 -b 127.0.0.1:8003 app:app
directory=${APP_DIR}/python-services/face-recognition
autostart=true
autorestart=true
user=deploy
redirect_stderr=true
stdout_logfile=${LOG_DIR}/deepface-8003.log
EOF

    supervisorctl reread
    supervisorctl update
    
    print_success "Supervisor configured"
}

#-------------------------------------------------------------------------------
# STEP 15: Configure Cron
#-------------------------------------------------------------------------------

configure_cron() {
    print_header "⏰ Step 15: Configuring Cron Jobs"
    
    # Laravel scheduler
    cat > /etc/cron.d/attendance-scheduler << EOF
* * * * * deploy cd ${APP_DIR}/backend && php artisan schedule:run >> /dev/null 2>&1
EOF

    # Daily backup
    cat > /etc/cron.d/attendance-backup << EOF
0 2 * * * root ${APP_DIR}/scripts/backup.sh >> ${LOG_DIR}/backup.log 2>&1
EOF

    # Create backup script
    cat > ${APP_DIR}/scripts/backup.sh << 'BACKUP_SCRIPT'
#!/bin/bash
BACKUP_DIR="/var/backups/attendance-system"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="attendance_system"

# Backup database
sudo -u postgres pg_dump $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup uploads
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz /var/www/attendance-system/backend/storage/app/public 2>/dev/null

# Keep only last 7 days
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $DATE"
BACKUP_SCRIPT

    chmod +x ${APP_DIR}/scripts/backup.sh 2>/dev/null || true
    
    print_success "Cron jobs configured"
}

#-------------------------------------------------------------------------------
# FINAL SETUP
#-------------------------------------------------------------------------------

final_setup() {
    print_header "🔄 Final Setup"
    
    print_step "Restarting services..."
    systemctl restart php8.2-fpm
    systemctl restart nginx
    systemctl restart postgresql
    systemctl restart redis-server
    supervisorctl restart all 2>/dev/null || true
    
    print_success "All services restarted"
}

#-------------------------------------------------------------------------------
# PRINT SUMMARY
#-------------------------------------------------------------------------------

print_summary() {
    echo ""
    print_header "✅ INSTALLATION COMPLETE!"
    echo ""
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo -e "  ${CYAN}🌐 Domain:${NC} https://${DOMAIN}"
    echo -e "  ${CYAN}📍 VPS IP:${NC} ${VPS_IP}"
    echo -e "  ${CYAN}📁 App Directory:${NC} ${APP_DIR}"
    echo ""
    echo -e "  ${CYAN}🗄️  Database:${NC}"
    echo -e "      Host: 127.0.0.1"
    echo -e "      Port: 5432"
    echo -e "      Name: ${DB_NAME}"
    echo -e "      User: ${DB_USER}"
    echo -e "      Pass: ${DB_PASSWORD}"
    echo ""
    echo -e "  ${CYAN}📦 Redis:${NC}"
    echo -e "      Host: 127.0.0.1"
    echo -e "      Port: 6379"
    echo -e "      Pass: ${REDIS_PASSWORD}"
    echo ""
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo -e "  ${YELLOW}📝 NEXT STEPS:${NC}"
    echo ""
    echo "  1. Point your domain DNS to: ${VPS_IP}"
    echo ""
    echo "  2. Setup SSL certificate:"
    echo "     sudo certbot --nginx -d ${DOMAIN}"
    echo ""
    echo "  3. Configure email settings in:"
    echo "     ${APP_DIR}/backend/.env"
    echo ""
    echo "  4. Create admin user:"
    echo "     cd ${APP_DIR}/backend && php artisan db:seed"
    echo ""
    echo "  5. Check services status:"
    echo "     sudo systemctl status nginx php8.2-fpm postgresql redis-server"
    echo "     sudo supervisorctl status"
    echo ""
    echo -e "${RED}  ⚠️  SAVE THESE CREDENTIALS IN A SECURE LOCATION!${NC}"
    echo ""
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    
    # Save credentials to file
    cat > /root/attendance-credentials.txt << EOF
==============================================
ATTENDANCE SYSTEM CREDENTIALS
Generated: $(date)
==============================================

Domain: ${DOMAIN}
VPS IP: ${VPS_IP}
App Directory: ${APP_DIR}

DATABASE (PostgreSQL)
---------------------
Host: 127.0.0.1
Port: 5432
Database: ${DB_NAME}
Username: ${DB_USER}
Password: ${DB_PASSWORD}

REDIS
---------------------
Host: 127.0.0.1
Port: 6379
Password: ${REDIS_PASSWORD}

IMPORTANT COMMANDS
---------------------
# Check services
sudo systemctl status nginx php8.2-fpm postgresql redis-server

# Check workers
sudo supervisorctl status

# View logs
tail -f /var/log/nginx/attendance.error.log
tail -f ${APP_DIR}/backend/storage/logs/laravel.log

# Setup SSL
sudo certbot --nginx -d ${DOMAIN}

# Deploy updates
cd ${APP_DIR} && git pull && ./scripts/deploy.sh
EOF

    chmod 600 /root/attendance-credentials.txt
    echo ""
    echo -e "  ${GREEN}Credentials saved to: /root/attendance-credentials.txt${NC}"
    echo ""
}

#-------------------------------------------------------------------------------
# RUN MAIN
#-------------------------------------------------------------------------------

main "$@"
