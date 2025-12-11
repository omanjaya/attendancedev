
import { useState } from 'react';
import {
    DollarSign,
    Download,
    MoreHorizontal,
    Plus,
    Search,
    FileText,
    Printer,
    TrendingUp,
    Users,
    Calculator
} from 'lucide-react';
import { Link } from '@tanstack/react-router';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { PageHeader } from '@/components/shared';
import { usePayrollPeriods } from '@/hooks/use-payroll';
import { LoadingState } from '@/components/states';

export function DesktopPayrollPage() {
    const [year, setYear] = useState(new Date().getFullYear().toString());
    const { data: periodsData, isLoading } = usePayrollPeriods({ year: parseInt(year) });
    const periods = periodsData?.data || [];

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'completed':
            case 'paid':
                return <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-100">Selesai</Badge>;
            case 'pending':
                return <Badge className="bg-amber-100 text-amber-700 hover:bg-amber-100">Menunggu</Badge>;
            case 'processing':
                return <Badge className="bg-blue-100 text-blue-700 hover:bg-blue-100">Diproses</Badge>;
            default:
                return <Badge variant="secondary">{status}</Badge>;
        }
    };

    return (
        <div className="space-y-6 p-6">
            <PageHeader
                title="Payroll"
                description="Kelola periode penggajian dan slip gaji karyawan"
                icon={DollarSign}
                actions={
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link to="/admin/payroll/formulas">
                                <Calculator className="mr-2 h-4 w-4" />
                                Formula Gaji
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm">
                            <Download className="mr-2 h-4 w-4" />
                            Export Laporan
                        </Button>
                        <Button size="sm">
                            <Plus className="mr-2 h-4 w-4" />
                            Buat Periode Baru
                        </Button>
                    </div>
                }
            />

            {/* Stats Cards */}
            <div className="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Total Pengeluaran (Tahun Ini)</CardTitle>
                        <DollarSign className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">Rp 0</div>
                        <p className="text-xs text-muted-foreground">+0% dari tahun lalu</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Rata-rata Gaji</CardTitle>
                        <TrendingUp className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">Rp 0</div>
                        <p className="text-xs text-muted-foreground">Per karyawan</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Total Karyawan Digaji</CardTitle>
                        <Users className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">0</div>
                        <p className="text-xs text-muted-foreground">Aktif bulan ini</p>
                    </CardContent>
                </Card>
            </div>

            {/* Filters */}
            <div className="flex items-center justify-between gap-4">
                <div className="flex items-center gap-2 flex-1">
                    <div className="relative w-full max-w-sm">
                        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="search"
                            placeholder="Cari periode..."
                            className="pl-8 w-full"
                        />
                    </div>
                    <Select value={year} onValueChange={setYear}>
                        <SelectTrigger className="w-[120px]">
                            <SelectValue placeholder="Tahun" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="2025">2025</SelectItem>
                            <SelectItem value="2024">2024</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            {/* Periods Table */}
            <Card>
                <CardHeader>
                    <CardTitle>Riwayat Periode Penggajian</CardTitle>
                    <CardDescription>Daftar semua periode penggajian yang telah dibuat</CardDescription>
                </CardHeader>
                <CardContent>
                    {isLoading ? (
                        <div className="py-8">
                            <LoadingState message="Memuat data..." />
                        </div>
                    ) : periods.length === 0 ? (
                        <div className="text-center py-12 text-muted-foreground">
                            Belum ada data periode penggajian
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama Periode</TableHead>
                                    <TableHead>Rentang Tanggal</TableHead>
                                    <TableHead>Tanggal Bayar</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {periods.map((period) => (
                                    <TableRow key={period.id}>
                                        <TableCell className="font-medium">
                                            <div className="flex items-center gap-2">
                                                <FileText className="h-4 w-4 text-muted-foreground" />
                                                {period.name}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {new Date(period.start_date).toLocaleDateString('id-ID')} - {new Date(period.end_date).toLocaleDateString('id-ID')}
                                        </TableCell>
                                        <TableCell>
                                            {new Date(period.pay_date).toLocaleDateString('id-ID')}
                                        </TableCell>
                                        <TableCell>{getStatusBadge(period.status)}</TableCell>
                                        <TableCell className="text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="icon">
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link to={`/admin/payroll/${period.id}` as any}>
                                                            Detail Periode
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem>
                                                        <Printer className="mr-2 h-4 w-4" />
                                                        Cetak Laporan
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
