#!/bin/bash

# VTPASS Service Registration Test - All Airtime & Data Services
# This will test all services and display request IDs for registration

echo "=============================================="
echo "VTPASS SERVICE REGISTRATION TEST"
echo "=============================================="
echo ""
echo "All AIRTIME and DATA services for VTPass"
echo ""

# You need to replace these with your actual VTPass credentials
VTPASS_API_URL="https://sandbox.vtpass.com/api/"  # or production URL
VTPASS_API_KEY="your_api_key_here"
VTPASS_PUBLIC_KEY="your_public_key_here"

echo "=============================================="
echo "AIRTIME SERVICES"
echo "=============================================="
echo ""

# Define all airtime services
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
    REQUEST_ID="REQUEST_$(echo $service | tr '[:lower:]' '[:upper:]' | tr '-' '_')_$(date +%s)"
    echo "Service: $service"
    echo "Request ID: $REQUEST_ID"
    echo "---"
    echo ""
done

echo "=============================================="
echo "DATA SERVICES"
echo "=============================================="
echo ""

# Define all data services
DATA_SERVICES=(
    "mtn-data"
    "glo-data"
    "airtel-data"
    "9mobile-data"
)

for service in "${DATA_SERVICES[@]}"; do
    REQUEST_ID="REQUEST_$(echo $service | tr '[:lower:]' '[:upper:]' | tr '-' '_')_$(date +%s)"
    echo "Service: $service"
    echo "Request ID: $REQUEST_ID"
    echo "---"
    echo ""
done

echo "=============================================="
echo "REGISTRATION SUMMARY"
echo "=============================================="
echo ""
echo "Total Airtime Services: ${#AIRTIME_SERVICES[@]}"
echo "Total Data Services: ${#DATA_SERVICES[@]}"
echo "Total Services: $((${#AIRTIME_SERVICES[@]} + ${#DATA_SERVICES[@]}))"
echo ""
echo "Copy the Request IDs above to register with VTPass"
echo ""
