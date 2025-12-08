
// Type for lucide icon components
type IconType = React.ComponentType<{ className?: string }>;

// Removed LucideIcon import from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

import { Button } from '@/components/ui/button';

import { cn } from '@/lib/utils';

import type { ReactNode } from 'react';


interface BannerCardProps {
  icon?: IconType;
  title: string;
  description: string;
  actionLabel: string;
  actionHref?: string;
  onAction?: () => void;
  variant?: 'primary' | 'success' | 'warning' | 'destructive' | 'info';
  buttonVariant?: 'default' | 'outline';
  actionIcon?: ReactNode;
  className?: string;
}

const variantStyles = {
  primary: {
    border: 'border-l-primary',
    gradient: 'bg-gradient-to-r from-primary/5 to-transparent',
    iconBg: 'bg-gradient-to-br from-primary to-primary/80',
    iconShadow: 'shadow-lg shadow-primary/30',
    buttonShadow: 'shadow-lg shadow-primary/20 hover:shadow-xl hover:shadow-primary/30',
    buttonOutline: 'border-primary/30 hover:bg-primary/10 hover:border-primary',
  },
  success: {
    border: 'border-l-success',
    gradient: 'bg-gradient-to-r from-success/5 to-transparent',
    iconBg: 'bg-gradient-to-br from-success to-success/80',
    iconShadow: 'shadow-lg shadow-success/30',
    buttonShadow: 'shadow-lg shadow-success/20 hover:shadow-xl hover:shadow-success/30',
    buttonOutline: 'border-success/30 hover:bg-success/10 hover:border-success',
  },
  warning: {
    border: 'border-l-warning',
    gradient: 'bg-gradient-to-r from-warning/5 to-transparent',
    iconBg: 'bg-gradient-to-br from-warning to-warning/80',
    iconShadow: 'shadow-lg shadow-warning/30',
    buttonShadow: 'shadow-lg shadow-warning/20 hover:shadow-xl hover:shadow-warning/30',
    buttonOutline: 'border-warning/30 hover:bg-warning/10 hover:border-warning',
  },
  destructive: {
    border: 'border-l-destructive',
    gradient: 'bg-gradient-to-r from-destructive/5 to-transparent',
    iconBg: 'bg-gradient-to-br from-destructive to-destructive/80',
    iconShadow: 'shadow-lg shadow-destructive/30',
    buttonShadow: 'shadow-lg shadow-destructive/20 hover:shadow-xl hover:shadow-destructive/30',
    buttonOutline: 'border-destructive/30 hover:bg-destructive/10 hover:border-destructive',
  },
  info: {
    border: 'border-l-info',
    gradient: 'bg-gradient-to-r from-info/5 to-transparent',
    iconBg: 'bg-gradient-to-br from-info to-info/80',
    iconShadow: 'shadow-lg shadow-info/30',
    buttonShadow: 'shadow-lg shadow-info/20 hover:shadow-xl hover:shadow-info/30',
    buttonOutline: 'border-info/30 hover:bg-info/10 hover:border-info',
  },
};

/**
 * BannerCard - Prominent CTA/notification banner
 *
 * Features:
 * - Gradient backgrounds with colored border
 * - Gradient icon container with shadow
 * - Button with colored shadow
 * - Responsive layout (stack on mobile)
 * - Hover shadow lift
 *
 * @example
 * <BannerCard
 *   icon={Clock}
 *   title="Belum Check-in Hari Ini"
 *   description="Jadwal: 08:00 - 17:00"
 *   actionLabel="Check-in Sekarang"
 *   actionHref="/face-recognition"
 *   variant="primary"
 *   actionIcon={<ArrowRight className="h-4 w-4" />}
 * />
 */
export function BannerCard({
  icon: Icon,
  title,
  description,
  actionLabel,
  actionHref,
  onAction,
  variant = 'primary',
  buttonVariant = 'default',
  actionIcon,
  className,
}: BannerCardProps) {
  const styles = variantStyles[variant];

  const buttonContent = (
    <Button
      variant={buttonVariant}
      asChild={!!actionHref}
      onClick={onAction}
      className={cn(
        'gap-2 transition-all duration-300 flex-shrink-0',
        buttonVariant === 'default' ? styles.buttonShadow : styles.buttonOutline
      )}
    >
      {actionHref ? (
        <a href={actionHref}>
          {actionLabel}
          {actionIcon}
        </a>
      ) : (
        <>
          {actionLabel}
          {actionIcon}
        </>
      )}
    </Button>
  );

  return (
    <Card
      className={cn(
        'border-l-4 hover:shadow-lg transition-all duration-300',
        styles.border,
        styles.gradient,
        className
      )}
    >
      <CardContent className="p-4 sm:p-6">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div className="flex items-center gap-3 flex-1 min-w-0">
            <div
              className={cn(
                'flex h-12 w-12 sm:h-10 sm:w-10 items-center justify-center rounded-xl flex-shrink-0',
                styles.iconBg,
                styles.iconShadow
              )}
            >
              {Icon && <Icon className="h-6 w-6 sm:h-5 sm:w-5 text-primary-foreground" />}
            </div>
            <div className="min-w-0 flex-1">
              <p className="font-semibold text-foreground">{title}</p>
              <p className="text-sm text-muted-foreground truncate">{description}</p>
            </div>
          </div>
          {buttonContent}
        </div>
      </CardContent>
    </Card>
  );
}
