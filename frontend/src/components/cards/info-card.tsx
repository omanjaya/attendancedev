
// Type for lucide icon components
type IconType = React.ComponentType<{ className?: string }>;

// Removed LucideIcon import from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

import { cn } from '@/lib/utils';

import type { ReactNode } from 'react';


interface InfoCardProps {
  icon?: IconType;
  title: string;
  children: ReactNode;
  action?: ReactNode;
  className?: string;
  contentClassName?: string;
}

/**
 * InfoCard - General purpose info card with header
 *
 * Features:
 * - Optional icon in header
 * - Action slot in header (button, link, etc.)
 * - Flexible content area
 * - Consistent styling with hover effects
 *
 * @example
 * <InfoCard
 *   icon={Calendar}
 *   title="Jadwal Hari Ini"
 *   action={<Button variant="ghost" size="sm">Lihat Semua</Button>}
 * >
 *   <div>Your content here</div>
 * </InfoCard>
 */
export function InfoCard({
  icon: Icon,
  title,
  children,
  action,
  className,
  contentClassName,
}: InfoCardProps) {
  return (
    <Card className={className}>
      <CardHeader className="flex flex-row items-center justify-between pb-2">
        <CardTitle className="flex items-center gap-2 text-base font-semibold">
          {Icon && <Icon className="h-5 w-5 text-primary" />}
          {title}
        </CardTitle>
        {action}
      </CardHeader>
      <CardContent className={contentClassName}>{children}</CardContent>
    </Card>
  );
}

/**
 * InfoCardItem - Item within info card
 *
 * @example
 * <InfoCard title="Quick Stats">
 *   <InfoCardItem
 *     label="Kehadiran"
 *     value="22/23 hari"
 *     badge={<Badge>95%</Badge>}
 *   />
 * </InfoCard>
 */
export function InfoCardItem({
  label,
  value,
  badge,
  className,
}: {
  label: string;
  value: string;
  badge?: ReactNode;
  className?: string;
}) {
  return (
    <div
      className={cn(
        'flex items-center justify-between rounded-lg bg-muted/30 px-3 py-2',
        'transition-colors duration-200 hover:bg-muted/50',
        className
      )}
    >
      <span className="text-sm text-muted-foreground">{label}</span>
      <div className="flex items-center gap-2">
        {badge}
        <span className="text-sm font-medium text-foreground">{value}</span>
      </div>
    </div>
  );
}
