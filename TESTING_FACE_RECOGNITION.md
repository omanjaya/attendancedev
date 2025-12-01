# Testing Face Recognition Integration

Panduan lengkap untuk testing server-side face recognition end-to-end.

## Status Services

Pastikan semua services running:

```bash
# 1. Python Face Recognition Service
# Location: python-face-service/
# Port: 5000
cd python-face-service
source venv/bin/activate
python app.py

# Output yang diharapkan:
# ✅ Starting Face Recognition Service on port 5000
# ✅ Model: hog
# ✅ Tolerance: 0.6
# ✅ Running on http://127.0.0.1:5000

# 2. Laravel API
# Location: backend/
# Port: 8000
cd backend
php artisan serve

# Output yang diharapkan:
# ✅ Server running on http://127.0.0.1:8000

# 3. React Frontend
# Location: frontend/
# Port: 5173 (atau 5174 jika 5173 terpakai)
cd frontend
npm run dev

# Output yang diharapkan:
# ✅ Local: http://localhost:5173/
```

## Verification Checklist

### 1. Python Service Health Check

```bash
curl http://127.0.0.1:5000/health
```

**Expected response:**
```json
{
  "status": "ok",
  "service": "face-recognition",
  "version": "1.0.0",
  "model": "hog",
  "timestamp": "2025-12-01T..."
}
```

### 2. Laravel API Routes Check

```bash
php artisan route:list | grep face-recognition
```

**Expected output:**
```
POST   api/v1/face-recognition/extract-encoding-server
POST   api/v1/face-recognition/verify-server
```

### 3. Frontend API Configuration

File: `frontend/src/lib/api/client.ts`

```typescript
const API_URL = 'http://localhost:8000/api/v1';  // ✅ Sudah benar
```

---

## End-to-End Testing Flow

### Prerequisites

1. **Database harus ada employee dengan face_descriptor**
   ```sql
   -- Check employees dengan face data
   SELECT id, employee_code, full_name,
          CASE WHEN face_descriptor IS NOT NULL THEN 'YES' ELSE 'NO' END as has_face
   FROM employees;
   ```

2. **User harus sudah login**
   - Buka browser: http://localhost:5173/login
   - Login dengan credentials yang valid
   - Pastikan redirect ke dashboard berhasil

### Testing Steps

#### Step 1: Navigate to Attendance Page

1. Buka browser: http://localhost:5173/
2. Login jika belum
3. Klik menu "Attendance" atau navigate ke `/attendance`
4. Pastikan muncul tombol **"DATANG"** dan **"PULANG"**

#### Step 2: Click "Datang" (Check-In)

1. Klik tombol hijau **"DATANG"**
2. Akan redirect ke `/attendance/verify-location`

**Expected behavior:**
- ✅ GPS permission diminta oleh browser
- ✅ Halaman menampilkan map dengan current location
- ✅ Validasi radius (jika di luar radius, tombol "Selanjutnya" disabled)
- ✅ Jika di dalam radius, tombol "Selanjutnya" enabled

**Troubleshooting GPS:**
- Jika GPS tidak akurat, gunakan browser location spoofing (Chrome DevTools)
- Atau untuk testing, set radius sangat besar (999999 meter)

#### Step 3: Verify Location

1. Pastikan GPS mendeteksi lokasi Anda
2. Pastikan status menunjukkan "Di dalam area" (atau sesuaikan radius di admin)
3. Klik tombol **"Selanjutnya"**
4. Akan redirect ke `/attendance/verify-face`

#### Step 4: Face Recognition

**Expected UI:**
- ✅ Camera permission diminta oleh browser
- ✅ Video preview muncul (live camera feed)
- ✅ Status: "Posisikan wajah Anda, scanning otomatis dalam 2 detik..."
- ✅ Auto-capture setelah 2 detik
- ✅ Captured image ditampilkan
- ✅ Status berubah: "Memverifikasi wajah dengan server..."
- ✅ Badge muncul di header menunjukkan ukuran image (misal: "150 KB")

**After 2-3 seconds:**
- ✅ Status berubah ke "Wajah terverifikasi!" (jika match)
- ✅ Atau "Wajah tidak dikenali" (jika tidak match)

**If face matched:**
```
✅ Wajah Ditemukan!
Wajah terverifikasi!

👤 Nama Karyawan
   [Employee Name]
   [Employee Code]

   Similarity: 85.5%
   Confidence: 78%
   Distance: 0.145
   Algorithm: dlib (server-side)

[Batal] [✓ Konfirmasi Kehadiran]
```

**If face NOT matched:**
```
❌ Wajah tidak dikenali
Silakan coba lagi atau hubungi admin untuk registrasi wajah

[📷 Coba Lagi]
```

#### Step 5: Confirm Attendance

1. Review data yang ditampilkan (nama, similarity score)
2. Klik tombol **"Konfirmasi Kehadiran"**
3. Status berubah: "Menyimpan absensi..."
4. Alert muncul: "Absensi datang berhasil!"
5. Redirect kembali ke `/attendance`

**Expected result di halaman attendance:**
- ✅ Tombol "DATANG" sekarang menampilkan jam check-in (misal: "08:15")
- ✅ Icon berubah dari ChevronRight → Clock
- ✅ Tombol "PULANG" masih aktif untuk check-out nanti

---

## Monitoring Logs

### Python Service Logs

Buka terminal tempat Python service running. Saat face verification, akan muncul log:

```
2025-12-01 08:15:23 - werkzeug - INFO - 127.0.0.1 - POST /verify-face HTTP/1.1 200
2025-12-01 08:15:23 - __main__ - INFO - Face detected and matched
```

**Atau jika tidak ada face:**
```
2025-12-01 08:15:23 - __main__ - WARNING - No face detected in image
2025-12-01 08:15:23 - werkzeug - INFO - 127.0.0.1 - POST /verify-face HTTP/1.1 422
```

### Laravel Logs

```bash
tail -f backend/storage/logs/laravel.log
```

Expected log saat verifikasi:
```
[2025-12-01 08:15:23] local.INFO: Face verification request received
[2025-12-01 08:15:23] local.INFO: Forwarding to Python service: http://127.0.0.1:5000/verify-face
[2025-12-01 08:15:25] local.INFO: Face matched: Employee #123
```

### Browser DevTools Console

Buka Chrome DevTools (F12) → Console tab

Expected logs:
```javascript
// Saat capture
Image size: 150 KB

// Saat verifying
Verifying face with server...

// Saat berhasil
Matched: John Doe
Similarity: 0.855
Distance: 0.145
Confidence: 0.78
```

### Browser DevTools Network Tab

Buka Network tab, filter by "XHR"

Expected requests:
1. **POST** `/api/v1/face-recognition/verify-server`
   - Request Payload: `{ "image": "data:image/jpeg;base64,...", "tolerance": 0.6 }`
   - Response: `{ "success": true, "matched": true, "data": {...} }`
   - Status: 200 OK
   - Time: ~1-2 seconds

2. **POST** `/api/v1/attendance/check-in`
   - Request Payload: `{ "face_confidence": 0.85, "latitude": ..., "longitude": ... }`
   - Response: `{ "success": true, "message": "Check-in successful" }`
   - Status: 200 OK

---

## Common Issues & Solutions

### Issue 1: "Tidak dapat mengakses kamera"

**Problem:**
- Browser tidak mengizinkan akses kamera

**Solution:**
```
1. Pastikan menggunakan HTTPS atau localhost
2. Klik icon kamera di address bar → Allow
3. Atau Chrome Settings → Privacy and Security → Site Settings → Camera → Allow localhost
4. Refresh halaman
```

### Issue 2: "Wajah tidak dikenali" (padahal sudah ada face_descriptor)

**Problem:**
- Similarity terlalu rendah
- Face encoding di database tidak cocok dengan wajah saat ini

**Solution:**
```sql
-- 1. Check face_descriptor exists
SELECT id, employee_code, full_name,
       LENGTH(face_descriptor) as descriptor_length
FROM employees
WHERE id = [employee_id];

-- 2. Descriptor should be ~500-1000 characters (128 float values in JSON)
-- If NULL atau terlalu pendek, re-enroll face

-- 3. Adjust tolerance (di Python service .env atau Laravel request)
-- Increase dari 0.6 ke 0.7 untuk lebih lenient
```

**Re-enroll face:**
1. Login sebagai admin
2. Go to Employees → Edit employee
3. Upload foto wajah baru
4. System akan auto-extract face encoding dan save ke database

### Issue 3: "Failed to fetch" atau Network Error

**Problem:**
- Python service tidak running
- Laravel tidak bisa connect ke Python service

**Solution:**
```bash
# 1. Check Python service
curl http://127.0.0.1:5000/health

# 2. Check Laravel .env
grep FACE_RECOGNITION backend/.env
# Should show:
# FACE_RECOGNITION_SERVICE_URL=http://127.0.0.1:5000

# 3. Test from Laravel server
curl http://127.0.0.1:5000/health

# 4. Restart Python service
cd python-face-service
source venv/bin/activate
python app.py
```

### Issue 4: Image terlalu besar / Upload timeout

**Problem:**
- Compressed image masih terlalu besar
- Network lambat

**Solution:**
```typescript
// Edit frontend/src/lib/utils/imageCompression.ts
// Reduce quality atau resolution:

const { dataUrl } = await captureAndCompress(video, {
  maxWidth: 480,     // Dari 640 → 480
  maxHeight: 360,    // Dari 480 → 360
  quality: 0.7,      // Dari 0.8 → 0.7
  mimeType: 'image/jpeg',
});

// Ini akan menghasilkan image ~50-100 KB instead of 150-200 KB
```

### Issue 5: "No face detected in image"

**Problem:**
- Pencahayaan kurang
- Wajah terlalu kecil di frame
- Angle wajah terlalu miring
- Image terlalu blur

**Solution:**
```
1. Posisikan wajah lebih dekat ke kamera
2. Pastikan pencahayaan cukup (tidak backlight)
3. Hadap langsung ke kamera (frontal face)
4. Tunggu beberapa detik sebelum auto-capture
5. Gunakan manual capture button jika auto-capture gagal
```

### Issue 6: Face terdeteksi tapi similarity rendah (<60%)

**Expected similarity scores:**
- **85-100%**: Sangat mirip (same person, same conditions)
- **70-85%**: Mirip (same person, different conditions)
- **60-70%**: Cukup mirip (borderline, perlu review)
- **<60%**: Tidak mirip (different person atau conditions sangat beda)

**Factors affecting similarity:**
- ✅ Pencahayaan (bright vs dark)
- ✅ Angle wajah (frontal vs side)
- ✅ Ekspresi (smile vs serious)
- ✅ Accessories (kacamata, masker, topi)
- ✅ Waktu (foto lama vs sekarang, aging)
- ✅ Resolution (high-res vs low-res)

**Solution:**
```python
# Adjust tolerance in:
# 1. Python service (.env)
FACE_RECOGNITION_TOLERANCE=0.65  # Increase dari 0.6

# 2. Laravel (.env)
FACE_RECOGNITION_TOLERANCE=0.65

# 3. Frontend (verify-face.tsx) - line ~133
const result = await verifyFaceServer({
  image: dataUrl,
  tolerance: 0.65,  // Increase dari 0.6
});
```

---

## Performance Benchmarks

Expected performance metrics:

| Metric | Expected Value | Notes |
|--------|---------------|-------|
| Image compression time | 50-200ms | Client-side, depends on device |
| Image upload time | 100-500ms | Depends on network speed |
| Face detection time | 200-500ms | Python service (HOG model, CPU) |
| Face encoding time | 300-700ms | Python service |
| Face comparison time | 1-5ms/employee | Python service |
| **Total verification time** | **500-1500ms** | From capture to result |

**Optimization tips:**
- 🚀 Use CNN model with GPU: 3-5x faster (50-150ms detection)
- 🚀 Redis cache for employee encodings: Reduces DB query time
- 🚀 CDN for static assets: Faster frontend loading
- 🚀 Database indexing: Faster employee lookups

---

## Test Data Setup

### Sample Employee with Face Data

```sql
-- Insert test employee (if not exists)
INSERT INTO employees (
  employee_code,
  full_name,
  email,
  face_descriptor
) VALUES (
  'TEST001',
  'Test Employee',
  'test@example.com',
  '[0.1, 0.2, 0.3, ...]'  -- 128 float values
);

-- Create user account for employee
INSERT INTO users (email, password)
VALUES ('test@example.com', '$2y$10$...');  -- Hashed password

-- Link employee to user
UPDATE employees SET user_id = [user_id] WHERE employee_code = 'TEST001';
```

**To get face_descriptor:**
1. Use admin panel: Upload foto wajah
2. Or use Python script:
   ```python
   from face_recognition import face_encodings, load_image_file

   image = load_image_file("test_face.jpg")
   encodings = face_encodings(image)

   if encodings:
       encoding = encodings[0].tolist()
       print(encoding)  # Save ke database
   ```

---

## Success Criteria

Testing dianggap berhasil jika:

- ✅ All 3 services running without errors
- ✅ Camera access granted di browser
- ✅ GPS location detected accurately
- ✅ Image compressed to <200 KB
- ✅ Face detected dalam <1 second
- ✅ Face matched dengan similarity >60%
- ✅ Employee name displayed correctly
- ✅ Attendance saved to database
- ✅ Check-in time displayed di attendance page
- ✅ No errors di console atau logs
- ✅ Total flow time <5 seconds (dari klik "Datang" sampai selesai)

---

## Next Steps After Testing

1. **Production deployment** - Deploy ke VPS (lihat `ingetinikalomaudeploykeproduction.md`)
2. **Fine-tuning tolerance** - Adjust based on real-world usage
3. **Performance optimization** - Redis cache, CDN, database indexing
4. **Security hardening** - Rate limiting, input validation, HTTPS
5. **Monitoring setup** - Sentry, logging, metrics
6. **User training** - Dokumentasi untuk end users
7. **Backup strategy** - Database backups, face data backups

---

**File:** `TESTING_FACE_RECOGNITION.md`
**Created:** 2025-12-01 03:30 UTC
**Purpose:** End-to-end testing guide untuk server-side face recognition
**Related:** `IMPLEMENTATION_SUMMARY.md`, `UI_CONTEXT_MOBILE_VS_DESKTOP.md`
