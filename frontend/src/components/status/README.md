# Status Badge Components

Comprehensive status badge system untuk berbagai entitas dalam aplikasi.

## Design Principles

- **Domain-Specific**: Separate badge components untuk setiap domain (Attendance, Leave, Employee, Payment)
- **Consistent Styling**: Outline variant dengan icon dan colored backgrounds
- **Icon Support**: Optional icons dengan size yang konsisten (h-3 w-3)
- **Color Coding**: Semantic colors untuk setiap status
- **Dark Mode**: Using semantic color tokens
- **Accessibility**: AAA contrast ratios

## Components

### 1. AttendanceBadge

Badge untuk status kehadiran karyawan.

```tsx
import { AttendanceBadge } from '@/components/status';

<AttendanceBadge status="present" />
<AttendanceBadge status="absent" showIcon={false} />
<AttendanceBadge status="late" className="text-xs" />
```

**Status Types:**
- `present` - Hadir (green/success)
- `absent` - Tidak Hadir (red/destructive)
- `late` - Terlambat (amber/warning)
- `leave` - Cuti (blue/info)
- `permission` - Izin (primary)
- `wfh` - Work From Home (purple)

**Props:**
- `status` - Attendance status type
- `showIcon` - Show icon (default: `true`)
- `className` - Additional classes

### 2. LeaveBadge

Badge untuk status pengajuan cuti.

```tsx
import { LeaveBadge } from '@/components/status';

<LeaveBadge status="pending" />
<LeaveBadge status="approved" />
<LeaveBadge status="rejected" showIcon={false} />
<LeaveBadge status="cancelled" />
```

**Status Types:**
- `pending` - Menunggu (amber/warning with Clock icon)
- `approved` - Disetujui (green/success with CheckCircle)
- `rejected` - Ditolak (red/destructive with XCircle)
- `cancelled` - Dibatalkan (muted with MinusCircle)

**Props:**
- `status` - Leave status type
- `showIcon` - Show icon (default: `true`)
- `className` - Additional classes

### 3. EmployeeBadge

Badge untuk status karyawan.

```tsx
import { EmployeeBadge } from '@/components/status';

<EmployeeBadge status="active" />
<EmployeeBadge status="inactive" />
<EmployeeBadge status="suspended" showIcon={false} />
```

**Status Types:**
- `active` - Aktif (green/success with UserCheck)
- `inactive` - Tidak Aktif (muted with UserX)
- `suspended` - Ditangguhkan (red/destructive with AlertCircle)

**Props:**
- `status` - Employee status type
- `showIcon` - Show icon (default: `true`)
- `className` - Additional classes

### 4. PaymentBadge

Badge untuk status pembayaran/payroll.

```tsx
import { PaymentBadge } from '@/components/status';

<PaymentBadge status="paid" />
<PaymentBadge status="unpaid" />
<PaymentBadge status="pending" showIcon={false} />
<PaymentBadge status="partial" />
```

**Status Types:**
- `paid` - Dibayar (green/success with CheckCircle)
- `unpaid` - Belum Dibayar (red/destructive with XCircle)
- `pending` - Menunggu (amber/warning with Clock)
- `partial` - Sebagian (blue/info with AlertCircle)

**Props:**
- `status` - Payment status type
- `showIcon` - Show icon (default: `true`)
- `className` - Additional classes

### 5. StatusBadge

Generic status badge untuk custom use cases.

```tsx
import { StatusBadge } from '@/components/status';
import { Star } from 'lucide-react';

<StatusBadge label="Baru" variant="success" />
<StatusBadge label="Urgent" variant="error" icon={Star} />
<StatusBadge label="Draft" variant="default" />
```

**Variants:**
- `success` - Green
- `warning` - Amber/Yellow
- `error` - Red
- `info` - Blue
- `default` - Muted gray

**Props:**
- `label` - Badge text
- `variant` - Color variant (default: `'default'`)
- `icon` - Optional Lucide icon component
- `className` - Additional classes

### 6. StatusIndicator

Small colored dot untuk tight spaces (avatars, compact lists).

```tsx
import { StatusIndicator } from '@/components/status';

<StatusIndicator variant="success" pulse />
<StatusIndicator variant="warning" />
<StatusIndicator variant="error" pulse={false} />

// With text
<span className="flex items-center gap-2">
  <StatusIndicator variant="success" pulse />
  Online
</span>
```

**Variants:**
- `success` - Green dot
- `warning` - Amber dot
- `error` - Red dot
- `info` - Blue dot
- `default` - Gray dot

**Props:**
- `variant` - Color variant (default: `'default'`)
- `pulse` - Animated pulse effect (default: `false`)
- `className` - Additional classes

## Usage Examples

### Attendance Table

```tsx
function AttendanceTable({ data }: { data: AttendanceRecord[] }) {
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

### Leave Request Card

```tsx
function LeaveRequestCard({ request }: { request: LeaveRequest }) {
  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle>{request.employeeName}</CardTitle>
          <LeaveBadge status={request.status} />
        </div>
      </CardHeader>
      <CardContent>
        <p className="text-sm text-muted-foreground">
          {formatDate(request.startDate)} - {formatDate(request.endDate)}
        </p>
        <p className="text-sm mt-2">{request.reason}</p>
      </CardContent>
    </Card>
  );
}
```

### Employee List with Status

```tsx
function EmployeeList({ employees }: { employees: Employee[] }) {
  return (
    <div className="space-y-2">
      {employees.map(employee => (
        <div key={employee.id} className="flex items-center justify-between p-4 border rounded-lg">
          <div className="flex items-center gap-3">
            <UserAvatar
              name={employee.name}
              src={employee.avatar}
              status={employee.status === 'active' ? 'success' : 'error'}
              showStatus
            />
            <div>
              <p className="font-medium">{employee.name}</p>
              <p className="text-sm text-muted-foreground">{employee.position}</p>
            </div>
          </div>
          <EmployeeBadge status={employee.status} />
        </div>
      ))}
    </div>
  );
}
```

### Payroll Dashboard

```tsx
function PayrollDashboard({ payrolls }: { payrolls: Payroll[] }) {
  return (
    <div className="grid gap-4">
      {payrolls.map(payroll => (
        <Card key={payroll.id}>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle>{payroll.month}</CardTitle>
                <p className="text-sm text-muted-foreground">
                  {payroll.employeeCount} karyawan
                </p>
              </div>
              <PaymentBadge status={payroll.status} />
            </div>
          </CardHeader>
          <CardContent>
            <div className="flex justify-between items-center">
              <span className="text-sm text-muted-foreground">Total</span>
              <span className="text-lg font-semibold">
                {formatCurrency(payroll.totalAmount)}
              </span>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
```

### Status Indicator in Avatar

```tsx
function UserListItem({ user }: { user: User }) {
  return (
    <div className="flex items-center gap-3">
      <div className="relative">
        <UserAvatar name={user.name} src={user.avatar} size="md" />
        <div className="absolute -bottom-1 -right-1">
          <StatusIndicator
            variant={user.isOnline ? 'success' : 'default'}
            pulse={user.isOnline}
          />
        </div>
      </div>
      <div>
        <p className="font-medium">{user.name}</p>
        <p className="text-sm text-muted-foreground">
          {user.isOnline ? 'Online' : 'Offline'}
        </p>
      </div>
    </div>
  );
}
```

### Generic Status Badge

```tsx
function DocumentCard({ doc }: { doc: Document }) {
  const getStatusVariant = (status: string) => {
    switch (status) {
      case 'published': return 'success';
      case 'draft': return 'default';
      case 'archived': return 'warning';
      default: return 'default';
    }
  };

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle>{doc.title}</CardTitle>
          <StatusBadge
            label={doc.status}
            variant={getStatusVariant(doc.status)}
          />
        </div>
      </CardHeader>
    </Card>
  );
}
```

### Compact List with Indicators

```tsx
function NotificationList({ notifications }: { notifications: Notification[] }) {
  return (
    <div className="space-y-1">
      {notifications.map(notification => (
        <div key={notification.id} className="flex items-center gap-2 p-2 hover:bg-muted rounded">
          <StatusIndicator
            variant={notification.read ? 'default' : 'info'}
            pulse={!notification.read}
          />
          <div className="flex-1 min-w-0">
            <p className="text-sm truncate">{notification.title}</p>
            <p className="text-xs text-muted-foreground">{notification.time}</p>
          </div>
        </div>
      ))}
    </div>
  );
}
```

## Color Variants

### Success (Green)
Used for: Present, Active, Approved, Paid
- Light: `bg-success/10 text-success border-success/20`
- Dark: Auto-adjusted with semantic tokens

### Warning (Amber)
Used for: Late, Pending
- Light: `bg-warning/10 text-warning border-warning/20`
- Dark: Auto-adjusted

### Destructive (Red)
Used for: Absent, Rejected, Unpaid, Suspended
- Light: `bg-destructive/10 text-destructive border-destructive/20`
- Dark: Auto-adjusted

### Info (Blue)
Used for: Leave, Partial
- Light: `bg-info/10 text-info border-info/20`
- Dark: Auto-adjusted

### Primary (Emerald)
Used for: Permission
- Light: `bg-primary/10 text-primary border-primary/20`
- Dark: Auto-adjusted

### Default (Muted)
Used for: Cancelled, Inactive
- Light: `bg-muted text-muted-foreground border-border`
- Dark: Auto-adjusted

## Icons

All icons use Lucide React library:
- `CheckCircle` - Success/approval states
- `XCircle` - Rejection/error states
- `Clock` - Pending/late states
- `AlertCircle` - Warning/attention states
- `MinusCircle` - Cancelled states
- `UserCheck` / `UserX` - Employee states
- `Calendar` - Leave states

## Accessibility

- **Contrast**: All badges meet AAA contrast ratios
- **Icons**: Provide visual reinforcement of status
- **Labels**: Indonesian text labels for clarity
- **Semantic colors**: Consistent color meaning across app

## Mobile Responsiveness

- Icons and text scale properly on all screen sizes
- `text-xs` recommended for mobile views
- Can be used in compact mobile card layouts
- Touch-friendly sizing (h-6 minimum)
