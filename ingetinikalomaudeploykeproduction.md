# 🚀 INGETIN KALO MAU DEPLOY KE PRODUCTION YA!

> **Jangan lupa baca ini sebelum deploy!**
> Kalau lupa nanti performancenya jelek, terus ngambek sendiri.

---

## 📋 Checklist Sebelum Deploy

- [ ] Baca dokumentasi ini sampai habis
- [ ] Siapkan VPS (minimal 2GB RAM)
- [ ] Siapkan domain & SSL
- [ ] Backup database development
- [ ] Siapkan kopi ☕

---

## 🖥️ VPS Requirements

### Minimal Spec (Budget Mode):
- **CPU**: 2 vCPU
- **RAM**: 2 GB
- **Storage**: 20 GB SSD
- **OS**: Ubuntu 22.04 LTS
- **Cost**: ~$10-15/month
- **Provider**: DigitalOcean, Vultr, Linode

### Recommended Spec (Performance Mode):
- **CPU**: 2-4 vCPU
- **RAM**: 4 GB
- **Storage**: 40 GB SSD
- **OS**: Ubuntu 22.04 LTS
- **Cost**: ~$20-30/month
- **Provider**: DigitalOcean, Vultr, Linode

---

## 🔧 Software Stack

### Must Have (Wajib!):
1. **PHP 8.2+** dengan OPcache
2. **PostgreSQL 15+**
3. **Nginx**
4. **Composer**
5. **Node.js 20+** (untuk build frontend)
6. **SSL/TLS** (Let's Encrypt)

### Strongly Recommended:
7. **Redis** (untuk cache & session)
8. **Supervisor** (untuk queue worker)

---

## 📦 Step-by-Step Installation

### 1. Update System & Install Dependencies

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install essential tools
sudo apt install -y curl git unzip software-properties-common

# Install PHP 8.2
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-pgsql php8.2-mbstring php8.2-xml \
    php8.2-curl php8.2-gd php8.2-intl php8.2-zip \
    php8.2-bcmath php8.2-redis

# Install PostgreSQL 15
sudo apt install -y postgresql postgresql-contrib

# Install Nginx
sudo apt install -y nginx

# Install Redis
sudo apt install -y redis-server

# Install Supervisor (untuk queue)
sudo apt install -y supervisor

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

### 2. Configure PHP OPcache

Edit `/etc/php/8.2/fpm/php.ini`:

```ini
# OPcache Settings (PENTING!)
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.enable_cli=0

# Memory Settings
memory_limit=512M
upload_max_filesize=20M
post_max_size=20M
max_execution_time=300

# Timezone
date.timezone=Asia/Makassar
```

Edit `/etc/php/8.2/fpm/pool.d/www.conf`:

```ini
# Process Manager
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
```

---

### 3. Setup PostgreSQL

```bash
# Login as postgres user
sudo -u postgres psql

# Buat database dan user
CREATE DATABASE attendance_prod;
CREATE USER attendance_user WITH ENCRYPTED PASSWORD 'password_yang_kuat_ya';
GRANT ALL PRIVILEGES ON DATABASE attendance_prod TO attendance_user;
\q

# Restart PostgreSQL
sudo systemctl restart postgresql
```

**JANGAN LUPA**: Ganti `password_yang_kuat_ya` dengan password yang kuat!

---

### 4. Setup Redis

Edit `/etc/redis/redis.conf`:

```conf
# Uncomment dan set password
requirepass password_redis_kamu

# Set maxmemory (50% dari total RAM)
maxmemory 1gb
maxmemory-policy allkeys-lru

# Disable RDB snapshots (pakai AOF aja)
save ""

# Enable AOF
appendonly yes
appendfilename "appendonly.aof"
appendfsync everysec
```

Restart Redis:
```bash
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

Test Redis:
```bash
redis-cli
AUTH password_redis_kamu
PING
# Should return: PONG
```

---

### 5. Clone & Setup Project

```bash
# Create directory
sudo mkdir -p /var/www/attendance
sudo chown -R $USER:$USER /var/www/attendance

# Clone project
cd /var/www/attendance
git clone https://github.com/your-repo/attendance.git .

# Install backend dependencies
cd backend
composer install --no-dev --optimize-autoloader

# Install frontend dependencies
cd ../frontend
npm ci --production

# Build frontend
npm run build
```

---

### 6. Configure Environment (.env)

Edit `backend/.env`:

```env
APP_NAME="Attendance System"
APP_ENV=production
APP_KEY=base64:xxxxx  # Generate dengan: php artisan key:generate
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=attendance_prod
DB_USERNAME=attendance_user
DB_PASSWORD=password_yang_kuat_ya

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=password_redis_kamu
REDIS_PORT=6379

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail (Mailgun/SES/SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Frontend URL (untuk CORS)
FRONTEND_URL=https://yourdomain.com

# Face Recognition Settings
ENABLE_FACE_RECOGNITION=true
FACE_RECOGNITION_THRESHOLD=0.6

# GPS Settings
ENABLE_GPS_VERIFICATION=true
```

**Generate App Key:**
```bash
cd /var/www/attendance/backend
php artisan key:generate
```

**Run Migrations:**
```bash
php artisan migrate --force
php artisan db:seed --force
```

**Cache Config:**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

### 7. Setup Nginx

Create `/etc/nginx/sites-available/attendance`:

```nginx
# Redirect HTTP to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

# HTTPS Server
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/attendance/frontend/dist;
    index index.html;

    # SSL Configuration (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml font/truetype font/opentype application/vnd.ms-fontobject image/svg+xml;

    # Frontend (React SPA)
    location / {
        try_files $uri $uri/ /index.html;
    }

    # API Backend (Laravel)
    location /api {
        alias /var/www/attendance/backend/public;
        try_files $uri $uri/ @backend;

        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_param SCRIPT_FILENAME /var/www/attendance/backend/public/index.php;
            fastcgi_param PATH_INFO $fastcgi_path_info;
        }
    }

    location @backend {
        rewrite /api/?(.*) /index.php?/$1 last;
    }

    # Static Assets (Cache)
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Access & Error Logs
    access_log /var/log/nginx/attendance_access.log;
    error_log /var/log/nginx/attendance_error.log;
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/attendance /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

### 8. Setup SSL (Let's Encrypt)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Get SSL Certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renew (cron job)
sudo certbot renew --dry-run
```

---

### 9. Setup Laravel Queue Worker (Supervisor)

Create `/etc/supervisor/conf.d/attendance-worker.conf`:

```ini
[program:attendance-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/attendance/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/attendance/backend/storage/logs/worker.log
stopwaitsecs=3600
```

Start worker:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start attendance-worker:*
```

Check status:
```bash
sudo supervisorctl status
```

---

### 10. Setup Cron Jobs

Edit crontab:
```bash
sudo crontab -e
```

Add:
```cron
# Laravel Scheduler
* * * * * cd /var/www/attendance/backend && php artisan schedule:run >> /dev/null 2>&1

# Clear old logs (optional)
0 0 * * * find /var/www/attendance/backend/storage/logs -type f -mtime +7 -delete
```

---

### 11. Set Permissions

```bash
cd /var/www/attendance/backend

# Set ownership
sudo chown -R www-data:www-data .

# Set directory permissions
sudo find . -type d -exec chmod 755 {} \;

# Set file permissions
sudo find . -type f -exec chmod 644 {} \;

# Make artisan executable
sudo chmod +x artisan

# Storage & cache writable
sudo chmod -R 775 storage bootstrap/cache
```

---

### 12. Setup Database Indexes (Performance!)

Login ke PostgreSQL:
```bash
sudo -u postgres psql attendance_prod
```

Run:
```sql
-- Attendance indexes
CREATE INDEX idx_attendance_date ON attendances(date);
CREATE INDEX idx_attendance_employee_date ON attendances(employee_id, date);
CREATE INDEX idx_attendance_created_at ON attendances(created_at);

-- Employee indexes
CREATE INDEX idx_employees_user_id ON employees(user_id);
CREATE INDEX idx_employees_active ON employees(is_active) WHERE is_active = true;

-- Leave requests indexes
CREATE INDEX idx_leave_employee ON leave_requests(employee_id);
CREATE INDEX idx_leave_status ON leave_requests(status);
CREATE INDEX idx_leave_dates ON leave_requests(start_date, end_date);

-- Location indexes
CREATE INDEX idx_locations_active ON locations(is_active) WHERE is_active = true;

-- Face recognition indexes (jika ada table face_data)
CREATE INDEX idx_face_employee ON face_recognition_data(employee_id);

-- Users indexes
CREATE INDEX idx_users_email ON users(email);
```

---

## 🔍 Monitoring & Maintenance

### Check System Status

```bash
# Check services
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status postgresql
sudo systemctl status redis-server
sudo systemctl status supervisor

# Check queue workers
sudo supervisorctl status

# Check logs
sudo tail -f /var/log/nginx/attendance_error.log
sudo tail -f /var/www/attendance/backend/storage/logs/laravel.log

# Check Redis
redis-cli
AUTH password_redis_kamu
INFO stats
```

### Performance Monitoring

```bash
# Check memory usage
free -h

# Check CPU usage
top

# Check disk usage
df -h

# Check PostgreSQL connections
sudo -u postgres psql attendance_prod -c "SELECT count(*) FROM pg_stat_activity;"

# Check Redis memory
redis-cli INFO memory
```

---

## 🚨 Troubleshooting

### Problem: 500 Internal Server Error

**Solution:**
```bash
# Check error logs
sudo tail -50 /var/log/nginx/attendance_error.log
sudo tail -50 /var/www/attendance/backend/storage/logs/laravel.log

# Check permissions
sudo chown -R www-data:www-data /var/www/attendance/backend/storage
sudo chmod -R 775 /var/www/attendance/backend/storage
```

### Problem: Queue not working

**Solution:**
```bash
# Restart workers
sudo supervisorctl restart attendance-worker:*

# Check worker logs
sudo tail -50 /var/www/attendance/backend/storage/logs/worker.log

# Manual test
cd /var/www/attendance/backend
php artisan queue:work redis --once
```

### Problem: Redis connection refused

**Solution:**
```bash
# Check Redis status
sudo systemctl status redis-server

# Restart Redis
sudo systemctl restart redis-server

# Test connection
redis-cli ping
```

### Problem: Database connection error

**Solution:**
```bash
# Check PostgreSQL status
sudo systemctl status postgresql

# Test connection
sudo -u postgres psql attendance_prod

# Check credentials in .env
cat /var/www/attendance/backend/.env | grep DB_
```

---

## 📊 Performance Tuning

### PostgreSQL Performance

Edit `/etc/postgresql/15/main/postgresql.conf`:

```conf
# Memory (untuk 4GB RAM VPS)
shared_buffers = 1GB
effective_cache_size = 3GB
maintenance_work_mem = 256MB
work_mem = 16MB

# Checkpoints
checkpoint_completion_target = 0.9
wal_buffers = 16MB

# Query Planner
random_page_cost = 1.1
effective_io_concurrency = 200

# Logging (optional - untuk debugging)
log_min_duration_statement = 1000  # Log queries > 1s
```

Restart PostgreSQL:
```bash
sudo systemctl restart postgresql
```

### Redis Performance

Edit `/etc/redis/redis.conf`:

```conf
# Memory optimization
maxmemory 1gb
maxmemory-policy allkeys-lru

# Persistence (pilih salah satu)
# Option 1: AOF (lebih safe, sedikit slower)
appendonly yes
appendfsync everysec

# Option 2: RDB (faster, kurang safe)
save 900 1
save 300 10
save 60 10000
```

---

## 🔄 Update & Deployment

### Deploy Update

```bash
# 1. Backup database
cd /var/www/attendance/backend
php artisan backup:run

# 2. Enable maintenance mode
php artisan down

# 3. Pull latest code
cd /var/www/attendance
git pull origin main

# 4. Update backend
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force

# 5. Update frontend
cd ../frontend
npm ci --production
npm run build

# 6. Clear & cache
cd ../backend
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 7. Restart services
sudo systemctl restart php8.2-fpm
sudo supervisorctl restart attendance-worker:*

# 8. Disable maintenance mode
php artisan up
```

---

## 💾 Backup Strategy

### Daily Database Backup

Create script `/usr/local/bin/backup-attendance.sh`:

```bash
#!/bin/bash

# Variables
BACKUP_DIR="/var/backups/attendance"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="attendance_prod"
RETENTION_DAYS=7

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
sudo -u postgres pg_dump $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup uploaded files
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz /var/www/attendance/backend/storage/app/public

# Delete old backups
find $BACKUP_DIR -type f -mtime +$RETENTION_DAYS -delete

echo "Backup completed: $DATE"
```

Make executable:
```bash
sudo chmod +x /usr/local/bin/backup-attendance.sh
```

Add to crontab:
```bash
sudo crontab -e
```

Add:
```cron
# Daily backup at 2 AM
0 2 * * * /usr/local/bin/backup-attendance.sh >> /var/log/attendance-backup.log 2>&1
```

---

## 🎯 Performance Benchmarks

**Target Performance (dengan Redis):**
- API Response Time: **< 100ms** (average)
- Face Recognition: **< 2s** (with image upload)
- GPS Verification: **< 50ms**
- Database Queries: **< 20ms** (cached)
- Page Load (Frontend): **< 1s**

**Test dengan Apache Bench:**
```bash
# Test API endpoint
ab -n 1000 -c 10 https://yourdomain.com/api/v1/attendance/today

# Test homepage
ab -n 1000 -c 10 https://yourdomain.com/
```

---

## 📝 Checklist Setelah Deploy

- [ ] Website bisa diakses via HTTPS
- [ ] SSL certificate valid (check dengan SSL Labs)
- [ ] Login berfungsi normal
- [ ] Face recognition berfungsi
- [ ] GPS verification berfungsi
- [ ] Queue worker jalan (check supervisor status)
- [ ] Redis connected (check logs)
- [ ] Database indexes dibuat
- [ ] Cron jobs berjalan
- [ ] Backup otomatis jalan
- [ ] Performance test passed
- [ ] Security headers aktif (check dengan securityheaders.com)
- [ ] CORS configured properly

---

## 🛡️ Security Checklist

- [ ] SSL/TLS aktif (A+ rating di SSL Labs)
- [ ] Firewall aktif (UFW)
- [ ] SSH key-based authentication only
- [ ] Disable root login
- [ ] Strong passwords untuk database & Redis
- [ ] `.env` file tidak accessible via web
- [ ] File permissions benar (755 dirs, 644 files)
- [ ] Security headers configured
- [ ] Rate limiting aktif
- [ ] Regular updates (apt update && apt upgrade)

---

## 📞 Emergency Contacts

**Jika ada masalah:**
1. Check logs dulu
2. Restart services
3. Check GitHub issues
4. Hubungi developer

**Important Logs:**
- Nginx: `/var/log/nginx/attendance_error.log`
- Laravel: `/var/www/attendance/backend/storage/logs/laravel.log`
- PHP-FPM: `/var/log/php8.2-fpm.log`
- PostgreSQL: `/var/log/postgresql/postgresql-15-main.log`
- Redis: `/var/log/redis/redis-server.log`

---

## 🎉 Selesai!

Kalau semua checklist sudah ✅, berarti deploy sukses!

**Don't forget:**
- Monitor performance minggu pertama
- Setup monitoring tools (Uptime Robot, New Relic, dll)
- Backup database berkala
- Update dependencies reguler

---

> **INGAT**: Kalau ada error, jangan panik! Check logs dulu. 90% masalah bisa diselesaikan dengan restart services. 😄

**Last updated**: December 2024
**Version**: 1.0
