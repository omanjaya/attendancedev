#!/bin/bash
# Stop All DeepFace Instances

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BASE_PORT=8001
MAX_INSTANCES=10

echo "========================================"
echo "Stopping DeepFace Cluster"
echo "========================================"
echo ""

# Stop by PID files
for i in $(seq 0 $((MAX_INSTANCES - 1))); do
    PORT=$((BASE_PORT + i))
    PID_FILE="$SCRIPT_DIR/logs/deepface-$PORT.pid"

    if [ -f "$PID_FILE" ]; then
        PID=$(cat "$PID_FILE")
        if ps -p $PID > /dev/null 2>&1; then
            echo "Stopping instance on port $PORT (PID: $PID)..."
            kill $PID
            rm "$PID_FILE"
        else
            echo "Instance on port $PORT already stopped"
            rm "$PID_FILE"
        fi
    fi
done

# Fallback: kill all python processes running main.py
echo ""
echo "Cleaning up any remaining processes..."
pkill -f "python.*main.py"

sleep 2

echo ""
echo "========================================"
echo "All DeepFace instances stopped"
echo "========================================"
