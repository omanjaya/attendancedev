import { motion } from 'framer-motion';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface MobileListCardProps<T = any> {
  /**
   * The data item for this card
   */
  item: T;

  /**
   * Card title
   */
  title: string | ((item: T) => string);

  /**
   * Optional subtitle
   */
  subtitle?: string | ((item: T) => string);

  /**
   * Optional avatar/icon on the left
   */
  avatar?: ReactNode | ((item: T) => ReactNode);

  /**
   * Optional badges/tags
   */
  badges?: ReactNode | ((item: T) => ReactNode);

  /**
   * Optional footer content
   */
  footer?: ReactNode | ((item: T) => ReactNode);

  /**
   * Optional action button (usually MoreVertical)
   */
  actions?: ReactNode | ((item: T, onActionClick: (e: React.MouseEvent) => void) => ReactNode);

  /**
   * Callback when card is tapped
   */
  onTap?: (item: T) => void;

  /**
   * Callback when actions button is clicked
   */
  onActionsClick?: (item: T, e: React.MouseEvent) => void;

  /**
   * Animation index for staggered animations
   */
  animationIndex?: number;

  /**
   * Whether to enable animations
   * @default true
   */
  animated?: boolean;

  /**
   * Optional className
   */
  className?: string;
}

/**
 * MobileListCard - Reusable mobile list card with animations
 *
 * Used across 12+ mobile list pages for consistent card styling with:
 * - Framer Motion animations
 * - Avatar/Icon support
 * - Badges and footer
 * - Touch-friendly tap interactions
 * - Action button support
 *
 * @example
 * ```tsx
 * <MobileListCard
 *   item={employee}
 *   title={employee.name}
 *   subtitle={employee.position}
 *   avatar={<Avatar><AvatarImage src={employee.avatar} /></Avatar>}
 *   badges={<Badge variant="success">Active</Badge>}
 *   footer={
 *     <div className="flex gap-2">
 *       <Building2 className="h-3 w-3" />
 *       <span>{employee.department}</span>
 *     </div>
 *   }
 *   onTap={(emp) => navigate(`/employees/${emp.id}`)}
 *   onActionsClick={(emp, e) => openMenu(e, emp)}
 *   animationIndex={index}
 * />
 * ```
 */
export function MobileListCard<T = any>({
  item,
  title,
  subtitle,
  avatar,
  badges,
  footer,
  actions,
  onTap,
  onActionsClick,
  animationIndex = 0,
  animated = true,
  className,
}: MobileListCardProps<T>) {
  const resolveValue = <V,>(value: V | ((item: T) => V)): V => {
    return typeof value === 'function' ? (value as (item: T) => V)(item) : value;
  };

  const handleActionClick = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (onActionsClick) {
      onActionsClick(item, e);
    }
  };

  const titleText = resolveValue(title);
  const subtitleText = subtitle ? resolveValue(subtitle) : undefined;
  const avatarContent = avatar ? resolveValue(avatar) : null;
  const badgesContent = badges ? resolveValue(badges) : null;
  const footerContent = footer ? resolveValue(footer) : null;
  const actionsContent = actions
    ? typeof actions === 'function'
      ? actions(item, handleActionClick)
      : actions
    : null;

  const card = (
    <div
      onClick={onTap ? () => onTap(item) : undefined}
      className={cn(
        'bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-border/50 transition-transform',
        onTap && 'cursor-pointer active:scale-[0.99]',
        className
      )}
    >
      <div className="flex items-start gap-3">
        {avatarContent}

        <div className="flex-1 min-w-0">
          <div className="flex items-start justify-between gap-2">
            <div className="min-w-0 flex-1">
              <h3 className="font-bold text-foreground truncate">{titleText}</h3>
              {subtitleText && (
                <p className="text-xs text-muted-foreground truncate">{subtitleText}</p>
              )}
            </div>
            {actionsContent}
          </div>

          {badgesContent && <div className="mt-2">{badgesContent}</div>}

          {footerContent && <div className="mt-2">{footerContent}</div>}
        </div>
      </div>
    </div>
  );

  if (!animated) {
    return card;
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, scale: 0.95 }}
      transition={{ delay: animationIndex * 0.05 }}
    >
      {card}
    </motion.div>
  );
}
