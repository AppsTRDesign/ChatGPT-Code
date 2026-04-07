#!/usr/bin/env bash
set -euo pipefail
BASE_URL="${1:-http://127.0.0.1:8000}"
COOKIE_JAR="$(mktemp)"
trap 'rm -f "$COOKIE_JAR"' EXIT

TS="$(date +%s)"
register_payload=$(cat <<JSON
{"username":"action_${TS}","email":"action_${TS}@mail.com","password":"Secret123"}
JSON
)
reg=$(curl -s -c "$COOKIE_JAR" -X POST "$BASE_URL/api/auth/register" -H 'Content-Type: application/json' -d "$register_payload")
ok=$(php -r '$j=json_decode($argv[1],true); echo ($j["success"] ?? false) ? "1" : "";' "$reg")
[[ -n "$ok" ]] || { echo "register failed"; exit 1; }

regions=$(curl -s "$BASE_URL/api/map/regions")
me=$(curl -s -b "$COOKIE_JAR" "$BASE_URL/api/player/me")
current=$(php -r '$j=json_decode($argv[1],true); echo (int)($j["data"]["current_region_id"] ?? 0);' "$me")
dest=$(php -r '$j=json_decode($argv[1],true); foreach(($j["data"] ?? []) as $r){ if((int)$r["id"] !== (int)$argv[2]){ echo $r["id"]; break; }}' "$regions" "$current")

valid_payload=$(cat <<JSON
{"region_id":$dest,"action":"travel"}
JSON
)
valid=$(curl -s -b "$COOKIE_JAR" -X POST "$BASE_URL/api/region/action" -H 'Content-Type: application/json' -d "$valid_payload")
valid_ok=$(php -r '$j=json_decode($argv[1],true); echo ($j["success"] ?? false) ? "1" : "0";' "$valid")
[[ "$valid_ok" == "1" ]] || { echo "valid travel action failed"; exit 1; }

echo "valid travel action passed"

same=$(curl -s -b "$COOKIE_JAR" -X POST "$BASE_URL/api/region/action" -H 'Content-Type: application/json' -d "$valid_payload")
same_err=$(php -r '$j=json_decode($argv[1],true); echo $j["error"] ?? "";' "$same")
[[ "$same_err" == "same_region" ]] || { echo "same region prevention failed"; exit 1; }

echo "same region prevention passed"

bad=$(curl -s -b "$COOKIE_JAR" -X POST "$BASE_URL/api/region/action" -H 'Content-Type: application/json' -d '{"region_id":999999,"action":"travel"}')
bad_err=$(php -r '$j=json_decode($argv[1],true); echo $j["error"] ?? "";' "$bad")
[[ "$bad_err" == "invalid_region" ]] || { echo "invalid region test failed"; exit 1; }

echo "invalid region test passed"

unauth=$(curl -s -X POST "$BASE_URL/api/region/action" -H 'Content-Type: application/json' -d "$valid_payload")
unauth_err=$(php -r '$j=json_decode($argv[1],true); echo $j["error"] ?? "";' "$unauth")
[[ "$unauth_err" == "Unauthorized" ]] || { echo "unauthorized test failed"; exit 1; }

echo "unauthorized request passed"
