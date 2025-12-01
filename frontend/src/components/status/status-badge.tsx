import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import {
  CheckCircle,
  XCircle,
  Clock,
  AlertCircle,
  MinusCircle,
  UserCheck,
  UserX,
  Calendar,
} from 'lucide-react';

// Type for lucide icons
type IconType = React.ComponentType<{ className?: string }>;

// ============================================
// Attendance Status
// ============================================

type AttendanceStatus = 'present' | 'absent' | 'late' | 'leave' | 'permission' | 'wfh';

const attendanceStyles: Record<AttendanceStatus, { label: string; className: string; icon: IconType }> = {
  present: {
    label: 'Hadir',
    className: 'bg-success/10 text-success border-success/20',
    icon: CheckCircle,
  },
  absent: {
    label: 'Tidak Hadir',
    className: 'bg-destructive/10 text-destructive border-destructive/20',
    icon: XCircle,
  },
  late: {
    label: 'Terlambat',
    className: 'bg-warning/10 text-warning border-warning/20',
    icon: Clock,
  },
  leave: {
    label: 'Cuti',
    className: 'bg-info/10 text-info border-info/20',
    icon: Calendar,
  },
  permission: {
    label: 'Izin',
    className: 'bg-primary/10 text-primary border-primary/20',
    icon: AlertCircle,
  },
  wfh: {
    label: 'WFH',
    className: 'bg-purple-500/10 text-purple-500 border-purple-500/20',
    icon: UserCheck,
  },
};

export function AttendanceBadge({
  status,
  showIcon = true,
  className,
}: {
  status: AttendanceStatus;
  showIcon?: boolean;
  className?: string;
}) {
  const style = attendanceStyles[status];
  const Icon = style.icon;

  return (
    <Badge variant="outline" className={cn('font-medium', style.className, className)}>
      {showIcon && <Icon className="h-3 w-3 mr-1" />}
      {style.label}
    </Badge>
  );
}

// ============================================
// Leave Status
// ============================================

type LeaveStatus = 'pending' | 'approved' | 'rejected' | 'cancelled';

const leaveStyles: Record<LeaveStatus, { label: string; className: string; icon: IconType }> = {
  pending: {
    label: 'Menunggu',
    className: 'bg-warning/10 text-warning border-warning/20',
    icon: Clock,
  },
  approved: {
    label: 'Disetujui',
    className: 'bg-success/10 text-success border-success/20',
    icon: CheckCircle,
  },
  rejected: {
    label: 'Ditolak',
    className: 'bg-destructive/10 text-destructive border-destructive/20',
    icon: XCircle,
  },
  cancelled: {
    label: 'Dibatalkan',
    className: 'bg-muted text-muted-foreground border-border',
    icon: MinusCircle,
  },
};

export function LeaveBadge({
  status,
  showIcon = true,
  className,
}: {
  status: LeaveStatus;
  showIcon?: boolean;
  className?: string;
}) {
  const style = leaveStyles[status];
  const Icon = style.icon;

  return (
    <Badge variant="outline" className={cn('font-medium', style.className, className)}>
      {showIcon && <Icon className="h-3 w-3 mr-1" />}
      {style.label}
    </Badge>
  );
}

// ============================================
// Employee Status
// ============================================

type EmployeeStatus = 'active' | 'inactive' | 'suspended';

const employeeStyles: Record<EmployeeStatus, { label: string; className: string; icon: IconType }> = {
  active: {
    label: 'Aktif',
    className: 'bg-success/10 text-success border-success/20',
    icon: UserCheck,
  },
  inactive: {
    label: 'Tidak Aktif',
    className: 'bg-muted text-muted-foreground border-border',
    icon: UserX,
  },
  suspended: {
    label: 'Ditangguhkan',
    className: 'bg-destructive/10 text-destructive border-destructive/20',
    icon: AlertCircle,
  },
};

export function EmployeeBadge({
  status,
  showIcon = true,
  className,
}: {
  status: EmployeeStatus;
  showIcon?: boolean;
  className?: string;
}) {
  const style = employeeStyles[status];
  const Icon = style.icon;

  return (
    <Badge variant="outline" className={cn('font-medium', style.className, className)}>
      {showIcon && <Icon className="h-3 w-3 mr-1" />}
      {style.label}
    </Badge>
  );
}

// ============================================
// Payment Status
// ============================================

type PaymentStatus = 'paid' | 'unpaid' | 'pending' | 'partial';

const paymentStyles: Record<PaymentStatus, { label: string; className: string; icon: IconType }> = {
  paid: {
    label: 'Dibayar',
    className: 'bg-success/10 text-success border-success/20',
    icon: CheckCircle,
  },
  unpaid: {
    label: 'Belum Dibayar',
    className: 'bg-destructive/10 text-destructive border-destructive/20',
    icon: XCircle,
  },
  pending: {
    label: 'Menunggu',
    className: 'bg-warning/10 text-warning border-warning/20',
    icon: Clock,
  },
  partial: {
    label: 'Sebagian',
    className: 'bg-info/10 text-info border-info/20',
    icon: AlertCircle,
  },
};

export function PaymentBadge({
  status,
  showIcon = true,
  className,
}: {
  status: PaymentStatus;
  showIcon?: boolean;
  className?: string;
}) {
  const style = paymentStyles[status];
  const Icon = style.icon;

  return (
    <Badge variant="outline" className={cn('font-medium', style.className, className)}>
      {showIcon && <Icon className="h-3 w-3 mr-1" />}
      {style.label}
    </Badge>
  );
}

// ============================================
// Generic Status Badge
// ============================================

type StatusVariant = 'success' | 'warning' | 'error' | 'info' | 'default';

const statusStyles: Record<StatusVariant, string> = {
  success: 'bg-success/10 text-success border-success/20',
  warning: 'bg-warning/10 text-warning border-warning/20',
  error: 'bg-destructive/10 text-destructive border-destructive/20',
  info: 'bg-info/10 text-info border-info/20',
  default: 'bg-muted text-muted-foreground border-border',
};

export function StatusBadge({
  label,
  variant = 'default',
  icon,
  className,
}: {
  label: string;
  variant?: StatusVariant;
  icon?: IconType;
  className?: string;
}) {
  const Icon = icon;

  return (
    <Badge variant="outline" className={cn('font-medium', statusStyles[variant], className)}>
      {Icon && <Icon className="h-3 w-3 mr-1" />}
      {label}
    </Badge>
  );
}

// ============================================
// Indicator Dot (for small spaces)
// ============================================

export function StatusIndicator({
  variant = 'default',
  pulse = false,
  className,
}: {
  variant?: StatusVariant;
  pulse?: boolean;
  className?: string;
}) {
  const colors = {
    success: 'bg-success',
    warning: 'bg-warning',
    error: 'bg-destructive',
    info: 'bg-info',
    default: 'bg-muted-foreground',
  };

  return (
    <span className={cn('inline-flex items-center gap-2', className)}>
      <span className="relative flex h-2 w-2">
        {pulse && (
          <span
            className={cn(
              'absolute inline-flex h-full w-full rounded-full opacity-75 animate-ping',
              colors[variant]
            )}
          />
        )}
        <span className={cn('relative inline-flex h-2 w-2 rounded-full', colors[variant])} />
      </span>
    </span>
  );
}
