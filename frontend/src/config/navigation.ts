import {
  LayoutDashboard,
  Users,
  Clock,
  Calendar,
  CalendarDays,
  Wallet,
  FileText,
  Settings,
  type LucideIcon,
} from 'lucide-react';
import type { UserRole, Permission } from '@/types/auth';

export interface NavItem {
  title: string;
  href: string;
  icon: LucideIcon;
  permission?: Permission;
  roles?: UserRole[];
  badge?: string | number;
  description?: string;
}

export interface NavGroup {
  title: string;
  items: NavItem[];
}

// Simplified navigation - features consolidated into pages with tabs
export const navigation: NavGroup[] = [
  {
    title: 'Menu Utama',
    items: [
      {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutDashboard,
        permission: 'dashboard.view',
        description: 'Ringkasan & statistik',
      },
      {
        title: 'Absensi',
        href: '/attendance',
        icon: Clock,
        permission: 'attendance.view',
        description: 'Kehadiran & Face Recognition',
      },
    ],
  },
  {
    title: 'Manajemen',
    items: [
      {
        title: 'Karyawan',
        href: '/employees',
        icon: Users,
        permission: 'employees.view',
        roles: ['super-admin', 'admin', 'kepala-sekolah'],
        description: 'Data karyawan & kredensial',
      },
      {
        title: 'Jadwal',
        href: '/schedules',
        icon: Calendar,
        permission: 'schedules.view',
        description: 'Jadwal kerja & penugasan',
      },
      {
        title: 'Cuti',
        href: '/leave',
        icon: CalendarDays,
        permission: 'leave.view',
        description: 'Pengajuan & persetujuan cuti',
      },
      {
        title: 'Penggajian',
        href: '/payroll',
        icon: Wallet,
        permission: 'payroll.view',
        description: 'Slip gaji & kalkulasi',
      },
      {
        title: 'Laporan',
        href: '/reports',
        icon: FileText,
        permission: 'view_attendance_reports',
        roles: ['super-admin', 'admin', 'kepala-sekolah', 'pegawai', 'guru'],
        description: 'Laporan & report builder',
      },
    ],
  },
  {
    title: 'Sistem',
    items: [
      {
        title: 'Pengaturan',
        href: '/settings',
        icon: Settings,
        roles: ['super-admin', 'admin'],
        description: 'Lokasi, libur, users & keamanan',
      },
    ],
  },
];

// Filter navigation based on user permissions and roles
export function filterNavigation(
  nav: NavGroup[],
  userPermissions: string[],
  userRole: UserRole
): NavGroup[] {
  return nav
    .map((group) => ({
      ...group,
      items: group.items.filter((item) => {
        // Super admin sees everything
        if (userRole === 'super-admin') return true;

        // Check role restriction
        if (item.roles && !item.roles.includes(userRole)) return false;

        // Check permission
        if (item.permission && !userPermissions.includes(item.permission)) return false;

        return true;
      }),
    }))
    .filter((group) => group.items.length > 0);
}
