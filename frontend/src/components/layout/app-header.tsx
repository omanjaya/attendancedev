import { useState, useEffect } from 'react';
import { Search, Moon, Sun, Menu, Command } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Separator } from '@/components/ui/separator';
import { useUIStore } from '@/stores';
import { NotificationCenter } from '@/components/notifications';
import { RoleSwitcher } from '@/components/dev';
import { CommandPalette } from '@/components/search';

interface AppHeaderProps {
  title?: string;
}

export function AppHeader({ title }: AppHeaderProps) {
  const { toggleTheme } = useUIStore();
  const [commandPaletteOpen, setCommandPaletteOpen] = useState(false);

  // Listen for Cmd+K / Ctrl+K to open command palette
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        setCommandPaletteOpen(true);
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, []);

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
          <button
            onClick={() => setCommandPaletteOpen(true)}
            className="relative w-full max-w-sm group"
          >
            <div className="flex items-center w-full px-3 py-2 bg-muted/40 border border-border/40 rounded-xl hover:bg-background hover:border-primary/40 transition-all duration-300 text-left">
              <Search className="h-4 w-4 text-foreground/50 mr-2 group-hover:text-primary transition-all duration-300 group-hover:scale-110" />
              <span className="text-sm text-muted-foreground flex-1">Cari...</span>
              <kbd className="hidden sm:inline-flex items-center gap-1 px-2 py-1 text-xs font-mono bg-background border rounded shadow-sm">
                <Command className="h-3 w-3" />
                K
              </kbd>
            </div>
          </button>
        </div>

        {/* Actions */}
        <div className="flex items-center gap-1">
          {/* Search - Mobile */}
          <Button
            variant="ghost"
            size="icon"
            onClick={() => setCommandPaletteOpen(true)}
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
            <Sun className="h-5 w-5 rotate-0 scale-100 transition-all duration-300 dark:-rotate-90 dark:scale-0 text-amber-500 dark:text-amber-400" />
            <Moon className="absolute h-5 w-5 rotate-90 scale-0 transition-all duration-300 dark:rotate-0 dark:scale-100 text-slate-700 dark:text-blue-300" />
            <span className="sr-only">Toggle theme</span>
          </Button>

          {/* Notifications */}
          <NotificationCenter />
        </div>
      </div>

      {/* Command Palette */}
      <CommandPalette open={commandPaletteOpen} onOpenChange={setCommandPaletteOpen} />
    </header>
  );
}
