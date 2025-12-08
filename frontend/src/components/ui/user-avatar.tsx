import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';
import { User } from 'lucide-react';
import React from 'react';


interface UserAvatarProps {
  src?: string;
  name?: string;
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
  status?: 'success' | 'warning' | 'error' | 'info';
  showStatus?: boolean;
  className?: string;
  fallbackClassName?: string;
}

const sizeStyles = {
  xs: {
    avatar: 'h-6 w-6',
    text: 'text-[10px]',
    icon: 'h-3 w-3',
    status: 'h-1.5 w-1.5 -bottom-0 -right-0',
  },
  sm: {
    avatar: 'h-8 w-8',
    text: 'text-xs',
    icon: 'h-4 w-4',
    status: 'h-2 w-2 -bottom-0.5 -right-0.5',
  },
  md: {
    avatar: 'h-10 w-10',
    text: 'text-sm',
    icon: 'h-5 w-5',
    status: 'h-2.5 w-2.5 -bottom-0.5 -right-0.5',
  },
  lg: {
    avatar: 'h-12 w-12',
    text: 'text-base',
    icon: 'h-6 w-6',
    status: 'h-3 w-3 -bottom-1 -right-1',
  },
  xl: {
    avatar: 'h-16 w-16',
    text: 'text-lg',
    icon: 'h-8 w-8',
    status: 'h-3.5 w-3.5 -bottom-1 -right-1',
  },
};

/**
 * UserAvatar - Enhanced avatar with status indicator
 *
 * Features:
 * - Multiple sizes (xs, sm, md, lg, xl)
 * - Status indicator with pulse animation
 * - Automatic initials from name
 * - Gradient fallback background
 * - Ring effect on hover
 *
 * @example
 * <UserAvatar
 *   src="/avatar.jpg"
 *   name="John Doe"
 *   size="md"
 *   status="success"
 *   showStatus
 * />
 */
export function UserAvatar({
  src,
  name,
  size = 'md',
  status,
  showStatus = false,
  className,
  fallbackClassName,
}: UserAvatarProps) {
  const styles = sizeStyles[size];

  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  return (
    <div className="relative inline-block">
      <Avatar
        className={cn(
          styles.avatar,
          'transition-all duration-300 ring-2 ring-background hover:ring-primary/20',
          className
        )}
      >
        <AvatarImage src={src} alt={name} />
        <AvatarFallback
          className={cn(
            'bg-gradient-to-br from-primary via-emerald-500 to-emerald-600 text-primary-foreground font-semibold',
            fallbackClassName
          )}
        >
          {name ? (
            <span className={styles.text}>{getInitials(name)}</span>
          ) : (
            <User className={styles.icon} />
          )}
        </AvatarFallback>
      </Avatar>
      {showStatus && status && (
        <span
          className={cn(
            'absolute rounded-full ring-2 ring-background',
            styles.status,
            {
              'bg-success': status === 'success',
              'bg-warning': status === 'warning',
              'bg-destructive': status === 'error',
              'bg-info': status === 'info',
            }
          )}
        />
      )}
    </div>
  );
}

/**
 * AvatarGroup - Stack multiple avatars
 *
 * @example
 * <AvatarGroup max={3}>
 *   <UserAvatar name="John Doe" />
 *   <UserAvatar name="Jane Smith" />
 *   <UserAvatar name="Bob Wilson" />
 *   <UserAvatar name="Alice Brown" />
 * </AvatarGroup>
 */
export function AvatarGroup({
  children,
  max = 3,
  size = 'md',
  className,
}: {
  children: React.ReactNode;
  max?: number;
  size?: 'xs' | 'sm' | 'md' | 'lg';
  className?: string;
}) {
  const childrenArray = React.Children.toArray(children);
  const displayChildren = childrenArray.slice(0, max);
  const remaining = childrenArray.length - max;

  const styles = sizeStyles[size];

  return (
    <div className={cn('flex items-center', className)}>
      {displayChildren.map((child, index) => (
        <div
          key={index}
          className={cn('relative -ml-2 first:ml-0 ring-2 ring-background rounded-full')}
          style={{ zIndex: displayChildren.length - index }}
        >
          {child}
        </div>
      ))}
      {remaining > 0 && (
        <Avatar
          className={cn(
            styles.avatar,
            '-ml-2 ring-2 ring-background bg-muted hover:bg-muted/80 transition-colors'
          )}
          style={{ zIndex: 0 }}
        >
          <AvatarFallback className="bg-transparent text-muted-foreground font-semibold">
            <span className={styles.text}>+{remaining}</span>
          </AvatarFallback>
        </Avatar>
      )}
    </div>
  );
}
