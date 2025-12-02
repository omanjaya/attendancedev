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
    ChevronLeft,
    Filter,
} from 'lucide-react';
import { toast } from 'sonner';
import { useNavigate } from '@tanstack/react-router';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
} from '@/components/ui/dialog';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
    SheetFooter,
} from '@/components/ui/sheet';
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
import { useLocations } from '@/hooks/use-locations';
import {
    locationTypeLabels,
    locationTypeColors,
    type Location,
    type LocationType,
    type LocationFormData,
} from '@/types/location';
import { cn } from '@/lib/utils';
import { LocationMapPicker } from '@/components/maps/LocationMapPicker';

// Type badge component
function TypeBadge({ type }: { type?: LocationType }) {
    if (!type) return null;
    return (
        <Badge
            variant="outline"
            className="text-[10px] px-2 py-0.5 h-5"
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

// Location form dialog (reused but styled for mobile)
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
        await onSubmit(formData);
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="w-[90%] rounded-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{location ? 'Edit Lokasi' : 'Tambah Lokasi'}</DialogTitle>
                    <DialogDescription>
                        {location ? 'Perbarui data lokasi' : 'Tambahkan lokasi baru'}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4 py-2">
                    <div className="space-y-2">
                        <Label htmlFor="name">Nama Lokasi</Label>
                        <Input
                            id="name"
                            value={formData.name}
                            onChange={(e) => setFormData((prev) => ({ ...prev, name: e.target.value }))}
                            placeholder="Nama lokasi"
                            required
                        />
                    </div>
                    <div className="space-y-2">
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
                    <div className="space-y-2">
                        <Label htmlFor="address">Alamat</Label>
                        <Textarea
                            id="address"
                            value={formData.address}
                            onChange={(e) => setFormData((prev) => ({ ...prev, address: e.target.value }))}
                            placeholder="Alamat lengkap"
                            required
                            className="min-h-[80px]"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Pilih Lokasi di Map</Label>
                        <LocationMapPicker
                            latitude={formData.latitude}
                            longitude={formData.longitude}
                            onLocationChange={(lat, lng, address) => {
                                setFormData((prev) => ({
                                    ...prev,
                                    latitude: lat,
                                    longitude: lng,
                                    ...(address && !prev.address ? { address } : {}),
                                }));
                            }}
                            height="300px"
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-2">
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
                        <div className="space-y-2">
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
                    <div className="space-y-2">
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
                    <div className="space-y-2">
                        <Label htmlFor="wifi">WiFi SSID (Opsional)</Label>
                        <Input
                            id="wifi"
                            value={formData.wifi_ssid}
                            onChange={(e) =>
                                setFormData((prev) => ({ ...prev, wifi_ssid: e.target.value }))
                            }
                            placeholder="SSID WiFi"
                        />
                    </div>
                    <div className="flex items-center justify-between py-2">
                        <Label htmlFor="is_active" className="text-sm">Status Aktif</Label>
                        <Switch
                            id="is_active"
                            checked={formData.is_active}
                            onCheckedChange={(checked) =>
                                setFormData((prev) => ({ ...prev, is_active: checked }))
                            }
                        />
                    </div>
                    <div className="pt-4 flex gap-2 justify-end">
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={isLoading}>
                            {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Simpan
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export function MobileLocationsPage() {
    const navigate = useNavigate();
    const {
        isLoading,
        locations,
        fetchLocations,
        createLocation,
        updateLocation,
        deleteLocation,
        toggleStatus,
    } = useLocations();

    const [searchQuery, setSearchQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState<'all' | 'active' | 'inactive'>('all');
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingLocation, setEditingLocation] = useState<Location | null>(null);
    const [deletingLocation, setDeletingLocation] = useState<Location | null>(null);
    const [isFilterOpen, setIsFilterOpen] = useState(false);

    useEffect(() => {
        fetchLocations();
    }, [fetchLocations]);

    const handleSearch = () => {
        fetchLocations({
            is_active: statusFilter === 'active' ? true : statusFilter === 'inactive' ? false : undefined,
            search: searchQuery || undefined,
        });
    };

    useEffect(() => {
        handleSearch();
    }, [statusFilter]);

    const handleCreate = async (data: LocationFormData) => {
        if (data.radius_meters <= 0) {
            toast.error('Radius harus lebih dari 0 meter');
            return;
        }
        try {
            await createLocation(data);
            toast.success('Lokasi berhasil dibuat');
            setIsFormOpen(false); // Close form on success
        } catch (error: any) {
            console.error('Create location error:', error);
            toast.error(error.message || 'Gagal membuat lokasi');
        }
    };

    const handleUpdate = async (data: LocationFormData) => {
        if (data.radius_meters <= 0) {
            toast.error('Radius harus lebih dari 0 meter');
            return;
        }
        if (editingLocation) {
            try {
                await updateLocation(editingLocation.id, data);
                toast.success('Lokasi berhasil diperbarui');
                setEditingLocation(null);
                setIsFormOpen(false); // Close form on success
            } catch (error: any) {
                console.error('Update location error:', error);
                toast.error(error.message || 'Gagal memperbarui lokasi');
            }
        }
    };

    const handleDelete = async () => {
        if (deletingLocation) {
            try {
                await deleteLocation(deletingLocation.id);
                toast.success('Lokasi berhasil dihapus');
                setDeletingLocation(null);
            } catch (error: any) {
                console.error('Delete location error:', error);
                toast.error(error.message || 'Gagal menghapus lokasi');
            }
        }
    };

    const activeFiltersCount = statusFilter !== 'all' ? 1 : 0;

    return (
        <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-24">
            {/* Header Wrapper */}
            <div className="px-4 pt-3 pb-3 sticky top-0 z-20">
                <div className="bg-card/80 dark:bg-card/60 backdrop-blur-md rounded-3xl p-1.5 shadow-xl border border-border/40 dark:border-border/30">
                    <div className="bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-3 rounded-[20px] flex items-center gap-3 shadow-lg">
                        <button
                            onClick={() => navigate({ to: '/admin/dashboard' })}
                            className="p-2 -ml-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                        >
                            <ChevronLeft className="h-5 w-5 text-white" />
                        </button>
                        <h1 className="text-base font-bold text-white flex-1">Manajemen Lokasi</h1>
                        <button
                            onClick={() => setIsFilterOpen(true)}
                            className="relative p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                        >
                            <Filter className="h-5 w-5 text-white" />
                            {activeFiltersCount > 0 && (
                                <span className="absolute top-1.5 right-1.5 h-2.5 w-2.5 bg-red-500 rounded-full border-2 border-emerald-600" />
                            )}
                        </button>
                    </div>
                </div>
            </div>

            {/* Search Bar */}
            <div className="px-4 mb-4">
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        placeholder="Cari lokasi..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                        className="pl-9 bg-white dark:bg-gray-900/50 rounded-2xl border-border/50 shadow-sm"
                    />
                </div>
            </div>

            {/* Locations List */}
            <div className="px-4 space-y-3">
                {isLoading ? (
                    <div className="flex flex-col items-center justify-center py-12 gap-3">
                        <Loader2 className="h-8 w-8 animate-spin text-primary" />
                        <p className="text-sm text-muted-foreground">Memuat data...</p>
                    </div>
                ) : locations.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-12 gap-3 text-center">
                        <div className="h-16 w-16 rounded-full bg-muted flex items-center justify-center">
                            <MapPin className="h-8 w-8 text-muted-foreground" />
                        </div>
                        <div>
                            <p className="font-medium">Tidak ada lokasi</p>
                            <p className="text-sm text-muted-foreground">Tambahkan lokasi baru untuk memulai</p>
                        </div>
                    </div>
                ) : (
                    locations.map((location) => (
                        <div
                            key={location.id}
                            className={cn(
                                "bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-border/50 flex flex-col gap-3 transition-opacity",
                                !location.is_active && "opacity-75"
                            )}
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className="flex items-center gap-3">
                                    <div className={cn(
                                        "h-10 w-10 rounded-xl flex items-center justify-center shadow-sm",
                                        location.is_active ? "bg-gradient-to-br from-emerald-500 to-teal-600 text-white" : "bg-muted text-muted-foreground"
                                    )}>
                                        <MapPin className="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h3 className="font-semibold text-sm line-clamp-1">{location.name}</h3>
                                        <p className="text-xs text-muted-foreground line-clamp-1">{location.address}</p>
                                    </div>
                                </div>
                                <TypeBadge type={location.metadata?.type} />
                            </div>

                            <div className="grid grid-cols-2 gap-2 text-xs text-muted-foreground border-t border-border/50 pt-3 mt-1">
                                <div className="flex items-center gap-1.5">
                                    <Navigation className="h-3.5 w-3.5" />
                                    <span>Radius: {location.radius_meters}m</span>
                                </div>
                                <div className="flex items-center gap-1.5">
                                    <Users className="h-3.5 w-3.5" />
                                    <span>{location.employee_count || 0} Pegawai</span>
                                </div>
                                <div className="col-span-2 flex items-center gap-1.5">
                                    <CircleDot className="h-3.5 w-3.5" />
                                    <span className="font-mono text-[10px]">{location.latitude.toFixed(6)}, {location.longitude.toFixed(6)}</span>
                                </div>
                                {location.wifi_ssid && (
                                    <div className="col-span-2 flex items-center gap-1.5">
                                        <Wifi className="h-3.5 w-3.5" />
                                        <span>{location.wifi_ssid}</span>
                                    </div>
                                )}
                            </div>

                            <div className="flex items-center justify-end gap-2 pt-2">
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="ghost" size="sm" className="h-8 w-8 p-0 rounded-full ml-auto">
                                            <MoreHorizontal className="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="w-48">
                                        <DropdownMenuItem onClick={() => {
                                            setEditingLocation(location);
                                            setIsFormOpen(true);
                                        }}>
                                            <Edit className="mr-2 h-4 w-4" />
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => toggleStatus(location.id)}>
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
                                        <DropdownMenuItem
                                            className="text-destructive"
                                            onClick={() => setDeletingLocation(location)}
                                        >
                                            <Trash2 className="mr-2 h-4 w-4" />
                                            Hapus
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    ))
                )}
            </div>

            {/* FAB Add Location */}
            <Button
                onClick={() => {
                    setEditingLocation(null);
                    setIsFormOpen(true);
                }}
                className="fixed bottom-6 right-6 h-14 w-14 rounded-full shadow-xl bg-emerald-600 hover:bg-emerald-700 text-white z-50"
            >
                <Plus className="h-6 w-6" />
            </Button>

            {/* Filter Sheet */}
            <Sheet open={isFilterOpen} onOpenChange={setIsFilterOpen}>
                <SheetContent side="bottom" className="rounded-t-[20px]">
                    <SheetHeader>
                        <SheetTitle>Filter Lokasi</SheetTitle>
                        <SheetDescription>
                            Tampilkan lokasi berdasarkan status
                        </SheetDescription>
                    </SheetHeader>
                    <div className="py-6 space-y-6">
                        <div className="space-y-2">
                            <Label>Status</Label>
                            <Select
                                value={statusFilter}
                                onValueChange={(value) =>
                                    setStatusFilter(value as 'all' | 'active' | 'inactive')
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Status</SelectItem>
                                    <SelectItem value="active">Aktif</SelectItem>
                                    <SelectItem value="inactive">Nonaktif</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <SheetFooter>
                        <Button onClick={() => setIsFilterOpen(false)} className="w-full">
                            Terapkan Filter
                        </Button>
                    </SheetFooter>
                </SheetContent>
            </Sheet>

            {/* Location Form Dialog */}
            <LocationFormDialog
                open={isFormOpen}
                onOpenChange={(open) => {
                    setIsFormOpen(open);
                    if (!open) setEditingLocation(null);
                }}
                location={editingLocation}
                onSubmit={editingLocation ? handleUpdate : handleCreate}
                isLoading={isLoading}
            />

            {/* Delete Confirmation */}
            <AlertDialog open={!!deletingLocation} onOpenChange={() => setDeletingLocation(null)}>
                <AlertDialogContent className="w-[90%] rounded-2xl">
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus Lokasi?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Yakin ingin menghapus <strong>{deletingLocation?.name}</strong>?
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter className="flex-row gap-2 justify-end">
                        <AlertDialogCancel className="mt-0">Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleDelete}
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
