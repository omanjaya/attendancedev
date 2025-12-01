import { useState } from 'react';
import { useNavigate } from '@tanstack/react-router';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  ChevronLeft,
  Plus,
  MoreVertical,
  Calendar,
  FileText,
  MapPin,
  Phone,
  Upload,
  Check,
  X,
  ChevronRight,
  Loader2,
  Clock,
  Filter,
  Search
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useAuthStore } from '@/stores';
import { getLeaveRequests, createLeaveRequest } from '@/lib/api/leave';
import { cn } from '@/lib/utils';
import type { LeaveStatus, LeaveType, LeaveRequestFormData } from '@/types/leave';
import { Badge } from '@/components/ui/badge';
import { LoadingState } from '@/components/states';

export function MobileLeaveRequestPage() {
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const [showCreateForm, setShowCreateForm] = useState(false);

  // Fetch user's leave requests
  const { data: leaveRequests, isLoading } = useQuery({
    queryKey: ['leave-requests', user?.id],
    queryFn: () => getLeaveRequests({ employee_id: user?.id?.toString() }),
    enabled: !!user?.id,
  });

  const getStatusBadge = (status: LeaveStatus) => {
    switch (status) {
      case 'approved':
        return (
          <Badge variant="outline" className="bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800">
            DISETUJUI
          </Badge>
        );
      case 'rejected':
        return (
          <Badge variant="outline" className="bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-800">
            DITOLAK
          </Badge>
        );
      case 'cancelled':
        return (
          <Badge variant="outline" className="bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800">
            DIBATALKAN
          </Badge>
        );
      default:
        return (
          <Badge variant="outline" className="bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800">
            MENUNGGU
          </Badge>
        );
    }
  };

  const getLeaveTypeName = (type: string) => {
    const types: Record<string, string> = {
      annual: 'Cuti Tahunan',
      sick: 'Cuti Sakit',
      maternity: 'Cuti Melahirkan',
      paternity: 'Cuti Ayah',
      unpaid: 'Cuti Tanpa Gaji',
      study: 'Cuti Belajar',
      other: 'Cuti Lainnya',
    };
    return types[type] || type;
  };

  // Get user initials
  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  if (showCreateForm) {
    return <MobileLeaveCreateForm onBack={() => setShowCreateForm(false)} />;
  }

  return (
    <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-20">
      {/* Header Wrapper */}
      <div className="px-4 pt-3 pb-3 sticky top-0 z-20">
        <div className="bg-card/80 dark:bg-card/60 backdrop-blur-md rounded-3xl p-1.5 shadow-xl border border-border/40 dark:border-border/30">
          <div className="bg-gradient-to-r from-orange-600 to-orange-500 dark:from-orange-900 dark:to-orange-800 px-4 py-3 rounded-[20px] flex items-center gap-3 shadow-lg">
            <button
              onClick={() => navigate({ to: '/dashboard' })}
              className="p-2 -ml-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
            >
              <ChevronLeft className="h-5 w-5 text-white" />
            </button>
            <h1 className="text-base font-bold text-white flex-1">Pengajuan Cuti</h1>
            <button className="p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95">
              <Search className="h-5 w-5 text-white" />
            </button>
          </div>
        </div>
      </div>

      {/* Content */}
      <div className="px-4 space-y-4">
        {/* Stats / Summary Card */}
        <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50 relative overflow-hidden">
          <div className="absolute top-0 right-0 p-4 opacity-5">
            <Calendar className="h-24 w-24" />
          </div>
          <div className="relative z-10">
            <h2 className="text-sm font-bold text-foreground mb-4">Ringkasan Cuti</h2>
            <div className="grid grid-cols-2 gap-4">
              <div className="bg-muted/30 rounded-2xl p-3 border border-border/50">
                <p className="text-xs text-muted-foreground mb-1">Sisa Cuti Tahunan</p>
                <p className="text-xl font-bold text-foreground">12 <span className="text-xs font-normal text-muted-foreground">Hari</span></p>
              </div>
              <div className="bg-muted/30 rounded-2xl p-3 border border-border/50">
                <p className="text-xs text-muted-foreground mb-1">Cuti Terpakai</p>
                <p className="text-xl font-bold text-foreground">3 <span className="text-xs font-normal text-muted-foreground">Hari</span></p>
              </div>
            </div>
          </div>
        </div>

        {/* Action Bar */}
        <div className="flex items-center justify-between">
          <h2 className="text-sm font-bold text-foreground">Riwayat Pengajuan</h2>
          <button className="p-2 hover:bg-muted rounded-full transition-colors">
            <Filter className="h-4 w-4 text-muted-foreground" />
          </button>
        </div>

        {/* Leave Requests List */}
        <div className="space-y-3">
          {isLoading ? (
            <div className="flex items-center justify-center py-12">
              <LoadingState message="Memuat data cuti..." size="sm" />
            </div>
          ) : leaveRequests?.data && leaveRequests.data.length > 0 ? (
            leaveRequests.data.map((leave) => (
              <div
                key={leave.id}
                className="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-border/50 active:scale-[0.99] transition-transform"
              >
                <div className="flex items-start gap-3">
                  {/* Icon based on type */}
                  <div className={cn(
                    "h-10 w-10 rounded-xl flex items-center justify-center shrink-0",
                    leave.type === 'sick' ? "bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400" :
                      leave.type === 'annual' ? "bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400" :
                        "bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400"
                  )}>
                    <FileText className="h-5 w-5" />
                  </div>

                  {/* Content */}
                  <div className="flex-1 min-w-0 space-y-1">
                    <div className="flex items-start justify-between gap-2">
                      <h3 className="text-sm font-bold text-foreground line-clamp-1">
                        {getLeaveTypeName(leave.type)}
                      </h3>
                      {getStatusBadge(leave.status)}
                    </div>

                    <p className="text-xs text-muted-foreground line-clamp-2">
                      {leave.reason}
                    </p>

                    <div className="flex items-center gap-3 pt-1">
                      <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <Calendar className="h-3.5 w-3.5" />
                        <span>
                          {new Date(leave.start_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })} - {new Date(leave.end_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })}
                        </span>
                      </div>
                      <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <Clock className="h-3.5 w-3.5" />
                        <span>{leave.total_days} Hari</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ))
          ) : (
            <div className="bg-white dark:bg-gray-900 rounded-3xl p-8 text-center shadow-sm border border-border/50">
              <div className="h-16 w-16 bg-muted/50 rounded-full flex items-center justify-center mx-auto mb-4">
                <FileText className="h-8 w-8 text-muted-foreground/50" />
              </div>
              <p className="text-sm font-bold text-foreground mb-1">Belum Ada Pengajuan</p>
              <p className="text-xs text-muted-foreground mb-4">
                Anda belum pernah mengajukan cuti.
              </p>
            </div>
          )}
        </div>
      </div>

      {/* Floating Action Button */}
      <div className="fixed bottom-6 right-6 z-50">
        <Button
          onClick={() => setShowCreateForm(true)}
          className="h-14 w-14 rounded-full shadow-xl bg-primary hover:bg-primary/90 text-primary-foreground p-0 flex items-center justify-center"
        >
          <Plus className="h-6 w-6" />
        </Button>
      </div>
    </div>
  );
}

// Leave Create Form Component (Multi-step)
function MobileLeaveCreateForm({ onBack }: { onBack: () => void }) {
  const [step, setStep] = useState<1 | 2>(1);
  const [formData, setFormData] = useState({
    type: '' as LeaveType | '',
    reason: '',
    emergency_contact: '', // alamat cuti
    emergency_phone: '', // nomor HP
    attachment: null as File | null,
    start_date: '',
    end_date: '',
    duration_type: 'full_day' as const,
  });
  const queryClient = useQueryClient();

  const createMutation = useMutation({
    mutationFn: (data: LeaveRequestFormData) => createLeaveRequest(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['leave-requests'] });
      alert('Pengajuan cuti berhasil dikirim');
      onBack();
    },
    onError: (error: any) => {
      alert(error.response?.data?.message || 'Terjadi kesalahan saat mengajukan cuti');
    },
  });

  const handleSubmit = () => {
    if (!formData.type || !formData.start_date || !formData.end_date) {
      alert('Mohon lengkapi semua field yang wajib diisi');
      return;
    }

    const submitData: LeaveRequestFormData = {
      type: formData.type as LeaveType,
      start_date: formData.start_date,
      end_date: formData.end_date,
      duration_type: formData.duration_type,
      reason: formData.reason,
      emergency_contact: formData.emergency_contact,
      emergency_phone: formData.emergency_phone,
      attachment: formData.attachment || undefined,
    };

    createMutation.mutate(submitData);
  };

  if (step === 1) {
    return <StepOne formData={formData} setFormData={setFormData} onBack={onBack} onNext={() => setStep(2)} />;
  }

  return (
    <StepTwo
      formData={formData}
      setFormData={setFormData}
      onBack={() => setStep(1)}
      onSubmit={handleSubmit}
      isSubmitting={createMutation.isPending}
    />
  );
}

// Step 1: Basic Information
function StepOne({
  formData,
  setFormData,
  onBack,
  onNext,
}: {
  formData: any;
  setFormData: (data: any) => void;
  onBack: () => void;
  onNext: () => void;
}) {
  const [showTypePicker, setShowTypePicker] = useState(false);
  const { user } = useAuthStore();

  const leaveTypes = [
    { value: 'annual', label: 'Cuti Tahunan', description: 'Cuti tahunan reguler' },
    { value: 'sick', label: 'Cuti Sakit', description: 'Izin sakit dengan surat dokter' },
    { value: 'maternity', label: 'Cuti Melahirkan', description: 'Cuti khusus melahirkan' },
    { value: 'unpaid', label: 'Cuti Tanpa Gaji', description: 'Izin tidak masuk tanpa gaji' },
  ];

  const selectedType = leaveTypes.find((t) => t.value === formData.type);

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file && file.size <= 5 * 1024 * 1024) { // 5MB max
      setFormData({ ...formData, attachment: file });
    } else {
      alert('Ukuran file maksimal 5 MB');
    }
  };

  const canProceed = formData.type && formData.reason && formData.emergency_contact && formData.emergency_phone;

  return (
    <div className="min-h-screen bg-background flex flex-col">
      {/* Header */}
      <div className="px-4 pt-3 pb-3 sticky top-0 z-20 bg-background/80 backdrop-blur-md border-b border-border/50">
        <div className="flex items-center gap-3">
          <button
            onClick={onBack}
            className="p-2 -ml-2 hover:bg-muted rounded-full transition-colors"
          >
            <ChevronLeft className="h-5 w-5" />
          </button>
          <div className="flex-1">
            <h1 className="text-base font-bold">Buat Pengajuan</h1>
            <p className="text-xs text-muted-foreground">Langkah 1 dari 2</p>
          </div>
          <div className="flex gap-1">
            <div className="h-2 w-8 rounded-full bg-primary" />
            <div className="h-2 w-8 rounded-full bg-muted" />
          </div>
        </div>
      </div>

      {/* Form Content */}
      <div className="flex-1 p-4 space-y-6 overflow-y-auto pb-24">
        {/* Jenis Cuti */}
        <div className="space-y-2">
          <label className="text-sm font-bold text-foreground">
            Jenis Cuti<span className="text-red-500">*</span>
          </label>
          <button
            type="button"
            onClick={() => setShowTypePicker(true)}
            className="w-full px-4 py-4 bg-card border border-border/50 rounded-2xl text-left flex items-center justify-between hover:bg-muted/50 transition-colors shadow-sm"
          >
            <div className="flex items-center gap-3">
              <div className="h-10 w-10 rounded-xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-400">
                <FileText className="h-5 w-5" />
              </div>
              <div>
                <p className={cn("text-sm font-semibold", !selectedType && "text-muted-foreground")}>
                  {selectedType ? selectedType.label : 'Pilih Jenis Cuti'}
                </p>
                {selectedType && <p className="text-xs text-muted-foreground">{selectedType.description}</p>}
              </div>
            </div>
            <ChevronRight className="h-4 w-4 text-muted-foreground rotate-90" />
          </button>
        </div>

        {/* Alasan Cuti */}
        <div className="space-y-2">
          <label className="text-sm font-bold text-foreground">
            Alasan Cuti<span className="text-red-500">*</span>
          </label>
          <textarea
            value={formData.reason}
            onChange={(e) => setFormData({ ...formData, reason: e.target.value })}
            placeholder="Jelaskan alasan pengajuan cuti anda..."
            rows={4}
            className="w-full px-4 py-3 bg-card border border-border/50 rounded-2xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-primary shadow-sm"
          />
        </div>

        {/* Alamat Cuti */}
        <div className="space-y-2">
          <label className="text-sm font-bold text-foreground">
            Alamat Selama Cuti<span className="text-red-500">*</span>
          </label>
          <div className="relative">
            <MapPin className="absolute left-4 top-3.5 h-4 w-4 text-muted-foreground" />
            <textarea
              value={formData.emergency_contact}
              onChange={(e) => setFormData({ ...formData, emergency_contact: e.target.value })}
              placeholder="Masukkan alamat lengkap..."
              rows={3}
              className="w-full pl-10 pr-4 py-3 bg-card border border-border/50 rounded-2xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-primary shadow-sm"
            />
          </div>
        </div>

        {/* Nomor Handphone */}
        <div className="space-y-2">
          <label className="text-sm font-bold text-foreground">
            Nomor Darurat<span className="text-red-500">*</span>
          </label>
          <div className="relative">
            <Phone className="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <input
              type="tel"
              value={formData.emergency_phone}
              onChange={(e) => setFormData({ ...formData, emergency_phone: e.target.value })}
              placeholder="08xxxxxxxxxx"
              className="w-full pl-10 pr-4 py-3.5 bg-card border border-border/50 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary shadow-sm"
            />
          </div>
        </div>

        {/* Dokumen Pendukung */}
        <div className="space-y-2">
          <label className="text-sm font-bold text-foreground">
            Dokumen Pendukung <span className="text-xs text-muted-foreground font-normal">(Opsional)</span>
          </label>
          <label className="block group cursor-pointer">
            <input
              type="file"
              accept="image/*,.pdf"
              onChange={handleFileChange}
              className="sr-only"
            />
            <div className="w-full px-4 py-6 bg-card border-2 border-border/50 border-dashed rounded-2xl text-center hover:bg-muted/50 transition-colors">
              <div className="h-10 w-10 rounded-full bg-muted flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                <Upload className="h-5 w-5 text-muted-foreground" />
              </div>
              <p className="text-sm font-medium text-foreground">
                {formData.attachment ? formData.attachment.name : 'Upload Dokumen'}
              </p>
              <p className="text-xs text-muted-foreground mt-1">
                Maksimal 5MB (PDF/JPG)
              </p>
            </div>
          </label>
        </div>
      </div>

      {/* Bottom Buttons */}
      <div className="fixed bottom-0 left-0 right-0 bg-background border-t border-border/50 p-4 flex gap-3 z-20">
        <Button
          variant="outline"
          onClick={onBack}
          className="flex-1 rounded-xl h-12"
        >
          Batalkan
        </Button>
        <Button
          onClick={onNext}
          disabled={!canProceed}
          className="flex-1 rounded-xl h-12"
        >
          Selanjutnya
        </Button>
      </div>

      {/* Type Picker Bottom Sheet */}
      {showTypePicker && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm" onClick={() => setShowTypePicker(false)}>
          <div
            className="absolute bottom-0 left-0 right-0 bg-background rounded-t-3xl p-6 shadow-2xl animate-in slide-in-from-bottom duration-300"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between mb-6">
              <h3 className="text-lg font-bold">Pilih Jenis Cuti</h3>
              <button
                onClick={() => setShowTypePicker(false)}
                className="p-2 hover:bg-muted rounded-full transition-colors bg-muted/50"
              >
                <X className="h-5 w-5" />
              </button>
            </div>
            <div className="space-y-3 max-h-[60vh] overflow-y-auto pb-6">
              {leaveTypes.map((type) => (
                <button
                  key={type.value}
                  onClick={() => {
                    setFormData({ ...formData, type: type.value });
                    setShowTypePicker(false);
                  }}
                  className={cn(
                    "w-full px-4 py-4 rounded-2xl text-left flex items-center gap-4 transition-all border",
                    formData.type === type.value
                      ? "bg-primary/5 border-primary shadow-sm"
                      : "bg-card border-border/50 hover:bg-muted/50"
                  )}
                >
                  <div className={cn(
                    "h-10 w-10 rounded-xl flex items-center justify-center shrink-0",
                    formData.type === type.value ? "bg-primary text-primary-foreground" : "bg-muted text-muted-foreground"
                  )}>
                    <Check className={cn("h-5 w-5", formData.type === type.value ? "opacity-100" : "opacity-0")} />
                  </div>
                  <div>
                    <p className="text-sm font-bold">{type.label}</p>
                    <p className="text-xs text-muted-foreground">{type.description}</p>
                  </div>
                </button>
              ))}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

// Step 2: Review & Confirmation
function StepTwo({
  formData,
  setFormData,
  onBack,
  onSubmit,
  isSubmitting,
}: {
  formData: any;
  setFormData: (data: any) => void;
  onBack: () => void;
  onSubmit: () => void;
  isSubmitting?: boolean;
}) {
  const { user } = useAuthStore();

  // Calculate total days
  const calculateDays = () => {
    if (!formData.start_date || !formData.end_date) return 0;
    const start = new Date(formData.start_date);
    const end = new Date(formData.end_date);
    const diffTime = Math.abs(end.getTime() - start.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
    return diffDays;
  };

  const formatDate = (dateStr: string) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: '2-digit',
    });
  };

  return (
    <div className="min-h-screen bg-background flex flex-col">
      {/* Header */}
      <div className="px-4 pt-3 pb-3 sticky top-0 z-20 bg-background/80 backdrop-blur-md border-b border-border/50">
        <div className="flex items-center gap-3">
          <button
            onClick={onBack}
            className="p-2 -ml-2 hover:bg-muted rounded-full transition-colors"
          >
            <ChevronLeft className="h-5 w-5" />
          </button>
          <div className="flex-1">
            <h1 className="text-base font-bold">Konfirmasi Pengajuan</h1>
            <p className="text-xs text-muted-foreground">Langkah 2 dari 2</p>
          </div>
          <div className="flex gap-1">
            <div className="h-2 w-8 rounded-full bg-primary/30" />
            <div className="h-2 w-8 rounded-full bg-primary" />
          </div>
        </div>
      </div>

      {/* Review Content */}
      <div className="flex-1 p-4 space-y-6 overflow-y-auto pb-24">
        {/* Summary Card */}
        <div className="bg-card border border-border/50 rounded-3xl p-5 shadow-sm space-y-4">
          <div className="flex items-center gap-4 pb-4 border-b border-border/50">
            <div className="h-12 w-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary font-bold text-lg">
              {user?.name ? user.name.charAt(0).toUpperCase() : 'U'}
            </div>
            <div>
              <p className="text-xs text-muted-foreground font-medium">Pemohon</p>
              <p className="font-bold text-foreground">{user?.name || 'N/A'}</p>
              <p className="text-xs text-muted-foreground">{user?.employee_id || 'N/A'}</p>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="bg-muted/30 p-3 rounded-2xl">
              <p className="text-xs text-muted-foreground mb-1">Jenis Cuti</p>
              <p className="text-sm font-bold text-foreground">
                {formData.type === 'annual' ? 'Cuti Tahunan' : formData.type === 'sick' ? 'Cuti Sakit' : formData.type === 'maternity' ? 'Melahirkan' : 'Lainnya'}
              </p>
            </div>
            <div className="bg-muted/30 p-3 rounded-2xl">
              <p className="text-xs text-muted-foreground mb-1">Total Durasi</p>
              <p className="text-sm font-bold text-foreground">{calculateDays()} Hari</p>
            </div>
          </div>
        </div>

        {/* Date Selection */}
        <div className="space-y-3">
          <h3 className="text-sm font-bold text-foreground px-1">Pilih Tanggal</h3>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <label className="text-xs font-medium text-muted-foreground">Mulai</label>
              <input
                type="date"
                value={formData.start_date}
                onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
                className="w-full px-3 py-3 bg-card border border-border/50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary shadow-sm"
              />
            </div>
            <div className="space-y-1.5">
              <label className="text-xs font-medium text-muted-foreground">Selesai</label>
              <input
                type="date"
                value={formData.end_date}
                onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
                min={formData.start_date}
                className="w-full px-3 py-3 bg-card border border-border/50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary shadow-sm"
              />
            </div>
          </div>
          {formData.start_date && formData.end_date && (
            <div className="bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 px-4 py-3 rounded-xl text-xs font-medium flex items-center gap-2">
              <Calendar className="h-4 w-4" />
              {formatDate(formData.start_date)} s/d {formatDate(formData.end_date)}
            </div>
          )}
        </div>

        {/* Details Review */}
        <div className="space-y-4">
          <h3 className="text-sm font-bold text-foreground px-1">Detail Pengajuan</h3>

          <div className="bg-card border border-border/50 rounded-2xl p-4 space-y-4">
            <div>
              <p className="text-xs text-muted-foreground mb-1">Alasan</p>
              <p className="text-sm text-foreground">{formData.reason}</p>
            </div>
            <div className="h-px bg-border/50" />
            <div>
              <p className="text-xs text-muted-foreground mb-1">Alamat Selama Cuti</p>
              <p className="text-sm text-foreground">{formData.emergency_contact}</p>
            </div>
            <div className="h-px bg-border/50" />
            <div>
              <p className="text-xs text-muted-foreground mb-1">Kontak Darurat</p>
              <p className="text-sm text-foreground">{formData.emergency_phone}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Bottom Buttons */}
      <div className="fixed bottom-0 left-0 right-0 bg-background border-t border-border/50 p-4 flex gap-3 z-20">
        <Button
          variant="outline"
          onClick={onBack}
          disabled={isSubmitting}
          className="flex-1 rounded-xl h-12"
        >
          Kembali
        </Button>
        <Button
          onClick={onSubmit}
          disabled={isSubmitting || !formData.start_date || !formData.end_date}
          className="flex-1 rounded-xl h-12"
        >
          {isSubmitting ? (
            <>
              <Loader2 className="h-4 w-4 mr-2 animate-spin" />
              Mengirim...
            </>
          ) : (
            'Kirim Pengajuan'
          )}
        </Button>
      </div>
    </div>
  );
}
