import { useState } from 'react';
import {
  FileText,
  Download,
  Calendar,
  Users,
  Filter,
  BarChart3,
  PieChart,
  Table,
  Printer,
  Settings,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { useNotificationStore } from '@/stores';

const reportTypes = [
  { id: 'attendance', label: 'Laporan Kehadiran', icon: Calendar },
  { id: 'payroll', label: 'Laporan Payroll', icon: FileText },
  { id: 'leave', label: 'Laporan Cuti', icon: Users },
  { id: 'performance', label: 'Laporan Performa', icon: BarChart3 },
];

const departments = [
  { id: 'all', name: 'Semua Departemen' },
  { id: '1', name: 'IT & Development' },
  { id: '2', name: 'Human Resources' },
  { id: '3', name: 'Finance & Accounting' },
  { id: '4', name: 'Marketing' },
  { id: '5', name: 'Operations' },
];

const columns = {
  attendance: [
    { id: 'employee_name', label: 'Nama Karyawan', default: true },
    { id: 'department', label: 'Departemen', default: true },
    { id: 'date', label: 'Tanggal', default: true },
    { id: 'check_in', label: 'Jam Masuk', default: true },
    { id: 'check_out', label: 'Jam Keluar', default: true },
    { id: 'status', label: 'Status', default: true },
    { id: 'late_minutes', label: 'Keterlambatan', default: false },
    { id: 'overtime_hours', label: 'Lembur', default: false },
  ],
  payroll: [
    { id: 'employee_name', label: 'Nama Karyawan', default: true },
    { id: 'department', label: 'Departemen', default: true },
    { id: 'basic_salary', label: 'Gaji Pokok', default: true },
    { id: 'allowances', label: 'Tunjangan', default: true },
    { id: 'deductions', label: 'Potongan', default: true },
    { id: 'net_salary', label: 'Gaji Bersih', default: true },
  ],
  leave: [
    { id: 'employee_name', label: 'Nama Karyawan', default: true },
    { id: 'department', label: 'Departemen', default: true },
    { id: 'leave_type', label: 'Jenis Cuti', default: true },
    { id: 'start_date', label: 'Tanggal Mulai', default: true },
    { id: 'end_date', label: 'Tanggal Selesai', default: true },
    { id: 'days', label: 'Jumlah Hari', default: true },
    { id: 'status', label: 'Status', default: true },
  ],
  performance: [
    { id: 'employee_name', label: 'Nama Karyawan', default: true },
    { id: 'department', label: 'Departemen', default: true },
    { id: 'attendance_rate', label: 'Tingkat Kehadiran', default: true },
    { id: 'late_count', label: 'Jumlah Terlambat', default: true },
    { id: 'leave_count', label: 'Jumlah Cuti', default: true },
    { id: 'overtime_hours', label: 'Total Lembur', default: true },
  ],
};

export default function ReportBuilderPage() {
  const { success } = useNotificationStore();
  const [reportType, setReportType] = useState('attendance');
  const [dateRange, setDateRange] = useState({ start: '', end: '' });
  const [department, setDepartment] = useState('all');
  const [selectedColumns, setSelectedColumns] = useState<string[]>(
    columns.attendance.filter(c => c.default).map(c => c.id)
  );
  const [outputFormat, setOutputFormat] = useState('pdf');

  const currentColumns = columns[reportType as keyof typeof columns] || columns.attendance;

  const handleReportTypeChange = (type: string) => {
    setReportType(type);
    const newColumns = columns[type as keyof typeof columns] || columns.attendance;
    setSelectedColumns(newColumns.filter(c => c.default).map(c => c.id));
  };

  const toggleColumn = (columnId: string) => {
    setSelectedColumns(prev =>
      prev.includes(columnId)
        ? prev.filter(c => c !== columnId)
        : [...prev, columnId]
    );
  };

  const handleGenerate = () => {
    success('Laporan Dibuat', 'Laporan sedang diproses dan akan diunduh');
  };

  return (
    <div className="p-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Report Builder</h1>
          <p className="text-sm text-muted-foreground">
            Buat laporan kustom sesuai kebutuhan Anda
          </p>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Configuration */}
        <div className="lg:col-span-2 space-y-6">
          {/* Report Type */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <FileText className="h-5 w-5 text-primary" />
                Jenis Laporan
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 gap-3">
                {reportTypes.map((type) => (
                  <button
                    key={type.id}
                    onClick={() => handleReportTypeChange(type.id)}
                    className={`
                      flex items-center gap-3 p-4 rounded-lg border transition-colors
                      ${reportType === type.id
                        ? 'bg-primary/10 border-primary'
                        : 'bg-background hover:bg-muted'
                      }
                    `}
                  >
                    <type.icon className={`h-5 w-5 ${reportType === type.id ? 'text-primary' : 'text-muted-foreground'}`} />
                    <span className="font-medium">{type.label}</span>
                  </button>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* Filters */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Filter className="h-5 w-5 text-primary" />
                Filter Data
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <label className="text-sm font-medium">Tanggal Mulai</label>
                  <Input
                    type="date"
                    value={dateRange.start}
                    onChange={(e) => setDateRange({ ...dateRange, start: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium">Tanggal Selesai</label>
                  <Input
                    type="date"
                    value={dateRange.end}
                    onChange={(e) => setDateRange({ ...dateRange, end: e.target.value })}
                  />
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium">Departemen</label>
                <Select value={department} onValueChange={setDepartment}>
                  <SelectTrigger>
                    <SelectValue placeholder="Pilih departemen" />
                  </SelectTrigger>
                  <SelectContent>
                    {departments.map((dept) => (
                      <SelectItem key={dept.id} value={dept.id}>
                        {dept.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </CardContent>
          </Card>

          {/* Columns */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Table className="h-5 w-5 text-primary" />
                Kolom Data
              </CardTitle>
              <CardDescription>
                Pilih kolom yang ingin ditampilkan dalam laporan
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                {currentColumns.map((column) => (
                  <label
                    key={column.id}
                    className={`
                      flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors
                      ${selectedColumns.includes(column.id)
                        ? 'bg-primary/10 border-primary'
                        : 'bg-background hover:bg-muted'
                      }
                    `}
                  >
                    <Checkbox
                      checked={selectedColumns.includes(column.id)}
                      onCheckedChange={() => toggleColumn(column.id)}
                    />
                    <span className="text-sm">{column.label}</span>
                  </label>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Preview & Actions */}
        <div className="space-y-6">
          {/* Output Format */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Settings className="h-5 w-5 text-primary" />
                Format Output
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <button
                onClick={() => setOutputFormat('pdf')}
                className={`
                  w-full flex items-center gap-3 p-3 rounded-lg border transition-colors
                  ${outputFormat === 'pdf' ? 'bg-primary/10 border-primary' : 'bg-background hover:bg-muted'}
                `}
              >
                <FileText className="h-5 w-5 text-red-500" />
                <div className="text-left">
                  <p className="font-medium">PDF</p>
                  <p className="text-xs text-muted-foreground">Dokumen siap cetak</p>
                </div>
              </button>
              <button
                onClick={() => setOutputFormat('excel')}
                className={`
                  w-full flex items-center gap-3 p-3 rounded-lg border transition-colors
                  ${outputFormat === 'excel' ? 'bg-primary/10 border-primary' : 'bg-background hover:bg-muted'}
                `}
              >
                <Table className="h-5 w-5 text-green-500" />
                <div className="text-left">
                  <p className="font-medium">Excel</p>
                  <p className="text-xs text-muted-foreground">Spreadsheet yang dapat diedit</p>
                </div>
              </button>
              <button
                onClick={() => setOutputFormat('csv')}
                className={`
                  w-full flex items-center gap-3 p-3 rounded-lg border transition-colors
                  ${outputFormat === 'csv' ? 'bg-primary/10 border-primary' : 'bg-background hover:bg-muted'}
                `}
              >
                <FileText className="h-5 w-5 text-blue-500" />
                <div className="text-left">
                  <p className="font-medium">CSV</p>
                  <p className="text-xs text-muted-foreground">Data mentah untuk import</p>
                </div>
              </button>
            </CardContent>
          </Card>

          {/* Summary */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Ringkasan</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Jenis Laporan</span>
                <Badge variant="outline">
                  {reportTypes.find(t => t.id === reportType)?.label}
                </Badge>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Kolom Dipilih</span>
                <span className="font-medium">{selectedColumns.length} kolom</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Format</span>
                <span className="font-medium uppercase">{outputFormat}</span>
              </div>

              <Separator />

              <div className="space-y-2">
                <Button className="w-full" onClick={handleGenerate}>
                  <Download className="h-4 w-4 mr-2" />
                  Generate Laporan
                </Button>
                <Button variant="outline" className="w-full">
                  <Printer className="h-4 w-4 mr-2" />
                  Preview
                </Button>
              </div>
            </CardContent>
          </Card>

          {/* Quick Templates */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Template Cepat</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              <Button variant="outline" size="sm" className="w-full justify-start">
                <BarChart3 className="h-4 w-4 mr-2" />
                Laporan Bulanan
              </Button>
              <Button variant="outline" size="sm" className="w-full justify-start">
                <PieChart className="h-4 w-4 mr-2" />
                Ringkasan Departemen
              </Button>
              <Button variant="outline" size="sm" className="w-full justify-start">
                <Users className="h-4 w-4 mr-2" />
                Rekap Karyawan
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
