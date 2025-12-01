import { Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';

interface LoadingStateProps {
  message?: string;
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

const sizeStyles = {
  sm: { container: 'py-6', icon: 'h-6 w-6', text: 'text-xs' },
  md: { container: 'py-12', icon: 'h-8 w-8', text: 'text-sm' },
  lg: { container: 'py-16', icon: 'h-10 w-10', text: 'text-base' },
};

/**
 * LoadingState - Centered loading spinner
 *
 * @example
 * <LoadingState message="Memuat data..." size="md" />
 */
export function LoadingState({ message, size = 'md', className }: LoadingStateProps) {
  const styles = sizeStyles[size];

  return (
    <div className={cn('flex flex-col items-center justify-center', styles.container, className)}>
      <Loader2 className={cn('animate-spin text-primary', styles.icon)} />
      {message && (
        <p className={cn('text-muted-foreground mt-3', styles.text)}>{message}</p>
      )}
    </div>
  );
}

/**
 * LoadingOverlay - Full-screen loading overlay
 *
 * @example
 * {isLoading && <LoadingOverlay />}
 */
export function LoadingOverlay({ message }: { message?: string }) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/80 backdrop-blur-sm">
      <div className="flex flex-col items-center">
        <Loader2 className="h-10 w-10 animate-spin text-primary" />
        {message && <p className="mt-4 text-sm text-muted-foreground">{message}</p>}
      </div>
    </div>
  );
}

/**
 * Skeleton components for loading placeholders
 */
export function Skeleton({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn('animate-pulse rounded-md bg-muted', className)}
      {...props}
    />
  );
}

/**
 * CardSkeleton - Skeleton for card loading
 */
export function CardSkeleton({ count = 1 }: { count?: number }) {
  return (
    <>
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className="rounded-xl border border-border/50 bg-card p-6 shadow-sm">
          <div className="flex items-center gap-3">
            <Skeleton className="h-12 w-12 rounded-full" />
            <div className="flex-1 space-y-2">
              <Skeleton className="h-4 w-3/4" />
              <Skeleton className="h-3 w-1/2" />
            </div>
          </div>
        </div>
      ))}
    </>
  );
}

/**
 * TableSkeleton - Skeleton for table loading
 */
export function TableSkeleton({ rows = 5, columns = 4 }: { rows?: number; columns?: number }) {
  return (
    <div className="space-y-3">
      {/* Header */}
      <div className="flex gap-4">
        {Array.from({ length: columns }).map((_, i) => (
          <Skeleton key={i} className="h-10 flex-1" />
        ))}
      </div>
      {/* Rows */}
      {Array.from({ length: rows }).map((_, i) => (
        <div key={i} className="flex gap-4">
          {Array.from({ length: columns }).map((_, j) => (
            <Skeleton key={j} className="h-12 flex-1" />
          ))}
        </div>
      ))}
    </div>
  );
}

/**
 * ListSkeleton - Skeleton for list items
 */
export function ListSkeleton({ count = 5 }: { count?: number }) {
  return (
    <div className="space-y-3">
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className="flex items-center gap-3 p-4">
          <Skeleton className="h-10 w-10 rounded-full" />
          <div className="flex-1 space-y-2">
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-3 w-3/4" />
          </div>
          <Skeleton className="h-8 w-20" />
        </div>
      ))}
    </div>
  );
}

/**
 * StatSkeleton - Skeleton for stat cards
 */
export function StatSkeleton({ count = 4 }: { count?: number }) {
  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className="rounded-xl border border-border/50 bg-card p-6 shadow-sm">
          <div className="flex items-center justify-between mb-2">
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-8 w-8 rounded-lg" />
          </div>
          <Skeleton className="h-8 w-16 mb-2" />
          <Skeleton className="h-3 w-20" />
        </div>
      ))}
    </div>
  );
}
