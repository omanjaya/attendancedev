# Face Recognition Service

Python Flask service for server-side face recognition processing using `face_recognition` library (dlib-based).

## Features

- ✅ Server-side face detection & encoding extraction
- ✅ Face verification against known encodings
- ✅ Automatic image resizing & optimization
- ✅ RESTful API with JSON responses
- ✅ Production-ready with Gunicorn
- ✅ Docker support

## Requirements

- Python 3.9+
- CMake (for dlib)
- C++ compiler
- 2GB+ RAM

## Installation (Development)

### 1. Install System Dependencies

**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install -y build-essential cmake libopenblas-dev \
    liblapack-dev libx11-dev libgtk-3-dev python3-dev python3-pip
```

**macOS:**
```bash
brew install cmake
```

### 2. Create Virtual Environment

```bash
cd python-face-service
python3 -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
```

### 3. Install Python Dependencies

```bash
pip install --upgrade pip
pip install -r requirements.txt
```

**Note:** Installing `dlib` may take 5-10 minutes as it compiles from source.

### 4. Configure Environment

```bash
cp .env.example .env
# Edit .env with your settings
```

### 5. Run Development Server

```bash
python app.py
```

Server akan berjalan di `http://localhost:5000`

## Installation (Production)

### Option 1: Systemd Service

```bash
# 1. Create directories
sudo mkdir -p /var/www/attendance/python-face-service
sudo mkdir -p /var/log/face-recognition

# 2. Copy files
sudo cp -r ./* /var/www/attendance/python-face-service/

# 3. Create virtual environment
cd /var/www/attendance/python-face-service
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt

# 4. Set permissions
sudo chown -R www-data:www-data /var/www/attendance/python-face-service
sudo chown -R www-data:www-data /var/log/face-recognition

# 5. Install systemd service
sudo cp face-recognition.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable face-recognition
sudo systemctl start face-recognition

# 6. Check status
sudo systemctl status face-recognition
```

### Option 2: Docker

```bash
# Build image
docker build -t face-recognition-service .

# Run container
docker run -d \
  --name face-recognition \
  -p 5000:5000 \
  -e FACE_RECOGNITION_TOLERANCE=0.6 \
  --restart unless-stopped \
  face-recognition-service

# Check logs
docker logs -f face-recognition
```

## API Endpoints

### 1. Health Check

```bash
GET /health
```

**Response:**
```json
{
  "status": "ok",
  "service": "face-recognition",
  "version": "1.0.0",
  "timestamp": "2024-12-01T02:30:00"
}
```

### 2. Extract Face Encoding

Extract 128-d face descriptor from image.

```bash
POST /extract-encoding
Content-Type: application/json

{
  "image": "data:image/jpeg;base64,/9j/4AAQ..."
}
```

**Response:**
```json
{
  "success": true,
  "encoding": [0.123, -0.456, ...],  // 128 values
  "confidence": 0.85,
  "message": "Face encoding extracted successfully"
}
```

### 3. Verify Face

Verify uploaded face against known encodings.

```bash
POST /verify-face
Content-Type: application/json

{
  "image": "data:image/jpeg;base64,/9j/4AAQ...",
  "known_encodings": [
    {
      "employee_id": 1,
      "name": "John Doe",
      "encoding": [0.123, -0.456, ...]
    }
  ],
  "tolerance": 0.6  // Optional
}
```

**Response (Match):**
```json
{
  "success": true,
  "matched": true,
  "employee": {
    "employee_id": 1,
    "name": "John Doe"
  },
  "distance": 0.42,
  "similarity": 0.85,
  "confidence": 0.90,
  "message": "Face matched successfully"
}
```

**Response (No Match):**
```json
{
  "success": true,
  "matched": false,
  "confidence": 0.85,
  "message": "No matching face found"
}
```

## Configuration

Edit `.env` file:

```env
# Server
PORT=5000
DEBUG=False

# Face Recognition
FACE_RECOGNITION_TOLERANCE=0.6  # Lower = stricter (0.4-0.7 recommended)
FACE_RECOGNITION_MODEL=hog      # 'hog' (CPU, faster) or 'cnn' (GPU, accurate)
MAX_IMAGE_SIZE=1024             # Max image dimension
```

### Face Recognition Tolerance

- **0.4**: Very strict (may reject valid faces)
- **0.5**: Strict (good for security)
- **0.6**: Balanced (recommended)
- **0.7**: Lenient (may accept similar faces)

### Face Recognition Model

- **hog**: Faster, works on CPU, good for most cases
- **cnn**: More accurate, requires GPU, slower on CPU

## Performance

**Hardware Requirements:**
- **Minimal**: 2 vCPU, 2GB RAM
- **Recommended**: 4 vCPU, 4GB RAM
- **With GPU**: CUDA-compatible GPU (for CNN model)

**Benchmarks (HOG model, CPU):**
- Face detection: ~200-500ms
- Encoding extraction: ~300-700ms
- Face comparison: ~1-5ms
- **Total**: ~500-1200ms per request

**Optimization Tips:**
1. Use `hog` model for CPU (faster)
2. Resize images to 640x480 before upload
3. Use Redis caching for known encodings
4. Scale horizontally with multiple workers

## Monitoring

### Check Service Status

```bash
# Systemd
sudo systemctl status face-recognition

# Docker
docker ps
docker logs face-recognition
```

### View Logs

```bash
# Systemd
sudo tail -f /var/log/face-recognition/error.log

# Docker
docker logs -f face-recognition
```

### Restart Service

```bash
# Systemd
sudo systemctl restart face-recognition

# Docker
docker restart face-recognition
```

## Troubleshooting

### Error: "No module named 'dlib'"

**Solution:**
```bash
# Install CMake first
sudo apt-get install cmake

# Reinstall dlib
pip install --force-reinstall dlib
```

### Error: "Failed to load image"

**Cause:** Invalid base64 image data

**Solution:** Ensure image is properly encoded:
```javascript
// Frontend
canvas.toDataURL('image/jpeg', 0.8)  // Include data URL prefix
```

### Error: "No face detected"

**Cause:** Poor image quality or no face visible

**Solution:**
- Ensure good lighting
- Face should be clearly visible
- Minimum face size: 50x50 pixels
- Use frontal face pose

### High Memory Usage

**Solution:**
- Reduce `MAX_IMAGE_SIZE` in .env
- Reduce number of workers
- Enable Redis caching

## Security

### API Key Authentication (Optional)

Add to `app.py`:

```python
from functools import wraps

def require_api_key(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        api_key = request.headers.get('X-API-Key')
        if api_key != os.getenv('API_KEY'):
            return jsonify({'error': 'Invalid API key'}), 401
        return f(*args, **kwargs)
    return decorated

# Apply to endpoints
@app.route('/verify-face', methods=['POST'])
@require_api_key
def verify_face():
    # ...
```

### Rate Limiting

Install Flask-Limiter:

```bash
pip install Flask-Limiter
```

```python
from flask_limiter import Limiter
from flask_limiter.util import get_remote_address

limiter = Limiter(
    app=app,
    key_func=get_remote_address,
    default_limits=["100 per hour"]
)

@app.route('/verify-face', methods=['POST'])
@limiter.limit("10 per minute")
def verify_face():
    # ...
```

## License

MIT License
