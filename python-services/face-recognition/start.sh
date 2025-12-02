#!/bin/bash

# Startup script for Face Recognition Service

echo "🚀 Starting Face Recognition Service (DeepFace)..."

# Activate virtual environment
source venv/bin/activate

# Check if dependencies are installed
if [ ! -d "venv/lib/python3.12/site-packages/deepface" ]; then
    echo "📦 Installing dependencies..."
    pip install -r requirements.txt
fi

# Start FastAPI service
echo "✅ Service starting on http://127.0.0.1:8001"
python main.py
