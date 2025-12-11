import { useState } from 'react';
import {
    CalendarDays,
    Search,
    Loader2,
    Users,
    Clock,
    BookOpen,
    GraduationCap,
    AlertCircle,
    FileText,
    FileSpreadsheet,
    TrendingUp,
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
import { useTeachingScheduleReport, useTeachingSubjects } from '@/hooks/use-reports';
import type { TeachingScheduleTeacher } from '@/lib/api/reports';

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

export function TeachingScheduleTab() {
    const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1);
    const [selectedYear, setSelectedYear] = useState(currentYear);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedSubject, setSelectedSubject] = useState<string>('all');

    // Fetch teaching schedule report data
    const { data: reportData, isLoading, error, refetch } = useTeachingScheduleReport({
        month: selectedMonth,
        year: selectedYear,
        subject: selectedSubject === 'all' ? undefined : selectedSubject,
    });

    // Fetch subjects for filter dropdown
    const { data: subjects } = useTeachingSubjects();

    // Filter data by search query
    const filteredData = reportData?.data?.filter((teacher: TeachingScheduleTeacher) =>
        teacher.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        teacher.employee_code?.toLowerCase().includes(searchQuery.toLowerCase()) ||
        teacher.subjects.some(s => s.toLowerCase().includes(searchQuery.toLowerCase()))
    ) || [];

    // Export to Excel (CSV)
    const handleExportExcel = () => {
        if (!reportData?.data || reportData.data.length === 0) {
            toast.error('Tidak ada data untuk di-export');
            return;
        }

        const monthName = MONTHS.find(m => m.value === selectedMonth)?.label || '';
        const headers = ['No', 'NIK', 'Nama Guru', 'Mata Pelajaran', 'Kelas', 'Sesi/Minggu', 'Jam/Minggu', 'Sesi Dijadwalkan', 'Sesi Hadir', 'Sesi Absen', '% Kehadiran'];
        const rows = filteredData.map((teacher: TeachingScheduleTeacher, idx: number) => [
            idx + 1,
            teacher.employee_code || '-',
            teacher.name,
            teacher.subjects.join(', ') || '-',
            teacher.classes.join(', ') || '-',
            teacher.sessions_per_week,
            teacher.hours_per_week,
            teacher.scheduled_sessions,
            teacher.sessions_taught,
            teacher.sessions_missed,
            `${teacher.attendance_rate}%`,
        ]);

        const csvContent = [
            headers.join(','),
            ...rows.map(row => row.map(cell => `"${cell}"`).join(','))
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `laporan-jadwal-mengajar-${monthName}-${selectedYear}.csv`;
        link.click();
        URL.revokeObjectURL(link.href);

        toast.success('Data berhasil di-export ke Excel!');
    };

    // Export to PDF
    const handleExportPDF = async () => {
        if (!reportData?.data || reportData.data.length === 0) {
            toast.error('Tidak ada data untuk di-export');
            return;
        }

        toast.loading('Generating PDF...');

        try {
            const { default: jsPDF } = await import('jspdf');
            const { default: autoTable } = await import('jspdf-autotable');

            const monthName = MONTHS.find(m => m.value === selectedMonth)?.label || '';
            const doc = new jsPDF('landscape');

            // Title
            doc.setFontSize(16);
            doc.text(`Laporan Jadwal Mengajar Bulan ${monthName} ${selectedYear}`, 14, 15);

            // Summary info
            doc.setFontSize(10);
            doc.text(`Total Guru: ${reportData.summary.total_teachers} | Rata-rata Jam/Guru: ${reportData.summary.avg_hours_per_teacher} | Rata-rata Kehadiran: ${reportData.summary.avg_attendance_rate}%`, 14, 22);
            doc.text(`Periode: ${reportData.period.start_date} s/d ${reportData.period.end_date}`, 14, 27);

            // Table headers
            const headers = [['No', 'NIK', 'Nama Guru', 'Mata Pelajaran', 'Kelas', 'Sesi/Mg', 'Jam/Mg', 'Dijadwal', 'Hadir', 'Absen', '% Hadir']];

            // Table data
            const data = filteredData.map((teacher: TeachingScheduleTeacher, idx: number) => [
                idx + 1,
                teacher.employee_code || '-',
                teacher.name,
                teacher.subjects.slice(0, 2).join(', ') + (teacher.subjects.length > 2 ? '...' : ''),
                teacher.classes.slice(0, 2).join(', ') + (teacher.classes.length > 2 ? '...' : ''),
                teacher.sessions_per_week,
                teacher.hours_per_week,
                teacher.scheduled_sessions,
                teacher.sessions_taught,
                teacher.sessions_missed,
                `${teacher.attendance_rate}%`,
            ]);

            // Generate table
            autoTable(doc, {
                head: headers,
                body: data,
                startY: 32,
                styles: { fontSize: 8, cellPadding: 2 },
                headStyles: { fillColor: [16, 185, 129], textColor: 255 },
                columnStyles: {
                    0: { halign: 'center', cellWidth: 10 },
                    1: { cellWidth: 22 },
                    2: { cellWidth: 35 },
                    3: { cellWidth: 40 },
                    4: { cellWidth: 35 },
                    5: { halign: 'center', cellWidth: 15 },
                    6: { halign: 'center', cellWidth: 15 },
                    7: { halign: 'center', cellWidth: 18 },
                    8: { halign: 'center', cellWidth: 15 },
                    9: { halign: 'center', cellWidth: 15 },
                    10: { halign: 'center', cellWidth: 18 },
                },
                alternateRowStyles: { fillColor: [245, 245, 245] },
            });

            // Footer
            const finalY = (doc as any).lastAutoTable.finalY || 150;
            doc.setFontSize(8);
            doc.text(`Dicetak pada: ${new Date().toLocaleString('id-ID')}`, 14, finalY + 10);

            // Save PDF
            doc.save(`laporan-jadwal-mengajar-${monthName}-${selectedYear}.pdf`);
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
                            Terjadi kesalahan saat mengambil data jadwal mengajar
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
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <CardTitle className="flex items-center gap-2">
                                <GraduationCap className="h-5 w-5" />
                                Laporan Jadwal Mengajar
                            </CardTitle>
                            <CardDescription>
                                Rekap jadwal mengajar dan kehadiran guru berdasarkan jadwal pelajaran
                            </CardDescription>
                        </div>
                        <div className="flex flex-col sm:flex-row items-center gap-3">
                            <div className="grid grid-cols-[1fr_1fr_auto] sm:flex sm:items-center gap-2 w-full sm:w-auto">
                                <Select
                                    value={selectedMonth.toString()}
                                    onValueChange={(v) => setSelectedMonth(parseInt(v))}
                                >
                                    <SelectTrigger className="w-full sm:w-[140px]">
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
                                    <SelectTrigger className="w-full sm:w-[100px]">
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
                            </div>
                            <div className="flex items-center gap-2 w-full sm:w-auto">
                                <Button variant="outline" className="flex-1 sm:flex-none" onClick={handleExportExcel} disabled={!reportData?.data?.length}>
                                    <FileSpreadsheet className="h-4 w-4 mr-2" />
                                    Excel
                                </Button>
                                <Button className="flex-1 sm:flex-none bg-emerald-600 hover:bg-emerald-700" onClick={handleExportPDF} disabled={!reportData?.data?.length}>
                                    <FileText className="h-4 w-4 mr-2" />
                                    PDF
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            {/* Summary Cards */}
            {reportData && (
                <div className="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-lg bg-emerald-100">
                                    <Users className="h-5 w-5 text-emerald-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Total Guru</p>
                                    <p className="text-2xl font-bold">{reportData.summary.total_teachers}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-lg bg-blue-100">
                                    <Clock className="h-5 w-5 text-blue-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Rata-rata Jam/Guru</p>
                                    <p className="text-2xl font-bold">{reportData.summary.avg_hours_per_teacher} jam</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-lg bg-purple-100">
                                    <CalendarDays className="h-5 w-5 text-purple-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Total Sesi Dijadwalkan</p>
                                    <p className="text-2xl font-bold">{reportData.summary.total_sessions_scheduled}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-lg bg-green-100">
                                    <TrendingUp className="h-5 w-5 text-green-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Rata-rata Kehadiran</p>
                                    <p className="text-2xl font-bold">{reportData.summary.avg_attendance_rate}%</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            )}

            {/* Search, Filter & Table */}
            <Card>
                <CardHeader className="pb-4">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div className="flex flex-col sm:flex-row gap-3 flex-1">
                            <div className="relative flex-1 max-w-sm">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Cari nama, NIK, atau mata pelajaran..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Select value={selectedSubject} onValueChange={setSelectedSubject}>
                                <SelectTrigger className="w-full sm:w-[180px]">
                                    <BookOpen className="h-4 w-4 mr-2" />
                                    <SelectValue placeholder="Filter Mapel" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Mata Pelajaran</SelectItem>
                                    {subjects?.map((subject) => (
                                        <SelectItem key={subject.id} value={subject.id}>
                                            {subject.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <TooltipProvider>
                            <div className="flex flex-wrap items-center justify-end gap-1.5 text-xs">
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Badge variant="outline" className="bg-emerald-50 text-emerald-700 cursor-help">Sesi/Mg</Badge>
                                    </TooltipTrigger>
                                    <TooltipContent>Jumlah sesi mengajar per minggu</TooltipContent>
                                </Tooltip>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Badge variant="outline" className="bg-blue-50 text-blue-700 cursor-help">Jam/Mg</Badge>
                                    </TooltipTrigger>
                                    <TooltipContent>Total jam mengajar per minggu</TooltipContent>
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
                                        <TableHead className="min-w-[150px]">Nama Guru</TableHead>
                                        <TableHead className="min-w-[150px]">Mata Pelajaran</TableHead>
                                        <TableHead className="min-w-[120px]">Kelas</TableHead>
                                        <TableHead className="w-[80px] text-center bg-emerald-50">Sesi/Mg</TableHead>
                                        <TableHead className="w-[80px] text-center bg-blue-50">Jam/Mg</TableHead>
                                        <TableHead className="w-[80px] text-center">Dijadwal</TableHead>
                                        <TableHead className="w-[70px] text-center text-green-700">Hadir</TableHead>
                                        <TableHead className="w-[70px] text-center text-red-700">Absen</TableHead>
                                        <TableHead className="w-[90px] text-center">% Hadir</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {filteredData.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={11} className="text-center py-12 text-muted-foreground">
                                                {searchQuery || selectedSubject !== 'all' ? 'Tidak ada data yang cocok' : 'Tidak ada data jadwal mengajar'}
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        filteredData.map((teacher: TeachingScheduleTeacher, index: number) => (
                                            <TableRow key={teacher.employee_id} className="hover:bg-muted/50">
                                                <TableCell className="text-center font-medium">{index + 1}</TableCell>
                                                <TableCell className="font-mono text-xs">{teacher.employee_code || '-'}</TableCell>
                                                <TableCell className="font-medium">{teacher.name}</TableCell>
                                                <TableCell>
                                                    <div className="flex flex-wrap gap-1">
                                                        {teacher.subjects.slice(0, 2).map((subj, i) => (
                                                            <Badge key={i} variant="outline" className="text-xs bg-purple-50 text-purple-700">
                                                                {subj}
                                                            </Badge>
                                                        ))}
                                                        {teacher.subjects.length > 2 && (
                                                            <Badge variant="outline" className="text-xs">
                                                                +{teacher.subjects.length - 2}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex flex-wrap gap-1">
                                                        {teacher.classes.slice(0, 2).map((cls, i) => (
                                                            <Badge key={i} variant="secondary" className="text-xs">
                                                                {cls}
                                                            </Badge>
                                                        ))}
                                                        {teacher.classes.length > 2 && (
                                                            <Badge variant="outline" className="text-xs">
                                                                +{teacher.classes.length - 2}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-center font-semibold text-emerald-700">{teacher.sessions_per_week}</TableCell>
                                                <TableCell className="text-center font-semibold text-blue-700">{teacher.hours_per_week}</TableCell>
                                                <TableCell className="text-center">{teacher.scheduled_sessions}</TableCell>
                                                <TableCell className="text-center font-semibold text-green-700">{teacher.sessions_taught}</TableCell>
                                                <TableCell className="text-center font-semibold text-red-700">{teacher.sessions_missed}</TableCell>
                                                <TableCell className="text-center">
                                                    <Badge className={`${getAttendanceRateColor(teacher.attendance_rate)} font-semibold`}>
                                                        {teacher.attendance_rate}%
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))
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

export default TeachingScheduleTab;
