#!/usr/bin/env python3
"""
Simple Bandwidth Test - Measures actual data transfer for attendance check-in
No authentication required - just measures image size and bandwidth
"""

import os
import sys
from PIL import Image, ImageDraw
import io
import time

def create_face_image(width=640, height=480, quality=85):
    """Create a realistic test image similar to camera capture"""
    img = Image.new('RGB', (width, height), color='lightblue')
    draw = ImageDraw.Draw(img)

    # Draw a simple face-like pattern
    draw.ellipse([width//4, height//4, 3*width//4, 3*height//4], fill='peachpuff', outline='black')
    draw.ellipse([width//3, height//3, width//3 + 40, height//3 + 40], fill='black')
    draw.ellipse([2*width//3 - 40, height//3, 2*width//3, height//3 + 40], fill='black')
    draw.arc([width//3, height//2, 2*width//3, 3*height//4], 0, 180, fill='black', width=3)

    # Convert to JPEG bytes
    buffer = io.BytesIO()
    img.save(buffer, format='JPEG', quality=quality)
    return buffer.getvalue()

def format_bytes(bytes_value):
    """Format bytes to human readable"""
    for unit in ['B', 'KB', 'MB', 'GB']:
        if bytes_value < 1024.0:
            return f"{bytes_value:.2f} {unit}"
        bytes_value /= 1024.0
    return f"{bytes_value:.2f} TB"

def calculate_bandwidth(num_users, time_seconds):
    """Calculate bandwidth requirements for N concurrent users"""

    print(f"\n{'='*70}")
    print(f"BANDWIDTH ANALYSIS - ATTENDANCE CHECK-IN")
    print(f"{'='*70}\n")

    # Test different image qualities
    qualities = [70, 85, 95]
    resolutions = [
        (320, 240, "Low (320x240)"),
        (640, 480, "Medium (640x480)"),
        (1280, 720, "High (1280x720)")
    ]

    print("1. IMAGE SIZE ANALYSIS")
    print(f"{'='*70}\n")

    results = []

    for width, height, label in resolutions:
        print(f"{label}:")
        for quality in qualities:
            image_bytes = create_face_image(width, height, quality)
            image_size = len(image_bytes)

            # Estimate total request size (image + metadata + headers)
            metadata_size = 500  # JSON metadata (lat, long, location_id, etc.)
            headers_size = 300   # HTTP headers
            total_sent = image_size + metadata_size + headers_size

            # Estimate response size
            response_json = 2000  # Typical response JSON
            response_headers = 300  # Response headers
            total_received = response_json + response_headers

            # Total per request
            total_per_request = total_sent + total_received

            results.append({
                'resolution': label,
                'quality': quality,
                'image_size': image_size,
                'total_sent': total_sent,
                'total_received': total_received,
                'total_per_request': total_per_request
            })

            print(f"  Quality {quality}: {format_bytes(image_size)} (Total with overhead: {format_bytes(total_per_request)})")

        print()

    # Use medium resolution, quality 85 as baseline
    baseline = next(r for r in results if r['resolution'] == 'Medium (640x480)' and r['quality'] == 85)

    print(f"\n2. BANDWIDTH CALCULATION FOR {num_users} CONCURRENT USERS")
    print(f"{'='*70}\n")
    print(f"Using: Medium (640x480), Quality 85")
    print(f"Per Request: {format_bytes(baseline['total_per_request'])}")
    print()

    # Different time scenarios
    scenarios = [
        (1, "Peak (all dalam 1 detik)"),
        (5, "High Load (dalam 5 detik)"),
        (10, "Normal (dalam 10 detik)"),
        (30, "Distributed (dalam 30 detik)"),
        (60, "Spread Out (dalam 1 menit)")
    ]

    print("Skenario Bandwidth:")
    print()

    for time_span, description in scenarios:
        total_data = baseline['total_per_request'] * num_users
        bandwidth_bps = (total_data * 8) / time_span  # bits per second
        bandwidth_mbps = bandwidth_bps / (1024 * 1024)

        print(f"{description}:")
        print(f"  Total Data: {format_bytes(total_data)}")
        print(f"  Bandwidth Required: {bandwidth_mbps:.2f} Mbps")
        print()

    print(f"\n3. STORAGE REQUIREMENTS")
    print(f"{'='*70}\n")

    # Database storage (embeddings + metadata)
    embedding_size = 512 * 4  # 512 floats * 4 bytes each
    metadata_json = 500  # Additional metadata
    db_per_user = embedding_size + metadata_json

    # Image storage (if storing original images)
    image_storage_per_user = baseline['image_size']

    print(f"Per User Face Data:")
    print(f"  Embedding (512-d): {format_bytes(embedding_size)}")
    print(f"  Metadata: {format_bytes(metadata_json)}")
    print(f"  Total in DB: {format_bytes(db_per_user)}")
    print()

    print(f"If storing images:")
    print(f"  Per Image: {format_bytes(image_storage_per_user)}")
    print(f"  100 employees: {format_bytes(image_storage_per_user * 100)}")
    print(f"  1000 employees: {format_bytes(image_storage_per_user * 1000)}")
    print()

    print(f"\n4. DAILY BANDWIDTH ESTIMATE")
    print(f"{'='*70}\n")

    # Assume employees check-in and check-out once per day
    requests_per_day = num_users * 2  # check-in + check-out
    daily_bandwidth = baseline['total_per_request'] * requests_per_day

    print(f"{num_users} employees, 2 requests each (check-in + check-out):")
    print(f"  Total Daily Requests: {requests_per_day}")
    print(f"  Total Daily Data: {format_bytes(daily_bandwidth)}")
    print()

    # If spread over 2 hours (typical check-in/out period)
    spread_hours = 2
    spread_seconds = spread_hours * 3600
    avg_bandwidth_bps = (daily_bandwidth * 8) / spread_seconds
    avg_bandwidth_mbps = avg_bandwidth_bps / (1024 * 1024)

    print(f"If spread over {spread_hours} hours (typical):")
    print(f"  Average Bandwidth: {avg_bandwidth_mbps:.2f} Mbps")
    print()

    print(f"\n5. RECOMMENDATIONS")
    print(f"{'='*70}\n")

    # Calculate recommended bandwidth
    peak_10s = (baseline['total_per_request'] * num_users * 8) / 10 / (1024 * 1024)
    recommended = peak_10s * 1.5  # 50% buffer

    print(f"For {num_users} concurrent users:")
    print()
    print(f"Minimum Internet Bandwidth:")
    print(f"  Peak (10s): {peak_10s:.2f} Mbps")
    print(f"  Recommended (with 50% buffer): {recommended:.2f} Mbps")
    print()

    if recommended < 10:
        tier = "10 Mbps"
        note = "Standard office internet sudah cukup"
    elif recommended < 50:
        tier = "50 Mbps"
        note = "Business-grade internet diperlukan"
    elif recommended < 100:
        tier = "100 Mbps"
        note = "High-speed business internet diperlukan"
    else:
        tier = "100+ Mbps atau Dedicated Line"
        note = "Enterprise-grade internet dengan multiple lines"

    print(f"Recommended Tier: {tier}")
    print(f"Note: {note}")
    print()

    print("Server Recommendations:")
    print(f"  - CPU: 4+ cores (8+ untuk {num_users}+ users)")
    print(f"  - RAM: 8GB minimum (16GB recommended)")
    print(f"  - Network: Gigabit ethernet (1000 Mbps)")
    print(f"  - DeepFace Instances: {max(1, num_users // 30)} (30 users per instance)")
    print()

    print("Optimization Tips:")
    print("  1. Use image quality 70-80 (vs 85) to reduce size by ~30%")
    print("  2. Resize images to 320x240 for enrollment (not verification)")
    print("  3. Implement client-side image compression")
    print("  4. Use Redis caching for embeddings lookup")
    print("  5. Deploy multiple DeepFace instances behind load balancer")
    print()

    print(f"\n6. COMPARISON WITH OTHER SYSTEMS")
    print(f"{'='*70}\n")

    systems = [
        ("Our System (DeepFace)", baseline['total_per_request']),
        ("QR Code Check-in", 5 * 1024),  # ~5 KB
        ("PIN/Password Check-in", 2 * 1024),  # ~2 KB
        ("RFID Card", 3 * 1024),  # ~3 KB
        ("GPS Only", 10 * 1024),  # ~10 KB
    ]

    for system_name, size_bytes in systems:
        bandwidth_for_100 = (size_bytes * 100 * 8) / 10 / (1024 * 1024)  # 100 users in 10s
        print(f"{system_name:30} {format_bytes(size_bytes):15} → {bandwidth_for_100:.2f} Mbps for 100 users")

    print()
    print("Note: Face recognition provides highest security but requires")
    print("      more bandwidth than simple methods.")

    print(f"\n{'='*70}\n")

if __name__ == '__main__':
    import argparse

    parser = argparse.ArgumentParser(description='Bandwidth Analysis for Attendance System')
    parser.add_argument('--users', type=int, default=100, help='Number of concurrent users (default: 100)')
    parser.add_argument('--time', type=int, default=10, help='Time span in seconds (default: 10)')

    args = parser.parse_args()

    calculate_bandwidth(args.users, args.time)
