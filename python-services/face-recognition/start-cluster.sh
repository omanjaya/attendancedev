#!/bin/bash
# Start Multiple DeepFace Instances for Load Balancing
# Usage: ./start-cluster.sh [number_of_instances]

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
NUM_INSTANCES=${1:-3}
BASE_PORT=8001

echo "========================================"
echo "Starting DeepFace Cluster"
echo "========================================"
echo "Instances: $NUM_INSTANCES"
echo "Base Port: $BASE_PORT"
echo "========================================"
echo ""

# Kill existing instances
echo "Cleaning up existing instances..."
pkill -f "python.*main.py" 2>/dev/null
sleep 2

# Start instances
for i in $(seq 0 $((NUM_INSTANCES - 1))); do
    PORT=$((BASE_PORT + i))
    LOG_FILE="$SCRIPT_DIR/logs/deepface-$PORT.log"
    PID_FILE="$SCRIPT_DIR/logs/deepface-$PORT.pid"

    # Create logs directory if not exists
    mkdir -p "$SCRIPT_DIR/logs"

    echo "Starting instance $((i + 1))/$NUM_INSTANCES on port $PORT..."

    # Start in background
    cd "$SCRIPT_DIR"
    nohup "$SCRIPT_DIR/venv/bin/python" main.py --port $PORT > "$LOG_FILE" 2>&1 &
    echo $! > "$PID_FILE"

    # Wait a bit before starting next instance
    sleep 2

    # Check if started successfully
    if curl -s "http://127.0.0.1:$PORT/health" > /dev/null 2>&1; then
        echo "✓ Instance on port $PORT started successfully (PID: $(cat $PID_FILE))"
    else
        echo "✗ Failed to start instance on port $PORT"
    fi

    echo ""
done

echo "========================================"
echo "DeepFace Cluster Status"
echo "========================================"
echo ""

# Show status of all instances
for i in $(seq 0 $((NUM_INSTANCES - 1))); do
    PORT=$((BASE_PORT + i))
    PID_FILE="$SCRIPT_DIR/logs/deepface-$PORT.pid"

    if [ -f "$PID_FILE" ]; then
        PID=$(cat "$PID_FILE")
        if ps -p $PID > /dev/null 2>&1; then
            echo "Port $PORT: RUNNING (PID: $PID)"
        else
            echo "Port $PORT: STOPPED"
        fi
    else
        echo "Port $PORT: NOT STARTED"
    fi
done

echo ""
echo "========================================"
echo "To stop cluster: ./stop-cluster.sh"
echo "To check logs: tail -f logs/deepface-*.log"
echo "========================================"
