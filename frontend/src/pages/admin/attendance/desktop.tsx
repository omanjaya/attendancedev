import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  Calendar,
  Clock,
  Users,
  Download,
  Filter,
  Search,
  CheckCircle,
  XCircle,
  AlertCircle,
  Edit,
  Plus,
  X,
} from 'lucide-react';
import { PageLayout } from '@/components/shared/PageLayout';
import { StatsGrid } from '@/components/shared/StatsGrid';
import { ContentCard } from '@/components/shared/ContentCard';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { getAttendance, getAttendanceStatistics } from '@/lib/api/attendance';
import type { AttendanceStatus } from '@/types/attendance';

interface AttendanceRecord {
  id: number;
  employee_id: string; // Backend returns string for employee_id column
  employee_name: string;
  employeePhoto?: string;
  date: string;
  check_in_formatted?: string;
  check_out_formatted?: string;
  status: 'present' | 'late' | 'absent' | 'leave' | 'pending';
  location?: string;
  notes?: string;
  needsApproval: boolean;
}

/**
 * Admin Attendance Page
 * Manage all employees' attendance with approval capabilities
 */
export function DesktopAdminAttendancePage() {
  const queryClient = useQueryClient();
  const [selectedMonth, setSelectedMonth] = useState(new Date());
  const [searchQuery, setSearchQuery] = useState('');
  const [filterStatus, setFilterStatus] = useState<string>('all');
  const [showManualEntry, setShowManualEntry] = useState(false);

  // Manual entry form state
  const [manualEmployeeId, setManualEmployeeId] = useState('');
  const [manualDate, setManualDate] = useState('');
  const [manualCheckIn, setManualCheckIn] = useState('');
  const [manualCheckOut, setManualCheckOut] = useState('');
  const [manualNotes, setManualNotes] = useState('');

  // Fetch attendance statistics
  const { data: stats } = useQuery({
    queryKey: ['admin', 'attendance-stats', format(selectedMonth, 'yyyy-MM-dd')],
    queryFn: () => getAttendanceStatistics(format(selectedMonth, 'yyyy-MM-dd')),
  });

  // Fetch attendance records
  const { data: attendanceData, isLoading: recordsLoading } = useQuery({
    queryKey: ['admin', 'attendance-records', format(selectedMonth, 'yyyy-MM'), searchQuery, filterStatus],
    queryFn: () => getAttendance({
      month: format(selectedMonth, 'yyyy-MM'),
      search: searchQuery || undefined,
      status: filterStatus !== 'all' ? filterStatus as AttendanceStatus : undefined,
    }),
  });

  const _attendanceRecords = attendanceData?.data || [];

  // Approve attendance mutation
  const approveAttendanceMutation = useMutation({
    mutationFn: async (id: number) => {
      // TODO: Replace with actual API call
      console.log('Approving attendance:', id);
      return { success: true };
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'attendance-records'] });
      queryClient.invalidateQueries({ queryKey: ['admin', 'attendance-stats'] });
    },
  });

  // Reject attendance mutation
  const rejectAttendanceMutation = useMutation({
    mutationFn: async ({ id, reason }: { id: number; reason: string }) => {
      // TODO: Replace with actual API call
      console.log('Rejecting attendance:', id, reason);
      return { success: true };
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'attendance-records'] });
      queryClient.invalidateQueries({ queryKey: ['admin', 'attendance-stats'] });
    },
  });

  // Manual entry mutation
  const manualEntryMutation = useMutation({
    mutationFn: async (data: {
      employeeId: string;
      date: string;
      checkIn: string;
      checkOut: string;
      notes: string;
    }) => {
      // TODO: Replace with actual API call
      console.log('Creating manual entry:', data);
      return { success: true };
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'attendance-records'] });
      queryClient.invalidateQueries({ queryKey: ['admin', 'attendance-stats'] });
      setShowManualEntry(false);
      resetManualForm();
    },
  });

  const resetManualForm = () => {
    setManualEmployeeId('');
    setManualDate('');
    setManualCheckIn('');
    setManualCheckOut('');
    setManualNotes('');
  };

  const handleApprove = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menyetujui absensi ini?')) {
      approveAttendanceMutation.mutate(id);
    }
  };

  const handleReject = (id: number) => {
    const reason = prompt('Alasan penolakan:');
    if (reason) {
      rejectAttendanceMutation.mutate({ id, reason });
    }
  };

  const handleManualSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    manualEntryMutation.mutate({
      employeeId: manualEmployeeId,
      date: manualDate,
      checkIn: manualCheckIn,
      checkOut: manualCheckOut,
      notes: manualNotes,
    });
  };

  const handleExport = () => {
    // TODO: Implement export functionality
    console.log('Export attendance data');
  };

  const dashboardStats = [
    {
      label: 'Total Karyawan',
      value: stats?.total_employees || 0,
      description: 'Karyawan aktif',
      icon: Users,
      color: 'info' as const,
    },
    {
      label: 'Hadir Hari Ini',
      value: stats?.present_today || 0,
      description: `${stats?.late_today || 0} terlambat`,
      icon: CheckCircle,
      color: 'success' as const,
      trend: 'up' as const,
      trendValue: `${stats?.attendance_rate || 0}%`,
    },
    {
      label: 'Tidak Hadir',
      value: stats?.absent_today || 0,
      description: 'Hari ini',
      icon: XCircle,
      color: 'destructive' as const,
    },
    {
      label: 'Perlu Approval',
      value: stats?.pending_approvals || 0,
      description: 'Koreksi absensi',
      icon: AlertCircle,
      color: 'warning' as const,
    },
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'present':
        return 'text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900/30';
      case 'late':
        return 'text-yellow-700 dark:text-yellow-300 bg-yellow-100 dark:bg-yellow-900/30';
      case 'absent':
        return 'text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/30';
      case 'leave':
        return 'text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900/30';
      case 'pending':
        return 'text-orange-700 dark:text-orange-300 bg-orange-100 dark:bg-orange-900/30';
      default:
        return 'text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-900/30';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'present':
        return <CheckCircle className="h-4 w-4" />;
      case 'late':
        return <Clock className="h-4 w-4" />;
      case 'absent':
        return <XCircle className="h-4 w-4" />;
      case 'leave':
        return <Calendar className="h-4 w-4" />;
      case 'pending':
        return <AlertCircle className="h-4 w-4" />;
      default:
        return <AlertCircle className="h-4 w-4" />;
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'present':
        return 'Hadir';
      case 'late':
        return 'Terlambat';
      case 'absent':
        return 'Tidak Hadir';
      case 'leave':
        return 'Cuti';
      case 'pending':
        return 'Pending Approval';
      default:
        return status;
    }
  };

  // Transform and filter records
  const transformedRecords: AttendanceRecord[] = (attendanceData?.data || []).map((record: any) => ({
    id: record.id,
    employee_id: record.employee_id?.toString() || '',
    employee_name: record.employee_name || record.employee?.full_name || 'Unknown',
    employeePhoto: record.employee?.avatar_url,
    date: record.date,
    check_in_formatted: record.check_in_time || record.check_in || undefined,
    check_out_formatted: record.check_out_time || record.check_out || undefined,
    status: record.status || 'present',
    location: typeof record.location === 'object' ? record.location?.address : record.location,
    notes: record.notes,
    needsApproval: record.needs_approval || record.status === 'pending' || false,
  }));

  const filteredRecords = transformedRecords.filter((record) => {
    const matchesSearch = record.employee_name?.toLowerCase().includes(searchQuery.toLowerCase()) ?? false;
    const matchesStatus = filterStatus === 'all' || record.status === filterStatus;
    return matchesSearch && matchesStatus;
  });

  return (
    <PageLayout
      title="Kelola Absensi"
      description="Kelola dan approve absensi karyawan"
      actions={
        <button
          onClick={() => setShowManualEntry(true)}
          className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors"
        >
          <Plus className="h-4 w-4" />
          Input Manual
        </button>
      }
    >
      {/* Stats Grid */}
      <StatsGrid stats={dashboardStats} />

      {/* Filters and Actions */}
      <div className="flex flex-col md:flex-row gap-4 mt-6 mb-4">
        {/* Search */}
        <div className="flex-1">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Cari nama karyawan..."
              className="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              aria-label="Cari nama karyawan"
              title="Cari nama karyawan"
            />
          </div>
        </div>

        {/* Status Filter */}
        <div className="flex items-center gap-2">
          <Filter className="h-4 w-4 text-muted-foreground" />
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            aria-label="Filter Status"
            title="Filter Status"
          >
            <option value="all">Semua Status</option>
            <option value="present">Hadir</option>
            <option value="late">Terlambat</option>
            <option value="absent">Tidak Hadir</option>
            <option value="leave">Cuti</option>
            <option value="pending">Pending</option>
          </select>
        </div>

        {/* Date Filter */}
        <div className="flex items-center gap-2">
          <Calendar className="h-4 w-4 text-muted-foreground" />
          <input
            type="month"
            value={format(selectedMonth, 'yyyy-MM')}
            onChange={(e) => setSelectedMonth(parseISO(e.target.value + '-01'))}
            className="px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            aria-label="Filter Bulan"
            title="Filter Bulan"
          />
        </div>

        {/* Export Button */}
        <button
          onClick={handleExport}
          className="inline-flex items-center gap-2 px-4 py-2 border rounded-lg hover:bg-muted transition-colors"
        >
          <Download className="h-4 w-4" />
          Export
        </button>
      </div>

      {/* Attendance Records */}
      <ContentCard title="Data Absensi">
        <div className="space-y-2">
          {recordsLoading ? (
            <div className="flex items-center justify-center py-12">
              <div className="text-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-3" />
                <p className="text-sm text-muted-foreground">Memuat data...</p>
              </div>
            </div>
          ) : filteredRecords && filteredRecords.length > 0 ? (
            filteredRecords.map((record) => (
              <div
                key={record.id}
                className={`p-4 rounded-lg border transition-colors ${record.needsApproval ? 'border-orange-300 bg-orange-50/50 dark:bg-orange-900/10' : 'bg-card hover:bg-muted/50'
                  }`}
              >
                <div className="flex items-start justify-between gap-4">
                  <div className="flex-1">
                    <div className="flex items-center gap-3 mb-2">
                      <div className="flex items-center gap-2">
                        <h3 className="text-sm font-semibold">{record.employee_name}</h3>
                        <span className="text-xs text-muted-foreground">#{record.employee_id}</span>
                      </div>
                      <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${getStatusColor(record.status)}`}>
                        {getStatusIcon(record.status)}
                        {getStatusLabel(record.status)}
                      </span>
                      {record.needsApproval && (
                        <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300">
                          <AlertCircle className="h-3 w-3" />
                          Perlu Approval
                        </span>
                      )}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-4 gap-2 text-sm text-muted-foreground">
                      <div className="flex items-center gap-2">
                        <Calendar className="h-4 w-4" />
                        <span>{format(parseISO(record.date), 'dd MMM yyyy', { locale: id })}</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <Clock className="h-4 w-4" />
                        <span>In: {record.check_in_formatted || '-'}</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <Clock className="h-4 w-4" />
                        <span>Out: {record.check_out_formatted || '-'}</span>
                      </div>
                      {record.location && (
                        <div className="flex items-center gap-2">
                          <span>{record.location}</span>
                        </div>
                      )}
                    </div>

                    {record.notes && (
                      <div className="mt-2 p-2 bg-muted/50 rounded text-xs text-muted-foreground italic">
                        {record.notes}
                      </div>
                    )}
                  </div>

                  <div className="flex flex-col gap-2">
                    {record.needsApproval ? (
                      <>
                        <button
                          onClick={() => handleApprove(record.id)}
                          disabled={approveAttendanceMutation.isPending}
                          className="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-xs font-medium disabled:opacity-50"
                        >
                          <CheckCircle className="h-3 w-3" />
                          Approve
                        </button>
                        <button
                          onClick={() => handleReject(record.id)}
                          disabled={rejectAttendanceMutation.isPending}
                          className="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-xs font-medium disabled:opacity-50"
                        >
                          <XCircle className="h-3 w-3" />
                          Reject
                        </button>
                      </>
                    ) : (
                      <button
                        onClick={() => console.log('Edit attendance:', record.id)}
                        className="inline-flex items-center gap-1 px-3 py-1.5 border rounded-lg hover:bg-muted transition-colors text-xs font-medium"
                      >
                        <Edit className="h-3 w-3" />
                        Edit
                      </button>
                    )}
                  </div>
                </div>
              </div>
            ))
          ) : (
            <div className="text-center py-12">
              <Users className="h-12 w-12 text-muted-foreground mx-auto mb-3 opacity-50" />
              <p className="text-sm text-muted-foreground">Tidak ada data absensi</p>
            </div>
          )}
        </div>
      </ContentCard>

      {/* Manual Entry Modal */}
      {showManualEntry && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
          <div className="bg-background rounded-lg max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between p-4 border-b">
              <h2 className="text-lg font-semibold">Input Absensi Manual</h2>
              <button
                onClick={() => {
                  setShowManualEntry(false);
                  resetManualForm();
                }}
                className="p-1 hover:bg-muted rounded"
                aria-label="Tutup"
                title="Tutup"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            <form onSubmit={handleManualSubmit} className="p-4 space-y-4">
              <div>
                <label htmlFor="manual-employee" className="block text-sm font-medium mb-2">
                  Karyawan <span className="text-red-500">*</span>
                </label>
                <select
                  id="manual-employee"
                  value={manualEmployeeId}
                  onChange={(e) => setManualEmployeeId(e.target.value)}
                  className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                  required
                >
                  <option value="">Pilih karyawan</option>
                  <option value="101">John Doe (#101)</option>
                  <option value="102">Jane Smith (#102)</option>
                  <option value="103">Bob Johnson (#103)</option>
                </select>
              </div>

              <div>
                <label htmlFor="manual-date" className="block text-sm font-medium mb-2">
                  Tanggal <span className="text-red-500">*</span>
                </label>
                <input
                  id="manual-date"
                  type="date"
                  value={manualDate}
                  onChange={(e) => setManualDate(e.target.value)}
                  className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label htmlFor="manual-checkin" className="block text-sm font-medium mb-2">
                    Check-In <span className="text-red-500">*</span>
                  </label>
                  <input
                    id="manual-checkin"
                    type="time"
                    value={manualCheckIn}
                    onChange={(e) => setManualCheckIn(e.target.value)}
                    className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                    required
                  />
                </div>

                <div>
                  <label htmlFor="manual-checkout" className="block text-sm font-medium mb-2">
                    Check-Out
                  </label>
                  <input
                    id="manual-checkout"
                    type="time"
                    value={manualCheckOut}
                    onChange={(e) => setManualCheckOut(e.target.value)}
                    className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                    title="Check-Out Time"
                  />
                </div>
              </div>

              <div>
                <label htmlFor="manual-notes" className="block text-sm font-medium mb-2">
                  Catatan
                </label>
                <textarea
                  id="manual-notes"
                  value={manualNotes}
                  onChange={(e) => setManualNotes(e.target.value)}
                  className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary resize-none"
                  rows={3}
                  placeholder="Catatan tambahan (opsional)"
                  title="Catatan tambahan"
                />
              </div>

              <div className="flex gap-3 pt-2">
                <button
                  type="button"
                  onClick={() => {
                    setShowManualEntry(false);
                    resetManualForm();
                  }}
                  className="flex-1 px-4 py-2 border rounded-lg hover:bg-muted transition-colors"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={manualEntryMutation.isPending}
                  className="flex-1 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50"
                >
                  {manualEntryMutation.isPending ? 'Menyimpan...' : 'Simpan'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </PageLayout>
  );
}
