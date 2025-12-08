import { UserCog, Shield, Building, BookOpen, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { useAuthStore } from '@/stores';
import type { UserRole } from '@/types/auth';

// Mock users for each role
const mockUsers: Record<UserRole, {
  name: string;
  email: string;
  icon: typeof Shield;
  permissions: string[];
}> = {
  'super-admin': {
    name: 'Super Admin',
    email: 'superadmin@sekolah.com',
    icon: Shield,
    permissions: ['*'], // All permissions
  },
  'admin': {
    name: 'Admin Sekolah',
    email: 'admin@sekolah.com',
    icon: UserCog,
    permissions: [
      'dashboard.view',
      'employees.view',
      'employees.create',
      'employees.edit',
      'attendance.view',
      'attendance.create',
      'leave.view',
      'leave.approve',
      'schedules.view',
      'schedules.create',
      'payroll.view',
      'reports.view',
      'settings.view',
    ],
  },
  'kepala-sekolah': {
    name: 'Kepala Sekolah',
    email: 'kepala@sekolah.com',
    icon: Building,
    permissions: [
      'dashboard.view',
      'employees.view',
      'attendance.view',
      'leave.view',
      'leave.approve',
      'schedules.view',
      'payroll.view',
      'reports.view',
    ],
  },
  'guru': {
    name: 'Guru/Pengajar',
    email: 'guru@sekolah.com',
    icon: BookOpen,
    permissions: [
      'dashboard.view',
      'attendance.view',
      'leave.view',
      'leave.create',
      'schedules.view',
    ],
  },
  'pegawai': {
    name: 'Pegawai',
    email: 'pegawai@sekolah.com',
    icon: Users,
    permissions: [
      'dashboard.view',
      'attendance.view',
      'leave.view',
      'leave.create',
      'schedules.view',
    ],
  },
};

// Role labels in Indonesian
const roleLabels: Record<UserRole, string> = {
  'super-admin': 'Super Admin',
  'admin': 'Admin',
  'kepala-sekolah': 'Kepala Sekolah',
  'guru': 'Guru',
  'pegawai': 'Pegawai',
};

export function RoleSwitcher() {
  const { user, setUser } = useAuthStore();

  // Only show in development AND only for super-admin
  if (!import.meta.env.DEV || user?.role !== 'super-admin') {
    return null;
  }

  const handleRoleChange = (role: UserRole) => {
    const mockUser = mockUsers[role];


    setUser({
      id: Math.floor(Math.random() * 1000), // Random ID
      name: mockUser.name,
      email: mockUser.email,
      role: role,
      permissions: mockUser.permissions,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    });
  };

  const currentRole = user?.role || 'pegawai';
  const CurrentIcon = mockUsers[currentRole]?.icon || Users;

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="outline"
          size="sm"
          className="gap-2 border-dashed border-warning/50 text-warning hover:bg-warning/10 hover:text-warning transition-all duration-300"
        >
          <CurrentIcon className="h-4 w-4" />
          <span className="hidden sm:inline">{roleLabels[currentRole]}</span>
          <Badge variant="outline" className="ml-1 border-warning/50 text-warning text-xs">
            DEV
          </Badge>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-56">
        <DropdownMenuLabel className="flex items-center gap-2">
          <UserCog className="h-4 w-4 text-warning" />
          <span>Switch Role (Dev)</span>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />

        {(Object.keys(mockUsers) as UserRole[]).map((role) => {
          const { icon: Icon } = mockUsers[role];
          const isActive = currentRole === role;

          return (
            <DropdownMenuItem
              key={role}
              onClick={() => handleRoleChange(role)}
              className={isActive ? 'bg-primary/10 text-primary' : ''}
            >
              <Icon className="mr-2 h-4 w-4" />
              <span className="flex-1">{roleLabels[role]}</span>
              {isActive && (
                <Badge variant="secondary" className="ml-2 text-xs">
                  Active
                </Badge>
              )}
            </DropdownMenuItem>
          );
        })}

        <DropdownMenuSeparator />
        <div className="px-2 py-1.5 text-xs text-muted-foreground">
          Untuk testing UI dengan role berbeda
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
