#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT_DIR"

TARGET_DIRS=(app public routes scripts tests)

matches=()
while IFS= read -r line; do
  matches+=("$line")
done < <(
  find "${TARGET_DIRS[@]}" -type f \
    \( -name '*.bak' -o -name '*.bak[0-9]*' -o -name '*.backup' -o -name '*.old' \) \
    -print 2>/dev/null | sort
)

if [[ ${#matches[@]} -gt 0 ]]; then
  echo "Legacy artifacts found in active directories:"
  printf ' - %s\n' "${matches[@]}"
  echo
  echo "Move these files to storage/legacy-quarantine or remove them after validation."
  exit 1
fi

echo "No legacy artifacts found in active directories."
