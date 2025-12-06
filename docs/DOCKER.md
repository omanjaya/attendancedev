# Docker Deployment Guide

Panduan lengkap untuk deploy Attendance System menggunakan Docker.

## 📋 Prasyarat

- Docker Engine 20.10+
- Docker Compose 2.0+
- Minimal 4GB RAM (8GB direkomendasikan untuk DeepFace)
- 20GB disk space

## 🚀 Quick Start (Development)

```bash
# 1. Clone repository
git clone https://github.com/your-repo/attendancedev.git
cd attendancedev

# 2. Copy environment file
cp .env.docker.example .env

# 3. Build dan jalankan
docker-compose up -d

# 4. Generate APP_KEY
docker-compose exec backend php artisan key:generate

# 5. Jalankan migrasi
docker-compose exec backend php artisan migrate --seed

# 6. Akses aplikasi
# Frontend: http://localhost:5173
# Backend API: http://localhost/api
# Adminer (DB): http://localhost:8080
```

## 🏭 Production Deployment

### 1. Persiapan VPS

```bash
# Install Docker
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Logout dan login kembali untuk refresh groups
```

### 2. Setup Aplikasi

```bash
# Clone repository
git clone https://github.com/your-repo/attendancedev.git
cd attendancedev

# Setup environment
cp .env.docker.example .env
nano .env  # Edit sesuai kebutuhan

# PENTING: Ganti nilai berikut di .env:
# - APP_KEY (generate dengan: php artisan key:generate --show)
# - DB_PASSWORD (password yang kuat)
# - REDIS_PASSWORD (password yang kuat)
# - APP_URL (domain Anda)
# - VITE_API_BASE_URL (domain API Anda)
```

### 3. Build dan Deploy

```bash
# Build images
docker-compose -f docker-compose.prod.yml build

# Start services
docker-compose -f docker-compose.prod.yml up -d

# Run migrations
docker-compose -f docker-compose.prod.yml exec backend php artisan migrate --seed

# Optimize Laravel
docker-compose -f docker-compose.prod.yml exec backend php artisan config:cache
docker-compose -f docker-compose.prod.yml exec backend php artisan route:cache
docker-compose -f docker-compose.prod.yml exec backend php artisan view:cache
```

### 4. Setup SSL (Let's Encrypt)

```bash
# Pastikan domain sudah pointing ke VPS

# Jalankan certbot
docker-compose -f docker-compose.prod.yml --profile ssl up -d certbot

# Generate certificate
docker-compose -f docker-compose.prod.yml exec certbot certbot certonly \
  --webroot -w /var/www/certbot \
  -d yourdomain.com \
  --email your@email.com \
  --agree-tos \
  --no-eff-email

# Restart nginx untuk load certificate
docker-compose -f docker-compose.prod.yml restart nginx
```

## 📊 Monitoring & Logging

### Lihat Logs

```bash
# Semua services
docker-compose logs -f

# Service spesifik
docker-compose logs -f backend
docker-compose logs -f deepface-1
docker-compose logs -f nginx

# Tail 100 baris terakhir
docker-compose logs --tail=100 backend
```

### Resource Usage

```bash
# Lihat resource usage
docker stats

# Lihat disk usage
docker system df
```

## 🔄 Update & Maintenance

### Update Aplikasi

```bash
# Pull latest code
git pull origin main

# Rebuild dan restart (zero-downtime)
docker-compose -f docker-compose.prod.yml build
docker-compose -f docker-compose.prod.yml up -d --no-deps --build backend
docker-compose -f docker-compose.prod.yml up -d --no-deps --build frontend

# Run any new migrations
docker-compose -f docker-compose.prod.yml exec backend php artisan migrate --force
```

### Backup Database

```bash
# Backup
docker-compose exec postgres pg_dump -U attendance_user attendance_system > backup_$(date +%Y%m%d).sql

# Restore
cat backup_20241206.sql | docker-compose exec -T postgres psql -U attendance_user attendance_system
```

### Cleanup

```bash
# Hapus unused images
docker image prune -a

# Hapus unused volumes (HATI-HATI!)
docker volume prune

# Full cleanup
docker system prune -a
```

## 🔧 Troubleshooting

### Container tidak start

```bash
# Cek status
docker-compose ps

# Cek logs
docker-compose logs --tail=50 [service_name]

# Restart service
docker-compose restart [service_name]
```

### Database connection error

```bash
# Cek postgres running
docker-compose exec postgres pg_isready

# Masuk ke postgres shell
docker-compose exec postgres psql -U attendance_user -d attendance_system
```

### DeepFace memory error

```bash
# Cek memory usage
docker stats attendance_deepface_1

# Increase memory di docker-compose.prod.yml:
# deploy:
#   resources:
#     limits:
#       memory: 4G
```

### Clear cache

```bash
docker-compose exec backend php artisan cache:clear
docker-compose exec backend php artisan config:clear
docker-compose exec backend php artisan route:clear
docker-compose exec backend php artisan view:clear
```

## 📁 Volume Locations

| Volume | Path | Description |
|--------|------|-------------|
| postgres_data | /var/lib/docker/volumes/attendance_postgres_data | Database files |
| redis_data | /var/lib/docker/volumes/attendance_redis_data | Redis persistence |
| backend_storage | /var/lib/docker/volumes/attendance_backend_storage | Laravel storage |
| face_data | /var/lib/docker/volumes/attendance_face_data | Face recognition data |
| deepface_models | /var/lib/docker/volumes/attendance_deepface_models | DeepFace AI models |

## 🔐 Security Checklist

- [ ] Ubah semua default passwords di .env
- [ ] Aktifkan firewall (ufw)
- [ ] Setup SSL certificate
- [ ] Disable root SSH login
- [ ] Setup fail2ban
- [ ] Backup database secara regular
- [ ] Update Docker images secara berkala

## 📞 Support

Jika mengalami masalah:

1. Cek logs: `docker-compose logs -f`
2. Cek status: `docker-compose ps`
3. Restart services: `docker-compose restart`
