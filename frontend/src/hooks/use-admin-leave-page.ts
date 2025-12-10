import { useState } from 'react';
import {
    useApproveLeaveRequest,
    useRejectLeaveRequest,
} from '@/hooks';

/**
 * Shared hook for admin leave pages (desktop + mobile)
 * Extracts all common state management and handlers
 * Note: Data fetching (useLeaveRequests) is done in parent component
 * to allow different filters (desktop has typeFilter, mobile doesn't)
 */
export function useAdminLeavePage() {
    // Shared state
    const [statusFilter, setStatusFilter] = useState<string>('all');
    const [showRejectDialog, setShowRejectDialog] = useState(false);
    const [selectedRequestId, setSelectedRequestId] = useState<string | null>(null);

    // Mutations
    const approveLeaveRequestMutation = useApproveLeaveRequest();
    const rejectLeaveRequestMutation = useRejectLeaveRequest();

    /**
     * Handle approve leave request
     */
    const handleApprove = async (id: string) => {
        try {
            await approveLeaveRequestMutation.mutateAsync({ id });
        } catch {
            // Error handled in hook
        }
    };

    /**
     * Handle reject click - opens reject dialog
     */
    const onRejectClick = (id: string) => {
        setSelectedRequestId(id);
        setShowRejectDialog(true);
    };

    /**
     * Handle reject confirm - submits rejection with reason
     */
    const handleRejectConfirm = async (reason: string) => {
        if (selectedRequestId) {
            try {
                await rejectLeaveRequestMutation.mutateAsync({ id: selectedRequestId, reason });
                setShowRejectDialog(false);
                setSelectedRequestId(null);
            } catch {
                // Error handled in hook
            }
        }
    };

    return {
        // Mutations
        approveLeaveRequestMutation,
        rejectLeaveRequestMutation,

        // State
        statusFilter,
        setStatusFilter,
        showRejectDialog,
        setShowRejectDialog,
        selectedRequestId,
        setSelectedRequestId,

        // Handlers
        handleApprove,
        onRejectClick,
        handleRejectConfirm,
    };
}
