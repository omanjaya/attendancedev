# Face Recognition Testing Guide

Panduan lengkap untuk testing sistem face recognition yang telah disempurnakan.

## 🧪 Jenis Testing

### 1. Unit Tests
**File:** `tests/Unit/Services/FaceRecognitionServiceTest.php`

```bash
# Jalankan unit tests untuk FaceRecognitionService
php artisan test tests/Unit/Services/FaceRecognitionServiceTest.php

# Jalankan test spesifik
php artisan test --filter="it_validates_face_descriptor_size"
```

**Coverage:**
- ✅ Validasi ukuran descriptor (128 float values)
- ✅ Validasi confidence score minimum (0.7)
- ✅ Perhitungan cosine similarity
- ✅ Algoritma quality scoring
- ✅ Liveness detection logic
- ✅ Best match finding algorithm
- ✅ Employee constraints verification
- ✅ Feature extraction dari face data
- ✅ Cache operations

### 2. Feature Tests
**File:** `tests/Feature/FaceRecognitionTest.php`

```bash
# Jalankan feature tests
php artisan test tests/Feature/FaceRecognitionTest.php

# Test dengan output verbose
php artisan test tests/Feature/FaceRecognitionTest.php --verbose
```

**Coverage:**
- ✅ Face registration workflow
- ✅ Face verification process
- ✅ Image upload handling
- ✅ Face data updates
- ✅ Face deletion
- ✅ Batch verification
- ✅ Activity logging
- ✅ Statistics generation
- ✅ Duplicate prevention
- ✅ Quality score calculation

### 3. API Tests
**File:** `tests/Feature/Api/FaceDetectionApiTest.php`

```bash
# Jalankan API tests
php artisan test tests/Feature/Api/FaceDetectionApiTest.php

# Test API endpoints secara individual
php artisan test --filter="it_can_register_face_via_api"
```

**Coverage:**
- ✅ POST `/api/v1/face-detection/register`
- ✅ POST `/api/v1/face-detection/verify`
- ✅ GET `/api/v1/face-detection/faces`
- ✅ PUT `/api/v1/face-detection/faces/{employee}`
- ✅ DELETE `/api/v1/face-detection/faces/{employee}`
- ✅ POST `/api/v1/face-detection/batch-verify`
- ✅ GET `/api/v1/face-detection/performance-metrics`
- ✅ Authentication & authorization
- ✅ Input validation
- ✅ Error handling

### 4. Browser Tests (Laravel Dusk)
**File:** `tests/Browser/FaceRecognitionBrowserTest.php`

```bash
# Setup Dusk jika belum
php artisan dusk:install

# Jalankan browser tests
php artisan dusk tests/Browser/FaceRecognitionBrowserTest.php

# Test individual browser scenario
php artisan dusk --filter="test_face_registration_workflow"
```

**Coverage:**
- ✅ Camera initialization
- ✅ Face registration UI workflow
- ✅ Attendance check-in dengan face recognition
- ✅ Liveness detection prompts
- ✅ Face quality feedback UI
- ✅ Camera permissions handling
- ✅ Error handling di UI
- ✅ Multiple faces detection warning
- ✅ Performance metrics dashboard
- ✅ Settings configuration

## 🔧 Manual Testing

### 1. Face Registration Manual Test

```bash
# 1. Buka browser ke /employees
# 2. Pilih employee yang belum punya face data
# 3. Klik "Register Face"
# 4. Test camera access
# 5. Posisikan wajah di frame
# 6. Perhatikan quality indicators:
#    - Lighting: Hijau = baik, Merah = buruk
#    - Pose: Frontal = baik, Miring = buruk  
#    - Confidence: >70% = baik
#    - Blur: Sharp = baik, Blurry = buruk
# 7. Klik "Capture Face"
# 8. Verify data tersimpan di database
```

### 2. Face Verification Manual Test

```bash
# 1. Pastikan employee sudah punya face data
# 2. Buka /attendance
# 3. Klik "Face Recognition Check-in"
# 4. Allow camera access
# 5. Test skenario:
#    - Wajah yang terdaftar (should succeed)
#    - Wajah orang lain (should fail)
#    - Multiple faces (should show warning)
#    - No face (should show warning)
#    - Poor lighting (should show quality warning)
```

### 3. Liveness Detection Test

```bash
# 1. Enable liveness detection di settings
# 2. Saat face verification, system akan prompt:
#    - "Please blink" - kedipkan mata
#    - "Please smile" - senyum
#    - "Please nod" - angguk
# 3. Test anti-spoofing:
#    - Gunakan foto/video (should fail)
#    - Gunakan real face (should pass)
```

## 📊 Performance Testing

### 1. Load Testing dengan Face Recognition

```bash
# Buat script untuk batch face verification
php artisan tinker

# Test batch verification performance
$faceData = array_fill(0, 50, [
    'descriptor' => array_fill(0, 128, rand(0, 100) / 100),
    'confidence' => 0.8
]);

$start = microtime(true);
$result = app(FaceRecognitionService::class)->batchVerify($faceData);
$duration = microtime(true) - $start;

echo "Processed " . count($faceData) . " faces in " . $duration . " seconds\n";
echo "Average: " . ($duration / count($faceData)) . " seconds per face\n";
```

### 2. Database Performance Test

```bash
# Test query performance dengan banyak face data
php artisan tinker

# Create test data
Employee::factory(1000)->create()->each(function ($employee) {
    $employee->update([
        'metadata' => [
            'face_recognition' => [
                'descriptor' => array_fill(0, 128, rand(0, 100) / 100),
                'confidence' => rand(70, 95) / 100,
                'quality_score' => rand(50, 100) / 100
            ]
        ]
    ]);
});

# Test repository performance
$start = microtime(true);
$faces = app(FaceRecognitionRepository::class)->getEmployeesWithFaceData();
$duration = microtime(true) - $start;
echo "Retrieved " . $faces->count() . " faces in " . $duration . " seconds\n";
```

## 🎯 Test Scenarios

### Success Scenarios
1. **Happy Path Registration**
   - Valid employee ID
   - Good quality image
   - Proper face data structure
   - Successful storage

2. **Happy Path Verification**
   - Registered employee
   - Good similarity match (>0.6)
   - Active employee status
   - Successful liveness check

3. **Batch Operations**
   - Multiple face verification
   - Performance within limits
   - Proper error handling

### Failure Scenarios
1. **Invalid Data**
   - Wrong descriptor size
   - Low confidence score
   - Missing required fields
   - Invalid employee ID

2. **Security Failures**
   - Unregistered face
   - Failed liveness detection
   - Inactive employee
   - Insufficient permissions

3. **System Failures**
   - Camera access denied
   - Network errors
   - Database connection issues
   - Storage failures

## 🚀 Running All Tests

```bash
# Jalankan semua face recognition tests
php artisan test --filter="FaceRecognition"

# Dengan coverage report
php artisan test --coverage --filter="FaceRecognition"

# Parallel testing untuk speed
php artisan test --parallel --filter="FaceRecognition"

# Generate HTML coverage report
php artisan test --coverage-html reports/coverage --filter="FaceRecognition"
```

## 📈 Performance Benchmarks

### Target Metrics
- **Face Registration:** < 2 seconds
- **Face Verification:** < 1 second  
- **Batch Verification (50 faces):** < 10 seconds
- **Database Query (1000+ faces):** < 500ms
- **API Response Time:** < 200ms
- **Browser Test Execution:** < 30 seconds per test

### Quality Thresholds
- **Minimum Confidence:** 0.7 (70%)
- **Similarity Threshold:** 0.6 (60%)
- **Liveness Score:** 0.8 (80%)
- **Quality Score:** 0.5 (50%)

## 🔍 Debugging Tips

### 1. Face Detection Issues
```bash
# Check face-api.js models loading
console.log('Models loaded:', window.faceapi?.nets?.tinyFaceDetector?.isLoaded);

# Debug face detection
const detections = await faceDetectionService.detectFaces(videoElement);
console.log('Detections:', detections);
```

### 2. Database Debugging
```bash
# Enable query logging
DB::enableQueryLog();
// ... run face recognition operations ...
dump(DB::getQueryLog());

# Check face_recognition_logs
DB::table('face_recognition_logs')->latest()->limit(10)->get();
```

### 3. Cache Issues
```bash
# Clear face recognition cache
php artisan cache:forget registered_faces
php artisan cache:forget face_recognition_statistics

# Monitor cache hits/misses
Cache::spy();
```

## ✅ Test Checklist

**Pre-Testing:**
- [ ] Database migrated dengan face_recognition_logs table
- [ ] Models face-api.js tersedia di /public/models
- [ ] Camera permissions granted
- [ ] Test user dengan proper permissions
- [ ] Storage disk 'private' configured

**Core Functionality:**
- [ ] Face registration works
- [ ] Face verification works
- [ ] Liveness detection active
- [ ] Quality scoring accurate
- [ ] Batch operations functional
- [ ] Statistics generation working

**Security:**
- [ ] Authentication required
- [ ] Permissions enforced
- [ ] Input validation active
- [ ] SQL injection prevented
- [ ] XSS protection enabled

**Performance:**
- [ ] Response times under targets
- [ ] Memory usage reasonable
- [ ] Database queries optimized
- [ ] Cache functioning properly
- [ ] Concurrent requests handled

**UI/UX:**
- [ ] Camera preview working
- [ ] Quality indicators visible
- [ ] Error messages clear
- [ ] Loading states shown
- [ ] Mobile responsive

Sistem face recognition sekarang memiliki comprehensive testing coverage untuk memastikan reliability dan performance yang optimal! 🎉