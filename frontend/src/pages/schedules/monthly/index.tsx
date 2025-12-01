import { useState } from 'react';
import { Link } from '@tanstack/react-router';
import {
  ArrowLeft,
  Calendar,
  Plus,
  Search,
  Filter,
  ChevronLeft,
  ChevronRight,
  Eye,
  Edit,
  Copy,
  Trash2,
  MoreVertical,
  Users,
  Clock,
  CalendarDays,
  Download,
  FileSpreadsheet,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { useNotificationStore } from '@/stores';

// Mock data
const mockMonthlySchedules = [
  {
    id: '1',
    name: 'Jadwal Januari 2025',
    month: 1,
    year: 2025,
    status: 'active',
    employeeCount: 45,
    createdAt: '2024-12-20',
    publishedAt: '2024-12-25',
  },
  {
    id: '2',
    name: 'Jadwal Februari 2025',
    month: 2,
    year: 2025,
    status: 'draft',
    employeeCount: 45,
    createdAt: '2025-01-15',
    publishedAt: null,
  },
  {
    id: '3',
    name: 'Jadwal Desember 2024',
    month: 12,
    year: 2024,
    status: 'archived',
    employeeCount: 42,
    createdAt: '2024-11-20',
    publishedAt: '2024-11-25',
  },
  {
    id: '4',
    name: 'Jadwal November 2024',
    month: 11,
    year: 2024,
    status: 'archived',
    employeeCount: 40,
    createdAt: '2024-10-20',
    publishedAt: '2024-10-25',
  },
];

const months = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const statusOptions = [
  { value: 'all', label: 'Semua Status' },
  { value: 'draft', label: 'Draft' },
  { value: 'active', label: 'Aktif' },
  { value: 'archived', label: 'Arsip' },
];

export default function MonthlyScheduleIndexPage() {
  const { success } = useNotificationStore();
  const [searchQuery, setSearchQuery] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  const [filterYear, setFilterYear] = useState('2025');
  const [deleteDialog, setDeleteDialog] = useState<{ open: boolean; schedule: typeof mockMonthlySchedules[0] | null }>({
    open: false,
    schedule: null,
  });

  // Generate year options
  const currentYear = new Date().getFullYear();
  const yearOptions = Array.from({ length: 5 }, (_, i) => currentYear - 2 + i);

  // Filter schedules
  const filteredSchedules = mockMonthlySchedules.filter((schedule) => {
    const matchesSearch = schedule.name.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesStatus = filterStatus === 'all' || schedule.status === filterStatus;
    const matchesYear = schedule.year.toString() === filterYear;
    return matchesSearch && matchesStatus && matchesYear;
  });

  // Stats
  const stats = {
    total: mockMonthlySchedules.filter(s => s.year.toString() === filterYear).length,
    active: mockMonthlySchedules.filter(s => s.status === 'active' && s.year.toString() === filterYear).length,
    draft: mockMonthlySchedules.filter(s => s.status === 'draft' && s.year.toString() === filterYear).length,
    archived: mockMonthlySchedules.filter(s => s.status === 'archived' && s.year.toString() === filterYear).length,
  };

  // Get status badge
  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'active':
        return <Badge className="bg-success/10 text-success border-success/20">Aktif</Badge>;
      case 'draft':
        return <Badge variant="secondary">Draft</Badge>;
      case 'archived':
        return <Badge variant="outline" className="text-muted-foreground">Arsip</Badge>;
      default:
        return <Badge variant="outline">{status}</Badge>;
    }
  };

  // Format date
  const formatDate = (dateStr: string | null) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    });
  };

  // Get month name
  const getMonthName = (month: number) => months[month - 1];

  // Handle delete
  const handleDelete = () => {
    if (deleteDialog.schedule) {
      success('Berhasil', `Jadwal ${deleteDialog.schedule.name} berhasil dihapus`);
      setDeleteDialog({ open: false, schedule: null });
    }
  };

  // Handle duplicate
  const handleDuplicate = (schedule: typeof mockMonthlySchedules[0]) => {
    success('Berhasil', `Jadwal ${schedule.name} berhasil diduplikasi`);
  };

  // Handle export
  const handleExport = () => {
    success('Berhasil', 'Jadwal berhasil diexport');
  };

  return (
    <div className="p-4 sm:p-6">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <Link
            to="/schedules"
            className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
          >
            <ArrowLeft className="h-4 w-4 mr-2" />
            Kembali ke jadwal
          </Link>
          <h1 className="text-2xl font-bold text-foreground">Jadwal Bulanan</h1>
          <p className="text-sm text-muted-foreground">
            Kelola jadwal kerja bulanan karyawan
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Button variant="outline" onClick={handleExport}>
            <Download className="h-4 w-4 mr-2" />
            Export
          </Button>
          <Link to="/schedules/monthly/create">
            <Button>
              <Plus className="h-4 w-4 mr-2" />
              Buat Jadwal
            </Button>
          </Link>
        </div>
      </div>

      {/* Year Navigation */}
      <Card className="mb-6">
        <CardContent className="p-4">
          <div className="flex items-center justify-between">
            <Button
              variant="outline"
              size="icon"
              onClick={() => setFilterYear((parseInt(filterYear) - 1).toString())}
            >
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <div className="flex items-center gap-2">
              <Calendar className="h-5 w-5 text-primary" />
              <span className="text-xl font-bold">{filterYear}</span>
            </div>
            <Button
              variant="outline"
              size="icon"
              onClick={() => setFilterYear((parseInt(filterYear) + 1).toString())}
            >
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <CalendarDays className="h-8 w-8 text-primary/30" />
              <div>
                <p className="text-2xl font-bold">{stats.total}</p>
                <p className="text-xs text-muted-foreground">Total Jadwal</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <Clock className="h-8 w-8 text-success/30" />
              <div>
                <p className="text-2xl font-bold">{stats.active}</p>
                <p className="text-xs text-muted-foreground">Jadwal Aktif</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <FileSpreadsheet className="h-8 w-8 text-warning/30" />
              <div>
                <p className="text-2xl font-bold">{stats.draft}</p>
                <p className="text-xs text-muted-foreground">Draft</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <Users className="h-8 w-8 text-muted-foreground/30" />
              <div>
                <p className="text-2xl font-bold">{stats.archived}</p>
                <p className="text-xs text-muted-foreground">Diarsipkan</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card className="mb-6">
        <CardContent className="p-4">
          <div className="flex flex-col md:flex-row gap-4">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Cari jadwal..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10"
              />
            </div>
            <Select value={filterStatus} onValueChange={setFilterStatus}>
              <SelectTrigger className="w-full md:w-[180px]">
                <Filter className="h-4 w-4 mr-2" />
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {statusOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Select value={filterYear} onValueChange={setFilterYear}>
              <SelectTrigger className="w-full md:w-[120px]">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {yearOptions.map((year) => (
                  <SelectItem key={year} value={year.toString()}>
                    {year}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Table */}
      <Card>
        <CardHeader>
          <CardTitle>Daftar Jadwal Bulanan</CardTitle>
          <CardDescription>
            {filteredSchedules.length} jadwal ditemukan
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="border rounded-lg">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nama Jadwal</TableHead>
                  <TableHead>Bulan</TableHead>
                  <TableHead>Karyawan</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Dibuat</TableHead>
                  <TableHead>Dipublikasi</TableHead>
                  <TableHead className="w-20">Aksi</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredSchedules.map((schedule) => (
                  <TableRow key={schedule.id}>
                    <TableCell>
                      <div className="font-medium">{schedule.name}</div>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Calendar className="h-4 w-4 text-muted-foreground" />
                        {getMonthName(schedule.month)} {schedule.year}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Users className="h-4 w-4 text-muted-foreground" />
                        {schedule.employeeCount}
                      </div>
                    </TableCell>
                    <TableCell>{getStatusBadge(schedule.status)}</TableCell>
                    <TableCell>{formatDate(schedule.createdAt)}</TableCell>
                    <TableCell>{formatDate(schedule.publishedAt)}</TableCell>
                    <TableCell>
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="icon">
                            <MoreVertical className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem>
                            <Eye className="h-4 w-4 mr-2" />
                            Lihat Detail
                          </DropdownMenuItem>
                          <DropdownMenuItem>
                            <Edit className="h-4 w-4 mr-2" />
                            Edit
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => handleDuplicate(schedule)}>
                            <Copy className="h-4 w-4 mr-2" />
                            Duplikasi
                          </DropdownMenuItem>
                          <DropdownMenuSeparator />
                          <DropdownMenuItem
                            className="text-destructive"
                            onClick={() => setDeleteDialog({ open: true, schedule })}
                          >
                            <Trash2 className="h-4 w-4 mr-2" />
                            Hapus
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))}
                {filteredSchedules.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                      <Calendar className="h-8 w-8 mx-auto mb-2" />
                      Tidak ada jadwal ditemukan
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      {/* Delete Dialog */}
      <Dialog open={deleteDialog.open} onOpenChange={(open) => setDeleteDialog({ open, schedule: null })}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Hapus Jadwal</DialogTitle>
            <DialogDescription>
              Apakah Anda yakin ingin menghapus jadwal "{deleteDialog.schedule?.name}"?
              Tindakan ini tidak dapat dibatalkan.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteDialog({ open: false, schedule: null })}>
              Batal
            </Button>
            <Button variant="destructive" onClick={handleDelete}>
              Hapus
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
