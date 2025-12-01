# Face-API.js Models

Models ini diperlukan untuk face recognition di frontend.

## Download Models

1. Download models dari GitHub:
   - **tiny_face_detector** model: https://github.com/justadudewhohacks/face-api.js/tree/master/weights
   - **face_landmark_68** model
   - **face_recognition** model

2. Letakkan semua file `.bin` dan manifest `.json` di folder ini (`/public/models/`)

## File yang Dibutuhkan:

```
public/models/
├── tiny_face_detector_model-weights_manifest.json
├── tiny_face_detector_model-shard1
├── face_landmark_68_model-weights_manifest.json
├── face_landmark_68_model-shard1
├── face_recognition_model-weights_manifest.json
├── face_recognition_model-shard1
└── face_recognition_model-shard2
```

## Cara Cepat (Manual Download):

```bash
cd /home/omanjaya/Project/attendancedev/frontend/public

# Buat folder models jika belum ada
mkdir -p models
cd models

# Download via wget (atau bisa manual download dari browser)
wget https://raw.githubusercontent.com/justadudewhohacks/face-api.js-models/master/tiny_face_detector/tiny_face_detector_model-weights_manifest.json
wget https://raw.githubusercontent.com/justadudewhohacks/face-api.js-models/master/tiny_face_detector/tiny_face_detector_model-shard1

wget https://raw.githubusercontent.com/justadudewhohacks/face-api.js-models/master/face_landmark_68/face_landmark_68_model-weights_manifest.json
wget https://raw.githubusercontent.com/justadudewhohacks/face-api.js-models/master/face_landmark_68/face_landmark_68_model-shard1

wget https://raw.githubusercontent.com/justadudewhohacks/face-api.js-models/master/face_recognition/face_recognition_model-weights_manifest.json
wget https://raw.githubusercontent.com/justadudewhohacks/face-api.js-models/master/face_recognition/face_recognition_model-shard1
wget https://raw.githubusercontent.com/justadudewhohacks/face-api.js-models/master/face_recognition/face_recognition_model-shard2
```

## Verifikasi

Setelah download, cek apakah semua file ada:
```bash
ls -lh /home/omanjaya/Project/attendancedev/frontend/public/models/
```

Total ukuran sekitar ~5 MB.
