# Face-API.js Models

This directory should contain the Face-API.js models required for face detection and recognition.

## Required Models

Download the following model files from the [face-api.js models repository](https://github.com/justadudewhohacks/face-api.js/tree/master/weights):

### Core Models (Required)
- `tiny_face_detector_model-weights_manifest.json`
- `tiny_face_detector_model-shard1`
- `face_landmark_68_model-weights_manifest.json`
- `face_landmark_68_model-shard1`
- `face_recognition_model-weights_manifest.json`
- `face_recognition_model-shard1`
- `face_recognition_model-shard2`

### Optional Models
- `face_expression_model-weights_manifest.json`
- `face_expression_model-shard1`
- `ssd_mobilenetv1_model-weights_manifest.json`
- `ssd_mobilenetv1_model-shard1`
- `ssd_mobilenetv1_model-shard2`

## Quick Download

You can download all models using:

```bash
cd public/models
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/tiny_face_detector_model-weights_manifest.json
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/tiny_face_detector_model-shard1
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/face_landmark_68_model-weights_manifest.json
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/face_landmark_68_model-shard1
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/face_recognition_model-weights_manifest.json
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/face_recognition_model-shard1
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/face_recognition_model-shard2
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/face_expression_model-weights_manifest.json
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/face_expression_model-shard1
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/ssd_mobilenetv1_model-weights_manifest.json
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/ssd_mobilenetv1_model-shard1
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/ssd_mobilenetv1_model-shard2
```

## Model Sizes
- Tiny Face Detector: ~200KB
- Face Landmarks: ~350KB  
- Face Recognition: ~6.2MB
- Face Expressions: ~310KB
- SSD MobileNet: ~5.4MB

Total size: ~12.5MB

## Usage

The models will be automatically loaded by the FaceDetectionService when the face detection system initializes.