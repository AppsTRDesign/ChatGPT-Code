#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-http://127.0.0.1:8000}"
COOKIE_JAR="$(mktemp)"
trap 'rm -f "$COOKIE_JAR"' EXIT

TS="$(date +%s)"
REGISTER_PAYLOAD=$(cat <<JSON
{"username":"tester_${TS}","email":"tester_${TS}@mail.com","password":"Secret123"}
JSON
)

REGISTER=$(curl -s -c "$COOKIE_JAR" -X POST "$BASE_URL/api/auth/register" -H 'Content-Type: application/json' -d "$REGISTER_PAYLOAD")
OK=$(php -r '$j=json_decode($argv[1],true); echo ($j["success"] ?? false) ? "1" : "";' "$REGISTER")
[[ -n "$OK" ]] || { echo "Auth test failed"; exit 1; }

echo "Auth test passed"

REGIONS=$(curl -s "$BASE_URL/api/map/regions")
COUNT=$(php -r '$j=json_decode($argv[1],true); echo count($j["data"] ?? []);' "$REGIONS")
[[ "$COUNT" -ge 50 ]] || { echo "Region fetch test failed"; exit 1; }

echo "Region fetch test passed"

ME=$(curl -s -b "$COOKIE_JAR" "$BASE_URL/api/player/me")
CURRENT=$(php -r '$j=json_decode($argv[1],true); echo (int)($j["data"]["current_region_id"] ?? 0);' "$ME")
DEST=$(php -r '$j=json_decode($argv[1],true); foreach(($j["data"] ?? []) as $r){ if((int)$r["id"] !== (int)$argv[2]){ echo $r["id"]; break; }}' "$REGIONS" "$CURRENT")

TRAVEL_PAYLOAD=$(cat <<JSON
{"to_region_id":$DEST}
JSON
)
TRAVEL=$(curl -s -b "$COOKIE_JAR" -X POST "$BASE_URL/api/player/travel" -H 'Content-Type: application/json' -d "$TRAVEL_PAYLOAD")
STATUS=$(php -r '$j=json_decode($argv[1],true); echo $j["data"]["status"] ?? "";' "$TRAVEL")
[[ "$STATUS" == "traveling" ]] || { echo "Travel validation test failed"; exit 1; }

echo "Travel validation test passed"
