# Load Testing & Bandwidth Testing - Attendance System

Testing bandwidth usage dan kapasitas sistem untuk menangani multiple concurrent users melakukan absensi.

## Setup

1. **Install Dependencies:**
```bash
cd load-testing
pip install -r requirements.txt
```

## Testing Methods

### Method 1: Bandwidth Test Script (Recommended untuk analisis detail)

Script Python sederhana yang mengukur bandwidth secara langsung:

```bash
# Test dengan 10 user selama 60 detik
python bandwidth_test.py --users 10 --duration 60

# Test dengan 100 user selama 30 detik
python bandwidth_test.py --users 100 --duration 30

# Custom configuration
python bandwidth_test.py --users 50 --duration 120 --host http://127.0.0.1:8000
```

**Output:**
- Total requests (berhasil/gagal)
- Bandwidth usage (sent/received)
- Response time statistics (avg, min, max, percentiles)
- **Estimasi untuk 100 concurrent users**
- Server throughput capacity

### Method 2: Locust Load Testing (Recommended untuk web UI dan monitoring)

Locust menyediakan web interface untuk monitoring real-time:

```bash
# Start Locust web UI
locust -f locustfile.py --host=http://127.0.0.1:8000

# Open browser ke: http://localhost:8089
# Set: Number of users, Spawn rate, kemudian klik Start
```

**Atau headless mode:**
```bash
# 100 users, spawn 10 per detik, run selama 5 menit
locust -f locustfile.py \
    --host=http://127.0.0.1:8000 \
    --users 100 \
    --spawn-rate 10 \
    --run-time 5m \
    --headless \
    --html report.html
```

## Metrics yang Diukur

### 1. Bandwidth Usage
- **Data Sent:** Image upload (JPEG, ~50-150 KB per image)
- **Data Received:** API response (JSON)
- **Per Request:** Average KB per check-in/check-out
- **Total:** Aggregate untuk semua requests

### 2. Response Time
- **Average:** Mean response time
- **P50 (Median):** 50% requests lebih cepat dari ini
- **P95:** 95% requests lebih cepat dari ini
- **P99:** 99% requests lebih cepat dari ini

### 3. Throughput
- **Requests per second:** Berapa banyak check-in yang bisa diproses per detik
- **Concurrent capacity:** Max concurrent users yang bisa ditangani

### 4. Error Rate
- **Success rate:** Percentage successful requests
- **Failed requests:** Dengan detail error messages

## Expected Results

### Single Check-In (dengan Face Recognition)
```
Request Size:
  - Image: ~80-120 KB (JPEG, 640x480, quality 85)
  - Metadata: ~0.5 KB (lat, long, location_id)
  - Total Sent: ~80-120 KB

Response Size:
  - API Response: ~2-5 KB (JSON)

Total per Check-in: ~85-125 KB
```

### 100 Concurrent Check-ins

**Scenario 1: All in 1 second (worst case)**
```
Total Data: 100 × 100 KB = 10 MB
Bandwidth Required: 80 Mbps (peak)
```

**Scenario 2: Spread over 10 seconds (realistic)**
```
Total Data: 10 MB
Bandwidth Required: 8 Mbps
```

**Scenario 3: Spread over 30 seconds (normal)**
```
Total Data: 10 MB
Bandwidth Required: 2.67 Mbps
```

## Interpretasi Hasil

### Bandwidth Requirements

**Untuk 100 karyawan:**
- **Best Case (30s spread):** 3 Mbps
- **Normal Case (10s spread):** 8 Mbps
- **Worst Case (1s peak):** 80 Mbps

**Rekomendasi:**
- **Minimum Internet:** 10 Mbps (untuk 100 users dalam 10 detik)
- **Recommended:** 20-50 Mbps (untuk buffer dan concurrent operations)
- **Optimal:** 100 Mbps (untuk instant response dan multiple locations)

### Server Capacity

**Single Backend Server (Laravel + Python DeepFace):**

Dengan server specs normal (4 CPU, 8GB RAM):
- **Sequential Processing:** ~2-3 requests/second
- **100 users:** ~30-50 seconds total
- **Bottleneck:** Python DeepFace processing time

**Scaling Options:**

1. **Horizontal Scaling (Multiple DeepFace Instances):**
   - Run 3-5 Python services on different ports
   - Laravel load balances between them
   - Capacity: 10-15 requests/second
   - 100 users: ~7-10 seconds

2. **Queue-Based Processing:**
   - Async job queue (Laravel Queue + Redis)
   - Immediate response, process in background
   - Better user experience
   - Capacity: Handle peaks smoothly

3. **GPU Acceleration:**
   - DeepFace with GPU support
   - 10x faster processing
   - 20-30 requests/second
   - 100 users: ~3-5 seconds

## Test Scenarios

### Test 1: Normal Load (10 users)
```bash
python bandwidth_test.py --users 10 --duration 60
```
Expected: All success, <2s response time

### Test 2: Peak Load (50 users)
```bash
python bandwidth_test.py --users 50 --duration 60
```
Expected: Slower responses, some queuing

### Test 3: Stress Test (100 users)
```bash
python bandwidth_test.py --users 100 --duration 30
```
Expected: High response times, possible failures

### Test 4: Extended Load (Continuous)
```bash
locust -f locustfile.py --host=http://127.0.0.1:8000 --users 20 --spawn-rate 5 --run-time 10m --headless
```
Expected: Sustained performance metrics

## Troubleshooting

### Issue: High failure rate

**Solutions:**
- Ensure backend and DeepFace service running
- Check credentials in test scripts
- Verify database has test users
- Increase timeout values

### Issue: Slow response times

**Causes:**
- DeepFace processing bottleneck
- Database queries slow
- No indexes on tables
- Limited CPU/RAM

**Solutions:**
- Add database indexes
- Scale DeepFace horizontally
- Use caching (Redis)
- Optimize queries

### Issue: Memory errors

**Solutions:**
- Reduce image quality in test
- Reduce concurrent users
- Increase server RAM
- Use queue-based processing

## Production Recommendations

Based on test results:

1. **Internet Bandwidth:**
   - Minimum: 20 Mbps upload/download
   - Recommended: 50-100 Mbps
   - Per 100 concurrent users

2. **Server Specs:**
   - CPU: 8+ cores
   - RAM: 16+ GB
   - Storage: SSD
   - GPU: Optional but recommended

3. **Architecture:**
   - Load balancer
   - 2-3 backend instances
   - 3-5 DeepFace instances
   - Redis for caching
   - Queue for async processing

4. **Monitoring:**
   - Response time alerts (>5s)
   - Error rate alerts (>5%)
   - Bandwidth monitoring
   - Server resource monitoring

## Next Steps

1. Run tests dengan script ini
2. Analyze results
3. Identify bottlenecks
4. Optimize (database, caching, scaling)
5. Re-test untuk verify improvements
