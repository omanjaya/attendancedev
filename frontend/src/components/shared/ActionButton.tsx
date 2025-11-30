import type { LucideIcon } from 'lucide-react';
import type { VariantProps } from 'class-variance-authority';
import { MoreVertical, ChevronDown } from 'lucide-react';
import { Button, buttonVariants } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';

type ButtonVariantProps = VariantProps<typeof buttonVariants>;

interface ActionButtonProps extends React.ComponentProps<'button'>, ButtonVariantProps {
  /** Button label */
  label: string;
  /** Button icon */
  icon?: LucideIcon;
  /** Icon position */
  iconPosition?: 'left' | 'right';
  /** Loading state */
  isLoading?: boolean;
  /** Loading text */
  loadingText?: string;
}

const ActionButton = ({
  label,
  icon: Icon,
  iconPosition = 'left',
  isLoading = false,
  loadingText,
  className,
  disabled,
  ...props
}: ActionButtonProps) => {
  return (
    <Button
      className={cn('gap-2', className)}
      disabled={disabled || isLoading}
      {...props}
    >
      {isLoading ? (
        <>
          <svg
            className="h-4 w-4 animate-spin"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              className="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              strokeWidth="4"
            />
            <path
              className="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
          </svg>
          {loadingText || 'Memproses...'}
        </>
      ) : (
        <>
          {Icon && iconPosition === 'left' && <Icon className="h-4 w-4" />}
          {label}
          {Icon && iconPosition === 'right' && <Icon className="h-4 w-4" />}
        </>
      )}
    </Button>
  );
};

// Action menu for multiple actions
interface ActionMenuItem {
  label: string;
  icon?: LucideIcon;
  onClick: () => void;
  variant?: 'default' | 'destructive';
  separator?: boolean;
}

interface ActionMenuProps {
  items: ActionMenuItem[];
  /** Trigger style */
  triggerStyle?: 'icon' | 'button' | 'dropdown';
  /** Button label when using button style */
  label?: string;
  /** Additional class names */
  className?: string;
}

const ActionMenu = ({
  items,
  triggerStyle = 'icon',
  label = 'Aksi',
  className,
}: ActionMenuProps) => {
  const renderTrigger = () => {
    switch (triggerStyle) {
      case 'button':
        return (
          <Button variant="outline" className={cn('gap-2', className)}>
            {label}
            <ChevronDown className="h-4 w-4" />
          </Button>
        );
      case 'dropdown':
        return (
          <Button variant="ghost" size="sm" className={cn('gap-1', className)}>
            {label}
            <ChevronDown className="h-3 w-3" />
          </Button>
        );
      case 'icon':
      default:
        return (
          <Button variant="ghost" size="icon" className={cn('h-8 w-8', className)}>
            <MoreVertical className="h-4 w-4" />
            <span className="sr-only">Buka menu</span>
          </Button>
        );
    }
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>{renderTrigger()}</DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        {items.map((item, index) => (
          <div key={index}>
            {item.separator && index > 0 && <DropdownMenuSeparator />}
            <DropdownMenuItem
              onClick={item.onClick}
              className={cn(
                item.variant === 'destructive' && 'text-destructive focus:text-destructive'
              )}
            >
              {item.icon && <item.icon className="mr-2 h-4 w-4" />}
              {item.label}
            </DropdownMenuItem>
          </div>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  );
};

export { ActionButton, ActionMenu, type ActionButtonProps, type ActionMenuProps, type ActionMenuItem };
