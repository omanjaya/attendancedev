import type { ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

interface PageLayoutProps {
  /** Page title */
  title: string;
  /** Optional description below title */
  description?: string;
  /** Optional icon before title */
  icon?: LucideIcon;
  /** Actions to display on the right side */
  actions?: ReactNode;
  /** Breadcrumb or back navigation */
  breadcrumb?: ReactNode;
  /** Main content */
  children: ReactNode;
  /** Additional class names */
  className?: string;
}

const PageLayout = ({
  title,
  description,
  icon: Icon,
  actions,
  breadcrumb,
  children,
  className,
}: PageLayoutProps) => {
  return (
    <div className={cn('space-y-6 p-6', className)}>
      {/* Breadcrumb */}
      {breadcrumb && <div className="mb-2">{breadcrumb}</div>}

      {/* Header */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex items-start gap-3">
          {Icon && (
            <div className="rounded-lg bg-primary/10 p-2.5">
              <Icon className="h-5 w-5 text-primary" />
            </div>
          )}
          <div>
            <h1 className="text-2xl font-bold tracking-tight text-foreground">{title}</h1>
            {description && (
              <p className="mt-1 text-sm text-muted-foreground">{description}</p>
            )}
          </div>
        </div>
        {actions && <div className="flex items-center gap-2">{actions}</div>}
      </div>

      {/* Content */}
      {children}
    </div>
  );
};

export { PageLayout, type PageLayoutProps };
