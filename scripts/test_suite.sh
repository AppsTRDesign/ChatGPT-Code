#!/usr/bin/env bash
set -euo pipefail

php tests/unit/StatFormulaServiceTest.php
php tests/integration/RouterDispatchTest.php

if [ "${RUN_LOAD_TEST:-0}" = "1" ]; then
  tests/load/health_load_check.sh
else
  echo "Load test skipped (set RUN_LOAD_TEST=1 to enable)"
fi
