# 🚀 VPS Deployment Prompt untuk Claude Code

Copy prompt di bawah ini dan paste ke Claude Code di VPS Anda.

---

## Prompt untuk Fresh VPS (Tanpa Docker)

```
Saya mau deploy Attendance System ke VPS ini.

Tolong clone Repository https://github.com/omanjaya/attendancedev.git ke folder /var/www/attendancedev

Tolong bantu saya:

1. Baca dokumentasi deployment terlebih dahulu:
   - CLAUDE.md (overview project)
   - docs/DEPLOYMENT.md (panduan deployment)
   - scripts/install-vps.sh (script instalasi)

2. Jalankan script instalasi VPS:
   sudo DOMAIN=absensi.yourdomain.com bash scripts/install-vps.sh

3. Setelah selesai, setup SSL dengan:
   sudo certbot --nginx -d absensi.yourdomain.com

4. Verify semua services berjalan dengan baik:
   - Backend Laravel (PHP-FPM)
   - Frontend React (served by Nginx)
   - PostgreSQL database
   - Redis cache
   - Python DeepFace service
   - Supervisor (queue workers)

5. Simpan dan catat semua credentials yang di-generate:
   - Database password
   - Redis password
   - Admin login credentials

Catatan:
- VPS ini Ubuntu 22.04 LTS
- Domain: absensi.yourdomain.com (ganti dengan domain Anda)
- Ini fresh VPS, belum ada software yang terinstall
- Pastikan gunakan Nginx sebagai web server
- Database menggunakan PostgreSQL

Mohon jalankan step by step dan konfirmasi setiap tahapnya.
```

---

## Prompt untuk VPS dengan Docker

```
Saya mau deploy Attendance System ke VPS ini menggunakan Docker.

Tolong clone Repository https://github.com/omanjaya/attendancedev.git ke folder /opt/attendancedev

Tolong bantu saya:

1. Baca dokumentasi terlebih dahulu:
   - CLAUDE.md (overview project)
   - docs/DOCKER.md (panduan Docker deployment)
   - docker-compose.prod.yml (production config)

2. Install Docker jika belum ada:
   curl -fsSL https://get.docker.com | sh
   sudo usermod -aG docker $USER

3. Setup environment:
   cd /opt/attendancedev
   cp .env.docker.example .env
   # Edit .env dengan konfigurasi yang benar

4. Build dan jalankan containers:
   docker compose -f docker-compose.prod.yml up -d

5. Jalankan database migration:
   docker compose exec backend php artisan migrate --seed

6. Setup SSL dengan Certbot:
   docker compose --profile ssl up -d certbot

7. Verify semua services berjalan:
   docker compose ps
   docker compose logs --tail=50

8. Catat credentials:
   - Database: attendance_user / [password di .env]
   - Admin: admin@school.edu / password

Catatan:
- VPS ini Ubuntu 22.04 LTS
- Domain: absensi.yourdomain.com
- Menggunakan Docker untuk deployment
- Minimum RAM: 4GB (8GB recommended untuk DeepFace)

Mohon jalankan step by step dan konfirmasi setiap tahapnya.
```

---

## File yang Harus Dibaca Claude

Sebelum deployment, pastikan Claude membaca file-file ini:

### Wajib Dibaca

| File | Deskripsi |
|------|-----------|
| `CLAUDE.md` | Overview project, struktur folder, tech stack |
| `docs/DEPLOYMENT.md` | Panduan deployment manual |
| `docs/DOCKER.md` | Panduan deployment Docker |
| `.env.docker.example` | Template environment variables |

### Opsional (untuk troubleshooting)

| File | Deskripsi |
|------|-----------|
| `docs/PERFORMANCE.md` | Optimasi performance |
| `docs/TROUBLESHOOTING.md` | Panduan troubleshooting |
| `docs/API.md` | Dokumentasi API endpoints |

---

## Checklist Sebelum Deploy

- [ ] Domain sudah pointing ke IP VPS (A record)
- [ ] VPS memiliki minimal 4GB RAM
- [ ] Port 80, 443, 22 terbuka di firewall
- [ ] SSH access ke VPS tersedia
- [ ] Clone repository berhasil

---

## Contoh Output yang Diharapkan

Setelah deployment berhasil, Anda akan mendapatkan:

```
============================================
🎉 INSTALLATION COMPLETE!
============================================

📝 CREDENTIALS (SIMPAN DENGAN AMAN):
   - Database Password: xxxxxxxxxx
   - Redis Password: xxxxxxxxxx
   - Admin Email: admin@school.edu
   - Admin Password: password

🌐 ACCESS URLs:
   - Frontend: https://absensi.yourdomain.com
   - API: https://absensi.yourdomain.com/api
   - DeepFace: https://absensi.yourdomain.com/deepface

✅ SERVICES STATUS:
   - nginx: running
   - php-fpm: running
   - postgresql: running
   - redis: running
   - deepface: running
   - supervisor: running

============================================
```

---

## Troubleshooting Quick Commands

Jika ada masalah, gunakan perintah ini:

```bash
# Check all services
sudo systemctl status nginx php8.2-fpm postgresql redis-server supervisor

# Check logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/www/attendancedev/backend/storage/logs/laravel.log

# Restart services
sudo systemctl restart nginx php8.2-fpm

# Test database connection
sudo -u postgres psql -c "SELECT 1"

# Test Redis
redis-cli ping

# Test DeepFace
curl http://localhost:8001/health
```
