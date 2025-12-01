# UI Context: Mobile vs Desktop Attendance Pages

Dokumentasi perbedaan UI dan behavior antara Mobile dan Desktop attendance page.

## 📱 Overview

Attendance page menggunakan **conditional rendering** berdasarkan device:

```typescript
// pages/attendance/index.tsx
export default function AttendancePage() {
  const isMobile = useIsMobile();
  return isMobile ? <MobileAttendancePage /> : <DesktopAttendancePage />;
}
```

---

## 🎨 UI Comparison

### Mobile Version (`mobile.tsx`)

#### **Layout & Design:**
- **Full Screen Mobile-First**: Optimized untuk layar kecil (320px - 768px)
- **Gradient Header**: Glassmorphism dengan backdrop blur
- **Card-Based**: Setiap action dalam card terpisah
- **Touch-Friendly**: Tombol besar dengan minimum 44x44px hit area
- **Bottom Navigation**: Fixed navigation di bawah (optional)

#### **Main Components:**

```
┌─────────────────────────────────┐
│ Header (Gradient Primary)       │
│ ┌───────────────────────────┐   │
│ │ 👤 User Avatar + Name     │   │
│ │ "Om Swastyastu"           │   │
│ └───────────────────────────┘   │
│ ┌───────────────────────────┐   │
│ │ 📅 Date | ⏰ Time         │   │
│ └───────────────────────────┘   │
└─────────────────────────────────┘

┌─────────────────────────────────┐
│ Check In/Out Section            │
│ ┌───────────────────────────┐   │
│ │ 🟢 DATANG                 │   │ ← Tombol full width
│ │ "Absensi datang" / "08:00"│   │
│ └───────────────────────────┘   │
│ ┌───────────────────────────┐   │
│ │ 🔴 PULANG                 │   │ ← Tombol full width
│ │ "Absensi pulang" / "17:00"│   │
│ └───────────────────────────┘   │
└─────────────────────────────────┘

┌─────────────────────────────────┐
│ Pengajuan (Submissions)         │
│ ┌─────────┬─────────┐           │
│ │ Ubah    │ Cuti    │           │ ← Grid 2 kolom
│ │ Absen   │         │           │
│ ├─────────┴─────────┤           │
│ │ Dinas/Diklat      │→          │ ← Compact horizontal
│ ├───────────────────┤           │
│ │ Jadwal Saya       │→          │ ← Compact horizontal
│ └───────────────────┘           │
└─────────────────────────────────┘
```

#### **Button States:**

```typescript
// DATANG (Check In) Button
<button
  onClick={handleCheckIn}
  className="bg-gradient-to-br from-emerald-500 to-emerald-600"
>
  <LogIn /> Datang
  {check_in_time ? "08:00" : "Absensi datang"}
  {check_in_time ? <Clock /> : <ChevronRight />}
</button>

// PULANG (Check Out) Button
<button
  onClick={handleCheckOut}
  className="bg-gradient-to-br from-rose-500 to-rose-600"
>
  <LogOut /> Pulang
  {check_out_time ? "17:00" : "Absensi pulang"}
  {check_out_time ? <Clock /> : <ChevronRight />}
</button>
```

#### **Navigation Flow:**

```
Mobile Page
    ↓ Click "Datang"
verify-location.tsx (GPS Check)
    ↓ GPS Valid → Click "Selanjutnya"
verify-face.tsx (Face Recognition)
    ↓ Face Matched → Click "Konfirmasi"
Submit Attendance → Back to Mobile Page
```

#### **Key Features:**
- ✅ **Always Enabled**: Tombol tidak pernah disabled
- ✅ **Time Display**: Show waktu check-in/out setelah absensi
- ✅ **Icon Changes**: ChevronRight → Clock setelah absensi
- ✅ **Real-time Refetch**: Auto refresh setiap 30 detik
- ✅ **Compact Grid**: 2x2 grid untuk quick actions

---

### Desktop Version (`index.tsx → DesktopAttendancePage`)

#### **Layout & Design:**
- **Dashboard Layout**: Statistics + Data Table + Actions
- **Modal/Dialog Based**: Face recognition dalam modal popup
- **Full Featured**: Lengkap dengan filters, pagination, export
- **Multi-Column**: Menggunakan space horizontal lebih baik

#### **Main Components:**

```
┌──────────────────────────────────────────────────────────┐
│ Page Header                                              │
│ "Attendance Management" + Date/Time                      │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│ Statistics Grid (4 Columns)                              │
│ ┌────────┬────────┬────────┬────────┐                    │
│ │ Total  │ Present│ Absent │ Late   │                    │
│ │   120  │   95   │   15   │  10    │                    │
│ └────────┴────────┴────────┴────────┘                    │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│ Quick Actions (Inline Buttons)                           │
│ [🟢 Check In]  [🔴 Check Out]  [📊 Export]  [🔍 Filter]  │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│ Attendance Table                                          │
│ ┌──────┬────────┬──────────┬──────────┬────────┐        │
│ │ Date │ Name   │ Check In │ Check Out│ Status │        │
│ ├──────┼────────┼──────────┼──────────┼────────┤        │
│ │ 01/12│ John   │ 08:00    │ 17:00    │ ✓      │        │
│ │ 01/12│ Jane   │ 08:15    │ 17:05    │ Late   │        │
│ └──────┴────────┴──────────┴──────────┴────────┘        │
│                                          [1] [2] [3] >   │
└──────────────────────────────────────────────────────────┘
```

#### **Face Recognition Modal:**

```
┌─────────────────────────────────────────┐
│ Face Recognition Dialog                 │
│ ┌─────────────────────────────────────┐ │
│ │                                     │ │
│ │   📹 Video Preview                  │ │
│ │   (Live Camera Feed)                │ │
│ │                                     │ │
│ │   [Face Detection Overlay]          │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ Status: Detecting face...               │
│ Confidence: 85%                         │
│ [██████████░░░░░░░] 70%                │
│                                         │
│ Matched: John Doe                       │
│ Department: IT                          │
│                                         │
│         [Cancel]  [Confirm]             │
└─────────────────────────────────────────┘
```

#### **Modal Flow:**

```
Desktop Page
    ↓ Click "Check In" Button
Open Modal (Dialog)
    ↓ Auto start camera & face detection
Face Detection Running (in modal)
    ↓ Face detected & matched
Show Matched Info
    ↓ Click "Confirm"
Submit Attendance → Close Modal → Refresh Table
```

#### **Key Features:**
- ✅ **Modal-Based**: Face recognition dalam dialog popup
- ✅ **Table View**: List semua attendance dengan pagination
- ✅ **Filters**: Filter by status, date range, employee
- ✅ **Statistics**: Real-time stats cards
- ✅ **Bulk Actions**: Export, print, approve multiple
- ✅ **Advanced Search**: Search by name, date, status

---

## 🔄 Behavior Differences

### Check-In/Out Flow:

| Feature | Mobile | Desktop |
|---------|--------|---------|
| **Button Location** | Full-width cards | Inline buttons di header |
| **GPS Verification** | Separate page (`verify-location.tsx`) | Optional/modal |
| **Face Recognition** | Separate page (`verify-face.tsx`) | Modal dialog |
| **Navigation** | Page-to-page (Router) | Modal-based (State) |
| **Camera Access** | Full screen camera | Modal camera preview |
| **Result Display** | Next page | Same modal |
| **Success Action** | Navigate back | Close modal + refresh |

### State Management:

**Mobile:**
```typescript
// Uses navigation for flow
const handleCheckIn = () => {
  navigate({
    to: '/attendance/verify-location',
    search: { type: 'check-in' }
  });
};
```

**Desktop:**
```typescript
// Uses modal state
const [isDialogOpen, setIsDialogOpen] = useState(false);
const [checkMode, setCheckMode] = useState<'check_in' | 'check_out'>('check_in');

const openCheckDialog = async (mode) => {
  setCheckMode(mode);
  setIsDialogOpen(true);
  await startCamera();
};
```

---

## 📐 Responsive Breakpoints

```typescript
// lib/utils/device.ts
export function useIsMobile() {
  // Returns true if width <= 768px
  const [isMobile, setIsMobile] = useState(
    typeof window !== 'undefined' && window.innerWidth <= 768
  );

  useEffect(() => {
    const handleResize = () => {
      setIsMobile(window.innerWidth <= 768);
    };
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  return isMobile;
}
```

**Breakpoint:**
- **Mobile**: ≤ 768px
- **Desktop**: > 768px

---

## 🎯 Implementation Strategy

### When Implementing Face Recognition:

#### **Mobile Approach:**
1. Create separate verification pages:
   - `verify-location.tsx` (GPS check)
   - `verify-face.tsx` (Face recognition)
2. Use TanStack Router for navigation
3. Pass data via search params or state
4. Full-screen camera UI
5. Clear back/next navigation

**Example:**
```typescript
// mobile.tsx
const handleCheckIn = () => {
  navigate({
    to: '/attendance/verify-location',
    search: { type: 'check-in' }
  });
};

// verify-location.tsx
const handleNext = () => {
  navigate({
    to: '/attendance/verify-face',
    search: { type: search.type, location: gpsData }
  });
};

// verify-face.tsx
const handleConfirm = async () => {
  await submitAttendance({ ...faceData, ...locationData });
  navigate({ to: '/attendance' });
};
```

#### **Desktop Approach:**
1. Use Dialog/Modal components
2. Manage state internally
3. Show camera in modal
4. Step-by-step within same modal
5. Close modal on success

**Example:**
```typescript
// index.tsx (Desktop)
const [step, setStep] = useState<'gps' | 'face' | 'confirm'>('gps');

const handleCheckIn = () => {
  setIsDialogOpen(true);
  setStep('gps');
};

// Inside modal
{step === 'gps' && <GPSVerification onNext={() => setStep('face')} />}
{step === 'face' && <FaceVerification onNext={() => setStep('confirm')} />}
{step === 'confirm' && <ConfirmationStep onSubmit={submitAttendance} />}
```

---

## 🚀 Shared Components

Both mobile and desktop can share:

### 1. **Verification Pages** (Current Setup):
```
verify-location.tsx  ← Shared component
verify-face.tsx      ← Shared component
```

These pages can be:
- Rendered as **full page** (mobile navigation)
- Rendered **inside modal** (desktop dialog)

### 2. **API Functions** (Always Shared):
```typescript
// lib/api/face-recognition.ts
verifyFaceServer()     ← Used by both
extractEncodingServer() ← Used by both

// lib/api/attendance.ts
submitAttendance()     ← Used by both
verifyLocation()       ← Used by both
```

### 3. **Utilities** (Always Shared):
```typescript
// lib/utils/imageCompression.ts
captureAndCompress()   ← Used by both
compressImage()        ← Used by both
```

---

## 💡 Best Practices

### Mobile:
- ✅ Use full-screen pages for better focus
- ✅ Large touch targets (min 44x44px)
- ✅ Clear navigation with back buttons
- ✅ Minimal text, focus on actions
- ✅ Use bottom navigation for primary actions
- ✅ Show loading states clearly
- ✅ Auto-refetch data on page focus

### Desktop:
- ✅ Use modals for quick actions
- ✅ Show more data in tables
- ✅ Provide filters and search
- ✅ Keyboard shortcuts
- ✅ Hover states for better UX
- ✅ Multi-column layouts
- ✅ Export and bulk actions

---

## 📝 Summary

| Aspect | Mobile | Desktop |
|--------|--------|---------|
| **Primary Use** | Field employees, on-the-go | Office, admins, managers |
| **Navigation** | Page-based routing | Modal/dialog based |
| **Layout** | Vertical, single column | Horizontal, multi-column |
| **Actions** | One at a time, sequential | Multiple, parallel |
| **Data Display** | Minimal, focused | Comprehensive, detailed |
| **Face Recognition** | Full-screen page | Modal popup |
| **GPS Verification** | Dedicated page | Optional/inline |
| **Success Flow** | Navigate to success page | Close modal, show toast |

---

## 🎨 Visual Examples

### Mobile Flow:
```
[Attendance Page]
     ↓ Tap "Datang"
[GPS Verification Page] ← Full screen
     ↓ Tap "Selanjutnya"
[Face Recognition Page] ← Full screen camera
     ↓ Face matched
[Confirmation Page] ← Show details
     ↓ Tap "Konfirmasi"
[Success Page] ← Show success
     ↓ Auto redirect
[Attendance Page] ← Updated with time
```

### Desktop Flow:
```
[Attendance Dashboard]
     ↓ Click "Check In"
[Modal Opens]
  Step 1: GPS Check ← Inside modal
     ↓ GPS verified
  Step 2: Face Recognition ← Inside modal
     ↓ Face matched
  Step 3: Confirmation ← Inside modal
     ↓ Click "Submit"
[Modal Closes]
[Dashboard Updates] ← Table refreshes
[Toast Shows] ← "Check-in successful"
```

---

**File:** `UI_CONTEXT_MOBILE_VS_DESKTOP.md`
**Last Updated:** 2025-12-01 03:00 UTC
**Purpose:** Reference untuk implementasi face recognition di mobile & desktop
