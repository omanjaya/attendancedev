import { useState, useEffect } from 'react';
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
} from 'lucide-react';
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
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import { useLeave } from '@/hooks/use-leave';
import {
  leaveTypeLabels,
  leaveStatusLabels,
  leaveTypeColors,
  type LeaveType,
  type LeaveStatus,
  type LeaveRequestFormData,
} from '@/types/leave';

// Status badge component
function StatusBadge({ status }: { status: LeaveStatus }) {
  const variants: Record<LeaveStatus, { variant: 'default' | 'secondary' | 'destructive' | 'outline'; className: string }> = {
    pending: { variant: 'secondary', className: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' },
    approved: { variant: 'default', className: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' },
    rejected: { variant: 'destructive', className: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
    cancelled: { variant: 'outline', className: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400' },
  };

  const config = variants[status];

  return (
    <Badge variant={config.variant} className={config.className}>
      {status === 'pending' && <Clock className="mr-1 h-3 w-3" />}
      {status === 'approved' && <CheckCircle2 className="mr-1 h-3 w-3" />}
      {status === 'rejected' && <XCircle className="mr-1 h-3 w-3" />}
      {status === 'cancelled' && <AlertCircle className="mr-1 h-3 w-3" />}
      {leaveStatusLabels[status]}
    </Badge>
  );
}

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

export default function LeavePage() {
  const [activeTab, setActiveTab] = useState('requests');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [typeFilter, setTypeFilter] = useState<string>('all');
  const [showCreateDialog, setShowCreateDialog] = useState(false);

  const {
    isLoading,
    error,
    leaveRequests,
    leaveBalance,
    fetchLeaveRequests,
    fetchLeaveBalance,
    createLeaveRequest,
    approveLeaveRequest,
    rejectLeaveRequest,
    cancelLeaveRequest,
    clearError,
  } = useLeave();

  // Load data on mount
  useEffect(() => {
    fetchLeaveRequests();
    fetchLeaveBalance();
  }, [fetchLeaveRequests, fetchLeaveBalance]);

  // Filter requests
  const filteredRequests = leaveRequests.filter((req) => {
    if (statusFilter !== 'all' && req.status !== statusFilter) return false;
    if (typeFilter !== 'all' && req.type !== typeFilter) return false;
    return true;
  });

  // Handle create
  const handleCreate = async (data: LeaveRequestFormData) => {
    try {
      await createLeaveRequest(data);
      setShowCreateDialog(false);
    } catch (err) {
      // Error handled in hook
    }
  };

  // Stats
  const stats = {
    pending: leaveRequests.filter((r) => r.status === 'pending').length,
    approved: leaveRequests.filter((r) => r.status === 'approved').length,
    total_days_this_month: leaveRequests
      .filter((r) => r.status === 'approved')
      .reduce((acc, r) => acc + r.total_days, 0),
  };

  return (
    <div className="space-y-6 p-6">
      {/* Header */}
      <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary/10 via-primary/5 to-background p-8">
        <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-primary/10 blur-3xl" />
        <div className="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-primary/5 blur-3xl" />

        <div className="relative">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-3">
              <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10">
                <CalendarDays className="h-6 w-6 text-primary" />
              </div>
              <div>
                <h1 className="text-2xl font-bold text-foreground">Cuti & Izin</h1>
                <p className="text-sm text-muted-foreground">
                  Kelola pengajuan cuti dan izin karyawan
                </p>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <Button variant="outline" size="sm" className="gap-2">
                <Download className="h-4 w-4" />
                Export
              </Button>
              <Button size="sm" className="gap-2" onClick={() => setShowCreateDialog(true)}>
                <Plus className="h-4 w-4" />
                Ajukan Cuti
              </Button>
            </div>
          </div>

          {/* Stats & Balance */}
          <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {/* Leave Balance Card */}
            <Card className="bg-card/50 backdrop-blur-sm sm:col-span-2">
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                  Saldo Cuti Anda
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="space-y-2">
                  <div className="flex items-center justify-between text-sm">
                    <span>Cuti Tahunan</span>
                    <span className="font-medium">
                      {leaveBalance.annual_remaining} / {leaveBalance.annual_total} hari
                    </span>
                  </div>
                  <Progress
                    value={(leaveBalance.annual_used / leaveBalance.annual_total) * 100}
                    className="h-2"
                  />
                </div>
                <div className="space-y-2">
                  <div className="flex items-center justify-between text-sm">
                    <span>Cuti Sakit</span>
                    <span className="font-medium">
                      {leaveBalance.sick_remaining} / {leaveBalance.sick_total} hari
                    </span>
                  </div>
                  <Progress
                    value={(leaveBalance.sick_used / leaveBalance.sick_total) * 100}
                    className="h-2"
                  />
                </div>
                {leaveBalance.carry_forward > 0 && (
                  <div className="rounded-lg bg-amber-50 dark:bg-amber-900/20 p-2 text-xs">
                    <span className="text-amber-700 dark:text-amber-400">
                      {leaveBalance.carry_forward} hari carry forward (exp: {leaveBalance.carry_forward_expiry})
                    </span>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Pending */}
            <Card className="bg-card/50 backdrop-blur-sm">
              <CardContent className="p-4">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-xs text-muted-foreground">Menunggu Approval</p>
                    <p className="text-2xl font-bold text-amber-600">{stats.pending}</p>
                  </div>
                  <Clock className="h-8 w-8 text-amber-500/30" />
                </div>
              </CardContent>
            </Card>

            {/* Approved this month */}
            <Card className="bg-card/50 backdrop-blur-sm">
              <CardContent className="p-4">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-xs text-muted-foreground">Disetujui Bulan Ini</p>
                    <p className="text-2xl font-bold text-emerald-600">{stats.approved}</p>
                  </div>
                  <CalendarCheck className="h-8 w-8 text-emerald-500/30" />
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>

      {/* Error Alert */}
      {error && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertTitle>Error</AlertTitle>
          <AlertDescription>{error}</AlertDescription>
          <Button variant="ghost" size="sm" onClick={clearError}>
            Tutup
          </Button>
        </Alert>
      )}

      {/* Main Content */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <TabsList>
            <TabsTrigger value="requests" className="gap-2">
              <FileText className="h-4 w-4" />
              Pengajuan
            </TabsTrigger>
            <TabsTrigger value="calendar" className="gap-2">
              <Calendar className="h-4 w-4" />
              Kalender
            </TabsTrigger>
            <TabsTrigger value="approvals" className="gap-2">
              <CheckCircle2 className="h-4 w-4" />
              Approvals
              {stats.pending > 0 && (
                <Badge variant="secondary" className="ml-1 h-5 w-5 rounded-full p-0 text-xs">
                  {stats.pending}
                </Badge>
              )}
            </TabsTrigger>
          </TabsList>

          {/* Filters */}
          <div className="flex items-center gap-2">
            <Filter className="h-4 w-4 text-muted-foreground" />
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-[140px]">
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
        <TabsContent value="requests" className="mt-6">
          {isLoading ? (
            <Card>
              <CardContent className="flex items-center justify-center py-20">
                <div className="text-center">
                  <Loader2 className="h-8 w-8 animate-spin text-primary mx-auto" />
                  <p className="mt-2 text-sm text-muted-foreground">Memuat data...</p>
                </div>
              </CardContent>
            </Card>
          ) : filteredRequests.length === 0 ? (
            <Card>
              <CardContent className="flex flex-col items-center justify-center py-20">
                <CalendarX className="h-12 w-12 text-muted-foreground/50" />
                <p className="mt-4 text-lg font-medium">Tidak ada pengajuan cuti</p>
                <p className="text-sm text-muted-foreground">
                  Belum ada pengajuan cuti yang sesuai filter
                </p>
                <Button className="mt-4 gap-2" onClick={() => setShowCreateDialog(true)}>
                  <Plus className="h-4 w-4" />
                  Ajukan Cuti Baru
                </Button>
              </CardContent>
            </Card>
          ) : (
            <div className="space-y-4">
              {filteredRequests.map((request) => (
                <Card key={request.id} className="overflow-hidden">
                  <CardContent className="p-0">
                    <div className="flex flex-col sm:flex-row">
                      {/* Type indicator */}
                      <div
                        className="w-full sm:w-2 shrink-0"
                        style={{ backgroundColor: leaveTypeColors[request.type] }}
                      />

                      <div className="flex-1 p-4">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                          <div className="space-y-1">
                            <div className="flex items-center gap-2">
                              <h3 className="font-semibold">{leaveTypeLabels[request.type]}</h3>
                              <StatusBadge status={request.status} />
                            </div>
                            <div className="flex items-center gap-4 text-sm text-muted-foreground">
                              <span className="flex items-center gap-1">
                                <User className="h-3 w-3" />
                                {request.employee_name}
                              </span>
                              <span className="flex items-center gap-1">
                                <Building className="h-3 w-3" />
                                {request.employee_department}
                              </span>
                            </div>
                            <p className="text-sm text-muted-foreground">{request.reason}</p>
                          </div>

                          <div className="flex items-center gap-4">
                            <div className="text-right">
                              <p className="text-sm font-medium">
                                {new Date(request.start_date).toLocaleDateString('id-ID', {
                                  day: 'numeric',
                                  month: 'short',
                                })}
                                {request.start_date !== request.end_date && (
                                  <>
                                    {' - '}
                                    {new Date(request.end_date).toLocaleDateString('id-ID', {
                                      day: 'numeric',
                                      month: 'short',
                                    })}
                                  </>
                                )}
                              </p>
                              <p className="text-xs text-muted-foreground">
                                {request.total_days} hari
                              </p>
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
                                    <DropdownMenuItem
                                      className="text-emerald-600"
                                      onClick={() => approveLeaveRequest(request.id)}
                                    >
                                      <CheckCircle2 className="mr-2 h-4 w-4" />
                                      Setujui
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                      className="text-red-600"
                                      onClick={() => rejectLeaveRequest(request.id, 'Ditolak')}
                                    >
                                      <XCircle className="mr-2 h-4 w-4" />
                                      Tolak
                                    </DropdownMenuItem>
                                  </>
                                )}
                                {request.status === 'pending' && (
                                  <DropdownMenuItem
                                    onClick={() => cancelLeaveRequest(request.id)}
                                  >
                                    <AlertCircle className="mr-2 h-4 w-4" />
                                    Batalkan
                                  </DropdownMenuItem>
                                )}
                              </DropdownMenuContent>
                            </DropdownMenu>
                          </div>
                        </div>

                        {/* Approval info */}
                        {request.status !== 'pending' && request.approved_by_name && (
                          <div className="mt-3 pt-3 border-t text-xs text-muted-foreground">
                            {request.status === 'approved' && (
                              <span>
                                Disetujui oleh {request.approved_by_name} pada{' '}
                                {new Date(request.approved_at!).toLocaleDateString('id-ID')}
                              </span>
                            )}
                            {request.status === 'rejected' && (
                              <span className="text-red-600">
                                Ditolak: {request.rejection_reason}
                              </span>
                            )}
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
        <TabsContent value="calendar" className="mt-6">
          <Card>
            <CardHeader>
              <CardTitle>Kalender Cuti</CardTitle>
              <CardDescription>Lihat jadwal cuti karyawan dalam format kalender</CardDescription>
            </CardHeader>
            <CardContent className="flex items-center justify-center py-20">
              <div className="text-center">
                <Calendar className="h-12 w-12 text-muted-foreground/50 mx-auto" />
                <p className="mt-4 text-lg font-medium">Coming Soon</p>
                <p className="text-sm text-muted-foreground">
                  Fitur kalender akan segera hadir
                </p>
              </div>
            </CardContent>
          </Card>
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
                <div className="flex flex-col items-center justify-center py-10">
                  <CheckCircle2 className="h-12 w-12 text-emerald-500/50" />
                  <p className="mt-4 text-lg font-medium">Semua Sudah Diproses</p>
                  <p className="text-sm text-muted-foreground">
                    Tidak ada pengajuan yang menunggu persetujuan
                  </p>
                </div>
              ) : (
                <div className="space-y-4">
                  {leaveRequests
                    .filter((r) => r.status === 'pending')
                    .map((request) => (
                      <div
                        key={request.id}
                        className="flex items-center justify-between rounded-lg border p-4"
                      >
                        <div className="space-y-1">
                          <div className="flex items-center gap-2">
                            <span className="font-medium">{request.employee_name}</span>
                            <Badge variant="outline">{leaveTypeLabels[request.type]}</Badge>
                          </div>
                          <p className="text-sm text-muted-foreground">
                            {new Date(request.start_date).toLocaleDateString('id-ID')} -{' '}
                            {new Date(request.end_date).toLocaleDateString('id-ID')} ({request.total_days} hari)
                          </p>
                          <p className="text-sm text-muted-foreground">{request.reason}</p>
                        </div>
                        <div className="flex gap-2">
                          <Button
                            variant="outline"
                            size="sm"
                            className="text-red-600"
                            onClick={() => rejectLeaveRequest(request.id, 'Ditolak')}
                          >
                            <XCircle className="mr-1 h-4 w-4" />
                            Tolak
                          </Button>
                          <Button
                            size="sm"
                            className="bg-emerald-600 hover:bg-emerald-700"
                            onClick={() => approveLeaveRequest(request.id)}
                          >
                            <CheckCircle2 className="mr-1 h-4 w-4" />
                            Setujui
                          </Button>
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
        isLoading={isLoading}
      />
    </div>
  );
}
