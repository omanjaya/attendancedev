import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
    CalendarDays,
    Search,
    Loader2,
    Users,
    TrendingUp,
    AlertCircle,
    FileText,
    FileSpreadsheet,
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { toast } from 'sonner';
import { getMonthlyRecap } from '@/lib/api/reports';
import type { MonthlyRecapEmployee } from '@/types/reports';
// jspdf imports are done dynamically in handleExportPDF

const MONTHS = [
    { value: 1, label: 'Januari' },
    { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },
    { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },
    { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },
    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },
    { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },
    { value: 12, label: 'Desember' },
];

const currentYear = new Date().getFullYear();
const YEARS = Array.from({ length: 5 }, (_, i) => currentYear - i);

export function MonthlyRecapTab() {
    const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1);
    const [selectedYear, setSelectedYear] = useState(currentYear);
    const [searchQuery, setSearchQuery] = useState('');

    // Fetch monthly recap data
    const { data: recapData, isLoading, error, refetch } = useQuery({
        queryKey: ['reports', 'monthly-recap', selectedMonth, selectedYear],
        queryFn: () => getMonthlyRecap({ month: selectedMonth, year: selectedYear }),
    });

    // Filter data by search query
    const filteredData = recapData?.data?.filter((employee: MonthlyRecapEmployee) =>
        employee.employee_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        employee.employee_code?.toLowerCase().includes(searchQuery.toLowerCase()) ||
        employee.department?.toLowerCase().includes(searchQuery.toLowerCase())
    ) || [];

    // Export to Excel (CSV)
    const handleExportExcel = () => {
        if (!recapData?.data || recapData.data.length === 0) {
            toast.error('Tidak ada data untuk di-export');
            return;
        }

        const monthName = MONTHS.find(m => m.value === selectedMonth)?.label || '';
        const headers = ['No', 'NIK', 'Nama', 'Unit Kerja', 'H', 'T', 'A', 'I', 'S', 'D', 'C', 'HK', '% Hadir'];
        const rows = filteredData.map((emp: MonthlyRecapEmployee, idx: number) => [
            idx + 1,
            emp.employee_code || '-',
            emp.employee_name,
            emp.department || '-',
            emp.hadir,
            emp.terlambat,
            emp.alpha,
            emp.izin,
            emp.sakit,
            emp.dinas,
            emp.cuti,
            emp.working_days,
            `${emp.attendance_rate}%`,
        ]);

        if (recapData.totals) {
            rows.push([
                '',
                '',
                'TOTAL',
                '',
                recapData.totals.hadir,
                recapData.totals.terlambat,
                recapData.totals.alpha,
                recapData.totals.izin,
                recapData.totals.sakit,
                recapData.totals.dinas,
                recapData.totals.cuti,
                recapData.working_days * recapData.total_employees,
                `${recapData.totals.overall_attendance_rate}%`,
            ]);
        }

        const csvContent = [
            headers.join(','),
            ...rows.map(row => row.join(','))
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `rekap-kehadiran-${monthName}-${selectedYear}.csv`;
        link.click();
        URL.revokeObjectURL(link.href);

        toast.success('Data berhasil di-export ke Excel!');
    };

    // Export to PDF
    const handleExportPDF = async () => {
        if (!recapData?.data || recapData.data.length === 0) {
            toast.error('Tidak ada data untuk di-export');
            return;
        }

        toast.loading('Generating PDF...');

        try {
            // Dynamic import for jspdf
            const { default: jsPDF } = await import('jspdf');
            const { default: autoTable } = await import('jspdf-autotable');

            const monthName = MONTHS.find(m => m.value === selectedMonth)?.label || '';
            const doc = new jsPDF('landscape');

            // Title
            doc.setFontSize(16);
            doc.text(`Rekap Kehadiran Bulan ${monthName} ${selectedYear}`, 14, 15);

            // Summary info
            doc.setFontSize(10);
            doc.text(`Total Karyawan: ${recapData.total_employees} | Hari Kerja: ${recapData.working_days} | Libur: ${recapData.holidays_count}`, 14, 22);
            doc.text(`Periode: ${recapData.period.start_date} s/d ${recapData.period.end_date}`, 14, 27);

            // Table headers
            const headers = [['No', 'NIK', 'Nama Karyawan', 'Unit Kerja', 'H', 'T', 'A', 'I', 'S', 'D', 'C', 'HK', '% Hadir']];

            // Table data
            const data = filteredData.map((emp: MonthlyRecapEmployee, idx: number) => [
                idx + 1,
                emp.employee_code || '-',
                emp.employee_name,
                emp.department || '-',
                emp.hadir,
                emp.terlambat,
                emp.alpha,
                emp.izin,
                emp.sakit,
                emp.dinas,
                emp.cuti,
                emp.working_days,
                `${emp.attendance_rate}%`,
            ]);

            // Add totals row
            if (recapData.totals) {
                data.push([
                    '',
                    '',
                    'TOTAL',
                    '',
                    recapData.totals.hadir,
                    recapData.totals.terlambat,
                    recapData.totals.alpha,
                    recapData.totals.izin,
                    recapData.totals.sakit,
                    recapData.totals.dinas,
                    recapData.totals.cuti,
                    recapData.working_days * recapData.total_employees,
                    `${recapData.totals.overall_attendance_rate}%`,
                ]);
            }

            // Generate table
            autoTable(doc, {
                head: headers,
                body: data,
                startY: 32,
                styles: { fontSize: 8, cellPadding: 2 },
                headStyles: { fillColor: [59, 130, 246], textColor: 255 },
                columnStyles: {
                    0: { halign: 'center', cellWidth: 10 },
                    1: { cellWidth: 25 },
                    2: { cellWidth: 45 },
                    3: { cellWidth: 30 },
                    4: { halign: 'center', cellWidth: 12 },
                    5: { halign: 'center', cellWidth: 12 },
                    6: { halign: 'center', cellWidth: 12 },
                    7: { halign: 'center', cellWidth: 12 },
                    8: { halign: 'center', cellWidth: 12 },
                    9: { halign: 'center', cellWidth: 12 },
                    10: { halign: 'center', cellWidth: 12 },
                    11: { halign: 'center', cellWidth: 12 },
                    12: { halign: 'center', cellWidth: 18 },
                },
                alternateRowStyles: { fillColor: [245, 245, 245] },
                didParseCell: function (cellData) {
                    // Style the totals row
                    if (cellData.row.index === filteredData.length) {
                        cellData.cell.styles.fontStyle = 'bold';
                        cellData.cell.styles.fillColor = [229, 231, 235];
                    }
                },
            });

            // Footer - Legend
            const finalY = (doc as any).lastAutoTable.finalY || 150;
            doc.setFontSize(8);
            doc.text('Keterangan: H=Hadir, T=Terlambat, A=Alpha, I=Izin, S=Sakit, D=Dinas, C=Cuti, HK=Hari Kerja', 14, finalY + 10);
            doc.text(`Dicetak pada: ${new Date().toLocaleString('id-ID')}`, 14, finalY + 15);

            // Save PDF
            doc.save(`rekap-kehadiran-${monthName}-${selectedYear}.pdf`);
            toast.dismiss();
            toast.success('Data berhasil di-export ke PDF!');
        } catch (error) {
            console.error('PDF export error:', error);
            toast.dismiss();
            toast.error('Gagal export PDF. Silakan coba lagi.');
        }
    };

    // Get attendance rate color
    const getAttendanceRateColor = (rate: number) => {
        if (rate >= 95) return 'text-green-600 bg-green-100';
        if (rate >= 85) return 'text-yellow-600 bg-yellow-100';
        if (rate >= 75) return 'text-orange-600 bg-orange-100';
        return 'text-red-600 bg-red-100';
    };

    if (error) {
        return (
            <Card>
                <CardContent className="py-12">
                    <div className="flex flex-col items-center justify-center text-center">
                        <AlertCircle className="h-12 w-12 text-destructive mb-4" />
                        <h3 className="text-lg font-semibold">Gagal memuat data</h3>
                        <p className="text-sm text-muted-foreground mb-4">
                            Terjadi kesalahan saat mengambil data rekap bulanan
                        </p>
                        <Button onClick={() => refetch()}>Coba Lagi</Button>
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header & Filters */}
            <Card>
                <CardHeader>
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <CardTitle className="flex items-center gap-2">
                                <CalendarDays className="h-5 w-5" />
                                Rekap Kehadiran Bulanan
                            </CardTitle>
                            <CardDescription>
                                Rekapitulasi kehadiran karyawan dengan status A/I/S/D/C
                            </CardDescription>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <Select
                                value={selectedMonth.toString()}
                                onValueChange={(v) => setSelectedMonth(parseInt(v))}
                            >
                                <SelectTrigger className="w-[140px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {MONTHS.map((month) => (
                                        <SelectItem key={month.value} value={month.value.toString()}>
                                            {month.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={selectedYear.toString()}
                                onValueChange={(v) => setSelectedYear(parseInt(v))}
                            >
                                <SelectTrigger className="w-[100px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {YEARS.map((year) => (
                                        <SelectItem key={year} value={year.toString()}>
                                            {year}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button variant="outline" size="icon" onClick={() => refetch()} title="Refresh data">
                                <Loader2 className={`h-4 w-4 ${isLoading ? 'animate-spin' : ''}`} />
                            </Button>
                            <Button variant="outline" onClick={handleExportExcel} disabled={!recapData?.data?.length}>
                                <FileSpreadsheet className="h-4 w-4 mr-2" />
                                Excel
                            </Button>
                            <Button onClick={handleExportPDF} disabled={!recapData?.data?.length}>
                                <FileText className="h-4 w-4 mr-2" />
                                PDF
                            </Button>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            {/* Summary Cards */}
            {recapData && (
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-lg bg-blue-100">
                                    <Users className="h-5 w-5 text-blue-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Total Karyawan</p>
                                    <p className="text-2xl font-bold">{recapData.total_employees}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-lg bg-green-100">
                                    <CalendarDays className="h-5 w-5 text-green-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Hari Kerja</p>
                                    <p className="text-2xl font-bold">{recapData.working_days}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-lg bg-yellow-100">
                                    <AlertCircle className="h-5 w-5 text-yellow-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Hari Libur</p>
                                    <p className="text-2xl font-bold">{recapData.holidays_count}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-lg bg-purple-100">
                                    <TrendingUp className="h-5 w-5 text-purple-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Rata-rata Kehadiran</p>
                                    <p className="text-2xl font-bold">{recapData.totals?.overall_attendance_rate || 0}%</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            )}

            {/* Search & Table */}
            <Card>
                <CardHeader className="pb-4">
                    <div className="flex items-center gap-4">
                        <div className="relative flex-1 max-w-sm">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Cari nama, NIK, atau unit kerja..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                        <TooltipProvider>
                            <div className="flex items-center gap-1 text-xs">
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Badge variant="outline" className="bg-green-50 text-green-700 cursor-help">H</Badge>
                                    </TooltipTrigger>
                                    <TooltipContent>Hadir (Tepat Waktu)</TooltipContent>
                                </Tooltip>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Badge variant="outline" className="bg-yellow-50 text-yellow-700 cursor-help">T</Badge>
                                    </TooltipTrigger>
                                    <TooltipContent>Terlambat</TooltipContent>
                                </Tooltip>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Badge variant="outline" className="bg-red-50 text-red-700 cursor-help">A</Badge>
                                    </TooltipTrigger>
                                    <TooltipContent>Alpha (Tanpa Keterangan)</TooltipContent>
                                </Tooltip>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Badge variant="outline" className="bg-blue-50 text-blue-700 cursor-help">I</Badge>
                                    </TooltipTrigger>
                                    <TooltipContent>Izin</TooltipContent>
                                </Tooltip>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Badge variant="outline" className="bg-orange-50 text-orange-700 cursor-help">S</Badge>
                                    </TooltipTrigger>
                                    <TooltipContent>Sakit</TooltipContent>
                                </Tooltip>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Badge variant="outline" className="bg-purple-50 text-purple-700 cursor-help">D</Badge>
                                    </TooltipTrigger>
                                    <TooltipContent>Dinas Luar</TooltipContent>
                                </Tooltip>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Badge variant="outline" className="bg-cyan-50 text-cyan-700 cursor-help">C</Badge>
                                    </TooltipTrigger>
                                    <TooltipContent>Cuti</TooltipContent>
                                </Tooltip>
                            </div>
                        </TooltipProvider>
                    </div>
                </CardHeader>
                <CardContent>
                    {isLoading ? (
                        <div className="flex items-center justify-center py-12">
                            <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                        </div>
                    ) : (
                        <div className="rounded-md border overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/50">
                                        <TableHead className="w-[50px] text-center">No</TableHead>
                                        <TableHead className="w-[100px]">NIK</TableHead>
                                        <TableHead className="min-w-[180px]">Nama Karyawan</TableHead>
                                        <TableHead className="min-w-[120px]">Unit Kerja</TableHead>
                                        <TableHead className="w-[50px] text-center bg-green-50 text-green-700">H</TableHead>
                                        <TableHead className="w-[50px] text-center bg-yellow-50 text-yellow-700">T</TableHead>
                                        <TableHead className="w-[50px] text-center bg-red-50 text-red-700">A</TableHead>
                                        <TableHead className="w-[50px] text-center bg-blue-50 text-blue-700">I</TableHead>
                                        <TableHead className="w-[50px] text-center bg-orange-50 text-orange-700">S</TableHead>
                                        <TableHead className="w-[50px] text-center bg-purple-50 text-purple-700">D</TableHead>
                                        <TableHead className="w-[50px] text-center bg-cyan-50 text-cyan-700">C</TableHead>
                                        <TableHead className="w-[60px] text-center">HK</TableHead>
                                        <TableHead className="w-[80px] text-center">% Hadir</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {filteredData.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={13} className="text-center py-12 text-muted-foreground">
                                                {searchQuery ? 'Tidak ada data yang cocok' : 'Tidak ada data kehadiran'}
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        <>
                                            {filteredData.map((employee: MonthlyRecapEmployee, index: number) => (
                                                <TableRow key={employee.employee_id} className="hover:bg-muted/50">
                                                    <TableCell className="text-center font-medium">{index + 1}</TableCell>
                                                    <TableCell className="font-mono text-xs">{employee.employee_code || '-'}</TableCell>
                                                    <TableCell className="font-medium">{employee.employee_name}</TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">{employee.department || '-'}</TableCell>
                                                    <TableCell className="text-center font-semibold text-green-700">{employee.hadir}</TableCell>
                                                    <TableCell className="text-center font-semibold text-yellow-700">{employee.terlambat}</TableCell>
                                                    <TableCell className="text-center font-semibold text-red-700">{employee.alpha}</TableCell>
                                                    <TableCell className="text-center font-semibold text-blue-700">{employee.izin}</TableCell>
                                                    <TableCell className="text-center font-semibold text-orange-700">{employee.sakit}</TableCell>
                                                    <TableCell className="text-center font-semibold text-purple-700">{employee.dinas}</TableCell>
                                                    <TableCell className="text-center font-semibold text-cyan-700">{employee.cuti}</TableCell>
                                                    <TableCell className="text-center">{employee.working_days}</TableCell>
                                                    <TableCell className="text-center">
                                                        <Badge className={`${getAttendanceRateColor(employee.attendance_rate)} font-semibold`}>
                                                            {employee.attendance_rate}%
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                            {/* Totals Row */}
                                            {recapData?.totals && (
                                                <TableRow className="bg-muted/80 font-bold border-t-2">
                                                    <TableCell colSpan={4} className="text-right pr-4">TOTAL:</TableCell>
                                                    <TableCell className="text-center text-green-700">{recapData.totals.hadir}</TableCell>
                                                    <TableCell className="text-center text-yellow-700">{recapData.totals.terlambat}</TableCell>
                                                    <TableCell className="text-center text-red-700">{recapData.totals.alpha}</TableCell>
                                                    <TableCell className="text-center text-blue-700">{recapData.totals.izin}</TableCell>
                                                    <TableCell className="text-center text-orange-700">{recapData.totals.sakit}</TableCell>
                                                    <TableCell className="text-center text-purple-700">{recapData.totals.dinas}</TableCell>
                                                    <TableCell className="text-center text-cyan-700">{recapData.totals.cuti}</TableCell>
                                                    <TableCell className="text-center">{recapData.working_days * recapData.total_employees}</TableCell>
                                                    <TableCell className="text-center">
                                                        <Badge className={`${getAttendanceRateColor(recapData.totals.overall_attendance_rate)} font-semibold`}>
                                                            {recapData.totals.overall_attendance_rate}%
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}

export default MonthlyRecapTab;
