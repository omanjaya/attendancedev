# Face-API.js Models

This directory should contain the Face-API.js model files for face detection and recognition.

## Download Models

Download the following model files from the Face-API.js GitHub repository:
https://github.com/justadudewhohacks/face-api.js/tree/master/weights

Required models:
1. **tiny_face_detector_model-weights_manifest.json** and **tiny_face_detector_model-shard1**
2. **face_landmark_68_model-weights_manifest.json** and **face_landmark_68_model-shard1**
3. **face_recognition_model-weights_manifest.json** and **face_recognition_model-shard1**
4. **face_expression_model-weights_manifest.json** and **face_expression_model-shard1**
5. **ssd_mobilenetv1_model-weights_manifest.json** and **ssd_mobilenetv1_model-shard1**

## Manual Download

You can download all models using these commands:

```bash
# Tiny Face Detector
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/tiny_face_detector_model-weights_manifest.json
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/tiny_face_detector_model-shard1

# Face Landmark 68
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/face_landmark_68_model-weights_manifest.json
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/face_landmark_68_model-shard1

# Face Recognition
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/face_recognition_model-weights_manifest.json
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/face_recognition_model-shard1

# Face Expression
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/face_expression_model-weights_manifest.json
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/face_expression_model-shard1

# SSD MobileNetV1
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/ssd_mobilenetv1_model-weights_manifest.json
wget https://github.com/justadudewhohacks/face-api.js/raw/master/weights/ssd_mobilenetv1_model-shard1
```

## Alternative: Use CDN

If you prefer to use a CDN, update the model loading path in enhanced-checkin.blade.php:

```javascript
// Change from:
faceapi.nets.tinyFaceDetector.loadFromUri('/models')

// To:
faceapi.nets.tinyFaceDetector.loadFromUri('https://justadudewhohacks.github.io/face-api.js/models')
```

Note: Using local models is recommended for production to avoid dependency on external services.