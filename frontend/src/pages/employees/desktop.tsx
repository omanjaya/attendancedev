import { useState } from 'react';
import {
    Plus,
    Search,
    Filter,
    MoreHorizontal,
    FileText,
    Download,
    Users,
    Building2,
    Mail,
    Phone
} from 'lucide-react';
import { Link } from '@tanstack/react-router';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { PageHeader } from '@/components/shared';
import { useEmployees } from '@/hooks/use-employees';
import { LoadingState } from '@/components/states';

export function DesktopEmployeesPage() {
    const [searchQuery, setSearchQuery] = useState('');
    const { data: employeesData, isLoading } = useEmployees({
        search: searchQuery,
        per_page: 10,
    });

    const employees = employeesData?.data || [];

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'active':
                return <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-100">Aktif</Badge>;
            case 'inactive':
                return <Badge variant="destructive">Tidak Aktif</Badge>;
            case 'leave':
                return <Badge variant="secondary" className="bg-amber-100 text-amber-700">Cuti</Badge>;
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    return (
        <div className="space-y-6 p-6">
            <PageHeader
                title="Karyawan"
                description="Kelola data karyawan, departemen, dan jabatan"
                icon={Users}
                actions={
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm">
                            <Download className="mr-2 h-4 w-4" />
                            Export
                        </Button>
                        <Button size="sm" asChild>
                            <Link to="/employees/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Tambah Karyawan
                            </Link>
                        </Button>
                    </div>
                }
            />

            {/* Filters */}
            <div className="flex items-center justify-between gap-4">
                <div className="flex items-center gap-2 flex-1">
                    <div className="relative w-full max-w-sm">
                        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="search"
                            placeholder="Cari nama, NIP, atau email..."
                            className="pl-8 w-full"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                        />
                    </div>
                    <Button variant="outline" size="icon">
                        <Filter className="h-4 w-4" />
                    </Button>
                </div>
            </div>

            {/* Employees Table */}
            <Card>
                <CardHeader>
                    <CardTitle>Daftar Karyawan</CardTitle>
                    <CardDescription>Total {employeesData?.meta?.total || 0} karyawan terdaftar</CardDescription>
                </CardHeader>
                <CardContent>
                    {isLoading ? (
                        <div className="py-8">
                            <LoadingState message="Memuat data karyawan..." />
                        </div>
                    ) : employees.length === 0 ? (
                        <div className="text-center py-12 text-muted-foreground">
                            Tidak ada data karyawan ditemukan
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Karyawan</TableHead>
                                    <TableHead>Kontak</TableHead>
                                    <TableHead>Departemen & Jabatan</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {employees.map((employee) => (
                                    <TableRow key={employee.id}>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <Avatar>
                                                    <AvatarImage src={employee.avatar || undefined} alt={employee.name} />
                                                    <AvatarFallback>{getInitials(employee.name)}</AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <div className="font-medium">{employee.name}</div>
                                                    <div className="text-xs text-muted-foreground">{employee.nip}</div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="space-y-1 text-sm">
                                                <div className="flex items-center gap-2">
                                                    <Mail className="h-3 w-3 text-muted-foreground" />
                                                    {employee.email}
                                                </div>
                                                {employee.phone && (
                                                    <div className="flex items-center gap-2">
                                                        <Phone className="h-3 w-3 text-muted-foreground" />
                                                        {employee.phone}
                                                    </div>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="space-y-1">
                                                <div className="font-medium">{employee.position}</div>
                                                <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                                    <Building2 className="h-3 w-3" />
                                                    {employee.department}
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>{getStatusBadge(employee.status)}</TableCell>
                                        <TableCell className="text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="icon">
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link to={`/employees/${employee.id}`}>
                                                            Lihat Detail
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <Link to={`/employees/${employee.id}/edit`}>
                                                            Edit Data
                                                        </Link>
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
