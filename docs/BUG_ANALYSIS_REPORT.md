# Bug Analysis & Issues Report

## Tanggal Analisis: 8 Desember 2025

---

## 📋 Ringkasan Temuan

| Kategori | Jumlah | Prioritas |
|----------|--------|-----------|
| 🔴 **Bug Kritis** | 0 | - |
| 🟠 **Bug Medium** | 5 | P2 |
| 🟡 **Bug Minor** | 8 | P3 |
| 🔵 **TODO/Incomplete** | 12 | P4 |
| ⚪ **Code Quality** | 10+ | P5 |

---

## 🔴 Bug Kritis (Priority 1)

*Tidak ditemukan bug kritis yang menghalangi penggunaan sistem*

---

## 🟠 Bug Medium (Priority 2)

### 1. **Employee Attendance Page - Hardcoded Mock Data**

**File:** `frontend/src/pages/employee/attendance/desktop.tsx` (Line 27-85)

**Masalah:**
Halaman attendance untuk employee menggunakan data mock hardcoded alih-alih memanggil API yang sebenarnya.

```typescript
// TODO: Replace with actual API call
return {
  stats: {
    totalDays: 22,
    present: 18,
    late: 3,
    // ... hardcoded data
  },
  records: [
    { id: 1, date: '2025-12-01', ... },
    // ... more hardcoded records
  ],
};
```

**Dampak:**

- Employee tidak bisa melihat data kehadiran aktual mereka
- Statistik kehadiran tidak akurat

**Solusi:**
Ganti dengan API call ke `/v1/attendance/data` dengan filter employee_id

---

### 2. **Admin Attendance - API Mutations Not Implemented**

**File:** `frontend/src/pages/admin/attendance/desktop.tsx` (Lines 75-120)

**Masalah:**
Fungsi approve, reject, dan manual entry hanya logging ke console tanpa actual API call.

```typescript
// TODO: Replace with actual API call
console.log('Approving attendance:', id);
return { success: true };
```

**Dampak:**

- Admin tidak bisa approve/reject koreksi absensi
- Manual entry tidak disimpan ke database

**Solusi:**
Implementasi endpoint:

- `POST /v1/attendance/{id}/approve`
- `POST /v1/attendance/{id}/reject`
- `POST /v1/attendance/manual`

---

### 3. **Export Functionality Not Implemented**

**File:** Multiple files

**Lokasi:**

- `frontend/src/pages/employee/attendance/desktop.tsx` (Line 176)
- `frontend/src/pages/admin/attendance/desktop.tsx` (Line 155)
- `frontend/src/pages/employee/payroll/desktop.tsx` (Line 139)

**Masalah:**

```typescript
const handleExport = () => {
  // TODO: Implement export functionality
  console.log('Export attendance data');
};
```

**Dampak:**

- User tidak bisa export data ke CSV/Excel
- Feature export button tidak berfungsi

---

### 4. **canAccessRoute Function Incomplete**

**File:** `frontend/src/lib/auth/guards.ts` (Lines 194-209)

**Masalah:**

```typescript
export function canAccessRoute(user: User | null, route: string): boolean {
  // ...
  if (route.startsWith('/admin/')) {
    return hasRole(user, 'admin'); // Hanya check 'admin', tidak check 'super-admin' atau 'kepala-sekolah'
  }
  // ...
}
```

**Dampak:**
Fungsi ini tidak konsisten dengan implementasi guard lainnya yang menggunakan `hasAnyRole(user, ['admin', 'super-admin', 'kepala-sekolah'])`.

**Solusi:**

```typescript
if (route.startsWith('/admin/')) {
  return hasAnyRole(user, ['admin', 'super-admin', 'kepala-sekolah']);
}
```

---

### 5. **Employee Payroll Page - Mock Data**

**File:** `frontend/src/pages/employee/payroll/desktop.tsx` (Line 58)

**Masalah:**
Menggunakan data mock untuk payroll employee.

```typescript
// TODO: Replace with actual API call
```

**Dampak:**
Employee tidak bisa melihat slip gaji aktual.

---

## 🟡 Bug Minor (Priority 3)

### 1. **Unused Variable Warning**

**File:** `frontend/src/pages/employee/attendance/desktop.tsx` (Line 24)

```typescript
const { data: attendanceData, isLoading: _isLoading } = useQuery({
```

**Masalah:** Variable `_isLoading` tidak digunakan.

**Solusi:** Hapus atau gunakan untuk loading state di UI.

---

### 2. **Mobile Payroll Console Log**

**File:** `frontend/src/pages/employee/payroll/mobile.tsx` (Line 328)

```typescript
<Button className="w-full" onClick={() => console.log('Download PDF')}>
```

**Masalah:** Button download PDF hanya logging ke console.

---

### 3. **Profile Edit Console Logs**

**File:** `frontend/src/pages/employee/profile/edit.tsx` (Lines 84, 98)

```typescript
console.log('Updating profile:', data);
console.log('Updating password');
```

**Masalah:** Debug log masih ada di production code.

---

### 4. **Manual Entry List Hardcoded**

**File:** `frontend/src/pages/admin/attendance/desktop.tsx` (Lines 477-481)

```typescript
<option value="101">John Doe (#101)</option>
<option value="102">Jane Smith (#102)</option>
```

**Masalah:** List employee di manual entry form hardcoded.

**Solusi:** Fetch dari `/v1/employees` endpoint.

---

### 5. **Check-in Flow Debug Logs**

**File:** `frontend/src/pages/attendance/check-in-flow.tsx` (Multiple lines)

```typescript
console.log('[DEBUG] Initial todayAttendanceData:', currentAttendance);
console.log('[DEBUG] Data missing, refetching...');
```

**Masalah:** Debug logs tidak di-strip untuk production.

---

### 6. **Report Mobile Debug Log**

**File:** `frontend/src/pages/admin/reports/mobile.tsx` (Line 45)

```typescript
console.log('DEBUG: Initial mobile report response:', response);
```

---

### 7. **Face Recognition Console Logs**

**File:** `frontend/src/pages/employee/profile/desktop.tsx` & `mobile.tsx` (Multiple)

Banyak console.log untuk face registration yang seharusnya di-remove di production.

---

### 8. **Monthly Schedule Console Logs**

**File:** `frontend/src/pages/admin/schedules/monthly/index.tsx` (Lines 177-178)

```typescript
console.log('✅ handleAssign called');
console.log('  Schedule:', assignDialog.schedule);
```

---

## 🔵 TODO Items (Priority 4)

Berikut daftar lengkap TODO yang ditemukan:

| No | File | Line | Description |
|----|------|------|-------------|
| 1 | admin/attendance/desktop.tsx | 78 | Replace approve API call |
| 2 | admin/attendance/desktop.tsx | 91 | Replace reject API call |
| 3 | admin/attendance/desktop.tsx | 110 | Replace manual entry API call |
| 4 | admin/attendance/desktop.tsx | 155 | Implement export functionality |
| 5 | admin/employees/show.tsx | 46 | Integrate with attendance API |
| 6 | admin/attendance/mobile.tsx | 41 | Replace with actual API call |
| 7 | admin/attendance/mobile.tsx | 55 | Replace with actual API call |
| 8 | employee/payroll/mobile.tsx | 53 | Replace with actual API call |
| 9 | employee/attendance/desktop.tsx | 27 | Replace with actual API call |
| 10 | employee/attendance/desktop.tsx | 176 | Implement export functionality |
| 11 | employee/payroll/desktop.tsx | 58 | Replace with actual API call |
| 12 | employee/payroll/desktop.tsx | 139 | Implement PDF download |

---

## ⚪ Code Quality Issues (Priority 5)

### 1. **Console.log Statements**

**Jumlah:** 50+ instance ditemukan

**Rekomendasi:**

- Gunakan proper logging library (e.g., pino, winston)
- Implementasi environment-aware logging
- Strip console.log di production build

### 2. **Type Safety**

Beberapa penggunaan `any` type ditemukan yang sebaiknya diganti dengan proper typing.

### 3. **Confirm/Prompt Usage**

**File:** `frontend/src/pages/admin/attendance/desktop.tsx` (Lines 131, 137)

```typescript
if (confirm('Apakah Anda yakin ingin menyetujui absensi ini?')) {
  // ...
}
const reason = prompt('Alasan penolakan:');
```

**Rekomendasi:** Gunakan custom dialog component (shadcn AlertDialog) untuk UX yang lebih baik.

### 4. **Hardcoded Strings**

Banyak string yang hardcoded dalam Bahasa Indonesia. Pertimbangkan untuk menggunakan i18n library jika rencana multi-language.

---

## 📊 File-by-File Impact Analysis

### High Impact Files (Perlu diperbaiki segera)

1. `frontend/src/pages/employee/attendance/desktop.tsx` - Data tidak aktual
2. `frontend/src/pages/admin/attendance/desktop.tsx` - Approve/reject tidak berfungsi
3. `frontend/src/lib/auth/guards.ts` - Role check inconsistent

### Medium Impact Files

1. `frontend/src/pages/employee/payroll/desktop.tsx`
2. `frontend/src/pages/employee/payroll/mobile.tsx`

### Low Impact Files (Code quality)

1. Profile pages - console logs
2. Check-in flow - debug logs
3. Face recognition pages - verbose logging

---

## ✅ Recommendations

### Immediate Actions (Sprint ini)

1. **Implementasi employee attendance API integration** - Replace mock data dengan actual API call
2. **Fix canAccessRoute function** - Tambahkan all admin roles ke check
3. **Implementasi approve/reject endpoints** - Backend dan frontend

### Short-term (1-2 Sprint)

1. Remove semua console.log statements
2. Implementasi export functionality (CSV/Excel)
3. Ganti confirm/prompt dengan proper UI components
4. Implementasi payroll API integration

### Long-term

1. Implement proper error boundary dan error handling
2. Add comprehensive unit tests
3. Implement i18n untuk multi-language support
4. Add end-to-end testing dengan Playwright

---

## 🔧 Quick Fixes Available

### 1. Fix canAccessRoute (5 menit)

```typescript
// frontend/src/lib/auth/guards.ts line 198-199
if (route.startsWith('/admin/')) {
  return hasAnyRole(user, ['admin', 'super-admin', 'kepala-sekolah']);
}
```

### 2. Remove unused _isLoading (1 menit)

```typescript
// frontend/src/pages/employee/attendance/desktop.tsx line 24
const { data: attendanceData } = useQuery({
// Remove isLoading: _isLoading jika tidak digunakan
```

---

*Report generated: 8 Desember 2025*
*Analyzed by: AI System Analyst*
