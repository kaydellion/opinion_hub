#!/bin/bash

# VTPASS Request ID Generator - Correct Format
# Format: YYYYMMDDHHMI (12 numeric chars) + optional alphanumeric string
# Timezone: Africa/Lagos (GMT +1)

# Set timezone to Africa/Lagos
export TZ='Africa/Lagos'

echo "=============================================="
echo "VTPASS REQUEST ID GENERATOR"
echo "Timezone: Africa/Lagos (GMT +1)"
echo "=============================================="
echo ""

# Get current date and time in Africa/Lagos timezone
CURRENT_DATE=$(date +%Y%m%d%H%M)
echo "Current Date/Time (Africa/Lagos): $(date)"
echo "Base Request ID (YYYYMMDDHHMI): $CURRENT_DATE"
echo ""

echo "=============================================="
echo "AIRTIME SERVICES - Request IDs"
echo "=============================================="
echo ""

AIRTIME_SERVICES=(
    "mtn-airtime"
    "mtn-gifting"
    "glo-airtime"
    "glo-gifting"
    "airtel-airtime"
    "airtel-gifting"
    "9mobile-airtime"
    "9mobile-gifting"
)

for service in "${AIRTIME_SERVICES[@]}"; do
    # Generate random suffix (alphanumeric string)
    RANDOM_SUFFIX=$(openssl rand -hex 8)
    REQUEST_ID="${CURRENT_DATE}_${RANDOM_SUFFIX}"
    
    echo "Service: $service"
    echo "Request ID: $REQUEST_ID"
    echo "  - Format: ${CURRENT_DATE} (base) + ${RANDOM_SUFFIX} (random)"
    echo "  - Length: ${#REQUEST_ID} characters"
    echo ""
done

echo "=============================================="
echo "DATA SERVICES - Request IDs"
echo "=============================================="
echo ""

DATA_SERVICES=(
    "mtn-data"
    "glo-data"
    "airtel-data"
    "9mobile-data"
)

for service in "${DATA_SERVICES[@]}"; do
    # Generate random suffix (alphanumeric string)
    RANDOM_SUFFIX=$(openssl rand -hex 8)
    REQUEST_ID="${CURRENT_DATE}_${RANDOM_SUFFIX}"
    
    echo "Service: $service"
    echo "Request ID: $REQUEST_ID"
    echo "  - Format: ${CURRENT_DATE} (base) + ${RANDOM_SUFFIX} (random)"
    echo "  - Length: ${#REQUEST_ID} characters"
    echo ""
done

echo "=============================================="
echo "INSTRUCTIONS FOR VTPASS REGISTRATION"
echo "=============================================="
echo ""
echo "1. Login to your VTPass Dashboard"
echo "2. Go to Sandbox/Test environment"
echo "3. For EACH service listed above:"
echo "   a. Make a test transaction"
echo "   b. Use the corresponding Request ID above"
echo "   c. Complete the transaction"
echo "4. After successful test, go to 'API Transactions' tab"
echo "5. Find each transaction and copy the actual Request ID"
echo "6. Use those Request IDs for your API registration"
echo ""
echo "Note: VTPass will show you the exact Request ID generated"
echo "      during your sandbox transaction. That's what you need!"
echo ""
