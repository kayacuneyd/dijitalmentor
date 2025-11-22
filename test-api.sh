#!/bin/bash
# DijitalMentor API Test Script
# Bu script API'nin çalışıp çalışmadığını test eder

API_BASE="https://api.dijitalmentor.de/server/api"

echo "🧪 DijitalMentor API Test Script"
echo "================================"
echo ""

# Test 1: Health Check - Subjects List
echo "1️⃣  Testing: Subjects List"
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$API_BASE/subjects/list.php")
if [ "$RESPONSE" = "200" ]; then
    echo "   ✅ Subjects endpoint: OK (HTTP $RESPONSE)"
else
    echo "   ❌ Subjects endpoint: FAILED (HTTP $RESPONSE)"
fi
echo ""

# Test 2: CORS Headers
echo "2️⃣  Testing: CORS Headers"
CORS_HEADER=$(curl -s -I -H "Origin: https://dijitalmentor.de" "$API_BASE/subjects/list.php" | grep -i "access-control-allow-origin")
if [ -n "$CORS_HEADER" ]; then
    echo "   ✅ CORS headers: OK"
    echo "   $CORS_HEADER"
else
    echo "   ❌ CORS headers: MISSING"
fi
echo ""

# Test 3: OPTIONS Preflight
echo "3️⃣  Testing: OPTIONS Preflight"
OPTIONS_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -X OPTIONS -H "Origin: https://dijitalmentor.de" -H "Access-Control-Request-Method: GET" "$API_BASE/subjects/list.php")
if [ "$OPTIONS_RESPONSE" = "200" ]; then
    echo "   ✅ OPTIONS preflight: OK (HTTP $OPTIONS_RESPONSE)"
else
    echo "   ❌ OPTIONS preflight: FAILED (HTTP $OPTIONS_RESPONSE)"
fi
echo ""

# Test 4: JSON Response Format
echo "4️⃣  Testing: JSON Response Format"
JSON_RESPONSE=$(curl -s "$API_BASE/subjects/list.php")
if echo "$JSON_RESPONSE" | jq . > /dev/null 2>&1; then
    echo "   ✅ JSON format: Valid"
else
    echo "   ❌ JSON format: Invalid"
    echo "   Response: $JSON_RESPONSE"
fi
echo ""

# Test 5: Teachers List (if available)
echo "5️⃣  Testing: Teachers List"
TEACHERS_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$API_BASE/teachers/list.php")
if [ "$TEACHERS_RESPONSE" = "200" ]; then
    echo "   ✅ Teachers endpoint: OK (HTTP $TEACHERS_RESPONSE)"
else
    echo "   ⚠️  Teachers endpoint: HTTP $TEACHERS_RESPONSE (may require auth)"
fi
echo ""

echo "================================"
echo "✅ Test completed!"
echo ""
echo "📝 Note: Some endpoints may require authentication."
echo "   Use the login endpoint to get a token for protected routes."

