import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface ActivityCardProps {
  avatar?: ReactNode;
  initials?: string;
  title: string;
  description: string;
  time?: string;
  badge?: {
    label: string;
    variant?: 'default' | 'secondary' | 'outline' | 'destructive';
    className?: string;
  };
  onClick?: () => void;
  className?: string;
}

/**
 * ActivityCard - Card for activity/timeline items
 *
 * Features:
 * - Avatar with gradient ring on hover
 * - Coordinated group hover effects
 * - Badge support for status
 * - Text truncation for long content
 * - Clickable with cursor pointer
 *
 * @example
 * <ActivityCard
 *   initials="JD"
 *   title="John Doe"
 *   description="Melakukan check-in"
 *   time="08:15"
 *   badge={{
 *     label: "Check In",
 *     className: "bg-success/10 text-success"
 *   }}
 *   onClick={() => navigate('/detail')}
 * />
 */
export function ActivityCard({
  avatar,
  initials,
  title,
  description,
  time,
  badge,
  onClick,
  className,
}: ActivityCardProps) {
  return (
    <div
      onClick={onClick}
      className={cn(
        'group flex items-center justify-between p-4',
        'transition-all duration-300 hover:bg-muted/30',
        onClick && 'cursor-pointer',
        className
      )}
    >
      <div className="flex items-center gap-3 flex-1 min-w-0">
        {/* Avatar */}
        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-muted to-muted/50 ring-2 ring-background group-hover:ring-primary/20 transition-all duration-300 flex-shrink-0">
          {avatar || (
            <span className="text-sm font-semibold text-foreground">
              {initials || 'U'}
            </span>
          )}
        </div>

        {/* Content */}
        <div className="min-w-0 flex-1">
          <p className="text-sm font-medium text-foreground truncate">{title}</p>
          <p className="text-xs text-muted-foreground truncate">{description}</p>
        </div>
      </div>

      {/* Right Side (Time & Badge) */}
      <div className="text-right flex-shrink-0 ml-2">
        {time && <p className="text-sm text-foreground font-medium mb-1">{time}</p>}
        {badge && (
          <Badge
            variant={badge.variant || 'secondary'}
            className={cn('font-medium', badge.className)}
          >
            {badge.label}
          </Badge>
        )}
      </div>
    </div>
  );
}

/**
 * ActivityCardList - Container for activity cards with dividers
 *
 * @example
 * <ActivityCardList>
 *   <ActivityCard ... />
 *   <ActivityCard ... />
 * </ActivityCardList>
 */
export function ActivityCardList({
  children,
  className,
}: {
  children: ReactNode;
  className?: string;
}) {
  return (
    <div className={cn('divide-y divide-border/50', className)}>
      {children}
    </div>
  );
}
