import { Link, useLocation } from '@tanstack/react-router';
import { Home, Clock, Users, Calendar, MoreHorizontal, FileText } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useAuthStore } from '@/stores';

interface NavItem {
  icon: typeof Home;
  label: string;
  href: string;
  roles?: string[];
}

const navItems: NavItem[] = [
  {
    icon: Home,
    label: 'Home',
    href: '/dashboard',
    roles: ['super-admin', 'admin', 'kepala-sekolah'],
  },
  {
    icon: Clock,
    label: 'Absensi',
    href: '/attendance',
  },
  {
    icon: FileText,
    label: 'Laporan',
    href: '/reports',
    roles: ['pegawai', 'guru'],
  },
  {
    icon: Calendar,
    label: 'Jadwal',
    href: '/schedules',
    roles: ['super-admin', 'admin', 'kepala-sekolah'],
  },
  {
    icon: Users,
    label: 'Karyawan',
    href: '/employees',
    roles: ['super-admin', 'admin', 'kepala-sekolah'],
  },
  {
    icon: MoreHorizontal,
    label: 'Lainnya',
    href: '/settings',
    roles: ['super-admin', 'admin', 'kepala-sekolah'],
  },
];

export function BottomNav() {
  const location = useLocation();
  const { user } = useAuthStore();

  const filteredItems = navItems.filter((item) => {
    // If no role restriction, show to everyone
    if (!item.roles) return true;

    // Check if user has required role
    return user?.role && item.roles.includes(user.role);
  });

  if (filteredItems.length === 0) return null;

  return (
    <nav className="fixed bottom-0 left-0 right-0 z-50 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 md:hidden">
      <div className="flex h-16 items-center justify-around px-2">
        {filteredItems.map((item) => {
          const Icon = item.icon;
          const isActive =
            location.pathname === item.href ||
            location.pathname.startsWith(item.href + '/');

          return (
            <Link
              key={item.href}
              to={item.href}
              className={cn(
                'flex flex-col items-center justify-center gap-1 rounded-lg px-3 py-2 text-xs font-medium transition-colors',
                'min-w-[60px]',
                'active:scale-95',
                isActive
                  ? 'text-primary'
                  : 'text-muted-foreground hover:text-foreground'
              )}
            >
              <Icon
                className={cn(
                  'h-5 w-5 transition-transform',
                  isActive && 'scale-110'
                )}
              />
              <span className="truncate">{item.label}</span>
            </Link>
          );
        })}
      </div>
    </nav>
  );
}
