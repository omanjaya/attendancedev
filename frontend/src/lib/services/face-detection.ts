/**
 * Face Detection Service - DeepFace Integration
 * 
 * Uses browser native APIs for camera access and DeepFace server for face detection/recognition.
 * 
 * @module face-detection
 */

import type {
    FaceDetectionResult,
} from '@/types/face-recognition';
import { extractEmbeddingDeepFace, type DeepFaceExtractEmbeddingResponse } from '@/lib/api/face-recognition';

class FaceDetectionService {
    private stream: MediaStream | null = null;
    private isInitialized = false;

    /**
     * Initialize the service
     */
    async initialize(): Promise<void> {
        if (this.isInitialized) return;
        this.isInitialized = true;
        console.log('Face detection service initialized (DeepFace backend)');
    }

    /**
     * Start camera stream
     */
    async startCamera(videoElement: HTMLVideoElement): Promise<void> {
        if (this.stream) {
            this.stopCamera();
        }

        try {
            // Check if getUserMedia is supported
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Browser tidak mendukung akses kamera');
            }

            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    facingMode: 'user',
                },
                audio: false,
            });

            this.stream = stream;
            videoElement.srcObject = stream;

            return new Promise((resolve) => {
                videoElement.onloadedmetadata = () => {
                    videoElement.play();
                    resolve();
                };
            });
        } catch (error: unknown) {
            console.error('Failed to access camera:', error);

            // Provide user-friendly error messages
            if (error instanceof Error) {
                if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                    throw new Error('Izin kamera ditolak. Silakan aktifkan izin kamera di pengaturan browser.');
                } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                    throw new Error('Kamera tidak ditemukan. Pastikan perangkat memiliki kamera.');
                } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                    throw new Error('Kamera sedang digunakan aplikasi lain.');
                } else if (error.name === 'OverconstrainedError') {
                    throw new Error('Kamera tidak mendukung resolusi yang diminta.');
                }
            }

            throw new Error('Gagal mengakses kamera. Pastikan izin kamera diaktifkan.');
        }
    }

    /**
     * Stop camera stream
     */
    stopCamera(): void {
        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }
    }

    /**
     * Get display size from video element
     */
    getDisplaySize(videoElement: HTMLVideoElement): { width: number; height: number } {
        return {
            width: videoElement.videoWidth,
            height: videoElement.videoHeight,
        };
    }

    /**
     * Draw detection results on canvas
     */
    drawDetections(
        canvas: HTMLCanvasElement,
        detections: FaceDetectionResult[],
        dims: { width: number; height: number }
    ): void {
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        // Match canvas size to video
        canvas.width = dims.width;
        canvas.height = dims.height;

        // Clear previous drawings
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Draw each detection
        detections.forEach((d) => {
            const box = d.detection.box;

            // Draw box
            ctx.strokeStyle = '#22c55e'; // Green
            ctx.lineWidth = 3;
            ctx.strokeRect(box.x, box.y, box.width, box.height);

            // Draw confidence label
            const confidence = Math.round(d.detection.score * 100);
            ctx.fillStyle = '#22c55e';
            ctx.font = 'bold 14px sans-serif';
            ctx.fillText(`${confidence}%`, box.x, box.y - 5);
        });
    }

    /**
     * Draw face guide overlay (oval guide for user positioning)
     */
    drawFaceGuide(
        canvas: HTMLCanvasElement,
        dims: { width: number; height: number },
        detected: boolean = false
    ): void {
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        canvas.width = dims.width;
        canvas.height = dims.height;

        const centerX = dims.width / 2;
        const centerY = dims.height / 2;
        const radiusX = dims.width * 0.25;
        const radiusY = dims.height * 0.35;

        // Draw oval guide
        ctx.beginPath();
        ctx.ellipse(centerX, centerY, radiusX, radiusY, 0, 0, 2 * Math.PI);
        ctx.strokeStyle = detected ? '#22c55e' : '#64748b';
        ctx.lineWidth = 3;
        ctx.setLineDash([10, 5]);
        ctx.stroke();
        ctx.setLineDash([]);
    }

    /**
     * Capture image from video element
     */
    async captureImage(videoElement: HTMLVideoElement): Promise<File> {
        const canvas = document.createElement('canvas');
        canvas.width = videoElement.videoWidth;
        canvas.height = videoElement.videoHeight;
        const ctx = canvas.getContext('2d');
        if (!ctx) throw new Error('Could not get canvas context');

        ctx.drawImage(videoElement, 0, 0);

        return new Promise((resolve, reject) => {
            canvas.toBlob((blob) => {
                if (blob) {
                    const file = new File([blob], `capture-${Date.now()}.jpg`, { type: 'image/jpeg' });
                    resolve(file);
                } else {
                    reject(new Error('Failed to capture image'));
                }
            }, 'image/jpeg', 0.95);
        });
    }

    /**
     * Capture image from HTMLImageElement
     */
    async captureImageFromElement(imageElement: HTMLImageElement): Promise<File> {
        const canvas = document.createElement('canvas');
        canvas.width = imageElement.naturalWidth;
        canvas.height = imageElement.naturalHeight;
        const ctx = canvas.getContext('2d');
        if (!ctx) throw new Error('Could not get canvas context');

        ctx.drawImage(imageElement, 0, 0);

        return new Promise((resolve, reject) => {
            canvas.toBlob((blob) => {
                if (blob) {
                    const file = new File([blob], `capture-${Date.now()}.jpg`, { type: 'image/jpeg' });
                    resolve(file);
                } else {
                    reject(new Error('Failed to capture image'));
                }
            }, 'image/jpeg', 0.95);
        });
    }

    /**
     * Capture image as base64 string
     */
    async captureBase64(videoElement: HTMLVideoElement): Promise<string> {
        const canvas = document.createElement('canvas');
        canvas.width = videoElement.videoWidth;
        canvas.height = videoElement.videoHeight;
        const ctx = canvas.getContext('2d');
        if (!ctx) throw new Error('Could not get canvas context');

        ctx.drawImage(videoElement, 0, 0);
        return canvas.toDataURL('image/jpeg', 0.95);
    }

    /**
     * Cleanup service
     */
    destroy(): void {
        this.stopCamera();
        this.isInitialized = false;
    }

    /**
     * Detect faces using DeepFace server
     * Returns a simple detection result for UI feedback
     */
    async detectFaces(videoOrImage: HTMLVideoElement | HTMLImageElement): Promise<FaceDetectionResult[]> {
        try {
            // Capture image from video or image element
            let imageFile: File;
            if (videoOrImage instanceof HTMLVideoElement) {
                imageFile = await this.captureImage(videoOrImage);
            } else {
                imageFile = await this.captureImageFromElement(videoOrImage);
            }

            // Call DeepFace to extract embedding (which also detects faces)
            const response: DeepFaceExtractEmbeddingResponse = await extractEmbeddingDeepFace(imageFile);

            if (response.success && response.embedding) {
                // Return detection result with face found
                return [{
                    detection: {
                        box: response.quality?.blur_score !== undefined ? {
                            x: 100,
                            y: 80,
                            width: 200,
                            height: 250
                        } : { x: 100, y: 80, width: 200, height: 250 },
                        score: response.confidence || 0.9
                    },
                    descriptor: new Float32Array(response.embedding)
                }];
            }

            return [];
        } catch (error) {
            console.error('Face detection error:', error);
            return [];
        }
    }

    /**
     * Fast face detection - same as detectFaces but for polling
     */
    async detectFacesFast(videoElement: HTMLVideoElement): Promise<FaceDetectionResult[]> {
        return this.detectFaces(videoElement);
    }

    /**
     * Capture face descriptor using DeepFace
     * This is the main method for enrollment
     */
    async captureFaceDescriptor(videoElement: HTMLVideoElement): Promise<{
        descriptor: number[];
        detection: { box: { x: number; y: number; width: number; height: number }; score: number };
        confidence: number;
        imageData?: string;
    }> {
        // Capture image from video
        const imageFile = await this.captureImage(videoElement);

        // Call DeepFace to extract embedding
        const response: DeepFaceExtractEmbeddingResponse = await extractEmbeddingDeepFace(imageFile);

        if (!response.success || !response.embedding) {
            throw new Error(response.message || 'Tidak ada wajah terdeteksi');
        }

        // Check quality
        if (response.quality && !response.quality.quality_ok) {
            const issues = [];
            if (response.quality.blur_score < 100) issues.push('gambar blur');
            if (response.quality.brightness < 50) issues.push('terlalu gelap');
            if (response.quality.brightness > 200) issues.push('terlalu terang');
            
            if (issues.length > 0) {
                throw new Error(`Kualitas foto kurang baik: ${issues.join(', ')}`);
            }
        }

        // Get base64 for storage
        const imageData = await this.captureBase64(videoElement);

        return {
            descriptor: response.embedding,
            detection: {
                box: { x: 100, y: 80, width: 200, height: 250 },
                score: response.confidence || 0.9
            },
            confidence: response.confidence || 0.9,
            imageData
        };
    }

    /**
     * Recognize face - use DeepFace verify endpoint instead
     */
    async recognizeFace(_videoElement: HTMLVideoElement, _knownDescriptors: unknown[]): Promise<{
        success: boolean;
        matched?: boolean;
        matchedId?: string | number;
        confidence: number;
        message?: string;
    }> {
        console.warn('recognizeFace: Use verifyFaceDeepFace API instead for face verification');
        return {
            success: false,
            confidence: 0,
            message: 'Use verifyFaceDeepFace API for face verification',
        };
    }
}

export const faceDetectionService = new FaceDetectionService();
