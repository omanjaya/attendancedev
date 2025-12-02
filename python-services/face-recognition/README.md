# Face Recognition Service (DeepFace)

Production-grade face recognition service using DeepFace with ArcFace model.

## Features

- **ArcFace Model**: 99.82% accuracy with 512-d embeddings
- **Anti-Spoofing**: Built-in liveness detection
- **RetinaFace Detector**: Best-in-class face detection
- **Image Quality Checks**: Blur and brightness detection
- **RESTful API**: FastAPI with automatic documentation

## Setup

```bash
# Create virtual environment
python3 -m venv venv
source venv/bin/activate  # Linux/Mac
# or
venv\Scripts\activate  # Windows

# Install dependencies
pip install -r requirements.txt

# Run service
python main.py
```

Service will run on `http://localhost:8001`

## API Endpoints

### 1. Extract Embedding
```bash
POST /extract-embedding
Content-Type: multipart/form-data

Parameters:
- image: Image file OR
- image_base64: Base64 encoded image

Response:
{
  "success": true,
  "embedding": [512-d array],
  "dimension": 512,
  "confidence": 0.99,
  "quality": {...}
}
```

### 2. Check Liveness
```bash
POST /check-liveness
Content-Type: multipart/form-data

Parameters:
- image: Image file

Response:
{
  "success": true,
  "is_live": true,
  "result": "Real",
  "confidence": 0.95
}
```

### 3. Verify Face
```bash
POST /verify-face
Content-Type: multipart/form-data

Parameters:
- image: Image file
- known_faces_json: JSON array of known faces

Response:
{
  "success": true,
  "matched": true,
  "employee": {...},
  "distance": 0.45,
  "similarity": 0.55,
  "confidence": 0.92,
  "is_live": true
}
```

## Configuration

Edit `CONFIG` in `main.py`:

```python
CONFIG = {
    "model_name": "ArcFace",  # Or: Buffalo_L, Facenet512, etc
    "detector_backend": "retinaface",
    "distance_metric": "cosine",
    "threshold": 0.68,  # Lower = stricter matching
    "embedding_dimension": 512,
}
```

## Model Options

- **ArcFace** (default): 99.82% accuracy, 512-d
- **Buffalo_L**: 99.85% accuracy (best)
- **Facenet512**: 99.65% accuracy, 512-d
- **GhostFaceNet**: Lightweight, 50ms inference

## Performance

- Enrollment: 2-4 seconds per face
- Verification: 1-2 seconds per face
- Liveness check: 500-800ms
- GPU Memory: 500-800MB

## Integration with Laravel

Laravel backend should proxy requests to this service at `http://127.0.0.1:8001`.

See Laravel controller implementation for details.
