import { useState } from 'react';
import { Link } from '@tanstack/react-router';
import {
  Search,
  Filter,
  CheckCircle,
  XCircle,
  AlertCircle,
  Calendar,
  Eye,
  Clock,
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

// Mock pending approvals
const mockApprovals = [
  {
    id: 1,
    employee: { name: 'Ahmad Fauzi', position: 'Senior Developer', department: 'IT' },
    type: 'annual',
    type_label: 'Cuti Tahunan',
    start_date: '2024-12-02',
    end_date: '2024-12-06',
    days: 5,
    reason: 'Liburan keluarga ke Bali',
    status: 'pending',
    created_at: '2024-11-25',
  },
  {
    id: 2,
    employee: { name: 'Siti Aminah', position: 'HR Manager', department: 'HR' },
    type: 'sick',
    type_label: 'Cuti Sakit',
    start_date: '2024-11-28',
    end_date: '2024-11-29',
    days: 2,
    reason: 'Demam dan flu',
    status: 'pending',
    created_at: '2024-11-27',
  },
  {
    id: 3,
    employee: { name: 'Budi Santoso', position: 'Finance Staff', department: 'Finance' },
    type: 'personal',
    type_label: 'Cuti Personal',
    start_date: '2024-12-10',
    end_date: '2024-12-10',
    days: 1,
    reason: 'Urusan keluarga',
    status: 'pending',
    created_at: '2024-11-26',
  },
];

const leaveTypeColors: Record<string, string> = {
  annual: 'bg-primary/10 text-primary',
  sick: 'bg-destructive/10 text-destructive',
  personal: 'bg-chart-5/10 text-chart-5',
};

export default function LeaveApprovalsPage() {
  const [searchQuery, setSearchQuery] = useState('');
  const [typeFilter, setTypeFilter] = useState('all');

  const filteredApprovals = mockApprovals.filter(approval => {
    const matchesSearch = approval.employee.name.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesType = typeFilter === 'all' || approval.type === typeFilter;
    return matchesSearch && matchesType;
  });

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
        <Link to="/leave">
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
            <p className="text-2xl font-bold">{mockApprovals.length}</p>
            <p className="text-xs text-muted-foreground">Menunggu</p>
          </CardContent>
        </Card>
        <Card className="bg-success/10 border-success/20">
          <CardContent className="p-4 text-center">
            <CheckCircle className="h-8 w-8 mx-auto mb-2 text-success" />
            <p className="text-2xl font-bold">12</p>
            <p className="text-xs text-muted-foreground">Disetujui</p>
          </CardContent>
        </Card>
        <Card className="bg-destructive/10 border-destructive/20">
          <CardContent className="p-4 text-center">
            <XCircle className="h-8 w-8 mx-auto mb-2 text-destructive" />
            <p className="text-2xl font-bold">2</p>
            <p className="text-xs text-muted-foreground">Ditolak</p>
          </CardContent>
        </Card>
        <Card className="bg-primary/10 border-primary/20">
          <CardContent className="p-4 text-center">
            <Clock className="h-8 w-8 mx-auto mb-2 text-primary" />
            <p className="text-2xl font-bold">24h</p>
            <p className="text-xs text-muted-foreground">Rata-rata Respon</p>
          </CardContent>
        </Card>
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
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Approval List */}
      <div className="space-y-4">
        {filteredApprovals.map((approval) => (
          <Card key={approval.id} className="hover:shadow-md transition-shadow">
            <CardContent className="p-4 sm:p-6">
              <div className="flex flex-col lg:flex-row lg:items-center gap-4">
                {/* Employee Info */}
                <div className="flex items-center gap-4 flex-1">
                  <Avatar className="h-12 w-12">
                    <AvatarFallback className="bg-primary/10 text-primary">
                      {approval.employee.name.split(' ').map(n => n[0]).join('')}
                    </AvatarFallback>
                  </Avatar>
                  <div>
                    <h3 className="font-semibold">{approval.employee.name}</h3>
                    <p className="text-sm text-muted-foreground">
                      {approval.employee.position} - {approval.employee.department}
                    </p>
                  </div>
                </div>

                {/* Leave Info */}
                <div className="flex flex-wrap items-center gap-4 flex-1">
                  <Badge className={leaveTypeColors[approval.type]}>
                    {approval.type_label}
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
                    <p className="text-muted-foreground">{approval.days} hari</p>
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
                    <a href={`/leave/${approval.id}`}>
                      <Eye className="h-4 w-4 mr-1" />
                      Detail
                    </a>
                  </Button>

                  <AlertDialog>
                    <AlertDialogTrigger asChild>
                      <Button variant="outline" size="sm" className="text-destructive">
                        <XCircle className="h-4 w-4" />
                      </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                      <AlertDialogHeader>
                        <AlertDialogTitle>Tolak Pengajuan Cuti?</AlertDialogTitle>
                        <AlertDialogDescription>
                          Anda akan menolak pengajuan cuti dari {approval.employee.name}.
                        </AlertDialogDescription>
                      </AlertDialogHeader>
                      <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction className="bg-destructive text-destructive-foreground">
                          Tolak
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
                          Anda akan menyetujui pengajuan cuti dari {approval.employee.name}
                          selama {approval.days} hari.
                        </AlertDialogDescription>
                      </AlertDialogHeader>
                      <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction>Setujui</AlertDialogAction>
                      </AlertDialogFooter>
                    </AlertDialogContent>
                  </AlertDialog>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}

        {filteredApprovals.length === 0 && (
          <Card>
            <CardContent className="p-12 text-center">
              <CheckCircle className="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
              <h3 className="text-lg font-semibold mb-2">Tidak Ada Pengajuan</h3>
              <p className="text-sm text-muted-foreground">
                Semua pengajuan cuti sudah diproses
              </p>
            </CardContent>
          </Card>
        )}
      </div>
    </div>
  );
}
