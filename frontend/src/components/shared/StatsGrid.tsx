import type { ReactNode } from 'react';


// Type for lucide icon components
type IconType = React.ComponentType<{ className?: string }>;

// Removed LucideIcon type import from 'lucide-react';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';

import { cn } from '@/lib/utils';


export interface StatItem {
  /** Stat label/title */
  label: string;
  /** Stat value (can be number or formatted string) */
  value: string | number;
  /** Optional description or subtitle */
  description?: string;
  /** Optional icon */
  icon?: IconType;
  /** Trend direction */
  trend?: 'up' | 'down' | 'neutral';
  /** Trend value (e.g., "+12%", "-5%") */
  trendValue?: string;
  /** Color variant */
  color?: 'default' | 'primary' | 'success' | 'warning' | 'destructive' | 'info';
}

interface StatsGridProps {
  /** Array of stat items to display */
  stats: StatItem[];
  /** Number of columns (1-4) */
  columns?: 1 | 2 | 3 | 4;
  /** Additional class names */
  className?: string;
  /** Variant style */
  variant?: 'cards' | 'minimal' | 'bordered';
}

const colorStyles = {
  default: 'bg-muted text-foreground',
  primary: 'bg-primary/10 text-primary',
  success: 'bg-success/10 text-success',
  warning: 'bg-warning/10 text-warning',
  destructive: 'bg-destructive/10 text-destructive',
  info: 'bg-info/10 text-info',
};

const trendStyles = {
  up: 'text-success',
  down: 'text-destructive',
  neutral: 'text-muted-foreground',
};

const TrendIcon = ({ trend }: { trend?: 'up' | 'down' | 'neutral' }) => {
  if (trend === 'up') return <TrendingUp className="h-3.5 w-3.5" />;
  if (trend === 'down') return <TrendingDown className="h-3.5 w-3.5" />;
  return <Minus className="h-3.5 w-3.5" />;
};

const columnClasses = {
  1: 'grid-cols-1',
  2: 'grid-cols-1 sm:grid-cols-2',
  3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
  4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
};

/**
 * StatsGrid - Display a grid of statistics
 * Based on shadcnblocks stats patterns
 */
const StatsGrid = ({
  stats,
  columns = 4,
  className,
  variant = 'cards',
}: StatsGridProps) => {
  if (variant === 'minimal') {
    return (
      <div className={cn('grid gap-8', columnClasses[columns], className)}>
        {stats.map((stat, index) => (
          <div key={index} className="text-center">
            <p className="text-sm font-medium text-muted-foreground">{stat.label}</p>
            <p className="mt-2 text-4xl font-bold tracking-tight">{stat.value}</p>
            {stat.description && (
              <p className="mt-1 text-sm text-muted-foreground">{stat.description}</p>
            )}
            {stat.trendValue && stat.trend && (
              <div className={cn('mt-2 flex items-center justify-center gap-1', trendStyles[stat.trend])}>
                <TrendIcon trend={stat.trend} />
                <span className="text-sm font-medium">{stat.trendValue}</span>
              </div>
            )}
          </div>
        ))}
      </div>
    );
  }

  if (variant === 'bordered') {
    return (
      <div className={cn('grid divide-y sm:divide-x sm:divide-y-0', columnClasses[columns], className)}>
        {stats.map((stat, index) => (
          <div key={index} className="py-4 text-center sm:px-6 sm:py-0 first:pt-0 last:pb-0 sm:first:pl-0 sm:last:pr-0">
            <p className="text-sm font-medium text-muted-foreground">{stat.label}</p>
            <p className="mt-2 text-3xl font-bold tracking-tight">{stat.value}</p>
            {stat.description && (
              <p className="mt-1 text-sm text-muted-foreground">{stat.description}</p>
            )}
          </div>
        ))}
      </div>
    );
  }

  // Default: cards variant
  return (
    <>
      {/* Mobile: Horizontal Scroll */}
      <div className={cn(
        'flex gap-4 overflow-x-auto pb-2 sm:hidden',
        'snap-x snap-mandatory hide-scrollbar',
        className
      )}>
        {stats.map((stat, index) => {
          const Icon = stat.icon;
          const color = stat.color || 'default';

          return (
            <Card key={index} className="min-w-[280px] flex-shrink-0 snap-center">
              <CardContent className="p-6">
                <div className="flex items-start justify-between">
                  <div className="space-y-1">
                    <p className="text-sm font-medium text-muted-foreground">{stat.label}</p>
                    <p className="text-3xl font-bold tracking-tight">{stat.value}</p>
                    {(stat.description || stat.trendValue) && (
                      <div className="flex items-center gap-2 pt-1">
                        {stat.trendValue && stat.trend && (
                          <span className={cn('flex items-center gap-1 text-sm font-medium', trendStyles[stat.trend])}>
                            <TrendIcon trend={stat.trend} />
                            {stat.trendValue}
                          </span>
                        )}
                        {stat.description && (
                          <span className="text-sm text-muted-foreground">{stat.description}</span>
                        )}
                      </div>
                    )}
                  </div>
                  {Icon && (
                    <div className={cn('rounded-lg p-2.5', colorStyles[color])}>
                      <Icon className="h-5 w-5" />
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>

      {/* Desktop: Grid */}
      <div className={cn('hidden gap-4 sm:grid', columnClasses[columns], className)}>
        {stats.map((stat, index) => {
          const Icon = stat.icon;
          const color = stat.color || 'default';

          return (
            <Card key={index}>
              <CardContent className="p-6">
                <div className="flex items-start justify-between">
                  <div className="space-y-1">
                    <p className="text-sm font-medium text-muted-foreground">{stat.label}</p>
                    <p className="text-3xl font-bold tracking-tight">{stat.value}</p>
                    {(stat.description || stat.trendValue) && (
                      <div className="flex items-center gap-2 pt-1">
                        {stat.trendValue && stat.trend && (
                          <span className={cn('flex items-center gap-1 text-sm font-medium', trendStyles[stat.trend])}>
                            <TrendIcon trend={stat.trend} />
                            {stat.trendValue}
                          </span>
                        )}
                        {stat.description && (
                          <span className="text-sm text-muted-foreground">{stat.description}</span>
                        )}
                      </div>
                    )}
                  </div>
                  {Icon && (
                    <div className={cn('rounded-lg p-2.5', colorStyles[color])}>
                      <Icon className="h-5 w-5" />
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>
    </>
  );
};

export { StatsGrid, type StatsGridProps };
