#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-http://127.0.0.1:8000}"
TS="$(date +%s)"
USERNAME="tester_${TS}"
EMAIL="tester_${TS}@mail.com"

REGISTER_PAYLOAD=$(cat <<JSON
{"username":"$USERNAME","email":"$EMAIL","password":"Secret123"}
JSON
)

REGISTER=$(curl -s -X POST "$BASE_URL/api/auth/register" -H 'Content-Type: application/json' -d "$REGISTER_PAYLOAD")
TOKEN=$(php -r '$j=json_decode($argv[1],true); echo $j["token"] ?? "";' "$REGISTER")

if [[ -z "$TOKEN" ]]; then
  echo "Auth test failed"
  exit 1
fi

echo "Auth test passed"

REGIONS=$(curl -s "$BASE_URL/api/map/regions")
COUNT=$(php -r '$j=json_decode($argv[1],true); echo count($j["data"] ?? []);' "$REGIONS")
if [[ "$COUNT" -lt 50 ]]; then
  echo "Region fetch test failed"
  exit 1
fi

echo "Region fetch test passed"

ME=$(curl -s "$BASE_URL/api/player/me" -H "Authorization: Bearer $TOKEN")
CURRENT=$(php -r '$j=json_decode($argv[1],true); echo (int)($j["data"]["current_region_id"] ?? 0);' "$ME")
DEST=$(php -r '$j=json_decode($argv[1],true); foreach(($j["data"] ?? []) as $r){ if((int)$r["id"] !== (int)$argv[2]){ echo $r["id"]; break; }}' "$REGIONS" "$CURRENT")

TRAVEL_PAYLOAD=$(cat <<JSON
{"to_region_id":$DEST}
JSON
)
TRAVEL=$(curl -s -X POST "$BASE_URL/api/player/travel" -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' -d "$TRAVEL_PAYLOAD")
STATUS=$(php -r '$j=json_decode($argv[1],true); echo $j["data"]["status"] ?? "";' "$TRAVEL")
if [[ "$STATUS" != "completed" ]]; then
  echo "Travel validation test failed"
  exit 1
fi

echo "Travel validation test passed"
