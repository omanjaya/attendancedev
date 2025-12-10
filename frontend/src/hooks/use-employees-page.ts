import { useState } from 'react';
import { useDeleteEmployee } from '@/hooks/use-employees';
import { resetEmployeePassword, type ResetPasswordResponse } from '@/lib/api/employees';
import { toast } from 'sonner';

/**
 * Shared hook for admin employees pages (desktop + mobile)
 * Extracts all common state management, data fetching, and handlers
 */
export function useEmployeesPage() {
    // Data fetching
    const [searchQuery, setSearchQuery] = useState('');
    const deleteEmployeeMutation = useDeleteEmployee();

    // Delete employee state
    const [employeeToDelete, setEmployeeToDelete] = useState<{ id: string; name: string } | null>(null);

    // Reset password states
    const [employeeToReset, setEmployeeToReset] = useState<{ id: string; name: string; email?: string } | null>(null);
    const [isResetting, setIsResetting] = useState(false);
    const [resetResult, setResetResult] = useState<ResetPasswordResponse | null>(null);
    const [showPassword, setShowPassword] = useState(false);
    const [customPassword, setCustomPassword] = useState('');

    // Handlers

    /**
     * Handle employee deletion
     * @param onSuccess Optional callback after successful deletion (for mobile to close drawer)
     */
    const handleDelete = async (onSuccess?: () => void) => {
        if (!employeeToDelete) return;
        try {
            await deleteEmployeeMutation.mutateAsync(employeeToDelete.id);
            setEmployeeToDelete(null);
            onSuccess?.();
        } catch (error) {
            console.error('Failed to delete employee:', error);
        }
    };

    /**
     * Handle password reset for employee
     */
    const handleResetPassword = async () => {
        if (!employeeToReset) return;

        setIsResetting(true);
        try {
            const result = await resetEmployeePassword(
                employeeToReset.id,
                customPassword || undefined
            );
            setResetResult(result);
            toast.success('Password berhasil direset!', {
                description: 'Password baru telah dibuat.',
            });
        } catch (error) {
            console.error('Failed to reset password:', error);
            toast.error('Gagal mereset password', {
                description: error instanceof Error ? error.message : 'Terjadi kesalahan.',
            });
        } finally {
            setIsResetting(false);
        }
    };

    /**
     * Copy password to clipboard
     */
    const handleCopyPassword = () => {
        if (resetResult?.temporary_password) {
            navigator.clipboard.writeText(resetResult.temporary_password);
            toast.success('Password disalin ke clipboard');
        }
    };

    /**
     * Close reset password dialog and reset all related state
     */
    const handleCloseResetDialog = () => {
        setEmployeeToReset(null);
        setResetResult(null);
        setCustomPassword('');
        setShowPassword(false);
    };

    /**
     * Get initials from employee name for avatar fallback
     */
    const getInitials = (name: string) => {
        if (!name) return '??';
        return name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    return {
        // Data & Mutations
        deleteEmployeeMutation,

        // State
        searchQuery,
        setSearchQuery,
        employeeToDelete,
        setEmployeeToDelete,
        employeeToReset,
        setEmployeeToReset,
        isResetting,
        resetResult,
        setResetResult,
        showPassword,
        setShowPassword,
        customPassword,
        setCustomPassword,

        // Handlers
        handleDelete,
        handleResetPassword,
        handleCopyPassword,
        handleCloseResetDialog,

        // Utilities
        getInitials,
    };
}
