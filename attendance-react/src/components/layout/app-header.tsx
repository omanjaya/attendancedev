import { Search, Moon, Sun, Menu } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Separator } from '@/components/ui/separator';
import { useUIStore } from '@/stores';
import { NotificationCenter } from '@/components/notifications';

interface AppHeaderProps {
  title?: string;
}

export function AppHeader({ title }: AppHeaderProps) {
  const { theme, toggleTheme } = useUIStore();

  return (
    <header className="sticky top-0 z-40 flex h-14 shrink-0 items-center gap-2 border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
      <div className="flex flex-1 items-center gap-2 px-4">
        {/* Sidebar Toggle */}
        <SidebarTrigger className="-ml-1">
          <Menu className="h-5 w-5" />
        </SidebarTrigger>

        <Separator orientation="vertical" className="mr-2 h-4" />

        {/* Page Title */}
        {title && (
          <h1 className="hidden text-macos-base font-medium text-foreground sm:block">
            {title}
          </h1>
        )}

        {/* Search - Desktop */}
        <div className="ml-auto hidden max-w-md flex-1 md:flex">
          <div className="relative w-full max-w-sm">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              type="search"
              placeholder="Cari..."
              className="w-full pl-9 pr-4"
            />
          </div>
        </div>

        {/* Actions */}
        <div className="flex items-center gap-1">
          {/* Search - Mobile */}
          <Button variant="ghost" size="icon" className="md:hidden">
            <Search className="h-5 w-5" />
            <span className="sr-only">Cari</span>
          </Button>

          {/* Theme Toggle */}
          <Button variant="ghost" size="icon" onClick={toggleTheme}>
            {theme === 'dark' ? (
              <Sun className="h-5 w-5" />
            ) : (
              <Moon className="h-5 w-5" />
            )}
            <span className="sr-only">Toggle theme</span>
          </Button>

          {/* Notifications */}
          <NotificationCenter />
        </div>
      </div>
    </header>
  );
}
