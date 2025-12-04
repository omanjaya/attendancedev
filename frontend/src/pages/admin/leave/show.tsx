import { useState } from 'react';
import { Link, useParams, useNavigate } from '@tanstack/react-router';
import {
  ArrowLeft,
  Calendar,
  Clock,
  User,
  FileText,
  CheckCircle,
  XCircle,
  AlertCircle,
  MessageSquare,
  RefreshCw,
  Loader2,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';
import {
  useLeaveRequest,
  useLeaveBalanceByEmployee,
  useApproveLeaveRequest,
  useRejectLeaveRequest,
  useCancelLeaveRequest,
} from '@/hooks';
import { leaveTypeLabels, leaveTypeColors, type LeaveType } from '@/types/leave';
import { RejectDialog } from '@/components/leave/RejectDialog';

// Loading skeleton
function ShowLoadingSkeleton() {
  return (
    <div className="p-4 sm:p-6 max-w-4xl mx-auto">
      <Skeleton className="h-4 w-48 mb-6" />
      <div className="flex justify-between items-start mb-6">
        <div className="space-y-2">
          <Skeleton className="h-8 w-64" />
          <Skeleton className="h-4 w-48" />
        </div>
        <div className="flex gap-2">
          <Skeleton className="h-10 w-24" />
          <Skeleton className="h-10 w-24" />
        </div>
      </div>
      <div className="grid gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-6">
          <Skeleton className="h-48 w-full" />
          <Skeleton className="h-64 w-full" />
        </div>
        <div className="space-y-6">
          <Skeleton className="h-40 w-full" />
          <Skeleton className="h-48 w-full" />
        </div>
      </div>
    </div>
  );
}

const statusConfig = {
  pending: { label: 'Menunggu', color: 'bg-warning/10 text-warning', icon: AlertCircle },
  approved: { label: 'Disetujui', color: 'bg-success/10 text-success', icon: CheckCircle },
  rejected: { label: 'Ditolak', color: 'bg-destructive/10 text-destructive', icon: XCircle },
  cancelled: { label: 'Dibatalkan', color: 'bg-muted text-muted-foreground', icon: XCircle },
};

export default function LeaveShowPage() {
  const { id } = useParams({ strict: false }) as { id: string };
  const navigate = useNavigate();
  const [showRejectDialog, setShowRejectDialog] = useState(false);

  // Fetch leave request
  const {
    data: leave,
    isLoading,
    error,
    refetch,
  } = useLeaveRequest(id);

  // Fetch employee leave balance
  const { data: leaveBalance } = useLeaveBalanceByEmployee(leave?.employee_id || '');

  // Mutations
  const approveLeaveRequestMutation = useApproveLeaveRequest();
  const rejectLeaveRequestMutation = useRejectLeaveRequest();
  const cancelLeaveRequestMutation = useCancelLeaveRequest();

  // Handle approve
  const handleApprove = async () => {
    try {
      await approveLeaveRequestMutation.mutateAsync({ id });
    } catch {
      // Error handled in hook
    }
  };

  // Handle reject confirm
  const handleRejectConfirm = async (reason: string) => {
    try {
      await rejectLeaveRequestMutation.mutateAsync({ id, reason });
      setShowRejectDialog(false);
    } catch {
      // Error handled in hook
    }
  };

  // Handle cancel
  const handleCancel = async () => {
    if (confirm('Apakah Anda yakin ingin membatalkan pengajuan ini?')) {
      try {
        await cancelLeaveRequestMutation.mutateAsync(id);
        navigate({ to: '/admin/leave' });
      } catch {
        // Error handled in hook
      }
    }
  };

  // Show loading state
  if (isLoading) {
    return <ShowLoadingSkeleton />;
  }

  // Show error state
  if (error) {
    return (
      <div className="p-4 sm:p-6 max-w-4xl mx-auto">
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertTitle>Error</AlertTitle>
          <AlertDescription>
            Gagal memuat data pengajuan cuti. {error.message}
          </AlertDescription>
        </Alert>
        <Button onClick={() => refetch()} className="mt-4">
          <RefreshCw className="mr-2 h-4 w-4" />
          Coba Lagi
        </Button>
      </div>
    );
  }

  if (!leave) {
    return (
      <div className="p-4 sm:p-6 max-w-4xl mx-auto">
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertTitle>Tidak Ditemukan</AlertTitle>
          <AlertDescription>
            Pengajuan cuti dengan ID {id} tidak ditemukan.
          </AlertDescription>
        </Alert>
        <Button asChild className="mt-4">
          <Link to="/admin/leave">Kembali ke Daftar</Link>
        </Button>
      </div>
    );
  }

  const status = statusConfig[leave.status];
  const balance = leaveBalance || {
    annual_remaining: 0,
    sick_remaining: 0,
    special_remaining: 0,
  };

  // Timeline data
  const timeline = [
    {
      id: 1,
      action: 'Pengajuan dibuat',
      user: leave.employee_name || 'Karyawan',
      date: leave.created_at,
      type: 'created',
    },
    ...(leave.status === 'pending'
      ? [{
        id: 2,
        action: 'Menunggu persetujuan',
        user: 'System',
        date: leave.created_at,
        type: 'pending',
      }]
      : []),
    ...(leave.status === 'approved'
      ? [{
        id: 3,
        action: 'Disetujui',
        user: leave.approved_by_name || 'Manager',
        date: leave.approved_at || '',
        type: 'approved',
      }]
      : []),
    ...(leave.status === 'rejected'
      ? [{
        id: 3,
        action: `Ditolak: ${leave.rejection_reason}`,
        user: leave.approved_by_name || 'Manager',
        date: leave.approved_at || '',
        type: 'rejected',
      }]
      : []),
  ];

  return (
    <div className="p-4 sm:p-6 max-w-4xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/admin/leave"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar cuti
        </Link>

        <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
          <div>
            <div className="flex items-center gap-3 mb-2">
              <h1 className="text-2xl font-bold text-foreground">Detail Pengajuan Cuti</h1>
              <Badge className={status.color}>
                <status.icon className="h-3 w-3 mr-1" />
                {status.label}
              </Badge>
            </div>
            <p className="text-sm text-muted-foreground">
              ID: #{leave.id} | Diajukan pada {new Date(leave.created_at).toLocaleDateString('id-ID')}
            </p>
          </div>
          {leave.status === 'pending' && (
            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                className="text-destructive border-destructive/20 hover:bg-destructive/10"
                onClick={() => setShowRejectDialog(true)}
                disabled={rejectLeaveRequestMutation.isPending}
              >
                <XCircle className="h-4 w-4 mr-2" />
                Tolak
              </Button>
              <Button
                onClick={handleApprove}
                disabled={approveLeaveRequestMutation.isPending}
                className="bg-success hover:bg-success/90 text-white"
              >
                {approveLeaveRequestMutation.isPending ? (
                  <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                ) : (
                  <CheckCircle className="h-4 w-4 mr-2" />
                )}
                Setujui
              </Button>
            </div>
          )}
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-6">
          {/* Employee Info */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <User className="h-5 w-5 text-primary" />
                Informasi Karyawan
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center gap-4">
                <Avatar className="h-16 w-16">
                  <AvatarFallback className="text-lg bg-primary/10 text-primary">
                    {leave.employee_name?.split(' ').map(n => n[0]).join('') || 'U'}
                  </AvatarFallback>
                </Avatar>
                <div>
                  <h3 className="text-lg font-semibold">{leave.employee_name}</h3>
                  <p className="text-sm text-muted-foreground">{leave.employee_department}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Leave Details */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Calendar className="h-5 w-5 text-primary" />
                Detail Cuti
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div className="p-3 rounded-lg bg-muted text-center">
                  <p className="text-xs text-muted-foreground mb-1">Jenis Cuti</p>
                  <Badge style={{ backgroundColor: leaveTypeColors[leave.type || 'annual'], color: 'white' }}>
                    {leaveTypeLabels[leave.type || 'annual']}
                  </Badge>
                </div>
                <div className="p-3 rounded-lg bg-muted text-center">
                  <p className="text-xs text-muted-foreground mb-1">Tanggal Mulai</p>
                  <p className="font-semibold">
                    {new Date(leave.start_date).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'short',
                    })}
                  </p>
                </div>
                <div className="p-3 rounded-lg bg-muted text-center">
                  <p className="text-xs text-muted-foreground mb-1">Tanggal Selesai</p>
                  <p className="font-semibold">
                    {new Date(leave.end_date).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'short',
                    })}
                  </p>
                </div>
                <div className="p-3 rounded-lg bg-primary/10 text-center">
                  <p className="text-xs text-muted-foreground mb-1">Total Hari</p>
                  <p className="text-2xl font-bold text-primary">{leave.days_requested}</p>
                </div>
              </div>

              <Separator />

              <div>
                <h4 className="text-sm font-medium mb-2">Alasan Pengajuan</h4>
                <p className="text-sm text-muted-foreground bg-muted p-3 rounded-lg italic">
                  "{leave.reason}"
                </p>
              </div>

              {leave.attachment_url && (
                <div>
                  <h4 className="text-sm font-medium mb-2">Lampiran</h4>
                  <Button variant="outline" size="sm" asChild>
                    <a href={leave.attachment_url} target="_blank" rel="noopener noreferrer">
                      <FileText className="h-4 w-4 mr-2" />
                      Lihat Lampiran
                    </a>
                  </Button>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Add Comment / Cancel */}
          {leave.status === 'pending' && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg">
                  <MessageSquare className="h-5 w-5 text-primary" />
                  Tindakan Lain
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex gap-2">
                  <Button
                    variant="outline"
                    className="text-destructive w-full sm:w-auto"
                    onClick={handleCancel}
                    disabled={cancelLeaveRequestMutation.isPending}
                  >
                    {cancelLeaveRequestMutation.isPending && (
                      <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                    )}
                    Batalkan Pengajuan Ini
                  </Button>
                </div>
              </CardContent>
            </Card>
          )}
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Status Card */}
          <Card className={`border-2 ${leave.status === 'approved' ? 'border-success' : leave.status === 'rejected' ? 'border-destructive' : 'border-warning'}`}>
            <CardContent className="p-4 sm:p-6 text-center">
              <div className={`inline-flex items-center justify-center w-16 h-16 rounded-full mb-4 ${status.color}`}>
                <status.icon className="h-8 w-8" />
              </div>
              <h3 className="text-lg font-semibold">{status.label}</h3>
              <p className="text-sm text-muted-foreground mt-1">
                {leave.status === 'pending'
                  ? 'Menunggu persetujuan atasan'
                  : leave.status === 'approved'
                    ? `Disetujui oleh ${leave.approved_by_name}`
                    : leave.status === 'rejected'
                      ? `Ditolak oleh ${leave.approved_by_name}`
                      : 'Pengajuan dibatalkan'}
              </p>
            </CardContent>
          </Card>

          {/* Timeline */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Clock className="h-5 w-5 text-primary" />
                Timeline
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {timeline.map((item, index) => (
                  <div key={item.id} className="flex gap-3">
                    <div className="flex flex-col items-center">
                      <div className={`w-3 h-3 rounded-full ${item.type === 'created' ? 'bg-primary' :
                          item.type === 'approved' ? 'bg-success' :
                            item.type === 'rejected' ? 'bg-destructive' :
                              'bg-warning'
                        }`} />
                      {index < timeline.length - 1 && (
                        <div className="w-px h-full bg-border mt-1" />
                      )}
                    </div>
                    <div className="flex-1 pb-4">
                      <p className="text-sm font-medium">{item.action}</p>
                      <p className="text-xs text-muted-foreground">{item.user}</p>
                      <p className="text-xs text-muted-foreground">
                        {item.date && new Date(item.date).toLocaleString('id-ID')}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* Leave Balance */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Sisa Cuti</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                <div className="flex justify-between items-center">
                  <span className="text-sm">Cuti Tahunan</span>
                  <span className="font-semibold">{balance.annual_remaining} hari</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm">Cuti Sakit</span>
                  <span className="font-semibold">{balance.sick_remaining} hari</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm">Cuti Khusus</span>
                  <span className="font-semibold">{balance.special_remaining} hari</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Reject Dialog */}
      <RejectDialog
        open={showRejectDialog}
        onOpenChange={setShowRejectDialog}
        onConfirm={handleRejectConfirm}
        isLoading={rejectLeaveRequestMutation.isPending}
      />
    </div>
  );
}
