import { useState } from 'react';
import {
  Calendar,
  CheckCircle2,
  XCircle,
  Search,
  ArrowLeft,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

import {
  useLeaveRequests,
  useApproveLeaveRequest,
  useRejectLeaveRequest,
} from '@/hooks';
import {
  leaveTypeLabels,
  leaveStatusLabels,
  type LeaveStatus,
} from '@/types/leave';
import { LoadingState } from '@/components/states';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';

export function MobileAdminLeavePage() {
  const [filterStatus, setFilterStatus] = useState<LeaveStatus | 'all'>('pending');

  // Fetch leave requests
  const { data: leaveRequestsData, isLoading } = useLeaveRequests({
    status: filterStatus === 'all' ? undefined : filterStatus,
  });

  const leaveRequests = leaveRequestsData?.data || [];

  // Mutations
  const approveLeaveRequestMutation = useApproveLeaveRequest();
  const rejectLeaveRequestMutation = useRejectLeaveRequest();

  const handleApprove = async (id: string) => {
    try {
      await approveLeaveRequestMutation.mutateAsync({ id });
    } catch {
      // Error handled in hook
    }
  };

  const handleReject = async (id: string) => {
    if (confirm('Apakah Anda yakin ingin menolak pengajuan ini?')) {
      try {
        await rejectLeaveRequestMutation.mutateAsync({ id, reason: 'Ditolak via Mobile' });
      } catch {
        // Error handled in hook
      }
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending': return 'bg-yellow-100 text-yellow-700 border-yellow-200';
      case 'approved': return 'bg-green-100 text-green-700 border-green-200';
      case 'rejected': return 'bg-red-100 text-red-700 border-red-200';
      default: return 'bg-gray-100 text-gray-700 border-gray-200';
    }
  };

  return (
    <div className="min-h-screen bg-background pb-20">
      {/* Header */}
      <div className="sticky top-0 z-10 bg-background/80 backdrop-blur-md border-b p-4 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="icon" onClick={() => window.history.back()}>
            <ArrowLeft className="h-5 w-5" />
          </Button>
          <div>
            <h1 className="text-lg font-bold">Kelola Cuti</h1>
            <p className="text-xs text-muted-foreground">Persetujuan & Riwayat</p>
          </div>
        </div>
        <Button size="icon" variant="ghost">
          <Search className="h-5 w-5" />
        </Button>
      </div>

      {/* Filters */}
      <div className="px-4 py-3 overflow-x-auto border-b">
        <div className="flex gap-2">
          {(['pending', 'approved', 'rejected', 'all'] as const).map((status) => (
            <Badge
              key={status}
              variant={filterStatus === status ? 'default' : 'outline'}
              className="cursor-pointer whitespace-nowrap capitalize"
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
          <div className="flex justify-center py-8">
            <LoadingState message="Memuat data..." />
          </div>
        ) : leaveRequests.length === 0 ? (
          <div className="text-center py-12 text-muted-foreground">
            <Calendar className="h-12 w-12 mx-auto mb-3 opacity-20" />
            <p>Tidak ada data pengajuan cuti</p>
          </div>
        ) : (
          leaveRequests.map((request) => (
            <div
              key={request.id}
              className="bg-card border rounded-xl p-4 shadow-sm space-y-3"
            >
              <div className="flex items-start justify-between">
                <div className="flex items-center gap-3">
                  <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold">
                    {request.employee_name?.charAt(0) || '?'}
                  </div>
                  <div>
                    <h3 className="font-semibold text-sm">{request.employee_name || 'Unknown'}</h3>
                    <p className="text-xs text-muted-foreground">{request.employee_department}</p>
                  </div>
                </div>
                <Badge variant="outline" className={getStatusColor(request.status)}>
                  {leaveStatusLabels[request.status]}
                </Badge>
              </div>

              <div className="bg-muted/30 p-3 rounded-lg space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Jenis:</span>
                  <span className="font-medium">{leaveTypeLabels[request.type]}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Tanggal:</span>
                  <span className="font-medium">
                    {format(new Date(request.start_date), 'dd MMM', { locale: id })} -{' '}
                    {format(new Date(request.end_date), 'dd MMM yyyy', { locale: id })}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Durasi:</span>
                  <span className="font-medium">{request.total_days} Hari</span>
                </div>
                <div className="pt-2 border-t border-border/50">
                  <p className="text-muted-foreground text-xs mb-1">Alasan:</p>
                  <p className="italic">{request.reason}</p>
                </div>
              </div>

              {request.status === 'pending' && (
                <div className="flex gap-2 pt-2">
                  <Button
                    variant="outline"
                    className="flex-1 text-destructive border-destructive/30 hover:bg-destructive/10"
                    onClick={() => handleReject(request.id)}
                    disabled={rejectLeaveRequestMutation.isPending}
                  >
                    <XCircle className="mr-2 h-4 w-4" />
                    Tolak
                  </Button>
                  <Button
                    className="flex-1 bg-green-600 hover:bg-green-700 text-white"
                    onClick={() => handleApprove(request.id)}
                    disabled={approveLeaveRequestMutation.isPending}
                  >
                    <CheckCircle2 className="mr-2 h-4 w-4" />
                    Setujui
                  </Button>
                </div>
              )}
            </div>
          ))
        )}
      </div>
    </div>
  );
}
