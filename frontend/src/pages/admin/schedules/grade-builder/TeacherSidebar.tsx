/**
 * Teacher Sidebar Component
 *
 * Menampilkan daftar guru dari database dengan info JP
 * Terintegrasi dengan API employees dan subjects
 */

import { useState, useMemo, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Skeleton } from '@/components/ui/skeleton';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Search, User, Check, AlertTriangle, RefreshCw, Database } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useGradeScheduleStore } from '@/stores/grade-schedule-store';
import { generateTeacherColor } from './constants';
import type { TeacherWithJP } from './types';
import { useEmployees } from '@/hooks/use-employees';
import { useSubjects } from '@/hooks/use-schedules';

export function TeacherSidebar() {
  const teachers = useGradeScheduleStore((s) => s.teachers);
  const activeTeacher = useGradeScheduleStore((s) => s.activeTeacher);
  const setActiveTeacher = useGradeScheduleStore((s) => s.setActiveTeacher);
  const addTeacher = useGradeScheduleStore((s) => s.addTeacher);
  const getTeacherJPUsage = useGradeScheduleStore((s) => s.getTeacherJPUsage);

  const [search, setSearch] = useState('');
  const [subjectFilter, setSubjectFilter] = useState<string>('all');

  // Fetch employees from database (guru only)
  const { data: employeesData, isLoading: loadingEmployees, refetch: refetchEmployees } = useEmployees({
    per_page: 200, // Get all teachers
  });

  // Fetch subjects from database
  const { data: subjectsData, isLoading: loadingSubjects } = useSubjects();

  const employees = employeesData?.data ?? [];
  const subjects = subjectsData ?? [];

  // Filter only teachers/guru (based on position containing 'guru' or employee_type)
  const guruEmployees = useMemo(() => {
    return employees.filter((emp) => {
      const posLower = (emp.position || '').toLowerCase();
      const deptLower = (emp.department || '').toLowerCase();
      const typeName = (emp.employee_type?.name || '').toLowerCase();

      return (
        posLower.includes('guru') ||
        deptLower.includes('guru') ||
        typeName.includes('guru') ||
        emp.subject_id // Has subject assigned
      );
    });
  }, [employees]);

  // Auto-load teachers from database on mount
  useEffect(() => {
    if (guruEmployees.length > 0 && teachers.size === 0) {
      loadTeachersFromDB();
    }
  }, [guruEmployees.length]);

  // Load teachers from database into store
  const loadTeachersFromDB = () => {
    guruEmployees.forEach((emp, index) => {
      const code = String(index + 1); // Use index as code if no employee_id
      const subject = subjects.find((s) => s.id === emp.subject_id);

      const teacher: TeacherWithJP = {
        code: emp.employee_id || code,
        name: emp.name,
        subject: subject?.name || emp.position || '',
        maxJP: null, // No default limit
        color: generateTeacherColor(emp.employee_id || code),
        employeeId: emp.id, // Store reference to database
      };

      addTeacher(teacher);
    });
  };

  // Convert Map to array and filter
  const teacherList = useMemo(() => {
    const list = Array.from(teachers.values());

    let filtered = list;

    // Filter by search
    if (search) {
      const searchLower = search.toLowerCase();
      filtered = filtered.filter(
        (t) =>
          t.code.toLowerCase().includes(searchLower) ||
          t.name.toLowerCase().includes(searchLower) ||
          (t.subject || '').toLowerCase().includes(searchLower)
      );
    }

    // Filter by subject
    if (subjectFilter && subjectFilter !== 'all') {
      filtered = filtered.filter((t) =>
        (t.subject || '').toLowerCase().includes(subjectFilter.toLowerCase())
      );
    }

    return filtered;
  }, [teachers, search, subjectFilter]);

  // Handle select teacher
  const handleSelectTeacher = (teacher: TeacherWithJP) => {
    if (activeTeacher?.code === teacher.code) {
      setActiveTeacher(null); // Deselect
    } else {
      setActiveTeacher(teacher);
    }
  };

  // Refresh teachers from database
  const handleRefresh = async () => {
    await refetchEmployees();
    loadTeachersFromDB();
  };

  // Get unique subjects from teachers for filter
  const uniqueSubjects = useMemo(() => {
    const subjectSet = new Set<string>();
    Array.from(teachers.values()).forEach((t) => {
      if (t.subject) subjectSet.add(t.subject);
    });
    return Array.from(subjectSet).sort();
  }, [teachers]);

  const isLoading = loadingEmployees || loadingSubjects;

  return (
    <Card className="h-full flex flex-col">
      <CardHeader className="pb-3">
        <CardTitle className="text-sm flex items-center justify-between">
          <div className="flex items-center gap-2">
            <User className="h-4 w-4" />
            Pilih Guru
          </div>
          <Button
            variant="ghost"
            size="icon"
            className="h-6 w-6"
            onClick={handleRefresh}
            disabled={isLoading}
            title="Refresh dari database"
          >
            <RefreshCw className={cn("h-3 w-3", isLoading && "animate-spin")} />
          </Button>
        </CardTitle>
      </CardHeader>
      <CardContent className="flex-1 flex flex-col gap-3 p-3 pt-0">
        {/* Search */}
        <div className="relative">
          <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Cari guru..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-8 h-9"
          />
        </div>

        {/* Subject Filter */}
        <Select value={subjectFilter} onValueChange={setSubjectFilter}>
          <SelectTrigger className="h-9">
            <SelectValue placeholder="Filter mapel" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Semua Mapel</SelectItem>
            {uniqueSubjects.map((subj) => (
              <SelectItem key={subj} value={subj}>
                {subj}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        {/* Database indicator */}
        {teachers.size > 0 && (
          <div className="flex items-center gap-1.5 text-xs text-muted-foreground bg-muted/50 rounded px-2 py-1">
            <Database className="h-3 w-3" />
            <span>Data dari database</span>
          </div>
        )}

        {/* Active teacher indicator */}
        {activeTeacher && (
          <div
            className="p-2 rounded border-2 border-primary"
            style={{ backgroundColor: activeTeacher.color }}
          >
            <div className="flex items-center gap-2">
              <Check className="h-4 w-4 text-primary" />
              <span className="font-mono font-bold text-gray-900">{activeTeacher.code}</span>
              <span className="text-sm truncate text-gray-900">{activeTeacher.name}</span>
            </div>
            <p className="text-xs text-gray-600 mt-1">
              Klik sel untuk memasukkan guru ini
            </p>
          </div>
        )}

        {/* Teacher list */}
        <ScrollArea className="flex-1">
          <div className="space-y-1.5">
            {isLoading ? (
              // Loading skeleton
              <>
                {[1, 2, 3, 4, 5].map((i) => (
                  <Skeleton key={i} className="h-16 w-full" />
                ))}
              </>
            ) : teacherList.length === 0 ? (
              <div className="text-center py-4 space-y-2">
                <p className="text-sm text-muted-foreground">
                  {teachers.size === 0
                    ? 'Belum ada guru. Klik refresh atau import dari Excel.'
                    : 'Tidak ada guru ditemukan.'}
                </p>
                {teachers.size === 0 && (
                  <Button variant="outline" size="sm" onClick={handleRefresh}>
                    <Database className="h-4 w-4 mr-1" />
                    Muat dari Database
                  </Button>
                )}
              </div>
            ) : (
              teacherList.map((teacher) => {
                const jpUsage = getTeacherJPUsage(teacher.code);
                const isActive = activeTeacher?.code === teacher.code;
                const isOverLimit =
                  teacher.maxJP !== null && jpUsage.current >= teacher.maxJP;
                const isNearLimit =
                  teacher.maxJP !== null &&
                  jpUsage.current >= teacher.maxJP * 0.8;

                return (
                  <div
                    key={teacher.code}
                    className={cn(
                      'p-2 rounded cursor-pointer transition-all border border-gray-300',
                      'hover:ring-2 hover:ring-primary/30',
                      isActive && 'ring-2 ring-primary',
                      isOverLimit && 'opacity-60'
                    )}
                    style={{ backgroundColor: teacher.color }}
                    onClick={() => handleSelectTeacher(teacher)}
                  >
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-2 min-w-0">
                        <span className="font-mono font-bold text-sm shrink-0 text-gray-900">
                          {teacher.code}
                        </span>
                        <span className="text-sm truncate text-gray-900">{teacher.name}</span>
                      </div>
                      {isActive && (
                        <Check className="h-4 w-4 text-primary shrink-0" />
                      )}
                    </div>
                    <div className="flex items-center justify-between mt-1">
                      <span className="text-xs text-gray-600 truncate">
                        {teacher.subject || 'Mapel belum diset'}
                      </span>
                      <Badge
                        variant={isOverLimit ? 'destructive' : isNearLimit ? 'secondary' : 'outline'}
                        className="text-[10px] px-1.5 py-0 shrink-0 bg-white/80 text-gray-900 border-gray-400"
                      >
                        {isOverLimit && (
                          <AlertTriangle className="h-2.5 w-2.5 mr-0.5" />
                        )}
                        JP: {jpUsage.current}
                        {teacher.maxJP !== null ? `/${teacher.maxJP}` : ''}
                      </Badge>
                    </div>
                  </div>
                );
              })
            )}
          </div>
        </ScrollArea>

        {/* Stats */}
        {teachers.size > 0 && (
          <div className="text-xs text-muted-foreground text-center pt-2 border-t">
            {teacherList.length} dari {teachers.size} guru
          </div>
        )}
      </CardContent>
    </Card>
  );
}
