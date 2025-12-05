import { useState } from 'react';
import {
    Users2,
    Plus,
    Pencil,
    Trash2,
    Clock,
    CalendarClock,
    Loader2,
    Check,
    X,
    GripVertical,
    Search,
} from 'lucide-react';
import { PageHeader } from '@/components/shared';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
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
import { Checkbox } from '@/components/ui/checkbox';
import { useEmployeeTypes } from '@/hooks/use-master-data';
import { getEmployeeTypeFeatures, createEmployeeType, updateEmployeeType, deleteEmployeeType } from '@/lib/api/master-data';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import type { EmployeeType } from '@/types/master-data';

interface EmployeeTypeFormData {
    name: string;
    code: string;
    description: string;
    schedule_mode: 'fixed' | 'flexible';
    default_start_time: string;
    default_end_time: string;
    late_tolerance_minutes: number;
    require_schedule_for_attendance: boolean;
    can_override_by_teaching: boolean;
    features: string[];
    is_active: boolean;
}

const initialFormData: EmployeeTypeFormData = {
    name: '',
    code: '',
    description: '',
    schedule_mode: 'fixed',
    default_start_time: '07:30',
    default_end_time: '15:30',
    late_tolerance_minutes: 15,
    require_schedule_for_attendance: true,
    can_override_by_teaching: false,
    features: [],
    is_active: true,
};

export default function EmployeeTypesPage() {
    const queryClient = useQueryClient();
    const [searchQuery, setSearchQuery] = useState('');
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
    const [selectedType, setSelectedType] = useState<EmployeeType | null>(null);
    const [formData, setFormData] = useState<EmployeeTypeFormData>(initialFormData);

    // Fetch employee types
    const { data: typesResponse, isLoading } = useEmployeeTypes({
        include_inactive: true,
        search: searchQuery || undefined
    });
    const types = typesResponse?.data || [];

    // Fetch available features
    const { data: featuresResponse } = useQuery({
        queryKey: ['employee-type-features'],
        queryFn: getEmployeeTypeFeatures,
    });
    const availableFeatures = featuresResponse?.data || {};

    // Mutations
    const createMutation = useMutation({
        mutationFn: createEmployeeType,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['employee-types'] });
            toast.success('Jenis pegawai berhasil dibuat');
            handleCloseDialog();
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal membuat jenis pegawai');
        },
    });

    const updateMutation = useMutation({
        mutationFn: ({ id, data }: { id: string; data: Partial<EmployeeType> }) =>
            updateEmployeeType(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['employee-types'] });
            toast.success('Jenis pegawai berhasil diupdate');
            handleCloseDialog();
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal mengupdate jenis pegawai');
        },
    });

    const deleteMutation = useMutation({
        mutationFn: deleteEmployeeType,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['employee-types'] });
            toast.success('Jenis pegawai berhasil dihapus');
            setIsDeleteDialogOpen(false);
            setSelectedType(null);
        },
        onError: (error: any) => {
            toast.error(error?.response?.data?.message || 'Gagal menghapus jenis pegawai');
        },
    });

    const handleOpenCreate = () => {
        setSelectedType(null);
        setFormData(initialFormData);
        setIsDialogOpen(true);
    };

    const handleOpenEdit = (type: EmployeeType) => {
        setSelectedType(type);
        setFormData({
            name: type.name,
            code: type.code,
            description: type.description || '',
            schedule_mode: type.schedule_mode,
            default_start_time: type.default_start_time || '07:30',
            default_end_time: type.default_end_time || '15:30',
            late_tolerance_minutes: type.late_tolerance_minutes,
            require_schedule_for_attendance: type.require_schedule_for_attendance,
            can_override_by_teaching: type.can_override_by_teaching,
            features: type.features || [],
            is_active: type.is_active,
        });
        setIsDialogOpen(true);
    };

    const handleCloseDialog = () => {
        setIsDialogOpen(false);
        setSelectedType(null);
        setFormData(initialFormData);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (selectedType) {
            updateMutation.mutate({ id: selectedType.id, data: formData });
        } else {
            createMutation.mutate(formData);
        }
    };

    const handleFeatureToggle = (feature: string, checked: boolean) => {
        if (checked) {
            setFormData(prev => ({ ...prev, features: [...prev.features, feature] }));
        } else {
            setFormData(prev => ({
                ...prev,
                features: prev.features.filter(f => f !== feature)
            }));
        }
    };

    const isPending = createMutation.isPending || updateMutation.isPending;

    return (
        <div className="p-4 space-y-6 sm:p-6">
            {/* Page Header */}
            <PageHeader
                title="Jenis Pegawai"
                description="Kelola tipe pegawai dan aturan jadwal masing-masing"
                icon={Users2}
                actions={
                    <Button onClick={handleOpenCreate}>
                        <Plus className="h-4 w-4 mr-2" />
                        Tambah Jenis
                    </Button>
                }
            />

            {/* Search & Filter */}
            <div className="flex flex-col sm:flex-row gap-4">
                <div className="relative flex-1">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Cari jenis pegawai..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="pl-9"
                    />
                </div>
            </div>

            {/* Employee Types Table */}
            <Card>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-10"></TableHead>
                                <TableHead>Nama</TableHead>
                                <TableHead>Kode</TableHead>
                                <TableHead>Mode Jadwal</TableHead>
                                <TableHead>Jam Kerja</TableHead>
                                <TableHead>Toleransi</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {isLoading ? (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center py-10">
                                        <Loader2 className="h-6 w-6 animate-spin mx-auto text-muted-foreground" />
                                    </TableCell>
                                </TableRow>
                            ) : types.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center py-10 text-muted-foreground">
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
                                            <Badge
                                                variant={type.schedule_mode === 'fixed' ? 'default' : 'secondary'}
                                                className="gap-1"
                                            >
                                                {type.schedule_mode === 'fixed' ? (
                                                    <Clock className="h-3 w-3" />
                                                ) : (
                                                    <CalendarClock className="h-3 w-3" />
                                                )}
                                                {type.schedule_mode === 'fixed' ? 'Tetap' : 'Fleksibel'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {type.default_start_time && type.default_end_time ? (
                                                <span className="text-sm">
                                                    {type.default_start_time} - {type.default_end_time}
                                                </span>
                                            ) : (
                                                <span className="text-sm text-muted-foreground">-</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <span className="text-sm">{type.late_tolerance_minutes} menit</span>
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
                                                    onClick={() => handleOpenEdit(type)}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => {
                                                        setSelectedType(type);
                                                        setIsDeleteDialogOpen(true);
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

            {/* Create/Edit Dialog */}
            <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            {selectedType ? 'Edit Jenis Pegawai' : 'Tambah Jenis Pegawai'}
                        </DialogTitle>
                        <DialogDescription>
                            Atur tipe pegawai dan aturan jadwal yang berlaku
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Basic Info */}
                        <div className="space-y-4">
                            <h4 className="font-medium text-sm text-muted-foreground uppercase tracking-wide">
                                Informasi Dasar
                            </h4>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Nama *</Label>
                                    <Input
                                        id="name"
                                        value={formData.name}
                                        onChange={(e) => setFormData(prev => ({
                                            ...prev,
                                            name: e.target.value
                                        }))}
                                        placeholder="Pegawai Tetap"
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="code">Kode *</Label>
                                    <Input
                                        id="code"
                                        value={formData.code}
                                        onChange={(e) => setFormData(prev => ({
                                            ...prev,
                                            code: e.target.value.toLowerCase().replace(/\s+/g, '_')
                                        }))}
                                        placeholder="tetap"
                                        required
                                    />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="description">Deskripsi</Label>
                                <Textarea
                                    id="description"
                                    value={formData.description}
                                    onChange={(e) => setFormData(prev => ({
                                        ...prev,
                                        description: e.target.value
                                    }))}
                                    placeholder="Deskripsi jenis pegawai..."
                                    rows={2}
                                />
                            </div>
                        </div>

                        {/* Schedule Settings */}
                        <div className="space-y-4">
                            <h4 className="font-medium text-sm text-muted-foreground uppercase tracking-wide">
                                Pengaturan Jadwal
                            </h4>

                            <div className="space-y-2">
                                <Label>Mode Jadwal *</Label>
                                <Select
                                    value={formData.schedule_mode}
                                    onValueChange={(value: 'fixed' | 'flexible') =>
                                        setFormData(prev => ({ ...prev, schedule_mode: value }))
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="fixed">
                                            <div className="flex items-center gap-2">
                                                <Clock className="h-4 w-4" />
                                                <div>
                                                    <div>Tetap</div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Jam kerja tetap setiap hari
                                                    </div>
                                                </div>
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="flexible">
                                            <div className="flex items-center gap-2">
                                                <CalendarClock className="h-4 w-4" />
                                                <div>
                                                    <div>Fleksibel</div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Berdasarkan jadwal mengajar
                                                    </div>
                                                </div>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {formData.schedule_mode === 'fixed' && (
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="start_time">Jam Masuk Default</Label>
                                        <Input
                                            id="start_time"
                                            type="time"
                                            value={formData.default_start_time}
                                            onChange={(e) => setFormData(prev => ({
                                                ...prev,
                                                default_start_time: e.target.value
                                            }))}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="end_time">Jam Pulang Default</Label>
                                        <Input
                                            id="end_time"
                                            type="time"
                                            value={formData.default_end_time}
                                            onChange={(e) => setFormData(prev => ({
                                                ...prev,
                                                default_end_time: e.target.value
                                            }))}
                                        />
                                    </div>
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="tolerance">Toleransi Keterlambatan (menit)</Label>
                                <Input
                                    id="tolerance"
                                    type="number"
                                    min={0}
                                    max={120}
                                    value={formData.late_tolerance_minutes}
                                    onChange={(e) => setFormData(prev => ({
                                        ...prev,
                                        late_tolerance_minutes: parseInt(e.target.value) || 0
                                    }))}
                                />
                            </div>
                        </div>

                        {/* Attendance Rules */}
                        <div className="space-y-4">
                            <h4 className="font-medium text-sm text-muted-foreground uppercase tracking-wide">
                                Aturan Absensi
                            </h4>

                            <div className="space-y-3">
                                <div className="flex items-center justify-between rounded-lg border p-4">
                                    <div className="space-y-0.5">
                                        <Label>Wajib Ada Jadwal untuk Absen</Label>
                                        <p className="text-sm text-muted-foreground">
                                            Pegawai tidak bisa absen jika tidak ada jadwal yang di-assign
                                        </p>
                                    </div>
                                    <Switch
                                        checked={formData.require_schedule_for_attendance}
                                        onCheckedChange={(checked) =>
                                            setFormData(prev => ({
                                                ...prev,
                                                require_schedule_for_attendance: checked
                                            }))
                                        }
                                    />
                                </div>

                                <div className="flex items-center justify-between rounded-lg border p-4">
                                    <div className="space-y-0.5">
                                        <Label>Bisa Override oleh Jadwal Mengajar</Label>
                                        <p className="text-sm text-muted-foreground">
                                            Jam kerja ditentukan oleh jadwal mengajar (untuk guru honor)
                                        </p>
                                    </div>
                                    <Switch
                                        checked={formData.can_override_by_teaching}
                                        onCheckedChange={(checked) =>
                                            setFormData(prev => ({
                                                ...prev,
                                                can_override_by_teaching: checked
                                            }))
                                        }
                                    />
                                </div>

                                <div className="flex items-center justify-between rounded-lg border p-4">
                                    <div className="space-y-0.5">
                                        <Label>Status Aktif</Label>
                                        <p className="text-sm text-muted-foreground">
                                            Jenis pegawai ini bisa digunakan
                                        </p>
                                    </div>
                                    <Switch
                                        checked={formData.is_active}
                                        onCheckedChange={(checked) =>
                                            setFormData(prev => ({ ...prev, is_active: checked }))
                                        }
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Features */}
                        <div className="space-y-4">
                            <h4 className="font-medium text-sm text-muted-foreground uppercase tracking-wide">
                                Fitur yang Diizinkan
                            </h4>

                            <div className="grid grid-cols-2 gap-3">
                                {Object.entries(availableFeatures).map(([key, label]) => (
                                    <div
                                        key={key}
                                        className="flex items-center space-x-2 rounded-lg border p-3"
                                    >
                                        <Checkbox
                                            id={key}
                                            checked={formData.features.includes(key)}
                                            onCheckedChange={(checked) =>
                                                handleFeatureToggle(key, checked as boolean)
                                            }
                                        />
                                        <Label
                                            htmlFor={key}
                                            className="text-sm font-normal cursor-pointer"
                                        >
                                            {label}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleCloseDialog}
                            >
                                Batal
                            </Button>
                            <Button type="submit" disabled={isPending}>
                                {isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                                {selectedType ? 'Simpan Perubahan' : 'Tambah Jenis'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <AlertDialog open={isDeleteDialogOpen} onOpenChange={setIsDeleteDialogOpen}>
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
                            onClick={() => selectedType && deleteMutation.mutate(selectedType.id)}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            disabled={deleteMutation.isPending}
                        >
                            {deleteMutation.isPending && (
                                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                            )}
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
