import { Link, useLocation } from '@tanstack/react-router';
import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarFooter,
  SidebarRail,
  useSidebar,
} from '@/components/ui/sidebar';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { LogOut, User, Settings, ChevronUp, Clock } from 'lucide-react';
import { useAuthStore } from '@/stores';
import { getNavigationByRole } from '@/config/navigation';
import { getDefaultRedirect } from '@/lib/auth';
import { cn } from '@/lib/utils';

export function AppSidebar() {
  const location = useLocation();
  const { user, logout } = useAuthStore();
  const { isMobile, setOpenMobile } = useSidebar();

  // Get role-based navigation
  const rawUserRole = user?.role || 'employee';
  const userRole = rawUserRole.toLowerCase().replace(/_/g, '-').replace(/ /g, '-');
  const navigation = getNavigationByRole(userRole);

  const handleLogout = async () => {
    await logout();
    // Hard redirect to login page to ensure clean state
    window.location.href = '/login';
  };

  // Get user initials for avatar
  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  return (
    <Sidebar collapsible="icon" className="border-r border-border/40 bg-gradient-to-b from-sidebar/95 via-sidebar/90 to-sidebar/95 backdrop-blur-2xl shadow-lg">

      {/* Header */}
      <SidebarHeader className="pb-4 pt-6 px-2">
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size="lg" asChild className="hover:bg-primary/10 transition-all duration-300 hover:scale-[1.02] group">
              <Link
                to={getDefaultRedirect(user)}
                onClick={() => isMobile && setOpenMobile(false)}
              >
                <div className="flex aspect-square size-10 items-center justify-center rounded-xl bg-gradient-to-br from-primary via-emerald-500 to-emerald-600 text-primary-foreground shadow-lg shadow-primary/30 group-hover:shadow-xl group-hover:shadow-primary/40 transition-all duration-300">
                  <Clock className="size-5 group-hover:rotate-12 transition-transform duration-300" />
                </div>
                <div className="grid flex-1 text-left text-sm leading-tight">
                  <span className="truncate font-bold text-base tracking-tight bg-gradient-to-r from-foreground to-foreground/80 bg-clip-text">
                    Attendance
                  </span>
                  <span className="truncate text-xs text-muted-foreground/80 font-medium">
                    Management System
                  </span>
                </div>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>

      {/* Navigation Content */}
      <SidebarContent className="px-2 py-2">
        {navigation.map((group) => (
          <SidebarGroup key={group.title} className="py-3">
            <SidebarGroupLabel className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60 px-4 mb-3 flex items-center gap-2">
              <div className="h-px flex-1 bg-gradient-to-r from-transparent via-border/40 to-transparent" />
              <span>{group.title}</span>
              <div className="h-px flex-1 bg-gradient-to-r from-transparent via-border/40 to-transparent" />
            </SidebarGroupLabel>
            <SidebarGroupContent>
              <SidebarMenu>
                {group.items.map((item) => {
                  const isActive = location.pathname === item.href ||
                    location.pathname.startsWith(item.href + '/');

                  return (
                    <SidebarMenuItem key={item.href}>
                      <SidebarMenuButton
                        asChild
                        isActive={isActive}
                        tooltip={item.title}
                        className={cn(
                          'transition-all duration-300 rounded-xl px-3 py-2.5 mb-0.5 group relative overflow-hidden',
                          isActive
                            ? 'bg-gradient-to-r from-primary to-emerald-600 text-primary-foreground shadow-lg shadow-primary/25 font-semibold scale-[1.02]'
                            : 'hover:bg-gradient-to-r hover:from-primary/10 hover:to-emerald-600/10 hover:text-primary text-muted-foreground hover:scale-[1.01] hover:shadow-sm'
                        )}
                      >
                        <Link
                          to={item.href}
                          onClick={() => isMobile && setOpenMobile(false)}
                        >
                          {isActive && (
                            <div className="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                          )}
                          <item.icon className={cn(
                            'size-5 mr-3 transition-all duration-300',
                            isActive ? 'text-primary-foreground scale-110' : 'text-muted-foreground group-hover:text-primary group-hover:scale-110'
                          )} />
                          <span className="relative">{item.title}</span>
                          {item.badge && (
                            <span className={cn(
                              "ml-auto rounded-full px-2 py-0.5 text-[10px] font-bold transition-all duration-300",
                              isActive
                                ? "bg-white/25 text-white shadow-sm"
                                : "bg-primary/15 text-primary group-hover:bg-primary/25"
                            )}>
                              {item.badge}
                            </span>
                          )}
                        </Link>
                      </SidebarMenuButton>
                    </SidebarMenuItem>
                  );
                })}
              </SidebarMenu>
            </SidebarGroupContent>
          </SidebarGroup>
        ))}
      </SidebarContent>

      {/* Footer - User Menu */}
      <SidebarFooter className="p-3">
        <SidebarMenu>
          <SidebarMenuItem>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <SidebarMenuButton
                  size="lg"
                  className="data-[state=open]:bg-gradient-to-r data-[state=open]:from-primary/10 data-[state=open]:to-emerald-600/10 hover:bg-gradient-to-r hover:from-primary/5 hover:to-emerald-600/5 rounded-xl border border-border/40 hover:border-primary/20 transition-all duration-300 hover:scale-[1.02] group shadow-sm"
                >
                  <Avatar className="h-10 w-10 rounded-xl border-2 border-background shadow-md ring-2 ring-primary/10 group-hover:ring-primary/20 transition-all duration-300">
                    <AvatarImage src={user?.avatar_url} alt={user?.name} />
                    <AvatarFallback className="rounded-xl bg-gradient-to-br from-primary via-emerald-500 to-emerald-600 text-primary-foreground font-bold text-sm">
                      {user?.name ? getInitials(user.name) : 'U'}
                    </AvatarFallback>
                  </Avatar>
                  <div className="grid flex-1 text-left text-sm leading-tight ml-2">
                    <span className="truncate font-bold text-foreground group-hover:text-primary transition-colors">{user?.name}</span>
                    <span className="truncate text-xs text-muted-foreground/80 capitalize font-medium">
                      {user?.role?.replace('-', ' ')}
                    </span>
                  </div>
                  <ChevronUp className="ml-auto size-4 text-muted-foreground group-hover:text-primary transition-all duration-300 group-hover:-translate-y-0.5" />
                </SidebarMenuButton>
              </DropdownMenuTrigger>
              <DropdownMenuContent
                className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-xl glass-card border-white/20 shadow-xl"
                side="top"
                align="end"
                sideOffset={8}
              >
                <DropdownMenuLabel className="p-0 font-normal">
                  <div className="flex items-center gap-3 px-3 py-2.5 text-left text-sm bg-muted/30">
                    <Avatar className="h-9 w-9 rounded-lg border border-white/10">
                      <AvatarImage src={user?.avatar_url} alt={user?.name} />
                      <AvatarFallback className="rounded-lg bg-primary text-primary-foreground font-bold">
                        {user?.name ? getInitials(user.name) : 'U'}
                      </AvatarFallback>
                    </Avatar>
                    <div className="grid flex-1 text-left text-sm leading-tight">
                      <span className="truncate font-bold">{user?.name}</span>
                      <span className="truncate text-xs text-muted-foreground">
                        {user?.email}
                      </span>
                    </div>
                  </div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator className="bg-border/50" />
                <DropdownMenuItem asChild className="focus:bg-primary/10 focus:text-primary cursor-pointer">
                  <Link
                    to={userRole === 'admin' || userRole === 'super-admin' || userRole === 'kepala-sekolah' ? '/admin/dashboard' : '/employee/profile'}
                    onClick={() => isMobile && setOpenMobile(false)}
                  >
                    <User className="mr-2 h-4 w-4" />
                    Profil
                  </Link>
                </DropdownMenuItem>
                {(userRole === 'admin' || userRole === 'super-admin' || userRole === 'kepala-sekolah') && (
                  <DropdownMenuItem asChild className="focus:bg-primary/10 focus:text-primary cursor-pointer">
                    <Link
                      to="/admin/settings"
                      onClick={() => isMobile && setOpenMobile(false)}
                    >
                      <Settings className="mr-2 h-4 w-4" />
                      Pengaturan
                    </Link>
                  </DropdownMenuItem>
                )}
                <DropdownMenuSeparator className="bg-border/50" />
                <DropdownMenuItem onClick={handleLogout} className="text-destructive focus:bg-destructive/10 focus:text-destructive cursor-pointer">
                  <LogOut className="mr-2 h-4 w-4" />
                  Keluar
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarFooter>

      <SidebarRail />
    </Sidebar>
  );
}
