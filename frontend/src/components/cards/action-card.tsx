import { ArrowRight } from 'lucide-react';

import { cn } from '@/lib/utils';



// Type for lucide icon components
type IconType = React.ComponentType<{ className?: string }>;

interface ActionCardProps {
  href: string;
  icon?: IconType;
  title: string;
  description: string;
  iconColor?: 'primary' | 'success' | 'warning' | 'destructive' | 'info';
  onClick?: () => void;
  className?: string;
}

const iconColorClasses = {
  primary: 'bg-primary/10 text-primary',
  success: 'bg-success/10 text-success',
  warning: 'bg-warning/10 text-warning',
  destructive: 'bg-destructive/10 text-destructive',
  info: 'bg-info/10 text-info',
};

/**
 * ActionCard - Interactive card for quick actions
 *
 * Features:
 * - Scale animation (1.02 hover, 0.99 active)
 * - Icon scale effect (110% on hover)
 * - Arrow slide animation
 * - Colored icon backgrounds
 * - Border color transitions
 *
 * @example
 * <ActionCard
 *   href="/attendance"
 *   icon={Clock}
 *   title="Check-in Sekarang"
 *   description="Absen kehadiran"
 *   iconColor="primary"
 * />
 */
export function ActionCard({
  href,
  icon: Icon,
  title,
  description,
  iconColor = 'primary',
  onClick,
  className,
}: ActionCardProps) {
  const handleClick = (e: React.MouseEvent) => {
    if (onClick) {
      e.preventDefault();
      onClick();
    }
  };

  return (
    <a
      href={href}
      onClick={handleClick}
      className={cn(
        'group flex items-center gap-3 rounded-lg border border-border/50 bg-card p-3',
        'text-left transition-all duration-300',
        'hover:border-primary/20 hover:bg-muted/50 hover:shadow-md',
        'hover:scale-[1.02] active:scale-[0.99]',
        className
      )}
    >
      <div
        className={cn(
          'rounded-lg p-2 transition-all duration-300 group-hover:scale-110',
          iconColorClasses[iconColor]
        )}
      >
        {Icon && <Icon className="h-4 w-4" />}
      </div>
      <div className="flex-1 min-w-0">
        <p className="text-sm font-medium text-foreground truncate">{title}</p>
        <p className="text-xs text-muted-foreground truncate">{description}</p>
      </div>
      <ArrowRight className="h-4 w-4 text-muted-foreground transition-transform duration-300 group-hover:translate-x-1 group-hover:text-primary flex-shrink-0" />
    </a>
  );
}
