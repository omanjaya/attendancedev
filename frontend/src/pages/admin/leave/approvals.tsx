import { useState } from 'react';
import { Link } from '@tanstack/react-router';
import { useQuery } from '@tanstack/react-query';
import {
  Search,
  Filter,
  CheckCircle,
  XCircle,
  AlertCircle,
  Calendar,
  Eye,
  Loader2,
} from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Textarea } from '@/components/ui/textarea';
import { getPendingApprovals } from '@/lib/api/leave';
import { useApproveLeaveRequest, useRejectLeaveRequest } from '@/hooks/use-leave';
import { leaveTypeLabels, leaveTypeColors } from '@/types/leave';

export default function LeaveApprovalsPage() {
  const [searchQuery, setSearchQuery] = useState('');
  const [typeFilter, setTypeFilter] = useState('all');
  const [rejectReason, setRejectReason] = useState('');
  const [selectedId, setSelectedId] = useState<string | null>(null);

  const { data: approvals = [], isLoading } = useQuery({
    queryKey: ['leave-approvals'],
    queryFn: getPendingApprovals,
  });

  const approveMutation = useApproveLeaveRequest();
  const rejectMutation = useRejectLeaveRequest();

  const handleApprove = (id: string) => {
    approveMutation.mutate({ id });
  };

  const handleReject = () => {
    if (selectedId && rejectReason) {
      rejectMutation.mutate({ id: selectedId, reason: rejectReason });
      setRejectReason('');
      setSelectedId(null);
    }
  };

  const filteredApprovals = approvals.filter((approval) => {
    // Handle both nested employee object and flat properties if API varies
    const employeeName = (approval as any).employee?.full_name || approval.employee_name || '';
    const matchesSearch = employeeName.toLowerCase().includes(searchQuery.toLowerCase());

    // Map API type to filter value if needed, or just compare
    const matchesType = typeFilter === 'all' || (approval.leave_type?.code === typeFilter || approval.type === typeFilter);
    return matchesSearch && matchesType;
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  return (
    <div className="p-4 sm:p-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Persetujuan Cuti</h1>
          <p className="text-sm text-muted-foreground">
            Kelola pengajuan cuti yang membutuhkan persetujuan
          </p>
        </div>
        <Link to="/admin/leave">
          <Button variant="outline">
            <Calendar className="h-4 w-4 mr-2" />
            Semua Cuti
          </Button>
        </Link>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <Card className="bg-warning/10 border-warning/20">
          <CardContent className="p-4 text-center">
            <AlertCircle className="h-8 w-8 mx-auto mb-2 text-warning" />
            <p className="text-2xl font-bold">{approvals.length}</p>
            <p className="text-xs text-muted-foreground">Menunggu</p>
          </CardContent>
        </Card>
        {/* Other stats could be fetched from statistics endpoint if needed, keeping static placeholders for now or removing */}
      </div>

      {/* Filters */}
      <Card className="mb-6">
        <CardContent className="p-4">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Cari nama karyawan..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10"
              />
            </div>
            <Select value={typeFilter} onValueChange={setTypeFilter}>
              <SelectTrigger className="w-full sm:w-[180px]">
                <Filter className="h-4 w-4 mr-2" />
                <SelectValue placeholder="Jenis Cuti" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Semua Jenis</SelectItem>
                <SelectItem value="annual">Cuti Tahunan</SelectItem>
                <SelectItem value="sick">Cuti Sakit</SelectItem>
                <SelectItem value="personal">Cuti Personal</SelectItem>
                {/* Add more types as needed */}
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Approval List */}
      <div className="space-y-4">
        {filteredApprovals.map((approval) => {
          const employeeName = (approval as any).employee?.full_name || approval.employee_name || 'Unknown';
          const employeePosition = (approval as any).employee?.position || 'Employee';
          const employeeDept = (approval as any).employee?.department || '-';
          const typeCode = approval.leave_type?.code || approval.type || 'other';
          const typeLabel = approval.leave_type?.name || leaveTypeLabels[typeCode as keyof typeof leaveTypeLabels] || typeCode;

          return (
            <Card key={approval.id} className="hover:shadow-md transition-shadow">
              <CardContent className="p-4 sm:p-6">
                <div className="flex flex-col lg:flex-row lg:items-center gap-4">
                  {/* Employee Info */}
                  <div className="flex items-center gap-4 flex-1">
                    <Avatar className="h-12 w-12">
                      <AvatarFallback className="bg-primary/10 text-primary">
                        {employeeName.split(' ').map((n: string) => n[0]).join('').substring(0, 2)}
                      </AvatarFallback>
                    </Avatar>
                    <div>
                      <h3 className="font-semibold">{employeeName}</h3>
                      <p className="text-sm text-muted-foreground">
                        {employeePosition} - {employeeDept}
                      </p>
                    </div>
                  </div>

                  {/* Leave Info */}
                  <div className="flex flex-wrap items-center gap-4 flex-1">
                    <Badge className={leaveTypeColors[typeCode as keyof typeof leaveTypeColors] || 'bg-gray-500'}>
                      {typeLabel}
                    </Badge>
                    <div className="text-sm">
                      <p className="font-medium">
                        {new Date(approval.start_date).toLocaleDateString('id-ID', {
                          day: 'numeric',
                          month: 'short',
                        })}
                        {' - '}
                        {new Date(approval.end_date).toLocaleDateString('id-ID', {
                          day: 'numeric',
                          month: 'short',
                        })}
                      </p>
                      <p className="text-muted-foreground">{approval.days_requested} hari</p>
                    </div>
                  </div>

                  {/* Reason */}
                  <div className="flex-1">
                    <p className="text-sm text-muted-foreground truncate">
                      {approval.reason}
                    </p>
                  </div>

                  {/* Actions */}
                  <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" asChild>
                      <a href={`/admin/leave/${approval.id}`}>
                        <Eye className="h-4 w-4 mr-1" />
                        Detail
                      </a>
                    </Button>

                    <AlertDialog>
                      <AlertDialogTrigger asChild>
                        <Button
                          variant="outline"
                          size="sm"
                          className="text-destructive"
                          onClick={() => setSelectedId(approval.id)}
                        >
                          <XCircle className="h-4 w-4" />
                        </Button>
                      </AlertDialogTrigger>
                      <AlertDialogContent>
                        <AlertDialogHeader>
                          <AlertDialogTitle>Tolak Pengajuan Cuti?</AlertDialogTitle>
                          <AlertDialogDescription>
                            Anda akan menolak pengajuan cuti dari {employeeName}.
                            Mohon berikan alasan penolakan.
                          </AlertDialogDescription>
                        </AlertDialogHeader>
                        <div className="py-4">
                          <Textarea
                            placeholder="Alasan penolakan..."
                            value={rejectReason}
                            onChange={(e) => setRejectReason(e.target.value)}
                          />
                        </div>
                        <AlertDialogFooter>
                          <AlertDialogCancel onClick={() => {
                            setRejectReason('');
                            setSelectedId(null);
                          }}>Batal</AlertDialogCancel>
                          <AlertDialogAction
                            className="bg-destructive text-destructive-foreground"
                            onClick={handleReject}
                            disabled={!rejectReason.trim() || rejectMutation.isPending}
                          >
                            {rejectMutation.isPending ? 'Memproses...' : 'Tolak'}
                          </AlertDialogAction>
                        </AlertDialogFooter>
                      </AlertDialogContent>
                    </AlertDialog>

                    <AlertDialog>
                      <AlertDialogTrigger asChild>
                        <Button size="sm">
                          <CheckCircle className="h-4 w-4" />
                        </Button>
                      </AlertDialogTrigger>
                      <AlertDialogContent>
                        <AlertDialogHeader>
                          <AlertDialogTitle>Setujui Pengajuan Cuti?</AlertDialogTitle>
                          <AlertDialogDescription>
                            Anda akan menyetujui pengajuan cuti dari {employeeName}
                            selama {approval.days_requested} hari.
                          </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                          <AlertDialogCancel>Batal</AlertDialogCancel>
                          <AlertDialogAction
                            onClick={() => handleApprove(approval.id)}
                            disabled={approveMutation.isPending}
                          >
                            {approveMutation.isPending ? 'Memproses...' : 'Setujui'}
                          </AlertDialogAction>
                        </AlertDialogFooter>
                      </AlertDialogContent>
                    </AlertDialog>
                  </div>
                </div>
              </CardContent>
            </Card>
          );
        })}

        {filteredApprovals.length === 0 && (
          <Card>
            <CardContent className="p-12 text-center">
              <CheckCircle className="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
              <h3 className="text-lg font-semibold mb-2">Tidak Ada Pengajuan</h3>
              <p className="text-sm text-muted-foreground">
                Semua pengajuan cuti sudah diproses atau tidak ditemukan
              </p>
            </CardContent>
          </Card>
        )}
      </div>
    </div>
  );
}
