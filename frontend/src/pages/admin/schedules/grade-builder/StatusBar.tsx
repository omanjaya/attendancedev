/**
 * StatusBar Component
 *
 * Menampilkan status auto-save dan statistik jadwal.
 * Posisi di footer grid untuk visibility yang baik.
 */

import { useMemo } from 'react';
import { Button } from '@/components/ui/button';
import {
  Loader2,
  CheckCircle2,
  Circle,
  AlertCircle,
  RefreshCw,
  Calendar,
  Users,
  LayoutGrid,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import type { AutoSaveStatus } from '@/hooks/use-auto-save';
import { useGradeScheduleStore } from '@/stores/grade-schedule-store';

interface StatusBarProps {
  status: AutoSaveStatus;
  lastSavedAt: Date | null;
  error: Error | null;
  retryCount: number;
  onRetry: () => void;
  onSaveNow: () => void;
}

export function StatusBar({
  status,
  lastSavedAt,
  error,
  retryCount,
  onRetry,
  onSaveNow,
}: StatusBarProps) {
  const activeGrade = useGradeScheduleStore((s) => s.activeGrade);
  const grids = useGradeScheduleStore((s) => s.grids);
  const teachers = useGradeScheduleStore((s) => s.teachers);

  // Calculate stats for current grade
  const stats = useMemo(() => {
    const gradeGrid = grids[activeGrade] || {};
    let filled = 0;
    let total = 0;
    const teacherSet = new Set<string>();

    for (const className in gradeGrid) {
      for (const rowKey in gradeGrid[className]) {
        total++;
        const cell = gradeGrid[className][rowKey];
        if (cell?.teacherCode) {
          filled++;
          teacherSet.add(cell.teacherCode);
        }
      }
    }

    return {
      filled,
      total,
      percentage: total > 0 ? Math.round((filled / total) * 100) : 0,
      teacherCount: teacherSet.size,
      classCount: Object.keys(gradeGrid).length,
    };
  }, [grids, activeGrade]);

  // Format last saved time
  const formatLastSaved = (date: Date | null) => {
    if (!date) return null;

    const now = new Date();
    const diff = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (diff < 5) return 'Baru saja';
    if (diff < 60) return `${diff} detik lalu`;
    if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`;

    return date.toLocaleTimeString('id-ID', {
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  return (
    <div className="flex items-center justify-between px-3 py-2 bg-muted/50 border-t text-sm">
      {/* Left: Stats */}
      <div className="flex items-center gap-4 text-muted-foreground">
        <div className="flex items-center gap-1.5">
          <Calendar className="h-3.5 w-3.5" />
          <span className="font-medium">Kelas {activeGrade}</span>
        </div>

        <div className="flex items-center gap-1.5">
          <LayoutGrid className="h-3.5 w-3.5" />
          <span>
            {stats.filled}/{stats.total} slot ({stats.percentage}%)
          </span>
        </div>

        <div className="flex items-center gap-1.5">
          <Users className="h-3.5 w-3.5" />
          <span>{stats.teacherCount} guru</span>
        </div>
      </div>

      {/* Right: Save Status */}
      <div className="flex items-center gap-2">
        {status === 'saving' && (
          <>
            <Loader2 className="h-3.5 w-3.5 animate-spin text-primary" />
            <span className="text-muted-foreground">
              Menyimpan...{retryCount > 0 && ` (percobaan ${retryCount + 1})`}
            </span>
          </>
        )}

        {status === 'saved' && (
          <>
            <CheckCircle2 className="h-3.5 w-3.5 text-green-500" />
            <span className="text-green-600">
              Tersimpan{lastSavedAt && ` • ${formatLastSaved(lastSavedAt)}`}
            </span>
          </>
        )}

        {status === 'pending' && (
          <>
            <Circle className={cn('h-3.5 w-3.5 text-yellow-500 fill-yellow-500')} />
            <span className="text-yellow-600">Perubahan belum disimpan</span>
            <Button
              variant="ghost"
              size="sm"
              className="h-6 px-2 text-xs"
              onClick={onSaveNow}
            >
              Simpan sekarang
            </Button>
          </>
        )}

        {status === 'error' && (
          <>
            <AlertCircle className="h-3.5 w-3.5 text-red-500" />
            <span className="text-red-600 max-w-[200px] truncate">
              {error?.message || 'Gagal menyimpan'}
            </span>
            <Button
              variant="ghost"
              size="sm"
              className="h-6 px-2 text-xs text-red-600 hover:text-red-700"
              onClick={onRetry}
            >
              <RefreshCw className="h-3 w-3 mr-1" />
              Coba lagi
            </Button>
          </>
        )}

        {status === 'idle' && lastSavedAt && (
          <>
            <CheckCircle2 className="h-3.5 w-3.5 text-muted-foreground" />
            <span className="text-muted-foreground">
              Tersimpan • {formatLastSaved(lastSavedAt)}
            </span>
          </>
        )}

        {status === 'idle' && !lastSavedAt && teachers.size > 0 && (
          <span className="text-muted-foreground">
            Siap menyimpan otomatis
          </span>
        )}
      </div>
    </div>
  );
}
