import { ChevronLeft } from 'lucide-react';
import { useNavigate } from '@tanstack/react-router';
import { cn } from '@/lib/utils';

interface MobilePageHeaderProps {
  /**
   * Title displayed in the header
   */
  title: string;

  /**
   * Callback when back button is clicked (optional if backTo is provided)
   */
  onBack?: () => void;

  /**
   * Path to navigate back to (alternative to onBack)
   */
  backTo?: string;

  /**
   * Gradient color scheme
   * @default 'blue'
   */
  gradient?: 'blue' | 'violet' | 'indigo' | 'emerald' | 'pink' | 'amber' | 'gray' | 'green' | 'teal' | 'cyan' | 'rose' | 'orange';

  /**
   * Optional action buttons displayed on the right (legacy prop)
   */
  actions?: React.ReactNode;

  /**
   * Optional action displayed on the right side (alias for actions)
   */
  rightAction?: React.ReactNode;

  /**
   * Optional subtitle content displayed below the title
   */
  subtitle?: React.ReactNode;

  /**
   * Optional className for the wrapper
   */
  className?: string;
}

const gradientClasses = {
  blue: 'from-blue-600 to-cyan-600 dark:from-blue-900 dark:to-cyan-800',
  violet: 'from-violet-600 to-indigo-600 dark:from-violet-900 dark:to-indigo-800',
  indigo: 'from-indigo-600 to-blue-600 dark:from-indigo-900 dark:to-blue-800',
  emerald: 'from-emerald-600 to-teal-600 dark:from-emerald-900 dark:to-teal-800',
  pink: 'from-pink-600 to-rose-600 dark:from-pink-900 dark:to-rose-800',
  amber: 'from-amber-600 to-orange-600 dark:from-amber-900 dark:to-orange-800',
  gray: 'from-gray-900 to-gray-800 dark:from-black dark:to-gray-900',
  green: 'from-green-600 to-emerald-600 dark:from-green-900 dark:to-emerald-800',
  teal: 'from-teal-600 to-cyan-600 dark:from-teal-900 dark:to-cyan-800',
  cyan: 'from-cyan-600 to-blue-600 dark:from-cyan-900 dark:to-blue-800',
  rose: 'from-rose-600 to-pink-600 dark:from-rose-900 dark:to-pink-800',
  orange: 'from-orange-600 to-amber-600 dark:from-orange-900 dark:to-amber-800',
};

/**
 * MobilePageHeader - Reusable mobile page header with gradient background
 *
 * Used across 15+ mobile pages for consistent header styling with:
 * - Back navigation button
 * - Gradient background
 * - Optional action buttons
 * - Glassmorphism effect
 *
 * @example
 * ```tsx
 * <MobilePageHeader
 *   title="Karyawan"
 *   onBack={() => navigate({ to: '/admin/dashboard' })}
 *   gradient="blue"
 *   actions={
 *     <button className="p-2 hover:bg-white/10 rounded-full">
 *       <Filter className="h-5 w-5 text-white" />
 *     </button>
 *   }
 * />
 * ```
 */
export function MobilePageHeader({
  title,
  onBack,
  backTo,
  gradient = 'blue',
  actions,
  rightAction,
  subtitle,
  className,
}: MobilePageHeaderProps) {
  const navigate = useNavigate();

  const handleBack = () => {
    if (onBack) {
      onBack();
    } else if (backTo) {
      navigate({ to: backTo });
    } else {
      window.history.back();
    }
  };

  // Use rightAction if provided, otherwise fall back to actions
  const actionContent = rightAction || actions;

  return (
    <div className={cn('px-4 pt-3 pb-3 sticky top-0 z-20', className)}>
      <div className="bg-card/80 dark:bg-card/60 backdrop-blur-md rounded-3xl p-1.5 shadow-xl border border-border/40 dark:border-border/30">
        <div
          className={cn(
            'px-4 py-3 rounded-[20px] shadow-lg bg-gradient-to-r',
            gradientClasses[gradient]
          )}
        >
          <div className="flex items-center gap-3">
            <button
              onClick={handleBack}
              className="p-2 -ml-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
              title="Kembali"
              aria-label="Kembali"
            >
              <ChevronLeft className="h-5 w-5 text-white" />
            </button>

            <h1 className="text-base font-bold text-white flex-1">{title}</h1>

            {actionContent}
          </div>
          {subtitle && <div className="mt-1 ml-10">{subtitle}</div>}
        </div>
      </div>
    </div>
  );
}
