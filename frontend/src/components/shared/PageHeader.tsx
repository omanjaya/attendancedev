import type { ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

interface PageHeaderProps {
  /** Page title */
  title: string;
  /** Optional description/subtitle */
  description?: string;
  /** Optional badge text */
  badge?: string;
  /** Optional icon */
  icon?: LucideIcon;
  /** Actions to display on the right */
  actions?: ReactNode;
  /** Breadcrumb navigation */
  breadcrumb?: ReactNode;
  /** Additional class names */
  className?: string;
  /** Alignment: left (default) or center */
  align?: 'left' | 'center';
  /** Size variant */
  size?: 'sm' | 'md' | 'lg';
}

const sizeStyles = {
  sm: {
    title: 'text-xl font-semibold',
    description: 'text-sm',
    icon: 'h-5 w-5',
    iconWrapper: 'p-2',
    spacing: 'py-4',
  },
  md: {
    title: 'text-2xl font-bold',
    description: 'text-base',
    icon: 'h-6 w-6',
    iconWrapper: 'p-2.5',
    spacing: 'py-6',
  },
  lg: {
    title: 'text-3xl font-bold lg:text-4xl',
    description: 'text-lg',
    icon: 'h-8 w-8',
    iconWrapper: 'p-3',
    spacing: 'py-8',
  },
};

/**
 * PageHeader - Hero-style page header based on shadcnblocks patterns
 */
const PageHeader = ({
  title,
  description,
  badge,
  icon: Icon,
  actions,
  breadcrumb,
  className,
  align = 'left',
  size = 'md',
}: PageHeaderProps) => {
  const styles = sizeStyles[size];
  const isCenter = align === 'center';

  return (
    <div className={cn(styles.spacing, className)}>
      {/* Breadcrumb */}
      {breadcrumb && <div className="mb-4">{breadcrumb}</div>}

      <div
        className={cn(
          'flex flex-col gap-4',
          isCenter ? 'items-center text-center' : 'sm:flex-row sm:items-start sm:justify-between'
        )}
      >
        {/* Left section: Icon, Title, Description */}
        <div className={cn('flex gap-4', isCenter ? 'flex-col items-center' : 'items-start')}>
          {Icon && (
            <div className={cn('shrink-0 rounded-lg bg-primary/10', styles.iconWrapper)}>
              <Icon className={cn('text-primary', styles.icon)} />
            </div>
          )}
          <div className={cn(isCenter && 'space-y-2')}>
            {badge && (
              <Badge variant="secondary" className="mb-2">
                {badge}
              </Badge>
            )}
            <h1 className={cn('tracking-tight text-foreground', styles.title)}>{title}</h1>
            {description && (
              <p className={cn('mt-1 text-muted-foreground max-w-2xl', styles.description)}>
                {description}
              </p>
            )}
          </div>
        </div>

        {/* Right section: Actions */}
        {actions && (
          <div className={cn('flex items-center gap-2 shrink-0', isCenter && 'mt-4')}>
            {actions}
          </div>
        )}
      </div>
    </div>
  );
};

export { PageHeader, type PageHeaderProps };
