import { Outlet } from '@tanstack/react-router';
import { SidebarProvider, SidebarInset } from '@/components/ui/sidebar';
import { AppSidebar } from './app-sidebar';
import { AppHeader } from './app-header';
import { BottomNav } from './bottom-nav';
import { ToastContainer } from '@/components/notifications';

interface AppShellProps {
  title?: string;
}

export function AppShell({ title }: AppShellProps) {
  return (
    <SidebarProvider>
      {/* Global Background Gradient */}
      <div className="fixed inset-0 -z-50 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-primary/10 via-background to-background" />
      <AppSidebar />
      <SidebarInset>
        <AppHeader title={title} />
        <main className="flex-1 overflow-auto pb-16 md:pb-0">
          <Outlet />
        </main>
      </SidebarInset>
      {/* Bottom Navigation - Mobile Only */}
      <BottomNav />
      <ToastContainer />
    </SidebarProvider>
  );
}
