import type { ReactNode } from 'react';
import { MoreHorizontal } from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

// Type for lucide-react icon components
type IconComponent = React.ComponentType<React.SVGProps<SVGSVGElement>>;

export interface ActionItem {
  /**
   * Action label
   */
  label: string;

  /**
   * Icon component from lucide-react
   */
  icon: IconComponent;

  /**
   * Click handler
   */
  onClick: () => void;

  /**
   * Visual variant
   * @default "default"
   */
  variant?: 'default' | 'destructive';

  /**
   * Whether to show a separator after this action
   * @default false
   */
  dividerAfter?: boolean;

  /**
   * Whether the action is disabled
   * @default false
   */
  disabled?: boolean;
}

interface ActionsDropdownProps {
  /**
   * Array of action items
   */
  actions: ActionItem[];

  /**
   * Dropdown alignment
   * @default "end"
   */
  align?: 'start' | 'end' | 'center';

  /**
   * Custom trigger element
   * If not provided, a default MoreHorizontal button is used
   */
  trigger?: ReactNode;

  /**
   * Optional className for the trigger button
   */
  triggerClassName?: string;

  /**
   * Optional label for accessibility
   */
  label?: string;
}

/**
 * ActionsDropdown - Reusable dropdown menu for row/item actions
 *
 * Used across 11+ desktop pages for consistent action menu UX in tables and cards.
 *
 * @example
 * ```tsx
 * <ActionsDropdown
 *   actions={[
 *     { label: "Edit", icon: Edit, onClick: () => handleEdit(item) },
 *     { label: "View", icon: Eye, onClick: () => handleView(item) },
 *     { label: "Delete", icon: Trash2, onClick: () => handleDelete(item), variant: "destructive", dividerAfter: true },
 *   ]}
 *   align="end"
 * />
 * ```
 */
export function ActionsDropdown({
  actions,
  align = 'end',
  trigger,
  triggerClassName,
  label = 'Actions',
}: ActionsDropdownProps) {
  const defaultTrigger = (
    <Button
      variant="ghost"
      size="sm"
      className={cn('h-8 w-8 p-0 rounded-full', triggerClassName)}
      aria-label={label}
    >
      <MoreHorizontal className="h-4 w-4" />
    </Button>
  );

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>{trigger || defaultTrigger}</DropdownMenuTrigger>
      <DropdownMenuContent align={align} className="w-48">
        {actions.map((action, index) => {
          const Icon = action.icon;
          const isDestructive = action.variant === 'destructive';

          return (
            <div key={index}>
              <DropdownMenuItem
                onClick={action.onClick}
                disabled={action.disabled}
                className={cn(
                  'cursor-pointer',
                  isDestructive && 'text-destructive focus:text-destructive focus:bg-destructive/10'
                )}
              >
                <Icon className="mr-2 h-4 w-4" />
                {action.label}
              </DropdownMenuItem>
              {action.dividerAfter && <DropdownMenuSeparator />}
            </div>
          );
        })}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
