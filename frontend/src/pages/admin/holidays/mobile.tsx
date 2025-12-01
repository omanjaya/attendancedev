import { useState, useEffect } from 'react';
import {
    Plus,
    Search,
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
    ChevronLeft,
    Filter,
} from 'lucide-react';
import { useNavigate } from '@tanstack/react-router';
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
import { useHolidays } from '@/hooks/use-holidays';
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
    const {
        isLoading,
        holidays,
        fetchHolidays,
        createHoliday,
        updateHoliday,
        deleteHoliday,
        cancelHoliday,
    } = useHolidays();

    const [searchQuery, setSearchQuery] = useState('');
    const [typeFilter, setTypeFilter] = useState<HolidayType | 'all'>('all');
    const [yearFilter, setYearFilter] = useState(new Date().getFullYear());
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingHoliday, setEditingHoliday] = useState<Holiday | null>(null);
    const [deletingHoliday, setDeletingHoliday] = useState<Holiday | null>(null);
    const [isFilterOpen, setIsFilterOpen] = useState(false);

    useEffect(() => {
        fetchHolidays({ year: yearFilter });
    }, [fetchHolidays, yearFilter]);

    const handleSearch = () => {
        fetchHolidays({
            type: typeFilter !== 'all' ? typeFilter : undefined,
            year: yearFilter,
            search: searchQuery || undefined,
        });
    };

    useEffect(() => {
        handleSearch();
    }, [typeFilter, yearFilter]);

    const handleCreate = async (data: HolidayFormData) => {
        await createHoliday(data);
    };

    const handleUpdate = async (data: HolidayFormData) => {
        if (editingHoliday) {
            await updateHoliday(editingHoliday.id, data);
            setEditingHoliday(null);
        }
    };

    const handleDelete = async () => {
        if (deletingHoliday) {
            await deleteHoliday(deletingHoliday.id);
            setDeletingHoliday(null);
        }
    };

    const currentYear = new Date().getFullYear();
    const yearOptions = [currentYear - 1, currentYear, currentYear + 1];
    const activeFiltersCount = (typeFilter !== 'all' ? 1 : 0) + (yearFilter !== currentYear ? 1 : 0);

    return (
        <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-24">
            {/* Header Wrapper */}
            <div className="px-4 pt-3 pb-3 sticky top-0 z-20">
                <div className="bg-card/80 dark:bg-card/60 backdrop-blur-md rounded-3xl p-1.5 shadow-xl border border-border/40 dark:border-border/30">
                    <div className="bg-gradient-to-r from-pink-600 to-rose-600 px-4 py-3 rounded-[20px] flex items-center gap-3 shadow-lg">
                        <button
                            onClick={() => navigate({ to: '/' })}
                            className="p-2 -ml-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                        >
                            <ChevronLeft className="h-5 w-5 text-white" />
                        </button>
                        <h1 className="text-base font-bold text-white flex-1">Manajemen Hari Libur</h1>
                        <button
                            onClick={() => setIsFilterOpen(true)}
                            className="relative p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                        >
                            <Filter className="h-5 w-5 text-white" />
                            {activeFiltersCount > 0 && (
                                <span className="absolute top-1.5 right-1.5 h-2.5 w-2.5 bg-yellow-400 rounded-full border-2 border-pink-600" />
                            )}
                        </button>
                    </div>
                </div>
            </div>

            {/* Search Bar */}
            <div className="px-4 mb-4">
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        placeholder="Cari hari libur..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                        className="pl-9 bg-white dark:bg-gray-900/50 rounded-2xl border-border/50 shadow-sm"
                    />
                </div>
            </div>

            {/* Holidays List */}
            <div className="px-4 space-y-3">
                {isLoading ? (
                    <div className="flex flex-col items-center justify-center py-12 gap-3">
                        <Loader2 className="h-8 w-8 animate-spin text-primary" />
                        <p className="text-sm text-muted-foreground">Memuat data...</p>
                    </div>
                ) : holidays.length === 0 ? (
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
                    holidays.map((holiday) => (
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
                                                setEditingHoliday(holiday);
                                                setIsFormOpen(true);
                                            }}
                                        >
                                            <Edit className="mr-2 h-4 w-4" />
                                            Edit
                                        </DropdownMenuItem>
                                        {holiday.status === 'active' && (
                                            <DropdownMenuItem onClick={() => cancelHoliday(holiday.id)}>
                                                <XCircle className="mr-2 h-4 w-4" />
                                                Batalkan
                                            </DropdownMenuItem>
                                        )}
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            className="text-destructive"
                                            onClick={() => setDeletingHoliday(holiday)}
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
                    setEditingHoliday(null);
                    setIsFormOpen(true);
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
                                value={yearFilter.toString()}
                                onValueChange={(value) => setYearFilter(parseInt(value))}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Tahun" />
                                </SelectTrigger>
                                <SelectContent>
                                    {yearOptions.map((year) => (
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
                                value={typeFilter}
                                onValueChange={(value) => setTypeFilter(value as HolidayType | 'all')}
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
                open={isFormOpen}
                onOpenChange={(open) => {
                    setIsFormOpen(open);
                    if (!open) setEditingHoliday(null);
                }}
                holiday={editingHoliday}
                onSubmit={editingHoliday ? handleUpdate : handleCreate}
                isLoading={isLoading}
            />

            {/* Delete Confirmation */}
            <AlertDialog open={!!deletingHoliday} onOpenChange={() => setDeletingHoliday(null)}>
                <AlertDialogContent className="w-[90%] rounded-2xl">
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus Hari Libur?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Yakin ingin menghapus <strong>{deletingHoliday?.name}</strong>?
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter className="flex-row gap-2 justify-end">
                        <AlertDialogCancel className="mt-0">Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleDelete}
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
