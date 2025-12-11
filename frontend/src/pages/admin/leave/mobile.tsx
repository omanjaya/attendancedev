import { useState, useCallback } from 'react';
import { useNavigate } from '@tanstack/react-router';
import {
  Calendar,
  CheckCircle2,
  XCircle,
  Search,
  Plus,
  Loader2,
  BookOpen,
} from 'lucide-react';
import { MobilePageHeader } from '@/components/mobile';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import {
  Drawer,
  DrawerClose,
  DrawerContent,
  DrawerDescription,
  DrawerFooter,
  DrawerHeader,
  DrawerTitle,
} from '@/components/ui/drawer';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
  useLeaveRequests,
} from '@/hooks';
import { useAdminLeavePage } from '@/hooks/use-admin-leave-page';
import { getAffectedSchedules, type AffectedSchedulesResponse } from '@/lib/api/leave';
import {
  leaveTypeLabels,
  leaveStatusLabels,
  leaveTypeColors,
  type LeaveStatus,
  type LeaveRequest,
} from '@/types/leave';
import { LoadingState } from '@/components/states';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { LeaveBadge } from '@/components/status';
import { useAuthStore } from '@/stores/auth-store';

// Reject Drawer Component
function RejectDrawer({
  open,
  onOpenChange,
  onConfirm,
  isLoading,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onConfirm: (reason: string) => void;
  isLoading: boolean;
}) {
  const [reason, setReason] = useState('');

  const handleSubmit = () => {
    onConfirm(reason);
    setReason('');
  };

  return (
    <Drawer open={open} onOpenChange={onOpenChange}>
      <DrawerContent>
        <div className="mx-auto w-full max-w-sm">
          <DrawerHeader>
            <DrawerTitle>Tolak Pengajuan Cuti</DrawerTitle>
            <DrawerDescription>
              Berikan alasan penolakan untuk karyawan.
            </DrawerDescription>
          </DrawerHeader>
          <div className="p-4 space-y-4">
            <div className="space-y-2">
              <Label htmlFor="reason">Alasan Penolakan</Label>
              <Textarea
                id="reason"
                value={reason}
                onChange={(e) => setReason(e.target.value)}
                placeholder="Contoh: Kuota habis, jadwal padat..."
                className="min-h-[100px]"
              />
            </div>
          </div>
          <DrawerFooter>
            <Button
              onClick={handleSubmit}
              variant="destructive"
              disabled={isLoading || !reason.trim()}
            >
              {isLoading ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
              Tolak Pengajuan
            </Button>
            <DrawerClose asChild>
              <Button variant="outline">Batal</Button>
            </DrawerClose>
          </DrawerFooter>
        </div>
      </DrawerContent>
    </Drawer>
  );
}

// Approve Drawer Component with Affected Schedules
function ApproveDrawer({
  open,
  onOpenChange,
  onConfirm,
  isLoading,
  employeeName,
  daysRequested,
  affectedSchedules,
  loadingSchedules,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onConfirm: () => void;
  isLoading: boolean;
  employeeName: string;
  daysRequested: number;
  affectedSchedules: AffectedSchedulesResponse | null;
  loadingSchedules: boolean;
}) {
  return (
    <Drawer open={open} onOpenChange={onOpenChange}>
      <DrawerContent>
        <div className="mx-auto w-full max-w-sm">
          <DrawerHeader>
            <DrawerTitle>Setujui Pengajuan Cuti?</DrawerTitle>
            <DrawerDescription>
              Anda akan menyetujui cuti {employeeName} selama {daysRequested} hari.
            </DrawerDescription>
          </DrawerHeader>

          <div className="p-4">
            {/* Affected Schedules Section */}
            {loadingSchedules ? (
              <div className="flex items-center justify-center py-4">
                <Loader2 className="h-5 w-5 animate-spin mr-2" />
                <span className="text-sm text-muted-foreground">Mengecek jadwal...</span>
              </div>
            ) : affectedSchedules?.is_teacher && affectedSchedules.affected_count > 0 ? (
              <div className="border rounded-lg p-3 bg-yellow-50 dark:bg-yellow-900/20">
                <div className="flex items-center gap-2 mb-2">
                  <BookOpen className="h-4 w-4 text-yellow-600" />
                  <span className="font-medium text-sm text-yellow-800 dark:text-yellow-200">
                    {affectedSchedules.affected_count} Jadwal Terdampak
                  </span>
                </div>
                <div className="space-y-2 max-h-32 overflow-y-auto">
                  {affectedSchedules.affected_schedules.slice(0, 5).map((schedule, idx) => (
                    <div
                      key={`${schedule.schedule_id}-${idx}`}
                      className="text-xs p-2 bg-white dark:bg-gray-800 rounded border"
                    >
                      <div className="font-medium">{schedule.subject}</div>
                      <div className="text-muted-foreground">
                        {schedule.day}, {schedule.date} • {schedule.class}
                      </div>
                    </div>
                  ))}
                  {affectedSchedules.affected_count > 5 && (
                    <p className="text-xs text-muted-foreground">
                      +{affectedSchedules.affected_count - 5} jadwal lainnya
                    </p>
                  )}
                </div>
                <p className="text-xs text-yellow-700 dark:text-yellow-300 mt-2">
                  Jadwal akan ditandai "Guru Cuti"
                </p>
              </div>
            ) : affectedSchedules?.is_teacher ? (
              <p className="text-sm text-muted-foreground text-center py-2">
                Tidak ada jadwal mengajar yang terdampak.
              </p>
            ) : null}
          </div>

          <DrawerFooter>
            <Button
              onClick={onConfirm}
              className="bg-green-600 hover:bg-green-700"
              disabled={isLoading}
            >
              {isLoading ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <CheckCircle2 className="mr-2 h-4 w-4" />}
              Setujui
            </Button>
            <DrawerClose asChild>
              <Button variant="outline">Batal</Button>
            </DrawerClose>
          </DrawerFooter>
        </div>
      </DrawerContent>
    </Drawer>
  );
}

export function MobileAdminLeavePage() {
  const navigate = useNavigate();
  const { hasPermission } = useAuthStore();

  // Use shared hook for common logic
  const logic = useAdminLeavePage();
  const [showRejectDrawer, setShowRejectDrawer] = useState(false);
  const [filterStatus, setFilterStatus] = useState<'all' | LeaveStatus>('all');

  // Approve drawer state
  const [showApproveDrawer, setShowApproveDrawer] = useState(false);
  const [selectedRequest, setSelectedRequest] = useState<LeaveRequest | null>(null);
  const [affectedSchedules, setAffectedSchedules] = useState<AffectedSchedulesResponse | null>(null);
  const [loadingSchedules, setLoadingSchedules] = useState(false);

  // Fetch leave requests (mobile uses filterStatus)
  const { data: leaveRequestsData, isLoading } = useLeaveRequests({
    status: filterStatus === 'all' ? undefined : (filterStatus as LeaveStatus),
  });

  const leaveRequests = leaveRequestsData?.data || [];

  // Handle opening approve drawer with affected schedules
  const handleOpenApproveDrawer = useCallback(async (request: LeaveRequest) => {
    setSelectedRequest(request);
    setShowApproveDrawer(true);
    setLoadingSchedules(true);
    try {
      const schedules = await getAffectedSchedules(request.id);
      setAffectedSchedules(schedules);
    } catch {
      setAffectedSchedules(null);
    } finally {
      setLoadingSchedules(false);
    }
  }, []);

  // Handle confirm approve
  const handleApproveConfirm = async () => {
    if (selectedRequest) {
      await logic.handleApprove(selectedRequest.id);
      setShowApproveDrawer(false);
      setSelectedRequest(null);
      setAffectedSchedules(null);
    }
  };

  return (
    <div className="min-h-screen bg-background pb-24">
      {/* Header */}
      <MobilePageHeader
        title="Kelola Cuti"
        onBack={() => navigate({ to: '/admin/dashboard' })}
        gradient="indigo"
        actions={
          <button className="p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95">
            <Search className="h-5 w-5 text-white" />
          </button>
        }
      />

      {/* Filters */}
      <div className="px-4 py-3 overflow-x-auto border-b bg-background/50">
        <div className="flex gap-2">
          {(['pending', 'approved', 'rejected', 'all'] as const).map((status) => (
            <Badge
              key={status}
              variant={filterStatus === status ? 'default' : 'outline'}
              className="cursor-pointer whitespace-nowrap capitalize px-4 py-1.5 h-auto text-sm"
              onClick={() => setFilterStatus(status)}
            >
              {status === 'all' ? 'Semua' : leaveStatusLabels[status]}
            </Badge>
          ))}
        </div>
      </div>

      {/* List */}
      <div className="p-4 space-y-4">
        {isLoading ? (
          <div className="flex justify-center py-12">
            <LoadingState message="Memuat data..." />
          </div>
        ) : leaveRequests.length === 0 ? (
          <div className="text-center py-16 text-muted-foreground">
            <div className="bg-muted/30 rounded-full p-6 w-24 h-24 mx-auto mb-4 flex items-center justify-center">
              <Calendar className="h-10 w-10 opacity-40" />
            </div>
            <h3 className="font-medium text-lg mb-1">Tidak ada data</h3>
            <p className="text-sm">Belum ada pengajuan cuti untuk status ini</p>
          </div>
        ) : (
          leaveRequests.map((request) => (
            <Card key={request.id} className="overflow-hidden shadow-sm border-border/60">
              <CardContent className="p-0">
                <div className="flex">
                  <div
                    className="w-1.5 shrink-0"
                    style={{ backgroundColor: leaveTypeColors[request.type || 'annual'] }}
                  />
                  <div className="flex-1 p-4 space-y-3">
                    {/* Header */}
                    <div className="flex items-start justify-between gap-2">
                      <div className="flex items-center gap-3">
                        <Avatar className="h-10 w-10 border">
                          <AvatarFallback className="bg-primary/5 text-primary text-xs font-bold">
                            {request.employee_name?.substring(0, 2).toUpperCase() || 'UN'}
                          </AvatarFallback>
                        </Avatar>
                        <div>
                          <h3 className="font-semibold text-sm line-clamp-1">{request.employee_name || 'Unknown'}</h3>
                          <p className="text-xs text-muted-foreground line-clamp-1">{request.employee_department}</p>
                        </div>
                      </div>
                      <LeaveBadge status={request.status} />
                    </div>

                    {/* Details */}
                    <div className="bg-muted/30 p-3 rounded-lg space-y-2 text-sm border border-border/40">
                      <div className="flex justify-between items-center">
                        <span className="text-muted-foreground text-xs">Jenis Cuti</span>
                        <span className="font-medium text-xs">{leaveTypeLabels[request.type || 'annual']}</span>
                      </div>
                      <div className="flex justify-between items-center">
                        <span className="text-muted-foreground text-xs">Tanggal</span>
                        <span className="font-medium text-xs">
                          {format(new Date(request.start_date), 'dd MMM', { locale: id })} -{' '}
                          {format(new Date(request.end_date), 'dd MMM yyyy', { locale: id })}
                        </span>
                      </div>
                      <div className="flex justify-between items-center">
                        <span className="text-muted-foreground text-xs">Durasi</span>
                        <span className="font-medium text-xs">{request.days_requested} Hari</span>
                      </div>
                      {request.reason && (
                        <div className="pt-2 border-t border-border/40 mt-2">
                          <p className="text-xs text-muted-foreground mb-1">Alasan:</p>
                          <p className="text-xs italic text-foreground/80 line-clamp-2">"{request.reason}"</p>
                        </div>
                      )}
                    </div>

                    {/* Actions */}
                    {request.status === 'pending' && (
                      <div className="flex gap-3 pt-1">
                        {hasPermission('reject_leave') && (
                          <Button
                            variant="outline"
                            size="sm"
                            className="flex-1 text-destructive border-destructive/30 hover:bg-destructive/10 hover:text-destructive h-9"
                            onClick={() => {
                              logic.onRejectClick(request.id);
                              setShowRejectDrawer(true);
                            }}
                            disabled={logic.rejectLeaveRequestMutation.isPending}
                          >
                            <XCircle className="mr-2 h-4 w-4" />
                            Tolak
                          </Button>
                        )}
                        {hasPermission('approve_leave') && (
                          <Button
                            size="sm"
                            className="flex-1 bg-green-600 hover:bg-green-700 text-white h-9"
                            onClick={() => handleOpenApproveDrawer(request)}
                            disabled={logic.approveLeaveRequestMutation.isPending}
                          >
                            <CheckCircle2 className="mr-2 h-4 w-4" />
                            Setujui
                          </Button>
                        )}
                      </div>
                    )}
                  </div>
                </div>
              </CardContent>
            </Card>
          ))
        )}
      </div>

      {/* FAB - Create Request */}
      {hasPermission('create_leave_requests') && (
        <div className="fixed bottom-6 right-6 z-50">
          <Button
            size="icon"
            className="h-14 w-14 rounded-full shadow-lg bg-primary hover:bg-primary/90"
            onClick={() => navigate({ to: '/admin/leave/create' })}
          >
            <Plus className="h-6 w-6" />
          </Button>
        </div>
      )}

      {/* Reject Drawer */}
      <RejectDrawer
        open={showRejectDrawer}
        onOpenChange={(open) => {
          setShowRejectDrawer(open);
          if (!open) {
            logic.setSelectedRequestId(null);
          }
        }}
        onConfirm={async (reason) => {
          await logic.handleRejectConfirm(reason);
          setShowRejectDrawer(false);
        }}
        isLoading={logic.rejectLeaveRequestMutation.isPending}
      />

      {/* Approve Drawer */}
      <ApproveDrawer
        open={showApproveDrawer}
        onOpenChange={(open) => {
          setShowApproveDrawer(open);
          if (!open) {
            setSelectedRequest(null);
            setAffectedSchedules(null);
          }
        }}
        onConfirm={handleApproveConfirm}
        isLoading={logic.approveLeaveRequestMutation.isPending}
        employeeName={selectedRequest?.employee_name || 'Karyawan'}
        daysRequested={selectedRequest?.days_requested || 0}
        affectedSchedules={affectedSchedules}
        loadingSchedules={loadingSchedules}
      />
    </div>
  );
}
