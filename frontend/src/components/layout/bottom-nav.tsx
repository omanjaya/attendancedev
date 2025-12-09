import { Link, useLocation } from '@tanstack/react-router';
import { Home, Clock, Users, MoreHorizontal, FileText } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useAuthStore } from '@/stores';

interface NavItem {
  icon: typeof Home;
  label: string;
  href: string;
}

// Admin bottom navigation
const adminNavItems: NavItem[] = [
  {
    icon: Home,
    label: 'Home',
    href: '/admin/dashboard',
  },
  {
    icon: Clock,
    label: 'Absensi',
    href: '/admin/attendance',
  },
  {
    icon: Users,
    label: 'Karyawan',
    href: '/admin/employees',
  },
  {
    icon: FileText,
    label: 'Laporan',
    href: '/admin/reports',
  },
  {
    icon: MoreHorizontal,
    label: 'Lainnya',
    href: '/admin/settings',
  },
];

// Employee bottom navigation - Only Absensi and Laporan
// Other menu items (Home, Jadwal, Cuti, Profil) are available in sidebar
const employeeNavItems: NavItem[] = [
  {
    icon: Clock,
    label: 'Absensi',
    href: '/employee/attendance', // Status page with Datang/Pulang buttons
  },
  {
    icon: FileText,
    label: 'Laporan',
    href: '/employee/reports',
  },
];

export function BottomNav() {
  const location = useLocation();
  const { user } = useAuthStore();

  // Get role-based navigation items
  const userRole = user?.role || 'employee';
  const navItems =
    userRole === 'admin' || userRole === 'super-admin' || userRole === 'kepala-sekolah'
      ? adminNavItems
      : employeeNavItems;

  if (!user) return null;

  return (
    <nav className="fixed bottom-0 left-0 right-0 z-50 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 md:hidden">
      <div className="flex h-16 items-center justify-around px-2">
        {navItems.map((item) => {
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
