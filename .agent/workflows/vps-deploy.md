---
description: Deploy Attendance System ke VPS dengan Docker
---

# VPS Deployment Workflow

Workflow ini untuk men-deploy Attendance System di VPS dengan Docker.

## Prerequisites Check

// turbo

1. Pastikan Docker terinstall:

```bash
docker --version && docker compose version
```

// turbo
2. Pastikan Git terinstall:

```bash
git --version
```

## Clone Repository

// turbo
3. Clone repository ke /opt:

```bash
cd /opt && git clone https://github.com/omanjaya/attendancedev.git && cd attendancedev
```

## Setup Environment

// turbo
4. Copy environment file:

```bash
cp .env.docker.example .env
```

// turbo
5. Generate secure credentials:

```bash
APP_KEY=$(openssl rand -base64 32)
DB_PASS=$(openssl rand -hex 16)
REDIS_PASS=$(openssl rand -hex 16)
sed -i "s|APP_KEY=.*|APP_KEY=base64:$APP_KEY|g" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASS|g" .env
sed -i "s|REDIS_PASSWORD=.*|REDIS_PASSWORD=$REDIS_PASS|g" .env
echo "Generated credentials:"
echo "DB_PASSWORD=$DB_PASS"
echo "REDIS_PASSWORD=$REDIS_PASS"
```

6. **MANUAL**: Edit .env untuk set IP VPS (tanyakan user untuk IP):

```bash
nano .env
```

Update baris berikut dengan IP VPS yang benar:

- `APP_URL=http://YOUR_VPS_IP`
- `VITE_API_BASE_URL=http://YOUR_VPS_IP/api`
- `VITE_DEEPFACE_URL=http://YOUR_VPS_IP/deepface`

## Build & Start Docker

7. Build dan start semua services (akan memakan waktu ~10-15 menit pertama kali):

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

// turbo
8. Tunggu dan cek status containers:

```bash
sleep 30 && docker compose ps
```

## Database Setup

9. Jalankan migrations dan seeders:

```bash
docker exec attendancedev-backend php artisan migrate --seed --force
```

// turbo
10. Clear dan cache config:

```bash
docker exec attendancedev-backend php artisan config:cache
docker exec attendancedev-backend php artisan route:cache
docker exec attendancedev-backend php artisan view:cache
```

## Verification

// turbo
11. Test API health:

```bash
curl -s http://localhost/api/health || echo "API belum ready, tunggu sebentar"
```

// turbo
12. Cek logs jika ada masalah:

```bash
docker compose logs --tail=50
```

## Done

Aplikasi seharusnya sudah bisa diakses di:

- Frontend: `http://YOUR_VPS_IP`
- API: `http://YOUR_VPS_IP/api`

Default login:

- Email: `admin@school.edu`
- Password: `password`

## Troubleshooting

Jika ada masalah:

// turbo

- Cek status containers: `docker compose ps`
- Cek logs backend: `docker logs attendancedev-backend`
- Cek logs nginx: `docker logs attendancedev-nginx`
- Restart services: `docker compose restart`
