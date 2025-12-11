import { useState } from 'react';
import { Link } from '@tanstack/react-router';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  ArrowLeft,
  ArrowRight,
  UserPlus,
  Key,
  Download,
  Search,
  CheckCircle,
  Loader2,
  RefreshCw,
  Copy,
} from 'lucide-react';

import { Button } from '@/components/ui/button';

import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { useNotificationStore } from '@/stores';
import {
  getCredentialStats,
  getEmployeesWithoutUsers,
  getEmployeesWithUsers,
  createUsersForEmployees,
  resetPasswordsForEmployees,
  type CreateUserResult,
} from '@/lib/api/credentials';

interface CreatedUser {
  employee_name: string;
  employee_email: string;
  password: string;
  role: string;
}

export default function EmployeeCredentialsPage() {
  const queryClient = useQueryClient();
  const { success, error: showError } = useNotificationStore();
  const [activeTab, setActiveTab] = useState('create-users');
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedWithoutUsers, setSelectedWithoutUsers] = useState<number[]>([]);
  const [selectedWithUsers, setSelectedWithUsers] = useState<number[]>([]);
  const [showResultsDialog, setShowResultsDialog] = useState(false);
  const [createdUsers, setCreatedUsers] = useState<CreatedUser[]>([]);

  // Fetch credential stats
  const { data: stats } = useQuery({
    queryKey: ['credentials', 'stats'],
    queryFn: getCredentialStats,
  });

  // Fetch employees without users
  const { data: employeesWithoutUsers = [] } = useQuery({
    queryKey: ['credentials', 'without-users'],
    queryFn: getEmployeesWithoutUsers,
  });

  // Fetch employees with users
  const { data: employeesWithUsers = [] } = useQuery({
    queryKey: ['credentials', 'with-users'],
    queryFn: getEmployeesWithUsers,
  });

  // Filter employees
  const filteredWithoutUsers = employeesWithoutUsers.filter(emp =>
    emp.full_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    emp.email.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const filteredWithUsers = employeesWithUsers.filter(emp =>
    emp.full_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    emp.email.toLowerCase().includes(searchQuery.toLowerCase())
  );

  // Select all handlers
  const handleSelectAllWithoutUsers = (checked: boolean) => {
    if (checked) {
      setSelectedWithoutUsers(filteredWithoutUsers.map(e => e.id));
    } else {
      setSelectedWithoutUsers([]);
    }
  };

  const handleSelectAllWithUsers = (checked: boolean) => {
    if (checked) {
      setSelectedWithUsers(filteredWithUsers.map(e => e.id));
    } else {
      setSelectedWithUsers([]);
    }
  };

  // Create users mutation
  const createUsersMutation = useMutation({
    mutationFn: createUsersForEmployees,
    onSuccess: (results: CreateUserResult[]) => {
      const formatted: CreatedUser[] = results.map(r => ({
        employee_name: r.employee_name,
        employee_email: r.email,
        password: r.password,
        role: 'pegawai', // Default role
      }));
      setCreatedUsers(formatted);
      setShowResultsDialog(true);
      setSelectedWithoutUsers([]);
      queryClient.invalidateQueries({ queryKey: ['credentials'] });
      success('Berhasil', `${results.length} user berhasil dibuat`);
    },
    onError: () => {
      showError('Error', 'Gagal membuat user');
    },
  });

  // Reset passwords mutation
  const resetPasswordsMutation = useMutation({
    mutationFn: resetPasswordsForEmployees,
    onSuccess: (results: CreateUserResult[]) => {
      const formatted: CreatedUser[] = results.map(r => ({
        employee_name: r.employee_name,
        employee_email: r.email,
        password: r.password,
        role: 'pegawai',
      }));
      setCreatedUsers(formatted);
      setShowResultsDialog(true);
      setSelectedWithUsers([]);
      queryClient.invalidateQueries({ queryKey: ['credentials'] });
      success('Berhasil', `Password ${results.length} user berhasil direset`);
    },
    onError: () => {
      showError('Error', 'Gagal reset password');
    },
  });

  const handleCreateUsers = (employeeIds: number[]) => {
    createUsersMutation.mutate(employeeIds);
  };

  const handleResetPasswords = (employeeIds: number[]) => {
    resetPasswordsMutation.mutate(employeeIds);
  };

  const isLoading = createUsersMutation.isPending || resetPasswordsMutation.isPending;

  // Copy password
  const handleCopyPassword = (password: string) => {
    navigator.clipboard.writeText(password);
    success('Disalin', 'Password berhasil disalin');
  };

  // Export to CSV
  const handleExport = () => {
    const csv = [
      ['Nama', 'Email', 'Password', 'Role'],
      ...createdUsers.map(u => [u.employee_name, u.employee_email, u.password, u.role])
    ].map(row => row.join(',')).join('\n');

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'user_credentials.csv';
    a.click();
    success('Berhasil', 'File berhasil diunduh');
  };

  return (
    <section className="py-8 sm:py-16">
      <div className="container">
        {/* Back Link */}
        <Link
          to="/admin/employees"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-8"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar karyawan
        </Link>

        {/* Header - shadcnblocks Stats8 style */}
        <div className="mb-16">
          <div className="flex flex-col gap-4 mb-10">
            <h1 className="text-4xl font-bold md:text-5xl">Manajemen User & Password</h1>
            <p className="text-lg text-muted-foreground">Kelola akun user untuk guru dan karyawan</p>
            <Link
              to="/admin/employees"
              className="flex items-center gap-1 font-bold hover:underline w-fit"
            >
              Lihat daftar karyawan
              <ArrowRight className="h-auto w-4" />
            </Link>
          </div>

          {/* Stats Grid - shadcnblocks Stats8 style */}
          <div className="grid gap-x-5 gap-y-8 md:grid-cols-2 lg:grid-cols-4">
            <div className="flex flex-col gap-4">
              <div className="text-5xl font-bold md:text-6xl">{stats?.total_employees || 0}</div>
              <p className="text-muted-foreground">total karyawan terdaftar</p>
            </div>
            <div className="flex flex-col gap-4">
              <div className="text-5xl font-bold md:text-6xl">{stats?.with_users || 0}</div>
              <p className="text-muted-foreground">sudah memiliki akun user</p>
            </div>
            <div className="flex flex-col gap-4">
              <div className="text-5xl font-bold md:text-6xl">{stats?.without_users || 0}</div>
              <p className="text-muted-foreground">belum memiliki akun user</p>
            </div>
            <div className="flex flex-col gap-4">
              <div className="text-5xl font-bold md:text-6xl">{stats?.percentage_with_users || 0}%</div>
              <p className="text-muted-foreground">tingkat kelengkapan akun</p>
            </div>
          </div>
        </div>

        {/* Tabs */}
        <Tabs value={activeTab} onValueChange={setActiveTab}>
          <TabsList className="mb-8">
            <TabsTrigger value="create-users" className="gap-2">
              <UserPlus className="h-4 w-4" />
              Buat User Baru
              <Badge variant="destructive" className="ml-1">{stats?.without_users || 0}</Badge>
            </TabsTrigger>
            <TabsTrigger value="reset-passwords" className="gap-2">
              <Key className="h-4 w-4" />
              Reset Password
              <Badge variant="secondary" className="ml-1">{stats?.with_users || 0}</Badge>
            </TabsTrigger>
          </TabsList>

          {/* Create Users Tab */}
          <TabsContent value="create-users">
            <div className="border-border bg-background rounded-xl border shadow-sm overflow-hidden">
              <div className="p-4 sm:p-6 border-b">
                <h3 className="text-lg font-semibold">Karyawan Tanpa Akun User</h3>
                <p className="text-sm text-muted-foreground">Pilih karyawan untuk dibuatkan akun user dan password</p>
              </div>

              {/* Actions Bar */}
              <div className="p-4 border-b bg-muted/30">
                <div className="flex flex-col sm:flex-row gap-4">
                  <div className="relative flex-1">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                      placeholder="Cari nama atau email..."
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      className="pl-10"
                    />
                  </div>
                  <Button
                    onClick={() => handleCreateUsers(selectedWithoutUsers)}
                    disabled={selectedWithoutUsers.length === 0 || isLoading}
                  >
                    {isLoading ? (
                      <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                    ) : (
                      <UserPlus className="h-4 w-4 mr-2" />
                    )}
                    Buat User ({selectedWithoutUsers.length})
                  </Button>
                </div>
              </div>

              {/* Table */}
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow className="bg-muted/50">
                      <TableHead className="w-12">
                        <Checkbox
                          checked={selectedWithoutUsers.length === filteredWithoutUsers.length && filteredWithoutUsers.length > 0}
                          onCheckedChange={handleSelectAllWithoutUsers}
                        />
                      </TableHead>
                      <TableHead>Nama</TableHead>
                      <TableHead>Email</TableHead>
                      <TableHead>Tipe</TableHead>
                      <TableHead>Lokasi</TableHead>
                      <TableHead>Tanggal Masuk</TableHead>
                      <TableHead className="w-24">Aksi</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {filteredWithoutUsers.map((employee) => (
                      <TableRow key={employee.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedWithoutUsers.includes(employee.id)}
                            onCheckedChange={(checked) => {
                              if (checked) {
                                setSelectedWithoutUsers([...selectedWithoutUsers, employee.id]);
                              } else {
                                setSelectedWithoutUsers(selectedWithoutUsers.filter(id => id !== employee.id));
                              }
                            }}
                          />
                        </TableCell>
                        <TableCell className="font-medium">{employee.full_name}</TableCell>
                        <TableCell className="text-muted-foreground">{employee.email}</TableCell>
                        <TableCell>
                          <Badge variant="outline">{employee.employee_type}</Badge>
                        </TableCell>
                        <TableCell>{employee.location}</TableCell>
                        <TableCell>{employee.hire_date ? new Date(employee.hire_date).toLocaleDateString('id-ID') : '-'}</TableCell>
                        <TableCell>
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => handleCreateUsers([employee.id])}
                            disabled={isLoading}
                          >
                            Buat User
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))}
                    {filteredWithoutUsers.length === 0 && (
                      <TableRow>
                        <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                          <CheckCircle className="h-8 w-8 mx-auto mb-2" />
                          Semua karyawan sudah memiliki akun user
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </div>
            </div>
          </TabsContent>

          {/* Reset Passwords Tab */}
          <TabsContent value="reset-passwords">
            <div className="border-border bg-background rounded-xl border shadow-sm overflow-hidden">
              <div className="p-4 sm:p-6 border-b">
                <h3 className="text-lg font-semibold">Karyawan dengan Akun User</h3>
                <p className="text-sm text-muted-foreground">Reset password untuk karyawan yang sudah memiliki akun</p>
              </div>

              {/* Actions Bar */}
              <div className="p-4 border-b bg-muted/30">
                <div className="flex flex-col sm:flex-row gap-4">
                  <div className="relative flex-1">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                      placeholder="Cari nama atau email..."
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      className="pl-10"
                    />
                  </div>
                  <Button
                    variant="outline"
                    onClick={() => handleResetPasswords(selectedWithUsers)}
                    disabled={selectedWithUsers.length === 0 || isLoading}
                  >
                    {isLoading ? (
                      <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                    ) : (
                      <RefreshCw className="h-4 w-4 mr-2" />
                    )}
                    Reset Password ({selectedWithUsers.length})
                  </Button>
                </div>
              </div>

              {/* Table */}
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow className="bg-muted/50">
                      <TableHead className="w-12">
                        <Checkbox
                          checked={selectedWithUsers.length === filteredWithUsers.length && filteredWithUsers.length > 0}
                          onCheckedChange={handleSelectAllWithUsers}
                        />
                      </TableHead>
                      <TableHead>Nama</TableHead>
                      <TableHead>Email</TableHead>
                      <TableHead>Role</TableHead>
                      <TableHead>Login Terakhir</TableHead>
                      <TableHead>User Dibuat</TableHead>
                      <TableHead className="w-32">Aksi</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {filteredWithUsers.map((employee) => (
                      <TableRow key={employee.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedWithUsers.includes(employee.id)}
                            onCheckedChange={(checked) => {
                              if (checked) {
                                setSelectedWithUsers([...selectedWithUsers, employee.id]);
                              } else {
                                setSelectedWithUsers(selectedWithUsers.filter(id => id !== employee.id));
                              }
                            }}
                          />
                        </TableCell>
                        <TableCell className="font-medium">{employee.full_name}</TableCell>
                        <TableCell className="text-muted-foreground">{employee.email}</TableCell>
                        <TableCell>
                          <Badge variant="secondary">{employee.role}</Badge>
                        </TableCell>
                        <TableCell>{employee.last_login || 'Belum login'}</TableCell>
                        <TableCell>{employee.created_at ? new Date(employee.created_at).toLocaleDateString('id-ID') : '-'}</TableCell>
                        <TableCell>
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => handleResetPasswords([employee.id])}
                            disabled={isLoading}
                          >
                            Reset
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            </div>
          </TabsContent>
        </Tabs>
      </div>

      {/* Results Dialog */}
      <AlertDialog open={showResultsDialog} onOpenChange={setShowResultsDialog}>
        <AlertDialogContent className="max-w-2xl">
          <AlertDialogHeader>
            <AlertDialogTitle className="flex items-center gap-2">
              <CheckCircle className="h-5 w-5 text-success" />
              Operasi Berhasil
            </AlertDialogTitle>
            <AlertDialogDescription>
              Berikut adalah daftar user yang berhasil diproses. Simpan password dengan aman!
            </AlertDialogDescription>
          </AlertDialogHeader>

          <div className="max-h-[400px] overflow-auto border rounded-lg">
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/50">
                  <TableHead>Nama</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Password</TableHead>
                  <TableHead>Role</TableHead>
                  <TableHead className="w-12"></TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {createdUsers.map((user, index) => (
                  <TableRow key={index}>
                    <TableCell className="font-medium">{user.employee_name}</TableCell>
                    <TableCell>{user.employee_email}</TableCell>
                    <TableCell>
                      <code className="px-2 py-1 rounded bg-muted font-mono text-sm">
                        {user.password}
                      </code>
                    </TableCell>
                    <TableCell>
                      <Badge variant="outline">{user.role}</Badge>
                    </TableCell>
                    <TableCell>
                      <Button
                        size="icon"
                        variant="ghost"
                        onClick={() => handleCopyPassword(user.password)}
                      >
                        <Copy className="h-4 w-4" />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>

          <AlertDialogFooter>
            <Button variant="outline" onClick={handleExport}>
              <Download className="h-4 w-4 mr-2" />
              Export CSV
            </Button>
            <AlertDialogAction>Tutup</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </section>
  );
}
