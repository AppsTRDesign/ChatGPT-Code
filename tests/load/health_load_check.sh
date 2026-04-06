#!/usr/bin/env bash
set -euo pipefail

TARGET_URL="${TARGET_URL:-http://127.0.0.1/health}"
REQUESTS="${REQUESTS:-20}"
CONCURRENCY="${CONCURRENCY:-5}"

if ! command -v curl >/dev/null 2>&1; then
  echo "curl bulunamadı" >&2
  exit 1
fi

echo "Load check: $TARGET_URL (requests=$REQUESTS, concurrency=$CONCURRENCY)"

seq "$REQUESTS" | xargs -n1 -P"$CONCURRENCY" -I{} sh -c '
  code=$(curl -s -o /dev/null -w "%{http_code}" "$0" || true)
  [ "$code" = "200" ] || { echo "failed:$code"; exit 1; }
' "$TARGET_URL"

echo "Health load check passed"
