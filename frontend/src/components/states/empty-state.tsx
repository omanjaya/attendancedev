
// Type for lucide icon components
type IconType = React.ComponentType<{ className?: string }>;

// Removed LucideIcon import from 'lucide-react';
import { Button } from '@/components/ui/button';

import { cn } from '@/lib/utils';


interface EmptyStateProps {
  icon?: IconType;
  title: string;
  description?: string;
  action?: {
    label: string;
    onClick?: () => void;
    href?: string;
  };
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

const sizeStyles = {
  sm: {
    container: 'py-6',
    icon: 'h-8 w-8 mb-2',
    iconBg: 'p-3',
    title: 'text-sm font-medium',
    description: 'text-xs',
  },
  md: {
    container: 'py-12',
    icon: 'h-10 w-10 mb-3',
    iconBg: 'p-4',
    title: 'text-base font-semibold',
    description: 'text-sm',
  },
  lg: {
    container: 'py-16',
    icon: 'h-12 w-12 mb-4',
    iconBg: 'p-5',
    title: 'text-lg font-semibold',
    description: 'text-base',
  },
};

/**
 * EmptyState - Displays when no data is available
 *
 * Features:
 * - Icon with subtle background
 * - Optional CTA button
 * - 3 size variants
 * - Centered layout
 * - Dark mode compatible
 *
 * @example
 * <EmptyState
 *   icon={FileText}
 *   title="Tidak ada laporan"
 *   description="Belum ada laporan yang dibuat"
 *   action={{
 *     label: "Buat Laporan",
 *     href: "/reports/create"
 *   }}
 *   size="md"
 * />
 */
export function EmptyState({
  icon: Icon,
  title,
  description,
  action,
  size = 'md',
  className,
}: EmptyStateProps) {
  const styles = sizeStyles[size];

  return (
    <div className={cn('flex flex-col items-center justify-center text-center', styles.container, className)}>
      <div className={cn('rounded-full bg-muted/50 mb-4 transition-all duration-300 hover:bg-muted', styles.iconBg)}>
        <Icon className={cn('text-muted-foreground', styles.icon)} />
      </div>
      <h3 className={cn('text-foreground mb-1', styles.title)}>{title}</h3>
      {description && (
        <p className={cn('text-muted-foreground max-w-sm', styles.description)}>
          {description}
        </p>
      )}
      {action && (
        <Button
          variant="outline"
          asChild={!!action.href}
          onClick={action.onClick}
          className="mt-4"
        >
          {action.href ? <a href={action.href}>{action.label}</a> : action.label}
        </Button>
      )}
    </div>
  );
}

/**
 * EmptyStateInline - Compact empty state for lists/tables
 *
 * @example
 * <EmptyStateInline
 *   message="Tidak ada data kehadiran"
 * />
 */
export function EmptyStateInline({
  message,
  className,
}: {
  message: string;
  className?: string;
}) {
  return (
    <div className={cn('p-8 text-center', className)}>
      <p className="text-sm text-muted-foreground">{message}</p>
    </div>
  );
}
