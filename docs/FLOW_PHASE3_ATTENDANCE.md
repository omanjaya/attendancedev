# FLOW PHASE 3: ATTENDANCE & FACE RECOGNITION

Dokumentasi lengkap untuk sistem absensi dengan verifikasi wajah menggunakan DeepFace (ArcFace).

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Face Registration Flow](#1-face-registration-flow)
3. [Attendance Check-In Flow](#2-attendance-check-in-flow)
4. [Attendance Check-Out Flow](#3-attendance-check-out-flow)
5. [Attendance Dashboard (Employee)](#4-attendance-dashboard-employee)
6. [Attendance Management (Admin)](#5-attendance-management-admin)
7. [DeepFace Service Integration](#6-deepface-service-integration)
8. [Database Schema](#7-database-schema)
9. [Security & Anti-Spoofing](#8-security--anti-spoofing)

---

## Architecture Overview

```
┌─────────────────┐
│  React Frontend │ (Camera, Face Detection UI)
└────────┬────────┘
         │ HTTPS
         ▼
┌─────────────────┐
│ Laravel Backend │ (API, Business Logic)
└────────┬────────┘
         │ HTTP
         ▼
┌─────────────────┐
│ DeepFace Python │ (ArcFace 512-d, Liveness)
│   Service(s)    │
└─────────────────┘
         │
         ▼
┌─────────────────┐
│   PostgreSQL    │ (Attendance, Employee Metadata)
└─────────────────┘
```

**Tech Stack:**
- **Frontend**: React 19, TypeScript, TanStack Router/Query, Tailwind CSS 4
- **Backend**: Laravel 12, PHP 8.3
- **Face Service**: Python FastAPI + DeepFace (ArcFace model)
- **Database**: PostgreSQL 16 (JSONB for face embeddings)
- **Cache**: Redis 7

**Key Models:**
- **ArcFace**: 512-dimensional face embeddings (high accuracy)
- **RetinaFace**: Face detector backend
- **Cosine Distance**: Similarity metric (threshold: 0.68)

---

## 1. Face Registration Flow

### 1.1 Overview

Proses registrasi wajah dilakukan saat employee pertama kali mendaftar atau update face data.

```
┌────────┐
│ Start  │
└───┬────┘
    │
    ▼
┌──────────────────┐
│ Open Camera      │
└───┬──────────────┘
    │
    ▼
┌──────────────────┐
│ Detect Face      │ (RetinaFace)
│ - Quality Check  │
│ - Position Check │
└───┬──────────────┘
    │
    ▼
┌──────────────────┐
│ Optional:        │
│ Liveness Check   │ (Smile Detection)
└───┬──────────────┘
    │
    ▼
┌──────────────────┐
│ Capture Photo    │
└───┬──────────────┘
    │
    ▼
┌──────────────────┐
│ Extract          │
│ Embedding        │ (POST /deepface/extract-embedding)
│ - 512-d vector   │
└───┬──────────────┘
    │
    ▼
┌──────────────────┐
│ Save to Database │
│ - Store in JSONB │
│ - Employee       │
│   metadata       │
└───┬──────────────┘
    │
    ▼
┌────────┐
│ Success│
└────────┘
```

### 1.2 Frontend Components

**File**: `/opt/attendancedev/frontend/src/components/attendance/auto-capture-face.tsx`

```tsx
// Auto-capture face component with liveness detection
export function AutoCaptureFace({
    onCapture,
    onError,
    autoCapture = true,
    confidenceThreshold = 0.5,
    requireSmile = true,
}: AutoCaptureFaceProps) {
    // Uses custom hook for face detection
    const {
        videoRef,
        isInitialized,
        cameraStatus,
        confidence,
        isSmiling,
        startCamera,
        startDetection,
    } = useFaceDetection();

    // Flow:
    // 1. Initialize camera
    // 2. Start face detection
    // 3. Wait for good quality face
    // 4. (Optional) Prompt for smile
    // 5. Auto-capture when conditions met
}
```

**Key Features:**
- Real-time face quality assessment
- Brightness check
- Confidence threshold (0.5 default)
- Optional smile detection for liveness
- Auto-capture with visual flash effect
- Haptic feedback on capture

### 1.3 API Endpoints

#### Extract Face Embedding (DeepFace)

**Endpoint**: `POST /api/v1/face/deepface/extract-embedding`

**Request**:
```typescript
FormData {
  image: File (jpg/jpeg/png, max 10MB)
}
```

**Response**:
```json
{
  "success": true,
  "embedding": [0.234, -0.123, ...], // 512-d array
  "dimension": 512,
  "confidence": 0.95,
  "quality": {
    "blur_score": 234.5,
    "is_blurry": false,
    "brightness": 128.3,
    "is_too_dark": false,
    "is_too_bright": false,
    "quality_ok": true
  },
  "facial_area": {
    "x": 100, "y": 80, "w": 200, "h": 240,
    "confidence": 0.99
  },
  "model": "ArcFace"
}
```

**Error Responses**:
- `400`: No face detected
- `500`: Service error

#### Register Face

**Endpoint**: `POST /api/v1/face-recognition/register`

**Request**:
```json
{
  "employee_id": "uuid-string",
  "descriptor": [0.234, -0.123, ...], // 512-d array
  "confidence": 0.95,
  "algorithm": "deepface-arcface",
  "model_version": "1.0",
  "device_info": {
    "user_agent": "...",
    "platform": "web"
  }
}
```

**Response**:
```json
{
  "success": true,
  "message": "Face registered successfully",
  "data": {
    "employee_id": "uuid",
    "confidence": 0.95,
    "quality_score": 0.87,
    "image_stored": true
  }
}
```

**Error Scenarios**:
- `422`: Invalid descriptor format (must be 512-d)
- `409`: Employee already has registered face
- `403`: Unauthorized to manage employee face data

### 1.4 Backend Logic

**Service**: `/opt/attendancedev/backend/app/Services/FaceRecognitionService.php`

```php
public function registerFace(
    Employee $employee,
    array $descriptor,
    ?UploadedFile $image = null,
    array $metadata = []
): array {
    return DB::transaction(function () use ($employee, $descriptor, $image, $metadata) {
        // 1. Validate descriptor (must be 512-d or 128-d)
        if (!$this->validateDescriptor($descriptor)) {
            throw new \InvalidArgumentException('Invalid face descriptor');
        }

        // 2. Check for duplicate registration
        if ($this->hasFaceRegistered($employee)) {
            throw new \Exception('Employee already has registered face');
        }

        // 3. Store face image (private storage, NOT avatar)
        $imagePath = null;
        if ($image) {
            $imagePath = $this->storeFaceImage($image, $employee->id);
        }

        // 4. Prepare face recognition data
        $faceRecognitionData = [
            'descriptor' => $descriptor,
            'confidence' => $metadata['confidence'] ?? 0.95,
            'algorithm' => 'deepface-arcface',
            'model_version' => '1.0',
            'registered_at' => now()->toISOString(),
            'image_path' => $imagePath,
            'quality_score' => $this->calculateQualityScore(['descriptor' => $descriptor]),
        ];

        // 5. Store in employee metadata (JSONB)
        $metadata = $employee->metadata ?? [];
        $metadata['face_recognition'] = $faceRecognitionData;
        $employee->update(['metadata' => $metadata]);

        // 6. Clear face cache
        $this->clearFaceCache();

        // 7. Log activity
        $this->logFaceActivity('register', $employee->id, [
            'confidence' => $faceRecognitionData['confidence'],
            'quality_score' => $faceRecognitionData['quality_score'],
        ]);

        return [
            'success' => true,
            'employee_id' => $employee->id,
            'confidence' => $faceRecognitionData['confidence'],
            'quality_score' => $faceRecognitionData['quality_score'],
        ];
    });
}
```

### 1.5 Multiple Face Registration

**Update Existing Face**:

**Endpoint**: `POST /api/v1/face-recognition/update`

**Use Cases**:
- Employee mengubah penampilan (haircut, glasses, etc.)
- Quality improvement
- Algorithm upgrade

**Backend**:
```php
public function updateFaceData(
    Employee $employee,
    array $descriptor,
    array $metadata = []
): bool {
    return DB::transaction(function () use ($employee, $descriptor, $metadata) {
        // 1. Backup existing face data
        $this->backupFaceData($employee);

        // 2. Delete old face image (if exists)
        if (isset($employee->metadata['face_recognition']['image_path'])) {
            $this->deleteFaceImage($employee->metadata['face_recognition']['image_path']);
        }

        // 3. Update with new data
        $faceRecognitionData = [
            'descriptor' => $descriptor,
            'confidence' => $metadata['confidence'],
            'updated_at' => now()->toISOString(),
            'update_count' => ($employee->metadata['face_recognition']['update_count'] ?? 0) + 1,
        ];

        $metadata = $employee->metadata ?? [];
        $metadata['face_recognition'] = $faceRecognitionData;
        $employee->update(['metadata' => $metadata]);

        return true;
    });
}
```

**Backup Strategy**:
- Old face data stored in `/storage/private/face-backups/{employee_id}/{timestamp}.json`
- Allows rollback if needed
- Audit trail for compliance

---

## 2. Attendance Check-In Flow

### 2.1 Complete Flow Diagram

```
┌─────────────┐
│   Start     │
│  Check-In   │
└──────┬──────┘
       │
       ▼
┌──────────────────────┐
│ Validate Schedule    │ ◄─── CRITICAL: Must have schedule
│ - Check can_attend   │
│ - Holiday check      │
│ - Teaching schedule  │
└──────┬───────────────┘
       │ ✓ Has Schedule
       ▼
┌──────────────────────┐
│ Validate Time        │ (POST /attendance/validate-time)
│ - checkin_start_time │
│ - Too early check    │
└──────┬───────────────┘
       │ ✓ Time OK
       ▼
┌──────────────────────┐
│ Get GPS Location     │
│ - navigator.         │
│   geolocation        │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Verify Location      │ (POST /locations/verify)
│ - Calculate distance │
│ - Check radius       │
└──────┬───────────────┘
       │ ✓ Within Radius
       ▼
┌──────────────────────┐
│ Open Camera          │
│ - Request permission │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Face Detection       │
│ - Quality check      │
│ - Confidence > 0.5   │
└──────┬───────────────┘
       │ ✓ Good Face
       ▼
┌──────────────────────┐
│ Liveness Check       │ (Optional: Smile)
│ - Smile detection    │
│ - Anti-spoofing      │
└──────┬───────────────┘
       │ ✓ Live Face
       ▼
┌──────────────────────┐
│ Capture Photo        │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Extract Embedding    │ (POST /deepface/extract-embedding)
│ - 512-d vector       │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Verify Face          │ (POST /deepface/verify-face)
│ - Match against DB   │
│ - Cosine distance    │
│ - Threshold: 0.68    │
└──────┬───────────────┘
       │ ✓ Match Found
       ▼
┌──────────────────────┐
│ Submit Check-In      │ (POST /attendance-face/check-in)
│ - Save attendance    │
│ - Determine status   │
│ - Send notification  │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Success Screen       │
│ - Show employee      │
│ - Show status        │
│ - Redirect to dash   │
└──────────────────────┘
```

### 2.2 Frontend Flow

**Page**: `/opt/attendancedev/frontend/src/pages/shared/attendance-verification.tsx`

```tsx
export function AttendanceVerificationPage() {
    const [state, setState] = useState<VerificationState>({
        step: 'location',
        message: 'Memeriksa jadwal...',
        progress: 5,
    });

    // Step 1: Check schedule
    useEffect(() => {
        const canAttend = dashboardData?.schedule?.today?.can_attend === true;

        if (!canAttend) {
            setState({
                step: 'error',
                locationError: 'Tidak ada jadwal untuk absen hari ini',
            });
        }
    }, [dashboardData]);

    // Step 2: Get GPS location
    useEffect(() => {
        if (state.step !== 'location') return;

        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const { latitude, longitude } = position.coords;

                // Step 3: Verify location
                const result = await verifyLocation({ latitude, longitude });

                if (result.verified) {
                    setState({
                        step: 'location_verified',
                        message: 'Lokasi terverifikasi!',
                        progress: 40,
                    });
                } else {
                    setState({
                        step: 'error',
                        locationError: `Anda ${result.distance}m dari kantor`,
                    });
                }
            },
            (error) => {
                setState({
                    step: 'error',
                    locationError: 'Gagal mendapatkan lokasi GPS',
                });
            }
        );
    }, [state.step]);

    // Step 4-7: Face verification (handled by AutoCaptureFace component)
    const handleFaceCapture = async (videoElement: HTMLVideoElement) => {
        // Extract embedding from video frame
        const blob = await captureFrameAsBlob(videoElement);
        const embedding = await extractEmbedding(blob);

        // Verify face
        const verification = await verifyFace(blob);

        if (verification.matched) {
            setState({
                step: 'submitting',
                faceVerified: true,
                employeeName: verification.employee.name,
            });

            // Step 8: Submit check-in
            await submitAttendance();
        } else {
            setState({
                step: 'error',
                faceError: 'Wajah tidak dikenali',
            });
        }
    };
}
```

### 2.3 API Endpoints

#### Validate Time

**Endpoint**: `POST /api/v1/attendance/validate-time`

**Request**:
```json
{
  "type": "check_in"
}
```

**Response (Success)**:
```json
{
  "data": {
    "allowed": true,
    "message": "Silakan lanjutkan absensi",
    "server_time": "08:30:45"
  }
}
```

**Response (Too Early)**:
```json
{
  "data": {
    "allowed": false,
    "message": "Belum waktunya absen masuk. Absen masuk dibuka mulai pukul 06:00",
    "server_time": "05:45:30",
    "boundary": {
      "start_time": "06:00",
      "type": "too_early"
    }
  }
}
```

**Response (No Schedule)**:
```json
{
  "data": {
    "allowed": false,
    "message": "Tidak ada jadwal yang di-assign untuk hari ini",
    "server_time": "08:00:00"
  }
}
```

#### Verify Location

**Endpoint**: `POST /api/v1/locations/verify`

**Request**:
```json
{
  "latitude": -5.147665,
  "longitude": 119.432732
}
```

**Response (Verified)**:
```json
{
  "verified": true,
  "message": "Lokasi terverifikasi",
  "location": {
    "id": 1,
    "name": "Kantor Pusat",
    "address": "Jl. Example No. 123",
    "latitude": -5.147500,
    "longitude": 119.432500,
    "radius_meters": 100
  },
  "distance": 23.5
}
```

**Response (Out of Range)**:
```json
{
  "verified": false,
  "message": "Anda di luar jangkauan lokasi kantor",
  "location": {
    "id": 1,
    "name": "Kantor Pusat",
    "radius_meters": 100
  },
  "distance": 234.7
}
```

**Special Cases**:
- **WFA/Remote**: If location has no GPS or radius >= 9999999, always verified
- **No Location**: If employee has no assigned location, always verified

#### Verify Face (DeepFace)

**Endpoint**: `POST /api/v1/face/deepface/verify-face`

**Request**:
```typescript
FormData {
  image: File,
  employee_id?: string // Optional: for 1:1 verification
}
```

**Response (Match)**:
```json
{
  "success": true,
  "matched": true,
  "message": "Face matched",
  "data": {
    "employee": {
      "id": "uuid",
      "code": "EMP001",
      "name": "John Doe",
      "department": "IT"
    },
    "distance": 0.45,
    "similarity": 0.55,
    "confidence": 0.92,
    "threshold_used": 0.68,
    "is_live": true,
    "model": "ArcFace"
  }
}
```

**Response (No Match)**:
```json
{
  "success": false,
  "matched": false,
  "message": "Face not recognized",
  "data": {
    "distance": 0.85,
    "similarity": 0.15,
    "confidence": 0.22,
    "threshold_used": 0.68,
    "is_live": true
  }
}
```

**Response (Liveness Failed)**:
```json
{
  "success": false,
  "matched": false,
  "message": "Liveness check failed - possible spoofing detected",
  "is_live": false,
  "confidence": 0.0
}
```

#### Check-In

**Endpoint**: `POST /api/v1/attendance-face/check-in`

**Request**:
```json
{
  "latitude": -5.147665,
  "longitude": 119.432732,
  "photo": "base64-encoded-image",
  "overwrite": false
}
```

**Response**:
```json
{
  "success": true,
  "message": "Check-in berhasil",
  "data": {
    "attendance": {
      "id": "uuid",
      "employee_id": "uuid",
      "date": "2025-12-20",
      "check_in_time": "2025-12-20T08:30:45",
      "check_out_time": null,
      "status": "present", // or "late"
      "check_in_confidence": 0.92,
      "check_in_latitude": -5.147665,
      "check_in_longitude": 119.432732,
      "total_hours": null,
      "metadata": {
        "check_in_photo": "attendance-photos/2025/12/20/xyz.jpg",
        "face_verification": {
          "similarity": 0.55,
          "model": "ArcFace"
        }
      }
    }
  }
}
```

**Error Responses**:
- `403`: Already checked in today (overwrite=false)
- `403`: No schedule for today
- `403`: Too early to check in
- `422`: Invalid location
- `422`: Face verification failed

### 2.4 Backend Logic

**Controller**: `/opt/attendancedev/backend/app/Http/Controllers/Api/AttendanceFaceController.php`

```php
public function checkIn(Request $request)
{
    $validated = $request->validate([
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'photo' => 'required|string', // base64
        'overwrite' => 'nullable|boolean',
    ]);

    $user = $request->user();
    $employee = $user->employee;

    // 1. Decode and validate photo
    $image = $this->decodeBase64Image($validated['photo']);

    // 2. Verify face via DeepFace
    $verification = $this->faceRecognitionService->verifyFaceDeepFace(
        $image,
        $employee->id
    );

    if (!$verification['matched']) {
        return $this->errorResponse('Face verification failed', 422);
    }

    // 3. Prepare location data
    $locationData = [
        'latitude' => $validated['latitude'],
        'longitude' => $validated['longitude'],
    ];

    // 4. Prepare face data
    $faceData = [
        'confidence' => $verification['confidence'],
        'similarity' => $verification['similarity'],
    ];

    // 5. Call AttendanceService
    $attendance = $this->attendanceService->checkIn(
        $employee,
        $locationData,
        $faceData,
        $image, // Store photo
        $validated['overwrite'] ?? false
    );

    return $this->apiResponse([
        'attendance' => $attendance,
    ], 'Check-in berhasil');
}
```

**Service**: `/opt/attendancedev/backend/app/Services/AttendanceService.php`

```php
public function checkIn(
    Employee $employee,
    array $locationData,
    ?array $faceData = null,
    ?UploadedFile $photo = null,
    bool $overwrite = false
): Attendance {
    return DB::transaction(function () use ($employee, $locationData, $faceData, $photo, $overwrite) {
        // 1. Check if already checked in
        $existingAttendance = $this->getTodayAttendance($employee);
        if ($existingAttendance && $existingAttendance->check_in_time && !$overwrite) {
            throw new \Exception('Already checked in today');
        }

        // 2. Validate schedule
        $effectiveSchedule = $employee->getEffectiveScheduleForDate(now());
        $canAttend = $effectiveSchedule['can_attend'] ?? false;

        if (!$canAttend) {
            $message = $effectiveSchedule['message'] ?? 'Tidak memiliki jadwal untuk absen hari ini';
            throw new \Exception($message);
        }

        // 3. Validate check-in time boundaries
        $this->validateCheckInTime($employee);

        // 4. Validate location
        if (config('attendance.require_location_verification')) {
            if (!$this->validateLocation($locationData, $employee)) {
                throw new \Exception('Invalid location for check-in');
            }
        }

        // 5. Store photo if provided
        $photoPath = null;
        if ($photo) {
            $photoPath = $photo->store('attendance-photos/' . date('Y/m/d'), 'public');
        }

        // 6. Get accurate WITA time
        $attendanceTime = $this->timeService->getAttendanceTime();
        $currentTime = $attendanceTime['timestamp'];

        // 7. Prepare metadata
        $metadata = [];
        if ($photoPath) {
            $metadata['check_in_photo'] = $photoPath;
        }

        // 8. Create or update attendance record
        $date = $this->timeService->today()->format('Y-m-d');

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $date,
            ],
            [
                'check_in_time' => $currentTime->format('Y-m-d H:i:s'),
                'check_in_latitude' => $locationData['latitude'] ?? null,
                'check_in_longitude' => $locationData['longitude'] ?? null,
                'check_in_confidence' => $faceData['confidence'] ?? null,
                'status' => $this->determineStatus($currentTime, 'check_in', $employee),
                'time_verification' => $attendanceTime['verification'],
                'metadata' => $metadata,
            ]
        );

        // 9. Send notification
        $this->notificationService->send(
            $employee->user,
            'attendance.checked_in',
            [
                'time' => $attendanceTime['formatted']['time'],
                'date' => $attendanceTime['formatted']['date'],
            ]
        );

        // 10. Send email notification (queued)
        $this->emailService->sendCheckInEmail($employee, $attendance);

        // 11. Log check-in to audit log
        AuditLog::createAuthLog('check_in', $employee->user, [
            'employee_id' => $employee->id,
            'time' => $attendanceTime['formatted']['time'],
            'status' => $attendance->status,
        ]);

        return $attendance;
    });
}
```

**Status Determination**:

```php
private function determineStatus(Carbon $time, string $type, Employee $employee): string
{
    if ($type === 'check_in') {
        // For GURU HONORER: Use TeachingSchedule
        if ($employee->isGuruHonorer()) {
            $boundaries = $employee->getGuruHonorerCheckInBoundaries($time);

            if ($boundaries['has_schedule'] && $boundaries['late_after']) {
                // Late if check-in after teaching_start_time (no tolerance)
                if ($time->gt($boundaries['late_after'])) {
                    return 'late';
                }
            }
            return 'present';
        }

        // For REGULAR EMPLOYEE: Use MonthlySchedule
        $employeeSchedule = $employee->getScheduleForDate($time);

        if ($employeeSchedule && $employeeSchedule->monthlySchedule) {
            $checkinEndTime = $employeeSchedule->monthlySchedule->getRawOriginal('checkin_end_time');
            if ($checkinEndTime) {
                $lateThreshold = Carbon::parse($time->format('Y-m-d') . ' ' . $checkinEndTime);
                if ($time->gt($lateThreshold)) {
                    return 'late';
                }
            }
        }
    }

    return 'present';
}
```

**Possible Status Values**:
- `present`: On time
- `late`: After `checkin_end_time` (batas terlambat)
- `absent`: No check-in
- `early_departure`: Check-out before `default_end_time`
- `incomplete`: Checked in but not checked out

### 2.5 Validation Rules

**Schedule Validation**:
```php
// IMPORTANT: Default to FALSE (cannot attend) if can_attend not set
$effectiveSchedule = $employee->getEffectiveScheduleForDate(now());
$canAttend = $effectiveSchedule['can_attend'] ?? false;

if (!$canAttend) {
    $scheduleType = $effectiveSchedule['schedule_type'] ?? 'none';

    // Error messages by schedule type:
    // - 'none': "Tidak ada jadwal yang di-assign untuk hari ini"
    // - 'holiday': "Hari ini adalah hari libur: {holiday_name}"
    // - 'no_teaching': "Tidak ada jadwal mengajar hari ini" (for guru honorer)
}
```

**Time Boundaries**:

For Regular Employees (using MonthlySchedule):
```
checkin_start_time  --> Mulai absen masuk (e.g., 06:00)
checkin_end_time    --> Batas terlambat (e.g., 08:00)
default_start_time  --> Jam masuk standar (e.g., 07:30)
default_end_time    --> Jam pulang standar (e.g., 16:00)
checkout_start_time --> Mulai absen pulang (e.g., 15:30)
checkout_end_time   --> Batas akhir absen pulang (e.g., 18:00)
```

Validation Logic:
```php
// Check-in validation
$currentTime = now()->format('H:i:s');
$checkinStartTime = $monthlySchedule->getRawOriginal('checkin_start_time');

if ($checkinStartTime && $currentTime < $checkinStartTime) {
    throw new \Exception("Belum waktunya absen masuk. Absen masuk dibuka mulai pukul {$checkinStartTime}");
}
```

For Guru Honorer (using TeachingSchedule):
- **No blocking** - can check-in anytime
- Late status determined by first teaching session start time
- No tolerance for lateness

---

## 3. Attendance Check-Out Flow

### 3.1 Flow Diagram

```
┌─────────────┐
│   Start     │
│  Check-Out  │
└──────┬──────┘
       │
       ▼
┌──────────────────────┐
│ Check Existing       │
│ Check-In             │
│ - Must exist         │
│ - Not checked out    │
└──────┬───────────────┘
       │ ✓ Has Check-In
       ▼
┌──────────────────────┐
│ Validate Time        │ (POST /attendance/validate-time)
│ - checkout_start_time│
│ - checkout_end_time  │
└──────┬───────────────┘
       │ ✓ Time OK
       ▼
┌──────────────────────┐
│ Get GPS Location     │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Verify Location      │
└──────┬───────────────┘
       │ ✓ Within Radius
       ▼
┌──────────────────────┐
│ Face Verification    │ (Same as Check-In)
│ - Camera             │
│ - Detection          │
│ - Liveness           │
│ - Verify             │
└──────┬───────────────┘
       │ ✓ Face Verified
       ▼
┌──────────────────────┐
│ Submit Check-Out     │ (POST /attendance-face/check-out)
│ - Update attendance  │
│ - Calculate hours    │
│ - Calculate overtime │
│ - Determine status   │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Success Screen       │
│ - Show total hours   │
│ - Show status        │
│ - Show summary       │
└──────────────────────┘
```

### 3.2 API Endpoints

#### Check-Out

**Endpoint**: `POST /api/v1/attendance-face/check-out`

**Request**:
```json
{
  "latitude": -5.147665,
  "longitude": 119.432732,
  "photo": "base64-encoded-image",
  "overwrite": false
}
```

**Response**:
```json
{
  "success": true,
  "message": "Check-out berhasil",
  "data": {
    "attendance": {
      "id": "uuid",
      "employee_id": "uuid",
      "date": "2025-12-20",
      "check_in_time": "2025-12-20T08:30:45",
      "check_out_time": "2025-12-20T17:15:30",
      "status": "present", // or "early_departure"
      "check_in_confidence": 0.92,
      "check_out_confidence": 0.89,
      "total_hours": 7.75,
      "metadata": {
        "check_in_photo": "...",
        "check_out_photo": "...",
        "overtime_hours": 0.25
      }
    }
  }
}
```

**Error Responses**:
- `403`: No check-in found for today
- `403`: Already checked out
- `403`: Too early to check out
- `403`: Too late to check out (exceeded checkout_end_time)

### 3.3 Backend Logic

**Service**: `/opt/attendancedev/backend/app/Services/AttendanceService.php`

```php
public function checkOut(
    Employee $employee,
    array $locationData,
    ?array $faceData = null,
    ?UploadedFile $photo = null,
    bool $overwrite = false
): Attendance {
    return DB::transaction(function () use ($employee, $locationData, $faceData, $photo, $overwrite) {
        // 1. Get today's attendance
        $attendance = $this->getTodayAttendance($employee);

        if (!$attendance || !$attendance->check_in_time) {
            throw new \Exception('No check-in found for today');
        }

        if ($attendance->check_out_time && !$overwrite) {
            throw new \Exception('Already checked out today');
        }

        // 2. Validate check-out time boundaries
        $this->validateCheckOutTime($employee);

        // 3. Validate location
        if (config('attendance.require_location_verification')) {
            if (!$this->validateLocation($locationData, $employee)) {
                throw new \Exception('Invalid location for check-out');
            }
        }

        // 4. Store photo
        $photoPath = null;
        if ($photo) {
            $photoPath = $photo->store('attendance-photos/' . date('Y/m/d'), 'public');
        }

        // 5. Get accurate WITA time
        $attendanceTime = $this->timeService->getAttendanceTime();
        $currentTime = $attendanceTime['timestamp'];

        // 6. Set check-out time first for calculations
        $attendance->check_out_time = $currentTime;

        // 7. Calculate working hours
        $totalHours = $this->calculateWorkingHours($attendance);
        $attendance->total_hours = $totalHours;

        // 8. Calculate overtime
        $overtimeHours = $this->calculateOvertimeHours($attendance, $employee);

        // 9. Prepare metadata
        $metadata = $attendance->metadata ?? [];
        if ($photoPath) {
            $metadata['check_out_photo'] = $photoPath;
        }
        $metadata['overtime_hours'] = $overtimeHours;

        // 10. Determine if early leave
        $checkOutStatus = $this->determineCheckOutStatus($currentTime, $employee);

        // 11. Update attendance record
        $updateData = [
            'check_out_time' => $currentTime,
            'check_out_latitude' => $locationData['latitude'] ?? null,
            'check_out_longitude' => $locationData['longitude'] ?? null,
            'check_out_confidence' => $faceData['confidence'] ?? null,
            'total_hours' => $totalHours,
            'time_verification' => $attendanceTime['verification'],
            'metadata' => $metadata,
        ];

        // Update status if early departure
        if ($checkOutStatus === 'early_departure') {
            $updateData['status'] = 'early_departure';
        }

        $attendance->update($updateData);

        // 12. Send notification
        $this->notificationService->send($employee->user, 'attendance.checked_out', [
            'time' => $currentTime->format('H:i'),
            'hours' => $attendance->working_hours,
        ]);

        // 13. Send email notification
        $this->emailService->sendCheckOutEmail($employee, $attendance);

        // 14. Log check-out
        AuditLog::createAuthLog('check_out', $employee->user, [
            'employee_id' => $employee->id,
            'total_hours' => $totalHours,
            'status' => $attendance->status,
        ]);

        return $attendance->fresh();
    });
}
```

### 3.4 Working Hours Calculation

**For Regular Employees**:
```php
public function calculateWorkingHours(Attendance $attendance): float
{
    if (!$attendance->check_in_time || !$attendance->check_out_time) {
        return 0;
    }

    $checkIn = Carbon::parse($attendance->check_in_time);
    $checkOut = Carbon::parse($attendance->check_out_time);

    // Calculate difference in minutes
    $workingMinutes = abs($checkIn->diffInMinutes($checkOut));

    // Subtract break time if worked more than minimum hours
    $breakMinutes = config('attendance.break_duration_minutes', 60);
    $minimumHoursForBreak = config('attendance.minimum_hours_for_break', 240); // 4 hours

    if ($workingMinutes > $minimumHoursForBreak) {
        $workingMinutes -= $breakMinutes;
    }

    return max(0, round($workingMinutes / 60, 2));
}
```

**For Guru Honorer**:
```php
public function calculateWorkingHoursForGuruHonorer(Employee $employee, $date): float
{
    // For guru honorer, working hours = sum of teaching hours (not range)
    return $employee->getTotalTeachingHoursForDate($date);
}
```

Example:
```
Regular Employee:
  Check-in:  08:30
  Check-out: 17:15
  Range: 8h 45m = 8.75h
  Break: -1h
  Total: 7.75h

Guru Honorer:
  Teaching Sessions:
    - Session 1: 07:30-09:00 (1.5h)
    - Session 2: 10:00-11:30 (1.5h)
    - Session 3: 13:00-14:30 (1.5h)
  Total: 4.5h (sum, not range)
```

### 3.5 Early Departure Detection

```php
private function determineCheckOutStatus(Carbon $time, Employee $employee): ?string
{
    // For GURU HONORER
    if ($employee->isGuruHonorer()) {
        $boundaries = $employee->getGuruHonorerCheckOutBoundaries($time);

        if ($boundaries['has_schedule'] && $boundaries['early_leave_before']) {
            // Early if check-out before last teaching session ends
            if ($time->lt($boundaries['early_leave_before'])) {
                return 'early_departure';
            }
        }
        return null;
    }

    // For REGULAR EMPLOYEE
    $employeeSchedule = $employee->getScheduleForDate($time);

    if ($employeeSchedule && $employeeSchedule->monthlySchedule) {
        $defaultEndTime = $employeeSchedule->monthlySchedule->getRawOriginal('default_end_time');
        if ($defaultEndTime) {
            $endTimeThreshold = Carbon::parse($time->format('Y-m-d') . ' ' . $defaultEndTime);
            if ($time->lt($endTimeThreshold)) {
                return 'early_departure';
            }
        }
    }

    return null; // No status change
}
```

---

## 4. Attendance Dashboard (Employee)

### 4.1 Features

**Today's Status**:
- Check-in time and status (present/late)
- Check-out time (if available)
- Working hours
- Location verified badge
- Face confidence score

**Monthly Summary**:
- Total present days
- Total late days
- Total absent days
- Average working hours
- Attendance rate percentage

**Attendance History**:
- Calendar view
- List view with filters
- Status badges (color-coded)
- Details modal

**Statistics Charts**:
- Weekly trend (line chart)
- Status distribution (pie chart)
- Monthly comparison (bar chart)

### 4.2 API Endpoints

#### Get Today's Attendance

**Endpoint**: `GET /api/v1/attendance/today`

**Response**:
```json
{
  "data": {
    "attendance": {
      "id": "uuid",
      "date": "2025-12-20",
      "check_in_time": "2025-12-20T08:30:45",
      "check_out_time": null,
      "status": "present",
      "check_in_confidence": 0.92,
      "total_hours": null
    },
    "has_checked_in": true,
    "has_checked_out": false
  }
}
```

#### Get Attendance History

**Endpoint**: `GET /api/v1/attendance`

**Query Parameters**:
- `date_from`: Start date (YYYY-MM-DD)
- `date_to`: End date (YYYY-MM-DD)
- `status`: Filter by status (present/late/absent)
- `month`: Filter by month (YYYY-MM)
- `per_page`: Pagination (default: 15)

**Response**:
```json
{
  "data": [
    {
      "id": "uuid",
      "employee_id": "uuid",
      "date": "2025-12-20",
      "check_in_time": "08:30:45",
      "check_out_time": "17:15:30",
      "status": "present",
      "total_hours": 7.75,
      "employee": {
        "id": "uuid",
        "employee_id": "EMP001",
        "full_name": "John Doe"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 100,
    "per_page": 15
  }
}
```

#### Get Attendance Statistics

**Endpoint**: `GET /api/v1/attendance/statistics?date=2025-12-20`

**Response**:
```json
{
  "statistics": {
    "total_employees": 150,
    "present": 120,
    "late": 20,
    "absent": 10,
    "on_leave": 5,
    "attendance_rate": 93.3
  }
}
```

### 4.3 Frontend Implementation

**Page**: `/opt/attendancedev/frontend/src/pages/employee/attendance/index.tsx`

**Key Features**:
- Real-time today status
- Calendar heatmap for monthly view
- Filterable history table
- Export to Excel (for admin only)

---

## 5. Attendance Management (Admin)

### 5.1 Features

**Dashboard**:
- Real-time statistics
- Today's attendance count
- Status distribution chart
- Late arrivals list
- Absent employees list

**View All Attendance**:
- Filter by date range
- Filter by employee
- Filter by status
- Search by name/ID
- Export to Excel

**Manual Entry**:
- Create attendance record manually
- Edit check-in/check-out times
- Add notes/reason
- Approval workflow

**Bulk Operations**:
- Approve multiple attendances
- Reject with reason
- Delete records
- Export filtered data

### 5.2 API Endpoints

#### Admin Statistics

**Endpoint**: `GET /api/v1/attendance/admin/stats?date=2025-12-20`

**Response**:
```json
{
  "data": {
    "presentToday": 120,
    "lateToday": 20,
    "absentToday": 10,
    "onLeaveToday": 5,
    "attendanceRate": 93.3
  }
}
```

#### Admin Records

**Endpoint**: `GET /api/v1/attendance/admin/records`

**Query Parameters**:
- `date`: Date (YYYY-MM-DD)
- `search`: Search by name/employee_id
- `status`: Filter by status

**Response**:
```json
{
  "data": [
    {
      "id": "uuid",
      "employee": {
        "id": "uuid",
        "name": "John Doe",
        "employeeId": "EMP001",
        "type": "pegawai"
      },
      "date": "2025-12-20",
      "checkIn": "08:30",
      "checkOut": "17:15",
      "status": "present",
      "notes": null
    }
  ]
}
```

#### Manual Entry

**Endpoint**: `POST /api/v1/attendance/manual`

**Request**:
```json
{
  "employee_id": "uuid",
  "date": "2025-12-20",
  "check_in": "08:30",
  "check_out": "17:00",
  "notes": "Manual entry by admin - system was down"
}
```

**Response**:
```json
{
  "data": {
    "id": "uuid",
    "employee_id": "uuid",
    "date": "2025-12-20",
    "check_in_time": "08:30:00",
    "check_out_time": "17:00:00",
    "status": "present",
    "notes": "Manual entry by admin - system was down"
  }
}
```

#### Approve Attendance

**Endpoint**: `POST /api/v1/attendance/{id}/approve`

**Response**:
```json
{
  "data": {
    "id": "uuid",
    "status": "present",
    "notes": "Approved by admin"
  }
}
```

#### Reject Attendance

**Endpoint**: `POST /api/v1/attendance/{id}/reject`

**Request**:
```json
{
  "reason": "Invalid location"
}
```

**Response**:
```json
{
  "data": {
    "id": "uuid",
    "status": "absent",
    "notes": "Rejected: Invalid location"
  }
}
```

### 5.3 Frontend Implementation

**Page**: `/opt/attendancedev/frontend/src/pages/admin/attendance/index.tsx`

**Features**:
- Real-time dashboard
- Advanced filtering
- Bulk selection
- Modal for details/edit
- Excel export

---

## 6. DeepFace Service Integration

### 6.1 Architecture

**Service**: Python FastAPI + DeepFace

**File**: `/opt/attendancedev/python-services/face-recognition/main.py`

**Configuration**:
```python
CONFIG = {
    "model_name": "ArcFace",           # Best accuracy
    "detector_backend": "retinaface",  # Best face detector
    "distance_metric": "cosine",       # Similarity metric
    "threshold": 0.68,                 # ArcFace recommended
    "embedding_dimension": 512,        # 512-d vectors
}
```

**Model Options**:
```python
MODEL_CONFIGS = {
    "ArcFace": {"dim": 512, "threshold": 0.68},      # Recommended (production)
    "Facenet512": {"dim": 512, "threshold": 0.30},   # Alternative
    "VGG-Face": {"dim": 2622, "threshold": 0.40},    # Lightweight
    "OpenFace": {"dim": 128, "threshold": 0.10},     # Fast
}
```

### 6.2 Load Balancing

**Service**: `/opt/attendancedev/backend/app/Services/DeepFaceLoadBalancer.php`

**Features**:
- Round-robin distribution
- Health checking
- Automatic failover
- Circuit breaker pattern

**Configuration** (`.env`):
```env
DEEPFACE_URLS="http://deepface-1:8001,http://deepface-2:8001,http://deepface-3:8001"
DEEPFACE_TIMEOUT=30
DEEPFACE_HEALTH_CHECK_INTERVAL=60
```

**Usage**:
```php
$result = $this->deepFaceLoadBalancer->verifyFace($image, $knownFaces);

// Automatically:
// 1. Selects healthy instance (round-robin)
// 2. Sends request
// 3. If fails, tries next instance
// 4. Marks instance as unhealthy if 3+ consecutive failures
// 5. Returns result or throws exception
```

### 6.3 API Endpoints

#### Health Check

**Endpoint**: `GET /api/v1/face/deepface/health`

**Response**:
```json
{
  "success": true,
  "service": "DeepFace Face Recognition Cluster",
  "status": "operational",
  "healthy_instances": 3,
  "total_instances": 3,
  "cluster_health_percentage": 100
}
```

#### Cluster Status (Detailed)

**Endpoint**: `GET /api/v1/face/deepface/cluster-status`

**Response**:
```json
{
  "success": true,
  "cluster": {
    "healthy_instances": 3,
    "total_instances": 3,
    "instances": [
      {
        "url": "http://deepface-1:8001",
        "healthy": true,
        "last_check": "2025-12-20T10:30:45",
        "consecutive_failures": 0,
        "response_time_ms": 234
      },
      {
        "url": "http://deepface-2:8001",
        "healthy": true,
        "last_check": "2025-12-20T10:30:46",
        "consecutive_failures": 0,
        "response_time_ms": 198
      },
      {
        "url": "http://deepface-3:8001",
        "healthy": true,
        "last_check": "2025-12-20T10:30:47",
        "consecutive_failures": 0,
        "response_time_ms": 267
      }
    ]
  },
  "recommendation": "All instances healthy. No action required."
}
```

#### Extract Embedding

**Endpoint**: `POST /api/v1/face/deepface/extract-embedding`

**Request**:
```typescript
FormData {
  image: File
}
```

**Response**: (See section 1.3)

#### Verify Face

**Endpoint**: `POST /api/v1/face/deepface/verify-face`

**Request**:
```typescript
FormData {
  image: File,
  employee_id?: string
}
```

**Response**: (See section 2.3)

#### Check Liveness

**Endpoint**: `POST /api/v1/face/deepface/check-liveness`

**Request**:
```typescript
FormData {
  image: File
}
```

**Response**:
```json
{
  "success": true,
  "message": "Liveness check completed",
  "data": {
    "is_live": true,
    "result": "Real",
    "confidence": 0.95
  }
}
```

**Note**: DeepFace liveness detection uses built-in anti-spoofing:
- Texture analysis
- Face depth estimation
- Pattern detection
- Returns "Real" or "Fake"

#### Analyze Emotion (for additional liveness)

**Endpoint**: `POST /api/v1/face/deepface/analyze-emotion`

**Request**:
```typescript
FormData {
  image: File,
  expected_emotion?: string // e.g., "happy" for smile check
}
```

**Response**:
```json
{
  "success": true,
  "emotions": {
    "happy": 89.2,
    "neutral": 5.1,
    "sad": 2.3,
    "angry": 1.5,
    "surprise": 1.2,
    "fear": 0.7
  },
  "dominant_emotion": "happy",
  "expected_emotion": "happy",
  "is_match": true,
  "confidence": 0.892
}
```

**Usage**: Can be used as additional liveness check by asking user to smile.

### 6.4 Python Service Details

**Startup**:
```bash
cd /opt/attendancedev/python-services/face-recognition
python main.py --port 8001 --host 0.0.0.0
```

**Docker**:
```yaml
services:
  deepface-1:
    build: ./python-services/face-recognition
    environment:
      - DEEPFACE_MODEL=ArcFace
      - DEEPFACE_DETECTOR=retinaface
      - DEEPFACE_THRESHOLD=0.68
    ports:
      - "8001:8001"
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 4G
```

**Image Quality Checks**:
```python
def check_image_quality(img: np.ndarray) -> dict:
    # 1. Blur detection (Laplacian variance)
    blur_score = cv2.Laplacian(gray, cv2.CV_64F).var()
    is_blurry = blur_score < 100

    # 2. Brightness check
    brightness = np.mean(gray)
    is_too_dark = brightness < 50
    is_too_bright = brightness > 200

    return {
        "blur_score": float(blur_score),
        "is_blurry": bool(is_blurry),
        "brightness": float(brightness),
        "is_too_dark": bool(is_too_dark),
        "is_too_bright": bool(is_too_bright),
        "quality_ok": bool(not (is_blurry or is_too_dark or is_too_bright))
    }
```

**Verification Logic**:
```python
async def verify_face(image, known_faces_json):
    # 1. Liveness check
    analysis = DeepFace.analyze(img_path=img_array, actions=['live'])
    is_live = analysis[0].get('live', 'Real') == 'Real'

    if not is_live:
        return {
            "success": False,
            "matched": False,
            "message": "Liveness check failed - possible spoofing detected",
            "is_live": False
        }

    # 2. Extract embedding
    result = DeepFace.represent(
        img_path=img_array,
        model_name="ArcFace",
        detector_backend="retinaface",
        enforce_detection=True,
        align=True
    )
    current_embedding = np.array(result[0]["embedding"])

    # 3. Find best match
    best_match = None
    min_distance = float('inf')

    for known_face in known_faces:
        known_embedding = np.array(known_face.embedding)

        # Calculate cosine distance
        distance = np.dot(current_embedding, known_embedding) / (
            np.linalg.norm(current_embedding) * np.linalg.norm(known_embedding)
        )
        distance = 1 - distance  # Convert similarity to distance

        if distance < min_distance:
            min_distance = distance
            best_match = known_face

    # 4. Check threshold
    threshold = 0.68  # ArcFace recommended
    matched = min_distance < threshold

    return {
        "success": True,
        "matched": bool(matched),
        "employee": {
            "id": best_match.employee_id,
            "code": best_match.employee_code,
            "name": best_match.name
        } if matched else None,
        "distance": float(min_distance),
        "similarity": float(1 - min_distance),
        "confidence": float(max(0, 1 - (min_distance / threshold))),
        "is_live": is_live
    }
```

### 6.5 Performance Optimization

**Caching**:
- Registered faces cached in Redis (1 hour TTL)
- Cache invalidated on face registration/update
- Reduces database queries

**Batch Processing**:
- Load all registered faces once per request
- Compare against all in memory
- O(n) complexity where n = number of employees

**Resource Limits**:
- Image size: Max 10MB
- Timeout: 30 seconds
- Max concurrent requests: 10 per instance

---

## 7. Database Schema

### 7.1 Attendances Table

**Migration**: `/opt/attendancedev/backend/database/migrations/2025_07_03_131518_create_attendances_table.php`

```sql
CREATE TABLE attendances (
    id UUID PRIMARY KEY,
    employee_id UUID NOT NULL,
    date DATE NOT NULL,
    check_in_time TIMESTAMP NULL,
    check_out_time TIMESTAMP NULL,
    total_hours DECIMAL(5,2) NULL,
    status VARCHAR(20) DEFAULT 'incomplete',

    -- Face verification
    check_in_confidence DECIMAL(5,4) NULL,
    check_out_confidence DECIMAL(5,4) NULL,

    -- Location verification
    check_in_latitude DECIMAL(10,8) NULL,
    check_in_longitude DECIMAL(11,8) NULL,
    check_out_latitude DECIMAL(10,8) NULL,
    check_out_longitude DECIMAL(11,8) NULL,
    location_verified BOOLEAN DEFAULT FALSE,

    -- Additional data
    check_in_notes TEXT NULL,
    check_out_notes TEXT NULL,
    metadata JSONB DEFAULT '{}',

    -- Audit
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,

    -- Indexes
    UNIQUE (employee_id, date),
    INDEX (employee_id, date),
    INDEX (date),
    INDEX (status),
    INDEX (check_in_time, check_out_time),

    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);
```

**Status Values**:
- `present`: On time check-in and check-out
- `late`: Check-in after `checkin_end_time`
- `absent`: No check-in record
- `early_departure`: Check-out before `default_end_time`
- `incomplete`: Checked in but not checked out
- `leave`: On approved leave

**Metadata JSONB Structure**:
```json
{
  "check_in_photo": "attendance-photos/2025/12/20/uuid.jpg",
  "check_out_photo": "attendance-photos/2025/12/20/uuid2.jpg",
  "overtime_hours": 0.25,
  "face_verification": {
    "check_in": {
      "similarity": 0.55,
      "distance": 0.45,
      "model": "ArcFace"
    },
    "check_out": {
      "similarity": 0.53,
      "distance": 0.47,
      "model": "ArcFace"
    }
  },
  "time_verification": {
    "source": "ntp",
    "server": "pool.ntp.org",
    "offset_ms": 12
  }
}
```

### 7.2 Employee Face Data (in metadata JSONB)

**Table**: `employees`

**Column**: `metadata JSONB`

**Structure**:
```json
{
  "face_recognition": {
    "descriptor": [0.234, -0.123, ...], // 512-d array
    "confidence": 0.95,
    "algorithm": "deepface-arcface",
    "model_version": "1.0",
    "registered_at": "2025-12-20T10:30:45Z",
    "updated_at": "2025-12-20T10:30:45Z",
    "image_path": "face-images/uuid/uuid_20251220103045.jpg",
    "device_info": {
      "user_agent": "...",
      "platform": "web"
    },
    "quality_score": 0.87,
    "features": {
      "descriptor_length": 512,
      "landmarks_count": 0,
      "has_expressions": false
    },
    "update_count": 0
  },
  "face_recognition_stats": {
    "total_verifications": 150,
    "successful_verifications": 148,
    "average_similarity": 0.56,
    "last_verified_at": "2025-12-20T15:30:00Z"
  }
}
```

**Querying Face Data** (PostgreSQL JSONB):
```sql
-- Get employees with face data
SELECT * FROM employees
WHERE (metadata -> 'face_recognition' -> 'descriptor') IS NOT NULL;

-- Get face descriptor
SELECT
    id,
    full_name,
    metadata -> 'face_recognition' -> 'descriptor' AS descriptor
FROM employees
WHERE (metadata -> 'face_recognition' -> 'descriptor') IS NOT NULL;

-- Get employees without face data
SELECT * FROM employees
WHERE (metadata -> 'face_recognition' -> 'descriptor') IS NULL
  AND is_active = TRUE;
```

### 7.3 Face Recognition Logs

**Migration**: `/opt/attendancedev/backend/database/migrations/2025_07_17_115724_create_face_recognition_logs_table.php`

```sql
CREATE TABLE face_recognition_logs (
    id UUID PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    employee_id UUID NULL,
    data JSONB DEFAULT '{}',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,

    INDEX (action),
    INDEX (employee_id),
    INDEX (created_at),

    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
);
```

**Action Types**:
- `register`: Face registration
- `update`: Face data update
- `delete`: Face data deletion
- `verify_success`: Successful verification
- `verify_failed`: Failed verification
- `verify_deepface_success`: DeepFace verification success
- `verify_deepface_failed`: DeepFace verification failed
- `liveness_check_deepface`: Liveness check performed

**Log Data Examples**:
```json
{
  "action": "verify_deepface_success",
  "employee_id": "uuid",
  "data": {
    "similarity": 0.55,
    "distance": 0.45,
    "is_live": true,
    "model": "ArcFace"
  },
  "ip_address": "192.168.1.100",
  "user_agent": "Mozilla/5.0..."
}
```

### 7.4 Indexes for Performance

```sql
-- Attendance queries
CREATE INDEX idx_attendances_employee_date ON attendances(employee_id, date);
CREATE INDEX idx_attendances_date ON attendances(date);
CREATE INDEX idx_attendances_status ON attendances(status);

-- Face recognition queries
CREATE INDEX idx_employees_face_metadata ON employees USING GIN (metadata);

-- Logs queries
CREATE INDEX idx_face_logs_action ON face_recognition_logs(action);
CREATE INDEX idx_face_logs_employee ON face_recognition_logs(employee_id);
CREATE INDEX idx_face_logs_created ON face_recognition_logs(created_at);
```

---

## 8. Security & Anti-Spoofing

### 8.1 Liveness Detection Layers

**Layer 1: DeepFace Built-in Liveness**
```python
analysis = DeepFace.analyze(img_path=img_array, actions=['live'])
is_live = analysis[0].get('live', 'Real') == 'Real'
```

**Layer 2: Smile Detection (Optional)**
```typescript
// Frontend: Require smile for liveness
<AutoCaptureFace
    requireSmile={true}
    onCapture={handleCapture}
/>
```

**Layer 3: Image Quality Checks**
```python
quality = check_image_quality(img_array)

if quality['is_blurry'] or quality['is_too_dark']:
    return {"success": False, "message": "Image quality too low"}
```

**Layer 4: Face Confidence Threshold**
```typescript
// Only accept faces with confidence > 0.5
if (confidence < 0.5) {
    return { message: "Posisikan wajah lebih jelas" };
}
```

**Layer 5: Multiple Verification Attempts**
```php
// Log all verification attempts
$this->logFaceActivity('verify_deepface_success', $employeeId, $data);

// Detect suspicious patterns
if ($recentFailures > 5) {
    throw new \Exception('Too many failed attempts');
}
```

### 8.2 Security Best Practices

**1. Face Data Storage**:
- Face embeddings stored in JSONB (encrypted at rest)
- Face images stored in private storage (not publicly accessible)
- Separate from avatar images

**2. API Security**:
- All endpoints require authentication (`auth:sanctum`)
- Rate limiting on face registration/verification
- CSRF protection on all POST requests
- Role-based access control (RBAC)

**3. Location Validation**:
- Haversine formula for accurate distance calculation
- Configurable radius per location
- WFA/Remote employees bypass validation

**4. Time Validation**:
- NTP time synchronization
- Server-side time verification
- Time boundaries enforced

**5. Audit Logging**:
- All face operations logged
- Attendance actions logged
- IP and user agent tracked
- Sentry integration for errors

### 8.3 Privacy Compliance

**GDPR/Data Protection**:
- Face data stored with explicit consent
- Employees can view their face data
- Employees can request face data deletion
- Backup before face data updates/deletions
- Audit trail for compliance

**Data Retention**:
```php
// Face data backups
Storage::disk('private')->put(
    sprintf('face-backups/%s/%s.json', $employee->id, now()->format('YmdHis')),
    json_encode($backupData)
);
```

### 8.4 Error Handling

**Frontend**:
```typescript
try {
    const result = await checkIn(data);
    navigate('/attendance/success');
} catch (error: any) {
    if (error.response?.status === 422) {
        // Validation error
        setError(error.response.data.message);
    } else if (error.response?.status === 403) {
        // Forbidden (no schedule, wrong time, etc.)
        setError(error.response.data.message);
    } else if (error.response?.status === 503) {
        // Service unavailable
        setError('Sistem sedang sibuk. Silakan coba lagi.');
    } else {
        // Generic error
        setError('Terjadi kesalahan. Silakan coba lagi.');
    }
}
```

**Backend**:
```php
try {
    $attendance = $this->attendanceService->checkIn(...);
    return $this->apiResponse(['attendance' => $attendance], 'Check-in berhasil');
} catch (\Illuminate\Http\Client\ConnectionException $e) {
    Log::error('DeepFace service unavailable', ['error' => $e->getMessage()]);
    return $this->errorResponse('Layanan pengenalan wajah tidak tersedia', 503);
} catch (\Exception $e) {
    Log::error('Check-in failed', ['error' => $e->getMessage()]);
    return $this->errorResponse($e->getMessage(), 422);
}
```

**Python Service**:
```python
try:
    result = DeepFace.represent(img_path=img_array, ...)
    return {"success": True, "embedding": result[0]["embedding"]}
except ValueError as e:
    # Face not detected
    logger.warning(f"Face detection failed: {e}")
    return {"success": False, "message": "No face detected in image"}
except Exception as e:
    logger.error(f"Embedding extraction error: {e}")
    raise HTTPException(status_code=500, detail=f"Extraction failed: {str(e)}")
```

---

## Appendix A: Configuration

### Environment Variables

**Backend** (`.env`):
```env
# Attendance settings
ATTENDANCE_REQUIRE_FACE_VERIFICATION=true
ATTENDANCE_REQUIRE_LOCATION_VERIFICATION=true
ATTENDANCE_DEFAULT_LOCATION_RADIUS=100
ATTENDANCE_BREAK_DURATION_MINUTES=60
ATTENDANCE_MINIMUM_HOURS_FOR_BREAK=240
ATTENDANCE_LATE_GRACE_MINUTES=15

# DeepFace service
DEEPFACE_URLS="http://deepface-1:8001,http://deepface-2:8001,http://deepface-3:8001"
DEEPFACE_TIMEOUT=30
DEEPFACE_HEALTH_CHECK_INTERVAL=60

# Time service
TIME_SERVICE_ENABLED=true
TIME_SERVICE_NTP_SERVERS="pool.ntp.org,time.google.com"
TIME_SERVICE_TIMEZONE=Asia/Makassar
```

**Python Service** (environment):
```env
DEEPFACE_MODEL=ArcFace
DEEPFACE_DETECTOR=retinaface
DEEPFACE_THRESHOLD=0.68
```

### Configuration Files

**Laravel** (`config/attendance.php`):
```php
return [
    'require_face_verification' => env('ATTENDANCE_REQUIRE_FACE_VERIFICATION', true),
    'require_location_verification' => env('ATTENDANCE_REQUIRE_LOCATION_VERIFICATION', true),
    'default_location_radius' => env('ATTENDANCE_DEFAULT_LOCATION_RADIUS', 100),
    'break_duration_minutes' => env('ATTENDANCE_BREAK_DURATION_MINUTES', 60),
    'minimum_hours_for_break' => env('ATTENDANCE_MINIMUM_HOURS_FOR_BREAK', 240),
    'late_grace_minutes' => env('ATTENDANCE_LATE_GRACE_MINUTES', 15),
];
```

---

## Appendix B: Common Issues & Troubleshooting

### Issue 1: Face Not Detected

**Symptoms**: "No face detected in image" error

**Solutions**:
1. Ensure good lighting
2. Face camera directly
3. Remove glasses/mask if possible
4. Move closer to camera
5. Check camera permissions

**Code Fix**:
```typescript
// Lower confidence threshold for testing
<AutoCaptureFace confidenceThreshold={0.3} />
```

### Issue 2: Face Not Recognized

**Symptoms**: "Face not recognized" after capture

**Solutions**:
1. Re-register face with better quality photo
2. Ensure employee has registered face
3. Check DeepFace service status
4. Review similarity threshold

**Code Fix**:
```python
# Lower threshold for testing (not recommended for production)
threshold = 0.75  # Default: 0.68
```

### Issue 3: DeepFace Service Unavailable

**Symptoms**: 503 error, "Face recognition service unavailable"

**Solutions**:
1. Check DeepFace containers: `docker ps | grep deepface`
2. Check logs: `docker logs deepface-1`
3. Restart service: `docker restart deepface-1`
4. Check cluster status: `GET /api/v1/face/deepface/cluster-status`

**Health Check**:
```bash
curl http://deepface-1:8001/health
```

### Issue 4: GPS Location Not Found

**Symptoms**: "Gagal mendapatkan lokasi GPS"

**Solutions**:
1. Check browser permissions
2. Enable location services on device
3. Try HTTPS (required for geolocation)
4. Check if WFA employee (should bypass)

**Code Fix**:
```typescript
// Fallback to manual location entry
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(success, error, {
        timeout: 10000,
        enableHighAccuracy: true,
        maximumAge: 0
    });
} else {
    // Show manual location entry
}
```

### Issue 5: Schedule Validation Failed

**Symptoms**: "Tidak ada jadwal yang di-assign untuk hari ini"

**Solutions**:
1. Check employee has MonthlySchedule assigned
2. Check schedule is published
3. Check date is within schedule period
4. For guru honorer: Check TeachingSchedule exists

**Database Check**:
```sql
-- Check employee schedule
SELECT * FROM employee_monthly_schedules
WHERE employee_id = 'uuid'
  AND month_year = '2025-12-01';

-- Check teaching schedule
SELECT * FROM teaching_schedules
WHERE employee_id = 'uuid'
  AND day_of_week = 5 -- Friday
  AND is_active = TRUE;
```

---

## Appendix C: API Quick Reference

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/attendance/validate-time` | POST | Validate check-in/out time |
| `/locations/verify` | POST | Verify GPS location |
| `/face/deepface/extract-embedding` | POST | Extract 512-d embedding |
| `/face/deepface/verify-face` | POST | Verify face against DB |
| `/face/deepface/check-liveness` | POST | Liveness check |
| `/face/deepface/analyze-emotion` | POST | Emotion analysis |
| `/face/deepface/health` | GET | Service health check |
| `/face-recognition/register` | POST | Register new face |
| `/face-recognition/update` | POST | Update face data |
| `/face-recognition/delete` | POST | Delete face data |
| `/attendance-face/check-in` | POST | Submit check-in |
| `/attendance-face/check-out` | POST | Submit check-out |
| `/attendance/today` | GET | Get today's attendance |
| `/attendance` | GET | Get attendance history |
| `/attendance/statistics` | GET | Get statistics |
| `/attendance/admin/stats` | GET | Admin dashboard stats |
| `/attendance/admin/records` | GET | Admin attendance records |
| `/attendance/{id}/approve` | POST | Approve attendance |
| `/attendance/{id}/reject` | POST | Reject attendance |
| `/attendance/manual` | POST | Manual entry |

---

## Conclusion

Sistem attendance dengan face recognition menggunakan DeepFace (ArcFace) memberikan:

**Benefits**:
- High accuracy (512-d embeddings, cosine similarity)
- Anti-spoofing (liveness detection, quality checks)
- Scalable (load balancing, clustering)
- Secure (encryption, RBAC, audit logs)
- User-friendly (auto-capture, real-time feedback)

**Performance**:
- Face detection: ~500ms
- Embedding extraction: ~1-2s
- Verification (1:N): ~100ms per 100 employees
- Total check-in time: ~3-5s

**Production Considerations**:
- Deploy 3+ DeepFace instances for redundancy
- Use Redis for face embedding cache
- Monitor with Sentry
- Regular backup of face data
- HTTPS required for camera access

---

**Documentation Version**: 1.0
**Last Updated**: 2025-12-20
**Author**: Claude Opus 4.5
