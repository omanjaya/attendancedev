import { useState } from 'react';
import {
    Users2,
    Plus,
    Pencil,
    Trash2,
    Loader2,
    Check,
    X,
    GripVertical,
    Search,
    Clock,
    CalendarClock,
    Building2,
    Briefcase,
} from 'lucide-react';
import { PageHeader } from '@/components/shared';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
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
import { useEmployeeTypes, useDepartments, usePositions } from '@/hooks/use-master-data';
import {
    createEmployeeType,
    updateEmployeeType,
    deleteEmployeeType,
    createDepartment,
    updateDepartment,
    deleteDepartment,
    createPosition,
    updatePosition,
    deletePosition,
} from '@/lib/api/master-data';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import type { EmployeeType, Department, Position } from '@/types/master-data';

// ============ Employee Type Section ============
interface EmployeeTypeFormData {
    name: string;
    code: string;
    description: string;
    schedule_mode: 'fixed' | 'flexible';
    is_active: boolean;
}

const initialEmployeeTypeFormData: EmployeeTypeFormData = {
    name: '',
    code: '',
    description: '',
    schedule_mode: 'fixed',
    is_active: true,
};

// ============ Department Section ============
interface DepartmentFormData {
    name: string;
    code: string;
    description: string;
    is_active: boolean;
}

const initialDepartmentFormData: DepartmentFormData = {
    name: '',
    code: '',
    description: '',
    is_active: true,
};

// ============ Position Section ============
interface PositionFormData {
    name: string;
    code: string;
    description: string;
    is_active: boolean;
}

const initialPositionFormData: PositionFormData = {
    name: '',
    code: '',
    description: '',
    is_active: true,
};

export default function EmployeeTypesPage() {
    const queryClient = useQueryClient();
    const [activeTab, setActiveTab] = useState('employee-types');

    // ============ Employee Types State ============
    const [typeSearchQuery, setTypeSearchQuery] = useState('');
    const [isTypeDialogOpen, setIsTypeDialogOpen] = useState(false);
    const [isTypeDeleteDialogOpen, setIsTypeDeleteDialogOpen] = useState(false);
    const [selectedType, setSelectedType] = useState<EmployeeType | null>(null);
    const [typeFormData, setTypeFormData] = useState<EmployeeTypeFormData>(initialEmployeeTypeFormData);

    // ============ Departments State ============
    const [deptSearchQuery, setDeptSearchQuery] = useState('');
    const [isDeptDialogOpen, setIsDeptDialogOpen] = useState(false);
    const [isDeptDeleteDialogOpen, setIsDeptDeleteDialogOpen] = useState(false);
    const [selectedDept, setSelectedDept] = useState<Department | null>(null);
    const [deptFormData, setDeptFormData] = useState<DepartmentFormData>(initialDepartmentFormData);

    // ============ Positions State ============
    const [posSearchQuery, setPosSearchQuery] = useState('');
    const [isPosDialogOpen, setIsPosDialogOpen] = useState(false);
    const [isPosDeleteDialogOpen, setIsPosDeleteDialogOpen] = useState(false);
    const [selectedPos, setSelectedPos] = useState<Position | null>(null);
    const [posFormData, setPosFormData] = useState<PositionFormData>(initialPositionFormData);

    // ============ Fetch Data ============
    const { data: typesResponse, isLoading: typesLoading } = useEmployeeTypes({
        include_inactive: true,
        search: typeSearchQuery || undefined
    });
    const types = typesResponse?.data || [];

    const { data: deptsResponse, isLoading: deptsLoading } = useDepartments({
        include_inactive: true,
        search: deptSearchQuery || undefined
    });
    const departments = deptsResponse?.data || [];

    const { data: posResponse, isLoading: posLoading } = usePositions({
        include_inactive: true,
        search: posSearchQuery || undefined
    });
    const positions = posResponse?.data || [];

    // ============ Employee Type Mutations ============
    const createTypeMutation = useMutation({
        mutationFn: createEmployeeType,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['employee-types'] });
            toast.success('Jenis pegawai berhasil dibuat');
            setIsTypeDialogOpen(false);
            setTypeFormData(initialEmployeeTypeFormData);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal membuat jenis pegawai');
        },
    });

    const updateTypeMutation = useMutation({
        mutationFn: ({ id, data }: { id: string; data: Partial<EmployeeType> }) =>
            updateEmployeeType(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['employee-types'] });
            toast.success('Jenis pegawai berhasil diupdate');
            setIsTypeDialogOpen(false);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal mengupdate jenis pegawai');
        },
    });

    const deleteTypeMutation = useMutation({
        mutationFn: deleteEmployeeType,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['employee-types'] });
            toast.success('Jenis pegawai berhasil dihapus');
            setIsTypeDeleteDialogOpen(false);
            setSelectedType(null);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal menghapus jenis pegawai');
        },
    });

    // ============ Department Mutations ============
    const createDeptMutation = useMutation({
        mutationFn: createDepartment,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['departments'] });
            toast.success('Unit kerja berhasil dibuat');
            setIsDeptDialogOpen(false);
            setDeptFormData(initialDepartmentFormData);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal membuat unit kerja');
        },
    });

    const updateDeptMutation = useMutation({
        mutationFn: ({ id, data }: { id: string; data: Partial<Department> }) =>
            updateDepartment(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['departments'] });
            toast.success('Unit kerja berhasil diupdate');
            setIsDeptDialogOpen(false);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal mengupdate unit kerja');
        },
    });

    const deleteDeptMutation = useMutation({
        mutationFn: deleteDepartment,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['departments'] });
            toast.success('Unit kerja berhasil dihapus');
            setIsDeptDeleteDialogOpen(false);
            setSelectedDept(null);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal menghapus unit kerja');
        },
    });

    // ============ Position Mutations ============
    const createPosMutation = useMutation({
        mutationFn: createPosition,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['positions'] });
            toast.success('Jabatan berhasil dibuat');
            setIsPosDialogOpen(false);
            setPosFormData(initialPositionFormData);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal membuat jabatan');
        },
    });

    const updatePosMutation = useMutation({
        mutationFn: ({ id, data }: { id: string; data: Partial<Position> }) =>
            updatePosition(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['positions'] });
            toast.success('Jabatan berhasil diupdate');
            setIsPosDialogOpen(false);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal mengupdate jabatan');
        },
    });

    const deletePosMutation = useMutation({
        mutationFn: deletePosition,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['positions'] });
            toast.success('Jabatan berhasil dihapus');
            setIsPosDeleteDialogOpen(false);
            setSelectedPos(null);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal menghapus jabatan');
        },
    });

    // ============ Employee Type Handlers ============
    const handleOpenTypeCreate = () => {
        setSelectedType(null);
        setTypeFormData(initialEmployeeTypeFormData);
        setIsTypeDialogOpen(true);
    };

    const handleOpenTypeEdit = (type: EmployeeType) => {
        setSelectedType(type);
        setTypeFormData({
            name: type.name,
            code: type.code,
            description: type.description || '',
            schedule_mode: type.schedule_mode || 'fixed',
            is_active: type.is_active,
        });
        setIsTypeDialogOpen(true);
    };

    const handleTypeSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (selectedType) {
            updateTypeMutation.mutate({ id: selectedType.id, data: typeFormData });
        } else {
            createTypeMutation.mutate(typeFormData);
        }
    };

    // ============ Department Handlers ============
    const handleOpenDeptCreate = () => {
        setSelectedDept(null);
        setDeptFormData(initialDepartmentFormData);
        setIsDeptDialogOpen(true);
    };

    const handleOpenDeptEdit = (dept: Department) => {
        setSelectedDept(dept);
        setDeptFormData({
            name: dept.name,
            code: dept.code,
            description: dept.description || '',
            is_active: dept.is_active,
        });
        setIsDeptDialogOpen(true);
    };

    const handleDeptSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (selectedDept) {
            updateDeptMutation.mutate({ id: selectedDept.id, data: deptFormData });
        } else {
            createDeptMutation.mutate(deptFormData);
        }
    };

    // ============ Position Handlers ============
    const handleOpenPosCreate = () => {
        setSelectedPos(null);
        setPosFormData(initialPositionFormData);
        setIsPosDialogOpen(true);
    };

    const handleOpenPosEdit = (pos: Position) => {
        setSelectedPos(pos);
        setPosFormData({
            name: pos.name,
            code: pos.code,
            description: pos.description || '',
            is_active: pos.is_active,
        });
        setIsPosDialogOpen(true);
    };

    const handlePosSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (selectedPos) {
            updatePosMutation.mutate({ id: selectedPos.id, data: posFormData });
        } else {
            createPosMutation.mutate(posFormData);
        }
    };

    const isTypePending = createTypeMutation.isPending || updateTypeMutation.isPending;
    const isDeptPending = createDeptMutation.isPending || updateDeptMutation.isPending;
    const isPosPending = createPosMutation.isPending || updatePosMutation.isPending;

    return (
        <div className="p-4 space-y-6 sm:p-6">
            {/* Page Header */}
            <PageHeader
                title="Pengaturan Pegawai"
                description="Kelola jenis pegawai, unit kerja, dan jabatan"
                icon={Users2}
            />

            {/* Tabs */}
            <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4">
                <TabsList className="grid w-full grid-cols-3">
                    <TabsTrigger value="employee-types" className="flex items-center gap-2">
                        <Users2 className="h-4 w-4" />
                        <span className="hidden sm:inline">Jenis Pegawai</span>
                        <span className="sm:hidden">Jenis</span>
                    </TabsTrigger>
                    <TabsTrigger value="departments" className="flex items-center gap-2">
                        <Building2 className="h-4 w-4" />
                        <span className="hidden sm:inline">Unit Kerja</span>
                        <span className="sm:hidden">Unit</span>
                    </TabsTrigger>
                    <TabsTrigger value="positions" className="flex items-center gap-2">
                        <Briefcase className="h-4 w-4" />
                        <span>Jabatan</span>
                    </TabsTrigger>
                </TabsList>

                {/* ============ Employee Types Tab ============ */}
                <TabsContent value="employee-types" className="space-y-4">
                    <div className="flex flex-col sm:flex-row gap-4 justify-between">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Cari jenis pegawai..."
                                value={typeSearchQuery}
                                onChange={(e) => setTypeSearchQuery(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                        <Button onClick={handleOpenTypeCreate}>
                            <Plus className="h-4 w-4 mr-2" />
                            Tambah Jenis
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
                                        <TableHead>Mode Jadwal</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {typesLoading ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center py-10">
                                                <Loader2 className="h-6 w-6 animate-spin mx-auto text-muted-foreground" />
                                            </TableCell>
                                        </TableRow>
                                    ) : types.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center py-10 text-muted-foreground">
                                                Belum ada jenis pegawai
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        types.map((type: EmployeeType) => (
                                            <TableRow key={type.id}>
                                                <TableCell>
                                                    <GripVertical className="h-4 w-4 text-muted-foreground cursor-grab" />
                                                </TableCell>
                                                <TableCell>
                                                    <div>
                                                        <div className="font-medium">{type.name}</div>
                                                        {type.description && (
                                                            <div className="text-sm text-muted-foreground truncate max-w-[200px]">
                                                                {type.description}
                                                            </div>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">{type.code}</Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {type.schedule_mode === 'flexible' ? (
                                                        <Badge variant="secondary" className="gap-1">
                                                            <CalendarClock className="h-3 w-3" />
                                                            Fleksibel
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="default" className="gap-1 bg-blue-100 text-blue-800 hover:bg-blue-100">
                                                            <Clock className="h-3 w-3" />
                                                            Tetap
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {type.is_active ? (
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
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => handleOpenTypeEdit(type)}
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => {
                                                                setSelectedType(type);
                                                                setIsTypeDeleteDialogOpen(true);
                                                            }}
                                                        >
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

                {/* ============ Departments Tab ============ */}
                <TabsContent value="departments" className="space-y-4">
                    <div className="flex flex-col sm:flex-row gap-4 justify-between">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Cari unit kerja..."
                                value={deptSearchQuery}
                                onChange={(e) => setDeptSearchQuery(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                        <Button onClick={handleOpenDeptCreate}>
                            <Plus className="h-4 w-4 mr-2" />
                            Tambah Unit Kerja
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
                                        <TableHead>Jumlah Pegawai</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {deptsLoading ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center py-10">
                                                <Loader2 className="h-6 w-6 animate-spin mx-auto text-muted-foreground" />
                                            </TableCell>
                                        </TableRow>
                                    ) : departments.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center py-10 text-muted-foreground">
                                                Belum ada unit kerja
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        departments.map((dept: Department) => (
                                            <TableRow key={dept.id}>
                                                <TableCell>
                                                    <GripVertical className="h-4 w-4 text-muted-foreground cursor-grab" />
                                                </TableCell>
                                                <TableCell>
                                                    <div>
                                                        <div className="font-medium">{dept.name}</div>
                                                        {dept.description && (
                                                            <div className="text-sm text-muted-foreground truncate max-w-[200px]">
                                                                {dept.description}
                                                            </div>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">{dept.code}</Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {dept.employees_count !== undefined ? (
                                                        <Badge variant="secondary">{dept.employees_count} pegawai</Badge>
                                                    ) : (
                                                        <span className="text-muted-foreground">-</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {dept.is_active ? (
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
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => handleOpenDeptEdit(dept)}
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => {
                                                                setSelectedDept(dept);
                                                                setIsDeptDeleteDialogOpen(true);
                                                            }}
                                                        >
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

                {/* ============ Positions Tab ============ */}
                <TabsContent value="positions" className="space-y-4">
                    <div className="flex flex-col sm:flex-row gap-4 justify-between">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Cari jabatan..."
                                value={posSearchQuery}
                                onChange={(e) => setPosSearchQuery(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                        <Button onClick={handleOpenPosCreate}>
                            <Plus className="h-4 w-4 mr-2" />
                            Tambah Jabatan
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
                                        <TableHead>Jumlah Pegawai</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {posLoading ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center py-10">
                                                <Loader2 className="h-6 w-6 animate-spin mx-auto text-muted-foreground" />
                                            </TableCell>
                                        </TableRow>
                                    ) : positions.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center py-10 text-muted-foreground">
                                                Belum ada jabatan
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        positions.map((pos: Position) => (
                                            <TableRow key={pos.id}>
                                                <TableCell>
                                                    <GripVertical className="h-4 w-4 text-muted-foreground cursor-grab" />
                                                </TableCell>
                                                <TableCell>
                                                    <div>
                                                        <div className="font-medium">{pos.name}</div>
                                                        {pos.description && (
                                                            <div className="text-sm text-muted-foreground truncate max-w-[200px]">
                                                                {pos.description}
                                                            </div>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">{pos.code}</Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {pos.employees_count !== undefined ? (
                                                        <Badge variant="secondary">{pos.employees_count} pegawai</Badge>
                                                    ) : (
                                                        <span className="text-muted-foreground">-</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {pos.is_active ? (
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
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => handleOpenPosEdit(pos)}
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => {
                                                                setSelectedPos(pos);
                                                                setIsPosDeleteDialogOpen(true);
                                                            }}
                                                        >
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

            {/* ============ Employee Type Dialog ============ */}
            <Dialog open={isTypeDialogOpen} onOpenChange={setIsTypeDialogOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {selectedType ? 'Edit Jenis Pegawai' : 'Tambah Jenis Pegawai'}
                        </DialogTitle>
                        <DialogDescription>
                            {selectedType ? 'Ubah informasi jenis pegawai' : 'Masukkan data jenis pegawai baru'}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleTypeSubmit} className="space-y-4">
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="type-name">Nama *</Label>
                                <Input
                                    id="type-name"
                                    value={typeFormData.name}
                                    onChange={(e) => setTypeFormData(prev => ({
                                        ...prev,
                                        name: e.target.value
                                    }))}
                                    placeholder="Contoh: Guru, Staff"
                                    required
                                    autoFocus
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="type-code">Kode *</Label>
                                <Input
                                    id="type-code"
                                    value={typeFormData.code}
                                    onChange={(e) => setTypeFormData(prev => ({
                                        ...prev,
                                        code: e.target.value.toLowerCase().replace(/\s+/g, '_')
                                    }))}
                                    placeholder="Contoh: guru, staff"
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="type-mode">Mode Jadwal *</Label>
                                <Select
                                    value={typeFormData.schedule_mode}
                                    onValueChange={(v) => setTypeFormData(prev => ({
                                        ...prev,
                                        schedule_mode: v as 'fixed' | 'flexible'
                                    }))}
                                >
                                    <SelectTrigger id="type-mode">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="fixed">
                                            <div className="flex items-center gap-2">
                                                <Clock className="h-4 w-4" />
                                                <span>Tetap (Fixed Schedule)</span>
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="flexible">
                                            <div className="flex items-center gap-2">
                                                <CalendarClock className="h-4 w-4" />
                                                <span>Fleksibel (Flexible Schedule)</span>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p className="text-xs text-muted-foreground">
                                    {typeFormData.schedule_mode === 'fixed'
                                        ? 'Staff dengan jadwal kerja tetap (contoh: 08:00-16:00)'
                                        : 'Guru dengan jadwal mengajar fleksibel berdasarkan jam pelajaran'}
                                </p>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="type-desc">Deskripsi</Label>
                                <Textarea
                                    id="type-desc"
                                    value={typeFormData.description}
                                    onChange={(e) => setTypeFormData(prev => ({
                                        ...prev,
                                        description: e.target.value
                                    }))}
                                    placeholder="Deskripsi singkat..."
                                    rows={2}
                                />
                            </div>
                        </div>

                        <div className="flex items-center justify-between rounded-lg border p-3">
                            <div className="space-y-0.5">
                                <Label>Status Aktif</Label>
                                <p className="text-xs text-muted-foreground">
                                    Jenis pegawai aktif bisa digunakan
                                </p>
                            </div>
                            <Switch
                                checked={typeFormData.is_active}
                                onCheckedChange={(checked) =>
                                    setTypeFormData(prev => ({ ...prev, is_active: checked }))
                                }
                            />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsTypeDialogOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={isTypePending}>
                                {isTypePending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                                {selectedType ? 'Simpan' : 'Tambah'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* ============ Department Dialog ============ */}
            <Dialog open={isDeptDialogOpen} onOpenChange={setIsDeptDialogOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {selectedDept ? 'Edit Unit Kerja' : 'Tambah Unit Kerja'}
                        </DialogTitle>
                        <DialogDescription>
                            {selectedDept ? 'Ubah informasi unit kerja' : 'Masukkan data unit kerja baru'}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleDeptSubmit} className="space-y-4">
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="dept-name">Nama Unit Kerja *</Label>
                                <Input
                                    id="dept-name"
                                    value={deptFormData.name}
                                    onChange={(e) => setDeptFormData(prev => ({
                                        ...prev,
                                        name: e.target.value
                                    }))}
                                    placeholder="Contoh: Tata Usaha, Keuangan"
                                    required
                                    autoFocus
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="dept-code">Kode *</Label>
                                <Input
                                    id="dept-code"
                                    value={deptFormData.code}
                                    onChange={(e) => setDeptFormData(prev => ({
                                        ...prev,
                                        code: e.target.value.toLowerCase().replace(/\s+/g, '_')
                                    }))}
                                    placeholder="Contoh: tu, keuangan"
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="dept-desc">Deskripsi</Label>
                                <Textarea
                                    id="dept-desc"
                                    value={deptFormData.description}
                                    onChange={(e) => setDeptFormData(prev => ({
                                        ...prev,
                                        description: e.target.value
                                    }))}
                                    placeholder="Deskripsi singkat..."
                                    rows={2}
                                />
                            </div>
                        </div>

                        <div className="flex items-center justify-between rounded-lg border p-3">
                            <div className="space-y-0.5">
                                <Label>Status Aktif</Label>
                                <p className="text-xs text-muted-foreground">
                                    Unit kerja aktif bisa digunakan
                                </p>
                            </div>
                            <Switch
                                checked={deptFormData.is_active}
                                onCheckedChange={(checked) =>
                                    setDeptFormData(prev => ({ ...prev, is_active: checked }))
                                }
                            />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsDeptDialogOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={isDeptPending}>
                                {isDeptPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                                {selectedDept ? 'Simpan' : 'Tambah'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* ============ Position Dialog ============ */}
            <Dialog open={isPosDialogOpen} onOpenChange={setIsPosDialogOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {selectedPos ? 'Edit Jabatan' : 'Tambah Jabatan'}
                        </DialogTitle>
                        <DialogDescription>
                            {selectedPos ? 'Ubah informasi jabatan' : 'Masukkan data jabatan baru'}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handlePosSubmit} className="space-y-4">
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="pos-name">Nama Jabatan *</Label>
                                <Input
                                    id="pos-name"
                                    value={posFormData.name}
                                    onChange={(e) => setPosFormData(prev => ({
                                        ...prev,
                                        name: e.target.value
                                    }))}
                                    placeholder="Contoh: Kepala TU, Bendahara"
                                    required
                                    autoFocus
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="pos-code">Kode *</Label>
                                <Input
                                    id="pos-code"
                                    value={posFormData.code}
                                    onChange={(e) => setPosFormData(prev => ({
                                        ...prev,
                                        code: e.target.value.toLowerCase().replace(/\s+/g, '_')
                                    }))}
                                    placeholder="Contoh: kepala_tu, bendahara"
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="pos-desc">Deskripsi</Label>
                                <Textarea
                                    id="pos-desc"
                                    value={posFormData.description}
                                    onChange={(e) => setPosFormData(prev => ({
                                        ...prev,
                                        description: e.target.value
                                    }))}
                                    placeholder="Deskripsi singkat..."
                                    rows={2}
                                />
                            </div>
                        </div>

                        <div className="flex items-center justify-between rounded-lg border p-3">
                            <div className="space-y-0.5">
                                <Label>Status Aktif</Label>
                                <p className="text-xs text-muted-foreground">
                                    Jabatan aktif bisa digunakan
                                </p>
                            </div>
                            <Switch
                                checked={posFormData.is_active}
                                onCheckedChange={(checked) =>
                                    setPosFormData(prev => ({ ...prev, is_active: checked }))
                                }
                            />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsPosDialogOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={isPosPending}>
                                {isPosPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                                {selectedPos ? 'Simpan' : 'Tambah'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* ============ Delete Dialogs ============ */}
            <AlertDialog open={isTypeDeleteDialogOpen} onOpenChange={setIsTypeDeleteDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus Jenis Pegawai</AlertDialogTitle>
                        <AlertDialogDescription>
                            Apakah Anda yakin ingin menghapus jenis pegawai "{selectedType?.name}"?
                            Tindakan ini tidak dapat dibatalkan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => selectedType && deleteTypeMutation.mutate(selectedType.id)}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            disabled={deleteTypeMutation.isPending}
                        >
                            {deleteTypeMutation.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <AlertDialog open={isDeptDeleteDialogOpen} onOpenChange={setIsDeptDeleteDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus Unit Kerja</AlertDialogTitle>
                        <AlertDialogDescription>
                            Apakah Anda yakin ingin menghapus unit kerja "{selectedDept?.name}"?
                            Tindakan ini tidak dapat dibatalkan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => selectedDept && deleteDeptMutation.mutate(selectedDept.id)}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            disabled={deleteDeptMutation.isPending}
                        >
                            {deleteDeptMutation.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <AlertDialog open={isPosDeleteDialogOpen} onOpenChange={setIsPosDeleteDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus Jabatan</AlertDialogTitle>
                        <AlertDialogDescription>
                            Apakah Anda yakin ingin menghapus jabatan "{selectedPos?.name}"?
                            Tindakan ini tidak dapat dibatalkan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => selectedPos && deletePosMutation.mutate(selectedPos.id)}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            disabled={deletePosMutation.isPending}
                        >
                            {deletePosMutation.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
