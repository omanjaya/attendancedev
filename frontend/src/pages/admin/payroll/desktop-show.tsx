import { Link } from '@tanstack/react-router';
import {
    ArrowLeft,
    Download,
    Printer,
    User,
    Building,
    Clock,
    TrendingUp,
    TrendingDown,
    Loader2,
    Calendar,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { payrollStatusLabels, payrollStatusColors, type PayrollEmployeeDetail } from '@/types/payroll';

interface DesktopPayrollShowPageProps {
    payroll: PayrollEmployeeDetail | null;
    isLoading: boolean;
    error: Error | null;
}

export function DesktopPayrollShowPage({ payroll, isLoading, error }: DesktopPayrollShowPageProps) {
    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount || 0);
    };

    if (isLoading) {
        return (
            <div className="flex items-center justify-center min-h-[400px]">
                <Loader2 className="h-8 w-8 animate-spin text-primary" />
            </div>
        );
    }

    if (error || !payroll) {
        return (
            <div className="p-4 sm:p-6 max-w-4xl mx-auto">
                <Link
                    to="/admin/payroll"
                    className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
                >
                    <ArrowLeft className="h-4 w-4 mr-2" />
                    Kembali ke daftar payroll
                </Link>
                <Card>
                    <CardContent className="p-4 sm:p-6 text-center">
                        <p className="text-muted-foreground">
                            {error?.message || 'Data payroll tidak ditemukan'}
                        </p>
                    </CardContent>
                </Card>
            </div>
        );
    }

    // Calculate bonus total from itemized data
    const totalBonuses = payroll.bonuses?.reduce((sum, item) => sum + (item.amount || 0), 0) || 0;

    return (
        <div className="p-4 sm:p-6 max-w-4xl mx-auto">
            {/* Header */}
            <div className="mb-6">
                <Link
                    to="/admin/payroll"
                    className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
                >
                    <ArrowLeft className="h-4 w-4 mr-2" />
                    Kembali ke daftar payroll
                </Link>

                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">Slip Gaji</h1>
                        <p className="text-sm text-muted-foreground">{payroll.employee?.name}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm">
                            <Printer className="h-4 w-4 mr-2" />
                            Print
                        </Button>
                        <Button size="sm">
                            <Download className="h-4 w-4 mr-2" />
                            Download PDF
                        </Button>
                    </div>
                </div>
            </div>

            <div className="space-y-6">
                {/* Employee Info */}
                <Card>
                    <CardContent className="p-4 sm:p-6">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div className="flex items-center gap-4">
                                <div className="h-16 w-16 rounded-full bg-primary/10 flex items-center justify-center">
                                    <User className="h-8 w-8 text-primary" />
                                </div>
                                <div>
                                    <h2 className="text-xl font-bold">{payroll.employee?.name}</h2>
                                    <p className="text-sm text-muted-foreground">
                                        {payroll.employee?.employee_code} {payroll.employee?.position && `| ${payroll.employee.position}`}
                                    </p>
                                    {payroll.employee?.department && (
                                        <div className="flex items-center gap-1 text-sm text-muted-foreground mt-1">
                                            <Building className="h-3 w-3" />
                                            {payroll.employee.department}
                                        </div>
                                    )}
                                </div>
                            </div>
                            <Badge
                                variant="outline"
                                style={{
                                    backgroundColor: `${payrollStatusColors[payroll.status] || '#9CA3AF'}20`,
                                    borderColor: payrollStatusColors[payroll.status] || '#9CA3AF',
                                    color: payrollStatusColors[payroll.status] || '#9CA3AF',
                                }}
                            >
                                {payrollStatusLabels[payroll.status] || payroll.status}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                {/* Period Info */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-lg">
                            <Calendar className="h-5 w-5 text-primary" />
                            Periode Gaji
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div className="text-center p-3 rounded-lg bg-muted">
                                <p className="text-sm font-medium">{payroll.period?.name}</p>
                                <p className="text-xs text-muted-foreground">Periode</p>
                            </div>
                            <div className="text-center p-3 rounded-lg bg-muted">
                                <p className="text-sm font-medium">{payroll.period?.start_date}</p>
                                <p className="text-xs text-muted-foreground">Mulai</p>
                            </div>
                            <div className="text-center p-3 rounded-lg bg-muted">
                                <p className="text-sm font-medium">{payroll.period?.end_date}</p>
                                <p className="text-xs text-muted-foreground">Selesai</p>
                            </div>
                            <div className="text-center p-3 rounded-lg bg-muted">
                                <p className="text-sm font-medium">{payroll.period?.pay_date || '-'}</p>
                                <p className="text-xs text-muted-foreground">Tanggal Bayar</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Work Summary */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-lg">
                            <Clock className="h-5 w-5 text-primary" />
                            Rekap Kerja
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="text-center p-3 rounded-lg bg-muted">
                                <p className="text-2xl font-bold">{payroll.worked_hours || 0}h</p>
                                <p className="text-xs text-muted-foreground">Jam Kerja</p>
                            </div>
                            <div className="text-center p-3 rounded-lg bg-primary/10">
                                <p className="text-2xl font-bold text-primary">{payroll.overtime_hours || 0}h</p>
                                <p className="text-xs text-muted-foreground">Jam Lembur</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Earnings & Deductions */}
                <div className="grid gap-6 md:grid-cols-2">
                    {/* Earnings */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg text-success">
                                <TrendingUp className="h-5 w-5" />
                                Pendapatan
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {payroll.earnings?.length > 0 ? (
                                payroll.earnings.map((item, index) => (
                                    <div key={item.id || index} className="flex justify-between">
                                        <span className="text-sm">{item.name || 'Item'}</span>
                                        <span className="font-medium">{formatCurrency(item.amount)}</span>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground">Tidak ada data pendapatan</p>
                            )}
                            {totalBonuses > 0 && (
                                <>
                                    <Separator />
                                    <p className="text-xs text-muted-foreground font-medium">Bonus</p>
                                    {payroll.bonuses?.map((item, index) => (
                                        <div key={item.id || index} className="flex justify-between">
                                            <span className="text-sm">{item.name || 'Bonus'}</span>
                                            <span className="font-medium">{formatCurrency(item.amount)}</span>
                                        </div>
                                    ))}
                                </>
                            )}
                            <Separator />
                            <div className="flex justify-between font-bold">
                                <span>Total Pendapatan</span>
                                <span className="text-success">{formatCurrency(payroll.gross_salary)}</span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Deductions */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg text-destructive">
                                <TrendingDown className="h-5 w-5" />
                                Potongan
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {payroll.deductions?.length > 0 ? (
                                payroll.deductions.map((item, index) => (
                                    <div key={item.id || index} className="flex justify-between">
                                        <span className="text-sm">{item.name || 'Potongan'}</span>
                                        <span className="font-medium">{formatCurrency(item.amount)}</span>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground">Tidak ada potongan</p>
                            )}
                            <Separator />
                            <div className="flex justify-between font-bold">
                                <span>Total Potongan</span>
                                <span className="text-destructive">{formatCurrency(payroll.total_deductions)}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Net Salary */}
                <Card className="border-primary">
                    <CardContent className="p-6">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <p className="text-sm text-muted-foreground">Gaji Bersih (Take Home Pay)</p>
                                <p className="text-3xl font-bold text-primary">
                                    {formatCurrency(payroll.net_salary)}
                                </p>
                            </div>
                            <div className="text-sm text-muted-foreground text-right">
                                <p>Pendapatan: {formatCurrency(payroll.gross_salary)}</p>
                                <p>Potongan: -{formatCurrency(payroll.total_deductions)}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Notes */}
                {payroll.notes && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Catatan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground">{payroll.notes}</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </div>
    );
}
