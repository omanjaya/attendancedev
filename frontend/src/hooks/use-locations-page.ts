import { useState } from 'react';
import {
    useLocations,
    useCreateLocation,
    useUpdateLocation,
    useDeleteLocation,
    useToggleLocationStatus,
    useAssignEmployees,
} from '@/hooks/use-locations';
import type { Location, LocationFormData } from '@/types/location';
import { toast } from 'sonner';

/**
 * Shared hook for admin locations pages (desktop + mobile)
 * Extracts all common state management, data fetching, and handlers
 */
export function useLocationsPage() {
    // Data fetching from existing hook
    const { data: locations = [], isLoading, refetch } = useLocations();

    // Mutations
    const createLocationMutation = useCreateLocation();
    const updateLocationMutation = useUpdateLocation();
    const deleteLocationMutation = useDeleteLocation();
    const toggleStatusMutation = useToggleLocationStatus();
    const assignEmployeesMutation = useAssignEmployees();

    // Shared state
    const [searchQuery, setSearchQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState<'all' | 'active' | 'inactive'>('all');
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingLocation, setEditingLocation] = useState<Location | null>(null);
    const [deletingLocation, setDeletingLocation] = useState<Location | null>(null);
    const [assigningLocation, setAssigningLocation] = useState<Location | null>(null);

    // Handle search with filters
    const handleSearch = () => {
        refetch();
    };

    /**
     * Handle create location with validation
     */
    const handleCreate = async (data: LocationFormData, onSuccess?: () => void) => {
        if (data.radius_meters <= 0) {
            toast.error('Radius harus lebih dari 0 meter');
            return;
        }
        try {
            await createLocationMutation.mutateAsync(data);
            toast.success('Lokasi berhasil dibuat');
            onSuccess?.();
        } catch (error: any) {
            console.error('Create location error:', error);
            toast.error(error.message || 'Gagal membuat lokasi');
        }
    };

    /**
     * Handle update location with validation
     */
    const handleUpdate = async (data: LocationFormData, onSuccess?: () => void) => {
        if (data.radius_meters <= 0) {
            toast.error('Radius harus lebih dari 0 meter');
            return;
        }
        if (editingLocation) {
            try {
                await updateLocationMutation.mutateAsync({ id: editingLocation.id, data });
                toast.success('Lokasi berhasil diperbarui');
                setEditingLocation(null);
                onSuccess?.();
            } catch (error: any) {
                console.error('Update location error:', error);
                toast.error(error.message || 'Gagal memperbarui lokasi');
            }
        }
    };

    /**
     * Handle assign employees to location
     */
    const handleAssignEmployees = async (locationId: string, employeeIds: string[], onSuccess?: () => void) => {
        try {
            await assignEmployeesMutation.mutateAsync({ locationId, employeeIds });
            toast.success('Pegawai berhasil ditugaskan');
            setAssigningLocation(null);
            onSuccess?.();
        } catch (error: any) {
            console.error('Assign employees error:', error);
            toast.error(error.message || 'Gagal menugaskan pegawai');
        }
    };

    /**
     * Handle delete location
     */
    const handleDelete = async (onSuccess?: () => void) => {
        if (deletingLocation) {
            try {
                await deleteLocationMutation.mutateAsync(deletingLocation.id);
                toast.success('Lokasi berhasil dihapus');
                setDeletingLocation(null);
                onSuccess?.();
            } catch (error: any) {
                console.error('Delete location error:', error);
                toast.error(error.message || 'Gagal menghapus lokasi');
            }
        }
    };

    /**
     * Handle toggle location status (activate/deactivate)
     */
    const handleToggleStatus = async (location: Location) => {
        try {
            await toggleStatusMutation.mutateAsync(location.id);
            toast.success(location.is_active ? 'Lokasi dinonaktifkan' : 'Lokasi diaktifkan');
        } catch (error: any) {
            console.error('Toggle status error:', error);
            toast.error(error.message || 'Gagal mengubah status lokasi');
        }
    };

    // Stub for getStatistics (not implemented yet)
    const getStatistics = async () => null;

    return {
        // Data & Loading
        isLoading,
        locations,
        fetchLocations: refetch,
        getStatistics,

        // State
        searchQuery,
        setSearchQuery,
        statusFilter,
        setStatusFilter,
        isFormOpen,
        setIsFormOpen,
        editingLocation,
        setEditingLocation,
        deletingLocation,
        setDeletingLocation,
        assigningLocation,
        setAssigningLocation,

        // Handlers
        handleSearch,
        handleCreate,
        handleUpdate,
        handleAssignEmployees,
        handleDelete,
        handleToggleStatus,
    };
}
