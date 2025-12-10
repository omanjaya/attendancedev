import { useState } from 'react';
import {
    Calendar,
    CalendarDays,
    Clock,
    FileText,
    Filter,
    Plus,
    CheckCircle2,
    XCircle,
    AlertCircle,
    Loader2,
    Eye,
    MoreHorizontal,
    Download,
    User,
    Building,
    CalendarCheck,
    CalendarX,
    RefreshCw,
    Search,
} from 'lucide-react';
import { EmptyState, PageHeader } from '@/components/shared';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
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
    DialogFooter,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RejectDialog } from '@/components/leave/RejectDialog';
import { LeaveCalendar } from '@/components/leave/LeaveCalendar';
import { Textarea } from '@/components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    useLeaveRequests,
    useLeaveBalance,
    useCreateLeaveRequest,
    useCancelLeaveRequest,
    useLeaveStatistics,
} from '@/hooks';
import { useAdminLeavePage } from '@/hooks/use-admin-leave-page';
import { useAuthStore } from '@/stores/auth-store';
import type { LeaveFilters } from '@/lib/api/leave';
import {
    leaveTypeLabels,
    leaveTypeColors,
    leaveStatusLabels,
    type LeaveType,
    type LeaveStatus,
    type LeaveRequestFormData,
} from '@/types/leave';
import { LeaveBadge } from '@/components/status';
import { CardSkeleton, StatSkeleton } from '@/components/states';

// Rejection Dialog Component


// Leave request form dialog
function LeaveRequestDialog({
    open,
    onOpenChange,
    onSubmit,
    isLoading,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSubmit: (data: LeaveRequestFormData) => void;
    isLoading: boolean;
}) {
    const [formData, setFormData] = useState<LeaveRequestFormData>({
        type: 'annual',
        start_date: '',
        end_date: '',
        duration_type: 'full_day',
        reason: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onSubmit(formData);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <FileText className="h-5 w-5 text-primary" />
                        Ajukan Cuti Baru
                    </DialogTitle>
                    <DialogDescription>
                        Isi form di bawah untuk mengajukan permohonan cuti
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="type">Jenis Cuti</Label>
                        <Select
                            value={formData.type}
                            onValueChange={(value: LeaveType) => setFormData({ ...formData, type: value })}
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(leaveTypeLabels).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        <div className="flex items-center gap-2">
                                            <div
                                                className="h-2 w-2 rounded-full"
                                                style={{ backgroundColor: leaveTypeColors[value as LeaveType] }}
                                            />
                                            {label}
                                        </div>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="start_date">Tanggal Mulai</Label>
                            <Input
                                id="start_date"
                                type="date"
                                value={formData.start_date}
                                onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="end_date">Tanggal Selesai</Label>
                            <Input
                                id="end_date"
                                type="date"
                                value={formData.end_date}
                                onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
                                required
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="duration_type">Durasi</Label>
                        <Select
                            value={formData.duration_type}
                            onValueChange={(value: 'full_day' | 'half_day_am' | 'half_day_pm') =>
                                setFormData({ ...formData, duration_type: value })
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="full_day">Sehari Penuh</SelectItem>
                                <SelectItem value="half_day_am">Setengah Hari (Pagi)</SelectItem>
                                <SelectItem value="half_day_pm">Setengah Hari (Siang)</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="reason">Alasan</Label>
                        <Textarea
                            id="reason"
                            value={formData.reason}
                            onChange={(e) => setFormData({ ...formData, reason: e.target.value })}
                            placeholder="Jelaskan alasan pengajuan cuti..."
                            required
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="emergency_contact">Kontak Darurat (opsional)</Label>
                            <Input
                                id="emergency_contact"
                                value={formData.emergency_contact || ''}
                                onChange={(e) => setFormData({ ...formData, emergency_contact: e.target.value })}
                                placeholder="Nama kontak"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="emergency_phone">No. Telepon</Label>
                            <Input
                                id="emergency_phone"
                                value={formData.emergency_phone || ''}
                                onChange={(e) => setFormData({ ...formData, emergency_phone: e.target.value })}
                                placeholder="08xxxxxxxxxx"
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={isLoading}>
                            {isLoading ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                            Ajukan Cuti
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

// Desktop version (original implementation)
export function DesktopAdminLeavePage() {
    const { hasPermission } = useAuthStore();

    // Use shared hook for common logic
    const logic = useAdminLeavePage();

    // Desktop-specific state
    const [activeTab, setActiveTab] = useState('requests');
    const [typeFilter, setTypeFilter] = useState<string>('all');
    const [showCreateDialog, setShowCreateDialog] = useState(false);

    // Build filters with both status and type
    const filters: LeaveFilters = {};
    if (logic.statusFilter !== 'all') filters.status = logic.statusFilter as LeaveStatus;
    if (typeFilter !== 'all') filters.type = typeFilter as LeaveType;

    // Desktop-specific data fetching
    const {
        data: leaveRequestsData,
        isLoading: isLoadingRequests,
        error: requestsError,
        refetch: refetchRequests,
    } = useLeaveRequests(filters);

    const {
        data: leaveBalance,
        isLoading: isLoadingBalance,
    } = useLeaveBalance();

    const leaveRequests = leaveRequestsData?.data || [];

    // Desktop-specific mutations
    const createLeaveRequestMutation = useCreateLeaveRequest();
    const cancelLeaveRequestMutation = useCancelLeaveRequest();

    // Handle create
    const handleCreate = async (data: LeaveRequestFormData) => {
        try {
            await createLeaveRequestMutation.mutateAsync(data);
            setShowCreateDialog(false);
        } catch {
            // Error handled in hook
        }
    };

    // Handle cancel
    const handleCancel = async (id: string) => {
        try {
            await cancelLeaveRequestMutation.mutateAsync(id);
        } catch {
            // Error handled in hook
        }
    };

    // Fetch stats
    const { data: statsData } = useLeaveStatistics();

    // Stats
    const stats = {
        pending: statsData?.pending_requests ?? 0,
        approved: statsData?.approved_requests ?? 0,
        total_days_this_month: statsData?.total_days_this_month ?? 0,
    };

    // Default balance values
    const balance = leaveBalance || {
        annual_total: 12,
        annual_used: 0,
        annual_remaining: 12,
        sick_total: 12,
        sick_used: 0,
        sick_remaining: 12,
        carry_forward: 0,
        carry_forward_expiry: undefined,
    };

    return (
        <div className="space-y-4 p-4 sm:space-y-6 sm:p-6 max-w-[1600px] mx-auto">
            {/* Page Header */}
            <PageHeader
                title="Cuti & Izin"
                description="Kelola pengajuan cuti dan izin karyawan"
                icon={CalendarDays}
                actions={
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm">
                            <Download className="mr-2 h-4 w-4" />
                            Export
                        </Button>
                        {hasPermission('create_leave_requests') && (
                            <Button size="sm" onClick={() => setShowCreateDialog(true)}>
                                <Plus className="mr-2 h-4 w-4" />
                                Ajukan Cuti
                            </Button>
                        )}
                    </div>
                }
            />

            {/* Stats & Balance */}
            {isLoadingBalance ? (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="sm:col-span-2">
                        <StatSkeleton />
                    </div>
                    <StatSkeleton />
                    <StatSkeleton />
                </div>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {/* Leave Balance Card */}
                    <Card className="sm:col-span-2 border-primary/10 bg-primary/5">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Saldo Cuti Anda
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <div className="flex items-center justify-between text-sm">
                                    <span className="flex items-center gap-2">
                                        <div className="h-2 w-2 rounded-full bg-blue-500" />
                                        Cuti Tahunan
                                    </span>
                                    <span className="font-medium">
                                        {balance.annual_remaining} / {balance.annual_total} hari
                                    </span>
                                </div>
                                <Progress
                                    value={(balance.annual_used / balance.annual_total) * 100}
                                    className="h-2 bg-blue-100 [&>div]:bg-blue-500"
                                />
                            </div>
                            <div className="space-y-2">
                                <div className="flex items-center justify-between text-sm">
                                    <span className="flex items-center gap-2">
                                        <div className="h-2 w-2 rounded-full bg-red-500" />
                                        Cuti Sakit
                                    </span>
                                    <span className="font-medium">
                                        {balance.sick_remaining} / {balance.sick_total} hari
                                    </span>
                                </div>
                                <Progress
                                    value={(balance.sick_used / balance.sick_total) * 100}
                                    className="h-2 bg-red-100 [&>div]:bg-red-500"
                                />
                            </div>
                            {balance.carry_forward > 0 && (
                                <div className="rounded-lg bg-warning/10 p-2 text-xs flex items-center gap-2">
                                    <AlertCircle className="h-3 w-3 text-warning" />
                                    <span className="text-warning-foreground font-medium">
                                        {balance.carry_forward} hari carry forward (exp: {balance.carry_forward_expiry})
                                    </span>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Pending */}
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Menunggu Approval</p>
                                    <p className="text-3xl font-bold text-warning mt-2">{stats.pending}</p>
                                </div>
                                <div className="rounded-full bg-warning/10 p-3">
                                    <Clock className="h-6 w-6 text-warning" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Approved this month */}
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Disetujui Bulan Ini</p>
                                    <p className="text-3xl font-bold text-success mt-2">{stats.approved}</p>
                                </div>
                                <div className="rounded-full bg-success/10 p-3">
                                    <CalendarCheck className="h-6 w-6 text-success" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            )}

            {/* Error Alert */}
            {requestsError && (
                <Alert variant="destructive">
                    <AlertCircle className="h-4 w-4" />
                    <AlertTitle>Error</AlertTitle>
                    <AlertDescription>{requestsError.message}</AlertDescription>
                    <Button variant="ghost" size="sm" onClick={() => refetchRequests()}>
                        <RefreshCw className="mr-2 h-4 w-4" />
                        Coba Lagi
                    </Button>
                </Alert>
            )}

            {/* Main Content */}
            <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <TabsList className="grid w-full grid-cols-3 sm:w-auto">
                        <TabsTrigger value="requests" className="gap-2">
                            <FileText className="h-4 w-4" />
                            Pengajuan
                        </TabsTrigger>
                        <TabsTrigger value="calendar" className="gap-2">
                            <Calendar className="h-4 w-4" />
                            Kalender
                        </TabsTrigger>
                        <TabsTrigger value="approvals" className="gap-2 relative">
                            <CheckCircle2 className="h-4 w-4" />
                            Approvals
                            {stats.pending > 0 && (
                                <span className="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-destructive text-[10px] text-destructive-foreground">
                                    {stats.pending}
                                </span>
                            )}
                        </TabsTrigger>
                    </TabsList>

                    {/* Filters */}
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="relative">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Cari..."
                                className="pl-9 w-[150px] lg:w-[200px]"
                            />
                        </div>
                        <Select value={logic.statusFilter} onValueChange={logic.setStatusFilter}>
                            <SelectTrigger className="w-[140px]">
                                <Filter className="mr-2 h-4 w-4 text-muted-foreground" />
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                {Object.entries(leaveStatusLabels).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select value={typeFilter} onValueChange={setTypeFilter}>
                            <SelectTrigger className="w-[140px]">
                                <SelectValue placeholder="Jenis" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Jenis</SelectItem>
                                {Object.entries(leaveTypeLabels).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Requests Tab */}
                <TabsContent value="requests" className="space-y-4">
                    {isLoadingRequests ? (
                        <div className="space-y-4">
                            <CardSkeleton />
                            <CardSkeleton />
                            <CardSkeleton />
                        </div>
                    ) : leaveRequests.length === 0 ? (
                        <Card>
                            <CardContent className="py-12">
                                <EmptyState
                                    icon={CalendarX}
                                    title="Tidak ada pengajuan cuti"
                                    description="Belum ada pengajuan cuti yang sesuai filter"
                                    action={{
                                        label: 'Ajukan Cuti Baru',
                                        onClick: () => setShowCreateDialog(true),
                                    }}
                                />
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid gap-4">
                            {leaveRequests.map((request) => (
                                <Card key={request.id} className="overflow-hidden hover:shadow-md transition-shadow">
                                    <CardContent className="p-0">
                                        <div className="flex flex-col sm:flex-row">
                                            {/* Type indicator */}
                                            <div
                                                className="w-full sm:w-1.5 shrink-0"
                                                style={{ backgroundColor: leaveTypeColors[request.type || 'annual'] }}
                                            />

                                            <div className="flex-1 p-4 sm:p-5">
                                                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                                    <div className="space-y-2">
                                                        <div className="flex items-center gap-3">
                                                            <h3 className="font-semibold text-lg">{leaveTypeLabels[request.type || 'annual']}</h3>
                                                            <LeaveBadge status={request.status} />
                                                        </div>
                                                        <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                                            <span className="flex items-center gap-1.5">
                                                                <User className="h-3.5 w-3.5" />
                                                                {request.employee_name}
                                                            </span>
                                                            <span className="flex items-center gap-1.5">
                                                                <Building className="h-3.5 w-3.5" />
                                                                {request.employee_department}
                                                            </span>
                                                        </div>
                                                        <p className="text-sm text-muted-foreground bg-muted/30 p-2 rounded-md border border-border/50">
                                                            "{request.reason}"
                                                        </p>
                                                    </div>

                                                    <div className="flex items-center gap-4">
                                                        <div className="text-right">
                                                            <p className="text-sm font-medium">
                                                                {new Date(request.start_date).toLocaleDateString('id-ID', {
                                                                    day: 'numeric',
                                                                    month: 'short',
                                                                    year: 'numeric'
                                                                })}
                                                            </p>
                                                            {request.start_date !== request.end_date && (
                                                                <p className="text-xs text-muted-foreground">
                                                                    s/d {new Date(request.end_date).toLocaleDateString('id-ID', {
                                                                        day: 'numeric',
                                                                        month: 'short',
                                                                        year: 'numeric'
                                                                    })}
                                                                </p>
                                                            )}
                                                            <Badge variant="secondary" className="mt-1">
                                                                {request.days_requested} Hari
                                                            </Badge>
                                                        </div>

                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger asChild>
                                                                <Button variant="ghost" size="icon">
                                                                    <MoreHorizontal className="h-4 w-4" />
                                                                </Button>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent align="end">
                                                                <DropdownMenuItem>
                                                                    <Eye className="mr-2 h-4 w-4" />
                                                                    Lihat Detail
                                                                </DropdownMenuItem>
                                                                {request.status === 'pending' && (
                                                                    <>
                                                                        <DropdownMenuSeparator />
                                                                        {hasPermission('approve_leave') && (
                                                                            <DropdownMenuItem
                                                                                className="text-success focus:text-success"
                                                                                onClick={() => logic.handleApprove(request.id)}
                                                                                disabled={logic.approveLeaveRequestMutation.isPending}
                                                                            >
                                                                                <CheckCircle2 className="mr-2 h-4 w-4" />
                                                                                Setujui
                                                                            </DropdownMenuItem>
                                                                        )}
                                                                        {hasPermission('reject_leave') && (
                                                                            <DropdownMenuItem
                                                                                className="text-destructive focus:text-destructive"
                                                                                onClick={() => logic.onRejectClick(request.id)}
                                                                                disabled={logic.rejectLeaveRequestMutation.isPending}
                                                                            >
                                                                                <XCircle className="mr-2 h-4 w-4" />
                                                                                Tolak
                                                                            </DropdownMenuItem>
                                                                        )}
                                                                        <DropdownMenuSeparator />
                                                                        <DropdownMenuItem
                                                                            onClick={() => handleCancel(request.id)}
                                                                            disabled={cancelLeaveRequestMutation.isPending}
                                                                        >
                                                                            <AlertCircle className="mr-2 h-4 w-4" />
                                                                            Batalkan
                                                                        </DropdownMenuItem>
                                                                    </>
                                                                )}
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    </div>
                                                </div>

                                                {/* Approval info */}
                                                {request.status !== 'pending' && (
                                                    <div className="mt-4 pt-3 border-t flex items-center gap-2 text-xs text-muted-foreground">
                                                        {request.status === 'approved' ? (
                                                            <>
                                                                <CheckCircle2 className="h-3 w-3 text-success" />
                                                                <span>
                                                                    Disetujui oleh <span className="font-medium text-foreground">{request.approved_by_name}</span> pada {new Date(request.approved_at!).toLocaleDateString('id-ID')}
                                                                </span>
                                                            </>
                                                        ) : request.status === 'rejected' ? (
                                                            <>
                                                                <XCircle className="h-3 w-3 text-destructive" />
                                                                <span className="text-destructive">
                                                                    Ditolak: {request.rejection_reason}
                                                                </span>
                                                            </>
                                                        ) : null}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </TabsContent>

                {/* Calendar Tab */}
                <TabsContent value="calendar" className="mt-6 h-[600px]">
                    <LeaveCalendar leaveRequests={leaveRequests} />
                </TabsContent>

                {/* Approvals Tab */}
                <TabsContent value="approvals" className="mt-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Persetujuan Cuti</CardTitle>
                            <CardDescription>Daftar pengajuan cuti yang menunggu persetujuan Anda</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {leaveRequests.filter((r) => r.status === 'pending').length === 0 ? (
                                <EmptyState
                                    icon={CheckCircle2}
                                    title="Semua Sudah Diproses"
                                    description="Tidak ada pengajuan yang menunggu persetujuan"
                                />
                            ) : (
                                <div className="space-y-4">
                                    {leaveRequests
                                        .filter((r) => r.status === 'pending')
                                        .map((request) => (
                                            <div
                                                key={request.id}
                                                className="flex flex-col sm:flex-row sm:items-center justify-between rounded-lg border p-4 gap-4 hover:bg-muted/50 transition-colors"
                                            >
                                                <div className="flex items-start gap-4">
                                                    <Avatar>
                                                        <AvatarFallback className="bg-primary/10 text-primary">
                                                            {request.employee_name?.charAt(0) || 'U'}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div className="space-y-1">
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-semibold">{request.employee_name}</span>
                                                            <Badge variant="outline" className="text-xs font-normal">
                                                                {leaveTypeLabels[request.type || 'annual']}
                                                            </Badge>
                                                        </div>
                                                        <p className="text-sm text-muted-foreground">
                                                            {new Date(request.start_date).toLocaleDateString('id-ID')} -{' '}
                                                            {new Date(request.end_date).toLocaleDateString('id-ID')} • <span className="font-medium text-foreground">{request.days_requested} hari</span>
                                                        </p>
                                                        <p className="text-sm text-muted-foreground italic">
                                                            "{request.reason}"
                                                        </p>
                                                    </div>
                                                </div>

                                                <div className="flex items-center gap-2 self-end sm:self-center">
                                                    {hasPermission('reject_leave') && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            className="text-destructive hover:text-destructive hover:bg-destructive/10 border-destructive/20"
                                                            onClick={() => logic.onRejectClick(request.id)}
                                                            disabled={logic.rejectLeaveRequestMutation.isPending}
                                                        >
                                                            <XCircle className="mr-2 h-4 w-4" />
                                                            Tolak
                                                        </Button>
                                                    )}
                                                    {hasPermission('approve_leave') && (
                                                        <Button
                                                            size="sm"
                                                            className="bg-success hover:bg-success/90 text-white"
                                                            onClick={() => logic.handleApprove(request.id)}
                                                            disabled={logic.approveLeaveRequestMutation.isPending}
                                                        >
                                                            <CheckCircle2 className="mr-2 h-4 w-4" />
                                                            Setujui
                                                        </Button>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

            {/* Create Dialog */}
            <LeaveRequestDialog
                open={showCreateDialog}
                onOpenChange={setShowCreateDialog}
                onSubmit={handleCreate}
                isLoading={createLeaveRequestMutation.isPending}
            />

            {/* Reject Dialog */}
            <RejectDialog
                open={logic.showRejectDialog}
                onOpenChange={logic.setShowRejectDialog}
                onConfirm={logic.handleRejectConfirm}
                isLoading={logic.rejectLeaveRequestMutation.isPending}
            />
        </div>
    );
}
