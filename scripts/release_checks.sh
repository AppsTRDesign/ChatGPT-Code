#!/usr/bin/env bash
set -euo pipefail

echo "[1/3] PHP syntax checks"
for f in $(rg --files -g '*.php'); do
  php -l "$f" >/dev/null
done

echo "[2/3] Frontend syntax check"
node --check assets/js/app.js

echo "[3/3] Migration order sanity"
rg -n "SOURCE db/migrations/" database.sql

echo "[4/4] Test suite"
./scripts/test_suite.sh

echo "Release checks completed successfully."
