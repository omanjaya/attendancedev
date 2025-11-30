import { useState, useCallback } from 'react';
import type {
  Location,
  LocationFormData,
  LocationStatistics,
} from '@/types/location';

// Mock data
const mockLocations: Location[] = [
  {
    id: '1',
    name: 'Gedung Utama',
    address: 'Jl. Pendidikan No. 1, Jakarta Selatan',
    latitude: -6.2088,
    longitude: 106.8456,
    radius_meters: 100,
    wifi_ssid: 'SCHOOL_MAIN',
    is_active: true,
    metadata: {
      description: 'Gedung utama untuk kegiatan belajar mengajar',
      type: 'school',
      capacity: 500,
      contact_person: 'Pak Ahmad',
      contact_phone: '021-7654321',
    },
    employee_count: 45,
    created_at: '2023-01-01T00:00:00',
    updated_at: '2024-03-01T00:00:00',
  },
  {
    id: '2',
    name: 'Gedung Laboratorium',
    address: 'Jl. Pendidikan No. 1A, Jakarta Selatan',
    latitude: -6.2092,
    longitude: 106.8460,
    radius_meters: 50,
    wifi_ssid: 'SCHOOL_LAB',
    is_active: true,
    metadata: {
      description: 'Gedung laboratorium IPA dan Komputer',
      type: 'school',
      capacity: 200,
      contact_person: 'Bu Siti',
      contact_phone: '021-7654322',
    },
    employee_count: 12,
    created_at: '2023-01-15T00:00:00',
    updated_at: '2024-02-15T00:00:00',
  },
  {
    id: '3',
    name: 'Kantor Administrasi',
    address: 'Jl. Pendidikan No. 2, Jakarta Selatan',
    latitude: -6.2085,
    longitude: 106.8450,
    radius_meters: 75,
    wifi_ssid: 'SCHOOL_ADMIN',
    is_active: true,
    metadata: {
      description: 'Kantor administrasi dan TU',
      type: 'office',
      capacity: 30,
      contact_person: 'Pak Budi',
      contact_phone: '021-7654323',
    },
    employee_count: 8,
    created_at: '2023-02-01T00:00:00',
    updated_at: '2024-01-20T00:00:00',
  },
  {
    id: '4',
    name: 'Lapangan Olahraga',
    address: 'Jl. Pendidikan No. 1B, Jakarta Selatan',
    latitude: -6.2095,
    longitude: 106.8465,
    radius_meters: 150,
    is_active: true,
    metadata: {
      description: 'Area olahraga outdoor',
      type: 'school',
      capacity: 300,
    },
    employee_count: 3,
    created_at: '2023-03-01T00:00:00',
    updated_at: '2024-03-01T00:00:00',
  },
  {
    id: '5',
    name: 'Gudang Perlengkapan',
    address: 'Jl. Pendidikan No. 3, Jakarta Selatan',
    latitude: -6.2080,
    longitude: 106.8445,
    radius_meters: 30,
    is_active: false,
    metadata: {
      description: 'Gudang penyimpanan perlengkapan sekolah',
      type: 'warehouse',
      capacity: 50,
    },
    employee_count: 0,
    created_at: '2023-04-01T00:00:00',
    updated_at: '2024-01-01T00:00:00',
  },
];

export function useLocations() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [locations, setLocations] = useState<Location[]>(mockLocations);
  const [selectedLocation, setSelectedLocation] = useState<Location | null>(null);

  // Fetch locations
  const fetchLocations = useCallback(async (filters?: {
    is_active?: boolean;
    search?: string;
  }) => {
    setIsLoading(true);
    setError(null);

    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      let filtered = [...mockLocations];

      if (filters?.is_active !== undefined) {
        filtered = filtered.filter((l) => l.is_active === filters.is_active);
      }
      if (filters?.search) {
        const search = filters.search.toLowerCase();
        filtered = filtered.filter(
          (l) =>
            l.name.toLowerCase().includes(search) ||
            l.address.toLowerCase().includes(search)
        );
      }

      setLocations(filtered);
    } catch (err) {
      setError('Gagal memuat data lokasi');
      console.error('Error fetching locations:', err);
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Create location
  const createLocation = useCallback(async (data: LocationFormData) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      const newLocation: Location = {
        id: `new-${Date.now()}`,
        name: data.name,
        address: data.address,
        latitude: data.latitude,
        longitude: data.longitude,
        radius_meters: data.radius_meters,
        wifi_ssid: data.wifi_ssid,
        is_active: data.is_active,
        metadata: {
          description: data.description,
          type: data.type,
        },
        employee_count: 0,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      };

      setLocations((prev) => [newLocation, ...prev]);
      return newLocation;
    } catch (err) {
      setError('Gagal membuat lokasi');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Update location
  const updateLocation = useCallback(async (id: string, data: Partial<LocationFormData>) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      setLocations((prev) =>
        prev.map((loc) =>
          loc.id === id
            ? {
                ...loc,
                ...data,
                metadata: {
                  ...loc.metadata,
                  description: data.description ?? loc.metadata?.description,
                  type: data.type ?? loc.metadata?.type,
                },
                updated_at: new Date().toISOString(),
              }
            : loc
        )
      );
    } catch (err) {
      setError('Gagal mengupdate lokasi');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Delete location
  const deleteLocation = useCallback(async (id: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));
      setLocations((prev) => prev.filter((l) => l.id !== id));
    } catch (err) {
      setError('Gagal menghapus lokasi');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Toggle location status
  const toggleStatus = useCallback(async (id: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 300));

      setLocations((prev) =>
        prev.map((loc) =>
          loc.id === id
            ? { ...loc, is_active: !loc.is_active, updated_at: new Date().toISOString() }
            : loc
        )
      );
    } catch (err) {
      setError('Gagal mengubah status lokasi');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Get statistics
  const getStatistics = useCallback(async (): Promise<LocationStatistics> => {
    await new Promise((resolve) => setTimeout(resolve, 300));

    const activeLocations = locations.filter((l) => l.is_active);
    const totalEmployees = locations.reduce((sum, l) => sum + (l.employee_count || 0), 0);
    const avgRadius = locations.reduce((sum, l) => sum + l.radius_meters, 0) / locations.length;
    const withWifi = locations.filter((l) => l.wifi_ssid).length;

    return {
      total_locations: locations.length,
      active_locations: activeLocations.length,
      inactive_locations: locations.length - activeLocations.length,
      total_employees_assigned: totalEmployees,
      average_radius: Math.round(avgRadius),
      locations_with_wifi: withWifi,
    };
  }, [locations]);

  return {
    // State
    isLoading,
    error,
    locations,
    selectedLocation,

    // Actions
    fetchLocations,
    createLocation,
    updateLocation,
    deleteLocation,
    toggleStatus,
    getStatistics,
    setSelectedLocation,
    clearError: () => setError(null),
  };
}
