import { useState, useRef, useEffect } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import {
    useProfile,
    useProfileStatistics,
    useUpdateProfile,
    useChangePassword,
    useUploadAvatar,
    useDeleteAvatar,
    useDeleteAccount,
} from '@/hooks/use-profile';
import { useCameraCapture } from '@/hooks/use-camera-capture';
import { useFaceData, useDeleteFace, faceRecognitionKeys } from '@/hooks/use-face-recognition-api';
import { extractEmbeddingDeepFace, registerFace, updateFace } from '@/lib/api/face-recognition';

type EnrollmentStep = 'idle' | 'camera' | 'ready' | 'capturing' | 'processing' | 'success' | 'error';
type RegistrationAction = 'register' | 'update' | null;

/**
 * useProfilePage - Shared logic for desktop and mobile profile pages
 *
 * Extracts ALL business logic, state management, and handlers that are
 * common between desktop/mobile views. Pages only handle UI rendering.
 */
export function useProfilePage() {
    const queryClient = useQueryClient();

    // Data fetching
    const { data: profile, isLoading } = useProfile();
    const { data: statistics } = useProfileStatistics();
    const { data: faceData, refetch: refetchFaceData } = useFaceData(profile?.employee_pk || '');

    // Mutations
    const updateProfile = useUpdateProfile();
    const changePassword = useChangePassword();
    const uploadAvatar = useUploadAvatar();
    const deleteAvatar = useDeleteAvatar();
    const deleteAccount = useDeleteAccount();
    const deleteFaceMutation = useDeleteFace();

    // Camera capture
    const {
        videoRef,
        errorMessage: cameraError,
        startCamera,
        stopCamera,
        captureImage,
    } = useCameraCapture();

    // Refs
    const fileInputRef = useRef<HTMLInputElement>(null);

    // Profile editing state
    const [editMode, setEditMode] = useState(false);
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        phone: '',
    });

    // Password change state
    const [passwordDialog, setPasswordDialog] = useState(false);
    const [passwordForm, setPasswordForm] = useState({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const [passwordError, setPasswordError] = useState<string | null>(null);

    // Delete account state
    const [deleteDialog, setDeleteDialog] = useState(false);
    const [deletePassword, setDeletePassword] = useState('');
    const [deleteError, setDeleteError] = useState<string | null>(null);

    // Face enrollment state
    const [faceEnrollmentOpen, setFaceEnrollmentOpen] = useState(false);
    const [deleteFaceOpen, setDeleteFaceOpen] = useState(false);
    const [enrollmentStep, setEnrollmentStep] = useState<EnrollmentStep>('idle');
    const [enrollmentError, setEnrollmentError] = useState<string | null>(null);
    const [countdown, setCountdown] = useState<number>(3);
    const [registrationAction, setRegistrationAction] = useState<RegistrationAction>(null);

    // Success/error messages
    const [successMessage, setSuccessMessage] = useState<string | null>(null);

    // Initialize form data when profile loads
    useEffect(() => {
        if (profile) {
            setFormData({
                name: profile.name || '',
                email: profile.email || '',
                phone: profile.phone || '',
            });
        }
    }, [profile]);

    // Note: Countdown removed - desktop uses manual capture button

    // Handlers: Profile Editing
    const handleEditClick = () => {
        if (profile) {
            setFormData({
                name: profile.name || '',
                email: profile.email || '',
                phone: profile.phone || '',
            });
            setEditMode(true);
        }
    };

    const handleSaveProfile = async () => {
        try {
            await updateProfile.mutateAsync(formData);
            setEditMode(false);
            setSuccessMessage('Profil berhasil diperbarui');
            setTimeout(() => setSuccessMessage(null), 3000);
        } catch (_error) {
            // Error handled by mutation
        }
    };

    // Handlers: Password Change
    const handleChangePassword = async () => {
        setPasswordError(null);

        if (passwordForm.password !== passwordForm.password_confirmation) {
            setPasswordError('Password konfirmasi tidak cocok');
            return;
        }

        try {
            await changePassword.mutateAsync(passwordForm);
            setPasswordDialog(false);
            setPasswordForm({
                current_password: '',
                password: '',
                password_confirmation: '',
            });
            setSuccessMessage('Password berhasil diubah');
            setTimeout(() => setSuccessMessage(null), 3000);
        } catch (error: any) {
            setPasswordError(error.message || 'Gagal mengubah password');
        }
    };

    // Handlers: Avatar Management
    const handleAvatarUpload = async (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file) return;

        try {
            await uploadAvatar.mutateAsync(file);
            setSuccessMessage('Foto profil berhasil diperbarui');
            setTimeout(() => setSuccessMessage(null), 3000);
        } catch (_error) {
            // Error handled by mutation
        }
    };

    const handleDeleteAvatar = async () => {
        try {
            await deleteAvatar.mutateAsync();
            setSuccessMessage('Foto profil berhasil dihapus');
            setTimeout(() => setSuccessMessage(null), 3000);
        } catch (_error) {
            // Error handled by mutation
        }
    };

    // Handlers: Account Deletion
    const handleDeleteAccount = async () => {
        setDeleteError(null);

        try {
            await deleteAccount.mutateAsync(deletePassword);
            setDeleteDialog(false);
            // Logout will be handled by the mutation's onSuccess
        } catch (error: any) {
            setDeleteError(error.message || 'Gagal menghapus akun');
        }
    };

    // Handlers: Face Enrollment
    const handleStartFaceEnrollment = async () => {
        setFaceEnrollmentOpen(true);
        setEnrollmentStep('camera');
        setEnrollmentError(null);

        try {
            await startCamera();
            setEnrollmentStep('ready');
        } catch (error: any) {
            setEnrollmentError(cameraError || error.message || 'Gagal mengakses kamera');
            setEnrollmentStep('error');
        }
    };

    const handleCaptureFace = async () => {
        if (!profile?.employee_pk) {
            setEnrollmentError('Employee ID tidak ditemukan');
            setEnrollmentStep('error');
            return;
        }

        setEnrollmentStep('capturing');
        setEnrollmentError(null);

        try {
            // Capture image from video as File
            const imageFile = await captureImage();

            // Extract embedding using DeepFace
            setEnrollmentStep('processing');
            const result = await extractEmbeddingDeepFace(imageFile);

            if (!result.success || !result.embedding) {
                throw new Error(result.message || 'Gagal mengekstrak data wajah');
            }

            // Check image quality if available
            if (result.quality && !result.quality.quality_ok) {
                const issues = result.quality.issues.join(', ');
                throw new Error(`Kualitas gambar kurang baik: ${issues}`);
            }

            // Check if face is already registered
            const hasFaceData = faceData && faceData.has_face_data;
            const action = hasFaceData ? 'update' : 'register';
            setRegistrationAction(action);

            let saveResult;
            if (hasFaceData) {
                // Update existing face data
                saveResult = await updateFace({
                    employee_id: profile.employee_pk,
                    descriptor: result.embedding,
                    confidence: result.confidence || 0.95,
                });
            } else {
                // Register new face data
                saveResult = await registerFace({
                    employee_id: profile.employee_pk,
                    descriptor: result.embedding,
                    confidence: result.confidence || 0.95,
                    algorithm: 'deepface-arcface',
                    model_version: result.model || 'ArcFace',
                });
            }

            if (!saveResult.success) {
                throw new Error(saveResult.message || 'Gagal menyimpan data wajah');
            }

            // Invalidate queries
            await Promise.all([
                queryClient.invalidateQueries({ queryKey: faceRecognitionKeys.registeredFaces() }),
                queryClient.invalidateQueries({ queryKey: faceRecognitionKeys.statistics() }),
                queryClient.invalidateQueries({ queryKey: faceRecognitionKeys.faceData(profile.employee_pk) }),
            ]);
            await refetchFaceData();

            stopCamera();
            setEnrollmentStep('success');

            // Auto-close after success
            setTimeout(() => {
                handleCloseFaceEnrollment();
            }, 2000);
        } catch (error: any) {
            console.error('Face enrollment error:', error);
            setEnrollmentError(
                error.response?.data?.message ||
                error.message ||
                'Gagal mendaftarkan wajah. Silakan coba lagi'
            );
            setEnrollmentStep('error');
        }
    };

    const handleCloseFaceEnrollment = () => {
        stopCamera();
        setFaceEnrollmentOpen(false);
        setEnrollmentStep('idle');
        setEnrollmentError(null);
        setCountdown(3);
        setRegistrationAction(null);
    };

    const handleDeleteFace = async () => {
        if (!faceData || !faceData.has_face_data) return;

        try {
            await deleteFaceMutation.mutateAsync(profile?.employee_pk || '');
            setDeleteFaceOpen(false);
            await refetchFaceData();
            setSuccessMessage('Data wajah berhasil dihapus');
            setTimeout(() => setSuccessMessage(null), 3000);
        } catch (_error) {
            // Error handled by mutation
        }
    };

    // Helper: Get user initials
    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map(n => n[0])
            .join('')
            .toUpperCase()
            .substring(0, 2);
    };

    return {
        // Data
        profile,
        statistics,
        faceData,
        isLoading,

        // Mutations (for checking isPending state)
        updateProfileMutation: updateProfile,
        changePasswordMutation: changePassword,
        uploadAvatarMutation: uploadAvatar,
        deleteAvatarMutation: deleteAvatar,
        deleteAccountMutation: deleteAccount,
        deleteFaceMutation,

        // Refs
        fileInputRef,
        videoRef,

        // Profile editing state
        editMode,
        setEditMode,
        formData,
        setFormData,

        // Password state
        passwordDialog,
        setPasswordDialog,
        passwordForm,
        setPasswordForm,
        passwordError,

        // Delete account state
        deleteDialog,
        setDeleteDialog,
        deletePassword,
        setDeletePassword,
        deleteError,

        // Face enrollment state
        faceEnrollmentOpen,
        setFaceEnrollmentOpen,
        deleteFaceOpen,
        setDeleteFaceOpen,
        enrollmentStep,
        enrollmentError,
        countdown,
        registrationAction,
        cameraError,

        // Messages
        successMessage,
        setSuccessMessage,

        // Handlers
        handleEditClick,
        handleSaveProfile,
        handleChangePassword,
        handleAvatarUpload,
        handleDeleteAvatar,
        handleDeleteAccount,
        handleStartFaceEnrollment,
        handleCaptureFace,
        handleCloseFaceEnrollment,
        handleDeleteFace,

        // Helpers
        getInitials,
    };
}
