"""
Load Testing Script for Attendance System
Tests bandwidth usage and concurrent user capacity for check-in/check-out

Usage:
    locust -f locustfile.py --host=http://127.0.0.1:8000

    Then open http://localhost:8089 for web UI
    Or run headless: locust -f locustfile.py --host=http://127.0.0.1:8000 --users 100 --spawn-rate 10 --run-time 5m --headless
"""

from locust import HttpUser, task, between, events
from PIL import Image
import io
import base64
import json
import random
import os
from datetime import datetime

# Global counters for bandwidth tracking
total_bytes_sent = 0
total_bytes_received = 0
request_count = 0

@events.request.add_listener
def on_request(request_type, name, response_time, response_length, exception, **kwargs):
    """Track bandwidth usage for each request"""
    global total_bytes_sent, total_bytes_received, request_count

    if exception is None:
        # Estimate request size (headers + body)
        # This is approximate as we don't have exact request size
        total_bytes_received += response_length
        request_count += 1

def create_test_face_image(width=640, height=480):
    """
    Generate a test face image (simple pattern, not real face)
    In production, you'd use actual face images
    """
    # Create a simple gradient image to simulate face capture
    img = Image.new('RGB', (width, height), color='white')
    pixels = img.load()

    # Create a simple pattern
    for i in range(width):
        for j in range(height):
            pixels[i, j] = (
                int(255 * i / width),
                int(255 * j / height),
                128
            )

    # Convert to bytes
    buffer = io.BytesIO()
    img.save(buffer, format='JPEG', quality=85)
    buffer.seek(0)
    return buffer.getvalue()

def image_to_base64(image_bytes):
    """Convert image bytes to base64 string"""
    return base64.b64encode(image_bytes).decode('utf-8')

class AttendanceUser(HttpUser):
    """
    Simulates a user performing attendance check-in/check-out
    """
    wait_time = between(1, 3)  # Wait 1-3 seconds between tasks

    def on_start(self):
        """
        Called when a user starts - performs login
        """
        # Test credentials - adjust based on your seeded users
        # We'll use different users to simulate real scenario
        self.user_id = random.randint(1, 100)
        self.email = f"user{self.user_id}@example.com"
        self.password = "password123"  # Adjust to match your test data

        # Login
        response = self.client.post("/api/v1/auth/login", json={
            "email": self.email,
            "password": self.password
        })

        if response.status_code == 200:
            data = response.json()
            self.token = data.get('data', {}).get('token')
            self.headers = {
                "Authorization": f"Bearer {self.token}",
                "Accept": "application/json"
            }
            self.user_info = data.get('data', {}).get('user', {})
        else:
            # If login fails, use a default test token
            # In production testing, ensure users exist
            self.token = None
            self.headers = {"Accept": "application/json"}

    @task(3)
    def check_in_with_face(self):
        """
        Simulate check-in with face recognition
        This is the most bandwidth-intensive operation
        """
        if not self.token:
            return

        # Generate test face image
        face_image = create_test_face_image()

        # Create multipart form data
        files = {
            'image': ('face.jpg', face_image, 'image/jpeg')
        }

        data = {
            'latitude': '-6.2088',  # Jakarta coordinates
            'longitude': '106.8456',
            'location_id': '1'  # Adjust based on your test data
        }

        # Track request size
        global total_bytes_sent
        total_bytes_sent += len(face_image) + 200  # Image + metadata

        with self.client.post(
            "/api/v1/attendance/check-in",
            files=files,
            data=data,
            headers={"Authorization": f"Bearer {self.token}"},
            catch_response=True
        ) as response:
            if response.status_code == 200:
                response.success()
            elif response.status_code == 400:
                # Expected errors (e.g., already checked in, not working day)
                response.success()
            else:
                response.failure(f"Unexpected status: {response.status_code}")

    @task(2)
    def check_out_with_face(self):
        """
        Simulate check-out with face recognition
        """
        if not self.token:
            return

        # Generate test face image
        face_image = create_test_face_image()

        # Create multipart form data
        files = {
            'image': ('face.jpg', face_image, 'image/jpeg')
        }

        data = {
            'latitude': '-6.2088',
            'longitude': '106.8456',
            'location_id': '1'
        }

        # Track request size
        global total_bytes_sent
        total_bytes_sent += len(face_image) + 200

        with self.client.post(
            "/api/v1/attendance/check-out",
            files=files,
            data=data,
            headers={"Authorization": f"Bearer {self.token}"},
            catch_response=True
        ) as response:
            if response.status_code == 200:
                response.success()
            elif response.status_code == 400:
                # Expected errors
                response.success()
            else:
                response.failure(f"Unexpected status: {response.status_code}")

    @task(1)
    def get_my_schedule(self):
        """
        Light operation - get user's schedule
        """
        if not self.token:
            return

        current_date = datetime.now()
        self.client.get(
            f"/api/v1/monthly-schedules/my-schedule?month={current_date.month}&year={current_date.year}",
            headers=self.headers
        )

    @task(1)
    def get_dashboard(self):
        """
        Light operation - get dashboard data
        """
        if not self.token:
            return

        self.client.get(
            "/api/v1/dashboard",
            headers=self.headers
        )

@events.test_stop.add_listener
def on_test_stop(environment, **kwargs):
    """
    Print bandwidth statistics when test completes
    """
    print("\n" + "="*60)
    print("BANDWIDTH USAGE REPORT")
    print("="*60)
    print(f"Total Requests: {request_count}")
    print(f"Total Data Sent: {total_bytes_sent / (1024*1024):.2f} MB")
    print(f"Total Data Received: {total_bytes_received / (1024*1024):.2f} MB")
    print(f"Total Bandwidth: {(total_bytes_sent + total_bytes_received) / (1024*1024):.2f} MB")

    if request_count > 0:
        print(f"\nAverage per Request:")
        print(f"  Sent: {total_bytes_sent / request_count / 1024:.2f} KB")
        print(f"  Received: {total_bytes_received / request_count / 1024:.2f} KB")
        print(f"  Total: {(total_bytes_sent + total_bytes_received) / request_count / 1024:.2f} KB")

    # Estimate for 100 concurrent users
    if request_count > 0:
        avg_per_checkin = (total_bytes_sent + total_bytes_received) / request_count
        total_for_100 = (avg_per_checkin * 100) / (1024*1024)
        print(f"\nEstimate for 100 Concurrent Check-ins:")
        print(f"  Bandwidth Required: {total_for_100:.2f} MB")
        print(f"  Peak Bandwidth (if all in 1 sec): {total_for_100 * 8:.2f} Mbps")

    print("="*60)
