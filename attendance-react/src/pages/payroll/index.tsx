import { useState } from 'react';
import {
  DollarSign,
  Calculator,
  FileText,
  Download,
  CheckCircle2,
  Clock,
  AlertCircle,
  Loader2,
  Eye,
  MoreHorizontal,
  Users,
  TrendingUp,
  Wallet,
  CreditCard,
  Calendar,
  Building,
  Receipt,
} from 'lucide-react';
import { EmptyState } from '@/components/shared';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
  usePayrollPeriods,
  usePayrollEmployees,
  useCalculatePayroll,
  useApprovePayroll,
  useMarkPayrollPaid,
} from '@/hooks/use-payroll';
import {
  payrollStatusLabels,
  payrollStatusColors,
  type PayrollStatus,
  type PayrollEmployee,
} from '@/types/payroll';

// Format currency
const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
};

// Status badge component
function StatusBadge({ status }: { status: PayrollStatus }) {
  const icons: Record<PayrollStatus, React.ReactNode> = {
    draft: <FileText className="mr-1 h-3 w-3" />,
    calculated: <Calculator className="mr-1 h-3 w-3" />,
    approved: <CheckCircle2 className="mr-1 h-3 w-3" />,
    paid: <CreditCard className="mr-1 h-3 w-3" />,
    cancelled: <AlertCircle className="mr-1 h-3 w-3" />,
  };

  return (
    <Badge
      variant="outline"
      style={{
        backgroundColor: `${payrollStatusColors[status]}20`,
        borderColor: payrollStatusColors[status],
        color: payrollStatusColors[status],
      }}
    >
      {icons[status]}
      {payrollStatusLabels[status]}
    </Badge>
  );
}

// Payslip Dialog
function PayslipDialog({
  open,
  onOpenChange,
  employee,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  employee: PayrollEmployee | null;
}) {
  if (!employee) return null;

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Receipt className="h-5 w-5 text-primary" />
            Slip Gaji - {employee.employee_name}
          </DialogTitle>
          <DialogDescription>
            Periode: Maret 2024 | NIP: {employee.employee_nip}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6">
          {/* Employee Info */}
          <div className="grid grid-cols-2 gap-4 rounded-lg bg-muted/50 p-4">
            <div>
              <p className="text-xs text-muted-foreground">Nama</p>
              <p className="font-medium">{employee.employee_name}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">NIP</p>
              <p className="font-medium">{employee.employee_nip}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Departemen</p>
              <p className="font-medium">{employee.department}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Jabatan</p>
              <p className="font-medium">{employee.position}</p>
            </div>
          </div>

          {/* Attendance Summary */}
          <div>
            <h4 className="font-medium mb-2">Ringkasan Kehadiran</h4>
            <div className="grid grid-cols-4 gap-2 text-center">
              <div className="rounded-lg bg-success/10 p-2">
                <p className="text-lg font-bold text-success">{employee.present_days}</p>
                <p className="text-xs text-muted-foreground">Hadir</p>
              </div>
              <div className="rounded-lg bg-destructive/10 p-2">
                <p className="text-lg font-bold text-destructive">{employee.absent_days}</p>
                <p className="text-xs text-muted-foreground">Tidak Hadir</p>
              </div>
              <div className="rounded-lg bg-warning/10 p-2">
                <p className="text-lg font-bold text-warning">{employee.late_days}</p>
                <p className="text-xs text-muted-foreground">Terlambat</p>
              </div>
              <div className="rounded-lg bg-primary/10 p-2">
                <p className="text-lg font-bold text-primary">{employee.overtime_hours}</p>
                <p className="text-xs text-muted-foreground">Jam Lembur</p>
              </div>
            </div>
          </div>

          {/* Earnings & Deductions */}
          <div className="grid grid-cols-2 gap-6">
            {/* Earnings */}
            <div>
              <h4 className="font-medium mb-2 text-success">Pendapatan</h4>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Gaji Pokok</span>
                  <span>{formatCurrency(employee.base_salary)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Tunjangan Jabatan</span>
                  <span>{formatCurrency(employee.position_allowance)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Tunjangan Transport</span>
                  <span>{formatCurrency(employee.transport_allowance)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Tunjangan Makan</span>
                  <span>{formatCurrency(employee.meal_allowance)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Lembur</span>
                  <span>{formatCurrency(employee.overtime_pay)}</span>
                </div>
                {employee.bonus > 0 && (
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Bonus</span>
                    <span>{formatCurrency(employee.bonus)}</span>
                  </div>
                )}
                <div className="flex justify-between font-medium pt-2 border-t">
                  <span>Total Pendapatan</span>
                  <span className="text-success">{formatCurrency(employee.gross_salary)}</span>
                </div>
              </div>
            </div>

            {/* Deductions */}
            <div>
              <h4 className="font-medium mb-2 text-destructive">Potongan</h4>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">PPh 21</span>
                  <span>{formatCurrency(employee.tax)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">BPJS Kesehatan</span>
                  <span>{formatCurrency(employee.bpjs_kesehatan)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">BPJS Ketenagakerjaan</span>
                  <span>{formatCurrency(employee.bpjs_ketenagakerjaan)}</span>
                </div>
                {employee.absence_deduction > 0 && (
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Potongan Absen</span>
                    <span>{formatCurrency(employee.absence_deduction)}</span>
                  </div>
                )}
                {employee.late_deduction > 0 && (
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Potongan Terlambat</span>
                    <span>{formatCurrency(employee.late_deduction)}</span>
                  </div>
                )}
                <div className="flex justify-between font-medium pt-2 border-t">
                  <span>Total Potongan</span>
                  <span className="text-destructive">{formatCurrency(employee.total_deductions)}</span>
                </div>
              </div>
            </div>
          </div>

          {/* Net Salary */}
          <div className="rounded-lg bg-primary/10 p-4 text-center">
            <p className="text-sm text-muted-foreground">Gaji Bersih (Take Home Pay)</p>
            <p className="text-3xl font-bold text-primary">{formatCurrency(employee.net_salary)}</p>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Tutup
          </Button>
          <Button className="gap-2">
            <Download className="h-4 w-4" />
            Download PDF
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export default function PayrollPage() {
  const [activeTab, setActiveTab] = useState('periods');
  const [selectedPeriodId, setSelectedPeriodId] = useState<string>('');
  const [showPayslip, setShowPayslip] = useState(false);
  const [selectedEmployee, setSelectedEmployee] = useState<PayrollEmployee | null>(null);
  const [error, setError] = useState<string | null>(null);

  // Fetch payroll periods
  const currentYear = new Date().getFullYear();
  const {
    data: periodsResponse,
    isLoading: isLoadingPeriods,
  } = usePayrollPeriods({ year: currentYear });
  const payrollPeriods = periodsResponse?.data ?? [];

  // Fetch employees for selected period
  const { data: employeesResponse } = usePayrollEmployees({
    period_id: selectedPeriodId,
  });
  const payrollEmployees = employeesResponse?.data ?? [];

  // Mutations
  const calculatePayrollMutation = useCalculatePayroll();
  const approvePayrollMutation = useApprovePayroll();
  const markAsPaidMutation = useMarkPayrollPaid();

  const isLoading = isLoadingPeriods;

  // Get selected period
  const selectedPeriod = payrollPeriods.find((p) => p.id === selectedPeriodId);

  // Handle mutation errors
  const clearError = () => setError(null);

  // Stats
  const stats = {
    total_periods: payrollPeriods.length,
    pending_approval: payrollPeriods.filter((p) => p.status === 'calculated').length,
    total_paid_this_month: payrollPeriods
      .filter((p) => p.status === 'paid')
      .slice(0, 1)
      .reduce((sum, p) => sum + p.total_net, 0),
    total_employees: payrollPeriods.find((p) => p.status === 'calculated')?.total_employees || 0,
  };

  const viewPayslip = (employee: PayrollEmployee) => {
    setSelectedEmployee(employee);
    setShowPayslip(true);
  };

  return (
    <div className="space-y-6 p-6">
      {/* Header */}
      <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary/10 via-primary/5 to-background p-8">
        <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-primary/10 blur-3xl" />
        <div className="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-primary/5 blur-3xl" />

        <div className="relative">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-3">
              <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10">
                <Wallet className="h-6 w-6 text-primary" />
              </div>
              <div>
                <h1 className="text-2xl font-bold text-foreground">Penggajian</h1>
                <p className="text-sm text-muted-foreground">
                  Kelola perhitungan dan pembayaran gaji karyawan
                </p>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <Button variant="outline" size="sm" className="gap-2">
                <Download className="h-4 w-4" />
                Export
              </Button>
              <Button size="sm" className="gap-2">
                <Calculator className="h-4 w-4" />
                Hitung Payroll
              </Button>
            </div>
          </div>

          {/* Stats */}
          <div className="mt-6 grid gap-4 sm:grid-cols-4">
            <Card className="bg-card/50 backdrop-blur-sm">
              <CardContent className="p-4">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-xs text-muted-foreground">Total Periode</p>
                    <p className="text-2xl font-bold">{stats.total_periods}</p>
                  </div>
                  <Calendar className="h-8 w-8 text-primary/30" />
                </div>
              </CardContent>
            </Card>

            <Card className="bg-card/50 backdrop-blur-sm">
              <CardContent className="p-4">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-xs text-muted-foreground">Menunggu Approval</p>
                    <p className="text-2xl font-bold text-warning">{stats.pending_approval}</p>
                  </div>
                  <Clock className="h-8 w-8 text-warning/30" />
                </div>
              </CardContent>
            </Card>

            <Card className="bg-card/50 backdrop-blur-sm">
              <CardContent className="p-4">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-xs text-muted-foreground">Dibayar Bulan Ini</p>
                    <p className="text-xl font-bold text-success">
                      {formatCurrency(stats.total_paid_this_month)}
                    </p>
                  </div>
                  <DollarSign className="h-8 w-8 text-success/30" />
                </div>
              </CardContent>
            </Card>

            <Card className="bg-card/50 backdrop-blur-sm">
              <CardContent className="p-4">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-xs text-muted-foreground">Total Karyawan</p>
                    <p className="text-2xl font-bold">{stats.total_employees}</p>
                  </div>
                  <Users className="h-8 w-8 text-primary/30" />
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>

      {/* Error Alert */}
      {error && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertTitle>Error</AlertTitle>
          <AlertDescription>{error}</AlertDescription>
          <Button variant="ghost" size="sm" onClick={clearError}>
            Tutup
          </Button>
        </Alert>
      )}

      {/* Main Content */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="periods" className="gap-2">
            <Calendar className="h-4 w-4" />
            Periode
          </TabsTrigger>
          <TabsTrigger value="employees" className="gap-2">
            <Users className="h-4 w-4" />
            Karyawan
          </TabsTrigger>
          <TabsTrigger value="reports" className="gap-2">
            <TrendingUp className="h-4 w-4" />
            Laporan
          </TabsTrigger>
        </TabsList>

        {/* Periods Tab */}
        <TabsContent value="periods" className="mt-6">
          {isLoading ? (
            <Card>
              <CardContent className="flex items-center justify-center py-20">
                <Loader2 className="h-8 w-8 animate-spin text-primary" />
              </CardContent>
            </Card>
          ) : (
            <div className="space-y-4">
              {payrollPeriods.map((period) => (
                <Card key={period.id} className="overflow-hidden">
                  <CardContent className="p-0">
                    <div className="flex flex-col sm:flex-row">
                      <div
                        className="w-full sm:w-2 shrink-0"
                        style={{ backgroundColor: payrollStatusColors[period.status] }}
                      />
                      <div className="flex-1 p-4">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                          <div className="space-y-1">
                            <div className="flex items-center gap-2">
                              <h3 className="font-semibold">{period.name}</h3>
                              <StatusBadge status={period.status} />
                            </div>
                            <div className="flex items-center gap-4 text-sm text-muted-foreground">
                              <span className="flex items-center gap-1">
                                <Calendar className="h-3 w-3" />
                                {new Date(period.start_date).toLocaleDateString('id-ID')} -{' '}
                                {new Date(period.end_date).toLocaleDateString('id-ID')}
                              </span>
                              <span className="flex items-center gap-1">
                                <Users className="h-3 w-3" />
                                {period.total_employees} Karyawan
                              </span>
                            </div>
                          </div>

                          <div className="flex items-center gap-4">
                            <div className="text-right">
                              <p className="text-xs text-muted-foreground">Total Gaji Bersih</p>
                              <p className="text-lg font-bold text-primary">
                                {formatCurrency(period.total_net)}
                              </p>
                            </div>

                            <DropdownMenu>
                              <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="icon">
                                  <MoreHorizontal className="h-4 w-4" />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end">
                                <DropdownMenuItem
                                  onClick={() => {
                                    setSelectedPeriodId(period.id);
                                    setActiveTab('employees');
                                  }}
                                >
                                  <Eye className="mr-2 h-4 w-4" />
                                  Lihat Detail
                                </DropdownMenuItem>
                                {period.status === 'draft' && (
                                  <DropdownMenuItem
                                    onClick={() =>
                                      calculatePayrollMutation.mutate({
                                        id: period.id,
                                        data: { include_overtime: true, include_bonus: false },
                                      })
                                    }
                                  >
                                    <Calculator className="mr-2 h-4 w-4" />
                                    Hitung Payroll
                                  </DropdownMenuItem>
                                )}
                                {period.status === 'calculated' && (
                                  <DropdownMenuItem
                                    className="text-success"
                                    onClick={() => approvePayrollMutation.mutate({ id: period.id })}
                                  >
                                    <CheckCircle2 className="mr-2 h-4 w-4" />
                                    Approve
                                  </DropdownMenuItem>
                                )}
                                {period.status === 'approved' && (
                                  <DropdownMenuItem
                                    onClick={() =>
                                      markAsPaidMutation.mutate({
                                        id: period.id,
                                        data: {
                                          payment_date: new Date().toISOString().split('T')[0],
                                          payment_method: 'bank_transfer',
                                        },
                                      })
                                    }
                                  >
                                    <CreditCard className="mr-2 h-4 w-4" />
                                    Tandai Dibayar
                                  </DropdownMenuItem>
                                )}
                                <DropdownMenuSeparator />
                                <DropdownMenuItem>
                                  <Download className="mr-2 h-4 w-4" />
                                  Export
                                </DropdownMenuItem>
                              </DropdownMenuContent>
                            </DropdownMenu>
                          </div>
                        </div>

                        {/* Summary row */}
                        {period.total_gross > 0 && (
                          <div className="mt-4 grid grid-cols-3 gap-4 rounded-lg bg-muted/50 p-3 text-sm">
                            <div>
                              <span className="text-muted-foreground">Total Bruto</span>
                              <p className="font-medium">{formatCurrency(period.total_gross)}</p>
                            </div>
                            <div>
                              <span className="text-muted-foreground">Total Potongan</span>
                              <p className="font-medium text-destructive">
                                -{formatCurrency(period.total_deductions)}
                              </p>
                            </div>
                            <div>
                              <span className="text-muted-foreground">Total Netto</span>
                              <p className="font-medium text-success">
                                {formatCurrency(period.total_net)}
                              </p>
                            </div>
                          </div>
                        )}
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </TabsContent>

        {/* Employees Tab */}
        <TabsContent value="employees" className="mt-6 space-y-4">
          {/* Period Selector */}
          <div className="flex items-center gap-4">
            <Select value={selectedPeriodId} onValueChange={setSelectedPeriodId}>
              <SelectTrigger className="w-[250px]">
                <SelectValue placeholder="Pilih Periode" />
              </SelectTrigger>
              <SelectContent>
                {payrollPeriods.map((period) => (
                  <SelectItem key={period.id} value={period.id}>
                    {period.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {selectedPeriod && <StatusBadge status={selectedPeriod.status} />}
          </div>

          {/* Employees Table */}
          <Card>
            <CardHeader>
              <CardTitle>Daftar Gaji Karyawan</CardTitle>
              <CardDescription>
                {selectedPeriod
                  ? `Periode: ${selectedPeriod.name}`
                  : 'Pilih periode untuk melihat data'}
              </CardDescription>
            </CardHeader>
            <CardContent>
              {!selectedPeriodId ? (
                <EmptyState
                  icon={Building}
                  title="Pilih Periode"
                  description="Pilih periode payroll untuk melihat data karyawan"
                />
              ) : isLoading ? (
                <div className="flex items-center justify-center py-10">
                  <Loader2 className="h-8 w-8 animate-spin text-primary" />
                </div>
              ) : payrollEmployees.length === 0 ? (
                <EmptyState
                  icon={Users}
                  title="Belum Ada Data"
                  description="Data payroll belum dihitung untuk periode ini"
                />
              ) : (
                <div className="rounded-lg border">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Karyawan</TableHead>
                        <TableHead>Departemen</TableHead>
                        <TableHead className="text-right">Gaji Pokok</TableHead>
                        <TableHead className="text-right">Tunjangan</TableHead>
                        <TableHead className="text-right">Potongan</TableHead>
                        <TableHead className="text-right">Gaji Bersih</TableHead>
                        <TableHead className="w-10"></TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {payrollEmployees.map((employee) => (
                        <TableRow key={employee.id}>
                          <TableCell>
                            <div>
                              <p className="font-medium">{employee.employee_name}</p>
                              <p className="text-xs text-muted-foreground">{employee.employee_nip}</p>
                            </div>
                          </TableCell>
                          <TableCell>
                            <div>
                              <p className="text-sm">{employee.department}</p>
                              <p className="text-xs text-muted-foreground">{employee.position}</p>
                            </div>
                          </TableCell>
                          <TableCell className="text-right">
                            {formatCurrency(employee.base_salary)}
                          </TableCell>
                          <TableCell className="text-right text-success">
                            +{formatCurrency(employee.gross_salary - employee.base_salary)}
                          </TableCell>
                          <TableCell className="text-right text-destructive">
                            -{formatCurrency(employee.total_deductions)}
                          </TableCell>
                          <TableCell className="text-right font-bold">
                            {formatCurrency(employee.net_salary)}
                          </TableCell>
                          <TableCell>
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => viewPayslip(employee)}
                            >
                              <Eye className="h-4 w-4" />
                            </Button>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Reports Tab */}
        <TabsContent value="reports" className="mt-6">
          <Card>
            <CardHeader>
              <CardTitle>Laporan Payroll</CardTitle>
              <CardDescription>Analisis dan laporan penggajian</CardDescription>
            </CardHeader>
            <CardContent className="py-10">
              <EmptyState
                icon={TrendingUp}
                title="Coming Soon"
                description="Fitur laporan dan analisis akan segera hadir"
              />
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* Payslip Dialog */}
      <PayslipDialog
        open={showPayslip}
        onOpenChange={setShowPayslip}
        employee={selectedEmployee}
      />
    </div>
  );
}
