# Rencana Migrasi ke Single Frontend (React Only)

Saat ini, project memiliki dua layer frontend yang berjalan bersamaan:
1. **Laravel Blade + Vite** (Port 5173) - Frontend bawaan Laravel (kurang terpakai).
2. **React App** (Port 5174) - Frontend utama yang sedang dikembangkan.

Untuk merapikan struktur dan fokus pada satu frontend saja, berikut adalah rencana migrasinya:

## 1. Restrukturisasi Folder
Agar lebih jelas, kita akan memisahkan backend dan frontend secara eksplisit.

- **`attendance-react/`** -> Di-rename menjadi **`frontend/`**.
- Root folder (`/`) tetap menjadi **Backend (Laravel API)**.

## 2. Penyesuaian Port & Server
Kita akan mematikan server aset Laravel yang tidak perlu agar tidak memakan port 5173.

- **Backend (Laravel)**: Tetap berjalan di port **8000** (API Only).
- **Frontend (React)**: Akan kita set agar berjalan di port **3000** (Standar React) atau **5173** (Default Vite).

## 3. Update Script Development (`start-dev.sh`)
Script `start-dev.sh` saat ini menjalankan `npm run dev` di root folder (yang menyalakan server aset Laravel). Kita akan mengubahnya agar:
1. Menjalankan `php artisan serve` (Backend).
2. Menjalankan `npm run dev` **hanya di dalam folder frontend**.

## 4. Cleanup (Pembersihan)
Setelah migrasi berjalan lancar, kita bisa:
- Menghapus `package.json` dan `vite.config.js` di root folder (milik Laravel) jika tidak ada aset backend yang butuh dikompilasi.
- Menghapus file-file view Blade (`resources/views`) yang tidak terpakai.
- Mengubah `routes/web.php` agar hanya melayani API atau redirect ke frontend.

---

## Langkah Eksekusi (Otomatis)

Jika Anda setuju, saya akan menjalankan langkah-langkah berikut sekarang:

1. Rename folder `attendance-react` menjadi `frontend`.
2. Update `start-dev.sh` untuk hanya menjalankan React frontend.
3. Update konfigurasi port React ke 3000 (agar lebih umum).
4. Restart server development.

**Apakah Anda setuju untuk melanjutkan eksekusi ini?**
