import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAuthStore } from '@/stores';
import {
    getLeaveBalance,
    getLeaveRequests,
    createLeaveRequest,
    cancelLeaveRequest
} from '@/lib/api/leave';
import { toast } from 'sonner';
import type { LeaveRequest } from '@/types';
import { differenceInDays, parseISO } from 'date-fns';

/**
 * Shared hook for employee leave pages (desktop + mobile)
 * Extracts ALL shared state, data fetching, mutations, and handlers
 * This is almost 100% shared logic between desktop and mobile!
 */
export function useEmployeeLeavePage() {
    const { user } = useAuthStore();
    const queryClient = useQueryClient();

    // Shared state
    const [showRequestForm, setShowRequestForm] = useState(false);
    const [filterStatus, setFilterStatus] = useState<'all' | 'pending' | 'approved' | 'rejected'>('all');

    // Form state
    const [leaveType, setLeaveType] = useState('');
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [reason, setReason] = useState('');

    // Fetch leave balance
    const { data: leaveBalance } = useQuery({
        queryKey: ['employee', 'leave-balance', user?.id],
        queryFn: getLeaveBalance,
    });

    // Fetch leave requests
    const { data: leaveRequestsResponse, isLoading } = useQuery({
        queryKey: ['employee', 'leave-requests', user?.id],
        queryFn: () => getLeaveRequests({ per_page: 50 }),
    });

    const leaveRequests = (leaveRequestsResponse?.data || []) as LeaveRequest[];

    // Create leave request mutation
    const createLeaveMutation = useMutation({
        mutationFn: async (data: { leaveType: string; startDate: string; endDate: string; reason: string }) => {
            return createLeaveRequest({
                type: data.leaveType as any,
                start_date: data.startDate,
                end_date: data.endDate,
                duration_type: 'full_day',
                reason: data.reason,
            });
        },
        onSuccess: async () => {
            await Promise.all([
                queryClient.invalidateQueries({ queryKey: ['employee', 'leave-requests'] }),
                queryClient.invalidateQueries({ queryKey: ['employee', 'leave-balance'] }),
            ]);
            setShowRequestForm(false);
            resetForm();
            toast.success('Pengajuan cuti berhasil dikirim');
        },
        onError: (error: any) => {
            toast.error(error.response?.data?.message || 'Gagal mengajukan cuti');
        }
    });

    // Cancel leave request mutation
    const cancelLeaveMutation = useMutation({
        mutationFn: async (id: string) => {
            return cancelLeaveRequest(id);
        },
        onSuccess: async () => {
            await Promise.all([
                queryClient.invalidateQueries({ queryKey: ['employee', 'leave-requests'] }),
                queryClient.invalidateQueries({ queryKey: ['employee', 'leave-balance'] }),
            ]);
            toast.success('Pengajuan cuti dibatalkan');
        },
        onError: (error: any) => {
            toast.error(error.response?.data?.message || 'Gagal membatalkan cuti');
        }
    });

    /**
     * Reset form to initial state
     */
    const resetForm = () => {
        setLeaveType('');
        setStartDate('');
        setEndDate('');
        setReason('');
    };

    /**
     * Handle submit leave request
     */
    const handleSubmitRequest = (e: React.FormEvent) => {
        e.preventDefault();
        createLeaveMutation.mutate({
            leaveType,
            startDate,
            endDate,
            reason,
        });
    };

    /**
     * Handle cancel leave request
     */
    const handleCancelRequest = (id: string) => {
        if (confirm('Apakah Anda yakin ingin membatalkan pengajuan cuti ini?')) {
            cancelLeaveMutation.mutate(id);
        }
    };

    /**
     * Get status label text
     */
    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'pending':
                return 'Menunggu';
            case 'approved':
                return 'Disetujui';
            case 'rejected':
                return 'Ditolak';
            default:
                return status;
        }
    };

    /**
     * Filter requests by status
     */
    const filteredRequests = leaveRequests?.filter((request) => {
        if (filterStatus === 'all') return true;
        return request.status === filterStatus;
    });

    /**
     * Calculate number of days between start and end date
     */
    const calculateDays = () => {
        if (startDate && endDate) {
            const days = differenceInDays(parseISO(endDate), parseISO(startDate)) + 1;
            return days > 0 ? days : 0;
        }
        return 0;
    };

    return {
        // Data & Loading
        leaveBalance,
        leaveRequests,
        filteredRequests,
        isLoading,

        // Mutations
        createLeaveMutation,
        cancelLeaveMutation,

        // State
        showRequestForm,
        setShowRequestForm,
        filterStatus,
        setFilterStatus,

        // Form State
        leaveType,
        setLeaveType,
        startDate,
        setStartDate,
        endDate,
        setEndDate,
        reason,
        setReason,

        // Handlers
        resetForm,
        handleSubmitRequest,
        handleCancelRequest,

        // Helpers
        getStatusLabel,
        calculateDays,
    };
}
