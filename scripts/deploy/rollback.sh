#!/usr/bin/env bash
set -euo pipefail

if [ "$#" -lt 2 ]; then
  echo "Usage: $0 <releases_dir> <target_release_dir>"
  exit 1
fi

RELEASES_DIR="$1"
TARGET_RELEASE="$2"
CURRENT_LINK="${RELEASES_DIR}/current"

if [ ! -d "$TARGET_RELEASE" ]; then
  echo "Target release not found: $TARGET_RELEASE"
  exit 1
fi

ln -sfn "$TARGET_RELEASE" "$CURRENT_LINK"
echo "Rollback complete -> $TARGET_RELEASE"
