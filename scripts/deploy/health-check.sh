#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-https://game.noasoft.org}"

echo "[health] checking ${BASE_URL}/api/v1/health"
curl -fsS "${BASE_URL}/api/v1/health" | sed -e 's/.*/[health] &/'

echo "[health] ok"
