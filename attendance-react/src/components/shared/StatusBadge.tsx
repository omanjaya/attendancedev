import { cn } from '@/lib/utils';

type StatusVariant = 'success' | 'warning' | 'destructive' | 'info' | 'default' | 'outline';

interface StatusBadgeProps {
  /** Status variant */
  variant?: StatusVariant;
  /** Badge content */
  children: React.ReactNode;
  /** Additional class names */
  className?: string;
  /** Optional icon */
  icon?: React.ReactNode;
}

const variantStyles: Record<StatusVariant, string> = {
  success: 'bg-success/10 text-success border-success/20',
  warning: 'bg-warning/10 text-warning border-warning/20',
  destructive: 'bg-destructive/10 text-destructive border-destructive/20',
  info: 'bg-info/10 text-info border-info/20',
  default: 'bg-muted text-muted-foreground border-border',
  outline: 'bg-transparent text-foreground border-border',
};

const StatusBadge = ({
  variant = 'default',
  children,
  className,
  icon,
}: StatusBadgeProps) => {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-xs font-medium',
        variantStyles[variant],
        className
      )}
    >
      {icon}
      {children}
    </span>
  );
};

// Pre-configured status badges for common use cases
const AttendanceStatusBadge = ({ status }: { status: string }) => {
  const config: Record<string, { variant: StatusVariant; label: string }> = {
    present: { variant: 'success', label: 'Hadir' },
    late: { variant: 'warning', label: 'Terlambat' },
    absent: { variant: 'destructive', label: 'Tidak Hadir' },
    leave: { variant: 'info', label: 'Cuti' },
    half_day: { variant: 'default', label: 'Setengah Hari' },
  };

  const { variant, label } = config[status] || { variant: 'default', label: status };
  return <StatusBadge variant={variant}>{label}</StatusBadge>;
};

const LeaveStatusBadge = ({ status }: { status: string }) => {
  const config: Record<string, { variant: StatusVariant; label: string }> = {
    pending: { variant: 'warning', label: 'Menunggu' },
    approved: { variant: 'success', label: 'Disetujui' },
    rejected: { variant: 'destructive', label: 'Ditolak' },
    cancelled: { variant: 'default', label: 'Dibatalkan' },
  };

  const { variant, label } = config[status] || { variant: 'default', label: status };
  return <StatusBadge variant={variant}>{label}</StatusBadge>;
};

const EmployeeStatusBadge = ({ status }: { status: string }) => {
  const config: Record<string, { variant: StatusVariant; label: string }> = {
    active: { variant: 'success', label: 'Aktif' },
    inactive: { variant: 'default', label: 'Tidak Aktif' },
    on_leave: { variant: 'info', label: 'Cuti' },
    terminated: { variant: 'destructive', label: 'Diberhentikan' },
  };

  const { variant, label } = config[status] || { variant: 'default', label: status };
  return <StatusBadge variant={variant}>{label}</StatusBadge>;
};

const PayrollStatusBadge = ({ status }: { status: string }) => {
  const config: Record<string, { variant: StatusVariant; label: string }> = {
    draft: { variant: 'default', label: 'Draft' },
    pending: { variant: 'warning', label: 'Menunggu' },
    processed: { variant: 'info', label: 'Diproses' },
    paid: { variant: 'success', label: 'Dibayar' },
    cancelled: { variant: 'destructive', label: 'Dibatalkan' },
  };

  const { variant, label } = config[status] || { variant: 'default', label: status };
  return <StatusBadge variant={variant}>{label}</StatusBadge>;
};

export {
  StatusBadge,
  AttendanceStatusBadge,
  LeaveStatusBadge,
  EmployeeStatusBadge,
  PayrollStatusBadge,
  type StatusBadgeProps,
  type StatusVariant,
};
