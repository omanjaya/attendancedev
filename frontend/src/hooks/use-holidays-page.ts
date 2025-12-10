import { useState, useEffect } from 'react';
import { useHolidays } from '@/hooks/use-holidays';
import type { Holiday, HolidayType, HolidayFormData, HolidayStatistics } from '@/types/holiday';

/**
 * Shared hook for admin holidays pages (desktop + mobile)
 * Extracts all common state management, data fetching, and handlers
 */
export function useHolidaysPage() {
    // Data fetching from existing hook
    const {
        isLoading,
        holidays,
        fetchHolidays,
        createHoliday,
        updateHoliday,
        deleteHoliday,
        cancelHoliday,
        getStatistics,
    } = useHolidays();

    // Shared state
    const [searchQuery, setSearchQuery] = useState('');
    const [typeFilter, setTypeFilter] = useState<HolidayType | 'all'>('all');
    const [yearFilter, setYearFilter] = useState(new Date().getFullYear());
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingHoliday, setEditingHoliday] = useState<Holiday | null>(null);
    const [deletingHoliday, setDeletingHoliday] = useState<Holiday | null>(null);
    const [stats, setStats] = useState<HolidayStatistics | null>(null);

    // Load holidays and stats on mount and year change
    useEffect(() => {
        fetchHolidays({ year: yearFilter });
        getStatistics(yearFilter).then(setStats);
    }, [fetchHolidays, yearFilter, getStatistics]);

    /**
     * Handle search with filters
     */
    const handleSearch = () => {
        fetchHolidays({
            type: typeFilter !== 'all' ? typeFilter : undefined,
            year: yearFilter,
            search: searchQuery || undefined,
        });
    };

    // Auto-search when filters change
    useEffect(() => {
        handleSearch();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [typeFilter, yearFilter]);

    /**
     * Handle create holiday with stats refresh
     */
    const handleCreate = async (data: HolidayFormData, onSuccess?: () => void) => {
        await createHoliday(data);
        const newStats = await getStatistics(yearFilter);
        setStats(newStats);
        onSuccess?.();
    };

    /**
     * Handle update holiday with stats refresh
     */
    const handleUpdate = async (data: HolidayFormData, onSuccess?: () => void) => {
        if (editingHoliday) {
            await updateHoliday(editingHoliday.id, data);
            setEditingHoliday(null);
            const newStats = await getStatistics(yearFilter);
            setStats(newStats);
            onSuccess?.();
        }
    };

    /**
     * Handle delete holiday with stats refresh
     */
    const handleDelete = async (onSuccess?: () => void) => {
        if (deletingHoliday) {
            await deleteHoliday(deletingHoliday.id);
            setDeletingHoliday(null);
            const newStats = await getStatistics(yearFilter);
            setStats(newStats);
            onSuccess?.();
        }
    };

    /**
     * Handle cancel holiday
     */
    const handleCancel = async (id: string) => {
        await cancelHoliday(id);
        const newStats = await getStatistics(yearFilter);
        setStats(newStats);
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

    return {
        // Data & Loading
        isLoading,
        holidays,
        stats,

        // API Functions
        fetchHolidays,
        getStatistics,

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

        // Handlers
        handleSearch,
        handleCreate,
        handleUpdate,
        handleDelete,
        handleCancel,

        // Helpers
        formatDate,
        yearOptions,
    };
}
