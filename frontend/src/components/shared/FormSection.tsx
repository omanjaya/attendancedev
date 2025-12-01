import type { ReactNode } from 'react';


// Type for lucide icon components
type IconType = React.ComponentType<{ className?: string }>;

// Removed LucideIcon type import from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

import { cn } from '@/lib/utils';


interface FormSectionProps {
  /** Section title */
  title: string;
  /** Section description */
  description?: string;
  /** Icon for the section */
  icon?: IconType;
  /** Whether the section is optional */
  optional?: boolean;
  /** Form fields */
  children: ReactNode;
  /** Additional class names */
  className?: string;
}

/**
 * FormSection - A card-based form section with title and description
 * Based on shadcnblocks contact form patterns
 */
const FormSection = ({
  title,
  description,
  icon: Icon,
  optional = false,
  children,
  className,
}: FormSectionProps) => {
  return (
    <Card className={cn('border-l-4 border-l-primary', className)}>
      <CardHeader className="pb-4">
        <div className="flex items-center gap-3">
          {Icon && (
            <div className="rounded-lg bg-primary/10 p-2">
              <Icon className="h-5 w-5 text-primary" />
            </div>
          )}
          <div>
            <CardTitle className="flex items-center gap-2 text-lg">
              {title}
              {optional && (
                <span className="text-sm font-normal text-muted-foreground">(Optional)</span>
              )}
            </CardTitle>
            {description && <CardDescription className="mt-1">{description}</CardDescription>}
          </div>
        </div>
      </CardHeader>
      <CardContent>{children}</CardContent>
    </Card>
  );
};

interface FormLayoutProps {
  /** Left panel content (info/features) */
  sidebar?: ReactNode;
  /** Form content */
  children: ReactNode;
  /** Additional class names */
  className?: string;
  /** Whether to hide sidebar on mobile */
  sidebarHiddenOnMobile?: boolean;
}

/**
 * FormLayout - Two-column layout for form pages
 * Left: Info/Features, Right: Form
 */
const FormLayout = ({
  sidebar,
  children,
  className,
  sidebarHiddenOnMobile = true,
}: FormLayoutProps) => {
  if (!sidebar) {
    return <div className={cn('space-y-6', className)}>{children}</div>;
  }

  return (
    <div className={cn('grid gap-8 lg:grid-cols-[1fr_2fr]', className)}>
      {/* Sidebar */}
      <div className={cn('space-y-6', sidebarHiddenOnMobile && 'hidden lg:block')}>
        {sidebar}
      </div>

      {/* Form */}
      <div className="space-y-6">{children}</div>
    </div>
  );
};

interface FormSidebarProps {
  /** Sidebar title */
  title: string;
  /** Sidebar description */
  description?: string;
  /** Feature list */
  features?: Array<{
    icon?: IconType;
    title: string;
    description?: string;
  }>;
  /** Additional content */
  children?: ReactNode;
  /** Additional class names */
  className?: string;
}

/**
 * FormSidebar - Info panel for form pages
 */
const FormSidebar = ({
  title,
  description,
  features,
  children,
  className,
}: FormSidebarProps) => {
  return (
    <div className={cn('sticky top-6 space-y-6', className)}>
      <div>
        <h2 className="text-2xl font-bold tracking-tight">{title}</h2>
        {description && <p className="mt-2 text-muted-foreground">{description}</p>}
      </div>

      {features && features.length > 0 && (
        <div className="space-y-4">
          {features.map((feature, index) => {
            const Icon = feature.icon;
            return (
              <div key={index} className="flex gap-3">
                {Icon && (
                  <div className="shrink-0 rounded-lg bg-primary/10 p-2">
                    <Icon className="h-4 w-4 text-primary" />
                  </div>
                )}
                <div>
                  <p className="font-medium">{feature.title}</p>
                  {feature.description && (
                    <p className="text-sm text-muted-foreground">{feature.description}</p>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      )}

      {children}
    </div>
  );
};

interface FormActionsProps {
  /** Primary action button */
  children: ReactNode;
  /** Additional class names */
  className?: string;
  /** Alignment */
  align?: 'left' | 'right' | 'center' | 'between';
}

/**
 * FormActions - Action buttons container for form pages
 */
const FormActions = ({ children, className, align = 'right' }: FormActionsProps) => {
  const alignStyles = {
    left: 'justify-start',
    right: 'justify-end',
    center: 'justify-center',
    between: 'justify-between',
  };

  return (
    <div className={cn('flex items-center gap-3 pt-4', alignStyles[align], className)}>
      {children}
    </div>
  );
};

export {
  FormSection,
  FormLayout,
  FormSidebar,
  FormActions,
  type FormSectionProps,
  type FormLayoutProps,
  type FormSidebarProps,
  type FormActionsProps,
};
