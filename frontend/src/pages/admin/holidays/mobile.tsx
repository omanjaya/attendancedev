import { useState, useEffect } from 'react';
import {
    Plus,
    MoreHorizontal,
    Edit,
    Trash2,
    Loader2,
    XCircle,
    RefreshCcw,
    CalendarDays,
    CalendarCheck,
    CalendarX,
    Repeat,
    Filter,
} from 'lucide-react';
import { useNavigate } from '@tanstack/react-router';
import { MobilePageHeader } from '@/components/mobile';
import { SearchBar } from '@/components/shared';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
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
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
    SheetFooter,
} from '@/components/ui/sheet';
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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Switch } from '@/components/ui/switch';
import { useHolidaysPage } from '@/hooks/use-holidays-page';
import {
    holidayTypeLabels,
    holidayTypeColors,
    holidayStatusLabels,
    holidayStatusColors,
    type Holiday,
    type HolidayType,
    type HolidayFormData,
} from '@/types/holiday';

// Type badge component
function TypeBadge({ type }: { type: HolidayType }) {
    return (
        <Badge
            variant="outline"
            className="text-[10px] px-2 py-0.5 h-5"
            style={{
                borderColor: holidayTypeColors[type],
                color: holidayTypeColors[type],
                backgroundColor: `${holidayTypeColors[type]}10`,
            }}
        >
            {holidayTypeLabels[type]}
        </Badge>
    );
}

// Status badge
function StatusBadge({ status }: { status: Holiday['status'] }) {
    const icons = {
        active: <CalendarCheck className="mr-1 h-3 w-3" />,
        cancelled: <CalendarX className="mr-1 h-3 w-3" />,
        moved: <RefreshCcw className="mr-1 h-3 w-3" />,
    };

    return (
        <Badge
            variant="outline"
            className="text-[10px] px-2 py-0.5 h-5"
            style={{
                borderColor: holidayStatusColors[status],
                color: holidayStatusColors[status],
                backgroundColor: `${holidayStatusColors[status]}10`,
            }}
        >
            {icons[status]}
            {holidayStatusLabels[status]}
        </Badge>
    );
}

// Holiday form dialog (reused but styled for mobile)
function HolidayFormDialog({
    open,
    onOpenChange,
    holiday,
    onSubmit,
    isLoading,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    holiday?: Holiday | null;
    onSubmit: (data: HolidayFormData) => Promise<void>;
    isLoading: boolean;
}) {
    const [formData, setFormData] = useState<HolidayFormData>({
        name: '',
        description: '',
        date: new Date().toISOString().split('T')[0],
        end_date: '',
        type: 'public_holiday',
        is_recurring: false,
        is_paid: true,
        color: '#DC2626',
    });

    useEffect(() => {
        if (holiday) {
            setFormData({
                name: holiday.name,
                description: holiday.description || '',
                date: holiday.date,
                end_date: holiday.end_date || '',
                type: holiday.type,
                is_recurring: holiday.is_recurring,
                is_paid: holiday.is_paid,
                color: holiday.color || holidayTypeColors[holiday.type],
            });
        } else {
            setFormData({
                name: '',
                description: '',
                date: new Date().toISOString().split('T')[0],
                end_date: '',
                type: 'public_holiday',
                is_recurring: false,
                is_paid: true,
                color: '#DC2626',
            });
        }
    }, [holiday, open]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        await onSubmit(formData);
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="w-[90%] rounded-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{holiday ? 'Edit Hari Libur' : 'Tambah Hari Libur'}</DialogTitle>
                    <DialogDescription>
                        {holiday ? 'Perbarui data hari libur' : 'Tambahkan hari libur baru'}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4 py-2">
                    <div className="space-y-2">
                        <Label htmlFor="name">Nama Hari Libur</Label>
                        <Input
                            id="name"
                            value={formData.name}
                            onChange={(e) => setFormData((prev) => ({ ...prev, name: e.target.value }))}
                            placeholder="Nama hari libur"
                            required
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="type">Tipe</Label>
                        <Select
                            value={formData.type}
                            onValueChange={(value: HolidayType) => {
                                setFormData((prev) => ({
                                    ...prev,
                                    type: value,
                                    color: holidayTypeColors[value],
                                }));
                            }}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih tipe" />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(holidayTypeLabels).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-2">
                            <Label htmlFor="date">Mulai</Label>
                            <Input
                                id="date"
                                type="date"
                                value={formData.date}
                                onChange={(e) => setFormData((prev) => ({ ...prev, date: e.target.value }))}
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="end_date">Selesai</Label>
                            <Input
                                id="end_date"
                                type="date"
                                value={formData.end_date}
                                onChange={(e) => setFormData((prev) => ({ ...prev, end_date: e.target.value }))}
                                min={formData.date}
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="description">Deskripsi</Label>
                        <Textarea
                            id="description"
                            value={formData.description}
                            onChange={(e) =>
                                setFormData((prev) => ({ ...prev, description: e.target.value }))
                            }
                            placeholder="Keterangan..."
                            className="min-h-[80px]"
                        />
                    </div>
                    <div className="flex items-center justify-between py-2">
                        <Label htmlFor="is_recurring" className="text-sm">Berulang Tiap Tahun</Label>
                        <Switch
                            id="is_recurring"
                            checked={formData.is_recurring}
                            onCheckedChange={(checked) =>
                                setFormData((prev) => ({ ...prev, is_recurring: checked }))
                            }
                        />
                    </div>
                    <div className="flex items-center justify-between py-2">
                        <Label htmlFor="is_paid" className="text-sm">Libur Berbayar</Label>
                        <Switch
                            id="is_paid"
                            checked={formData.is_paid}
                            onCheckedChange={(checked) =>
                                setFormData((prev) => ({ ...prev, is_paid: checked }))
                            }
                        />
                    </div>
                    <div className="pt-4 flex gap-2 justify-end">
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={isLoading}>
                            {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Simpan
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export function MobileHolidaysPage() {
    const navigate = useNavigate();

    // Use shared hook for all logic
    const logic = useHolidaysPage();

    // Mobile-specific state only
    const [isFilterOpen, setIsFilterOpen] = useState(false);

    const currentYear = new Date().getFullYear();
    const activeFiltersCount = (logic.typeFilter !== 'all' ? 1 : 0) + (logic.yearFilter !== currentYear ? 1 : 0);

    return (
        <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-24">
            {/* Header */}
            <MobilePageHeader
                title="Manajemen Hari Libur"
                onBack={() => navigate({ to: '/admin/dashboard' })}
                gradient="pink"
                actions={
                    <button
                        onClick={() => setIsFilterOpen(true)}
                        className="relative p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                    >
                        <Filter className="h-5 w-5 text-white" />
                        {activeFiltersCount > 0 && (
                            <span className="absolute top-1.5 right-1.5 h-2.5 w-2.5 bg-yellow-400 rounded-full border-2 border-pink-600" />
                        )}
                    </button>
                }
            />

            {/* Search Bar */}
            <div className="px-4 mb-4">
                <SearchBar
                    value={logic.searchQuery}
                    onChange={logic.setSearchQuery}
                    placeholder="Cari hari libur..."
                    onSearch={logic.handleSearch}
                    inputClassName="bg-white dark:bg-gray-900/50 rounded-2xl border-border/50 shadow-sm"
                />
            </div>

            {/* Holidays List */}
            <div className="px-4 space-y-3">
                {logic.isLoading ? (
                    <div className="flex flex-col items-center justify-center py-12 gap-3">
                        <Loader2 className="h-8 w-8 animate-spin text-primary" />
                        <p className="text-sm text-muted-foreground">Memuat data...</p>
                    </div>
                ) : logic.holidays.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-12 gap-3 text-center">
                        <div className="h-16 w-16 rounded-full bg-muted flex items-center justify-center">
                            <CalendarDays className="h-8 w-8 text-muted-foreground" />
                        </div>
                        <div>
                            <p className="font-medium">Tidak ada hari libur</p>
                            <p className="text-sm text-muted-foreground">Coba ubah filter atau tahun</p>
                        </div>
                    </div>
                ) : (
                    logic.holidays.map((holiday: Holiday) => (
                        <div
                            key={holiday.id}
                            className="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-border/50 flex flex-col gap-3"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className="flex items-center gap-3">
                                    <div
                                        className="h-10 w-10 rounded-xl flex items-center justify-center text-white shadow-sm font-bold text-xs"
                                        style={{ backgroundColor: holiday.color || holidayTypeColors[holiday.type] }}
                                    >
                                        {new Date(holiday.date).getDate()}
                                    </div>
                                    <div>
                                        <h3 className="font-semibold text-sm line-clamp-1">{holiday.name}</h3>
                                        <p className="text-xs text-muted-foreground line-clamp-1">
                                            {new Date(holiday.date).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })}
                                        </p>
                                    </div>
                                </div>
                                <StatusBadge status={holiday.status} />
                            </div>

                            <div className="flex flex-wrap gap-2 text-xs text-muted-foreground border-t border-border/50 pt-3 mt-1">
                                <TypeBadge type={holiday.type} />
                                {holiday.is_recurring && (
                                    <Badge variant="secondary" className="text-[10px] px-2 h-5">
                                        <Repeat className="mr-1 h-3 w-3" />
                                        Berulang
                                    </Badge>
                                )}
                                {holiday.is_paid && (
                                    <Badge variant="secondary" className="text-[10px] px-2 h-5">
                                        Berbayar
                                    </Badge>
                                )}

                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="ghost" size="sm" className="h-8 w-8 p-0 rounded-full ml-auto">
                                            <MoreHorizontal className="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="w-48">
                                        <DropdownMenuItem
                                            onClick={() => {
                                                logic.setEditingHoliday(holiday);
                                                logic.setIsFormOpen(true);
                                            }}
                                        >
                                            <Edit className="mr-2 h-4 w-4" />
                                            Edit
                                        </DropdownMenuItem>
                                        {holiday.status === 'active' && (
                                            <DropdownMenuItem onClick={() => logic.handleCancel(holiday.id)}>
                                                <XCircle className="mr-2 h-4 w-4" />
                                                Batalkan
                                            </DropdownMenuItem>
                                        )}
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            className="text-destructive"
                                            onClick={() => logic.setDeletingHoliday(holiday)}
                                        >
                                            <Trash2 className="mr-2 h-4 w-4" />
                                            Hapus
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    ))
                )}
            </div>

            {/* FAB Add Holiday */}
            <Button
                onClick={() => {
                    logic.setEditingHoliday(null);
                    logic.setIsFormOpen(true);
                }}
                className="fixed bottom-6 right-6 h-14 w-14 rounded-full shadow-xl bg-pink-600 hover:bg-pink-700 text-white z-50"
            >
                <Plus className="h-6 w-6" />
            </Button>

            {/* Filter Sheet */}
            <Sheet open={isFilterOpen} onOpenChange={setIsFilterOpen}>
                <SheetContent side="bottom" className="rounded-t-[20px]">
                    <SheetHeader>
                        <SheetTitle>Filter Hari Libur</SheetTitle>
                        <SheetDescription>
                            Tampilkan hari libur berdasarkan kriteria
                        </SheetDescription>
                    </SheetHeader>
                    <div className="py-6 space-y-6">
                        <div className="space-y-2">
                            <Label>Tahun</Label>
                            <Select
                                value={logic.yearFilter.toString()}
                                onValueChange={(value) => logic.setYearFilter(parseInt(value))}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Tahun" />
                                </SelectTrigger>
                                <SelectContent>
                                    {logic.yearOptions.map((year: number) => (
                                        <SelectItem key={year} value={year.toString()}>
                                            {year}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label>Tipe</Label>
                            <Select
                                value={logic.typeFilter}
                                onValueChange={(value) => logic.setTypeFilter(value as HolidayType | 'all')}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Semua Tipe" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Tipe</SelectItem>
                                    {Object.entries(holidayTypeLabels).map(([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <SheetFooter>
                        <Button onClick={() => setIsFilterOpen(false)} className="w-full">
                            Terapkan Filter
                        </Button>
                    </SheetFooter>
                </SheetContent>
            </Sheet>

            {/* Holiday Form Dialog */}
            <HolidayFormDialog
                open={logic.isFormOpen}
                onOpenChange={(open) => {
                    logic.setIsFormOpen(open);
                    if (!open) logic.setEditingHoliday(null);
                }}
                holiday={logic.editingHoliday}
                onSubmit={logic.editingHoliday ? logic.handleUpdate : logic.handleCreate}
                isLoading={logic.isLoading}
            />

            {/* Delete Confirmation */}
            <AlertDialog open={!!logic.deletingHoliday} onOpenChange={() => logic.setDeletingHoliday(null)}>
                <AlertDialogContent className="w-[90%] rounded-2xl">
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus Hari Libur?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Yakin ingin menghapus <strong>{logic.deletingHoliday?.name}</strong>?
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter className="flex-row gap-2 justify-end">
                        <AlertDialogCancel className="mt-0">Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => logic.handleDelete()}
                            className="bg-destructive hover:bg-destructive/90"
                        >
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
