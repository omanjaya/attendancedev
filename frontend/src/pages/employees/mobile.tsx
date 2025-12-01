import { useState } from 'react';
import { useNavigate } from '@tanstack/react-router';
import {
    ChevronLeft,
    Search,
    Plus,
    User,
    Phone,
    Mail,
    Building2,
    MoreVertical,
    Filter
} from 'lucide-react';
import { useEmployees } from '@/hooks/use-employees';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { LoadingState } from '@/components/states';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export function MobileEmployeesPage() {
    const navigate = useNavigate();
    const [searchQuery, setSearchQuery] = useState('');

    const { data: employeesData, isLoading } = useEmployees({
        search: searchQuery,
        per_page: 20,
    });

    const employees = employeesData?.data || [];

    const getInitials = (name: string) => {
        if (!name) return 'U';
        return name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'active':
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
            case 'inactive':
                return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
            case 'leave':
                return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
            default:
                return 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400';
        }
    };

    return (
        <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-20 relative">
            {/* Header */}
            <div className="px-4 pt-3 pb-3 sticky top-0 z-20">
                <div className="bg-card/80 dark:bg-card/60 backdrop-blur-md rounded-3xl p-1.5 shadow-xl border border-border/40 dark:border-border/30">
                    <div className="bg-gradient-to-r from-blue-600 to-cyan-600 dark:from-blue-900 dark:to-cyan-800 px-4 py-3 rounded-[20px] flex items-center gap-3 shadow-lg">
                        <button
                            onClick={() => navigate({ to: '/dashboard' })}
                            className="p-2 -ml-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                        >
                            <ChevronLeft className="h-5 w-5 text-white" />
                        </button>
                        <h1 className="text-base font-bold text-white flex-1">Karyawan</h1>
                        <button className="p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95">
                            <Filter className="h-5 w-5 text-white" />
                        </button>
                    </div>
                </div>
            </div>

            {/* Search Bar */}
            <div className="px-4 mt-2 mb-4">
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Cari karyawan..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="pl-9 bg-white dark:bg-gray-900 border-border/50 rounded-2xl shadow-sm h-11"
                    />
                </div>
            </div>

            {/* Employee List */}
            <div className="px-4 space-y-3">
                {isLoading ? (
                    <div className="flex justify-center py-12">
                        <LoadingState message="Memuat data karyawan..." size="sm" />
                    </div>
                ) : employees.length > 0 ? (
                    employees.map((employee) => (
                        <div
                            key={employee.id}
                            onClick={() => navigate({ to: `/employees/${employee.id}` })}
                            className="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-border/50 active:scale-[0.99] transition-transform cursor-pointer"
                        >
                            <div className="flex items-start gap-3">
                                <Avatar className="h-12 w-12 border border-border/50">
                                    <AvatarImage src={employee.avatar || undefined} alt={employee.name} />
                                    <AvatarFallback className="bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 font-medium">
                                        {getInitials(employee.name)}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center justify-between mb-1">
                                        <h3 className="font-bold text-foreground truncate pr-2">{employee.name}</h3>
                                        <Badge variant="secondary" className={getStatusColor(employee.status)}>
                                            {employee.status}
                                        </Badge>
                                    </div>
                                    <p className="text-xs text-muted-foreground mb-2">{employee.position}</p>

                                    <div className="flex items-center gap-3 text-xs text-muted-foreground">
                                        <div className="flex items-center gap-1">
                                            <Building2 className="h-3 w-3" />
                                            <span className="truncate max-w-[80px]">{employee.department}</span>
                                        </div>
                                        {employee.phone && (
                                            <div className="flex items-center gap-1">
                                                <Phone className="h-3 w-3" />
                                                <span>{employee.phone}</span>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))
                ) : (
                    <div className="text-center py-12 text-muted-foreground">
                        <User className="h-12 w-12 mx-auto mb-3 opacity-20" />
                        <p>Tidak ada karyawan ditemukan</p>
                    </div>
                )}
            </div>

            {/* FAB */}
            <Button
                onClick={() => navigate({ to: '/employees/create' })}
                className="fixed bottom-6 right-6 h-14 w-14 rounded-full shadow-xl bg-blue-600 hover:bg-blue-700 text-white p-0 z-30"
            >
                <Plus className="h-6 w-6" />
            </Button>
        </div>
    );
}
