import { useMemo } from 'react';
import {
  Bell,
  BellOff,
  Check,
  CheckCheck,
  Trash2,
  Clock,
  Calendar,
  DollarSign,
  Shield,
  X,
  ExternalLink,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet';
import {
  useNotificationStore,
  notificationCategoryLabels,
  type PersistentNotification,
  type NotificationCategory,
} from '@/stores/notification-store';

const categoryIcons: Record<NotificationCategory, typeof Bell> = {
  attendance: Clock,
  leave: Calendar,
  schedule: Calendar,
  payroll: DollarSign,
  security: Shield,
  system: Bell,
};

const typeColors = {
  success: 'bg-success/10 text-success',
  error: 'bg-destructive/10 text-destructive',
  warning: 'bg-warning/10 text-warning',
  info: 'bg-primary/10 text-primary',
};

function NotificationItem({
  notification,
  onMarkAsRead,
  onRemove,
}: {
  notification: PersistentNotification;
  onMarkAsRead: (id: string) => void;
  onRemove: (id: string) => void;
}) {
  const Icon = categoryIcons[notification.category] || Bell;
  const timeAgo = getTimeAgo(notification.created_at);

  return (
    <div
      className={cn(
        'p-4 border-b last:border-b-0 transition-colors hover:bg-muted/30',
        !notification.read && 'bg-primary/5'
      )}
    >
      <div className="flex items-start gap-3">
        <div className={cn('p-2 rounded-lg transition-all duration-300', typeColors[notification.type])}>
          <Icon className="h-4 w-4" />
        </div>
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2">
            <p className={cn(
              'text-sm',
              !notification.read ? 'font-semibold text-foreground' : 'text-muted-foreground'
            )}>
              {notification.title}
            </p>
            {!notification.read && (
              <span className="h-2 w-2 rounded-full bg-primary flex-shrink-0 animate-pulse shadow-sm shadow-primary/50" />
            )}
          </div>
          <p className="text-sm text-muted-foreground mt-1 line-clamp-2">
            {notification.message}
          </p>
          <div className="flex items-center gap-2 mt-2">
            <Badge variant="outline" className="text-xs">
              {notificationCategoryLabels[notification.category]}
            </Badge>
            <span className="text-xs text-muted-foreground">{timeAgo}</span>
          </div>
          {notification.action_url && (
            <a
              href={notification.action_url}
              className="inline-flex items-center gap-1 text-xs text-primary hover:underline mt-2 transition-colors"
            >
              {notification.action_label || 'Lihat Detail'}
              <ExternalLink className="h-3 w-3" />
            </a>
          )}
        </div>
        <div className="flex flex-col gap-1">
          {!notification.read && (
            <button
              onClick={() => onMarkAsRead(notification.id)}
              className="p-1.5 rounded hover:bg-success/10 text-muted-foreground hover:text-success transition-all duration-300 group"
              title="Tandai sudah dibaca"
            >
              <Check className="h-4 w-4 transition-colors" />
            </button>
          )}
          <button
            onClick={() => onRemove(notification.id)}
            className="p-1.5 rounded hover:bg-destructive/10 text-muted-foreground hover:text-destructive transition-all duration-300 group"
            title="Hapus notifikasi"
          >
            <X className="h-4 w-4 transition-colors" />
          </button>
        </div>
      </div>
    </div>
  );
}

function getTimeAgo(dateString: string): string {
  const date = new Date(dateString);
  const now = new Date();
  const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

  if (diffInSeconds < 60) return 'Baru saja';
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} menit lalu`;
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} jam lalu`;
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} hari lalu`;
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}

export function NotificationCenter() {
  const {
    notifications,
    isNotificationCenterOpen,
    setNotificationCenterOpen,
    markAsRead,
    markAllAsRead,
    removeNotification,
    clearAllNotifications,
    getUnreadCount,
  } = useNotificationStore();

  const unreadCount = getUnreadCount();

  const groupedNotifications = useMemo(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    const groups: { title: string; notifications: PersistentNotification[] }[] = [
      { title: 'Hari Ini', notifications: [] },
      { title: 'Kemarin', notifications: [] },
      { title: 'Sebelumnya', notifications: [] },
    ];

    notifications.forEach((notification) => {
      const notifDate = new Date(notification.created_at);
      notifDate.setHours(0, 0, 0, 0);

      if (notifDate.getTime() === today.getTime()) {
        groups[0].notifications.push(notification);
      } else if (notifDate.getTime() === yesterday.getTime()) {
        groups[1].notifications.push(notification);
      } else {
        groups[2].notifications.push(notification);
      }
    });

    return groups.filter((g) => g.notifications.length > 0);
  }, [notifications]);

  return (
    <Sheet open={isNotificationCenterOpen} onOpenChange={setNotificationCenterOpen}>
      <SheetTrigger asChild>
        <Button
          variant="ghost"
          size="icon"
          className="relative hover:bg-primary/10 hover:text-primary transition-all duration-300 hover:scale-110 active:scale-95 rounded-xl group"
        >
          <Bell className={cn(
            "h-5 w-5 transition-all duration-300",
            unreadCount > 0 ? "animate-pulse text-primary" : "text-foreground"
          )} />
          {unreadCount > 0 && (
            <>
              <span className="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-gradient-to-br from-destructive to-destructive/80 text-destructive-foreground text-xs font-bold flex items-center justify-center shadow-lg shadow-destructive/30 ring-2 ring-background animate-pulse">
                {unreadCount > 99 ? '99+' : unreadCount}
              </span>
              <span className="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-destructive animate-ping opacity-75" />
            </>
          )}
        </Button>
      </SheetTrigger>
      <SheetContent className="w-full sm:max-w-md p-0">
        <SheetHeader className="p-4 border-b">
          <div className="flex items-center justify-between">
            <SheetTitle className="flex items-center gap-2">
              <Bell className="h-5 w-5" />
              Notifikasi
              {unreadCount > 0 && (
                <Badge variant="secondary">{unreadCount} baru</Badge>
              )}
            </SheetTitle>
            <div className="flex items-center gap-1">
              {unreadCount > 0 && (
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={markAllAsRead}
                  title="Tandai semua sudah dibaca"
                >
                  <CheckCheck className="h-4 w-4" />
                </Button>
              )}
              {notifications.length > 0 && (
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={clearAllNotifications}
                  title="Hapus semua notifikasi"
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              )}
            </div>
          </div>
        </SheetHeader>

        <ScrollArea className="h-[calc(100vh-80px)]">
          {notifications.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-12 text-center">
              <div className="p-4 rounded-full bg-muted/50 mb-4 transition-all duration-300 hover:bg-muted">
                <BellOff className="h-8 w-8 text-muted-foreground" />
              </div>
              <p className="text-foreground font-medium">Tidak ada notifikasi</p>
              <p className="text-xs text-muted-foreground mt-1">Anda akan melihat notifikasi di sini</p>
            </div>
          ) : (
            <div>
              {groupedNotifications.map((group) => (
                <div key={group.title}>
                  <div className="sticky top-0 px-4 py-2 bg-muted/80 backdrop-blur-md border-b border-border/50 z-10">
                    <p className="text-xs font-bold text-muted-foreground uppercase tracking-wider">
                      {group.title}
                    </p>
                  </div>
                  {group.notifications.map((notification) => (
                    <NotificationItem
                      key={notification.id}
                      notification={notification}
                      onMarkAsRead={markAsRead}
                      onRemove={removeNotification}
                    />
                  ))}
                </div>
              ))}
            </div>
          )}
        </ScrollArea>
      </SheetContent>
    </Sheet>
  );
}
