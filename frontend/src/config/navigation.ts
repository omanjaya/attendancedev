import {
  LayoutDashboard,
  Users,
  Clock,
  Calendar,
  CalendarDays,
  Wallet,
  FileText,
  Settings,
  Plane,
  UserCircle,
  BarChart3,
  MapPin,
  Edit3,
  GraduationCap,
} from 'lucide-react';
import type { UserRole, Permission } from '@/types/auth';

// Define LucideIcon type manually to avoid import issues
type LucideIcon = React.ComponentType<{ className?: string; size?: number | string; strokeWidth?: number | string }>;

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

// Admin Navigation
export const adminNavigation: NavGroup[] = [
  {
    title: 'Menu Utama',
    items: [
      {
        title: 'Dashboard',
        href: '/admin/dashboard',
        icon: LayoutDashboard,
        description: 'Ringkasan & statistik perusahaan',
      },
      {
        title: 'Absensi',
        href: '/admin/attendance',
        icon: Clock,
        description: 'Kelola absensi karyawan',
      },
    ],
  },
  {
    title: 'Manajemen',
    items: [
      {
        title: 'Karyawan',
        href: '/admin/employees',
        icon: Users,
        description: 'Data karyawan & kredensial',
      },
      {
        title: 'Lokasi',
        href: '/admin/locations',
        icon: MapPin,
        description: 'Lokasi kerja & GPS verification',
      },
      {
        title: 'Jadwal',
        href: '/admin/schedules',
        icon: Calendar,
        description: 'Jadwal kerja & penugasan',
      },
      {
        title: 'Cuti',
        href: '/admin/leave',
        icon: CalendarDays,
        description: 'Pengajuan & persetujuan cuti',
      },
      {
        title: 'Penggajian',
        href: '/admin/payroll',
        icon: Wallet,
        description: 'Slip gaji & kalkulasi',
      },
      {
        title: 'Laporan',
        href: '/admin/reports',
        icon: FileText,
        description: 'Laporan & report builder',
      },
      {
        title: 'Koreksi Absensi',
        href: '/admin/corrections',
        icon: Edit3,
        description: 'Kelola permintaan koreksi',
      },
    ],
  },
  {
    title: 'Sistem',
    items: [
      {
        title: 'Pengaturan',
        href: '/admin/settings',
        icon: Settings,
        description: 'Lokasi, libur, users & keamanan',
      },
      // Services menu disabled - use Portainer at :9443 for container management
      // {
      //   title: 'Services',
      //   href: '/admin/services',
      //   icon: Server,
      //   description: 'Monitor & kelola system services',
      //   roles: ['super-admin'],
      // },
    ],
  },
];

// Employee Navigation
export const employeeNavigation: NavGroup[] = [
  {
    title: 'Menu Utama',
    items: [
      {
        title: 'Dashboard',
        href: '/employee/dashboard',
        icon: LayoutDashboard,
        description: 'Dashboard pribadi',
      },
      {
        title: 'Absensi',
        href: '/employee/attendance',
        icon: Clock,
        description: 'Riwayat kehadiran',
      },
      {
        title: 'Laporan',
        href: '/employee/reports',
        icon: BarChart3,
        description: 'Laporan kehadiran',
      },
      {
        title: 'Koreksi Absensi',
        href: '/employee/corrections',
        icon: Edit3,
        description: 'Ajukan koreksi absensi',
      },
    ],
  },
  {
    title: 'Pribadi',
    items: [
      {
        title: 'Jadwal Saya',
        href: '/employee/schedule',
        icon: Calendar,
        description: 'Jadwal kerja Anda',
      },
      {
        title: 'Jadwal Mengajar',
        href: '/employee/teaching-schedule',
        icon: GraduationCap,
        description: 'Jadwal mengajar (untuk Guru)',
      },
      {
        title: 'Cuti Saya',
        href: '/employee/leave',
        icon: Plane,
        description: 'Pengajuan cuti',
      },
      {
        title: 'Slip Gaji',
        href: '/employee/payroll',
        icon: Wallet,
        description: 'Riwayat gaji',
      },
      {
        title: 'Profil',
        href: '/employee/profile',
        icon: UserCircle,
        description: 'Profil pribadi',
      },
    ],
  },
];

// Get navigation based on user role
export function getNavigationByRole(userRole: string | undefined): NavGroup[] {
  if (!userRole) return employeeNavigation;

  const normalizedRole = userRole.toLowerCase().replace(/_/g, '-').replace(/ /g, '-');

  // Handle admin roles (super-admin, admin, kepala-sekolah)
  if (['admin', 'super-admin', 'kepala-sekolah'].includes(normalizedRole)) {
    return adminNavigation;
  }

  // Handle employee roles (pegawai, guru)
  if (['pegawai', 'guru', 'employee'].includes(normalizedRole)) {
    return employeeNavigation;
  }

  // Default to employee navigation for unknown roles
  return employeeNavigation;
}

// Filter navigation based on user permissions and roles (legacy function)
export function filterNavigation(
  nav: NavGroup[],
  userPermissions: string[],
  userRole: UserRole
): NavGroup[] {
  const normalizedRole = userRole.toLowerCase().replace(/_/g, '-').replace(/ /g, '-') as UserRole;

  return nav
    .map((group) => ({
      ...group,
      items: group.items.filter((item) => {
        // Super admin sees everything
        if (normalizedRole === 'super-admin') return true;

        // Check role restriction
        if (item.roles && !item.roles.includes(normalizedRole)) return false;

        // Check permission
        if (item.permission && !userPermissions.includes(item.permission)) return false;

        return true;
      }),
    }))
    .filter((group) => group.items.length > 0);
}
