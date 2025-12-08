# ============================================
# Python DeepFace Service
# ============================================
FROM python:3.10-slim-bookworm

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    libgl1 \
    libglib2.0-0 \
    libsm6 \
    libxext6 \
    libxrender1 \
    libgomp1 \
    curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy requirements first for better caching
COPY python-services/face-recognition/requirements.txt .

# Install Python dependencies
RUN pip install --no-cache-dir --upgrade pip \
    && pip install --no-cache-dir -r requirements.txt

# Pre-download DeepFace models to avoid runtime download
RUN python -c "from deepface import DeepFace; DeepFace.build_model('ArcFace')" || true

# Copy application code
COPY python-services/face-recognition/ .

# Create directories for face data
RUN mkdir -p /app/face_data /app/logs

# Set environment variables
ENV PYTHONUNBUFFERED=1
ENV DEEPFACE_HOME=/app/.deepface

# Expose port
EXPOSE 8001

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost:8001/health || exit 1

# Run the service
CMD ["python", "main.py"]
