#!/bin/bash
# Check Status of DeepFace Cluster

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BASE_PORT=8001
MAX_INSTANCES=10

echo "========================================"
echo "DeepFace Cluster Status"
echo "========================================"
echo ""

RUNNING=0
STOPPED=0

for i in $(seq 0 $((MAX_INSTANCES - 1))); do
    PORT=$((BASE_PORT + i))

    # Check if port is listening
    if lsof -Pi :$PORT -sTCP:LISTEN -t >/dev/null 2>&1; then
        # Check health endpoint
        if RESPONSE=$(curl -s "http://127.0.0.1:$PORT/health" 2>/dev/null); then
            echo "Port $PORT: ✓ HEALTHY"
            echo "  Response: $RESPONSE"
            RUNNING=$((RUNNING + 1))
        else
            echo "Port $PORT: ⚠ RUNNING but unhealthy"
            RUNNING=$((RUNNING + 1))
        fi
    else
        # Only show first few stopped instances
        if [ $STOPPED -lt 1 ]; then
            echo "Port $PORT: ✗ STOPPED"
        fi
        STOPPED=$((STOPPED + 1))
    fi
    echo ""
done

echo "========================================"
echo "Summary: $RUNNING running, $STOPPED stopped"
echo "========================================"

# Show recent logs if any instance is running
if [ $RUNNING -gt 0 ]; then
    echo ""
    echo "Recent errors (if any):"
    for i in $(seq 0 $((RUNNING - 1))); do
        PORT=$((BASE_PORT + i))
        LOG_FILE="$SCRIPT_DIR/logs/deepface-$PORT.log"
        if [ -f "$LOG_FILE" ]; then
            ERRORS=$(grep -i "error\|exception\|failed" "$LOG_FILE" | tail -3)
            if [ ! -z "$ERRORS" ]; then
                echo ""
                echo "Port $PORT errors:"
                echo "$ERRORS"
            fi
        fi
    done
fi
