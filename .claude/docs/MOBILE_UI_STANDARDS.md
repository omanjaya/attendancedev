# Mobile UI Standards & Design System

> Dokumentasi standar UI untuk tampilan mobile aplikasi Attendance System.
> Gunakan dokumen ini sebagai referensi saat membuat atau memperbaiki halaman mobile.

---

## Table of Contents

1. [Header System](#1-header-system)
2. [Card Styles](#2-card-styles)
3. [Spacing System](#3-spacing-system)
4. [Status Badge Colors](#4-status-badge-colors)
5. [Typography](#5-typography)
6. [FAB (Floating Action Button)](#6-fab-floating-action-button)
7. [Empty State](#7-empty-state)
8. [Loading State](#8-loading-state)
9. [Sheet & Dialog](#9-sheet--dialog)
10. [Implementation Checklist](#10-implementation-checklist)
11. [Progress Tracker](#11-progress-tracker)

---

## 1. Header System

### Wajib Menggunakan `MobilePageHeader`

Semua halaman mobile **WAJIB** menggunakan component `MobilePageHeader` untuk konsistensi.

### Gradient Color Mapping

| Kategori | Gradient | Halaman |
|----------|----------|---------|
| Dashboard/Home | `blue` | Dashboard (Admin & Employee) |
| Kehadiran | `emerald` | Attendance, Corrections |
| Jadwal | `indigo` | Schedules, Teaching Schedule |
| Cuti/Izin | `teal` | Leave (Admin & Employee) |
| Keuangan | `amber` | Payroll |
| Pengguna | `violet` | Users, Profile, Employees |
| Lokasi | `cyan` | Locations |
| Pengaturan | `slate` | Settings, Master Data |
| Laporan | `rose` | Reports |
| Hari Libur | `pink` | Holidays |

### Usage Example

```tsx
<MobilePageHeader
  title="Pengajuan Cuti"
  gradient="teal"
  backTo="/employee/dashboard"

  // Optional: Right action button
  rightAction={
    <Button variant="ghost" size="icon" className="text-white">
      <Search className="h-5 w-5" />
    </Button>
  }

  // Optional: Subtitle/filters below title
  subtitle={
    <div className="flex gap-2 mt-2">
      <Badge variant="secondary">Filter</Badge>
    </div>
  }
/>
```

### MobilePageHeader Props (To Be Enhanced)

```tsx
interface MobilePageHeaderProps {
  title: string;
  gradient: 'blue' | 'emerald' | 'indigo' | 'teal' | 'amber' | 'violet' | 'cyan' | 'slate' | 'rose' | 'pink';
  backTo?: string;
  onBack?: () => void;
  showSearch?: boolean;
  onSearch?: (query: string) => void;
  rightAction?: React.ReactNode;
  subtitle?: React.ReactNode;
}
```

---

## 2. Card Styles

### Standard Card

```tsx
className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50"
```

**Rules:**
- Border radius: `rounded-2xl` (16px) untuk semua card
- Shadow: `shadow-sm` untuk light mode
- Dark mode: Tambahkan subtle border `dark:border dark:border-border/50`
- Background: `bg-card` (uses theme)

### Card Variants

| Type | Classes |
|------|---------|
| **Standard Card** | `bg-card rounded-2xl shadow-sm dark:border dark:border-border/50` |
| **Stats Card** | `bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4` |
| **List Item Card** | `bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4` |
| **Nested Card** | `bg-muted/30 rounded-xl border border-border/50 p-3` |

### Card Padding

```tsx
// Standard card content
className="p-4"

// Compact card (stats, small items)
className="p-3"
```

---

## 3. Spacing System

### Page Layout

```tsx
// Page container
<div className="min-h-screen bg-background pb-24">
  {/* MobilePageHeader */}

  {/* Content */}
  <div className="px-4 space-y-4">
    {/* Sections */}
  </div>
</div>
```

### Spacing Values

| Element | Value | Keterangan |
|---------|-------|------------|
| Page horizontal padding | `px-4` | 16px left/right |
| Page bottom padding | `pb-24` | Safe area untuk bottom nav |
| Section gap | `space-y-4` | 16px antar section |
| Card internal padding | `p-4` | 16px all sides |
| Card compact padding | `p-3` | 12px all sides |
| Grid gap | `gap-3` | 12px antar items |
| Inline gap | `gap-2` | 8px antar inline items |

### Grid Layouts

```tsx
// 2 columns (stats, quick actions)
<div className="grid grid-cols-2 gap-3">

// 3 columns (icons, small items)
<div className="grid grid-cols-3 gap-3">

// List (full width cards)
<div className="space-y-3">
```

---

## 4. Status Badge Colors

### Color Mapping

```tsx
const statusColors = {
  // Waiting/Pending states
  pending: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',
  draft: 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700',

  // Processing states
  processing: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800',
  reviewing: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800',

  // Success states
  approved: 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800',
  completed: 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800',
  active: 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800',
  present: 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800',

  // Warning states
  late: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',

  // Error states
  rejected: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800',
  absent: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800',

  // Neutral states
  cancelled: 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700',
  inactive: 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700',

  // Info states
  leave: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800',
  holiday: 'bg-pink-100 text-pink-700 border-pink-200 dark:bg-pink-900/30 dark:text-pink-400 dark:border-pink-800',
};
```

### Usage

```tsx
// Simple badge
<Badge className={`border ${statusColors[status]}`}>
  {statusLabel}
</Badge>

// Badge with icon
<Badge className={`border ${statusColors[status]} flex items-center gap-1`}>
  <Clock className="h-3 w-3" />
  {statusLabel}
</Badge>
```

### Status Labels (Indonesian)

```tsx
const statusLabels = {
  pending: 'Menunggu',
  draft: 'Draft',
  processing: 'Diproses',
  reviewing: 'Ditinjau',
  approved: 'Disetujui',
  completed: 'Selesai',
  active: 'Aktif',
  present: 'Hadir',
  late: 'Terlambat',
  rejected: 'Ditolak',
  absent: 'Tidak Hadir',
  cancelled: 'Dibatalkan',
  inactive: 'Nonaktif',
  leave: 'Cuti',
  holiday: 'Libur',
};
```

---

## 5. Typography

### Text Styles

| Element | Classes |
|---------|---------|
| Page title | Handled by `MobilePageHeader` |
| Section title | `text-sm font-bold text-foreground` |
| Card title | `text-sm font-semibold text-foreground` |
| Card subtitle | `text-xs text-muted-foreground` |
| Body text | `text-sm text-foreground` |
| Caption/Helper | `text-xs text-muted-foreground` |
| Large number/stat | `text-2xl font-bold text-foreground` |
| Medium number | `text-lg font-bold text-foreground` |

### Usage Examples

```tsx
// Section header
<h2 className="text-sm font-bold text-foreground">Riwayat Pengajuan</h2>

// Card title + subtitle
<div>
  <h3 className="text-sm font-semibold text-foreground">Cuti Tahunan</h3>
  <p className="text-xs text-muted-foreground">3 hari - 20-22 Des 2025</p>
</div>

// Stats
<div>
  <p className="text-2xl font-bold text-foreground">12</p>
  <p className="text-xs text-muted-foreground">Hari tersisa</p>
</div>
```

---

## 6. FAB (Floating Action Button)

### Standard FAB

```tsx
<Button
  size="icon"
  className="fixed bottom-24 right-4 h-14 w-14 rounded-full shadow-lg z-50"
  onClick={handleAdd}
>
  <Plus className="h-6 w-6" />
</Button>
```

### FAB with Gradient (Match Header)

```tsx
// For teal pages (Leave)
<Button
  size="icon"
  className="fixed bottom-24 right-4 h-14 w-14 rounded-full shadow-lg z-50 bg-teal-600 hover:bg-teal-700"
>
  <Plus className="h-6 w-6" />
</Button>

// For emerald pages (Attendance)
className="... bg-emerald-600 hover:bg-emerald-700"

// For pink pages (Holidays)
className="... bg-pink-600 hover:bg-pink-700"
```

### FAB Position Rules

- `bottom-24` - Above bottom navigation (96px)
- `right-4` - Consistent with page padding (16px)
- `h-14 w-14` - 56px touch target
- `z-50` - Above other content

---

## 7. Empty State

### Reusable Component (To Be Created)

```tsx
interface EmptyStateProps {
  icon: LucideIcon;
  title: string;
  description?: string;
  action?: {
    label: string;
    onClick: () => void;
  };
}
```

### Standard Empty State

```tsx
<div className="flex flex-col items-center justify-center py-12">
  <div className="h-16 w-16 rounded-full bg-muted flex items-center justify-center mb-4">
    <Calendar className="h-8 w-8 text-muted-foreground" />
  </div>
  <p className="text-sm font-semibold text-foreground mb-1">Tidak Ada Data</p>
  <p className="text-xs text-muted-foreground text-center max-w-[200px]">
    Belum ada pengajuan cuti yang diajukan
  </p>
</div>
```

### With Action Button

```tsx
<div className="flex flex-col items-center justify-center py-12">
  <div className="h-16 w-16 rounded-full bg-muted flex items-center justify-center mb-4">
    <Plus className="h-8 w-8 text-muted-foreground" />
  </div>
  <p className="text-sm font-semibold text-foreground mb-1">Tidak Ada Data</p>
  <p className="text-xs text-muted-foreground text-center max-w-[200px] mb-4">
    Mulai dengan menambahkan data baru
  </p>
  <Button size="sm" onClick={handleAdd}>
    <Plus className="h-4 w-4 mr-2" />
    Tambah Baru
  </Button>
</div>
```

---

## 8. Loading State

### Always Use LoadingState Component

```tsx
import { LoadingState } from '@/components/states';

// In your component
{isLoading && (
  <div className="flex items-center justify-center py-12">
    <LoadingState message="Memuat data..." size="sm" />
  </div>
)}
```

### Loading Messages (Indonesian)

- Default: `"Memuat data..."`
- Submitting: `"Menyimpan..."`
- Deleting: `"Menghapus..."`
- Uploading: `"Mengunggah..."`
- Processing: `"Memproses..."`

---

## 9. Sheet & Dialog

### Sheet Heights

| Type | Height | Use Case |
|------|--------|----------|
| Form Sheet | `h-[85vh]` | Create/Edit forms |
| Detail Sheet | `h-[80vh]` | View detail information |
| Action Sheet | `max-h-[60vh]` | Quick actions, confirmations |

### Sheet Example

```tsx
<Sheet open={isOpen} onOpenChange={setIsOpen}>
  <SheetContent side="bottom" className="h-[85vh] rounded-t-3xl">
    <SheetHeader>
      <SheetTitle>Ajukan Cuti</SheetTitle>
    </SheetHeader>
    <div className="space-y-4 mt-4 overflow-y-auto">
      {/* Form content */}
    </div>
  </SheetContent>
</Sheet>
```

### Dialog (Confirmation)

```tsx
<AlertDialog>
  <AlertDialogContent className="w-[90%] rounded-2xl">
    <AlertDialogHeader>
      <AlertDialogTitle>Konfirmasi Hapus</AlertDialogTitle>
      <AlertDialogDescription>
        Apakah Anda yakin ingin menghapus data ini?
      </AlertDialogDescription>
    </AlertDialogHeader>
    <AlertDialogFooter>
      <AlertDialogCancel>Batal</AlertDialogCancel>
      <AlertDialogAction className="bg-red-600 hover:bg-red-700">
        Hapus
      </AlertDialogAction>
    </AlertDialogFooter>
  </AlertDialogContent>
</AlertDialog>
```

---

## 10. Implementation Checklist

Gunakan checklist ini saat membuat/memperbaiki halaman mobile:

### Page Structure
- [ ] Menggunakan `MobilePageHeader` dengan gradient yang sesuai
- [ ] Page container: `min-h-screen bg-background pb-24`
- [ ] Content wrapper: `px-4 space-y-4`

### Cards
- [ ] Border radius: `rounded-2xl`
- [ ] Shadow: `shadow-sm`
- [ ] Dark mode border: `dark:border dark:border-border/50`
- [ ] Padding: `p-4` (standard) atau `p-3` (compact)

### Status Badges
- [ ] Menggunakan warna dari `statusColors`
- [ ] Include dark mode variants
- [ ] Label dalam Bahasa Indonesia

### Empty State
- [ ] Icon dalam circle background
- [ ] Title: `text-sm font-semibold`
- [ ] Description: `text-xs text-muted-foreground`

### Loading State
- [ ] Menggunakan `LoadingState` component
- [ ] Message dalam Bahasa Indonesia

### FAB (jika ada)
- [ ] Position: `fixed bottom-24 right-4`
- [ ] Size: `h-14 w-14`
- [ ] Warna sesuai gradient halaman

### Typography
- [ ] Section title: `text-sm font-bold`
- [ ] Card title: `text-sm font-semibold`
- [ ] Subtitle/caption: `text-xs text-muted-foreground`

---

## 11. Progress Tracker

### Phase 1: Foundation (Components)

| Task | Status | Notes |
|------|--------|-------|
| Update `MobilePageHeader` component | [ ] Pending | Add gradient system, rightAction, subtitle props |
| Create `MobileEmptyState` component | [ ] Pending | Reusable empty state |
| Create `MobileStatusBadge` component | [ ] Pending | Centralized status colors |
| Create `statusColors` utility | [ ] Pending | Shared color definitions |

### Phase 2: Employee Pages

| Page | Status | File Path |
|------|--------|-----------|
| Employee Dashboard | [ ] Pending | `/pages/employee/dashboard/mobile.tsx` |
| Employee Attendance | [ ] Pending | `/pages/employee/attendance/mobile.tsx` |
| Employee Leave | [ ] Pending | `/pages/employee/leave/mobile.tsx` |
| Employee Corrections | [ ] Pending | `/pages/employee/corrections/mobile.tsx` |
| Employee Profile | [ ] Pending | `/pages/employee/profile/mobile.tsx` |
| Employee Schedule | [ ] Pending | `/pages/employee/schedule/mobile.tsx` |
| Employee Teaching Schedule | [ ] Pending | `/pages/employee/teaching-schedule/mobile.tsx` |
| Employee Payroll | [ ] Pending | `/pages/employee/payroll/mobile.tsx` |
| Employee Reports | [ ] Pending | `/pages/employee/reports/mobile.tsx` |

### Phase 3: Admin Pages

| Page | Status | File Path |
|------|--------|-----------|
| Admin Dashboard | [ ] Pending | `/pages/admin/dashboard/mobile.tsx` |
| Admin Attendance | [ ] Pending | `/pages/admin/attendance/mobile.tsx` |
| Admin Leave | [ ] Pending | `/pages/admin/leave/mobile.tsx` |
| Admin Corrections | [ ] Pending | `/pages/admin/corrections/mobile.tsx` |
| Admin Schedules | [ ] Pending | `/pages/admin/schedules/mobile.tsx` |
| Admin Employees | [ ] Pending | `/pages/admin/employees/mobile.tsx` |
| Admin Users | [ ] Pending | `/pages/admin/users/mobile.tsx` |
| Admin Locations | [ ] Pending | `/pages/admin/locations/mobile.tsx` |
| Admin Holidays | [ ] Pending | `/pages/admin/holidays/mobile.tsx` |
| Admin Payroll | [ ] Pending | `/pages/admin/payroll/mobile.tsx` |
| Admin Settings | [ ] Pending | `/pages/admin/settings/mobile.tsx` |
| Admin Reports | [ ] Pending | `/pages/admin/reports/mobile.tsx` |
| Admin Face Recognition | [ ] Pending | `/pages/admin/face-recognition/mobile.tsx` |
| Admin Security | [ ] Pending | `/pages/admin/security/mobile.tsx` |

---

## Quick Reference Card

```
┌─────────────────────────────────────────────────────────────┐
│ MOBILE UI QUICK REFERENCE                                   │
├─────────────────────────────────────────────────────────────┤
│ Page:        min-h-screen bg-background pb-24               │
│ Content:     px-4 space-y-4                                 │
│ Card:        rounded-2xl shadow-sm dark:border              │
│ Card pad:    p-4 (standard) | p-3 (compact)                 │
│ Grid:        grid grid-cols-2 gap-3                         │
│ FAB:         fixed bottom-24 right-4 h-14 w-14              │
│ Sheet:       h-[85vh] rounded-t-3xl (form)                  │
│ Dialog:      w-[90%] rounded-2xl                            │
├─────────────────────────────────────────────────────────────┤
│ TYPOGRAPHY                                                  │
│ Section:     text-sm font-bold                              │
│ Card title:  text-sm font-semibold                          │
│ Subtitle:    text-xs text-muted-foreground                  │
│ Big number:  text-2xl font-bold                             │
├─────────────────────────────────────────────────────────────┤
│ HEADER GRADIENTS                                            │
│ Dashboard: blue    │ Attendance: emerald │ Schedule: indigo │
│ Leave: teal        │ Payroll: amber      │ Users: violet    │
│ Locations: cyan    │ Settings: slate     │ Reports: rose    │
│ Holidays: pink     │                     │                  │
└─────────────────────────────────────────────────────────────┘
```

---

*Last Updated: December 2025*
*Document Version: 1.0*
