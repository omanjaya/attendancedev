import type { ReactNode } from 'react';
import { ChevronRight, Loader2 } from 'lucide-react';
import { Card } from '@/components/ui/card';
import { cn } from '@/lib/utils';

export interface MobileCardListProps<T> {
  items: T[];
  renderCard: (item: T, index: number) => ReactNode;
  onItemClick?: (item: T) => void;
  loading?: boolean;
  emptyMessage?: string;
  className?: string;
  showChevron?: boolean;
}

export function MobileCardList<T>({
  items,
  renderCard,
  onItemClick,
  loading = false,
  emptyMessage = 'Tidak ada data',
  className,
  showChevron = true,
}: MobileCardListProps<T>) {
  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!items || items.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center py-12 text-center">
        <p className="text-sm text-muted-foreground">{emptyMessage}</p>
      </div>
    );
  }

  return (
    <div className={cn('space-y-3', className)}>
      {items.map((item, index) => (
        <Card
          key={index}
          className={cn(
            'py-0 transition-all duration-200 hover:border-primary/20',
            onItemClick &&
            'cursor-pointer hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] hover:bg-muted/30'
          )}
          onClick={() => onItemClick?.(item)}
        >
          <div className="flex items-center gap-3 p-4">
            <div className="flex-1">{renderCard(item, index)}</div>
            {showChevron && onItemClick && (
              <ChevronRight className="h-5 w-5 flex-shrink-0 text-muted-foreground transition-transform duration-200 group-hover:translate-x-1" />
            )}
          </div>
        </Card>
      ))}
    </div>
  );
}

// Skeleton loading component for mobile cards
export function MobileCardListSkeleton({ count = 5 }: { count?: number }) {
  return (
    <div className="space-y-3">
      {Array.from({ length: count }).map((_, i) => (
        <Card key={i} className="p-4">
          <div className="flex items-center gap-3">
            <div className="h-12 w-12 animate-pulse rounded-full bg-muted" />
            <div className="flex-1 space-y-2">
              <div className="h-4 w-3/4 animate-pulse rounded bg-muted" />
              <div className="h-3 w-1/2 animate-pulse rounded bg-muted" />
            </div>
          </div>
        </Card>
      ))}
    </div>
  );
}
