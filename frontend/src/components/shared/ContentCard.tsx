import type { ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

interface ContentCardProps {
  /** Card title */
  title?: string;
  /** Title icon */
  icon?: LucideIcon;
  /** Optional description below title */
  description?: string;
  /** Actions to display in header (right side) */
  headerActions?: ReactNode;
  /** Main content */
  children: ReactNode;
  /** Footer content */
  footer?: ReactNode;
  /** Loading state */
  isLoading?: boolean;
  /** Additional class names for the card */
  className?: string;
  /** Additional class names for the content */
  contentClassName?: string;
  /** Whether to remove default content padding */
  noPadding?: boolean;
}

const ContentCard = ({
  title,
  icon: Icon,
  description,
  headerActions,
  children,
  footer,
  isLoading = false,
  className,
  contentClassName,
  noPadding = false,
}: ContentCardProps) => {
  if (isLoading) {
    return (
      <Card className={cn('', className)}>
        {(title || description) && (
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <div className="space-y-1">
              <Skeleton className="h-5 w-32" />
              {description && <Skeleton className="h-4 w-48" />}
            </div>
          </CardHeader>
        )}
        <CardContent className={cn(noPadding && 'p-0', contentClassName)}>
          <div className="space-y-3">
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-4 w-3/4" />
            <Skeleton className="h-4 w-1/2" />
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className={cn('', className)}>
      {(title || description || headerActions) && (
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <div className="flex items-center gap-2">
            {Icon && <Icon className="h-5 w-5 text-primary" />}
            <div>
              {title && (
                <CardTitle className="text-base font-semibold">{title}</CardTitle>
              )}
              {description && (
                <CardDescription className="text-sm">{description}</CardDescription>
              )}
            </div>
          </div>
          {headerActions && <div className="flex items-center gap-2">{headerActions}</div>}
        </CardHeader>
      )}
      <CardContent className={cn(noPadding && 'p-0', contentClassName)}>
        {children}
      </CardContent>
      {footer && <CardFooter className="border-t pt-4">{footer}</CardFooter>}
    </Card>
  );
};

// Compact variant for stat cards
interface StatCardProps {
  title: string;
  value: string | number;
  description?: string;
  icon?: LucideIcon;
  trend?: 'up' | 'down' | 'neutral';
  trendValue?: string;
  color?: 'primary' | 'success' | 'warning' | 'destructive' | 'info';
  className?: string;
}

const colorStyles = {
  primary: 'bg-primary/10 text-primary',
  success: 'bg-success/10 text-success',
  warning: 'bg-warning/10 text-warning',
  destructive: 'bg-destructive/10 text-destructive',
  info: 'bg-info/10 text-info',
};

const StatCard = ({
  title,
  value,
  description,
  icon: Icon,
  trend,
  trendValue,
  color = 'primary',
  className,
}: StatCardProps) => {
  return (
    <Card className={cn('', className)}>
      <CardContent className="p-4">
        <div className="flex items-start justify-between">
          <div className="space-y-1">
            <p className="text-sm text-muted-foreground">{title}</p>
            <p className="text-2xl font-bold">{value}</p>
            {(description || trendValue) && (
              <p className="text-xs text-muted-foreground">
                {trendValue && (
                  <span
                    className={cn(
                      'mr-1 font-medium',
                      trend === 'up' && 'text-success',
                      trend === 'down' && 'text-destructive',
                      trend === 'neutral' && 'text-muted-foreground'
                    )}
                  >
                    {trend === 'up' && '+'}{trendValue}
                  </span>
                )}
                {description}
              </p>
            )}
          </div>
          {Icon && (
            <div className={cn('rounded-lg p-2', colorStyles[color])}>
              <Icon className="h-5 w-5" />
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
};

export { ContentCard, StatCard, type ContentCardProps, type StatCardProps };
