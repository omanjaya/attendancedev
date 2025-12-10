import { useState } from 'react';
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
    X,
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
import { Checkbox } from '@/components/ui/checkbox';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useHolidaysPage } from '@/hooks/use-holidays-page';
import {
    holidayTypeLabels,
    holidayTypeColors,
    holidayTypeColorClasses,
    holidayStatusLabels,
    holidayStatusColors,
    type Holiday,
    type HolidayType,
    type HolidayFormData,
} from '@/types/holiday';
import { GenerateHolidaysDialog } from './GenerateHolidaysDialog';

// Type badge component
function TypeBadge({ type }: { type: HolidayType }) {
    return (
        <Badge
            variant="outline"
            className={`${holidayTypeColorClasses[type].border} ${holidayTypeColorClasses[type].text} ${holidayTypeColorClasses[type].bgSoft}`}
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
                            className={`truncate rounded px-1 py-0.5 text-xs text-white ${!h.color ? holidayTypeColorClasses[h.type].bg : ''}`}
                            style={h.color ? { backgroundColor: h.color } : undefined}
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

// Available roles for holiday assignment
const availableRoles = [
    { value: 'guru', label: 'Guru', color: 'bg-blue-500' },
    { value: 'pegawai', label: 'Pegawai', color: 'bg-green-500' },
    { value: 'kepala-sekolah', label: 'Kepala Sekolah', color: 'bg-purple-500' },
];

// Role badge component
function RoleBadge({ role }: { role: string }) {
    const roleConfig = availableRoles.find(r => r.value === role);
    if (!roleConfig) return <Badge variant="secondary">{role}</Badge>;
    
    return (
        <Badge 
            variant="secondary" 
            className={`${roleConfig.color} text-white text-xs`}
        >
            {roleConfig.label}
        </Badge>
    );
}

// Group holidays by year
function groupHolidaysByYear(holidays: Holiday[]): { year: number; holidays: Holiday[] }[] {
    const grouped = holidays.reduce((acc, holiday) => {
        const year = new Date(holiday.date).getFullYear();
        if (!acc[year]) {
            acc[year] = [];
        }
        acc[year].push(holiday);
        return acc;
    }, {} as Record<number, Holiday[]>);

    // Sort by year descending (newest first)
    return Object.entries(grouped)
        .map(([year, holidays]) => ({ year: parseInt(year), holidays }))
        .sort((a, b) => b.year - a.year);
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
    // Initialize state from props directly since we force remount on open/holiday change
    const [formData, setFormData] = useState<HolidayFormData>(() => {
        if (holiday) {
            return {
                name: holiday.name,
                description: holiday.description || '',
                date: holiday.date,
                end_date: holiday.end_date || '',
                type: holiday.type,
                is_recurring: holiday.is_recurring,
                is_paid: holiday.is_paid,
                color: holiday.color || holidayTypeColors[holiday.type],
                affected_roles: holiday.affected_roles || [],
            };
        }
        return {
            name: '',
            description: '',
            date: new Date().toISOString().split('T')[0],
            end_date: '',
            type: 'public_holiday',
            is_recurring: false,
            is_paid: true,
            color: '#DC2626',
            affected_roles: [],
        };
    });

    const handleRoleToggle = (role: string) => {
        setFormData((prev) => {
            const currentRoles = prev.affected_roles || [];
            const newRoles = currentRoles.includes(role)
                ? currentRoles.filter((r) => r !== role)
                : [...currentRoles, role];
            return { ...prev, affected_roles: newRoles };
        });
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        // Clean up affected_roles - send undefined if empty (means all roles)
        const submitData = {
            ...formData,
            affected_roles: formData.affected_roles?.length ? formData.affected_roles : undefined,
        };
        await onSubmit(submitData);
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[500px] max-h-[90vh] overflow-y-auto">
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

                        {/* Affected Roles Section */}
                        <div className="grid gap-2">
                            <Label>Berlaku Untuk Role</Label>
                            <p className="text-xs text-muted-foreground">
                                Kosongkan jika berlaku untuk semua karyawan
                            </p>
                            <div className="flex flex-wrap gap-2 p-3 border rounded-lg bg-muted/30">
                                {availableRoles.map((role) => {
                                    const isSelected = formData.affected_roles?.includes(role.value);
                                    return (
                                        <button
                                            key={role.value}
                                            type="button"
                                            onClick={() => handleRoleToggle(role.value)}
                                            className={`
                                                px-3 py-1.5 rounded-full text-sm font-medium transition-all
                                                ${isSelected 
                                                    ? `${role.color} text-white shadow-sm` 
                                                    : 'bg-background border hover:bg-muted'
                                                }
                                            `}
                                        >
                                            {role.label}
                                        </button>
                                    );
                                })}
                            </div>
                            {formData.affected_roles && formData.affected_roles.length > 0 && (
                                <p className="text-xs text-blue-600 dark:text-blue-400">
                                    Libur hanya untuk: {formData.affected_roles.map(r => 
                                        availableRoles.find(ar => ar.value === r)?.label
                                    ).join(', ')}
                                </p>
                            )}
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
    // Use shared hook for all logic
    const logic = useHolidaysPage();

    // Desktop-specific state only
    const [isGenerateOpen, setIsGenerateOpen] = useState(false);
    const [isBatchDeleteOpen, setIsBatchDeleteOpen] = useState(false);
    const [currentMonth, setCurrentMonth] = useState(new Date());
    const [activeTab, setActiveTab] = useState("list");

    const statsItems: StatItem[] = logic.stats ? [
        {
            label: 'Tahun Ini',
            value: logic.stats.holidays_this_year,
            icon: CalendarDays,
            color: 'primary',
        },
        {
            label: 'Bulan Ini',
            value: logic.stats.holidays_this_month,
            icon: CalendarCheck,
            color: 'success',
        },
        {
            label: 'Berulang',
            value: logic.stats.recurring_holidays,
            icon: Repeat,
            color: 'warning',
        },
        {
            label: 'Berbayar',
            value: logic.stats.paid_holidays,
            icon: CalendarIcon,
            color: 'info',
        },
    ] : [];

    return (
        <div className="space-y-4 p-4 sm:space-y-6 sm:p-6">
            {/* Page Header */}
            <PageHeader
                title="Hari Libur"
                description="Kelola hari libur nasional dan cuti bersama"
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={() => setIsGenerateOpen(true)}>
                            <RefreshCcw className="mr-2 h-4 w-4" />
                            Generate Otomatis
                        </Button>
                        <Button onClick={() => {
                            logic.setEditingHoliday(null);
                            logic.setIsFormOpen(true);
                        }}>
                            <Plus className="mr-2 h-4 w-4" />
                            Tambah Hari Libur
                        </Button>
                    </div>
                }
            />

            {/* Stats */}
            {logic.stats && <StatsGrid stats={statsItems} columns={4} variant="cards" />}

            <GenerateHolidaysDialog
                open={isGenerateOpen}
                onOpenChange={setIsGenerateOpen}
            />

            {/* Tabs */}
            <Tabs value={activeTab} onValueChange={setActiveTab}>
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
                                            value={logic.searchQuery}
                                            onChange={(e) => logic.setSearchQuery(e.target.value)}
                                            onKeyDown={(e) => e.key === 'Enter' && logic.handleSearch()}
                                            className="pl-9"
                                        />
                                    </div>
                                    <Button variant="outline" onClick={logic.handleSearch}>
                                        Cari
                                    </Button>
                                </div>
                                <div className="flex gap-2">
                                    <Select
                                        value={logic.typeFilter}
                                        onValueChange={(value) => logic.setTypeFilter(value as HolidayType | 'all')}
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
                                        value={logic.yearFilter.toString()}
                                        onValueChange={(value) => logic.setYearFilter(parseInt(value))}
                                    >
                                        <SelectTrigger className="w-[100px]">
                                            <SelectValue placeholder="Tahun" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {logic.yearOptions.map((year) => (
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
                            <div className="flex items-center justify-between">
                                <CardTitle className="flex items-center gap-2">
                                    <CalendarDays className="h-5 w-5" />
                                    Daftar Hari Libur ({logic.totalItems})
                                </CardTitle>
                                {logic.selectedIds.length > 0 && (
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm text-muted-foreground">
                                            {logic.selectedIds.length} dipilih
                                        </span>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={logic.clearSelection}
                                        >
                                            <X className="h-4 w-4 mr-1" />
                                            Batal
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => setIsBatchDeleteOpen(true)}
                                        >
                                            <Trash2 className="h-4 w-4 mr-1" />
                                            Hapus ({logic.selectedIds.length})
                                        </Button>
                                    </div>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent>
                            {logic.isLoading && !logic.holidays.length ? (
                                <div className="flex items-center justify-center py-8">
                                    <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                                </div>
                            ) : logic.holidays.length === 0 ? (
                                <div className="py-8 text-center text-muted-foreground">
                                    Tidak ada hari libur ditemukan
                                </div>
                            ) : (
                                <div className={`overflow-x-auto ${logic.isFetching ? 'opacity-60' : ''}`}>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-[40px]">
                                                    <Checkbox
                                                        checked={logic.isAllSelected}
                                                        onCheckedChange={logic.toggleSelectAll}
                                                    />
                                                </TableHead>
                                                <TableHead>Nama</TableHead>
                                                <TableHead>Tanggal</TableHead>
                                                <TableHead>Tipe</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Berlaku Untuk</TableHead>
                                                <TableHead className="w-[50px]"></TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {groupHolidaysByYear(logic.holidays).map((group) => (
                                                <>
                                                    {/* Year Group Header */}
                                                    <TableRow key={`year-${group.year}`} className="bg-muted/30 hover:bg-muted/30">
                                                        <TableCell colSpan={7} className="py-2">
                                                            <div className="flex items-center gap-2">
                                                                <CalendarDays className="h-4 w-4 text-primary" />
                                                                <span className="font-semibold text-primary">{group.year}</span>
                                                                <Badge variant="secondary" className="text-xs">
                                                                    {group.holidays.length} hari libur
                                                                </Badge>
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                    {/* Holidays in this year */}
                                                    {group.holidays.map((holiday) => (
                                                        <TableRow key={holiday.id} className={logic.selectedIds.includes(holiday.id) ? 'bg-muted/50' : ''}>
                                                            <TableCell>
                                                                <Checkbox
                                                                    checked={logic.selectedIds.includes(holiday.id)}
                                                                    onCheckedChange={() => logic.toggleSelect(holiday.id)}
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <div className="flex items-center gap-3">
                                                                    <div
                                                                        className={`h-3 w-3 rounded-full ${!holiday.color ? holidayTypeColorClasses[holiday.type].bg : ''}`}
                                                                        style={holiday.color ? { backgroundColor: holiday.color } : undefined}
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
                                                                    {logic.formatDate(holiday.date)}
                                                                    {holiday.end_date && holiday.end_date !== holiday.date && (
                                                                        <div className="text-muted-foreground">
                                                                            s/d {logic.formatDate(holiday.end_date)}
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
                                                                {holiday.affected_roles && holiday.affected_roles.length > 0 ? (
                                                                    <div className="flex flex-wrap gap-1">
                                                                        {holiday.affected_roles.map((role) => (
                                                                            <RoleBadge key={role} role={role} />
                                                                        ))}
                                                                    </div>
                                                                ) : (
                                                                    <Badge variant="outline" className="text-xs">
                                                                        Semua
                                                                    </Badge>
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
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            )}

                            {/* Pagination */}
                            {logic.totalPages > 1 && (
                                <div className="flex items-center justify-between pt-4 border-t">
                                    <div className="text-sm text-muted-foreground">
                                        Menampilkan {logic.holidays.length} dari {logic.totalItems} hari libur
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => logic.handlePageChange(logic.currentPage - 1)}
                                            disabled={logic.currentPage <= 1 || logic.isFetching}
                                        >
                                            <ChevronLeft className="h-4 w-4" />
                                            Prev
                                        </Button>
                                        <div className="flex items-center gap-1">
                                            {Array.from({ length: Math.min(5, logic.totalPages) }, (_, i) => {
                                                let pageNum: number;
                                                if (logic.totalPages <= 5) {
                                                    pageNum = i + 1;
                                                } else if (logic.currentPage <= 3) {
                                                    pageNum = i + 1;
                                                } else if (logic.currentPage >= logic.totalPages - 2) {
                                                    pageNum = logic.totalPages - 4 + i;
                                                } else {
                                                    pageNum = logic.currentPage - 2 + i;
                                                }
                                                return (
                                                    <Button
                                                        key={pageNum}
                                                        variant={logic.currentPage === pageNum ? 'default' : 'outline'}
                                                        size="sm"
                                                        className="w-8 h-8 p-0"
                                                        onClick={() => logic.handlePageChange(pageNum)}
                                                        disabled={logic.isFetching}
                                                    >
                                                        {pageNum}
                                                    </Button>
                                                );
                                            })}
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => logic.handlePageChange(logic.currentPage + 1)}
                                            disabled={logic.currentPage >= logic.totalPages || logic.isFetching}
                                        >
                                            Next
                                            <ChevronRight className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="calendar">
                    <CalendarView
                        holidays={logic.holidays}
                        currentMonth={currentMonth}
                        onMonthChange={setCurrentMonth}
                    />
                </TabsContent>
            </Tabs>

            {/* Holiday Form Dialog */}
            <HolidayFormDialog
                key={`${logic.isFormOpen}-${logic.editingHoliday?.id || 'new'}`}
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
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus Hari Libur?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Anda yakin ingin menghapus hari libur <strong>{logic.deletingHoliday?.name}</strong>?
                            Tindakan ini tidak dapat dibatalkan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => logic.handleDelete()}
                            className="bg-destructive hover:bg-destructive/90"
                        >
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Batch Delete Confirmation */}
            <AlertDialog open={isBatchDeleteOpen} onOpenChange={setIsBatchDeleteOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus {logic.selectedIds.length} Hari Libur?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Anda yakin ingin menghapus <strong>{logic.selectedIds.length} hari libur</strong> yang dipilih?
                            Tindakan ini tidak dapat dibatalkan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => {
                                logic.handleBatchDelete();
                                setIsBatchDeleteOpen(false);
                            }}
                            className="bg-destructive hover:bg-destructive/90"
                        >
                            Hapus Semua
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
