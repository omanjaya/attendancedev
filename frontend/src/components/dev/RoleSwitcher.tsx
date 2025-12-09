import { UserCog, Shield, Building, BookOpen, Users, RotateCcw } from 'lucide-react';
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

  // Only show in development mode (any role can use it)
  if (!import.meta.env.DEV) {
    return null;
  }

  const handleRoleChange = (role: UserRole) => {
    const mockUser = mockUsers[role];

    const newUser = {
      id: Math.floor(Math.random() * 1000), // Random ID
      name: mockUser.name,
      email: mockUser.email,
      role: role,
      permissions: mockUser.permissions,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    };

    setUser(newUser);

    // Persist to localStorage for survival across refreshes
    localStorage.setItem('dev_mock_role', role);

    // Force reload to apply new role permissions
    window.location.reload();
  };

  const currentRole = user?.role || 'pegawai';
  const CurrentIcon = mockUsers[currentRole]?.icon || Users;

  const handleReset = () => {
    localStorage.removeItem('dev_mock_role');
    window.location.reload();
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="outline"
          size="sm"
          className="gap-2 border-dashed border-warning/50 text-warning hover:bg-warning/10 hover:text-warning hover:scale-105 transition-all duration-300 shadow-sm hover:shadow-warning/20"
        >
          <CurrentIcon className="h-4 w-4 animate-pulse" />
          <span className="hidden sm:inline font-semibold">{roleLabels[currentRole]}</span>
          <Badge variant="outline" className="ml-1 border-warning/50 text-warning text-xs font-bold animate-pulse">
            DEV
          </Badge>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-64">
        <DropdownMenuLabel className="flex items-center gap-2">
          <UserCog className="h-4 w-4 text-warning" />
          <div className="flex flex-col">
            <span className="font-semibold">Role Switcher</span>
            <span className="text-xs text-muted-foreground font-normal">Development Mode</span>
          </div>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />

        {(Object.keys(mockUsers) as UserRole[]).map((role) => {
          const { icon: Icon, permissions } = mockUsers[role];
          const isActive = currentRole === role;

          return (
            <DropdownMenuItem
              key={role}
              onClick={() => handleRoleChange(role)}
              className={`cursor-pointer transition-all ${
                isActive
                  ? 'bg-primary/10 text-primary font-semibold'
                  : 'hover:bg-muted'
              }`}
            >
              <Icon className={`mr-2 h-4 w-4 ${isActive ? 'text-primary' : ''}`} />
              <div className="flex-1">
                <div className="flex items-center gap-2">
                  <span>{roleLabels[role]}</span>
                  {isActive && (
                    <Badge variant="secondary" className="text-xs">
                      Active
                    </Badge>
                  )}
                </div>
                <div className="text-xs text-muted-foreground">
                  {permissions[0] === '*'
                    ? 'All permissions'
                    : `${permissions.length} permissions`}
                </div>
              </div>
            </DropdownMenuItem>
          );
        })}

        <DropdownMenuSeparator />

        {/* Reset Button */}
        <DropdownMenuItem
          onClick={handleReset}
          className="text-destructive hover:bg-destructive/10 cursor-pointer"
        >
          <RotateCcw className="mr-2 h-4 w-4" />
          <span>Reset to Default</span>
        </DropdownMenuItem>

        <DropdownMenuSeparator />
        <div className="px-2 py-2 text-xs text-muted-foreground space-y-1">
          <div className="flex items-center gap-1">
            <span className="font-semibold">💡 Tip:</span>
            <span>Role persists on refresh</span>
          </div>
          <div className="text-[10px] opacity-70">
            Testing UI dengan permission berbeda
          </div>
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
