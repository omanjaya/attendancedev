# Component Library

Comprehensive reusable component library untuk Attendance System.

## Overview

Library ini mengikuti design principles yang konsisten:
- **Mobile First**: Responsive di semua screen sizes
- **Dark Mode**: Semantic color tokens untuk light/dark theme
- **Glassmorphism**: Backdrop blur dengan gradient backgrounds
- **Micro-interactions**: 300ms transitions, scale animations
- **Accessibility**: AAA contrast ratios
- **Type Safety**: Full TypeScript support

## Directory Structure

```
components/
├── cards/              # Interactive card components
│   ├── action-card.tsx
│   ├── banner-card.tsx
│   ├── activity-card.tsx
│   ├── info-card.tsx
│   ├── index.ts
│   └── README.md
│
├── states/             # Empty & loading states
│   ├── empty-state.tsx
│   ├── loading-state.tsx
│   ├── index.ts
│   └── README.md
│
├── status/             # Status badge system
│   ├── status-badge.tsx
│   ├── index.ts
│   └── README.md
│
└── ui/                 # Enhanced UI components
    ├── user-avatar.tsx
    ├── card.tsx
    ├── button.tsx
    └── index.ts
```

## Component Categories

### 1. Cards (`/components/cards/`)

Interactive card components untuk quick actions, banners, activities, dan info.

**Components:**
- `ActionCard` - Quick action cards dengan icon dan arrow
- `BannerCard` - CTA/notification banners dengan color variants
- `ActivityCard` - Timeline/activity items dengan avatar
- `InfoCard` - General purpose info card dengan header
- `InfoCardItem` - List item untuk InfoCard

**Import:**
```tsx
import { ActionCard, BannerCard, ActivityCard, InfoCard } from '@/components/cards';
```

**Documentation:** [cards/README.md](./cards/README.md)

### 2. States (`/components/states/`)

Empty states dan loading states dengan skeleton loaders.

**Components:**
- `EmptyState` - Full empty state dengan icon, title, description, CTA
- `EmptyStateInline` - Compact inline empty state
- `LoadingState` - Centered loading spinner
- `LoadingOverlay` - Full-screen loading overlay
- `Skeleton` - Base skeleton component
- `CardSkeleton` - Card loading placeholder
- `TableSkeleton` - Table loading placeholder
- `ListSkeleton` - List loading placeholder
- `StatSkeleton` - Stat card loading placeholder

**Import:**
```tsx
import {
  EmptyState,
  LoadingState,
  CardSkeleton,
  TableSkeleton
} from '@/components/states';
```

**Documentation:** [states/README.md](./states/README.md)

### 3. Status (`/components/status/`)

Comprehensive status badge system untuk berbagai entitas.

**Components:**
- `AttendanceBadge` - Attendance status (present, absent, late, leave, permission, wfh)
- `LeaveBadge` - Leave request status (pending, approved, rejected, cancelled)
- `EmployeeBadge` - Employee status (active, inactive, suspended)
- `PaymentBadge` - Payment status (paid, unpaid, pending, partial)
- `StatusBadge` - Generic status badge
- `StatusIndicator` - Small colored dot dengan pulse animation

**Import:**
```tsx
import {
  AttendanceBadge,
  LeaveBadge,
  EmployeeBadge,
  PaymentBadge,
  StatusIndicator
} from '@/components/status';
```

**Documentation:** [status/README.md](./status/README.md)

### 4. UI (`/components/ui/`)

Enhanced UI components based on shadcn/ui.

**Components:**
- `UserAvatar` - Avatar dengan status indicator dan multiple sizes
- `AvatarGroup` - Stack multiple avatars
- All shadcn/ui base components (Button, Card, Badge, etc.)

**Import:**
```tsx
import { UserAvatar, AvatarGroup } from '@/components/ui';
```

## Quick Start

### Example: Dashboard Page

```tsx
import { ActionCard, BannerCard, ActivityCard, InfoCard } from '@/components/cards';
import { AttendanceBadge, StatusIndicator } from '@/components/status';
import { EmptyState, LoadingState, CardSkeleton } from '@/components/states';
import { UserAvatar } from '@/components/ui';
import { Clock, CalendarOff, Users, FileText } from 'lucide-react';

function Dashboard() {
  const { data, isLoading } = useAttendance();

  return (
    <div className="space-y-6">
      {/* Quick Actions */}
      <div className="grid gap-2">
        <ActionCard
          href="/attendance"
          icon={Clock}
          title="Absen Sekarang"
          description="Check-in / Check-out"
          iconColor="primary"
        />
        <ActionCard
          href="/leave"
          icon={CalendarOff}
          title="Ajukan Cuti"
          description="Buat pengajuan baru"
          iconColor="warning"
        />
      </div>

      {/* Banner */}
      <BannerCard
        icon={Clock}
        title="Belum Check-in Hari Ini"
        description="Jadwal: 08:00 - 17:00"
        actionLabel="Check-in Sekarang"
        actionHref="/face-recognition"
        variant="primary"
      />

      {/* Activity Timeline */}
      <InfoCard icon={Users} title="Aktivitas Terbaru">
        {isLoading ? (
          <CardSkeleton count={3} />
        ) : data.length === 0 ? (
          <EmptyState
            icon={Clock}
            title="Tidak ada aktivitas"
            description="Belum ada check-in hari ini"
            size="sm"
          />
        ) : (
          <ActivityCardList>
            {data.map(activity => (
              <ActivityCard
                key={activity.id}
                initials={activity.initials}
                title={activity.name}
                description={activity.description}
                time={formatTime(activity.time)}
                badge={{
                  label: activity.type,
                  className: 'bg-success/10 text-success'
                }}
              />
            ))}
          </ActivityCardList>
        )}
      </InfoCard>
    </div>
  );
}
```

### Example: Data Table with States

```tsx
import { EmptyState, TableSkeleton } from '@/components/states';
import { AttendanceBadge } from '@/components/status';
import { Users } from 'lucide-react';

function AttendanceTable() {
  const { data, isLoading } = useQuery({
    queryKey: ['attendance'],
    queryFn: fetchAttendance
  });

  if (isLoading) {
    return <TableSkeleton rows={10} columns={5} />;
  }

  if (!data || data.length === 0) {
    return (
      <EmptyState
        icon={Users}
        title="Tidak ada data kehadiran"
        description="Belum ada check-in hari ini"
        action={{
          label: "Refresh",
          onClick: () => refetch()
        }}
        size="md"
      />
    );
  }

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Nama</TableHead>
          <TableHead>Waktu</TableHead>
          <TableHead>Status</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {data.map(record => (
          <TableRow key={record.id}>
            <TableCell>{record.name}</TableCell>
            <TableCell>{formatTime(record.time)}</TableCell>
            <TableCell>
              <AttendanceBadge status={record.status} />
            </TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}
```

## Design Tokens

All components use consistent design tokens from `/src/index.css`:

### Colors
- `--primary` - Emerald brand color
- `--success` - Green for positive states
- `--warning` - Amber for attention
- `--destructive` - Red for errors
- `--info` - Blue for information

### Text Hierarchy
- `--text-primary` - Headings (OKLCH 0.15)
- `--text-secondary` - Body text (OKLCH 0.35)
- `--text-tertiary` - Captions (OKLCH 0.5)
- `--text-quaternary` - Disabled (OKLCH 0.6)

### Animations
- **Transition Duration**: 300ms
- **Hover Scale**: 1.02
- **Active Scale**: 0.99
- **Icon Hover**: scale 110%

### Shadows
- **Default**: `shadow-sm`
- **Hover**: `shadow-md`
- **Icon**: `shadow-lg shadow-primary/30`

## Best Practices

### 1. Use Semantic Components

```tsx
// Good - Semantic
<AttendanceBadge status="present" />

// Bad - Generic
<Badge className="bg-green-500">Hadir</Badge>
```

### 2. Consistent Loading States

```tsx
// Good - Appropriate skeleton
if (isLoading) return <TableSkeleton rows={10} columns={5} />;

// Bad - Generic loading
if (isLoading) return <div>Loading...</div>;
```

### 3. Empty States with Actions

```tsx
// Good - Actionable empty state
<EmptyState
  icon={Users}
  title="Tidak ada karyawan"
  action={{ label: "Tambah Karyawan", onClick: openModal }}
/>

// Bad - Passive message
<p>No employees found</p>
```

### 4. Mobile-First Sizing

```tsx
// Good - Responsive
<div className="grid gap-2 sm:gap-4">
  <ActionCard size="sm" />
</div>

// Bad - Desktop only
<div className="grid gap-6">
  <ActionCard />
</div>
```

## Migration Guide

### From Old Cards to New ActionCard

```tsx
// Before
<a href="/attendance" className="flex items-center gap-3 p-4 border rounded-lg">
  <Clock className="h-5 w-5" />
  <div>
    <p>Absen Sekarang</p>
    <p className="text-sm">Check-in / Check-out</p>
  </div>
</a>

// After
<ActionCard
  href="/attendance"
  icon={Clock}
  title="Absen Sekarang"
  description="Check-in / Check-out"
  iconColor="primary"
/>
```

### From Custom Badges to StatusBadge

```tsx
// Before
<Badge className="bg-green-500/10 text-green-500">
  <CheckCircle className="h-3 w-3 mr-1" />
  Hadir
</Badge>

// After
<AttendanceBadge status="present" />
```

### From Custom Loading to Skeletons

```tsx
// Before
{isLoading && <div className="animate-pulse">...</div>}

// After
{isLoading && <CardSkeleton count={3} />}
```

## Color Variants Reference

All card and badge components support these variants:

- `primary` - Emerald/Green (brand color)
- `success` - Green (positive actions)
- `warning` - Amber (attention needed)
- `destructive` - Red (errors, dangerous)
- `info` - Blue (informational)
- `default` - Muted gray (neutral)

## File Size

All components are tree-shakeable:
- Import only what you need
- No runtime dependencies beyond React & Tailwind
- TypeScript types included

## Contributing

When adding new components:
1. Follow existing design patterns
2. Use semantic color tokens
3. Support dark mode
4. Include TypeScript types
5. Add documentation with examples
6. Test on mobile viewports
7. Ensure AAA contrast ratios

## Support

For issues or questions:
- Check component README files for detailed docs
- Review examples in this file
- Refer to design tokens in `/src/index.css`
