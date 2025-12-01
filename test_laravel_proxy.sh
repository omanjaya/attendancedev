#!/bin/bash

echo "============================================================"
echo "🧪 Testing Laravel Face Recognition Proxy Endpoints"
echo "============================================================"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

LARAVEL_URL="http://localhost:8000"

# Create a simple test image (1x1 pixel base64)
TEST_IMAGE="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=="

echo "Testing Laravel API..."
echo "URL: $LARAVEL_URL"
echo ""

# Test 1: Check if endpoints exist (should return 401 or 403 for auth)
echo "============================================================"
echo "TEST 1: Extract Encoding Server Endpoint"
echo "============================================================"
echo "POST /api/face-recognition/extract-encoding-server"
echo ""

RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -X POST "$LARAVEL_URL/api/face-recognition/extract-encoding-server" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"image\": \"$TEST_IMAGE\"}")

HTTP_STATUS=$(echo "$RESPONSE" | grep "HTTP_STATUS" | cut -d: -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_STATUS/d')

echo "HTTP Status: $HTTP_STATUS"
echo "Response:"
echo "$BODY" | python3 -m json.tool 2>/dev/null || echo "$BODY"
echo ""

if [ "$HTTP_STATUS" = "401" ] || [ "$HTTP_STATUS" = "403" ]; then
    echo -e "${YELLOW}✓ Endpoint exists (needs authentication)${NC}"
elif [ "$HTTP_STATUS" = "404" ]; then
    echo -e "${RED}✗ Endpoint not found (routing issue)${NC}"
elif [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "422" ]; then
    echo -e "${GREEN}✓ Endpoint accessible${NC}"
else
    echo -e "${YELLOW}? Unexpected status: $HTTP_STATUS${NC}"
fi

echo ""
echo "============================================================"
echo "TEST 2: Verify Face Server Endpoint"
echo "============================================================"
echo "POST /api/face-recognition/verify-server"
echo ""

RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -X POST "$LARAVEL_URL/api/face-recognition/verify-server" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"image\": \"$TEST_IMAGE\", \"tolerance\": 0.6}")

HTTP_STATUS=$(echo "$RESPONSE" | grep "HTTP_STATUS" | cut -d: -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_STATUS/d')

echo "HTTP Status: $HTTP_STATUS"
echo "Response:"
echo "$BODY" | python3 -m json.tool 2>/dev/null || echo "$BODY"
echo ""

if [ "$HTTP_STATUS" = "401" ] || [ "$HTTP_STATUS" = "403" ]; then
    echo -e "${YELLOW}✓ Endpoint exists (needs authentication)${NC}"
elif [ "$HTTP_STATUS" = "404" ]; then
    echo -e "${RED}✗ Endpoint not found (routing issue)${NC}"
elif [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "422" ]; then
    echo -e "${GREEN}✓ Endpoint accessible${NC}"
else
    echo -e "${YELLOW}? Unexpected status: $HTTP_STATUS${NC}"
fi

echo ""
echo "============================================================"
echo "📝 Summary"
echo "============================================================"
echo ""
echo "Expected results:"
echo "- 401/403: Endpoints exist but need authentication ✓"
echo "- 404: Endpoints not found (check routes) ✗"
echo "- 200/422: Endpoints accessible and working ✓✓"
echo ""
echo "If you see 401/403, the endpoints are configured correctly!"
echo "Authentication will be handled by the frontend automatically."
echo ""
