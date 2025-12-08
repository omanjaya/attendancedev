import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
    getAcademicYears,
    createAcademicYear,
    updateAcademicYear,
    deleteAcademicYear,
    getEmployeeTypes,
    createEmployeeType,
    updateEmployeeType,
    deleteEmployeeType,
    getSubjects,
    createSubject,
    updateSubject,
    deleteSubject,
    getClassrooms,
    createClassroom,
    updateClassroom,
    deleteClassroom,
    getPeriods,
    createPeriod,
    updatePeriod,
    deletePeriod,
} from '@/lib/api/master-data';
import { useNotificationStore } from '@/stores';

export function useMasterData() {
    const queryClient = useQueryClient();
    const { success, error: showError } = useNotificationStore();

    // Helper to create mutations
    const createMutation = <TVariables = any>(apiFunc: (variables: TVariables) => Promise<any>, key: string, successMsg: string) => {
        return useMutation({
            mutationFn: apiFunc,
            onSuccess: () => {
                queryClient.invalidateQueries({ queryKey: [key] });
                success('Berhasil', successMsg);
            },
            onError: (err: any) => {
                const message = err?.response?.data?.message || 'Terjadi kesalahan';
                showError('Error', message);
            },
        });
    };

    // Academic Years
    const academicYearsQuery = (params?: any) => useQuery({
        queryKey: ['academic-years', params],
        queryFn: () => getAcademicYears(params),
    });
    const createAcademicYearMutation = createMutation(createAcademicYear, 'academic-years', 'Tahun ajaran berhasil dibuat');
    const updateAcademicYearMutation = createMutation(({ id, data }: any) => updateAcademicYear(id, data), 'academic-years', 'Tahun ajaran berhasil diupdate');
    const deleteAcademicYearMutation = createMutation(deleteAcademicYear, 'academic-years', 'Tahun ajaran berhasil dihapus');

    // Employee Types
    const employeeTypesQuery = (params?: any) => useQuery({
        queryKey: ['employee-types', params],
        queryFn: () => getEmployeeTypes(params),
    });
    const createEmployeeTypeMutation = createMutation(createEmployeeType, 'employee-types', 'Jenis pegawai berhasil dibuat');
    const updateEmployeeTypeMutation = createMutation(({ id, data }: any) => updateEmployeeType(id, data), 'employee-types', 'Jenis pegawai berhasil diupdate');
    const deleteEmployeeTypeMutation = createMutation(deleteEmployeeType, 'employee-types', 'Jenis pegawai berhasil dihapus');

    // Subjects
    const subjectsQuery = (params?: any) => useQuery({
        queryKey: ['subjects', params],
        queryFn: () => getSubjects(params),
    });
    const createSubjectMutation = createMutation(createSubject, 'subjects', 'Mata pelajaran berhasil dibuat');
    const updateSubjectMutation = createMutation(({ id, data }: any) => updateSubject(id, data), 'subjects', 'Mata pelajaran berhasil diupdate');
    const deleteSubjectMutation = createMutation(deleteSubject, 'subjects', 'Mata pelajaran berhasil dihapus');

    // Classrooms
    const classroomsQuery = (params?: any) => useQuery({
        queryKey: ['classrooms', params],
        queryFn: () => getClassrooms(params),
    });
    const createClassroomMutation = createMutation(createClassroom, 'classrooms', 'Kelas berhasil dibuat');
    const updateClassroomMutation = createMutation(({ id, data }: any) => updateClassroom(id, data), 'classrooms', 'Kelas berhasil diupdate');
    const deleteClassroomMutation = createMutation(deleteClassroom, 'classrooms', 'Kelas berhasil dihapus');

    // Periods
    const periodsQuery = (params?: any) => useQuery({
        queryKey: ['periods', params],
        queryFn: () => getPeriods(params),
    });
    const createPeriodMutation = createMutation(createPeriod, 'periods', 'Periode berhasil dibuat');
    const updatePeriodMutation = createMutation(({ id, data }: any) => updatePeriod(id, data), 'periods', 'Periode berhasil diupdate');
    const deletePeriodMutation = createMutation(deletePeriod, 'periods', 'Periode berhasil dihapus');

    return {
        academicYears: {
            useQuery: academicYearsQuery,
            create: createAcademicYearMutation.mutateAsync,
            update: updateAcademicYearMutation.mutateAsync,
            delete: deleteAcademicYearMutation.mutateAsync,
        },
        employeeTypes: {
            useQuery: employeeTypesQuery,
            create: createEmployeeTypeMutation.mutateAsync,
            update: updateEmployeeTypeMutation.mutateAsync,
            delete: deleteEmployeeTypeMutation.mutateAsync,
        },
        subjects: {
            useQuery: subjectsQuery,
            create: createSubjectMutation.mutateAsync,
            update: updateSubjectMutation.mutateAsync,
            delete: deleteSubjectMutation.mutateAsync,
        },
        classrooms: {
            useQuery: classroomsQuery,
            create: createClassroomMutation.mutateAsync,
            update: updateClassroomMutation.mutateAsync,
            delete: deleteClassroomMutation.mutateAsync,
        },
        periods: {
            useQuery: periodsQuery,
            create: createPeriodMutation.mutateAsync,
            update: updatePeriodMutation.mutateAsync,
            delete: deletePeriodMutation.mutateAsync,
        },
    };
}

// Standalone hooks for direct use
export function useEmployeeTypes(params?: any) {
    return useQuery({
        queryKey: ['employee-types', params],
        queryFn: () => getEmployeeTypes(params),
    });
}

export function useAcademicYears(params?: any) {
    return useQuery({
        queryKey: ['academic-years', params],
        queryFn: () => getAcademicYears(params),
    });
}

export function useDepartments(params?: any) {
    return useQuery({
        queryKey: ['departments', params],
        queryFn: async () => {
            const { getDepartments } = await import('@/lib/api/master-data');
            return getDepartments(params);
        },
    });
}

export function usePositions(params?: any) {
    return useQuery({
        queryKey: ['positions', params],
        queryFn: async () => {
            const { getPositions } = await import('@/lib/api/master-data');
            return getPositions(params);
        },
    });
}

export function useSubjects(params?: any) {
    return useQuery({
        queryKey: ['subjects', params],
        queryFn: () => getSubjects(params),
    });
}

export function useClassrooms(params?: any) {
    return useQuery({
        queryKey: ['classrooms', params],
        queryFn: () => getClassrooms(params),
    });
}

export function usePeriods(params?: any) {
    return useQuery({
        queryKey: ['periods', params],
        queryFn: () => getPeriods(params),
    });
}
