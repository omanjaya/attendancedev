import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { KnownFaceDescriptor, FaceRecognitionSettings } from '@/types/face-recognition';

// Enrollment step status
export type EnrollmentStep = 'idle' | 'frontal' | 'left' | 'right' | 'processing' | 'completed' | 'error';

// Identification result
export interface IdentificationResult {
  employeeId: string;
  employeeName: string;
  similarity: number;
  timestamp: number;
  photoUrl?: string;
}

interface FaceStoreState {
  // Registered faces cache
  registeredFaces: KnownFaceDescriptor[];
  lastFetchedAt: number | null;
  isFetchingFaces: boolean;

  // Enrollment state
  enrollmentStep: EnrollmentStep;
  enrollmentDescriptors: number[][];
  enrollmentError: string | null;

  // Identification state
  lastIdentification: IdentificationResult | null;
  identificationHistory: IdentificationResult[];

  // Settings
  settings: FaceRecognitionSettings;

  // Actions - Registered faces
  setRegisteredFaces: (faces: KnownFaceDescriptor[]) => void;
  addRegisteredFace: (face: KnownFaceDescriptor) => void;
  removeRegisteredFace: (employeeId: string | number) => void;
  clearRegisteredFaces: () => void;
  setFetchingFaces: (fetching: boolean) => void;

  // Actions - Enrollment
  setEnrollmentStep: (step: EnrollmentStep) => void;
  addEnrollmentDescriptor: (descriptor: number[]) => void;
  clearEnrollmentData: () => void;
  setEnrollmentError: (error: string | null) => void;

  // Actions - Identification
  setIdentificationResult: (result: IdentificationResult | null) => void;
  addToIdentificationHistory: (result: IdentificationResult) => void;
  clearIdentificationHistory: () => void;

  // Actions - Settings
  updateSettings: (settings: Partial<FaceRecognitionSettings>) => void;
  resetSettings: () => void;

  // Getters
  getRegisteredFaceCount: () => number;
  isCacheValid: () => boolean;
  getAverageDescriptor: () => number[] | null;
}

// Default settings
const defaultSettings: FaceRecognitionSettings = {
  confidence_threshold: 0.6,
  liveness_threshold: 0.8,
  max_detection_distance: 2.0,
  enable_liveness: false,
  enable_expressions: true,
  camera_resolution: 'medium',
};

// Cache validity duration (5 minutes)
const CACHE_DURATION = 5 * 60 * 1000;

export const useFaceStore = create<FaceStoreState>()(
  persist(
    (set, get) => ({
      // Initial state
      registeredFaces: [],
      lastFetchedAt: null,
      isFetchingFaces: false,
      enrollmentStep: 'idle',
      enrollmentDescriptors: [],
      enrollmentError: null,
      lastIdentification: null,
      identificationHistory: [],
      settings: defaultSettings,

      // Registered faces actions
      setRegisteredFaces: (faces) => {
        set({
          registeredFaces: faces,
          lastFetchedAt: Date.now(),
        });
      },

      addRegisteredFace: (face) => {
        set((state) => ({
          registeredFaces: [
            ...state.registeredFaces.filter(
              (f) => f.employeeId !== face.employeeId
            ),
            face,
          ],
          lastFetchedAt: Date.now(),
        }));
      },

      removeRegisteredFace: (employeeId) => {
        set((state) => ({
          registeredFaces: state.registeredFaces.filter(
            (f) => f.employeeId !== employeeId
          ),
        }));
      },

      clearRegisteredFaces: () => {
        set({
          registeredFaces: [],
          lastFetchedAt: null,
        });
      },

      setFetchingFaces: (fetching) => {
        set({ isFetchingFaces: fetching });
      },

      // Enrollment actions
      setEnrollmentStep: (step) => {
        set({ enrollmentStep: step });
      },

      addEnrollmentDescriptor: (descriptor) => {
        set((state) => ({
          enrollmentDescriptors: [...state.enrollmentDescriptors, descriptor],
        }));
      },

      clearEnrollmentData: () => {
        set({
          enrollmentStep: 'idle',
          enrollmentDescriptors: [],
          enrollmentError: null,
        });
      },

      setEnrollmentError: (error) => {
        set({
          enrollmentError: error,
          enrollmentStep: error ? 'error' : get().enrollmentStep,
        });
      },

      // Identification actions
      setIdentificationResult: (result) => {
        set({ lastIdentification: result });
      },

      addToIdentificationHistory: (result) => {
        set((state) => ({
          identificationHistory: [result, ...state.identificationHistory].slice(0, 50),
          lastIdentification: result,
        }));
      },

      clearIdentificationHistory: () => {
        set({
          identificationHistory: [],
          lastIdentification: null,
        });
      },

      // Settings actions
      updateSettings: (newSettings) => {
        set((state) => ({
          settings: { ...state.settings, ...newSettings },
        }));
      },

      resetSettings: () => {
        set({ settings: defaultSettings });
      },

      // Getters
      getRegisteredFaceCount: () => {
        return get().registeredFaces.length;
      },

      isCacheValid: () => {
        const { lastFetchedAt, registeredFaces } = get();
        if (!lastFetchedAt || registeredFaces.length === 0) {
          return false;
        }
        return Date.now() - lastFetchedAt < CACHE_DURATION;
      },

      // Calculate average descriptor from enrollment captures
      getAverageDescriptor: () => {
        const { enrollmentDescriptors } = get();
        if (enrollmentDescriptors.length === 0) {
          return null;
        }

        const length = enrollmentDescriptors[0].length;
        const average = new Array(length).fill(0);

        for (const descriptor of enrollmentDescriptors) {
          for (let i = 0; i < length; i++) {
            average[i] += descriptor[i];
          }
        }

        for (let i = 0; i < length; i++) {
          average[i] /= enrollmentDescriptors.length;
        }

        // Normalize the averaged descriptor
        let norm = 0;
        for (let i = 0; i < length; i++) {
          norm += average[i] * average[i];
        }
        norm = Math.sqrt(norm);

        if (norm > 0) {
          for (let i = 0; i < length; i++) {
            average[i] /= norm;
          }
        }

        return average;
      },
    }),
    {
      name: 'face-recognition-storage',
      partialize: (state) => ({
        // Only persist settings and history, not cached faces
        settings: state.settings,
        identificationHistory: state.identificationHistory.slice(0, 20),
      }),
    }
  )
);

// Selector hooks for performance optimization
export const useRegisteredFaces = () => useFaceStore((state) => state.registeredFaces);
export const useEnrollmentStep = () => useFaceStore((state) => state.enrollmentStep);
export const useEnrollmentDescriptors = () => useFaceStore((state) => state.enrollmentDescriptors);
export const useFaceSettings = () => useFaceStore((state) => state.settings);
export const useLastIdentification = () => useFaceStore((state) => state.lastIdentification);
