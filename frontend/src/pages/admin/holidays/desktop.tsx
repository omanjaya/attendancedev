import { useState, useEffect } from 'react';
import {
    Calendar as CalendarIcon,
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
    ChevronRight,
} from 'lucide-react';
import { PageHeader, StatsGrid, type StatItem } from '@/components/shared';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useHolidays } from '@/hooks/use-holidays';
import {
    holidayTypeLabels,
    holidayTypeColors,
    holidayStatusLabels,
    holidayStatusColors,
    type Holiday,
    type HolidayType,
    type HolidayFormData,
    type HolidayStatistics,
} from '@/types/holiday';

// Type badge component
function TypeBadge({ type }: { type: HolidayType }) {
    return (
        <Badge
            variant="outline"
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

// Simple calendar view component
function CalendarView({ holidays, currentMonth, onMonthChange }: {
    holidays: Holiday[];
    currentMonth: Date;
    onMonthChange: (date: Date) => void;
}) {
    const year = currentMonth.getFullYear();
    const month = currentMonth.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDay = firstDay.getDay();

    const monthNames = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

    const getHolidaysForDate = (day: number) => {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        return holidays.filter(h => {
            if (h.status !== 'active') return false;
            const start = h.date;
            const end = h.end_date || h.date;
            return dateStr >= start && dateStr <= end;
        });
    };

    const days = [];
    // Empty cells before first day
    for (let i = 0; i < startingDay; i++) {
        days.push(<div key={`empty-${i}`} className="p-2" />);
    }
    // Days of month
    for (let day = 1; day <= daysInMonth; day++) {
        const dayHolidays = getHolidaysForDate(day);
        const isToday = new Date().toDateString() === new Date(year, month, day).toDateString();

        days.push(
            <div
                key={day}
                className={`min-h-[80px] border-t p-2 ${isToday ? 'bg-primary/5' : ''}`}
            >
                <div className={`text-sm font-medium ${isToday ? 'text-primary' : ''}`}>
                    {day}
                </div>
                <div className="mt-1 space-y-1">
                    {dayHolidays.slice(0, 2).map((h) => (
                        <div
                            key={h.id}
                            className="truncate rounded px-1 py-0.5 text-xs text-white"
                            style={{ backgroundColor: holidayTypeColors[h.type] }}
                            title={h.name}
                        >
                            {h.name}
                        </div>
                    ))}
                    {dayHolidays.length > 2 && (
                        <div className="text-xs text-muted-foreground">
                            +{dayHolidays.length - 2} lagi
                        </div>
                    )}
                </div>
            </div>
        );
    }

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-lg">
                    {monthNames[month]} {year}
                </CardTitle>
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="icon"
                        onClick={() => onMonthChange(new Date(year, month - 1, 1))}
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => onMonthChange(new Date())}
                    >
                        Hari Ini
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        onClick={() => onMonthChange(new Date(year, month + 1, 1))}
                    >
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <div className="grid grid-cols-7 gap-0">
                    {dayNames.map((name) => (
                        <div key={name} className="p-2 text-center text-sm font-medium text-muted-foreground">
                            {name}
                        </div>
                    ))}
                    {days}
                </div>
            </CardContent>
        </Card>
    );
}

// Holiday form dialog
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
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>{holiday ? 'Edit Hari Libur' : 'Tambah Hari Libur'}</DialogTitle>
                    <DialogDescription>
                        {holiday
                            ? 'Edit informasi hari libur yang ada'
                            : 'Tambahkan hari libur baru ke kalender'}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama Hari Libur</Label>
                            <Input
                                id="name"
                                value={formData.name}
                                onChange={(e) => setFormData((prev) => ({ ...prev, name: e.target.value }))}
                                placeholder="Tahun Baru"
                                required
                            />
                        </div>

                        <div className="grid gap-2">
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

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="date">Tanggal Mulai</Label>
                                <Input
                                    id="date"
                                    type="date"
                                    value={formData.date}
                                    onChange={(e) => setFormData((prev) => ({ ...prev, date: e.target.value }))}
                                    required
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="end_date">Tanggal Selesai (Opsional)</Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    value={formData.end_date}
                                    onChange={(e) => setFormData((prev) => ({ ...prev, end_date: e.target.value }))}
                                    min={formData.date}
                                />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Deskripsi (Opsional)</Label>
                            <Textarea
                                id="description"
                                value={formData.description}
                                onChange={(e) =>
                                    setFormData((prev) => ({ ...prev, description: e.target.value }))
                                }
                                placeholder="Keterangan hari libur..."
                            />
                        </div>

                        <div className="flex items-center justify-between">
                            <Label htmlFor="is_recurring">Berulang Setiap Tahun</Label>
                            <Switch
                                id="is_recurring"
                                checked={formData.is_recurring}
                                onCheckedChange={(checked) =>
                                    setFormData((prev) => ({ ...prev, is_recurring: checked }))
                                }
                            />
                        </div>

                        <div className="flex items-center justify-between">
                            <Label htmlFor="is_paid">Libur Berbayar</Label>
                            <Switch
                                id="is_paid"
                                checked={formData.is_paid}
                                onCheckedChange={(checked) =>
                                    setFormData((prev) => ({ ...prev, is_paid: checked }))
                                }
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={isLoading}>
                            {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            {holiday ? 'Simpan' : 'Tambah'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export function DesktopHolidaysPage() {
    const {
        isLoading,
        holidays,
        fetchHolidays,
        createHoliday,
        updateHoliday,
        deleteHoliday,
        cancelHoliday,
        getStatistics,
    } = useHolidays();

    const [searchQuery, setSearchQuery] = useState('');
    const [typeFilter, setTypeFilter] = useState<HolidayType | 'all'>('all');
    const [yearFilter, setYearFilter] = useState(new Date().getFullYear());
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingHoliday, setEditingHoliday] = useState<Holiday | null>(null);
    const [deletingHoliday, setDeletingHoliday] = useState<Holiday | null>(null);
    const [stats, setStats] = useState<HolidayStatistics | null>(null);
    const [currentMonth, setCurrentMonth] = useState(new Date());

    useEffect(() => {
        fetchHolidays({ year: yearFilter });
        loadStats();
    }, [fetchHolidays, yearFilter]);

    const loadStats = async () => {
        const statistics = await getStatistics();
        setStats(statistics);
    };

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
        loadStats();
    };

    const handleUpdate = async (data: HolidayFormData) => {
        if (editingHoliday) {
            await updateHoliday(editingHoliday.id, data);
            setEditingHoliday(null);
            loadStats();
        }
    };

    const handleDelete = async () => {
        if (deletingHoliday) {
            await deleteHoliday(deletingHoliday.id);
            setDeletingHoliday(null);
            loadStats();
        }
    };

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString('id-ID', {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    };

    const currentYear = new Date().getFullYear();
    const yearOptions = [currentYear - 1, currentYear, currentYear + 1];

    const statsItems: StatItem[] = stats ? [
        {
            label: 'Tahun Ini',
            value: stats.holidays_this_year,
            icon: CalendarDays,
            color: 'primary',
        },
        {
            label: 'Bulan Ini',
            value: stats.holidays_this_month,
            icon: CalendarCheck,
            color: 'success',
        },
        {
            label: 'Berulang',
            value: stats.recurring_holidays,
            icon: Repeat,
            color: 'warning',
        },
        {
            label: 'Berbayar',
            value: stats.paid_holidays,
            icon: CalendarIcon,
            color: 'info',
        },
    ] : [];

    return (
        <div className="space-y-4 p-4 sm:space-y-6 sm:p-6">
            {/* Page Header */}
            <PageHeader
                title="Manajemen Hari Libur"
                description="Kelola kalender hari libur dan cuti bersama"
                icon={CalendarDays}
                actions={
                    <Button
                        onClick={() => {
                            setEditingHoliday(null);
                            setIsFormOpen(true);
                        }}
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        Tambah Hari Libur
                    </Button>
                }
            />

            {/* Stats */}
            {stats && <StatsGrid stats={statsItems} columns={4} variant="cards" />}

            {/* Tabs */}
            <Tabs defaultValue="list">
                <TabsList>
                    <TabsTrigger value="list">Daftar</TabsTrigger>
                    <TabsTrigger value="calendar">Kalender</TabsTrigger>
                </TabsList>

                <TabsContent value="list" className="space-y-4">
                    {/* Filters */}
                    <Card>
                        <CardContent className="p-4">
                            <div className="flex flex-col gap-4 md:flex-row md:items-center">
                                <div className="flex flex-1 gap-2">
                                    <div className="relative flex-1">
                                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            placeholder="Cari hari libur..."
                                            value={searchQuery}
                                            onChange={(e) => setSearchQuery(e.target.value)}
                                            onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                                            className="pl-9"
                                        />
                                    </div>
                                    <Button variant="outline" onClick={handleSearch}>
                                        Cari
                                    </Button>
                                </div>
                                <div className="flex gap-2">
                                    <Select
                                        value={typeFilter}
                                        onValueChange={(value) => setTypeFilter(value as HolidayType | 'all')}
                                    >
                                        <SelectTrigger className="w-[160px]">
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
                                    <Select
                                        value={yearFilter.toString()}
                                        onValueChange={(value) => setYearFilter(parseInt(value))}
                                    >
                                        <SelectTrigger className="w-[100px]">
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
                            </div>
                        </CardContent>
                    </Card>

                    {/* Holidays Table */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CalendarDays className="h-5 w-5" />
                                Daftar Hari Libur ({holidays.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {isLoading ? (
                                <div className="flex items-center justify-center py-8">
                                    <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                                </div>
                            ) : holidays.length === 0 ? (
                                <div className="py-8 text-center text-muted-foreground">
                                    Tidak ada hari libur ditemukan
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Nama</TableHead>
                                                <TableHead>Tanggal</TableHead>
                                                <TableHead>Tipe</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Keterangan</TableHead>
                                                <TableHead className="w-[50px]"></TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {holidays.map((holiday) => (
                                                <TableRow key={holiday.id}>
                                                    <TableCell>
                                                        <div className="flex items-center gap-3">
                                                            <div
                                                                className="h-3 w-3 rounded-full"
                                                                style={{ backgroundColor: holiday.color || holidayTypeColors[holiday.type] }}
                                                            />
                                                            <div>
                                                                <div className="font-medium">{holiday.name}</div>
                                                                {holiday.description && (
                                                                    <div className="text-sm text-muted-foreground line-clamp-1">
                                                                        {holiday.description}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="text-sm">
                                                            {formatDate(holiday.date)}
                                                            {holiday.end_date && holiday.end_date !== holiday.date && (
                                                                <div className="text-muted-foreground">
                                                                    s/d {formatDate(holiday.end_date)}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <TypeBadge type={holiday.type} />
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex flex-col gap-1">
                                                            <StatusBadge status={holiday.status} />
                                                            <div className="flex gap-1">
                                                                {holiday.is_recurring && (
                                                                    <Badge variant="secondary" className="text-xs">
                                                                        <Repeat className="mr-1 h-3 w-3" />
                                                                        Berulang
                                                                    </Badge>
                                                                )}
                                                                {holiday.is_paid && (
                                                                    <Badge variant="secondary" className="text-xs">
                                                                        Berbayar
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        {holiday.affected_roles && holiday.affected_roles.length > 0 && (
                                                            <div className="text-sm text-muted-foreground">
                                                                Untuk: {holiday.affected_roles.join(', ')}
                                                            </div>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger asChild>
                                                                <Button variant="ghost" size="icon">
                                                                    <MoreHorizontal className="h-4 w-4" />
                                                                </Button>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent align="end">
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
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="calendar">
                    <CalendarView
                        holidays={holidays}
                        currentMonth={currentMonth}
                        onMonthChange={setCurrentMonth}
                    />
                </TabsContent>
            </Tabs>

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
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus Hari Libur?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Anda yakin ingin menghapus hari libur <strong>{deletingHoliday?.name}</strong>?
                            Tindakan ini tidak dapat dibatalkan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
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
