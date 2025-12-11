# Page Structure Documentation

Dokumentasi struktur halaman frontend dan status responsive (mobile/desktop).

## Konvensi Struktur

Setiap halaman yang memerlukan tampilan berbeda untuk mobile dan desktop harus memiliki struktur:

```
pages/
└── [area]/
    └── [feature]/
        ├── index.tsx      # Entry point dengan useIsMobile()
        ├── desktop.tsx    # Tampilan desktop
        └── mobile.tsx     # Tampilan mobile
```

### Pattern index.tsx

```tsx
import { useIsMobile } from '@/lib/utils/device';
import { MobileFeaturePage } from './mobile';
import { DesktopFeaturePage } from './desktop';

export default function FeaturePage() {
  const isMobile = useIsMobile();
  return isMobile ? <MobileFeaturePage /> : <DesktopFeaturePage />;
}
```

---

## Status Halaman

### Employee Pages

| Halaman | index | desktop | mobile | Status |
|---------|:-----:|:-------:|:------:|--------|
| attendance | ✅ | ✅ | ✅ | **Lengkap** |
| corrections | ✅ | ❌ | ❌ | ⚠️ Perlu split |
| dashboard | ✅ | ✅ | ❌ | ⚠️ Perlu mobile |
| leave | ✅ | ✅ | ✅ | **Lengkap** |
| payroll | ✅ | ✅ | ✅ | **Lengkap** |
| profile | ✅ | ✅ | ✅ | **Lengkap** |
| reports | ✅ | ✅ | ✅ | **Lengkap** |
| schedule | ✅ | ✅ | ✅ | **Lengkap** |
| teaching-schedule | ✅ | ✅ | ✅ | **Lengkap** |

### Admin Pages

| Halaman | index | desktop | mobile | Status |
|---------|:-----:|:-------:|:------:|--------|
| attendance | ✅ | ✅ | ✅ | **Lengkap** |
| corrections | ✅ | ❌ | ❌ | ⚠️ Perlu split |
| dashboard | ✅ | ✅ | ✅ | **Lengkap** |
| employees | ✅ | ✅ | ✅ | **Lengkap** |
| face-recognition | ✅ | ❌ | ❌ | Desktop only (OK) |
| holidays | ✅ | ✅ | ✅ | **Lengkap** |
| leave | ✅ | ✅ | ✅ | **Lengkap** |
| locations | ✅ | ✅ | ✅ | **Lengkap** |
| master-data | ✅ | ❌ | ❌ | Desktop only (OK) |
| payroll | ✅ | ✅ | ✅ | **Lengkap** |
| reports | ✅ | ✅ | ✅ | **Lengkap** |
| schedules | ✅ | ✅ | ✅ | **Lengkap** |
| security | ✅ | ❌ | ❌ | Desktop only (OK) |
| services | ✅ | ❌ | ❌ | Desktop only (OK) |
| settings | ✅ | ✅ | ✅ | **Lengkap** |
| users | ✅ | ✅ | ✅ | **Lengkap** |

### Shared Pages

| Halaman | Responsive | Catatan |
|---------|:----------:|---------|
| attendance-verification | ✅ | Single file, sudah responsive |
| verify-face | ✅ | Single file, sudah responsive |
| verify-location | ✅ | Single file, sudah responsive |
| login | ✅ | Single file, sudah responsive |

---

## Prioritas Pengembangan

### High Priority (Sering diakses dari HP)

1. **employee/corrections** - Karyawan perlu ajukan koreksi dari HP
2. **employee/dashboard** - Halaman utama employee, perlu mobile

### Medium Priority (Admin, tapi kadang perlu mobile)

3. **admin/corrections** - Admin approve dari HP

### Low Priority (Desktop-only OK)

- admin/face-recognition - Setup wajah lebih baik dari desktop
- admin/security - Pengaturan keamanan
- admin/services - Monitoring container
- admin/master-data - Data master
- settings/employee-types - Pengaturan

---

## Komponen Mobile Best Practices

### Layout Mobile

```tsx
// Padding lebih kecil
<div className="p-4 pb-24 space-y-4">
  {/* pb-24 untuk clearance bottom nav */}
</div>
```

### Cards Mobile

```tsx
// Compact card untuk mobile
<Card className="p-3">
  <div className="flex items-center gap-3">
    <Icon className="h-5 w-5" />
    <div>
      <p className="font-medium text-sm">{title}</p>
      <p className="text-xs text-muted-foreground">{subtitle}</p>
    </div>
  </div>
</Card>
```

### Dialog/Sheet Mobile

```tsx
// Gunakan Sheet untuk mobile, Dialog untuk desktop
import { Sheet, SheetContent, SheetHeader } from '@/components/ui/sheet';

// Mobile: Sheet dari bawah
<Sheet open={isOpen} onOpenChange={setIsOpen}>
  <SheetContent side="bottom" className="h-[90vh]">
    {content}
  </SheetContent>
</Sheet>
```

### List Mobile

```tsx
// Touch-friendly list items
<div
  className="flex items-center justify-between p-4 active:bg-muted/50"
  onClick={onClick}
>
  <div className="flex-1 min-w-0">
    <p className="font-medium truncate">{title}</p>
    <p className="text-sm text-muted-foreground">{subtitle}</p>
  </div>
  <ChevronRight className="h-5 w-5 text-muted-foreground flex-shrink-0" />
</div>
```

### FAB (Floating Action Button)

```tsx
// FAB untuk aksi utama di mobile
<Button
  className="fixed bottom-20 right-4 h-14 w-14 rounded-full shadow-lg"
  onClick={onAdd}
>
  <Plus className="h-6 w-6" />
</Button>
```

---

## Checklist Sebelum Deploy

- [ ] Test di viewport 375px (iPhone SE)
- [ ] Test di viewport 414px (iPhone Plus)
- [ ] Test di viewport 768px (Tablet)
- [ ] Test di viewport 1024px+ (Desktop)
- [ ] Bottom nav tidak overlap content
- [ ] Touch targets minimal 44x44px
- [ ] Font size minimal 14px untuk body text
- [ ] Loading states ada
- [ ] Error states ada
- [ ] Empty states ada

---

*Last updated: 2024-12-11*
