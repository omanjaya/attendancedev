import { useState, useCallback, useEffect } from 'react';
import type {
  Location,
  LocationFormData,
  LocationStatistics,
} from '@/types/location';
import * as locationsApi from '@/lib/api/locations';

export function useLocations() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [locations, setLocations] = useState<Location[]>([]);
  const [selectedLocation, setSelectedLocation] = useState<Location | null>(null);
  const [statistics, setStatistics] = useState<LocationStatistics | null>(null);

  // Fetch locations
  const fetchLocations = useCallback(async (filters?: {
    is_active?: boolean;
    search?: string;
  }) => {
    setIsLoading(true);
    setError(null);

    try {
      const data = await locationsApi.getLocations();

      let filtered = [...data];

      // Apply filters
      if (filters?.is_active !== undefined) {
        filtered = filtered.filter((l) => l.is_active === filters.is_active);
      }
      if (filters?.search) {
        const search = filters.search.toLowerCase();
        filtered = filtered.filter(
          (l) =>
            l.name.toLowerCase().includes(search) ||
            (l.address && l.address.toLowerCase().includes(search))
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

  // Fetch statistics
  const fetchStatistics = useCallback(async () => {
    try {
      const stats = await locationsApi.getLocationStatistics();
      setStatistics(stats);
    } catch (err) {
      console.error('Error fetching statistics:', err);
    }
  }, []);

  // Create location
  const createLocation = useCallback(async (data: LocationFormData) => {
    setIsLoading(true);
    setError(null);

    try {
      const newLocation = await locationsApi.createLocation(data);
      setLocations((prev) => [newLocation, ...prev]);
      return newLocation;
    } catch (err: any) {
      const errorMsg = err.response?.data?.message || 'Gagal membuat lokasi';
      setError(errorMsg);
      throw new Error(errorMsg);
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Update location
  const updateLocation = useCallback(async (id: string, data: Partial<LocationFormData>) => {
    setIsLoading(true);
    setError(null);

    try {
      const updatedLocation = await locationsApi.updateLocation(id, data);
      setLocations((prev) =>
        prev.map((loc) => (loc.id === id ? updatedLocation : loc))
      );
      if (selectedLocation?.id === id) {
        setSelectedLocation(updatedLocation);
      }
    } catch (err: any) {
      const errorMsg = err.response?.data?.message || 'Gagal mengupdate lokasi';
      setError(errorMsg);
      throw new Error(errorMsg);
    } finally {
      setIsLoading(false);
    }
  }, [selectedLocation]);

  // Delete location
  const deleteLocation = useCallback(async (id: string) => {
    setIsLoading(true);
    setError(null);

    try {
      await locationsApi.deleteLocation(id);
      setLocations((prev) => prev.filter((l) => l.id !== id));
      if (selectedLocation?.id === id) {
        setSelectedLocation(null);
      }
    } catch (err: any) {
      const errorMsg = err.response?.data?.message || 'Gagal menghapus lokasi';
      setError(errorMsg);
      throw new Error(errorMsg);
    } finally {
      setIsLoading(false);
    }
  }, [selectedLocation]);

  // Toggle location status
  const toggleStatus = useCallback(async (id: string) => {
    setIsLoading(true);
    setError(null);

    try {
      const updatedLocation = await locationsApi.toggleLocationStatus(id);
      setLocations((prev) =>
        prev.map((loc) => (loc.id === id ? updatedLocation : loc))
      );
      if (selectedLocation?.id === id) {
        setSelectedLocation(updatedLocation);
      }
    } catch (err: any) {
      const errorMsg = err.response?.data?.message || 'Gagal mengubah status lokasi';
      setError(errorMsg);
      throw new Error(errorMsg);
    } finally {
      setIsLoading(false);
    }
  }, [selectedLocation]);

  // Get statistics (kept for backward compatibility)
  const getStatistics = useCallback(async (): Promise<LocationStatistics> => {
    const stats = await locationsApi.getLocationStatistics();
    setStatistics(stats);
    return stats;
  }, []);

  // Load locations on mount
  useEffect(() => {
    fetchLocations();
    fetchStatistics();
  }, [fetchLocations, fetchStatistics]);

  return {
    // State
    isLoading,
    error,
    locations,
    selectedLocation,
    statistics,

    // Actions
    fetchLocations,
    fetchStatistics,
    createLocation,
    updateLocation,
    deleteLocation,
    toggleStatus,
    getStatistics,
    setSelectedLocation,
    clearError: () => setError(null),
  };
}
