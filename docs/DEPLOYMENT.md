# 🚀 VPS Deployment Guide

Panduan lengkap deploy Attendance System ke VPS.

## Quick Deploy (One Command!)

Setelah clone repository ke VPS:

```bash
# Clone repository
git clone https://github.com/your-repo/attendance-system.git
cd attendance-system

# Run installation script
sudo DOMAIN=absensi.yourdomain.com bash scripts/install-vps.sh
```

Script ini akan menginstall dan mengkonfigurasi **SEMUA** yang dibutuhkan secara otomatis!

---

## VPS Requirements

### Minimum Specs

| Resource | Minimum | Recommended |
|----------|---------|-------------|
| CPU | 2 vCPU | 4 vCPU |
| RAM | 2 GB | 4 GB |
| Storage | 20 GB SSD | 40 GB SSD |
| OS | Ubuntu 22.04 LTS | Ubuntu 22.04 LTS |

### Estimated Cost

- **Budget**: $10-15/month (DigitalOcean, Vultr, Linode)
- **Recommended**: $20-30/month

---

## What Gets Installed

Script `install-vps.sh` akan menginstall:

| Component | Version | Purpose |
|-----------|---------|---------|
| PHP | 8.2 + FPM | Backend runtime |
| PostgreSQL | 15 | Database (optimized) |
| Redis | 7 | Cache, Session, Queue |
| Nginx | Latest | Web server |
| Node.js | 20 | Build frontend |
| Python | 3.10+ | DeepFace service |
| Supervisor | Latest | Process manager |
| Certbot | Latest | SSL certificates |
| UFW | Latest | Firewall |
| Fail2ban | Latest | Brute-force protection |

---

## Step-by-Step Manual Installation

Jika ingin install manual:

### 1. Prepare VPS

```bash
# Login ke VPS
ssh root@your-vps-ip

# Update system
apt update && apt upgrade -y

# Install essential tools
apt install -y curl git wget unzip
```

### 2. Clone Repository

```bash
# Create directory
mkdir -p /var/www
cd /var/www

# Clone
git clone https://github.com/your-repo/attendance-system.git
cd attendance-system
```

### 3. Run Installation Script

```bash
# Set domain (optional, can be changed later)
export DOMAIN="absensi.yourdomain.com"

# Run script
sudo bash scripts/install-vps.sh
```

### 4. Configure SSL

Setelah DNS pointing ke VPS:

```bash
sudo certbot --nginx -d your-domain.com
```

### 5. Verify Installation

```bash
# Check all services
sudo systemctl status nginx php8.2-fpm postgresql redis-server

# Check workers
sudo supervisorctl status

# Test API
curl https://your-domain.com/api/health
```

---

## Post-Installation

### 1. Create Admin User

```bash
cd /var/www/attendance-system/backend
php artisan db:seed
```

### 2. Configure Email (Optional)

Edit `/var/www/attendance-system/backend/.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

### 3. Configure Pusher (Optional)

Untuk real-time notifications:

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=ap1
```

---

## Updating Application

### Quick Update

```bash
cd /var/www/attendance-system

# Pull latest code
git pull origin main

# Update dependencies
cd backend && composer install --no-dev --optimize-autoloader
cd ../frontend && npm ci && npm run build

# Clear caches
cd ../backend
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl restart php8.2-fpm nginx
sudo supervisorctl restart all
```

### With Maintenance Mode

```bash
cd /var/www/attendance-system/backend

# Enable maintenance
php artisan down

# ... do updates ...

# Disable maintenance  
php artisan up
```

---

## Backup & Restore

### Manual Backup

```bash
# Database
sudo -u postgres pg_dump attendance_system > backup.sql

# Full backup
tar -czf backup.tar.gz /var/www/attendance-system
```

### Automatic Backup

Cron job sudah dikonfigurasi untuk backup harian jam 2 pagi.

Lokasi backup: `/var/backups/attendance-system/`

### Restore

```bash
# Restore database
sudo -u postgres psql attendance_system < backup.sql

# Restore files
tar -xzf backup.tar.gz -C /
```

---

## Monitoring

### Check Logs

```bash
# Laravel logs
tail -f /var/www/attendance-system/backend/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/attendance.error.log

# Queue worker logs
tail -f /var/log/attendance-system/queue.log

# DeepFace logs
tail -f /var/log/attendance-system/deepface-*.log
```

### System Health

```bash
# All services
sudo systemctl status nginx php8.2-fpm postgresql redis-server

# Workers
sudo supervisorctl status

# Disk usage
df -h

# Memory
free -h

# CPU
top -bn1 | head -20
```

---

## Troubleshooting

### 500 Internal Server Error

```bash
# Check permissions
sudo chown -R deploy:www-data /var/www/attendance-system
sudo chmod -R 775 /var/www/attendance-system/backend/storage

# Check logs
tail -50 /var/www/attendance-system/backend/storage/logs/laravel.log
```

### Queue Not Processing

```bash
sudo supervisorctl restart attendance-queue:*
```

### SSL Certificate Issues

```bash
# Renew certificate
sudo certbot renew

# Check certificate
sudo certbot certificates
```

### Database Connection Error

```bash
# Check PostgreSQL
sudo systemctl status postgresql

# Test connection
sudo -u postgres psql -d attendance_system
```

---

## Security Checklist

- [ ] SSL certificate installed (HTTPS)
- [ ] Firewall enabled (UFW)
- [ ] Fail2ban configured
- [ ] Strong database passwords (auto-generated)
- [ ] Redis password set
- [ ] `.env` file secured (chmod 600)
- [ ] Regular backups enabled
- [ ] SSH key authentication only

---

## Credentials Location

Semua credentials disimpan di:

```bash
sudo cat /root/attendance-credentials.txt
```

⚠️ **SIMPAN CREDENTIALS INI DI TEMPAT YANG AMAN!**

---

## Support

Jika ada masalah:

1. Check logs terlebih dahulu
2. Lihat [Troubleshooting Guide](TROUBLESHOOTING.md)
3. Buat issue di GitHub
