# 🚀 Panduan Deployment ke VPS dengan Docker Hub CI/CD

## 📋 Daftar Isi

1. [Prerequisites](#prerequisites)
2. [Setup Docker Hub](#setup-docker-hub)
3. [Setup GitHub Secrets](#setup-github-secrets)
4. [Persiapan VPS](#persiapan-vps)
5. [Deployment Manual Pertama](#deployment-manual-pertama)
6. [CI/CD Otomatis](#cicd-otomatis)
7. [Monitoring & Maintenance](#monitoring--maintenance)
8. [Troubleshooting](#troubleshooting)

---

## 📌 Prerequisites

### Kebutuhan VPS

- **OS**: Ubuntu 22.04 LTS atau Debian 11+
- **RAM**: Minimum 4GB (Recommended 8GB untuk DeepFace)
- **Storage**: 40GB+ SSD
- **CPU**: 2 cores minimum (4 cores recommended)
- **Network**: Public IP dan domain (optional tapi recommended)

### Akun yang Diperlukan

- Docker Hub account (gratis)
- GitHub repository
- VPS provider (DigitalOcean, Vultr, Linode, dll)

---

## 🐳 Setup Docker Hub

### 1. Buat Akun Docker Hub

1. Daftar di [hub.docker.com](https://hub.docker.com)
2. Catat username Anda

### 2. Buat Access Token

1. Login ke Docker Hub
2. Pergi ke **Account Settings → Security**
3. Klik **New Access Token**
4. Beri nama: `github-actions-attendancedev`
5. Pilih permission: **Read, Write, Delete**
6. Copy token (hanya ditampilkan sekali!)

### 3. Repository akan dibuat otomatis

CI/CD akan membuat repositories:

- `yourusername/attendancedev-backend`
- `yourusername/attendancedev-frontend`
- `yourusername/attendancedev-deepface`

---

## 🔐 Setup GitHub Secrets

Pergi ke repository GitHub → **Settings → Secrets and Variables → Actions**

### Secrets yang Diperlukan

| Secret Name | Deskripsi | Contoh |
|-------------|-----------|--------|
| `DOCKERHUB_USERNAME` | Username Docker Hub | `omanjaya` |
| `DOCKERHUB_TOKEN` | Access token dari Docker Hub | `dckr_pat_xxxxx` |
| `VPS_HOST` | IP atau domain VPS | `123.456.789.10` |
| `VPS_USERNAME` | User SSH (biasanya root) | `root` |
| `VPS_SSH_KEY` | Private key SSH | `-----BEGIN...` |
| `VPS_SSH_PORT` | Port SSH (default 22) | `22` |
| `APP_URL` | URL aplikasi | `https://attendance.domain.com` |
| `APP_KEY` | Laravel app key | `base64:xxxxx` |
| `DB_PASSWORD` | Password PostgreSQL | `securepassword123` |
| `REDIS_PASSWORD` | Password Redis | `anotherpassword` |
| `VITE_API_BASE_URL` | URL API untuk frontend | `https://attendance.domain.com/api` |
| `VITE_DEEPFACE_URL` | URL DeepFace | `https://attendance.domain.com/deepface` |

### Optional Secrets (untuk notifikasi)

| Secret Name | Deskripsi |
|-------------|-----------|
| `TELEGRAM_BOT_TOKEN` | Token bot Telegram |
| `TELEGRAM_CHAT_ID` | ID chat untuk notifikasi |

---

## 🖥️ Persiapan VPS

### 1. Setup Awal VPS

```bash
# SSH ke VPS
ssh root@your-vps-ip

# Download dan jalankan setup script
curl -sSL https://raw.githubusercontent.com/YOUR_USER/attendancedev/main/scripts/vps-setup.sh | sudo bash
```

### 2. Manual Setup (jika tidak pakai script)

```bash
# Update sistem
apt update && apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com | sh
systemctl enable docker

# Install Docker Compose
apt install docker-compose-plugin -y

# Buat direktori project
mkdir -p /opt/attendancedev
cd /opt/attendancedev

# Clone repository
git clone https://github.com/YOUR_USER/attendancedev.git .

# Setup environment
cp .env.docker.example .env
nano .env  # Edit sesuai kebutuhan
```

### 3. Generate SSH Key untuk CI/CD

Di **local machine** Anda:

```bash
# Generate SSH key
ssh-keygen -t ed25519 -C "github-actions" -f ./github_actions_key

# Copy public key ke VPS
ssh-copy-id -i github_actions_key.pub root@your-vps-ip

# Private key (github_actions_key) → masukkan ke GitHub Secret VPS_SSH_KEY
cat github_actions_key
```

---

## 🚢 Deployment Manual Pertama

Sebelum CI/CD berjalan, deploy manual sekali:

### 1. Di VPS, buat file environment

```bash
cd /opt/attendancedev
nano .env
```

Isi dengan:

```env
# Application
APP_NAME="Attendance System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_KEY=base64:YOUR_GENERATED_KEY

# Database
DB_DATABASE=attendance_system
DB_USERNAME=attendance_user
DB_PASSWORD=YOUR_SECURE_PASSWORD

# Redis
REDIS_PASSWORD=YOUR_REDIS_PASSWORD

# Docker Hub
DOCKERHUB_USERNAME=your_dockerhub_username
IMAGE_TAG=latest

# Frontend
VITE_API_BASE_URL=https://yourdomain.com/api
VITE_DEEPFACE_URL=https://yourdomain.com/deepface

# DeepFace
DEEPFACE_MODEL=ArcFace
DEEPFACE_DETECTOR=retinaface
DEEPFACE_THRESHOLD=0.68
```

### 2. Build dan push images dari local

```bash
# Di local machine
cd /path/to/attendancedev

# Login Docker Hub
docker login

# Build dan push
docker compose -f docker-compose.prod.yml build
docker tag attendancedev-backend:latest your_user/attendancedev-backend:latest
docker tag attendancedev-frontend:latest your_user/attendancedev-frontend:latest
docker tag attendancedev-deepface:latest your_user/attendancedev-deepface:latest

docker push your_user/attendancedev-backend:latest
docker push your_user/attendancedev-frontend:latest
docker push your_user/attendancedev-deepface:latest
```

### 3. Deploy di VPS

```bash
cd /opt/attendancedev

# Pull images
docker compose -f docker-compose.hub.yml pull

# Start services
docker compose -f docker-compose.hub.yml up -d

# Cek status
docker compose -f docker-compose.hub.yml ps

# Jalankan migrations
docker exec attendancedev-backend php artisan migrate --seed

# Buat admin user
docker exec -it attendancedev-backend php artisan tinker
# Di tinker:
# User::create(['name'=>'Admin','email'=>'admin@domain.com','password'=>bcrypt('password'),'role'=>'admin']);
```

### 4. Setup SSL dengan Certbot

```bash
# Install certbot
apt install certbot -y

# Dapatkan sertifikat
certbot certonly --webroot -w /opt/attendancedev/docker/nginx/certbot -d yourdomain.com

# Copy sertifikat
cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem /opt/attendancedev/docker/nginx/ssl/
cp /etc/letsencrypt/live/yourdomain.com/privkey.pem /opt/attendancedev/docker/nginx/ssl/

# Restart nginx
docker compose -f docker-compose.hub.yml restart nginx
```

---

## 🔄 CI/CD Otomatis

Setelah setup selesai, setiap push ke branch `main` akan:

1. ✅ Check TypeScript errors
2. ✅ Build frontend
3. ✅ Build Docker images
4. ✅ Push ke Docker Hub
5. ✅ SSH ke VPS & pull images
6. ✅ Deploy dengan zero-downtime
7. ✅ Run migrations
8. ✅ Clear cache
9. ✅ Health check
10. ✅ Notify via Telegram (optional)

### Manual Trigger

Anda juga bisa trigger deployment manual dari:
**GitHub Actions → Workflows → Build & Deploy → Run workflow**

---

## 📊 Monitoring & Maintenance

### Cek Status Services

```bash
# Lihat semua container
docker compose -f docker-compose.hub.yml ps

# Lihat logs
docker compose -f docker-compose.hub.yml logs -f

# Logs spesifik service
docker logs -f attendancedev-backend
docker logs -f attendancedev-nginx
```

### Health Check Endpoints

- **API**: `https://yourdomain.com/api/health`
- **DeepFace**: `https://yourdomain.com/deepface/health`

### Backup Database

```bash
# Manual backup
docker exec attendancedev-postgres pg_dump -U attendance_user attendance_system > backup_$(date +%Y%m%d).sql

# Restore
cat backup.sql | docker exec -i attendancedev-postgres psql -U attendance_user attendance_system
```

### Update Manual

```bash
cd /opt/attendancedev

# Pull latest images
docker compose -f docker-compose.hub.yml pull

# Recreate containers
docker compose -f docker-compose.hub.yml up -d

# Run migrations
docker exec attendancedev-backend php artisan migrate --force
```

---

## 🔧 Troubleshooting

### Container tidak mau start

```bash
# Cek logs
docker logs attendancedev-backend

# Cek resource usage
docker stats
htop
```

### Database connection error

```bash
# Cek apakah postgres ready
docker exec attendancedev-postgres pg_isready

# Cek network
docker network ls
docker network inspect attendancedev-internal
```

### DeepFace lambat startup

DeepFace perlu download model saat pertama kali start (~60 detik).

```bash
# Cek progress
docker logs -f attendancedev-deepface-1
```

### SSL tidak bekerja

```bash
# Cek sertifikat
ls -la /opt/attendancedev/docker/nginx/ssl/

# Renew manual
certbot renew
```

### Reset semua

```bash
# ⚠️ HATI-HATI: Ini akan menghapus semua data!
docker compose -f docker-compose.hub.yml down -v
docker compose -f docker-compose.hub.yml up -d
docker exec attendancedev-backend php artisan migrate:fresh --seed
```

---

## 📈 Spesifikasi VPS Recommended

| Usage | RAM | CPU | Storage | Provider Price |
|-------|-----|-----|---------|----------------|
| Development | 4GB | 2 vCPU | 40GB | ~$20/mo |
| Production (50 users) | 8GB | 4 vCPU | 80GB | ~$40/mo |
| Production (200 users) | 16GB | 8 vCPU | 160GB | ~$80/mo |

### Provider Recommendations

- **DigitalOcean**: $48/mo untuk 8GB Droplet
- **Vultr**: $40/mo untuk 8GB High Frequency
- **Linode**: $48/mo untuk 8GB Dedicated
- **Hetzner**: €15/mo untuk 8GB (paling murah, EU based)

---

*Dokumentasi ini terakhir diupdate: Desember 2025*
