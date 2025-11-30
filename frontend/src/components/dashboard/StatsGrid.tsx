import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

interface StatItem {
  id: string;
  title: string;
  value: string | number;
  description?: string;
  icon?: LucideIcon;
  trend?: 'up' | 'down' | 'neutral';
  trendValue?: string;
  color?: 'default' | 'success' | 'warning' | 'destructive' | 'primary';
}

interface StatsGridProps {
  stats: StatItem[];
  columns?: 2 | 3 | 4;
  variant?: 'default' | 'compact' | 'detailed';
}

const colorClasses = {
  default: 'bg-muted text-muted-foreground',
  success: 'bg-success/10 text-success',
  warning: 'bg-warning/10 text-warning',
  destructive: 'bg-destructive/10 text-destructive',
  primary: 'bg-primary/10 text-primary',
};

const StatsGrid = ({ stats, columns = 4, variant = 'default' }: StatsGridProps) => {
  const gridCols = {
    2: 'sm:grid-cols-2',
    3: 'sm:grid-cols-2 lg:grid-cols-3',
    4: 'sm:grid-cols-2 lg:grid-cols-4',
  };

  if (variant === 'compact') {
    return (
      <div className={cn('grid gap-4', gridCols[columns])}>
        {stats.map((stat) => (
          <div
            key={stat.id}
            className="flex items-center gap-4 rounded-lg border border-border bg-card p-4"
          >
            {stat.icon && (
              <div className={cn('rounded-lg p-2', colorClasses[stat.color || 'default'])}>
                <stat.icon className="h-5 w-5" />
              </div>
            )}
            <div>
              <p className="text-sm text-muted-foreground">{stat.title}</p>
              <p className="text-2xl font-bold">{stat.value}</p>
            </div>
          </div>
        ))}
      </div>
    );
  }

  if (variant === 'detailed') {
    return (
      <div className={cn('grid gap-4', gridCols[columns])}>
        {stats.map((stat) => (
          <Card key={stat.id} className="overflow-hidden">
            <CardContent className="p-6">
              <div className="flex items-start justify-between">
                <div className="space-y-1">
                  <p className="text-sm font-medium text-muted-foreground">{stat.title}</p>
                  <p className="text-3xl font-bold">{stat.value}</p>
                  {stat.description && (
                    <p className="text-xs text-muted-foreground">{stat.description}</p>
                  )}
                </div>
                {stat.icon && (
                  <div className={cn('rounded-xl p-3', colorClasses[stat.color || 'default'])}>
                    <stat.icon className="h-6 w-6" />
                  </div>
                )}
              </div>
              {stat.trendValue && (
                <div className="mt-4 flex items-center gap-1 text-sm">
                  <span
                    className={cn(
                      stat.trend === 'up' && 'text-success',
                      stat.trend === 'down' && 'text-destructive',
                      stat.trend === 'neutral' && 'text-muted-foreground'
                    )}
                  >
                    {stat.trend === 'up' && '↑'}
                    {stat.trend === 'down' && '↓'}
                    {stat.trendValue}
                  </span>
                  <span className="text-muted-foreground">vs bulan lalu</span>
                </div>
              )}
            </CardContent>
          </Card>
        ))}
      </div>
    );
  }

  // Default variant - inspired by stats8
  return (
    <div className={cn('grid gap-4', gridCols[columns])}>
      {stats.map((stat) => (
        <Card key={stat.id} className="bg-accent/50 border-none">
          <CardContent className="flex flex-col items-center p-6 text-center">
            {stat.icon && (
              <div className={cn('mb-3 rounded-xl p-3', colorClasses[stat.color || 'primary'])}>
                <stat.icon className="h-6 w-6" />
              </div>
            )}
            <p className="text-3xl font-bold">{stat.value}</p>
            <p className="text-sm font-medium text-foreground">{stat.title}</p>
            {stat.description && (
              <p className="mt-1 text-xs text-muted-foreground">{stat.description}</p>
            )}
          </CardContent>
        </Card>
      ))}
    </div>
  );
};

export { StatsGrid, type StatItem, type StatsGridProps };
