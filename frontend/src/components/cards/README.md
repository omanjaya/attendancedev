# Card Components

Reusable card components dengan design patterns yang konsisten.

## Design Principles

All card components follow these principles:
- **Hover Effects**: Scale (1.02 hover, 0.99 active) + shadow + border color
- **Icon Animations**: Scale 110% on hover with colored shadows
- **Transitions**: Consistent 300ms duration
- **Mobile First**: Responsive sizing, proper truncation
- **Dark Mode**: Using semantic color tokens
- **Accessibility**: Proper contrast ratios (AAA)

## Components

### 1. ActionCard

Interactive card untuk quick actions.

```tsx
import { ActionCard } from '@/components/cards';
import { Clock, CalendarOff, Users } from 'lucide-react';

<ActionCard
  href="/face-recognition"
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
  onClick={() => console.log('clicked')}
/>
```

**Props:**
- `href` - Link destination
- `icon` - Lucide icon component
- `title` - Main title text
- `description` - Supporting text
- `iconColor` - `'primary' | 'success' | 'warning' | 'destructive' | 'info'`
- `onClick` - Optional click handler
- `className` - Additional classes

### 2. BannerCard

Prominent CTA/notification banner.

```tsx
import { BannerCard } from '@/components/cards';
import { Clock, AlertCircle, ArrowRight } from 'lucide-react';

<BannerCard
  icon={Clock}
  title="Belum Check-in Hari Ini"
  description="Jadwal: 08:00 - 17:00"
  actionLabel="Check-in Sekarang"
  actionHref="/face-recognition"
  variant="primary"
  actionIcon={<ArrowRight className="h-4 w-4" />}
/>

<BannerCard
  icon={AlertCircle}
  title="5 Pengajuan Menunggu"
  description="Cuti yang perlu ditinjau"
  actionLabel="Lihat Pengajuan"
  actionHref="/leave"
  variant="warning"
  buttonVariant="outline"
/>
```

**Props:**
- `icon` - Lucide icon component
- `title` - Main title
- `description` - Supporting text
- `actionLabel` - Button text
- `actionHref` - Button link (optional)
- `onAction` - Button click handler (optional)
- `variant` - `'primary' | 'success' | 'warning' | 'destructive' | 'info'`
- `buttonVariant` - `'default' | 'outline'`
- `actionIcon` - Icon in button
- `className` - Additional classes

### 3. ActivityCard

Card untuk activity/timeline items.

```tsx
import { ActivityCard, ActivityCardList } from '@/components/cards';

<ActivityCardList>
  <ActivityCard
    initials="JD"
    title="John Doe"
    description="Melakukan check-in"
    time="08:15"
    badge={{
      label: "Check In",
      className: "bg-success/10 text-success"
    }}
    onClick={() => navigate('/detail')}
  />
  <ActivityCard
    initials="AS"
    title="Alice Smith"
    description="Mengajukan cuti"
    time="09:30"
    badge={{
      label: "Cuti",
      className: "bg-warning/10 text-warning"
    }}
  />
</ActivityCardList>
```

**Props (ActivityCard):**
- `avatar` - Custom avatar component
- `initials` - Avatar initials (if no avatar)
- `title` - Person/activity name
- `description` - Activity description
- `time` - Time string
- `badge` - Badge object `{ label, variant, className }`
- `onClick` - Click handler
- `className` - Additional classes

**Props (ActivityCardList):**
- `children` - ActivityCard components
- `className` - Additional classes

### 4. InfoCard

General purpose info card with header.

```tsx
import { InfoCard, InfoCardItem } from '@/components/cards';
import { Calendar, Button, Badge } from '@/components/ui';

<InfoCard
  icon={Calendar}
  title="Jadwal Hari Ini"
  action={<Button variant="ghost" size="sm">Lihat Semua</Button>}
>
  <div className="space-y-3">
    <InfoCardItem
      label="Shift Pagi"
      value="08:00 - 17:00"
      badge={<Badge className="bg-success/10 text-success">Aktif</Badge>}
    />
  </div>
</InfoCard>

<InfoCard
  icon={Users}
  title="Statistik Departemen"
>
  <div className="space-y-2">
    <InfoCardItem label="IT" value="15" />
    <InfoCardItem label="HR" value="8" />
    <InfoCardItem label="Finance" value="12" />
  </div>
</InfoCard>
```

**Props (InfoCard):**
- `icon` - Lucide icon component
- `title` - Card title
- `children` - Card content
- `action` - Header action (button, link, etc.)
- `className` - Additional classes
- `contentClassName` - Content area classes

**Props (InfoCardItem):**
- `label` - Item label
- `value` - Item value
- `badge` - Optional badge component
- `className` - Additional classes

## Usage Examples

### Dashboard Quick Actions

```tsx
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
  <ActionCard
    href="/reports"
    icon={FileText}
    title="Laporan"
    description="Generate laporan"
    iconColor="info"
  />
</div>
```

### Notification Banners

```tsx
<div className="space-y-4">
  <BannerCard
    icon={Clock}
    title="Belum Check-in"
    description="Jadwal: 08:00 - 17:00"
    actionLabel="Check-in Sekarang"
    actionHref="/face-recognition"
    variant="primary"
  />

  <BannerCard
    icon={AlertCircle}
    title="5 Pengajuan Pending"
    description="Review pengajuan cuti"
    actionLabel="Lihat Pengajuan"
    actionHref="/leave"
    variant="warning"
    buttonVariant="outline"
  />
</div>
```

### Activity Timeline

```tsx
<InfoCard
  icon={Clock}
  title="Aktivitas Terbaru"
  action={<Button variant="ghost">Lihat Semua</Button>}
>
  <ActivityCardList>
    {activities.map(activity => (
      <ActivityCard
        key={activity.id}
        initials={activity.initials}
        title={activity.name}
        description={activity.description}
        time={formatTime(activity.time)}
        badge={{
          label: activity.type,
          className: getActivityStyle(activity.type)
        }}
        onClick={() => navigate(`/activity/${activity.id}`)}
      />
    ))}
  </ActivityCardList>
</InfoCard>
```

## Color Variants

All components support these color variants:
- `primary` - Blue/Emerald (main brand color)
- `success` - Green (positive actions, completed)
- `warning` - Amber/Yellow (pending, attention needed)
- `destructive` - Red (errors, dangerous actions)
- `info` - Blue (informational)

## Mobile Responsiveness

All components are mobile-first:
- Icons scale based on screen size (`h-12 w-12 sm:h-10 sm:w-10`)
- Text truncates properly (`truncate`, `min-w-0`)
- Buttons don't squish (`flex-shrink-0`)
- Layouts stack on mobile (ActionCard, BannerCard)

## Dark Mode

All components use semantic color tokens:
- `text-foreground` / `text-muted-foreground`
- `bg-card` / `bg-muted`
- `border-border`
- Variant-specific colors auto-adjust

## Accessibility

- Proper contrast ratios (AAA)
- Clickable areas have cursor pointer
- Truncated text prevents layout breaks
- Semantic HTML structure
