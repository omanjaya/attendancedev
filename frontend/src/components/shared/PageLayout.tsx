import type { ReactNode } from 'react';


// Type for lucide icon components
type IconType = React.ComponentType<{ className?: string }>;

// Removed LucideIcon type import from 'lucide-react';
import { cn } from '@/lib/utils';


interface PageLayoutProps {
  /** Page title */
  title: string;
  /** Optional description below title */
  description?: string;
  /** Optional icon before title */
  icon?: IconType;
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
            <div className="rounded-xl bg-gradient-to-br from-primary/10 to-emerald-500/10 p-3 shadow-sm ring-1 ring-inset ring-primary/10">
              <Icon className="h-6 w-6 text-primary" />
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
