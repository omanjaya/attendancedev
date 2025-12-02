"""
Test script for Face Recognition Service
Tests basic functionality
"""
import requests
import base64
import json

SERVICE_URL = "http://127.0.0.1:8001"

def test_health():
    """Test health endpoint"""
    print("🔍 Testing health endpoint...")
    response = requests.get(f"{SERVICE_URL}/health")
    print(f"✅ Health check: {response.json()}")

def test_extract_embedding(image_path):
    """Test embedding extraction"""
    print(f"\n🔍 Testing embedding extraction with {image_path}...")

    # Read image as base64
    with open(image_path, "rb") as f:
        image_base64 = base64.b64encode(f.read()).decode()

    # Send request
    response = requests.post(
        f"{SERVICE_URL}/extract-embedding",
        data={"image_base64": image_base64}
    )

    result = response.json()
    if result.get("success"):
        print(f"✅ Embedding extracted: {result['dimension']}-d vector")
        print(f"   Confidence: {result['confidence']:.2f}")
        print(f"   Quality: {'OK' if result['quality']['quality_ok'] else 'POOR'}")
        return result["embedding"]
    else:
        print(f"❌ Failed: {result.get('message')}")
        return None

def test_liveness(image_path):
    """Test liveness detection"""
    print(f"\n🔍 Testing liveness check with {image_path}...")

    with open(image_path, "rb") as f:
        image_base64 = base64.b64encode(f.read()).decode()

    response = requests.post(
        f"{SERVICE_URL}/check-liveness",
        data={"image_base64": image_base64}
    )

    result = response.json()
    if result.get("success"):
        is_live = result["is_live"]
        print(f"{'✅' if is_live else '❌'} Liveness: {result['result']}")
        return is_live
    else:
        print(f"❌ Failed: {result.get('message')}")
        return None

if __name__ == "__main__":
    print("=" * 50)
    print("Face Recognition Service - Test Suite")
    print("=" * 50)

    try:
        # Test 1: Health check
        test_health()

        # Test 2: Extract embedding (you need to provide a test image)
        # test_image = "path/to/test/image.jpg"
        # embedding = test_extract_embedding(test_image)

        # Test 3: Liveness check
        # test_liveness(test_image)

        print("\n✅ All tests passed!")

    except requests.exceptions.ConnectionError:
        print("\n❌ Service not running! Start with: python main.py")
    except Exception as e:
        print(f"\n❌ Test failed: {e}")
