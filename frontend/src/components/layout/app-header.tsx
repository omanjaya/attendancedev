import { Search, Moon, Sun, Menu } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Separator } from '@/components/ui/separator';
import { useUIStore } from '@/stores';
import { NotificationCenter } from '@/components/notifications';
import { RoleSwitcher } from '@/components/dev';

interface AppHeaderProps {
  title?: string;
}

export function AppHeader({ title }: AppHeaderProps) {
  const { theme, toggleTheme } = useUIStore();

  return (
    <header className="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-2 border-b border-border/40 bg-gradient-to-r from-background/98 via-background/95 to-background/98 backdrop-blur-xl supports-[backdrop-filter]:bg-background/60 shadow-sm">
      <div className="flex flex-1 items-center gap-3 px-4 sm:px-6">
        {/* Sidebar Toggle */}
        <SidebarTrigger className="-ml-1 hover:bg-primary/10 hover:text-primary transition-all duration-300 hover:scale-110 active:scale-95 rounded-lg">
          <Menu className="h-5 w-5 text-foreground transition-transform group-hover:rotate-90 duration-300" />
        </SidebarTrigger>

        <Separator orientation="vertical" className="mr-2 h-5 bg-gradient-to-b from-transparent via-border to-transparent" />

        {/* Page Title */}
        {title && (
          <h1 className="hidden text-base font-bold text-foreground sm:block bg-gradient-to-r from-foreground to-foreground/70 bg-clip-text">
            {title}
          </h1>
        )}

        {/* Search - Desktop */}
        <div className="ml-auto hidden max-w-md flex-1 md:flex">
          <div className="relative w-full max-w-sm group">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-foreground/50 group-focus-within:text-primary transition-all duration-300 group-focus-within:scale-110" />
            <Input
              type="search"
              placeholder="Cari..."
              className="w-full pl-9 pr-4 bg-muted/40 border-border/40 focus:bg-background focus:border-primary/40 transition-all duration-300 rounded-xl"
            />
          </div>
        </div>

        {/* Actions */}
        <div className="flex items-center gap-1">
          {/* Search - Mobile */}
          <Button
            variant="ghost"
            size="icon"
            className="md:hidden hover:bg-primary/10 hover:text-primary transition-all duration-300 hover:scale-110 active:scale-95 rounded-xl group"
          >
            <Search className="h-5 w-5 text-foreground group-hover:rotate-12 transition-transform duration-300" />
            <span className="sr-only">Cari</span>
          </Button>

          {/* Role Switcher (Dev Only) */}
          <RoleSwitcher />

          {/* Theme Toggle */}
          <Button
            variant="ghost"
            size="icon"
            onClick={toggleTheme}
            className="relative hover:bg-primary/10 hover:text-primary transition-all duration-300 hover:scale-110 active:scale-95 rounded-xl overflow-hidden group"
          >
            <div className="absolute inset-0 bg-gradient-to-r from-transparent via-primary/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
            {theme === 'dark' ? (
              <Sun className="h-5 w-5 rotate-0 scale-100 transition-all duration-300 dark:rotate-180 dark:scale-0 text-amber-400 group-hover:text-amber-300 relative z-10" />
            ) : (
              <Moon className="h-5 w-5 rotate-180 scale-0 transition-all duration-300 dark:rotate-0 dark:scale-100 text-foreground group-hover:text-slate-900 dark:group-hover:text-blue-300 relative z-10" />
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
