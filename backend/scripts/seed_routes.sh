#!/bin/bash

# Seed script for route statistics testing
# This script generates fake route data by calling the route calculation API
# with real station pairs and analytic codes, ensuring Dijkstra's algorithm is used

set -e

# Colors
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
RESET='\033[0m'

# Configuration
BACKEND_URL="${BACKEND_URL:-http://localhost:8000}"
COUNT="${1:-100}"  # Number of routes to generate (default: 100)

# Station pairs (from/to) - using various realistic combinations
STATIONS=(
    "MX:RODN"
    "MX:ZW"
    "VV:RODN"
    "BLON:PLEI"
    "MX:AVA"
    "CAUX:RODN"
    "MX:JAMA"
    "VV:MX"
    "GLI:RODN"
    "SONZ:JAMA"
    "CHER:AVA"
    "MX:CAUX"
    "CHOE:MTB"
    "ROSI:GST"
    "ZW:LENK"
    "MX:SONZ"
    "CGE:CHAL"
    "VV:STLE"
)

# Analytic codes from distances.json
ANALYTIC_CODES=(
    "MOB"
    "MVR-ce"
)

echo -e "${YELLOW}Seeding database with $COUNT realistic routes...${RESET}"

# Get JWT token
echo -e "${YELLOW}Getting authentication token...${RESET}"
TOKEN=$(docker compose exec -T backend php bin/console lexik:jwt:generate-token api_user 2>&1 | grep -oE 'eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+')

if [ -z "$TOKEN" ]; then
    echo "Error: Could not generate JWT token"
    exit 1
fi

echo -e "${GREEN}✓ Token acquired${RESET}"

SUCCESS=0
FAILED=0

for i in $(seq 1 $COUNT); do
    # Pick random station pair
    STATION_PAIR=${STATIONS[$RANDOM % ${#STATIONS[@]}]}
    FROM=$(echo $STATION_PAIR | cut -d: -f1)
    TO=$(echo $STATION_PAIR | cut -d: -f2)

    # Pick random analytic code
    ANALYTIC_CODE=${ANALYTIC_CODES[$RANDOM % ${#ANALYTIC_CODES[@]}]}

    # Calculate route via API (this uses Dijkstra internally)
    # Call through nginx proxy on https://localhost
    RESPONSE=$(curl -sk -X POST https://localhost/api/v1/routes \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $TOKEN" \
        -d "{\"fromStationId\":\"$FROM\",\"toStationId\":\"$TO\",\"analyticCode\":\"$ANALYTIC_CODE\"}")

    # Check if successful
    if echo "$RESPONSE" | grep -q '"id"'; then
        SUCCESS=$((SUCCESS + 1))
        echo -ne "\r${GREEN}Progress: $SUCCESS/$COUNT routes created${RESET}"
    else
        FAILED=$((FAILED + 1))
    fi
done

echo ""
echo -e "${GREEN}✓ Seeding complete!${RESET}"
echo "  - Successfully created: $SUCCESS routes"
echo "  - Failed: $FAILED routes"

# Randomize created_at dates for the newly created routes (last 90 days)
echo ""
echo -e "${YELLOW}Randomizing route creation dates over the last 90 days...${RESET}"
docker compose exec -T db psql -U app -d trainrouting -c "
UPDATE routes
SET created_at = NOW() - (FLOOR(RANDOM() * 90)::INT || ' days')::INTERVAL
                       - (FLOOR(RANDOM() * 24)::INT || ' hours')::INTERVAL
                       - (FLOOR(RANDOM() * 60)::INT || ' minutes')::INTERVAL
WHERE created_at > NOW() - INTERVAL '5 minutes';
" > /dev/null

echo -e "${GREEN}✓ Dates randomized${RESET}"

# Display summary
echo ""
echo -e "${YELLOW}Database summary:${RESET}"
docker compose exec -T db psql -U app -d trainrouting -c "
SELECT
    analytic_code,
    COUNT(*) as route_count,
    ROUND(SUM(distance_km)::NUMERIC, 2) as total_distance_km,
    ROUND(AVG(distance_km)::NUMERIC, 2) as avg_distance_km,
    MIN(created_at)::DATE as earliest_date,
    MAX(created_at)::DATE as latest_date
FROM routes
GROUP BY analytic_code
ORDER BY analytic_code;
"
