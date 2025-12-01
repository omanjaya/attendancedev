# State Components

Reusable state components untuk empty states dan loading states.

## Design Principles

- **Consistent Sizing**: 3 size variants (sm, md, lg) untuk semua components
- **Smooth Animations**: 300ms transitions dengan animate-pulse untuk loading
- **Responsive**: Mobile-first dengan proper text truncation
- **Dark Mode**: Semantic color tokens
- **Accessibility**: AAA contrast ratios

## Components

### 1. EmptyState

Displays when no data is available with optional CTA button.

```tsx
import { EmptyState } from '@/components/states';
import { FileText, Users, Calendar } from 'lucide-react';

<EmptyState
  icon={FileText}
  title="Tidak ada laporan"
  description="Belum ada laporan yang dibuat bulan ini"
  action={{
    label: "Buat Laporan",
    href: "/reports/create"
  }}
  size="md"
/>

<EmptyState
  icon={Users}
  title="Tidak ada karyawan"
  description="Mulai dengan menambahkan karyawan pertama"
  action={{
    label: "Tambah Karyawan",
    onClick: () => openModal()
  }}
  size="lg"
/>
```

**Props:**
- `icon` - Lucide icon component
- `title` - Main heading text
- `description` - Optional supporting text
- `action` - Optional CTA button `{ label, onClick?, href? }`
- `size` - `'sm' | 'md' | 'lg'` (default: `'md'`)
- `className` - Additional classes

**Size Variants:**
- `sm` - Compact (py-6, h-8 icon) - for inline/modal use
- `md` - Default (py-12, h-10 icon) - for main content areas
- `lg` - Large (py-16, h-12 icon) - for full-page empty states

### 2. EmptyStateInline

Compact empty state for lists/tables without icon.

```tsx
import { EmptyStateInline } from '@/components/states';

<EmptyStateInline message="Tidak ada data kehadiran hari ini" />

<table>
  <tbody>
    {data.length === 0 ? (
      <tr>
        <td colSpan={5}>
          <EmptyStateInline message="Tidak ada hasil ditemukan" />
        </td>
      </tr>
    ) : (
      // table rows
    )}
  </tbody>
</table>
```

**Props:**
- `message` - Text to display
- `className` - Additional classes

### 3. LoadingState

Centered loading spinner with optional message.

```tsx
import { LoadingState } from '@/components/states';

<LoadingState message="Memuat data..." size="md" />

<LoadingState size="lg" />

<LoadingState message="Menyimpan..." size="sm" />
```

**Props:**
- `message` - Optional loading text
- `size` - `'sm' | 'md' | 'lg'` (default: `'md'`)
- `className` - Additional classes

**Size Variants:**
- `sm` - h-6 spinner, text-xs
- `md` - h-8 spinner, text-sm
- `lg` - h-10 spinner, text-base

### 4. LoadingOverlay

Full-screen loading overlay with backdrop blur.

```tsx
import { LoadingOverlay } from '@/components/states';

{isSubmitting && <LoadingOverlay message="Menyimpan data..." />}

{isLoading && <LoadingOverlay />}
```

**Props:**
- `message` - Optional loading text

**Features:**
- Fixed positioning (z-50)
- Backdrop blur effect
- Semi-transparent background
- Centered spinner

### 5. Skeleton Components

Loading placeholders with animate-pulse effect.

#### Base Skeleton

```tsx
import { Skeleton } from '@/components/states';

<Skeleton className="h-4 w-3/4" />
<Skeleton className="h-12 w-12 rounded-full" />
```

#### CardSkeleton

```tsx
import { CardSkeleton } from '@/components/states';

<CardSkeleton count={3} />
```

**Props:**
- `count` - Number of skeleton cards (default: 1)

#### TableSkeleton

```tsx
import { TableSkeleton } from '@/components/states';

<TableSkeleton rows={5} columns={4} />
```

**Props:**
- `rows` - Number of skeleton rows (default: 5)
- `columns` - Number of columns (default: 4)

#### ListSkeleton

```tsx
import { ListSkeleton } from '@/components/states';

<ListSkeleton count={5} />
```

**Props:**
- `count` - Number of skeleton items (default: 5)

#### StatSkeleton

```tsx
import { StatSkeleton } from '@/components/states';

<StatSkeleton count={4} />
```

**Props:**
- `count` - Number of stat cards (default: 4)

## Usage Examples

### Conditional Loading

```tsx
function AttendanceList() {
  const { data, isLoading } = useQuery({ queryKey: ['attendance'], queryFn: fetchAttendance });

  if (isLoading) {
    return <ListSkeleton count={5} />;
  }

  if (!data || data.length === 0) {
    return (
      <EmptyState
        icon={Clock}
        title="Tidak ada data kehadiran"
        description="Belum ada check-in hari ini"
        size="md"
      />
    );
  }

  return (
    <div>
      {data.map(item => (
        <AttendanceItem key={item.id} data={item} />
      ))}
    </div>
  );
}
```

### Full-page Loading

```tsx
function ReportPage() {
  const [isGenerating, setIsGenerating] = useState(false);

  async function generateReport() {
    setIsGenerating(true);
    await api.generateReport();
    setIsGenerating(false);
  }

  return (
    <>
      {isGenerating && <LoadingOverlay message="Membuat laporan..." />}

      <Button onClick={generateReport}>Generate Report</Button>
    </>
  );
}
```

### Table Loading States

```tsx
function EmployeeTable() {
  const { data, isLoading } = useEmployees();

  return (
    <Card>
      <CardHeader>
        <CardTitle>Daftar Karyawan</CardTitle>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <TableSkeleton rows={10} columns={5} />
        ) : data.length === 0 ? (
          <EmptyState
            icon={Users}
            title="Tidak ada karyawan"
            description="Mulai dengan menambahkan karyawan"
            action={{
              label: "Tambah Karyawan",
              onClick: openAddModal
            }}
            size="sm"
          />
        ) : (
          <DataTable data={data} />
        )}
      </CardContent>
    </Card>
  );
}
```

### Dashboard Stats Loading

```tsx
function DashboardStats() {
  const { data, isLoading } = useStats();

  if (isLoading) {
    return <StatSkeleton count={4} />;
  }

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      {data.map(stat => (
        <StatCard key={stat.id} {...stat} />
      ))}
    </div>
  );
}
```

### Inline List Empty State

```tsx
function ActivityList({ activities }: { activities: Activity[] }) {
  if (activities.length === 0) {
    return <EmptyStateInline message="Tidak ada aktivitas terbaru" />;
  }

  return (
    <div className="space-y-2">
      {activities.map(activity => (
        <ActivityCard key={activity.id} {...activity} />
      ))}
    </div>
  );
}
```

## Mobile Responsiveness

All components are mobile-first:
- **EmptyState**: Icon sizes scale down on mobile (`h-12 sm:h-10`)
- **LoadingState**: Compact padding on mobile (`py-6 sm:py-12`)
- **Skeletons**: Responsive grid layouts with breakpoints

## Dark Mode

All components use semantic color tokens:
- `text-foreground` / `text-muted-foreground`
- `bg-card` / `bg-muted`
- `border-border`
- Colors auto-adjust based on theme

## Animation Details

- **Loading spinner**: `animate-spin` with `text-primary`
- **Skeleton pulse**: `animate-pulse` with `bg-muted`
- **Empty state icon**: Hover effect with `hover:bg-muted` transition
- **Consistent timing**: All transitions use 300ms duration
