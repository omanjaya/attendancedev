import { useState, useEffect, useCallback, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { 
    getHolidays, 
    getHolidayStatistics,
    createHoliday as createHolidayApi,
    updateHoliday as updateHolidayApi,
    deleteHoliday as deleteHolidayApi,
    type HolidayFilters 
} from '@/lib/api/holidays';
import { useNotificationStore } from '@/stores';
import type { Holiday, HolidayType, HolidayFormData } from '@/types/holiday';

/**
 * Shared hook for admin holidays pages (desktop + mobile)
 * Extracts all common state management, data fetching, and handlers
 */
export function useHolidaysPage() {
    const queryClient = useQueryClient();
    const { success, error: showError } = useNotificationStore();

    // Filter state
    const [searchQuery, setSearchQuery] = useState('');
    const [appliedSearch, setAppliedSearch] = useState('');
    const [typeFilter, setTypeFilter] = useState<HolidayType | 'all'>('all');
    const [yearFilter, setYearFilter] = useState(new Date().getFullYear());
    const [currentPage, setCurrentPage] = useState(1);
    const [perPage, setPerPage] = useState(10);
    
    // UI state
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingHoliday, setEditingHoliday] = useState<Holiday | null>(null);
    const [deletingHoliday, setDeletingHoliday] = useState<Holiday | null>(null);
    const [selectedIds, setSelectedIds] = useState<string[]>([]);

    // Build filters object - includes page for pagination
    const filters: HolidayFilters = useMemo(() => ({
        year: yearFilter,
        type: typeFilter !== 'all' ? typeFilter : undefined,
        search: appliedSearch || undefined,
        per_page: perPage,
        page: currentPage,
    }), [yearFilter, typeFilter, appliedSearch, perPage, currentPage]);

    // Fetch holidays with React Query
    const { 
        data: holidaysData, 
        isLoading,
        isFetching,
        refetch 
    } = useQuery({
        queryKey: ['holidays', filters],
        queryFn: () => getHolidays(filters),
        staleTime: 30000,
        placeholderData: (previousData) => previousData, // Keep previous data while fetching new page
    });

    const holidays = holidaysData?.data || [];
    const meta = holidaysData?.meta;

    // Fetch statistics
    const { data: stats } = useQuery({
        queryKey: ['holiday-statistics', yearFilter],
        queryFn: () => getHolidayStatistics(yearFilter),
        staleTime: 60000,
    });

    // Create mutation
    const createMutation = useMutation({
        mutationFn: createHolidayApi,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['holidays'] });
            queryClient.invalidateQueries({ queryKey: ['holiday-statistics'] });
            success('Berhasil', 'Hari libur berhasil dibuat');
        },
        onError: (err: any) => {
            showError('Error', err?.response?.data?.message || 'Gagal membuat hari libur');
        },
    });

    // Update mutation
    const updateMutation = useMutation({
        mutationFn: ({ id, data }: { id: string; data: Partial<HolidayFormData> }) => 
            updateHolidayApi(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['holidays'] });
            queryClient.invalidateQueries({ queryKey: ['holiday-statistics'] });
            success('Berhasil', 'Hari libur berhasil diupdate');
        },
        onError: (err: any) => {
            showError('Error', err?.response?.data?.message || 'Gagal update hari libur');
        },
    });

    // Delete mutation
    const deleteMutation = useMutation({
        mutationFn: deleteHolidayApi,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['holidays'] });
            queryClient.invalidateQueries({ queryKey: ['holiday-statistics'] });
            success('Berhasil', 'Hari libur berhasil dihapus');
        },
        onError: (err: any) => {
            showError('Error', err?.response?.data?.message || 'Gagal hapus hari libur');
        },
    });

    // Reset page when filters change
    useEffect(() => {
        setCurrentPage(1);
    }, [typeFilter, yearFilter, appliedSearch]);

    /**
     * Handle search button click
     */
    const handleSearch = useCallback(() => {
        setAppliedSearch(searchQuery);
        setCurrentPage(1);
    }, [searchQuery]);

    /**
     * Handle page change
     */
    const handlePageChange = useCallback((page: number) => {
        setCurrentPage(page);
    }, []);

    /**
     * Handle create holiday
     */
    const handleCreate = async (data: HolidayFormData, onSuccess?: () => void) => {
        await createMutation.mutateAsync(data);
        onSuccess?.();
    };

    /**
     * Handle update holiday
     */
    const handleUpdate = async (data: HolidayFormData, onSuccess?: () => void) => {
        if (editingHoliday) {
            await updateMutation.mutateAsync({ id: editingHoliday.id, data });
            setEditingHoliday(null);
            onSuccess?.();
        }
    };

    /**
     * Handle delete holiday
     */
    const handleDelete = async (onSuccess?: () => void) => {
        if (deletingHoliday) {
            await deleteMutation.mutateAsync(deletingHoliday.id);
            setDeletingHoliday(null);
            onSuccess?.();
        }
    };

    /**
     * Handle batch delete holidays
     */
    const handleBatchDelete = async (onSuccess?: () => void) => {
        if (selectedIds.length === 0) return;
        
        try {
            // Delete all selected holidays
            await Promise.all(selectedIds.map(id => deleteHolidayApi(id)));
            queryClient.invalidateQueries({ queryKey: ['holidays'] });
            queryClient.invalidateQueries({ queryKey: ['holiday-statistics'] });
            success('Berhasil', `${selectedIds.length} hari libur berhasil dihapus`);
            setSelectedIds([]);
            onSuccess?.();
        } catch (err: any) {
            showError('Error', err?.response?.data?.message || 'Gagal menghapus hari libur');
        }
    };

    /**
     * Toggle select single item
     */
    const toggleSelect = (id: string) => {
        setSelectedIds(prev => 
            prev.includes(id) 
                ? prev.filter(i => i !== id) 
                : [...prev, id]
        );
    };

    /**
     * Toggle select all on current page
     */
    const toggleSelectAll = () => {
        if (selectedIds.length === holidays.length) {
            setSelectedIds([]);
        } else {
            setSelectedIds(holidays.map(h => h.id));
        }
    };

    /**
     * Clear selection
     */
    const clearSelection = () => setSelectedIds([]);

    /**
     * Handle cancel holiday (set status to cancelled)
     */
    const handleCancel = async (id: string) => {
        await updateMutation.mutateAsync({ id, data: { status: 'cancelled' } });
    };

    /**
     * Format date to Indonesian locale
     */
    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString('id-ID', {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    };

    /**
     * Get year options (previous, current, next)
     */
    const currentYear = new Date().getFullYear();
    const yearOptions = [currentYear - 1, currentYear, currentYear + 1];

    // Calculate pagination info from API response
    const totalItems = meta?.total || holidays.length;
    const totalPages = meta?.last_page || Math.ceil(totalItems / perPage);

    // Combined loading state
    const isMutating = createMutation.isPending || updateMutation.isPending || deleteMutation.isPending;

    return {
        // Data & Loading
        isLoading: isLoading || isMutating,
        isFetching, // True when fetching in background (pagination)
        holidays,
        stats: stats || null,

        // State
        searchQuery,
        setSearchQuery,
        typeFilter,
        setTypeFilter,
        yearFilter,
        setYearFilter,
        isFormOpen,
        setIsFormOpen,
        editingHoliday,
        setEditingHoliday,
        deletingHoliday,
        setDeletingHoliday,

        // Pagination
        currentPage,
        setCurrentPage,
        perPage,
        setPerPage,
        totalPages,
        totalItems,
        handlePageChange,

        // Selection
        selectedIds,
        toggleSelect,
        toggleSelectAll,
        clearSelection,
        isAllSelected: holidays.length > 0 && selectedIds.length === holidays.length,

        // Handlers
        handleSearch,
        handleCreate,
        handleUpdate,
        handleDelete,
        handleBatchDelete,
        handleCancel,
        refetch,

        // Helpers
        formatDate,
        yearOptions,
    };
}
