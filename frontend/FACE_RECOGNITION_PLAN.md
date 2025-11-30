# Face Recognition Implementation Plan

## Overview

Implementasi sistem Face Recognition yang lengkap dengan flow pendaftaran wajah yang jelas dan akurat. Sistem mendukung:
- **Enrollment dari foto profil** (admin) dan **live camera** (self-service)
- **Face Identification** (1:N) - detect wajah dan identifikasi otomatis siapa orangnya
- **Face Verification** (1:1) - verifikasi wajah untuk attendance check-in/out
- **Liveness Detection** - optional anti-spoofing

---

## Current State Analysis

### Backend (Laravel) - ✅ READY
| Komponen | Status | File |
|----------|--------|------|
| API Register | ✅ Ready | `POST /api/face-recognition/register` |
| API Verify | ✅ Ready | `POST /api/face-recognition/verify` |
| API Liveness | ✅ Ready | `POST /api/face-recognition/check-liveness` |
| Face Service | ✅ Ready | `app/Services/FaceRecognitionService.php` |
| Repository | ✅ Ready | `app/Repositories/FaceRecognitionRepository.php` |
| Storage | ✅ Ready | `Employee.metadata['face_recognition']` |

### Frontend (React) - ❌ NEEDS WORK
| Komponen | Status | Issue |
|----------|--------|-------|
| Face Detection | ✅ Works | face-api.js integrated |
| API Integration | ❌ Missing | Tidak ada API calls ke backend |
| Enrollment Flow | ⚠️ Partial | UI ada, tidak save ke backend |
| Verification | ❌ Mock | Selalu return mock data |
| Liveness | ❌ Fake | Pakai random values |

---

## Implementation Plan

### Phase 1: API Service Layer
**Goal**: Buat service untuk komunikasi dengan backend

#### 1.1 Create Face Recognition API Service
**File**: `src/lib/api/face-recognition.ts`

```typescript
// Endpoints to implement:
- registerFace(employeeId, descriptor, confidence, image?)
- verifyFace(descriptor, confidence, livenessData?)
- getFaceData(employeeId)
- updateFace(employeeId, descriptor, confidence, image?)
- deleteFace(employeeId)
- getRegisteredFaces() // untuk local matching
- checkLiveness(livenessData)
- getStatistics()
```

#### 1.2 Create React Query Hooks
**File**: `src/hooks/use-face-recognition-api.ts`

```typescript
// Hooks to implement:
- useRegisterFace() // mutation
- useVerifyFace() // mutation
- useGetFaceData(employeeId) // query
- useUpdateFace() // mutation
- useDeleteFace() // mutation
- useRegisteredFaces() // query - fetch all for local matching
- useFaceStatistics() // query
```

---

### Phase 2: Enrollment Flow Redesign
**Goal**: Flow pendaftaran wajah yang jelas dengan 2 metode

#### 2.1 Admin Enrollment (Foto Profil)
**Location**: Employee Create/Edit page

**Flow**:
```
1. Admin buka halaman employee
2. Upload/pilih foto profil
3. System auto-detect wajah dari foto
4. Extract face descriptor (128-dim)
5. Tampilkan preview dengan bounding box
6. Admin confirm → Save ke backend
7. Employee sekarang "Face Registered"
```

**UI Components**:
- `FaceEnrollmentFromPhoto` - Upload foto, extract descriptor
- Preview dengan face detection overlay
- Quality score indicator
- Confirm/retry buttons

#### 2.2 Self-Service Enrollment (Live Camera)
**Location**: `/face-recognition` page atau Profile page

**Flow**:
```
1. Karyawan buka halaman Face Recognition
2. Klik "Daftarkan Wajah"
3. Camera aktif dengan guide overlay
4. Capture 3 foto dari sudut berbeda:
   - Foto 1: Hadap depan (frontal)
   - Foto 2: Sedikit ke kiri
   - Foto 3: Sedikit ke kanan
5. Optional: Liveness check (kedip/gerak)
6. Extract descriptors, average them
7. Quality check (min 0.7)
8. Save ke backend
9. Success → redirect ke verification test
```

**UI Components**:
- `FaceEnrollmentWizard` - Multi-step enrollment
- `CameraGuide` - Oval overlay untuk positioning
- `CaptureProgress` - 3-step indicator
- `QualityFeedback` - Real-time quality score
- `LivenessChallenge` - Blink/move head prompt

---

### Phase 3: Face Identification (1:N)
**Goal**: Detect wajah dan auto-identify siapa orangnya

#### 3.1 Identification Flow
```
1. Camera aktif, detect wajah
2. Extract descriptor dari wajah terdeteksi
3. Fetch all registered faces dari backend (cached)
4. Compare descriptor dengan semua registered faces
5. Find best match (highest similarity > 0.6)
6. Display: "Terdeteksi: [Nama Pegawai]" dengan confidence
7. Jika tidak match: "Wajah tidak dikenali"
```

#### 3.2 Local Matching dengan Cache
**Strategy**:
```
- Fetch registered faces saat app load
- Cache di memory/zustand store
- Refresh setiap 5 menit atau on-demand
- Local cosine similarity matching
- Fallback ke backend API jika cache miss
```

#### 3.3 UI Components
- `FaceIdentificationPanel` - Real-time identification
- `IdentifiedEmployee` - Card dengan foto, nama, department
- `ConfidenceIndicator` - Visual confidence meter
- `UnknownFaceAlert` - Prompt untuk register

---

### Phase 4: Attendance Integration
**Goal**: Face verification untuk check-in/out

#### 4.1 Check-in Flow dengan Face
```
1. Employee klik "Check In"
2. Camera aktif
3. Detect & identify face
4. Verify: detected face == logged-in user
5. Optional: Liveness check
6. Capture GPS location
7. Send to backend:
   - employee_id
   - face_descriptor
   - face_confidence
   - liveness_data (optional)
   - location {lat, lng}
8. Backend verify & create attendance record
9. Show success/failure
```

#### 4.2 API Request Format
```typescript
POST /api/attendance/check-in
{
  "employee_id": "uuid",
  "face_descriptor": [128 numbers],
  "face_confidence": 0.92,
  "location": {
    "latitude": -6.175,
    "longitude": 106.827,
    "accuracy": 10.5
  },
  "liveness_data": {
    "blink_detected": true,
    "head_movement": 0.15,
    "score": 0.89
  },
  "device_info": {
    "browser": "Chrome",
    "os": "Windows"
  }
}
```

---

### Phase 5: Liveness Detection (Optional)
**Goal**: Anti-spoofing untuk mencegah foto/video attack

#### 5.1 Liveness Checks
| Check | Method | Weight |
|-------|--------|--------|
| Blink | Eye Aspect Ratio change | 30% |
| Head Movement | Face position delta | 25% |
| Expression | Smile/neutral change | 20% |
| Texture | Moire pattern detection | 25% |

#### 5.2 Challenge Flow
```
1. Show random challenge: "Kedipkan mata" / "Gerakkan kepala ke kiri"
2. Monitor for 3 seconds
3. Detect if challenge completed
4. Calculate liveness score
5. Pass if score >= 0.8
```

#### 5.3 Settings Toggle
- Admin dapat enable/disable liveness per use-case
- Settings stored di backend
- Default: enabled untuk self-enrollment, disabled untuk admin enrollment

---

### Phase 6: Re-enrollment & Management
**Goal**: Update wajah kapan saja

#### 6.1 Update Face Flow
```
1. User buka Profile → Face Settings
2. Lihat current face status (registered/not)
3. Klik "Update Wajah"
4. Same flow as enrollment
5. Backend backup old face data
6. Replace with new descriptor
7. Log activity untuk audit
```

#### 6.2 Admin Management
- View all employees face status
- Filter: registered / not registered / low quality
- Bulk enroll dari foto
- Delete face data
- View verification logs

---

## File Structure

```
src/
├── lib/
│   ├── api/
│   │   └── face-recognition.ts       # NEW: API service
│   └── services/
│       └── face-detection.ts         # MODIFY: Add local matching
│
├── hooks/
│   ├── use-face-detection.ts         # MODIFY: Integrate API
│   └── use-face-recognition-api.ts   # NEW: React Query hooks
│
├── components/
│   └── face-recognition/
│       ├── FaceEnrollmentWizard.tsx  # NEW: Multi-step enrollment
│       ├── FaceEnrollmentFromPhoto.tsx # NEW: Photo-based enrollment
│       ├── FaceIdentificationPanel.tsx # NEW: 1:N identification
│       ├── CameraGuide.tsx           # NEW: Positioning guide
│       ├── QualityFeedback.tsx       # NEW: Quality indicator
│       ├── LivenessChallenge.tsx     # NEW: Liveness prompts
│       ├── ConfidenceIndicator.tsx   # NEW: Confidence meter
│       └── IdentifiedEmployee.tsx    # NEW: Result card
│
├── pages/
│   ├── face-recognition/
│   │   ├── index.tsx                 # MODIFY: Use new components
│   │   └── settings.tsx              # MODIFY: Connect to API
│   ├── attendance/
│   │   └── index.tsx                 # MODIFY: Real face verification
│   └── employees/
│       ├── create.tsx                # MODIFY: Add face enrollment
│       └── edit.tsx                  # MODIFY: Add face management
│
└── stores/
    └── face-store.ts                 # NEW: Zustand store for cache
```

---

## Implementation Order

### Week 1: Foundation
1. [ ] Create `src/lib/api/face-recognition.ts`
2. [ ] Create `src/hooks/use-face-recognition-api.ts`
3. [ ] Create `src/stores/face-store.ts` untuk cache
4. [ ] Test API connectivity dengan backend

### Week 2: Enrollment
5. [ ] Build `FaceEnrollmentFromPhoto` component
6. [ ] Build `FaceEnrollmentWizard` component
7. [ ] Integrate enrollment ke Employee create/edit
8. [ ] Integrate self-enrollment ke Face Recognition page

### Week 3: Identification & Verification
9. [ ] Implement local face matching dengan cache
10. [ ] Build `FaceIdentificationPanel`
11. [ ] Integrate face verification ke Attendance
12. [ ] Build real-time identification display

### Week 4: Polish & Liveness
13. [ ] Implement `LivenessChallenge` component
14. [ ] Add quality feedback UI
15. [ ] Admin face management page
16. [ ] Testing & bug fixes

---

## API Endpoints Summary

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/face-recognition/register` | Register new face |
| POST | `/api/face-recognition/verify` | Verify face (1:N) |
| POST | `/api/face-recognition/get-data` | Get employee face data |
| POST | `/api/face-recognition/update` | Update face |
| POST | `/api/face-recognition/delete` | Delete face |
| POST | `/api/face-recognition/check-liveness` | Liveness check |
| GET | `/api/face-recognition/statistics` | Get stats |
| POST | `/api/attendance/check-in` | Check-in with face |
| POST | `/api/attendance/check-out` | Check-out with face |

---

## Success Criteria

1. **Enrollment Accuracy**: Face descriptor tersimpan dengan benar di backend
2. **Identification Speed**: < 500ms untuk identify dari 100 employees
3. **Verification Accuracy**: > 95% true positive rate
4. **False Positive Rate**: < 0.1% (salah identify orang lain)
5. **Liveness Detection**: Block 95%+ spoofing attempts
6. **UX**: Clear feedback, progress indicators, error handling

---

## Technical Notes

### Face Descriptor Format
- 128-dimensional Float32Array
- Range: -1 to 1
- Normalized L2 vector
- Algorithm: face-api.js (resnet-based)

### Similarity Matching
- Method: Cosine Similarity
- Threshold: 0.6 (configurable)
- Higher = more similar (1.0 = identical)

### Quality Score Factors
- Detection confidence (30%)
- Face size in frame (20%)
- Head pose angle (20%)
- Lighting uniformity (15%)
- Image sharpness (15%)

### Caching Strategy
- Registered faces: 5 minutes TTL
- Invalidate on: register, update, delete
- Storage: Zustand + localStorage fallback
