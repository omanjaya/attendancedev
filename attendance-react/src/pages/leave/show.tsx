import { Link } from '@tanstack/react-router';
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
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';

// Mock leave request data
const mockLeaveRequest = {
  id: 1,
  employee: {
    name: 'Ahmad Fauzi',
    position: 'Senior Developer',
    department: 'IT & Development',
    avatar: null,
  },
  type: 'annual',
  type_label: 'Cuti Tahunan',
  start_date: '2024-12-02',
  end_date: '2024-12-06',
  days: 5,
  reason: 'Liburan keluarga ke Bali untuk merayakan ulang tahun pernikahan. Sudah direncanakan sejak 3 bulan lalu.',
  status: 'pending',
  created_at: '2024-11-25T10:30:00',
  approver: null,
  approved_at: null,
  attachment: 'dokumen_pendukung.pdf',
};

const timeline = [
  {
    id: 1,
    action: 'Pengajuan dibuat',
    user: 'Ahmad Fauzi',
    date: '2024-11-25T10:30:00',
    type: 'created',
  },
  {
    id: 2,
    action: 'Menunggu persetujuan HR',
    user: 'System',
    date: '2024-11-25T10:30:00',
    type: 'pending',
  },
];

const leaveTypeColors: Record<string, string> = {
  annual: 'bg-blue-100 text-blue-700',
  sick: 'bg-red-100 text-red-700',
  personal: 'bg-purple-100 text-purple-700',
  maternity: 'bg-pink-100 text-pink-700',
  unpaid: 'bg-gray-100 text-gray-700',
};

const statusConfig = {
  pending: { label: 'Menunggu', color: 'bg-warning/10 text-warning', icon: AlertCircle },
  approved: { label: 'Disetujui', color: 'bg-success/10 text-success', icon: CheckCircle },
  rejected: { label: 'Ditolak', color: 'bg-destructive/10 text-destructive', icon: XCircle },
};

export default function LeaveShowPage() {
  const leave = mockLeaveRequest;
  const status = statusConfig[leave.status as keyof typeof statusConfig];

  return (
    <div className="p-6 max-w-4xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/leave"
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
              <Button variant="outline" className="text-destructive">
                <XCircle className="h-4 w-4 mr-2" />
                Tolak
              </Button>
              <Button>
                <CheckCircle className="h-4 w-4 mr-2" />
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
                    {leave.employee.name.split(' ').map(n => n[0]).join('')}
                  </AvatarFallback>
                </Avatar>
                <div>
                  <h3 className="text-lg font-semibold">{leave.employee.name}</h3>
                  <p className="text-sm text-muted-foreground">{leave.employee.position}</p>
                  <p className="text-sm text-muted-foreground">{leave.employee.department}</p>
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
                  <Badge className={leaveTypeColors[leave.type]}>{leave.type_label}</Badge>
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
                  <p className="text-2xl font-bold text-primary">{leave.days}</p>
                </div>
              </div>

              <Separator />

              <div>
                <h4 className="text-sm font-medium mb-2">Alasan Pengajuan</h4>
                <p className="text-sm text-muted-foreground bg-muted p-3 rounded-lg">
                  {leave.reason}
                </p>
              </div>

              {leave.attachment && (
                <div>
                  <h4 className="text-sm font-medium mb-2">Lampiran</h4>
                  <Button variant="outline" size="sm">
                    <FileText className="h-4 w-4 mr-2" />
                    {leave.attachment}
                  </Button>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Add Comment */}
          {leave.status === 'pending' && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg">
                  <MessageSquare className="h-5 w-5 text-primary" />
                  Tambah Komentar
                </CardTitle>
              </CardHeader>
              <CardContent>
                <Textarea
                  placeholder="Tambahkan catatan atau alasan penolakan..."
                  className="mb-3"
                />
                <Button variant="outline" size="sm">
                  Kirim Komentar
                </Button>
              </CardContent>
            </Card>
          )}
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Status Card */}
          <Card className={`border-2 ${leave.status === 'approved' ? 'border-success' : leave.status === 'rejected' ? 'border-destructive' : 'border-warning'}`}>
            <CardContent className="p-6 text-center">
              <div className={`inline-flex items-center justify-center w-16 h-16 rounded-full mb-4 ${status.color}`}>
                <status.icon className="h-8 w-8" />
              </div>
              <h3 className="text-lg font-semibold">{status.label}</h3>
              <p className="text-sm text-muted-foreground mt-1">
                {leave.status === 'pending'
                  ? 'Menunggu persetujuan atasan'
                  : leave.status === 'approved'
                  ? `Disetujui oleh ${leave.approver}`
                  : `Ditolak oleh ${leave.approver}`}
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
                      <div className={`w-3 h-3 rounded-full ${
                        item.type === 'created' ? 'bg-primary' : 'bg-warning'
                      }`} />
                      {index < timeline.length - 1 && (
                        <div className="w-px h-full bg-border mt-1" />
                      )}
                    </div>
                    <div className="flex-1 pb-4">
                      <p className="text-sm font-medium">{item.action}</p>
                      <p className="text-xs text-muted-foreground">{item.user}</p>
                      <p className="text-xs text-muted-foreground">
                        {new Date(item.date).toLocaleString('id-ID')}
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
                  <span className="font-semibold">7 hari</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm">Cuti Sakit</span>
                  <span className="font-semibold">12 hari</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm">Cuti Personal</span>
                  <span className="font-semibold">3 hari</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
