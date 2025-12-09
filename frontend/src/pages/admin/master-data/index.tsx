import { useState } from 'react';
import {
    CalendarDays,
    BookOpen,
    School,
    Clock,
    Plus,
    Pencil,
    Trash2,
    Loader2,
    Check,
    X,
    GripVertical,
    Search,
    Upload,
} from 'lucide-react';
import { PageHeader } from '@/components/shared/PageHeader';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    useAcademicYears,
    useSubjects,
    useClassrooms,
    usePeriods,
} from '@/hooks/use-master-data';
import {
    createAcademicYear,
    updateAcademicYear,
    deleteAcademicYear,
    createSubject,
    updateSubject,
    deleteSubject,
    createClassroom,
    updateClassroom,
    deleteClassroom,
    createPeriod,
    updatePeriod,
    deletePeriod,
} from '@/lib/api/master-data';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import type { AcademicYear, Subject, Classroom, Period } from '@/types/master-data';
import { ExcelImportDialog, type ExcelColumn, type ImportResult } from '@/components/shared/ExcelImportDialog';
import { importClassrooms } from '@/lib/api/imports';

// ============ Form Data Types ============
interface AcademicYearFormData {
    name: string;
    start_date: string;
    end_date: string;
    semester: 'odd' | 'even';
    is_active: boolean;
}

interface SubjectFormData {
    name: string;
    code: string;
    description: string;
    is_active: boolean;
}

interface ClassroomFormData {
    name: string;
    grade_level: string;
    major: string;
    class_number: string;
    capacity: number;
    room: string;
    is_active: boolean;
}

interface PeriodFormData {
    name: string;
    start_time: string;
    end_time: string;
    order: number;
    is_active: boolean;
}

const initialAcademicYearFormData: AcademicYearFormData = {
    name: '',
    start_date: '',
    end_date: '',
    semester: 'odd',
    is_active: true,
};

const initialSubjectFormData: SubjectFormData = {
    name: '',
    code: '',
    description: '',
    is_active: true,
};

const initialClassroomFormData: ClassroomFormData = {
    name: '',
    grade_level: '',
    major: '',
    class_number: '',
    capacity: 30,
    room: '',
    is_active: true,
};

const initialPeriodFormData: PeriodFormData = {
    name: '',
    start_time: '',
    end_time: '',
    order: 1,
    is_active: true,
};



export default function MasterDataPage() {
    const queryClient = useQueryClient();
    const [activeTab, setActiveTab] = useState('academic-years');

    // ============ Academic Year State ============
    const [yearSearchQuery, setYearSearchQuery] = useState('');
    const [isYearDialogOpen, setIsYearDialogOpen] = useState(false);
    const [isYearDeleteDialogOpen, setIsYearDeleteDialogOpen] = useState(false);
    const [selectedYear, setSelectedYear] = useState<AcademicYear | null>(null);
    const [yearFormData, setYearFormData] = useState<AcademicYearFormData>(initialAcademicYearFormData);

    // ============ Subject State ============
    const [subjectSearchQuery, setSubjectSearchQuery] = useState('');
    const [isSubjectDialogOpen, setIsSubjectDialogOpen] = useState(false);
    const [isSubjectDeleteDialogOpen, setIsSubjectDeleteDialogOpen] = useState(false);
    const [selectedSubject, setSelectedSubject] = useState<Subject | null>(null);
    const [subjectFormData, setSubjectFormData] = useState<SubjectFormData>(initialSubjectFormData);

    // ============ Classroom State ============
    const [classroomSearchQuery, setClassroomSearchQuery] = useState('');
    const [isClassroomDialogOpen, setIsClassroomDialogOpen] = useState(false);
    const [isClassroomDeleteDialogOpen, setIsClassroomDeleteDialogOpen] = useState(false);
    const [selectedClassroom, setSelectedClassroom] = useState<Classroom | null>(null);
    const [classroomFormData, setClassroomFormData] = useState<ClassroomFormData>(initialClassroomFormData);

    // ============ Period State ============
    const [periodSearchQuery, setPeriodSearchQuery] = useState('');
    const [isPeriodDialogOpen, setIsPeriodDialogOpen] = useState(false);
    const [isPeriodDeleteDialogOpen, setIsPeriodDeleteDialogOpen] = useState(false);
    const [selectedPeriod, setSelectedPeriod] = useState<Period | null>(null);
    const [periodFormData, setPeriodFormData] = useState<PeriodFormData>(initialPeriodFormData);

    // ============ Classroom Import State ============
    const [isClassroomImportDialogOpen, setIsClassroomImportDialogOpen] = useState(false);

    const classroomImportColumns: ExcelColumn[] = [
        { key: 'name', label: 'Nama Kelas', required: true, type: 'string', width: 20 },
        { key: 'grade_level', label: 'Tingkat', required: false, type: 'string', width: 15 },
        { key: 'description', label: 'Deskripsi', required: false, type: 'string', width: 30 },
    ];

    const handleClassroomFileImport = async (file: File): Promise<ImportResult> => {
        return await importClassrooms(file);
    };

    const handleClassroomImportSuccess = () => {
        queryClient.invalidateQueries({ queryKey: ['classrooms'] });
        toast.success('Import kelas berhasil!');
    };

    // ============ Fetch Data ============
    const { data: yearsResponse, isLoading: yearsLoading } = useAcademicYears({
        include_inactive: true,
        search: yearSearchQuery || undefined
    });
    const years = yearsResponse?.data || [];

    const { data: subjectsResponse, isLoading: subjectsLoading } = useSubjects({
        include_inactive: true,
        search: subjectSearchQuery || undefined
    });
    const subjects = subjectsResponse?.data || [];

    const { data: classroomsResponse, isLoading: classroomsLoading } = useClassrooms({
        include_inactive: true,
        search: classroomSearchQuery || undefined
    });
    const classrooms = classroomsResponse?.data || [];

    const { data: periodsResponse, isLoading: periodsLoading } = usePeriods({
        include_inactive: true,
        search: periodSearchQuery || undefined
    });
    const periods = periodsResponse?.data || [];

    // ============ Academic Year Mutations ============
    const createYearMutation = useMutation({
        mutationFn: createAcademicYear,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['academic-years'] });
            toast.success('Tahun ajaran berhasil dibuat');
            setIsYearDialogOpen(false);
            setYearFormData(initialAcademicYearFormData);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal membuat tahun ajaran');
        },
    });

    const updateYearMutation = useMutation({
        mutationFn: ({ id, data }: { id: string; data: Partial<AcademicYear> }) =>
            updateAcademicYear(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['academic-years'] });
            toast.success('Tahun ajaran berhasil diupdate');
            setIsYearDialogOpen(false);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal mengupdate tahun ajaran');
        },
    });

    const deleteYearMutation = useMutation({
        mutationFn: deleteAcademicYear,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['academic-years'] });
            toast.success('Tahun ajaran berhasil dihapus');
            setIsYearDeleteDialogOpen(false);
            setSelectedYear(null);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal menghapus tahun ajaran');
        },
    });

    // ============ Subject Mutations ============
    const createSubjectMutation = useMutation({
        mutationFn: createSubject,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['subjects'] });
            toast.success('Mata pelajaran berhasil dibuat');
            setIsSubjectDialogOpen(false);
            setSubjectFormData(initialSubjectFormData);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal membuat mata pelajaran');
        },
    });

    const updateSubjectMutation = useMutation({
        mutationFn: ({ id, data }: { id: string; data: Partial<Subject> }) =>
            updateSubject(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['subjects'] });
            toast.success('Mata pelajaran berhasil diupdate');
            setIsSubjectDialogOpen(false);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal mengupdate mata pelajaran');
        },
    });

    const deleteSubjectMutation = useMutation({
        mutationFn: deleteSubject,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['subjects'] });
            toast.success('Mata pelajaran berhasil dihapus');
            setIsSubjectDeleteDialogOpen(false);
            setSelectedSubject(null);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal menghapus mata pelajaran');
        },
    });

    // ============ Classroom Mutations ============
    const createClassroomMutation = useMutation({
        mutationFn: createClassroom,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['classrooms'] });
            toast.success('Kelas berhasil dibuat');
            setIsClassroomDialogOpen(false);
            setClassroomFormData(initialClassroomFormData);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal membuat kelas');
        },
    });

    const updateClassroomMutation = useMutation({
        mutationFn: ({ id, data }: { id: string; data: Partial<Classroom> }) =>
            updateClassroom(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['classrooms'] });
            toast.success('Kelas berhasil diupdate');
            setIsClassroomDialogOpen(false);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal mengupdate kelas');
        },
    });

    const deleteClassroomMutation = useMutation({
        mutationFn: deleteClassroom,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['classrooms'] });
            toast.success('Kelas berhasil dihapus');
            setIsClassroomDeleteDialogOpen(false);
            setSelectedClassroom(null);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal menghapus kelas');
        },
    });

    // ============ Period Mutations ============
    const createPeriodMutation = useMutation({
        mutationFn: createPeriod,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['periods'] });
            toast.success('Jam pelajaran berhasil dibuat');
            setIsPeriodDialogOpen(false);
            setPeriodFormData(initialPeriodFormData);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal membuat jam pelajaran');
        },
    });

    const updatePeriodMutation = useMutation({
        mutationFn: ({ id, data }: { id: string; data: Partial<Period> }) =>
            updatePeriod(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['periods'] });
            toast.success('Jam pelajaran berhasil diupdate');
            setIsPeriodDialogOpen(false);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal mengupdate jam pelajaran');
        },
    });

    const deletePeriodMutation = useMutation({
        mutationFn: deletePeriod,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['periods'] });
            toast.success('Jam pelajaran berhasil dihapus');
            setIsPeriodDeleteDialogOpen(false);
            setSelectedPeriod(null);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal menghapus jam pelajaran');
        },
    });

    // ============ Handlers ============
    const handleOpenYearCreate = () => {
        setSelectedYear(null);
        setYearFormData(initialAcademicYearFormData);
        setIsYearDialogOpen(true);
    };

    const handleOpenYearEdit = (year: AcademicYear) => {
        setSelectedYear(year);
        setYearFormData({
            name: year.name,
            start_date: year.start_date?.split('T')[0] || '',
            end_date: year.end_date?.split('T')[0] || '',
            semester: year.semester || 'odd',
            is_active: year.is_active,
        });
        setIsYearDialogOpen(true);
    };

    const handleYearSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (selectedYear) {
            updateYearMutation.mutate({ id: selectedYear.id, data: yearFormData });
        } else {
            createYearMutation.mutate(yearFormData);
        }
    };

    const handleOpenSubjectCreate = () => {
        setSelectedSubject(null);
        setSubjectFormData(initialSubjectFormData);
        setIsSubjectDialogOpen(true);
    };

    const handleOpenSubjectEdit = (subject: Subject) => {
        setSelectedSubject(subject);
        setSubjectFormData({
            name: subject.name,
            code: subject.code,
            description: subject.description || '',
            is_active: subject.is_active,
        });
        setIsSubjectDialogOpen(true);
    };

    const handleSubjectSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (selectedSubject) {
            updateSubjectMutation.mutate({ id: selectedSubject.id, data: subjectFormData });
        } else {
            createSubjectMutation.mutate(subjectFormData);
        }
    };

    const handleOpenClassroomCreate = () => {
        setSelectedClassroom(null);
        setClassroomFormData(initialClassroomFormData);
        setIsClassroomDialogOpen(true);
    };

    const handleOpenClassroomEdit = (classroom: any) => {
        setSelectedClassroom(classroom);
        setClassroomFormData({
            name: classroom.name,
            grade_level: classroom.grade_level || '',
            major: classroom.major || '',
            class_number: classroom.class_number || '',
            capacity: classroom.capacity || 30,
            room: classroom.room || '',
            is_active: classroom.is_active,
        });
        setIsClassroomDialogOpen(true);
    };

    const handleClassroomSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (selectedClassroom) {
            updateClassroomMutation.mutate({ id: selectedClassroom.id, data: classroomFormData });
        } else {
            createClassroomMutation.mutate(classroomFormData);
        }
    };

    const handleOpenPeriodCreate = () => {
        setSelectedPeriod(null);
        setPeriodFormData(initialPeriodFormData);
        setIsPeriodDialogOpen(true);
    };

    const handleOpenPeriodEdit = (period: any) => {
        setSelectedPeriod(period);
        setPeriodFormData({
            name: period.name,
            start_time: period.start_time?.substring(0, 5) || '',
            end_time: period.end_time?.substring(0, 5) || '',
            order: period.order || 1,
            is_active: period.is_active,
        });
        setIsPeriodDialogOpen(true);
    };

    const handlePeriodSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (selectedPeriod) {
            updatePeriodMutation.mutate({ id: selectedPeriod.id, data: periodFormData });
        } else {
            createPeriodMutation.mutate(periodFormData);
        }
    };

    const isYearPending = createYearMutation.isPending || updateYearMutation.isPending;
    const isSubjectPending = createSubjectMutation.isPending || updateSubjectMutation.isPending;
    const isClassroomPending = createClassroomMutation.isPending || updateClassroomMutation.isPending;
    const isPeriodPending = createPeriodMutation.isPending || updatePeriodMutation.isPending;

    return (
        <div className="p-4 space-y-6 sm:p-6">
            <PageHeader
                title="Master Data"
                description="Kelola data referensi sistem seperti tahun ajaran, kelas, mata pelajaran, dan periode waktu."
                icon={CalendarDays}
            />

            <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4">
                <TabsList className="grid w-full grid-cols-4">
                    <TabsTrigger value="academic-years" className="flex items-center gap-2">
                        <CalendarDays className="h-4 w-4" />
                        <span className="hidden sm:inline">Tahun Ajaran</span>
                        <span className="sm:hidden">Tahun</span>
                    </TabsTrigger>
                    <TabsTrigger value="subjects" className="flex items-center gap-2">
                        <BookOpen className="h-4 w-4" />
                        <span className="hidden sm:inline">Mata Pelajaran</span>
                        <span className="sm:hidden">Mapel</span>
                    </TabsTrigger>
                    <TabsTrigger value="classrooms" className="flex items-center gap-2">
                        <School className="h-4 w-4" />
                        <span>Kelas</span>
                    </TabsTrigger>
                    <TabsTrigger value="periods" className="flex items-center gap-2">
                        <Clock className="h-4 w-4" />
                        <span className="hidden sm:inline">Jam Pelajaran</span>
                        <span className="sm:hidden">Jam</span>
                    </TabsTrigger>
                </TabsList>

                {/* Academic Years Tab */}
                <TabsContent value="academic-years" className="space-y-4">
                    <div className="flex flex-col sm:flex-row gap-4 justify-between">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Cari tahun ajaran..."
                                value={yearSearchQuery}
                                onChange={(e) => setYearSearchQuery(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                        <Button onClick={handleOpenYearCreate}>
                            <Plus className="h-4 w-4 mr-2" />
                            Tambah Tahun Ajaran
                        </Button>
                    </div>

                    <Card>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-10"></TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Periode</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {yearsLoading ? (
                                        <TableRow>
                                            <TableCell colSpan={5} className="text-center py-10">
                                                <Loader2 className="h-6 w-6 animate-spin mx-auto text-muted-foreground" />
                                            </TableCell>
                                        </TableRow>
                                    ) : years.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={5} className="text-center py-10 text-muted-foreground">
                                                Belum ada tahun ajaran
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        years.map((year: AcademicYear) => (
                                            <TableRow key={year.id}>
                                                <TableCell>
                                                    <GripVertical className="h-4 w-4 text-muted-foreground cursor-grab" />
                                                </TableCell>
                                                <TableCell className="font-medium">{year.name}</TableCell>
                                                <TableCell>
                                                    <span className="text-sm text-muted-foreground">
                                                        {year.start_date && new Date(year.start_date).toLocaleDateString('id-ID')} - {year.end_date && new Date(year.end_date).toLocaleDateString('id-ID')}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    {year.is_active ? (
                                                        <Badge variant="default" className="bg-green-100 text-green-800 hover:bg-green-100">
                                                            <Check className="h-3 w-3 mr-1" />
                                                            Aktif
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="secondary">
                                                            <X className="h-3 w-3 mr-1" />
                                                            Nonaktif
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button variant="ghost" size="icon" onClick={() => handleOpenYearEdit(year)}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button variant="ghost" size="icon" onClick={() => { setSelectedYear(year); setIsYearDeleteDialogOpen(true); }}>
                                                            <Trash2 className="h-4 w-4 text-destructive" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                {/* Subjects Tab */}
                <TabsContent value="subjects" className="space-y-4">
                    <div className="flex flex-col sm:flex-row gap-4 justify-between">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Cari mata pelajaran..."
                                value={subjectSearchQuery}
                                onChange={(e) => setSubjectSearchQuery(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                        <Button onClick={handleOpenSubjectCreate}>
                            <Plus className="h-4 w-4 mr-2" />
                            Tambah Mata Pelajaran
                        </Button>
                    </div>

                    <Card>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-10"></TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {subjectsLoading ? (
                                        <TableRow>
                                            <TableCell colSpan={5} className="text-center py-10">
                                                <Loader2 className="h-6 w-6 animate-spin mx-auto text-muted-foreground" />
                                            </TableCell>
                                        </TableRow>
                                    ) : subjects.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={5} className="text-center py-10 text-muted-foreground">
                                                Belum ada mata pelajaran
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        subjects.map((subject: Subject) => (
                                            <TableRow key={subject.id}>
                                                <TableCell>
                                                    <GripVertical className="h-4 w-4 text-muted-foreground cursor-grab" />
                                                </TableCell>
                                                <TableCell>
                                                    <div>
                                                        <div className="font-medium">{subject.name}</div>
                                                        {subject.description && (
                                                            <div className="text-sm text-muted-foreground truncate max-w-[200px]">
                                                                {subject.description}
                                                            </div>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">{subject.code}</Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {subject.is_active ? (
                                                        <Badge variant="default" className="bg-green-100 text-green-800 hover:bg-green-100">
                                                            <Check className="h-3 w-3 mr-1" />
                                                            Aktif
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="secondary">
                                                            <X className="h-3 w-3 mr-1" />
                                                            Nonaktif
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button variant="ghost" size="icon" onClick={() => handleOpenSubjectEdit(subject)}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button variant="ghost" size="icon" onClick={() => { setSelectedSubject(subject); setIsSubjectDeleteDialogOpen(true); }}>
                                                            <Trash2 className="h-4 w-4 text-destructive" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                {/* Classrooms Tab */}
                <TabsContent value="classrooms" className="space-y-4">
                    <div className="flex flex-col sm:flex-row gap-4 justify-between">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Cari kelas..."
                                value={classroomSearchQuery}
                                onChange={(e) => setClassroomSearchQuery(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" onClick={() => setIsClassroomImportDialogOpen(true)}>
                                <Upload className="h-4 w-4 mr-2" />
                                Import Excel
                            </Button>
                            <Button onClick={handleOpenClassroomCreate}>
                                <Plus className="h-4 w-4 mr-2" />
                                Tambah Kelas
                            </Button>
                        </div>
                    </div>

                    <Card>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-10"></TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Tingkat</TableHead>
                                        <TableHead>Jurusan</TableHead>
                                        <TableHead>Nomor</TableHead>
                                        <TableHead>Kapasitas</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {classroomsLoading ? (
                                        <TableRow>
                                            <TableCell colSpan={8} className="text-center py-10">
                                                <Loader2 className="h-6 w-6 animate-spin mx-auto text-muted-foreground" />
                                            </TableCell>
                                        </TableRow>
                                    ) : classrooms.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={8} className="text-center py-10 text-muted-foreground">
                                                Belum ada kelas
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        classrooms.map((classroom: any) => (
                                            <TableRow key={classroom.id}>
                                                <TableCell>
                                                    <GripVertical className="h-4 w-4 text-muted-foreground cursor-grab" />
                                                </TableCell>
                                                <TableCell className="font-medium">{classroom.name}</TableCell>
                                                <TableCell>{classroom.grade_level || '-'}</TableCell>
                                                <TableCell>{classroom.major || '-'}</TableCell>
                                                <TableCell>{classroom.class_number || '-'}</TableCell>
                                                <TableCell>{classroom.capacity || '-'}</TableCell>
                                                <TableCell>
                                                    {classroom.is_active ? (
                                                        <Badge variant="default" className="bg-green-100 text-green-800 hover:bg-green-100">
                                                            <Check className="h-3 w-3 mr-1" />
                                                            Aktif
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="secondary">
                                                            <X className="h-3 w-3 mr-1" />
                                                            Nonaktif
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button variant="ghost" size="icon" onClick={() => handleOpenClassroomEdit(classroom)}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button variant="ghost" size="icon" onClick={() => { setSelectedClassroom(classroom); setIsClassroomDeleteDialogOpen(true); }}>
                                                            <Trash2 className="h-4 w-4 text-destructive" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                {/* Periods Tab (Now Time Slots) */}
                <TabsContent value="periods" className="space-y-4">
                    <div className="flex flex-col sm:flex-row gap-4 justify-between">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Cari jam pelajaran..."
                                value={periodSearchQuery}
                                onChange={(e) => setPeriodSearchQuery(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                        <Button onClick={handleOpenPeriodCreate}>
                            <Plus className="h-4 w-4 mr-2" />
                            Tambah Jam Pelajaran
                        </Button>
                    </div>

                    <Card>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-10"></TableHead>
                                        <TableHead>Urutan</TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Waktu</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {periodsLoading ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center py-10">
                                                <Loader2 className="h-6 w-6 animate-spin mx-auto text-muted-foreground" />
                                            </TableCell>
                                        </TableRow>
                                    ) : periods.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center py-10 text-muted-foreground">
                                                Belum ada jam pelajaran
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        periods.map((period: any) => (
                                            <TableRow key={period.id}>
                                                <TableCell>
                                                    <GripVertical className="h-4 w-4 text-muted-foreground cursor-grab" />
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline" className="w-8 justify-center">{period.order}</Badge>
                                                </TableCell>
                                                <TableCell className="font-medium">{period.name}</TableCell>
                                                <TableCell>
                                                    {period.start_time?.substring(0, 5)} - {period.end_time?.substring(0, 5)}
                                                </TableCell>
                                                <TableCell>
                                                    {period.is_active ? (
                                                        <Badge variant="default" className="bg-green-100 text-green-800 hover:bg-green-100">
                                                            <Check className="h-3 w-3 mr-1" />
                                                            Aktif
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="secondary">
                                                            <X className="h-3 w-3 mr-1" />
                                                            Nonaktif
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button variant="ghost" size="icon" onClick={() => handleOpenPeriodEdit(period)}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button variant="ghost" size="icon" onClick={() => { setSelectedPeriod(period); setIsPeriodDeleteDialogOpen(true); }}>
                                                            <Trash2 className="h-4 w-4 text-destructive" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

            {/* Academic Year Dialogs */}
            <Dialog open={isYearDialogOpen} onOpenChange={setIsYearDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{selectedYear ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran'}</DialogTitle>
                        <DialogDescription>
                            {selectedYear ? 'Edit detail tahun ajaran yang sudah ada.' : 'Tambahkan tahun ajaran baru ke sistem.'}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleYearSubmit} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="year-name">Nama</Label>
                            <Input
                                id="year-name"
                                value={yearFormData.name}
                                onChange={(e) => setYearFormData({ ...yearFormData, name: e.target.value })}
                                placeholder="Contoh: 2024/2025"
                                required
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="year-start">Tanggal Mulai</Label>
                                <Input
                                    id="year-start"
                                    type="date"
                                    value={yearFormData.start_date}
                                    onChange={(e) => setYearFormData({ ...yearFormData, start_date: e.target.value })}
                                    required
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="year-end">Tanggal Selesai</Label>
                                <Input
                                    id="year-end"
                                    type="date"
                                    value={yearFormData.end_date}
                                    onChange={(e) => setYearFormData({ ...yearFormData, end_date: e.target.value })}
                                    required
                                />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="year-semester">Semester</Label>
                            <Select
                                value={yearFormData.semester}
                                onValueChange={(value: 'odd' | 'even') => setYearFormData({ ...yearFormData, semester: value })}
                            >
                                <SelectTrigger id="year-semester">
                                    <SelectValue placeholder="Pilih semester" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="odd">Ganjil</SelectItem>
                                    <SelectItem value="even">Genap</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Switch
                                id="year-active"
                                checked={yearFormData.is_active}
                                onCheckedChange={(checked) => setYearFormData({ ...yearFormData, is_active: checked })}
                            />
                            <Label htmlFor="year-active">Status Aktif</Label>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsYearDialogOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={isYearPending}>
                                {isYearPending ? (
                                    <>
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                        Menyimpan...
                                    </>
                                ) : (
                                    'Simpan'
                                )}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <AlertDialog open={isYearDeleteDialogOpen} onOpenChange={setIsYearDeleteDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Apakah anda yakin?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Tindakan ini tidak dapat dibatalkan. Tahun ajaran yang dihapus tidak dapat dipulihkan kembali.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={() => setIsYearDeleteDialogOpen(false)}>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => selectedYear && deleteYearMutation.mutate(selectedYear.id)}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Subject Dialogs */}
            <Dialog open={isSubjectDialogOpen} onOpenChange={setIsSubjectDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{selectedSubject ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran'}</DialogTitle>
                        <DialogDescription>
                            {selectedSubject ? 'Edit detail mata pelajaran.' : 'Tambahkan mata pelajaran baru.'}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleSubjectSubmit} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="subject-name">Nama</Label>
                            <Input
                                id="subject-name"
                                value={subjectFormData.name}
                                onChange={(e) => setSubjectFormData({ ...subjectFormData, name: e.target.value })}
                                placeholder="Contoh: Matematika"
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="subject-code">Kode</Label>
                            <Input
                                id="subject-code"
                                value={subjectFormData.code}
                                onChange={(e) => setSubjectFormData({ ...subjectFormData, code: e.target.value })}
                                placeholder="Contoh: MTK"
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="subject-desc">Deskripsi</Label>
                            <Textarea
                                id="subject-desc"
                                value={subjectFormData.description}
                                onChange={(e) => setSubjectFormData({ ...subjectFormData, description: e.target.value })}
                                placeholder="Deskripsi mata pelajaran (opsional)"
                            />
                        </div>
                        <div className="flex items-center space-x-2">
                            <Switch
                                id="subject-active"
                                checked={subjectFormData.is_active}
                                onCheckedChange={(checked) => setSubjectFormData({ ...subjectFormData, is_active: checked })}
                            />
                            <Label htmlFor="subject-active">Status Aktif</Label>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsSubjectDialogOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={isSubjectPending}>
                                {isSubjectPending ? (
                                    <>
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                        Menyimpan...
                                    </>
                                ) : (
                                    'Simpan'
                                )}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <AlertDialog open={isSubjectDeleteDialogOpen} onOpenChange={setIsSubjectDeleteDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Apakah anda yakin?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Tindakan ini tidak dapat dibatalkan. Mata pelajaran ini akan dihapus dari sistem.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={() => setIsSubjectDeleteDialogOpen(false)}>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => selectedSubject && deleteSubjectMutation.mutate(selectedSubject.id)}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Classroom Dialogs */}
            <Dialog open={isClassroomDialogOpen} onOpenChange={setIsClassroomDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{selectedClassroom ? 'Edit Kelas' : 'Tambah Kelas'}</DialogTitle>
                        <DialogDescription>
                            {selectedClassroom ? 'Edit detail kelas.' : 'Tambahkan kelas baru.'}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleClassroomSubmit} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="classroom-name">Nama (Opsional - Auto Generate)</Label>
                            <Input
                                id="classroom-name"
                                value={classroomFormData.name}
                                onChange={(e) => setClassroomFormData({ ...classroomFormData, name: e.target.value })}
                                placeholder="Contoh: X IPA 1 (Kosongkan untuk auto)"
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="classroom-grade">Tingkat</Label>
                                <Select
                                    value={classroomFormData.grade_level}
                                    onValueChange={(value) => setClassroomFormData({ ...classroomFormData, grade_level: value })}
                                >
                                    <SelectTrigger id="classroom-grade">
                                        <SelectValue placeholder="Pilih Tingkat" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="X">X (Sepuluh)</SelectItem>
                                        <SelectItem value="XI">XI (Sebelas)</SelectItem>
                                        <SelectItem value="XII">XII (Dua Belas)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="classroom-major">Jurusan</Label>
                                <Select
                                    value={classroomFormData.major}
                                    onValueChange={(value) => setClassroomFormData({ ...classroomFormData, major: value })}
                                >
                                    <SelectTrigger id="classroom-major">
                                        <SelectValue placeholder="Pilih Jurusan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="IPA">IPA</SelectItem>
                                        <SelectItem value="IPS">IPS</SelectItem>
                                        <SelectItem value="BAHASA">Bahasa</SelectItem>
                                        <SelectItem value="UMUM">Umum</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="classroom-number">Nomor Kelas</Label>
                                <Input
                                    id="classroom-number"
                                    value={classroomFormData.class_number}
                                    onChange={(e) => setClassroomFormData({ ...classroomFormData, class_number: e.target.value })}
                                    placeholder="Contoh: 1"
                                    required
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="classroom-capacity">Kapasitas</Label>
                                <Input
                                    id="classroom-capacity"
                                    type="number"
                                    value={classroomFormData.capacity}
                                    onChange={(e) => setClassroomFormData({ ...classroomFormData, capacity: parseInt(e.target.value) })}
                                    required
                                />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="classroom-room">Ruangan (Opsional)</Label>
                            <Input
                                id="classroom-room"
                                value={classroomFormData.room}
                                onChange={(e) => setClassroomFormData({ ...classroomFormData, room: e.target.value })}
                                placeholder="Contoh: R-101"
                            />
                        </div>

                        <div className="flex items-center space-x-2">
                            <Switch
                                id="classroom-active"
                                checked={classroomFormData.is_active}
                                onCheckedChange={(checked) => setClassroomFormData({ ...classroomFormData, is_active: checked })}
                            />
                            <Label htmlFor="classroom-active">Status Aktif</Label>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsClassroomDialogOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={isClassroomPending}>
                                {isClassroomPending ? (
                                    <>
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                        Menyimpan...
                                    </>
                                ) : (
                                    'Simpan'
                                )}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <AlertDialog open={isClassroomDeleteDialogOpen} onOpenChange={setIsClassroomDeleteDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Apakah anda yakin?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Tindakan ini tidak dapat dibatalkan. Kelas ini akan dihapus dari sistem.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={() => setIsClassroomDeleteDialogOpen(false)}>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => selectedClassroom && deleteClassroomMutation.mutate(selectedClassroom.id)}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Period Dialogs (Now Jam Pelajaran / Time Slots) */}
            <Dialog open={isPeriodDialogOpen} onOpenChange={setIsPeriodDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{selectedPeriod ? 'Edit Jam Pelajaran' : 'Tambah Jam Pelajaran'}</DialogTitle>
                        <DialogDescription>
                            {selectedPeriod ? 'Edit detail jam pelajaran.' : 'Tambahkan jam pelajaran baru.'}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handlePeriodSubmit} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="period-name">Nama</Label>
                            <Input
                                id="period-name"
                                value={periodFormData.name}
                                onChange={(e) => setPeriodFormData({ ...periodFormData, name: e.target.value })}
                                placeholder="Contoh: Jam Ke-1"
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="period-order">Urutan</Label>
                            <Input
                                id="period-order"
                                type="number"
                                value={periodFormData.order}
                                onChange={(e) => setPeriodFormData({ ...periodFormData, order: parseInt(e.target.value) })}
                                required
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="period-start">Jam Mulai</Label>
                                <Input
                                    id="period-start"
                                    type="time"
                                    value={periodFormData.start_time}
                                    onChange={(e) => setPeriodFormData({ ...periodFormData, start_time: e.target.value })}
                                    required
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="period-end">Jam Selesai</Label>
                                <Input
                                    id="period-end"
                                    type="time"
                                    value={periodFormData.end_time}
                                    onChange={(e) => setPeriodFormData({ ...periodFormData, end_time: e.target.value })}
                                    required
                                />
                            </div>
                        </div>

                        <div className="flex items-center space-x-2">
                            <Switch
                                id="period-active"
                                checked={periodFormData.is_active}
                                onCheckedChange={(checked) => setPeriodFormData({ ...periodFormData, is_active: checked })}
                            />
                            <Label htmlFor="period-active">Status Aktif</Label>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsPeriodDialogOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={isPeriodPending}>
                                {isPeriodPending ? (
                                    <>
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                        Menyimpan...
                                    </>
                                ) : (
                                    'Simpan'
                                )}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <AlertDialog open={isPeriodDeleteDialogOpen} onOpenChange={setIsPeriodDeleteDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Apakah anda yakin?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Tindakan ini tidak dapat dibatalkan. Jam pelajaran ini akan dihapus dari sistem.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={() => setIsPeriodDeleteDialogOpen(false)}>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => selectedPeriod && deletePeriodMutation.mutate(selectedPeriod.id)}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* ============ Classroom Import Dialog ============ */}
            <ExcelImportDialog
                open={isClassroomImportDialogOpen}
                onOpenChange={setIsClassroomImportDialogOpen}
                title="Import Kelas"
                description="Upload file Excel untuk menambahkan data kelas secara massal."
                expectedColumns={classroomImportColumns}
                onFileImport={handleClassroomFileImport}
                onSuccess={handleClassroomImportSuccess}
                maxRows={200}
            />
        </div>
    );
}
