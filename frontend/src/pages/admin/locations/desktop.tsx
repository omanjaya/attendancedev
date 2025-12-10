import { useState, useEffect } from 'react';
import {
    MapPin,
    Plus,
    Search,
    MoreHorizontal,
    Edit,
    Trash2,
    Loader2,
    Wifi,
    Users,
    Navigation,
    CircleDot,
    Power,
    PowerOff,
    Map,
    UserPlus,
} from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';
import { getEmployees } from '@/lib/api/employees';
import type { Employee } from '@/types';
import { toast } from 'sonner';
import { PageHeader, StatsGrid, type StatItem } from '@/components/shared';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Switch } from '@/components/ui/switch';
import { useLocationsPage } from '@/hooks/use-locations-page';
import {
    locationTypeLabels,
    locationTypeColors,
    type Location,
    type LocationType,
    type LocationFormData,
    type LocationStatistics,
} from '@/types/location';
import { LocationMapPickerWithRadius } from '@/components/maps/LocationMapPickerWithRadius';

// Type badge component
function TypeBadge({ type }: { type?: LocationType }) {
    if (!type) return null;
    return (
        <Badge
            variant="outline"
            style={{
                borderColor: locationTypeColors[type],
                color: locationTypeColors[type],
                backgroundColor: `${locationTypeColors[type]}10`,
            }}
        >
            {locationTypeLabels[type]}
        </Badge>
    );
}

// Location card component
function LocationCard({
    location,
    onEdit,
    onDelete,
    onToggle,
    onAssign,
}: {
    location: Location;
    onEdit: () => void;
    onDelete: () => void;
    onToggle: () => void;
    onAssign: () => void;
}) {
    return (
        <Card className={`transition-all ${!location.is_active ? 'opacity-60' : ''}`}>
            <CardHeader className="pb-3">
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-3">
                        <div
                            className={`flex h-12 w-12 items-center justify-center rounded-xl ${location.is_active
                                ? 'bg-gradient-to-br from-primary to-primary/70'
                                : 'bg-muted-foreground'
                                }`}
                        >
                            <MapPin className="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <CardTitle className="text-lg">{location.name}</CardTitle>
                            <CardDescription className="line-clamp-1">{location.address}</CardDescription>
                        </div>
                    </div>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon">
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem onClick={onEdit}>
                                <Edit className="mr-2 h-4 w-4" />
                                Edit
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={onAssign}>
                                <UserPlus className="mr-2 h-4 w-4" />
                                Assign Pegawai
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={onToggle}>
                                {location.is_active ? (
                                    <>
                                        <PowerOff className="mr-2 h-4 w-4" />
                                        Nonaktifkan
                                    </>
                                ) : (
                                    <>
                                        <Power className="mr-2 h-4 w-4" />
                                        Aktifkan
                                    </>
                                )}
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem className="text-destructive" onClick={onDelete}>
                                <Trash2 className="mr-2 h-4 w-4" />
                                Hapus
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </CardHeader>
            <CardContent>
                <div className="space-y-3">
                    <div className="flex flex-wrap gap-2">
                        <TypeBadge type={location.metadata?.type} />
                        <Badge variant={location.is_active ? 'default' : 'secondary'}>
                            {location.is_active ? 'Aktif' : 'Nonaktif'}
                        </Badge>
                    </div>

                    <div className="grid grid-cols-2 gap-3 text-sm">
                        <div className="flex items-center gap-2 text-muted-foreground">
                            <Navigation className="h-4 w-4" />
                            <span>Radius: {location.radius_meters}m</span>
                        </div>
                        <div className="flex items-center gap-2 text-muted-foreground">
                            <Users className="h-4 w-4" />
                            <span>{location.employee_count || 0} Pegawai</span>
                        </div>
                        {location.wifi_ssid && (
                            <div className="col-span-2 flex items-center gap-2 text-muted-foreground">
                                <Wifi className="h-4 w-4" />
                                <span>{location.wifi_ssid}</span>
                            </div>
                        )}
                    </div>

                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                        <CircleDot className="h-3 w-3" />
                        <span>
                            {location.latitude.toFixed(6)}, {location.longitude.toFixed(6)}
                        </span>
                    </div>

                    {location.metadata?.description && (
                        <p className="text-sm text-muted-foreground line-clamp-2">
                            {location.metadata.description}
                        </p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

// Location form dialog
function LocationFormDialog({
    open,
    onOpenChange,
    location,
    onSubmit,
    isLoading,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    location?: Location | null;
    onSubmit: (data: LocationFormData) => Promise<void>;
    isLoading: boolean;
}) {
    const [formData, setFormData] = useState<LocationFormData>({
        name: '',
        address: '',
        latitude: -6.2088,
        longitude: 106.8456,
        radius_meters: 100,
        wifi_ssid: '',
        is_active: true,
        description: '',
        type: 'office',
    });

    useEffect(() => {
        if (location) {
            setFormData({
                name: location.name,
                address: location.address,
                latitude: location.latitude,
                longitude: location.longitude,
                radius_meters: location.radius_meters,
                wifi_ssid: location.wifi_ssid || '',
                is_active: location.is_active,
                description: location.metadata?.description || '',
                type: location.metadata?.type || 'office',
            });
        } else {
            setFormData({
                name: '',
                address: '',
                latitude: -6.2088,
                longitude: 106.8456,
                radius_meters: 100,
                wifi_ssid: '',
                is_active: true,
                description: '',
                type: 'office',
            });
        }
    }, [location, open]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        // If type is remote (WFA), set default values for location-specific fields
        const submitData: LocationFormData = formData.type === 'remote'
            ? {
                ...formData,
                name: formData.name || 'WFA',
                address: 'Remote Work',
                latitude: 0,
                longitude: 0,
                radius_meters: 9999999, // Set to large number for WFA (no radius limit)
                wifi_ssid: '',
            }
            : formData;

        await onSubmit(submitData);
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[650px] max-h-[90vh] flex flex-col">
                <DialogHeader className="flex-shrink-0">
                    <DialogTitle>{location ? 'Edit Lokasi' : 'Tambah Lokasi Baru'}</DialogTitle>
                    <DialogDescription>
                        {location
                            ? 'Edit informasi lokasi yang ada'
                            : 'Tambahkan lokasi baru untuk absensi'}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="flex flex-col flex-1 overflow-hidden">
                    <div className="grid gap-4 py-4 overflow-y-auto pr-1">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama Lokasi</Label>
                                <Input
                                    id="name"
                                    value={formData.name}
                                    onChange={(e) => setFormData((prev) => ({ ...prev, name: e.target.value }))}
                                    placeholder={formData.type === 'remote' ? 'WFA' : 'Gedung Utama'}
                                    required
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="type">Tipe Lokasi</Label>
                                <Select
                                    value={formData.type}
                                    onValueChange={(value: LocationType) =>
                                        setFormData((prev) => ({ ...prev, type: value }))
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih tipe" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(locationTypeLabels).map(([value, label]) => (
                                            <SelectItem key={value} value={value}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        {formData.type !== 'remote' && (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="address">Alamat</Label>
                                    <Textarea
                                        id="address"
                                        value={formData.address}
                                        onChange={(e) => setFormData((prev) => ({ ...prev, address: e.target.value }))}
                                        placeholder="Jl. Pendidikan No. 1, Jakarta"
                                        required
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Pilih Lokasi di Map</Label>
                                    <LocationMapPickerWithRadius
                                        latitude={formData.latitude}
                                        longitude={formData.longitude}
                                        radius={formData.radius_meters}
                                        autoFocus={true}
                                        onLocationChange={(lat, lng, address) => {
                                            setFormData((prev) => ({
                                                ...prev,
                                                latitude: lat,
                                                longitude: lng,
                                                // Always auto-fill address from search result
                                                ...(address ? { address } : {}),
                                            }));
                                        }}
                                        height="300px"
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="latitude">Latitude</Label>
                                        <Input
                                            id="latitude"
                                            type="number"
                                            step="0.000001"
                                            value={formData.latitude}
                                            onChange={(e) => {
                                                const val = parseFloat(e.target.value);
                                                setFormData((prev) => ({ ...prev, latitude: isNaN(val) ? 0 : val }));
                                            }}
                                            required
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="longitude">Longitude</Label>
                                        <Input
                                            id="longitude"
                                            type="number"
                                            step="0.000001"
                                            value={formData.longitude}
                                            onChange={(e) => {
                                                const val = parseFloat(e.target.value);
                                                setFormData((prev) => ({ ...prev, longitude: isNaN(val) ? 0 : val }));
                                            }}
                                            required
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="radius">Radius (meter)</Label>
                                        <Input
                                            id="radius"
                                            type="number"
                                            value={formData.radius_meters}
                                            onChange={(e) => {
                                                const val = parseInt(e.target.value);
                                                setFormData((prev) => ({ ...prev, radius_meters: isNaN(val) ? 0 : val }));
                                            }}
                                            required
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="wifi">WiFi SSID (Opsional)</Label>
                                        <Input
                                            id="wifi"
                                            value={formData.wifi_ssid}
                                            onChange={(e) =>
                                                setFormData((prev) => ({ ...prev, wifi_ssid: e.target.value }))
                                            }
                                            placeholder="SCHOOL_WIFI"
                                        />
                                    </div>
                                </div>
                            </>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="description">Deskripsi (Opsional)</Label>
                            <Textarea
                                id="description"
                                value={formData.description}
                                onChange={(e) =>
                                    setFormData((prev) => ({ ...prev, description: e.target.value }))
                                }
                                placeholder="Deskripsi lokasi..."
                            />
                        </div>

                        <div className="flex items-center justify-between">
                            <Label htmlFor="is_active">Status Aktif</Label>
                            <Switch
                                id="is_active"
                                checked={formData.is_active}
                                onCheckedChange={(checked) =>
                                    setFormData((prev) => ({ ...prev, is_active: checked }))
                                }
                            />
                        </div>
                    </div>
                    <DialogFooter className="flex-shrink-0 mt-4">
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={isLoading}>
                            {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            {location ? 'Simpan' : 'Tambah'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

// Assign Employees Dialog
function AssignEmployeesDialog({
    open,
    onOpenChange,
    location,
    onSubmit,
    isLoading,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    location: Location | null;
    onSubmit: (locationId: string, employeeIds: string[]) => Promise<void>;
    isLoading: boolean;
}) {
    const [employees, setEmployees] = useState<(Employee & { location_id?: string | number })[]>([]);
    const [selectedIds, setSelectedIds] = useState<string[]>([]);
    const [searchQuery, setSearchQuery] = useState('');
    const [showAssignedOnly, setShowAssignedOnly] = useState(false);
    const [isFetching, setIsFetching] = useState(false);

    useEffect(() => {
        if (open && location) {
            fetchEmployees();
            setSelectedIds([]);
        }
    }, [open, location]);

    const fetchEmployees = async () => {
        setIsFetching(true);
        try {
            const response = await getEmployees({ per_page: 1000 }); // Fetch many
            setEmployees(response.data as (Employee & { location_id?: string | number })[]);

            // Pre-select employees who are already in this location
            // Note: API response might not include location_id unless we request it or it's in the model
            // If it's not in the response, we can't pre-select. 
            // Assuming getEmployees returns location_id if available.
            if (location) {
                const current = response.data
                    .filter((e: any) => String(e.location_id) === String(location.id))
                    .map(e => String(e.id));
                setSelectedIds(current);
            }
        } catch (error) {
            console.error('Failed to fetch employees', error);
            toast.error('Gagal memuat data pegawai');
        } finally {
            setIsFetching(false);
        }
    };

    const handleSubmit = async () => {
        if (location) {
            await onSubmit(location.id, selectedIds);
            onOpenChange(false);
        }
    };

    const filteredEmployees = employees.filter(e => {
        const matchesSearch = e.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            (e.employee_id && e.employee_id.toLowerCase().includes(searchQuery.toLowerCase()));

        if (showAssignedOnly) {
            return matchesSearch && selectedIds.includes(String(e.id));
        }
        return matchesSearch;
    });

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[500px] h-[600px] flex flex-col">
                <DialogHeader>
                    <DialogTitle>Assign Pegawai</DialogTitle>
                    <DialogDescription>
                        Pilih pegawai untuk ditugaskan ke lokasi <strong>{location?.name}</strong>.
                    </DialogDescription>
                </DialogHeader>

                <div className="py-4 space-y-4 flex-1 flex flex-col min-h-0">
                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Cari pegawai..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="pl-9"
                        />
                    </div>

                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="show-assigned"
                            checked={showAssignedOnly}
                            onCheckedChange={(checked) => setShowAssignedOnly(checked as boolean)}
                        />
                        <Label htmlFor="show-assigned" className="text-sm font-normal cursor-pointer">
                            Tampilkan yang ditugaskan saja
                        </Label>
                    </div>

                    <div className="border rounded-md flex-1 overflow-y-auto p-2 space-y-2">
                        {isFetching ? (
                            <div className="flex justify-center py-4">
                                <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                            </div>
                        ) : filteredEmployees.length === 0 ? (
                            <p className="text-center text-muted-foreground py-4">Tidak ada pegawai ditemukan</p>
                        ) : (
                            filteredEmployees.map(employee => (
                                <div key={employee.id} className="flex items-center space-x-3 p-2 hover:bg-accent rounded-md">
                                    <Checkbox
                                        id={`emp-${employee.id}`}
                                        checked={selectedIds.includes(String(employee.id))}
                                        onCheckedChange={(checked) => {
                                            const id = String(employee.id);
                                            if (checked) {
                                                setSelectedIds(prev => [...prev, id]);
                                            } else {
                                                setSelectedIds(prev => prev.filter(i => i !== id));
                                            }
                                        }}
                                    />
                                    <div className="flex-1">
                                        <Label htmlFor={`emp-${employee.id}`} className="cursor-pointer font-medium">
                                            {employee.name}
                                        </Label>
                                        <p className="text-xs text-muted-foreground">{employee.employee_id}</p>
                                    </div>
                                    {employee.location_id && String(employee.location_id) !== String(location?.id) && (
                                        <Badge variant="outline" className="text-xs">
                                            Pindah Lokasi
                                        </Badge>
                                    )}
                                </div>
                            ))
                        )}
                    </div>

                    <div className="text-sm text-muted-foreground text-right">
                        {selectedIds.length} pegawai dipilih
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)}>Batal</Button>
                    <Button onClick={handleSubmit} disabled={isLoading}>
                        {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Simpan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export function DesktopLocationsPage() {
    // Use shared hook for common logic
    const logic = useLocationsPage();

    // Desktop-specific state
    const [stats, setStats] = useState<LocationStatistics | null>(null);

    const loadStats = async () => {
        const statistics = await logic.getStatistics();
        setStats(statistics);
    };

    useEffect(() => {
        loadStats();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const statsItems: StatItem[] = stats ? [
        {
            label: 'Total Lokasi',
            value: stats.total_locations,
            icon: Map,
            color: 'primary',
        },
        {
            label: 'Aktif',
            value: stats.active_locations,
            icon: Power,
            color: 'success',
        },
        {
            label: 'Total Pegawai',
            value: stats.total_employees_assigned,
            icon: Users,
            color: 'info',
        },
        {
            label: 'Dengan WiFi',
            value: stats.locations_with_wifi,
            icon: Wifi,
            color: 'warning',
        },
    ] : [];

    return (
        <div className="space-y-4 p-4 sm:space-y-6 sm:p-6">
            {/* Page Header */}
            <PageHeader
                title="Manajemen Lokasi"
                description="Kelola lokasi untuk verifikasi absensi GPS"
                icon={MapPin}
                actions={
                    <Button
                        onClick={() => {
                            logic.setEditingLocation(null);
                            logic.setIsFormOpen(true);
                        }}
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        Tambah Lokasi
                    </Button>
                }
            />

            {/* Stats */}
            {stats && <StatsGrid stats={statsItems} columns={4} variant="cards" />}

            {/* Filters */}
            <Card>
                <CardContent className="p-4">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center">
                        <div className="flex flex-1 gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Cari nama atau alamat..."
                                    value={logic.searchQuery}
                                    onChange={(e) => logic.setSearchQuery(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && logic.handleSearch()}
                                    className="pl-9"
                                />
                            </div>
                            <Button variant="outline" onClick={logic.handleSearch}>
                                Cari
                            </Button>
                        </div>
                        <Select
                            value={logic.statusFilter}
                            onValueChange={(value) => logic.setStatusFilter(value as 'all' | 'active' | 'inactive')}
                        >
                            <SelectTrigger className="w-[150px]">
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                <SelectItem value="active">Aktif</SelectItem>
                                <SelectItem value="inactive">Nonaktif</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            {/* Locations Grid */}
            {logic.isLoading ? (
                <div className="flex items-center justify-center py-12">
                    <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                </div>
            ) : logic.locations.length === 0 ? (
                <Card>
                    <CardContent className="py-12 text-center">
                        <MapPin className="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 className="mt-4 text-lg font-medium">Belum ada lokasi</h3>
                        <p className="mt-2 text-muted-foreground">
                            Tambahkan lokasi baru untuk memulai verifikasi absensi
                        </p>
                        <Button
                            className="mt-4"
                            onClick={() => {
                                logic.setEditingLocation(null);
                                logic.setIsFormOpen(true);
                            }}
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            Tambah Lokasi
                        </Button>
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {logic.locations.map((location: Location) => (
                        <LocationCard
                            key={location.id}
                            location={location}
                            onEdit={() => {
                                logic.setEditingLocation(location);
                                logic.setIsFormOpen(true);
                            }}
                            onDelete={() => logic.setDeletingLocation(location)}
                            onToggle={() => logic.handleToggleStatus(location)}
                            onAssign={() => logic.setAssigningLocation(location)}
                        />
                    ))}
                </div>
            )}

            {/* Location Form Dialog */}
            <LocationFormDialog
                open={logic.isFormOpen}
                onOpenChange={(open) => {
                    logic.setIsFormOpen(open);
                    if (!open) logic.setEditingLocation(null);
                }}
                location={logic.editingLocation}
                onSubmit={(data) => logic.editingLocation ? logic.handleUpdate(data, loadStats) : logic.handleCreate(data, loadStats)}
                isLoading={logic.isLoading}
            />

            {/* Assign Employees Dialog */}
            <AssignEmployeesDialog
                open={!!logic.assigningLocation}
                onOpenChange={(open) => !open && logic.setAssigningLocation(null)}
                location={logic.assigningLocation}
                onSubmit={(id, ids) => logic.handleAssignEmployees(id, ids, () => { logic.fetchLocations(); loadStats(); })}
                isLoading={logic.isLoading}
            />

            {/* Delete Confirmation */}
            <AlertDialog open={!!logic.deletingLocation} onOpenChange={() => logic.setDeletingLocation(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus Lokasi?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Anda yakin ingin menghapus lokasi <strong>{logic.deletingLocation?.name}</strong>?
                            {logic.deletingLocation?.employee_count && logic.deletingLocation.employee_count > 0 && (
                                <span className="mt-2 block text-warning">
                                    Perhatian: Lokasi ini memiliki {logic.deletingLocation.employee_count} pegawai yang
                                    terdaftar.
                                </span>
                            )}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => logic.handleDelete(loadStats)}
                            className="bg-destructive hover:bg-destructive/90"
                        >
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
