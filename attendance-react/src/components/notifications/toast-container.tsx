import { X, CheckCircle, AlertCircle, AlertTriangle, Info } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useNotificationStore, type ToastNotification } from '@/stores/notification-store';

const icons = {
  success: CheckCircle,
  error: AlertCircle,
  warning: AlertTriangle,
  info: Info,
};

const colors = {
  success: 'bg-success/10 border-success/20',
  error: 'bg-destructive/10 border-destructive/20',
  warning: 'bg-warning/10 border-warning/20',
  info: 'bg-primary/10 border-primary/20',
};

const iconColors = {
  success: 'text-success',
  error: 'text-destructive',
  warning: 'text-warning',
  info: 'text-primary',
};

function Toast({ toast }: { toast: ToastNotification }) {
  const removeToast = useNotificationStore((state) => state.removeToast);
  const Icon = icons[toast.type];

  return (
    <div
      className={cn(
        'flex items-start gap-3 p-4 rounded-lg border shadow-lg animate-in slide-in-from-right-full duration-300',
        colors[toast.type]
      )}
    >
      <Icon className={cn('h-5 w-5 flex-shrink-0 mt-0.5', iconColors[toast.type])} />
      <div className="flex-1 min-w-0">
        <p className="text-sm font-medium text-foreground">{toast.title}</p>
        {toast.message && (
          <p className="text-sm text-muted-foreground mt-1">{toast.message}</p>
        )}
      </div>
      {toast.dismissible && (
        <button
          onClick={() => removeToast(toast.id)}
          className="flex-shrink-0 p-1 rounded hover:bg-black/5 dark:hover:bg-white/5 transition-colors"
        >
          <X className="h-4 w-4 text-muted-foreground" />
        </button>
      )}
    </div>
  );
}

export function ToastContainer() {
  const toasts = useNotificationStore((state) => state.toasts);

  if (toasts.length === 0) return null;

  return (
    <div className="fixed bottom-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full">
      {toasts.map((toast) => (
        <Toast key={toast.id} toast={toast} />
      ))}
    </div>
  );
}
