/**
 * Grade Schedule Grid Component
 *
 * Grid table untuk menampilkan jadwal per grade dengan format Excel-like
 * Rows = Day x Period, Columns = Classes
 */

import { useMemo, useCallback } from 'react';
import {
  ContextMenu,
  ContextMenuContent,
  ContextMenuItem,
  ContextMenuTrigger,
  ContextMenuSeparator,
} from '@/components/ui/context-menu';
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { Lock, Unlock, ArrowRightLeft, Trash2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useGradeScheduleStore } from '@/stores/grade-schedule-store';
import { useNotificationStore } from '@/stores/notification-store';
import {
  DAYS,
  TIME_SLOTS,
  getRowKey,
  generateTeacherColor,
} from './constants';

interface GradeScheduleGridProps {
  grade: string;
}

export function GradeScheduleGrid({ grade }: GradeScheduleGridProps) {
  const { success, error: showError, warning } = useNotificationStore();

  const grids = useGradeScheduleStore((s) => s.grids);
  const teachers = useGradeScheduleStore((s) => s.teachers);
  const swapSource = useGradeScheduleStore((s) => s.swapSource);

  const toggleCell = useGradeScheduleStore((s) => s.toggleCell);
  const clearCell = useGradeScheduleStore((s) => s.clearCell);
  const lockCell = useGradeScheduleStore((s) => s.lockCell);
  const unlockCell = useGradeScheduleStore((s) => s.unlockCell);
  const startSwap = useGradeScheduleStore((s) => s.startSwap);
  const cancelSwap = useGradeScheduleStore((s) => s.cancelSwap);
  const completeSwap = useGradeScheduleStore((s) => s.completeSwap);
  const setSelectedCell = useGradeScheduleStore((s) => s.setSelectedCell);
  const selectedCell = useGradeScheduleStore((s) => s.selectedCell);
  const getTeacherJPUsage = useGradeScheduleStore((s) => s.getTeacherJPUsage);

  const gradeGrid = grids[grade] || {};
  const classes = useMemo(() => Object.keys(gradeGrid).sort(), [gradeGrid]);

  // Handle cell click
  const handleCellClick = useCallback(
    (className: string, rowKey: string) => {
      // If in swap mode
      if (swapSource) {
        // Same cell - cancel swap
        if (swapSource.grade === grade && swapSource.className === className && swapSource.rowKey === rowKey) {
          cancelSwap();
          return;
        }

        // Complete swap
        const result = completeSwap(grade, className, rowKey);
        if (result.valid) {
          success('Berhasil', 'Sel berhasil ditukar');
        } else {
          showError('Gagal', result.message || 'Tidak bisa menukar sel');
        }
        return;
      }

      // Normal toggle
      const result = toggleCell(grade, className, rowKey);
      if (!result.valid) {
        if (result.type === 'locked_cell') {
          warning('Sel Terkunci', result.message || 'Sel ini terkunci');
        } else if (result.type === 'time_conflict') {
          showError('Bentrok Waktu', result.message || 'Guru sudah mengajar di waktu yang sama');
        } else if (result.type === 'jp_exceeded') {
          showError('Batas JP', result.message || 'Batas jam pelajaran tercapai');
        } else if (result.message) {
          warning('Perhatian', result.message);
        }
      }
    },
    [grade, swapSource, toggleCell, completeSwap, cancelSwap, success, showError, warning]
  );

  // Handle context menu actions
  const handleToggleLock = useCallback(
    (className: string, rowKey: string) => {
      const cell = gradeGrid[className]?.[rowKey];
      if (cell?.isLocked) {
        unlockCell(grade, className, rowKey);
        success('Berhasil', 'Sel dibuka kuncinya');
      } else {
        lockCell(grade, className, rowKey);
        success('Berhasil', 'Sel dikunci');
      }
    },
    [grade, gradeGrid, lockCell, unlockCell, success]
  );

  const handleStartSwap = useCallback(
    (className: string, rowKey: string) => {
      startSwap(grade, className, rowKey);
      success('Mode Tukar', 'Klik sel lain untuk menukar');
    },
    [grade, startSwap, success]
  );

  const handleClearCell = useCallback(
    (className: string, rowKey: string) => {
      const cell = gradeGrid[className]?.[rowKey];
      if (cell?.isLocked) {
        warning('Sel Terkunci', 'Buka kunci terlebih dahulu');
        return;
      }
      clearCell(grade, className, rowKey);
    },
    [grade, gradeGrid, clearCell, warning]
  );

  // Get teacher info and color
  const getTeacherInfo = useCallback(
    (teacherCode: string) => {
      const teacher = teachers.get(teacherCode);
      const jpUsage = getTeacherJPUsage(teacherCode);
      return {
        name: teacher?.name || teacherCode,
        subject: teacher?.subject || '',
        color: teacher?.color || generateTeacherColor(teacherCode),
        jpCurrent: jpUsage.current,
        jpMax: jpUsage.max,
      };
    },
    [teachers, getTeacherJPUsage]
  );

  // Handle cell selection (for keyboard Delete)
  const handleCellSelect = useCallback(
    (className: string, rowKey: string) => {
      setSelectedCell({ grade, className, rowKey });
    },
    [grade, setSelectedCell]
  );

  // Check if cell is selected
  const isCellSelected = useCallback(
    (className: string, rowKey: string) => {
      return (
        selectedCell?.grade === grade &&
        selectedCell?.className === className &&
        selectedCell?.rowKey === rowKey
      );
    },
    [selectedCell, grade]
  );

  return (
    <div className="overflow-x-auto">
      <table className="w-full border-collapse text-sm">
        <thead className="sticky top-0 z-10">
          <tr>
            <th className="border bg-muted/70 p-2 text-left font-medium w-20">Hari</th>
            <th className="border bg-muted/70 p-2 text-center font-medium w-12">Jam</th>
            <th className="border bg-muted/70 p-2 text-center font-medium w-24">Waktu</th>
            {classes.map((className) => (
              <th
                key={className}
                className="border bg-muted/70 p-2 text-center font-medium min-w-[80px]"
              >
                {className}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {DAYS.map((day) => (
            <>
              {TIME_SLOTS.map((slot, slotIdx) => {
                const rowKey = getRowKey(day, slot.period);
                const isFirstOfDay = slotIdx === 0;
                const dayRowSpan = TIME_SLOTS.length;

                // Break row
                if (slot.isBreak) {
                  return (
                    <tr key={`${day}-break-${slot.period}`} className="bg-muted/30">
                      {isFirstOfDay && (
                        <td
                          rowSpan={dayRowSpan}
                          className="border bg-muted/50 p-2 font-medium text-center align-middle"
                        >
                          <span className="writing-mode-vertical">{day}</span>
                        </td>
                      )}
                      <td className="border p-1 text-center text-muted-foreground text-xs">
                        -
                      </td>
                      <td className="border p-1 text-center text-muted-foreground text-xs">
                        {slot.start}-{slot.end}
                      </td>
                      {classes.map((className) => (
                        <td
                          key={className}
                          className="border p-1 text-center text-muted-foreground text-xs bg-muted/20"
                        >
                          Istirahat
                        </td>
                      ))}
                    </tr>
                  );
                }

                return (
                  <tr key={`${day}-${slot.period}`}>
                    {isFirstOfDay && (
                      <td
                        rowSpan={dayRowSpan}
                        className="border bg-muted/50 p-2 font-medium text-center align-middle"
                      >
                        <div className="[writing-mode:vertical-lr] rotate-180">
                          {day}
                        </div>
                      </td>
                    )}
                    <td className="border p-1 text-center font-medium text-xs">
                      {slot.period}
                    </td>
                    <td className="border p-1 text-center text-xs text-muted-foreground">
                      {slot.start}-{slot.end}
                    </td>
                    {classes.map((className) => {
                      const cell = gradeGrid[className]?.[rowKey];
                      const isSwapSource =
                        swapSource?.grade === grade &&
                        swapSource?.className === className &&
                        swapSource?.rowKey === rowKey;
                      const isSelected = isCellSelected(className, rowKey);

                      const teacherInfo = cell?.teacherCode
                        ? getTeacherInfo(cell.teacherCode)
                        : null;

                      const cellContent = (
                        <td
                          className={cn(
                            'border p-0.5 cursor-pointer transition-all h-10',
                            'hover:ring-2 hover:ring-primary/30 hover:ring-inset',
                            cell?.isLocked && 'bg-muted/40',
                            isSwapSource && 'ring-2 ring-primary ring-inset',
                            isSelected && 'ring-2 ring-blue-500 ring-inset',
                            swapSource && !isSwapSource && 'opacity-70'
                          )}
                          onClick={() => {
                            handleCellSelect(className, rowKey);
                            handleCellClick(className, rowKey);
                          }}
                        >
                          {cell?.teacherCode ? (
                            <div
                              className="h-full rounded px-1 py-0.5 flex flex-col justify-center items-center relative"
                              style={{ backgroundColor: teacherInfo?.color }}
                            >
                              <span className="font-mono font-bold text-xs">
                                {cell.teacherCode}
                              </span>
                              {cell.subject && (
                                <span className="text-[9px] text-muted-foreground truncate max-w-full">
                                  {cell.subject.length > 8
                                    ? cell.subject.substring(0, 8) + '..'
                                    : cell.subject}
                                </span>
                              )}
                              {cell.isLocked && (
                                <Lock className="h-2.5 w-2.5 absolute top-0.5 right-0.5 text-muted-foreground" />
                              )}
                            </div>
                          ) : (
                            <div className="h-full flex items-center justify-center text-muted-foreground/20">
                              -
                            </div>
                          )}
                        </td>
                      );

                      return (
                        <ContextMenu key={className}>
                          <ContextMenuTrigger asChild>
                            {teacherInfo ? (
                              <Tooltip>
                                <TooltipTrigger asChild>
                                  {cellContent}
                                </TooltipTrigger>
                                <TooltipContent side="top" className="max-w-[200px]">
                                  <div className="space-y-1">
                                    <div className="font-semibold text-sm">{teacherInfo.name}</div>
                                    {teacherInfo.subject && (
                                      <div className="text-xs text-muted-foreground">{teacherInfo.subject}</div>
                                    )}
                                    <div className="text-xs">
                                      JP: {teacherInfo.jpCurrent}
                                      {teacherInfo.jpMax !== null && `/${teacherInfo.jpMax}`}
                                    </div>
                                    {cell?.isLocked && (
                                      <div className="text-xs text-yellow-600">Sel terkunci</div>
                                    )}
                                  </div>
                                </TooltipContent>
                              </Tooltip>
                            ) : (
                              cellContent
                            )}
                          </ContextMenuTrigger>
                          <ContextMenuContent>
                            <ContextMenuItem onClick={() => handleToggleLock(className, rowKey)}>
                              {cell?.isLocked ? (
                                <>
                                  <Unlock className="h-4 w-4 mr-2" />
                                  Buka Kunci
                                </>
                              ) : (
                                <>
                                  <Lock className="h-4 w-4 mr-2" />
                                  Kunci Sel
                                </>
                              )}
                            </ContextMenuItem>
                            <ContextMenuSeparator />
                            {!swapSource ? (
                              <ContextMenuItem
                                onClick={() => handleStartSwap(className, rowKey)}
                                disabled={!cell?.teacherCode}
                              >
                                <ArrowRightLeft className="h-4 w-4 mr-2" />
                                Mulai Tukar
                              </ContextMenuItem>
                            ) : (
                              <ContextMenuItem onClick={() => handleCellClick(className, rowKey)}>
                                <ArrowRightLeft className="h-4 w-4 mr-2" />
                                Tukar di Sini
                              </ContextMenuItem>
                            )}
                            <ContextMenuSeparator />
                            <ContextMenuItem
                              onClick={() => handleClearCell(className, rowKey)}
                              disabled={!cell?.teacherCode || cell?.isLocked}
                              className="text-destructive"
                            >
                              <Trash2 className="h-4 w-4 mr-2" />
                              Hapus
                            </ContextMenuItem>
                          </ContextMenuContent>
                        </ContextMenu>
                      );
                    })}
                  </tr>
                );
              })}
            </>
          ))}
        </tbody>
      </table>
    </div>
  );
}
