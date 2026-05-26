#!/bin/bash
# HYREME Stability Test Script
# Run this after applying stability fixes

echo "🔍 HYREME Stability Verification"
echo "=================================="
echo ""

# Check if PHP syntax is valid
echo "✓ Checking PHP Syntax..."
php -l hyreme-core.php
if [ $? -eq 0 ]; then
    echo "  ✅ PHP Syntax Valid"
else
    echo "  ❌ PHP Syntax Error - Fix required!"
    exit 1
fi

echo ""
echo "✓ Checking Handler Functions..."

# Count try-catch blocks
TRYCATCH=$(grep -c "try {" hyreme-core.php)
echo "  Found $TRYCATCH try-catch blocks (should be 9)"

# Verify key handlers exist
HANDLERS=("hyreme_ajax_save_candidate" "hyreme_ajax_send_message" "hyreme_ajax_get_messages" "hyreme_ajax_upload_resume" "hyreme_ajax_delete_resume" "hyreme_ajax_schedule_interview" "hyreme_ajax_get_recruiters" "hyreme_ajax_admin_delete_user" "hyreme_ajax_admin_delete_video")

for handler in "${HANDLERS[@]}"; do
    if grep -q "function $handler" hyreme-core.php; then
        echo "  ✅ $handler exists"
    else
        echo "  ❌ $handler missing!"
    fi
done

echo ""
echo "✓ Checking for Safe Practices..."

# Check for nullish coalescing
NULLISH=$(grep -c "\??" hyreme-core.php)
echo "  Found $NULLISH nullish coalescing operators (should be > 20)"

# Check for error suppression operator @
SUPPRESS=$(grep -c "@" hyreme-core.php)
echo "  Found $SUPPRESS error suppression uses"

echo ""
echo "✓ Checking Dashboard Files..."

# Verify dashboard files exist
for file in dashboards-recruiter.php dashboards-candidate.php admin-dashboard.php; do
    if [ -f "$file" ]; then
        echo "  ✅ $file exists"
    else
        echo "  ❌ $file missing!"
    fi
done

echo ""
echo "=================================="
echo "🟢 Stability Check Complete"
echo "=================================="
echo ""
echo "Next Steps:"
echo "1. Activate plugin in WordPress"
echo "2. Test messaging between recruiter and candidate"
echo "3. Test resume upload"
echo "4. Check browser console for errors (F12)"
echo "5. Check WordPress debug log: /wp-content/debug.log"
