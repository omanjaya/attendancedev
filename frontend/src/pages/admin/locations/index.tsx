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
} from 'lucide-react';
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
import { useLocations } from '@/hooks/use-locations';
import {
  locationTypeLabels,
  locationTypeColors,
  type Location,
  type LocationType,
  type LocationFormData,
  type LocationStatistics,
} from '@/types/location';

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
}: {
  location: Location;
  onEdit: () => void;
  onDelete: () => void;
  onToggle: () => void;
}) {
  return (
    <Card className={`transition-all ${!location.is_active ? 'opacity-60' : ''}`}>
      <CardHeader className="pb-3">
        <div className="flex items-start justify-between">
          <div className="flex items-center gap-3">
            <div
              className={`flex h-12 w-12 items-center justify-center rounded-xl ${
                location.is_active
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
    await onSubmit(formData);
    onOpenChange(false);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[600px]">
        <DialogHeader>
          <DialogTitle>{location ? 'Edit Lokasi' : 'Tambah Lokasi Baru'}</DialogTitle>
          <DialogDescription>
            {location
              ? 'Edit informasi lokasi yang ada'
              : 'Tambahkan lokasi baru untuk absensi'}
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className="grid gap-4 py-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="grid gap-2">
                <Label htmlFor="name">Nama Lokasi</Label>
                <Input
                  id="name"
                  value={formData.name}
                  onChange={(e) => setFormData((prev) => ({ ...prev, name: e.target.value }))}
                  placeholder="Gedung Utama"
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

            <div className="grid grid-cols-2 gap-4">
              <div className="grid gap-2">
                <Label htmlFor="latitude">Latitude</Label>
                <Input
                  id="latitude"
                  type="number"
                  step="0.000001"
                  value={formData.latitude}
                  onChange={(e) =>
                    setFormData((prev) => ({ ...prev, latitude: parseFloat(e.target.value) }))
                  }
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
                  onChange={(e) =>
                    setFormData((prev) => ({ ...prev, longitude: parseFloat(e.target.value) }))
                  }
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
                  onChange={(e) =>
                    setFormData((prev) => ({ ...prev, radius_meters: parseInt(e.target.value) }))
                  }
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
          <DialogFooter>
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

export default function LocationsPage() {
  const {
    isLoading,
    locations,
    fetchLocations,
    createLocation,
    updateLocation,
    deleteLocation,
    toggleStatus,
    getStatistics,
  } = useLocations();

  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<'all' | 'active' | 'inactive'>('all');
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingLocation, setEditingLocation] = useState<Location | null>(null);
  const [deletingLocation, setDeletingLocation] = useState<Location | null>(null);
  const [stats, setStats] = useState<LocationStatistics | null>(null);

  useEffect(() => {
    fetchLocations();
    loadStats();
  }, [fetchLocations]);

  const loadStats = async () => {
    const statistics = await getStatistics();
    setStats(statistics);
  };

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
    await createLocation(data);
    loadStats();
  };

  const handleUpdate = async (data: LocationFormData) => {
    if (editingLocation) {
      await updateLocation(editingLocation.id, data);
      setEditingLocation(null);
      loadStats();
    }
  };

  const handleDelete = async () => {
    if (deletingLocation) {
      await deleteLocation(deletingLocation.id);
      setDeletingLocation(null);
      loadStats();
    }
  };

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
    <div className="space-y-6 p-6">
      {/* Page Header */}
      <PageHeader
        title="Manajemen Lokasi"
        description="Kelola lokasi untuk verifikasi absensi GPS"
        icon={MapPin}
        actions={
          <Button
            onClick={() => {
              setEditingLocation(null);
              setIsFormOpen(true);
            }}
          >
            <Plus className="mr-2 h-4 w-4" />
            Tambah Lokasi
          </Button>
        }
      />

      {/* Stats */}
      {stats && <StatsGrid items={statsItems} columns={4} variant="cards" />}

      {/* Filters */}
      <Card>
        <CardContent className="p-4">
          <div className="flex flex-col gap-4 md:flex-row md:items-center">
            <div className="flex flex-1 gap-2">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  placeholder="Cari nama atau alamat..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                  className="pl-9"
                />
              </div>
              <Button variant="outline" onClick={handleSearch}>
                Cari
              </Button>
            </div>
            <Select
              value={statusFilter}
              onValueChange={(value) => setStatusFilter(value as 'all' | 'active' | 'inactive')}
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
      {isLoading ? (
        <div className="flex items-center justify-center py-12">
          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
        </div>
      ) : locations.length === 0 ? (
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
                setEditingLocation(null);
                setIsFormOpen(true);
              }}
            >
              <Plus className="mr-2 h-4 w-4" />
              Tambah Lokasi
            </Button>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {locations.map((location) => (
            <LocationCard
              key={location.id}
              location={location}
              onEdit={() => {
                setEditingLocation(location);
                setIsFormOpen(true);
              }}
              onDelete={() => setDeletingLocation(location)}
              onToggle={() => toggleStatus(location.id)}
            />
          ))}
        </div>
      )}

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
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Hapus Lokasi?</AlertDialogTitle>
            <AlertDialogDescription>
              Anda yakin ingin menghapus lokasi <strong>{deletingLocation?.name}</strong>?
              {deletingLocation?.employee_count && deletingLocation.employee_count > 0 && (
                <span className="mt-2 block text-warning">
                  Perhatian: Lokasi ini memiliki {deletingLocation.employee_count} pegawai yang
                  terdaftar.
                </span>
              )}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Batal</AlertDialogCancel>
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
