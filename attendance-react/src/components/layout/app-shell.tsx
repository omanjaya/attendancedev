import { Outlet } from '@tanstack/react-router';
import { SidebarProvider, SidebarInset } from '@/components/ui/sidebar';
import { AppSidebar } from './app-sidebar';
import { AppHeader } from './app-header';
import { ToastContainer } from '@/components/notifications';

interface AppShellProps {
  title?: string;
}

export function AppShell({ title }: AppShellProps) {
  return (
    <SidebarProvider>
      <AppSidebar />
      <SidebarInset>
        <AppHeader title={title} />
        <main className="flex-1 overflow-auto">
          <Outlet />
        </main>
      </SidebarInset>
      <ToastContainer />
    </SidebarProvider>
  );
}
