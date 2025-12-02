#!/usr/bin/env python3
"""
Bandwidth Measurement Script for Attendance System
Direct measurement of bandwidth usage for check-in/check-out operations

Usage:
    python bandwidth_test.py --users 10 --duration 60
"""

import requests
import time
import argparse
import sys
import os
from PIL import Image
import io
import json
from datetime import datetime
from concurrent.futures import ThreadPoolExecutor, as_completed
from threading import Lock

# Add parent directory to path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

# Global statistics with thread safety
stats_lock = Lock()
stats = {
    'total_requests': 0,
    'successful_requests': 0,
    'failed_requests': 0,
    'total_bytes_sent': 0,
    'total_bytes_received': 0,
    'response_times': [],
    'errors': []
}

def create_test_image(width=640, height=480, quality=85):
    """Create a test image similar to camera capture"""
    img = Image.new('RGB', (width, height), color='lightblue')

    # Add some pattern to simulate a face capture
    from PIL import ImageDraw
    draw = ImageDraw.Draw(img)

    # Draw a simple face-like pattern
    # Face outline
    draw.ellipse([width//4, height//4, 3*width//4, 3*height//4], fill='peachpuff', outline='black')
    # Eyes
    draw.ellipse([width//3, height//3, width//3 + 40, height//3 + 40], fill='black')
    draw.ellipse([2*width//3 - 40, height//3, 2*width//3, height//3 + 40], fill='black')
    # Mouth
    draw.arc([width//3, height//2, 2*width//3, 3*height//4], 0, 180, fill='black', width=3)

    # Convert to JPEG bytes
    buffer = io.BytesIO()
    img.save(buffer, format='JPEG', quality=quality)
    buffer.seek(0)
    return buffer.getvalue()

def get_auth_token(base_url, email, password):
    """Login and get authentication token"""
    try:
        response = requests.post(
            f"{base_url}/api/v1/auth/login",
            json={
                "email": email,
                "password": password
            },
            timeout=10
        )

        if response.status_code == 200:
            data = response.json()
            return data.get('data', {}).get('token')
        else:
            print(f"Login failed for {email}: {response.status_code}")
            return None
    except Exception as e:
        print(f"Login error for {email}: {str(e)}")
        return None

def perform_check_in(base_url, token, user_id):
    """Perform a single check-in with face recognition"""
    start_time = time.time()

    # Create test face image
    face_image = create_test_image()
    image_size = len(face_image)

    # Prepare request
    files = {
        'image': ('face.jpg', face_image, 'image/jpeg')
    }

    data = {
        'latitude': '-6.2088',
        'longitude': '106.8456',
        'location_id': '1'
    }

    headers = {
        'Authorization': f'Bearer {token}'
    }

    try:
        response = requests.post(
            f"{base_url}/api/v1/attendance/check-in",
            files=files,
            data=data,
            headers=headers,
            timeout=30
        )

        response_time = time.time() - start_time
        response_size = len(response.content)

        # Update statistics
        with stats_lock:
            stats['total_requests'] += 1
            stats['total_bytes_sent'] += image_size + 200  # Image + metadata
            stats['total_bytes_received'] += response_size
            stats['response_times'].append(response_time)

            if response.status_code in [200, 400]:  # 400 might be expected (already checked in, etc.)
                stats['successful_requests'] += 1
            else:
                stats['failed_requests'] += 1
                stats['errors'].append({
                    'user_id': user_id,
                    'status': response.status_code,
                    'message': response.text[:100]
                })

        return {
            'user_id': user_id,
            'success': response.status_code in [200, 400],
            'status_code': response.status_code,
            'response_time': response_time,
            'bytes_sent': image_size + 200,
            'bytes_received': response_size
        }

    except Exception as e:
        with stats_lock:
            stats['total_requests'] += 1
            stats['failed_requests'] += 1
            stats['errors'].append({
                'user_id': user_id,
                'error': str(e)
            })

        return {
            'user_id': user_id,
            'success': False,
            'error': str(e)
        }

def run_load_test(base_url, num_users, duration_seconds):
    """Run load test with specified number of users"""
    print(f"\n{'='*60}")
    print(f"Starting Load Test")
    print(f"{'='*60}")
    print(f"Target: {base_url}")
    print(f"Concurrent Users: {num_users}")
    print(f"Duration: {duration_seconds} seconds")
    print(f"{'='*60}\n")

    # Create test users and get tokens
    print("Authenticating users...")
    user_tokens = []

    # For testing, we'll use a single test user repeatedly
    # In production, you'd have multiple real test accounts
    test_email = "mita@gmail.com"  # Using existing test user
    test_password = "password"

    token = get_auth_token(base_url, test_email, test_password)

    if not token:
        print("ERROR: Could not authenticate. Please check credentials.")
        return

    print(f"✓ Authentication successful\n")

    # Run test
    start_time = time.time()
    end_time = start_time + duration_seconds

    print("Running test...")
    print("(Press Ctrl+C to stop early)\n")

    with ThreadPoolExecutor(max_workers=num_users) as executor:
        futures = []
        user_counter = 0

        try:
            while time.time() < end_time:
                # Submit check-in tasks
                for _ in range(num_users):
                    future = executor.submit(
                        perform_check_in,
                        base_url,
                        token,
                        user_counter
                    )
                    futures.append(future)
                    user_counter += 1

                # Wait a bit before next batch
                time.sleep(2)

                # Show progress
                completed = sum(1 for f in futures if f.done())
                print(f"\rCompleted: {completed}/{len(futures)} requests", end='', flush=True)

            # Wait for all to complete
            print("\n\nWaiting for all requests to complete...")
            for future in as_completed(futures):
                try:
                    future.result()
                except Exception as e:
                    print(f"Error: {e}")

        except KeyboardInterrupt:
            print("\n\nTest interrupted by user")

    # Print results
    print_results()

def print_results():
    """Print test results and bandwidth statistics"""
    print(f"\n{'='*60}")
    print("LOAD TEST RESULTS")
    print(f"{'='*60}\n")

    # Request statistics
    print(f"Total Requests: {stats['total_requests']}")
    print(f"Successful: {stats['successful_requests']} ({stats['successful_requests']/max(stats['total_requests'],1)*100:.1f}%)")
    print(f"Failed: {stats['failed_requests']} ({stats['failed_requests']/max(stats['total_requests'],1)*100:.1f}%)")

    # Bandwidth statistics
    total_sent_mb = stats['total_bytes_sent'] / (1024 * 1024)
    total_received_mb = stats['total_bytes_received'] / (1024 * 1024)
    total_bandwidth_mb = (stats['total_bytes_sent'] + stats['total_bytes_received']) / (1024 * 1024)

    print(f"\n{'='*60}")
    print("BANDWIDTH USAGE")
    print(f"{'='*60}")
    print(f"Total Data Sent: {total_sent_mb:.2f} MB")
    print(f"Total Data Received: {total_received_mb:.2f} MB")
    print(f"Total Bandwidth: {total_bandwidth_mb:.2f} MB")

    if stats['total_requests'] > 0:
        avg_sent_kb = stats['total_bytes_sent'] / stats['total_requests'] / 1024
        avg_received_kb = stats['total_bytes_received'] / stats['total_requests'] / 1024
        avg_total_kb = (stats['total_bytes_sent'] + stats['total_bytes_received']) / stats['total_requests'] / 1024

        print(f"\nPer Request Average:")
        print(f"  Sent: {avg_sent_kb:.2f} KB")
        print(f"  Received: {avg_received_kb:.2f} KB")
        print(f"  Total: {avg_total_kb:.2f} KB")

    # Response time statistics
    if stats['response_times']:
        avg_response = sum(stats['response_times']) / len(stats['response_times'])
        min_response = min(stats['response_times'])
        max_response = max(stats['response_times'])

        # Calculate percentiles
        sorted_times = sorted(stats['response_times'])
        p50 = sorted_times[len(sorted_times) // 2]
        p95 = sorted_times[int(len(sorted_times) * 0.95)]
        p99 = sorted_times[int(len(sorted_times) * 0.99)]

        print(f"\n{'='*60}")
        print("RESPONSE TIME")
        print(f"{'='*60}")
        print(f"Average: {avg_response:.2f}s")
        print(f"Min: {min_response:.2f}s")
        print(f"Max: {max_response:.2f}s")
        print(f"P50 (Median): {p50:.2f}s")
        print(f"P95: {p95:.2f}s")
        print(f"P99: {p99:.2f}s")

    # Estimate for 100 concurrent users
    print(f"\n{'='*60}")
    print("ESTIMATE FOR 100 CONCURRENT CHECK-INS")
    print(f"{'='*60}")

    if stats['total_requests'] > 0:
        avg_per_request = (stats['total_bytes_sent'] + stats['total_bytes_received']) / stats['total_requests']

        # Bandwidth for 100 simultaneous check-ins
        bandwidth_100_mb = (avg_per_request * 100) / (1024 * 1024)

        # If all complete in 1 second (worst case)
        bandwidth_mbps_1s = bandwidth_100_mb * 8

        # If spread over 10 seconds (more realistic)
        bandwidth_mbps_10s = bandwidth_mbps_1s / 10

        # If spread over 30 seconds (normal scenario)
        bandwidth_mbps_30s = bandwidth_mbps_1s / 30

        print(f"Total Data Transfer: {bandwidth_100_mb:.2f} MB")
        print(f"\nBandwidth Requirements:")
        print(f"  If all in 1 second:  {bandwidth_mbps_1s:.2f} Mbps (peak)")
        print(f"  If spread over 10s:  {bandwidth_mbps_10s:.2f} Mbps")
        print(f"  If spread over 30s:  {bandwidth_mbps_30s:.2f} Mbps")

        print(f"\nServer Capacity:")
        if stats['response_times']:
            avg_response = sum(stats['response_times']) / len(stats['response_times'])
            throughput = 1 / avg_response if avg_response > 0 else 0
            print(f"  Requests per second: {throughput:.2f}")
            print(f"  Time for 100 users: {100 / throughput:.2f} seconds")

    # Errors
    if stats['errors']:
        print(f"\n{'='*60}")
        print(f"ERRORS ({len(stats['errors'])} total)")
        print(f"{'='*60}")
        for i, error in enumerate(stats['errors'][:10], 1):  # Show first 10
            print(f"{i}. User {error.get('user_id', 'N/A')}: {error}")
        if len(stats['errors']) > 10:
            print(f"... and {len(stats['errors']) - 10} more errors")

    print(f"\n{'='*60}\n")

def main():
    parser = argparse.ArgumentParser(description='Bandwidth and Load Testing for Attendance System')
    parser.add_argument('--users', type=int, default=10, help='Number of concurrent users (default: 10)')
    parser.add_argument('--duration', type=int, default=60, help='Test duration in seconds (default: 60)')
    parser.add_argument('--host', type=str, default='http://127.0.0.1:8000', help='Backend host URL')

    args = parser.parse_args()

    run_load_test(args.host, args.users, args.duration)

if __name__ == '__main__':
    main()
