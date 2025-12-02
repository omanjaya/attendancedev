import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  Calendar,
  Plane,
  Plus,
  CheckCircle,
  XCircle,
  Clock,
  FileText,
  X,
} from 'lucide-react';
import { PageLayout } from '@/components/shared/PageLayout';
import { ContentCard } from '@/components/shared/ContentCard';
import { useAuthStore } from '@/stores';
import { format, parseISO, differenceInDays } from 'date-fns';
import { id } from 'date-fns/locale';

interface LeaveRequest {
  id: number;
  leaveType: string;
  startDate: string;
  endDate: string;
  days: number;
  reason: string;
  status: 'pending' | 'approved' | 'rejected';
  approvedBy?: string;
  approvedAt?: string;
  rejectedReason?: string;
  createdAt: string;
}

/**
 * Employee Leave Page
 * Request and view personal leave requests
 */
export function DesktopEmployeeLeavePage() {
  const { user } = useAuthStore();
  const queryClient = useQueryClient();
  const [showRequestForm, setShowRequestForm] = useState(false);
  const [filterStatus, setFilterStatus] = useState<'all' | 'pending' | 'approved' | 'rejected'>('all');

  // Form state
  const [leaveType, setLeaveType] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [reason, setReason] = useState('');

  // Fetch leave balance
  const { data: leaveBalance } = useQuery({
    queryKey: ['employee', 'leave-balance', user?.id],
    queryFn: async () => {
      // TODO: Replace with actual API call
      return {
        total: 12,
        used: 3,
        pending: 2,
        remaining: 7,
      };
    },
  });

  // Fetch leave requests
  const { data: leaveRequests, isLoading } = useQuery({
    queryKey: ['employee', 'leave-requests', user?.id],
    queryFn: async () => {
      // TODO: Replace with actual API call
      return [
        {
          id: 1,
          leaveType: 'Cuti Tahunan',
          startDate: '2025-12-10',
          endDate: '2025-12-12',
          days: 3,
          reason: 'Liburan keluarga',
          status: 'pending' as const,
          createdAt: '2025-12-01T08:00:00',
        },
        {
          id: 2,
          leaveType: 'Cuti Sakit',
          startDate: '2025-11-28',
          endDate: '2025-11-29',
          days: 2,
          reason: 'Sakit demam',
          status: 'approved' as const,
          approvedBy: 'Manager HR',
          approvedAt: '2025-11-27T10:30:00',
          createdAt: '2025-11-27T08:00:00',
        },
        {
          id: 3,
          leaveType: 'Cuti Tahunan',
          startDate: '2025-11-15',
          endDate: '2025-11-16',
          days: 2,
          reason: 'Keperluan pribadi',
          status: 'rejected' as const,
          approvedBy: 'Manager HR',
          rejectedReason: 'Sudah ada karyawan lain yang cuti pada tanggal tersebut',
          createdAt: '2025-11-10T08:00:00',
        },
      ] as LeaveRequest[];
    },
  });

  // Create leave request mutation
  const createLeaveMutation = useMutation({
    mutationFn: async (data: { leaveType: string; startDate: string; endDate: string; reason: string }) => {
      // TODO: Replace with actual API call
      console.log('Creating leave request:', data);
      return { success: true };
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['employee', 'leave-requests'] });
      queryClient.invalidateQueries({ queryKey: ['employee', 'leave-balance'] });
      setShowRequestForm(false);
      resetForm();
    },
  });

  // Cancel leave request mutation
  const cancelLeaveMutation = useMutation({
    mutationFn: async (id: number) => {
      // TODO: Replace with actual API call
      console.log('Canceling leave request:', id);
      return { success: true };
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['employee', 'leave-requests'] });
      queryClient.invalidateQueries({ queryKey: ['employee', 'leave-balance'] });
    },
  });

  const resetForm = () => {
    setLeaveType('');
    setStartDate('');
    setEndDate('');
    setReason('');
  };

  const handleSubmitRequest = (e: React.FormEvent) => {
    e.preventDefault();
    createLeaveMutation.mutate({
      leaveType,
      startDate,
      endDate,
      reason,
    });
  };

  const handleCancelRequest = (id: number) => {
    if (confirm('Apakah Anda yakin ingin membatalkan pengajuan cuti ini?')) {
      cancelLeaveMutation.mutate(id);
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending':
        return 'text-yellow-700 dark:text-yellow-300 bg-yellow-100 dark:bg-yellow-900/30';
      case 'approved':
        return 'text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900/30';
      case 'rejected':
        return 'text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/30';
      default:
        return 'text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-900/30';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'pending':
        return <Clock className="h-4 w-4" />;
      case 'approved':
        return <CheckCircle className="h-4 w-4" />;
      case 'rejected':
        return <XCircle className="h-4 w-4" />;
      default:
        return <FileText className="h-4 w-4" />;
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'pending':
        return 'Menunggu';
      case 'approved':
        return 'Disetujui';
      case 'rejected':
        return 'Ditolak';
      default:
        return status;
    }
  };

  const filteredRequests = leaveRequests?.filter((request) => {
    if (filterStatus === 'all') return true;
    return request.status === filterStatus;
  });

  const calculateDays = () => {
    if (startDate && endDate) {
      const days = differenceInDays(parseISO(endDate), parseISO(startDate)) + 1;
      return days > 0 ? days : 0;
    }
    return 0;
  };

  return (
    <PageLayout
      title="Cuti Saya"
      description="Ajukan dan kelola cuti pribadi"
      action={{
        label: 'Ajukan Cuti',
        icon: Plus,
        onClick: () => setShowRequestForm(true),
      }}
    >
      {/* Leave Balance Card */}
      <ContentCard className="mb-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <div className="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
              <Plane className="h-6 w-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <h3 className="text-sm font-medium text-muted-foreground">Saldo Cuti</h3>
              <p className="text-3xl font-bold text-blue-600 dark:text-blue-400">
                {leaveBalance?.remaining || 0} hari
              </p>
            </div>
          </div>
          <div className="grid grid-cols-3 gap-4 text-center">
            <div>
              <p className="text-xs text-muted-foreground">Total</p>
              <p className="text-lg font-semibold">{leaveBalance?.total || 0}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Terpakai</p>
              <p className="text-lg font-semibold">{leaveBalance?.used || 0}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Pending</p>
              <p className="text-lg font-semibold">{leaveBalance?.pending || 0}</p>
            </div>
          </div>
        </div>
      </ContentCard>

      {/* Request Form Modal */}
      {showRequestForm && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
          <div className="bg-background rounded-lg max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between p-4 border-b">
              <h2 className="text-lg font-semibold">Ajukan Cuti Baru</h2>
              <button
                onClick={() => {
                  setShowRequestForm(false);
                  resetForm();
                }}
                className="p-1 hover:bg-muted rounded"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            <form onSubmit={handleSubmitRequest} className="p-4 space-y-4">
              <div>
                <label className="block text-sm font-medium mb-2">
                  Jenis Cuti <span className="text-red-500">*</span>
                </label>
                <select
                  value={leaveType}
                  onChange={(e) => setLeaveType(e.target.value)}
                  className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                  required
                >
                  <option value="">Pilih jenis cuti</option>
                  <option value="Cuti Tahunan">Cuti Tahunan</option>
                  <option value="Cuti Sakit">Cuti Sakit</option>
                  <option value="Cuti Penting">Cuti Penting</option>
                  <option value="Cuti Melahirkan">Cuti Melahirkan</option>
                </select>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium mb-2">
                    Tanggal Mulai <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="date"
                    value={startDate}
                    onChange={(e) => setStartDate(e.target.value)}
                    className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-2">
                    Tanggal Selesai <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="date"
                    value={endDate}
                    onChange={(e) => setEndDate(e.target.value)}
                    min={startDate}
                    className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                    required
                  />
                </div>
              </div>

              {startDate && endDate && (
                <div className="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                  <p className="text-sm">
                    <span className="font-medium">Durasi:</span>{' '}
                    <span className="text-blue-600 dark:text-blue-400 font-semibold">
                      {calculateDays()} hari
                    </span>
                  </p>
                </div>
              )}

              <div>
                <label className="block text-sm font-medium mb-2">
                  Alasan <span className="text-red-500">*</span>
                </label>
                <textarea
                  value={reason}
                  onChange={(e) => setReason(e.target.value)}
                  className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary resize-none"
                  rows={4}
                  placeholder="Jelaskan alasan pengajuan cuti..."
                  required
                />
              </div>

              <div className="flex gap-3 pt-2">
                <button
                  type="button"
                  onClick={() => {
                    setShowRequestForm(false);
                    resetForm();
                  }}
                  className="flex-1 px-4 py-2 border rounded-lg hover:bg-muted transition-colors"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={createLeaveMutation.isPending}
                  className="flex-1 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50"
                >
                  {createLeaveMutation.isPending ? 'Mengajukan...' : 'Ajukan Cuti'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Filter */}
      <div className="flex items-center gap-2 mb-4">
        <span className="text-sm font-medium">Filter:</span>
        <div className="flex gap-2">
          {(['all', 'pending', 'approved', 'rejected'] as const).map((status) => (
            <button
              key={status}
              onClick={() => setFilterStatus(status)}
              className={`px-3 py-1 rounded-lg text-xs font-medium transition-colors ${filterStatus === status
                ? 'bg-primary text-primary-foreground'
                : 'border hover:bg-muted'
                }`}
            >
              {status === 'all' ? 'Semua' : getStatusLabel(status)}
            </button>
          ))}
        </div>
      </div>

      {/* Leave Requests List */}
      <ContentCard title="Riwayat Pengajuan Cuti">
        <div className="space-y-3">
          {isLoading ? (
            <div className="flex items-center justify-center py-12">
              <div className="text-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-3" />
                <p className="text-sm text-muted-foreground">Memuat data...</p>
              </div>
            </div>
          ) : filteredRequests && filteredRequests.length > 0 ? (
            filteredRequests.map((request) => (
              <div
                key={request.id}
                className="p-4 rounded-lg border bg-card hover:bg-muted/50 transition-colors"
              >
                <div className="flex items-start justify-between gap-4 mb-3">
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-2">
                      <h3 className="text-sm font-semibold">{request.leaveType}</h3>
                      <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${getStatusColor(request.status)}`}>
                        {getStatusIcon(request.status)}
                        {getStatusLabel(request.status)}
                      </span>
                    </div>
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                      <Calendar className="h-4 w-4" />
                      <span>
                        {format(parseISO(request.startDate), 'dd MMM', { locale: id })} -{' '}
                        {format(parseISO(request.endDate), 'dd MMM yyyy', { locale: id })}
                      </span>
                      <span className="text-xs">({request.days} hari)</span>
                    </div>
                  </div>

                  {request.status === 'pending' && (
                    <button
                      onClick={() => handleCancelRequest(request.id)}
                      disabled={cancelLeaveMutation.isPending}
                      className="px-3 py-1 text-xs border border-red-300 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors"
                    >
                      Batalkan
                    </button>
                  )}
                </div>

                <div className="p-3 bg-muted/50 rounded text-sm mb-2">
                  <p className="text-xs text-muted-foreground mb-1">Alasan:</p>
                  <p>{request.reason}</p>
                </div>

                {request.status === 'approved' && request.approvedBy && (
                  <div className="text-xs text-muted-foreground">
                    <CheckCircle className="h-3 w-3 inline mr-1" />
                    Disetujui oleh {request.approvedBy} pada{' '}
                    {format(parseISO(request.approvedAt!), 'dd MMM yyyy HH:mm', { locale: id })}
                  </div>
                )}

                {request.status === 'rejected' && request.rejectedReason && (
                  <div className="p-2 bg-red-50 dark:bg-red-900/20 rounded text-xs text-red-700 dark:text-red-300">
                    <strong>Alasan Penolakan:</strong> {request.rejectedReason}
                  </div>
                )}

                <div className="text-xs text-muted-foreground mt-2">
                  Diajukan pada {format(parseISO(request.createdAt), 'dd MMM yyyy HH:mm', { locale: id })}
                </div>
              </div>
            ))
          ) : (
            <div className="text-center py-12">
              <Plane className="h-12 w-12 text-muted-foreground mx-auto mb-3 opacity-50" />
              <p className="text-sm text-muted-foreground">
                {filterStatus === 'all'
                  ? 'Belum ada pengajuan cuti'
                  : `Tidak ada cuti dengan status ${getStatusLabel(filterStatus)}`}
              </p>
            </div>
          )}
        </div>
      </ContentCard>
    </PageLayout>
  );
}
