"""
Face Recognition Service
========================
Python Flask service for server-side face recognition processing.

This service receives images, extracts face descriptors, and verifies against database.
"""

import os
import base64
import io
import logging
from datetime import datetime
from typing import Dict, List, Optional, Tuple

import cv2
import face_recognition
import numpy as np
from flask import Flask, jsonify, request
from flask_cors import CORS
from PIL import Image
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

# Initialize Flask app
app = Flask(__name__)
CORS(app)

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Configuration
FACE_RECOGNITION_TOLERANCE = float(os.getenv('FACE_RECOGNITION_TOLERANCE', '0.6'))
FACE_RECOGNITION_MODEL = os.getenv('FACE_RECOGNITION_MODEL', 'hog')  # 'hog' or 'cnn'
MAX_IMAGE_SIZE = int(os.getenv('MAX_IMAGE_SIZE', '1024'))  # Max width/height


def decode_image(image_data: str) -> np.ndarray:
    """
    Decode base64 image to numpy array.

    Args:
        image_data: Base64 encoded image string

    Returns:
        Numpy array of image in RGB format
    """
    try:
        # Remove data URL prefix if present
        if 'base64,' in image_data:
            image_data = image_data.split('base64,')[1]

        # Decode base64
        image_bytes = base64.b64decode(image_data)

        # Convert to PIL Image
        image = Image.open(io.BytesIO(image_bytes))

        # Convert to RGB if needed
        if image.mode != 'RGB':
            image = image.convert('RGB')

        # Convert to numpy array
        image_array = np.array(image)

        return image_array

    except Exception as e:
        logger.error(f"Failed to decode image: {str(e)}")
        raise ValueError(f"Invalid image data: {str(e)}")


def resize_image(image: np.ndarray, max_size: int = MAX_IMAGE_SIZE) -> np.ndarray:
    """
    Resize image to maximum size while maintaining aspect ratio.

    Args:
        image: Input image as numpy array
        max_size: Maximum width or height

    Returns:
        Resized image
    """
    height, width = image.shape[:2]

    if height <= max_size and width <= max_size:
        return image

    # Calculate scaling factor
    if height > width:
        scale = max_size / height
    else:
        scale = max_size / width

    new_width = int(width * scale)
    new_height = int(height * scale)

    resized = cv2.resize(image, (new_width, new_height), interpolation=cv2.INTER_AREA)

    return resized


def extract_face_encoding(image: np.ndarray) -> Tuple[Optional[List[float]], float]:
    """
    Extract face encoding (descriptor) from image.

    Args:
        image: Input image as numpy array

    Returns:
        Tuple of (face_encoding, confidence)
    """
    try:
        # Resize image for faster processing
        image = resize_image(image)

        # Detect face locations
        face_locations = face_recognition.face_locations(
            image,
            model=FACE_RECOGNITION_MODEL
        )

        if not face_locations:
            logger.warning("No face detected in image")
            return None, 0.0

        if len(face_locations) > 1:
            logger.warning(f"Multiple faces detected ({len(face_locations)}), using first one")

        # Extract face encoding (128-d descriptor)
        face_encodings = face_recognition.face_encodings(
            image,
            face_locations,
            model='large'  # Use large model for better accuracy
        )

        if not face_encodings:
            logger.warning("Failed to extract face encoding")
            return None, 0.0

        # Get first face encoding
        face_encoding = face_encodings[0]

        # Calculate confidence (quality metric)
        # Based on face size and detection score
        face_location = face_locations[0]
        top, right, bottom, left = face_location
        face_width = right - left
        face_height = bottom - top
        face_area = face_width * face_height
        image_area = image.shape[0] * image.shape[1]

        # Confidence based on face size (larger = better)
        face_ratio = face_area / image_area
        confidence = min(1.0, face_ratio * 10)  # Scale to 0-1

        logger.info(f"Face detected with confidence: {confidence:.2f}")

        return face_encoding.tolist(), confidence

    except Exception as e:
        logger.error(f"Failed to extract face encoding: {str(e)}")
        raise


def compare_faces(
    known_encoding: List[float],
    test_encoding: List[float],
    tolerance: float = FACE_RECOGNITION_TOLERANCE
) -> Tuple[bool, float]:
    """
    Compare two face encodings.

    Args:
        known_encoding: Known face encoding from database
        test_encoding: Test face encoding from uploaded image
        tolerance: Maximum distance for match (default: 0.6)

    Returns:
        Tuple of (is_match, distance)
    """
    try:
        # Convert to numpy arrays
        known = np.array(known_encoding)
        test = np.array(test_encoding)

        # Calculate Euclidean distance
        distance = np.linalg.norm(known - test)

        # Check if match
        is_match = distance <= tolerance

        # Calculate similarity percentage (inverse of distance)
        similarity = max(0.0, 1.0 - (distance / 1.0))

        logger.info(f"Face comparison: distance={distance:.4f}, match={is_match}, similarity={similarity:.2%}")

        return is_match, float(distance)

    except Exception as e:
        logger.error(f"Failed to compare faces: {str(e)}")
        raise


@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint."""
    return jsonify({
        'status': 'ok',
        'service': 'face-recognition',
        'version': '1.0.0',
        'timestamp': datetime.utcnow().isoformat()
    })


@app.route('/extract-encoding', methods=['POST'])
def extract_encoding():
    """
    Extract face encoding from uploaded image.

    Request JSON:
    {
        "image": "base64_encoded_image"
    }

    Response JSON:
    {
        "success": true,
        "encoding": [128 float values],
        "confidence": 0.85
    }
    """
    try:
        # Get request data
        data = request.get_json()

        if not data or 'image' not in data:
            return jsonify({
                'success': False,
                'message': 'Missing image data'
            }), 400

        # Decode image
        image = decode_image(data['image'])

        # Extract face encoding
        encoding, confidence = extract_face_encoding(image)

        if encoding is None:
            return jsonify({
                'success': False,
                'message': 'No face detected in image'
            }), 422

        return jsonify({
            'success': True,
            'encoding': encoding,
            'confidence': confidence,
            'message': 'Face encoding extracted successfully'
        })

    except ValueError as e:
        logger.error(f"Invalid request: {str(e)}")
        return jsonify({
            'success': False,
            'message': str(e)
        }), 400

    except Exception as e:
        logger.error(f"Unexpected error: {str(e)}")
        return jsonify({
            'success': False,
            'message': 'Internal server error'
        }), 500


@app.route('/verify-face', methods=['POST'])
def verify_face():
    """
    Verify uploaded face against known encodings.

    Request JSON:
    {
        "image": "base64_encoded_image",
        "known_encodings": [
            {
                "employee_id": 1,
                "name": "John Doe",
                "encoding": [128 float values]
            }
        ],
        "tolerance": 0.6  # Optional
    }

    Response JSON:
    {
        "success": true,
        "matched": true,
        "employee": {
            "employee_id": 1,
            "name": "John Doe"
        },
        "distance": 0.42,
        "confidence": 0.85
    }
    """
    try:
        # Get request data
        data = request.get_json()

        if not data or 'image' not in data:
            return jsonify({
                'success': False,
                'message': 'Missing image data'
            }), 400

        if 'known_encodings' not in data or not data['known_encodings']:
            return jsonify({
                'success': False,
                'message': 'Missing known encodings'
            }), 400

        # Get tolerance
        tolerance = data.get('tolerance', FACE_RECOGNITION_TOLERANCE)

        # Decode and process image
        image = decode_image(data['image'])
        test_encoding, confidence = extract_face_encoding(image)

        if test_encoding is None:
            return jsonify({
                'success': False,
                'message': 'No face detected in image',
                'matched': False
            }), 422

        # Compare with known encodings
        best_match = None
        best_distance = float('inf')

        for known in data['known_encodings']:
            is_match, distance = compare_faces(
                known['encoding'],
                test_encoding,
                tolerance
            )

            if is_match and distance < best_distance:
                best_distance = distance
                best_match = known

        # Return result
        if best_match:
            similarity = max(0.0, 1.0 - (best_distance / 1.0))

            return jsonify({
                'success': True,
                'matched': True,
                'employee': {
                    'employee_id': best_match.get('employee_id'),
                    'name': best_match.get('name'),
                    'employee_code': best_match.get('employee_code')
                },
                'distance': best_distance,
                'similarity': similarity,
                'confidence': confidence,
                'message': 'Face matched successfully'
            })
        else:
            return jsonify({
                'success': True,
                'matched': False,
                'confidence': confidence,
                'message': 'No matching face found'
            })

    except ValueError as e:
        logger.error(f"Invalid request: {str(e)}")
        return jsonify({
            'success': False,
            'message': str(e)
        }), 400

    except Exception as e:
        logger.error(f"Unexpected error: {str(e)}")
        return jsonify({
            'success': False,
            'message': 'Internal server error'
        }), 500


@app.errorhandler(404)
def not_found(error):
    """Handle 404 errors."""
    return jsonify({
        'success': False,
        'message': 'Endpoint not found'
    }), 404


@app.errorhandler(500)
def internal_error(error):
    """Handle 500 errors."""
    return jsonify({
        'success': False,
        'message': 'Internal server error'
    }), 500


if __name__ == '__main__':
    # Development server
    port = int(os.getenv('PORT', '5000'))
    debug = os.getenv('DEBUG', 'False').lower() == 'true'

    logger.info(f"Starting Face Recognition Service on port {port}")
    logger.info(f"Model: {FACE_RECOGNITION_MODEL}")
    logger.info(f"Tolerance: {FACE_RECOGNITION_TOLERANCE}")

    app.run(
        host='0.0.0.0',
        port=port,
        debug=debug
    )
