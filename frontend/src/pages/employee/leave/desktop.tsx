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
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { useEmployeeLeavePage } from '@/hooks/use-employee-leave-page';

/**
 * Employee Leave Page
 * Request and view personal leave requests
 */
export function DesktopEmployeeLeavePage() {
  // Use shared hook for all logic
  const logic = useEmployeeLeavePage();

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


  return (
    <PageLayout
      title="Cuti Saya"
      description="Ajukan dan kelola cuti pribadi"
      actions={
        <button
          onClick={() => logic.setShowRequestForm(true)}
          className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors"
        >
          <Plus className="h-4 w-4" />
          Ajukan Cuti
        </button>
      }
    >
      {/* Leave Balance Card */}
      <ContentCard className="mb-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <div className="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
              <Plane className="h-6 w-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <h3 className="text-sm font-medium text-muted-foreground">Saldo Cuti Tahunan</h3>
              <p className="text-3xl font-bold text-blue-600 dark:text-blue-400">
                {logic.leaveBalance?.annual_remaining || 0} hari
              </p>
            </div>
          </div>
          <div className="grid grid-cols-3 gap-4 text-center">
            <div>
              <p className="text-xs text-muted-foreground">Total</p>
              <p className="text-lg font-semibold">{logic.leaveBalance?.annual_total || 0}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Terpakai</p>
              <p className="text-lg font-semibold">{logic.leaveBalance?.annual_used || 0}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Cuti Sakit</p>
              <p className="text-lg font-semibold">{logic.leaveBalance?.sick_remaining || 0}</p>
            </div>
          </div>
        </div>
      </ContentCard>

      {/* Request Form Modal */}
      {logic.showRequestForm && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
          <div className="bg-background rounded-lg max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between p-4 border-b">
              <h2 className="text-lg font-semibold">Ajukan Cuti Baru</h2>
              <button
                onClick={() => {
                  logic.setShowRequestForm(false);
                  logic.resetForm();
                }}
                className="p-1 hover:bg-muted rounded"
                aria-label="Close modal"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            <form onSubmit={logic.handleSubmitRequest} className="p-4 space-y-4">
              <div>
                <label className="block text-sm font-medium mb-2">
                  Jenis Cuti <span className="text-red-500">*</span>
                </label>
                <select
                  value={logic.leaveType}
                  onChange={(e) => logic.setLeaveType(e.target.value)}
                  className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                  required
                  aria-label="Pilih jenis cuti"
                >
                  <option value="">Pilih jenis cuti</option>
                  <option value="annual">Cuti Tahunan</option>
                  <option value="sick">Cuti Sakit</option>
                  <option value="special">Cuti Khusus</option>
                  <option value="maternity">Cuti Melahirkan</option>
                </select>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium mb-2">
                    Tanggal Mulai <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="date"
                    value={logic.startDate}
                    onChange={(e) => logic.setStartDate(e.target.value)}
                    className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                    required
                    aria-label="Tanggal Mulai"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-2">
                    Tanggal Selesai <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="date"
                    value={logic.endDate}
                    onChange={(e) => logic.setEndDate(e.target.value)}
                    min={logic.startDate}
                    className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                    required
                    aria-label="Tanggal Selesai"
                  />
                </div>
              </div>

              {logic.startDate && logic.endDate && (
                <div className="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                  <p className="text-sm">
                    <span className="font-medium">Durasi:</span>{' '}
                    <span className="text-blue-600 dark:text-blue-400 font-semibold">
                      {logic.calculateDays()} hari
                    </span>
                  </p>
                </div>
              )}

              <div>
                <label className="block text-sm font-medium mb-2">
                  Alasan <span className="text-red-500">*</span>
                </label>
                <textarea
                  value={logic.reason}
                  onChange={(e) => logic.setReason(e.target.value)}
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
                    logic.setShowRequestForm(false);
                    logic.resetForm();
                  }}
                  className="flex-1 px-4 py-2 border rounded-lg hover:bg-muted transition-colors"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={logic.createLeaveMutation.isPending}
                  className="flex-1 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50"
                >
                  {logic.createLeaveMutation.isPending ? 'Mengajukan...' : 'Ajukan Cuti'}
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
              onClick={() => logic.setFilterStatus(status)}
              className={`px-3 py-1 rounded-lg text-xs font-medium transition-colors ${logic.filterStatus === status
                ? 'bg-primary text-primary-foreground'
                : 'border hover:bg-muted'
                }`}
            >
              {status === 'all' ? 'Semua' : logic.getStatusLabel(status)}
            </button>
          ))}
        </div>
      </div>

      {/* Leave Requests List */}
      <ContentCard title="Riwayat Pengajuan Cuti">
        <div className="space-y-3">
          {logic.isLoading ? (
            <div className="flex items-center justify-center py-12">
              <div className="text-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-3" />
                <p className="text-sm text-muted-foreground">Memuat data...</p>
              </div>
            </div>
          ) : logic.filteredRequests && logic.filteredRequests?.length > 0 ? (
            logic.filteredRequests?.map((request) => (
              <div
                key={request.id}
                className="p-4 rounded-lg border bg-card hover:bg-muted/50 transition-colors"
              >
                <div className="flex items-start justify-between gap-4 mb-3">
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-2">
                      <h3 className="text-sm font-semibold">{request.leave_type?.name || request.leave_type_id}</h3>
                      <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${getStatusColor(request.status)}`}>
                        {getStatusIcon(request.status)}
                        {logic.getStatusLabel(request.status)}
                      </span>
                    </div>
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                      <Calendar className="h-4 w-4" />
                      <span>
                        {format(parseISO(request.start_date), 'dd MMM', { locale: id })} -{' '}
                        {format(parseISO(request.end_date), 'dd MMM yyyy', { locale: id })}
                      </span>
                      <span className="text-xs">({request.days_requested} hari)</span>
                    </div>
                  </div>

                  {request.status === 'pending' && (
                    <button
                      onClick={() => logic.handleCancelRequest(request.id)}
                      disabled={logic.cancelLeaveMutation.isPending}
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

                {request.status === 'approved' && request.approved_by && (
                  <div className="text-xs text-muted-foreground">
                    <CheckCircle className="h-3 w-3 inline mr-1" />
                    Disetujui oleh {request.approved_by} pada{' '}
                    {format(parseISO(request.approved_at!), 'dd MMM yyyy HH:mm', { locale: id })}
                  </div>
                )}

                {request.status === 'rejected' && request.rejection_reason && (
                  <div className="p-2 bg-red-50 dark:bg-red-900/20 rounded text-xs text-red-700 dark:text-red-300">
                    <strong>Alasan Penolakan:</strong> {request.rejection_reason}
                  </div>
                )}

                <div className="text-xs text-muted-foreground mt-2">
                  Diajukan pada {format(parseISO(request.created_at), 'dd MMM yyyy HH:mm', { locale: id })}
                </div>
              </div>
            ))
          ) : (
            <div className="text-center py-12">
              <Plane className="h-12 w-12 text-muted-foreground mx-auto mb-3 opacity-50" />
              <p className="text-sm text-muted-foreground">
                {logic.filterStatus === 'all'
                  ? 'Belum ada pengajuan cuti'
                  : `Tidak ada cuti dengan status ${logic.getStatusLabel(logic.filterStatus)}`}
              </p>
            </div>
          )}
        </div>
      </ContentCard>
    </PageLayout>
  );
}
