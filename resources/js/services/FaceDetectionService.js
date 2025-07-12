import * as faceapi from 'face-api.js';

class FaceDetectionService {
    constructor() {
        this.isInitialized = false;
        this.currentStream = null;
        this.faceDescriptors = new Map();
    }

    async initialize() {
        if (this.isInitialized) return;

        try {
            // Load face-api.js models
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
                faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
                faceapi.nets.faceRecognitionNet.loadFromUri('/models'),
                faceapi.nets.faceExpressionNet.loadFromUri('/models'),
                faceapi.nets.ssdMobilenetv1.loadFromUri('/models')
            ]);

            this.isInitialized = true;
            console.log('Face detection models loaded successfully');
        } catch (error) {
            console.error('Failed to load face detection models:', error);
            throw new Error('Face detection initialization failed');
        }
    }

    async startCamera(videoElement) {
        try {
            const constraints = {
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user'
                }
            };

            this.currentStream = await navigator.mediaDevices.getUserMedia(constraints);
            videoElement.srcObject = this.currentStream;

            return new Promise((resolve) => {
                videoElement.onloadedmetadata = () => {
                    videoElement.play();
                    resolve();
                };
            });
        } catch (error) {
            console.error('Failed to start camera:', error);
            throw new Error('Camera access denied or not available');
        }
    }

    stopCamera() {
        if (this.currentStream) {
            this.currentStream.getTracks().forEach(track => track.stop());
            this.currentStream = null;
        }
    }

    async detectFaces(input) {
        if (!this.isInitialized) {
            throw new Error('Face detection not initialized');
        }

        try {
            const detections = await faceapi
                .detectAllFaces(input, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptors()
                .withFaceExpressions();

            return detections;
        } catch (error) {
            console.error('Face detection failed:', error);
            return [];
        }
    }

    async captureFaceDescriptor(videoElement, employeeId) {
        const detections = await this.detectFaces(videoElement);
        
        if (detections.length === 0) {
            throw new Error('No face detected. Please ensure your face is clearly visible.');
        }

        if (detections.length > 1) {
            throw new Error('Multiple faces detected. Please ensure only one person is in frame.');
        }

        const faceDescriptor = detections[0].descriptor;
        this.faceDescriptors.set(employeeId, faceDescriptor);
        
        return {
            descriptor: Array.from(faceDescriptor),
            landmarks: detections[0].landmarks.positions,
            confidence: detections[0].detection.score
        };
    }

    async recognizeFace(videoElement, knownDescriptors = []) {
        const detections = await this.detectFaces(videoElement);
        
        if (detections.length === 0) {
            return { success: false, message: 'No face detected' };
        }

        if (detections.length > 1) {
            return { success: false, message: 'Multiple faces detected' };
        }

        const detection = detections[0];
        const threshold = 0.6; // Similarity threshold

        // Compare against known descriptors
        for (const known of knownDescriptors) {
            const distance = faceapi.euclideanDistance(detection.descriptor, known.descriptor);
            const similarity = 1 - distance;

            if (similarity > threshold) {
                return {
                    success: true,
                    employeeId: known.employeeId,
                    confidence: similarity,
                    landmarks: detection.landmarks.positions
                };
            }
        }

        return { 
            success: false, 
            message: 'Face not recognized',
            confidence: 0
        };
    }

    drawDetections(canvas, detections, displaySize) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (detections.length === 0) return;

        // Resize detections to match display size
        const resizedDetections = faceapi.resizeResults(detections, displaySize);

        // Draw face boxes
        resizedDetections.forEach(detection => {
            const box = detection.detection.box;
            ctx.strokeStyle = '#00ff00';
            ctx.lineWidth = 2;
            ctx.strokeRect(box.x, box.y, box.width, box.height);

            // Draw confidence score
            const confidence = Math.round(detection.detection.score * 100);
            ctx.fillStyle = '#00ff00';
            ctx.font = '16px Arial';
            ctx.fillText(`${confidence}%`, box.x, box.y - 10);

            // Draw landmarks
            if (detection.landmarks) {
                const landmarks = detection.landmarks.positions;
                ctx.fillStyle = '#ff0000';
                landmarks.forEach(point => {
                    ctx.beginPath();
                    ctx.arc(point.x, point.y, 1, 0, 2 * Math.PI);
                    ctx.fill();
                });
            }

            // Draw expressions
            if (detection.expressions) {
                const expressions = detection.expressions.asSortedArray();
                const topExpression = expressions[0];
                ctx.fillStyle = '#0099ff';
                ctx.fillText(
                    `${topExpression.expression}: ${Math.round(topExpression.probability * 100)}%`,
                    box.x, box.y + box.height + 20
                );
            }
        });
    }

    async validateImage(imageFile) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = async () => {
                try {
                    const detections = await this.detectFaces(img);
                    
                    if (detections.length === 0) {
                        resolve({ valid: false, message: 'No face detected in image' });
                        return;
                    }

                    if (detections.length > 1) {
                        resolve({ valid: false, message: 'Multiple faces detected in image' });
                        return;
                    }

                    const detection = detections[0];
                    const minConfidence = 0.5;
                    
                    if (detection.detection.score < minConfidence) {
                        resolve({ 
                            valid: false, 
                            message: `Face detection confidence too low: ${Math.round(detection.detection.score * 100)}%` 
                        });
                        return;
                    }

                    resolve({ 
                        valid: true, 
                        descriptor: Array.from(detection.descriptor),
                        confidence: detection.detection.score
                    });
                } catch (error) {
                    resolve({ valid: false, message: 'Error processing image' });
                }
            };
            
            img.onerror = () => {
                resolve({ valid: false, message: 'Invalid image file' });
            };
            
            img.src = URL.createObjectURL(imageFile);
        });
    }

    // Utility methods
    getDisplaySize(element) {
        return { width: element.offsetWidth, height: element.offsetHeight };
    }

    async downloadModels() {
        // This would be called during app initialization to ensure models are cached
        if (!this.isInitialized) {
            await this.initialize();
        }
    }

    // Cleanup method
    destroy() {
        this.stopCamera();
        this.faceDescriptors.clear();
        this.isInitialized = false;
    }
}

export default new FaceDetectionService();