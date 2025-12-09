import { useEffect, useState, useMemo } from 'react';
import { useNavigate } from '@tanstack/react-router';
import {
  Search,
  Home,
  Users,
  Clock,
  Calendar,
  FileText,
  Settings,
  MapPin,
  DollarSign,
  LayoutGrid,
  TrendingUp,
  User,
  CalendarOff,
  Briefcase,
  Building2,
  X,
} from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { useAuthStore } from '@/stores';

interface SearchItem {
  id: string;
  title: string;
  description?: string;
  href: string;
  icon: typeof Home;
  category: 'navigation' | 'action' | 'recent';
  keywords?: string[];
  roles?: string[]; // Which roles can see this item
}

// Navigation items for search
const searchItems: SearchItem[] = [
  // Admin Navigation
  {
    id: 'dashboard',
    title: 'Dashboard',
    description: 'Overview dan statistik',
    href: '/admin/dashboard',
    icon: Home,
    category: 'navigation',
    keywords: ['home', 'beranda', 'overview'],
    roles: ['super-admin', 'admin', 'kepala-sekolah'],
  },
  {
    id: 'employees',
    title: 'Karyawan',
    description: 'Manajemen data karyawan',
    href: '/admin/employees',
    icon: Users,
    category: 'navigation',
    keywords: ['pegawai', 'guru', 'staff', 'employee'],
    roles: ['super-admin', 'admin'],
  },
  {
    id: 'attendance',
    title: 'Absensi',
    description: 'Data kehadiran karyawan',
    href: '/admin/attendance',
    icon: Clock,
    category: 'navigation',
    keywords: ['kehadiran', 'check-in', 'check-out', 'presence'],
    roles: ['super-admin', 'admin', 'kepala-sekolah'],
  },
  {
    id: 'schedules',
    title: 'Jadwal',
    description: 'Manajemen jadwal kerja',
    href: '/admin/schedules',
    icon: Calendar,
    category: 'navigation',
    keywords: ['shift', 'roster', 'schedule'],
    roles: ['super-admin', 'admin'],
  },
  {
    id: 'leave',
    title: 'Cuti & Izin',
    description: 'Permohonan cuti karyawan',
    href: '/admin/leave',
    icon: CalendarOff,
    category: 'navigation',
    keywords: ['leave', 'vacation', 'permission', 'izin'],
    roles: ['super-admin', 'admin', 'kepala-sekolah'],
  },
  {
    id: 'corrections',
    title: 'Koreksi Absensi',
    description: 'Permohonan perbaikan absensi',
    href: '/admin/corrections',
    icon: FileText,
    category: 'navigation',
    keywords: ['correction', 'edit', 'fix', 'perbaikan'],
    roles: ['super-admin', 'admin'],
  },
  {
    id: 'locations',
    title: 'Lokasi',
    description: 'Manajemen lokasi kantor',
    href: '/admin/locations',
    icon: MapPin,
    category: 'navigation',
    keywords: ['location', 'office', 'geofencing', 'gps'],
    roles: ['super-admin', 'admin'],
  },
  {
    id: 'departments',
    title: 'Departemen',
    description: 'Manajemen departemen',
    href: '/admin/departments',
    icon: Building2,
    category: 'navigation',
    keywords: ['division', 'unit', 'divisi'],
    roles: ['super-admin', 'admin'],
  },
  {
    id: 'positions',
    title: 'Jabatan',
    description: 'Manajemen jabatan',
    href: '/admin/positions',
    icon: Briefcase,
    category: 'navigation',
    keywords: ['position', 'role', 'title'],
    roles: ['super-admin', 'admin'],
  },
  {
    id: 'payroll',
    title: 'Payroll',
    description: 'Manajemen gaji karyawan',
    href: '/admin/payroll',
    icon: DollarSign,
    category: 'navigation',
    keywords: ['salary', 'gaji', 'payment'],
    roles: ['super-admin', 'admin'],
  },
  {
    id: 'reports',
    title: 'Laporan',
    description: 'Laporan dan analitik',
    href: '/admin/reports',
    icon: TrendingUp,
    category: 'navigation',
    keywords: ['report', 'analytics', 'chart', 'grafik'],
    roles: ['super-admin', 'admin', 'kepala-sekolah'],
  },
  {
    id: 'settings',
    title: 'Pengaturan',
    description: 'Konfigurasi sistem',
    href: '/admin/settings',
    icon: Settings,
    category: 'navigation',
    keywords: ['config', 'configuration', 'setup'],
    roles: ['super-admin', 'admin'],
  },

  // Employee Navigation
  {
    id: 'employee-dashboard',
    title: 'Dashboard Saya',
    description: 'Dashboard karyawan',
    href: '/employee/dashboard',
    icon: Home,
    category: 'navigation',
    keywords: ['home', 'my dashboard'],
    roles: ['guru', 'pegawai'],
  },
  {
    id: 'employee-attendance',
    title: 'Absensi Saya',
    description: 'Check-in dan check-out',
    href: '/employee/attendance',
    icon: Clock,
    category: 'navigation',
    keywords: ['my attendance', 'check in', 'check out'],
    roles: ['guru', 'pegawai'],
  },
  {
    id: 'employee-schedule',
    title: 'Jadwal Saya',
    description: 'Lihat jadwal kerja',
    href: '/employee/schedule',
    icon: Calendar,
    category: 'navigation',
    keywords: ['my schedule', 'shift'],
    roles: ['guru', 'pegawai'],
  },
  {
    id: 'employee-leave',
    title: 'Pengajuan Cuti',
    description: 'Ajukan cuti atau izin',
    href: '/employee/leave',
    icon: CalendarOff,
    category: 'navigation',
    keywords: ['my leave', 'request leave'],
    roles: ['guru', 'pegawai'],
  },
  {
    id: 'employee-reports',
    title: 'Laporan Saya',
    description: 'Riwayat kehadiran',
    href: '/employee/reports',
    icon: FileText,
    category: 'navigation',
    keywords: ['my report', 'history'],
    roles: ['guru', 'pegawai'],
  },
  {
    id: 'employee-profile',
    title: 'Profil Saya',
    description: 'Edit profil dan pengaturan',
    href: '/employee/profile',
    icon: User,
    category: 'navigation',
    keywords: ['my profile', 'account', 'settings'],
    roles: ['guru', 'pegawai'],
  },
];

interface CommandPaletteProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export function CommandPalette({ open, onOpenChange }: CommandPaletteProps) {
  const [search, setSearch] = useState('');
  const [selectedIndex, setSelectedIndex] = useState(0);
  const navigate = useNavigate();
  const { user } = useAuthStore();

  // Filter items based on search query and user role
  const filteredItems = useMemo(() => {
    const query = search.toLowerCase().trim();

    return searchItems.filter((item) => {
      // Check if user has permission to see this item
      if (item.roles && !item.roles.includes(user?.role || '')) {
        return false;
      }

      // If no search query, show all accessible items
      if (!query) return true;

      // Search in title, description, and keywords
      const searchableText = [
        item.title,
        item.description || '',
        ...(item.keywords || []),
      ].join(' ').toLowerCase();

      return searchableText.includes(query);
    });
  }, [search, user?.role]);

  // Reset when dialog opens/closes
  useEffect(() => {
    if (!open) {
      setSearch('');
      setSelectedIndex(0);
    }
  }, [open]);

  // Handle keyboard navigation
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (!open) return;

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setSelectedIndex((prev) =>
          prev < filteredItems.length - 1 ? prev + 1 : prev
        );
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setSelectedIndex((prev) => (prev > 0 ? prev - 1 : prev));
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (filteredItems[selectedIndex]) {
          handleSelect(filteredItems[selectedIndex]);
        }
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [open, filteredItems, selectedIndex]);

  // Reset selected index when filtered items change
  useEffect(() => {
    setSelectedIndex(0);
  }, [filteredItems]);

  const handleSelect = (item: SearchItem) => {
    navigate({ to: item.href });
    onOpenChange(false);
  };

  const getCategoryLabel = (category: string) => {
    switch (category) {
      case 'navigation':
        return 'Navigasi';
      case 'action':
        return 'Aksi';
      case 'recent':
        return 'Terbaru';
      default:
        return category;
    }
  };

  // Group items by category
  const groupedItems = useMemo(() => {
    const groups: Record<string, SearchItem[]> = {};
    filteredItems.forEach((item) => {
      if (!groups[item.category]) {
        groups[item.category] = [];
      }
      groups[item.category].push(item);
    });
    return groups;
  }, [filteredItems]);

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl p-0 gap-0 overflow-hidden">
        <DialogHeader className="p-4 pb-0">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground" />
            <Input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Cari menu, halaman, atau aksi... (tekan ESC untuk tutup)"
              className="pl-10 pr-10 h-12 text-base border-0 focus-visible:ring-0 focus-visible:ring-offset-0"
              autoFocus
            />
            {search && (
              <button
                onClick={() => setSearch('')}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
              >
                <X className="h-4 w-4" />
              </button>
            )}
          </div>
        </DialogHeader>

        <div className="max-h-[400px] overflow-y-auto p-2">
          {filteredItems.length === 0 ? (
            <div className="py-12 text-center text-muted-foreground">
              <Search className="h-12 w-12 mx-auto mb-4 opacity-20" />
              <p className="text-sm">Tidak ada hasil ditemukan</p>
              <p className="text-xs mt-1">Coba kata kunci lain</p>
            </div>
          ) : (
            <div className="space-y-4">
              {Object.entries(groupedItems).map(([category, items]) => (
                <div key={category}>
                  <div className="px-2 py-1.5 text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                    {getCategoryLabel(category)}
                  </div>
                  <div className="space-y-1">
                    {items.map((item, index) => {
                      const globalIndex = filteredItems.indexOf(item);
                      const isSelected = globalIndex === selectedIndex;
                      const Icon = item.icon;

                      return (
                        <button
                          key={item.id}
                          onClick={() => handleSelect(item)}
                          onMouseEnter={() => setSelectedIndex(globalIndex)}
                          className={cn(
                            'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left transition-all',
                            isSelected
                              ? 'bg-primary text-primary-foreground'
                              : 'hover:bg-muted'
                          )}
                        >
                          <Icon
                            className={cn(
                              'h-5 w-5 shrink-0',
                              isSelected ? 'text-primary-foreground' : 'text-muted-foreground'
                            )}
                          />
                          <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2">
                              <span className="font-medium truncate">{item.title}</span>
                            </div>
                            {item.description && (
                              <p
                                className={cn(
                                  'text-xs truncate',
                                  isSelected ? 'text-primary-foreground/80' : 'text-muted-foreground'
                                )}
                              >
                                {item.description}
                              </p>
                            )}
                          </div>
                          {isSelected && (
                            <Badge variant="secondary" className="text-xs">
                              Enter
                            </Badge>
                          )}
                        </button>
                      );
                    })}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        <div className="border-t p-3 bg-muted/30">
          <div className="flex items-center justify-between text-xs text-muted-foreground">
            <div className="flex items-center gap-4">
              <span>
                <kbd className="px-1.5 py-0.5 rounded bg-background border shadow-sm">↑</kbd>
                <kbd className="px-1.5 py-0.5 rounded bg-background border shadow-sm ml-1">↓</kbd>
                {' '}untuk navigasi
              </span>
              <span>
                <kbd className="px-1.5 py-0.5 rounded bg-background border shadow-sm">Enter</kbd>
                {' '}untuk pilih
              </span>
            </div>
            <span>
              <kbd className="px-1.5 py-0.5 rounded bg-background border shadow-sm">ESC</kbd>
              {' '}untuk tutup
            </span>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
