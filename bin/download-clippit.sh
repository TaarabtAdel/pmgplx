#!/usr/bin/env bash
# Tai Clippit CLI (win32-x64) vao laravel/bin/clippit.exe — chay tren Mac/Linux co npm.
set -euo pipefail
DIR="$(cd "$(dirname "$0")" && pwd)"
OUT="$DIR/clippit.exe"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
cd "$TMP"
npm pack @sergey-tihon/clippit-bin-win32-x64 --silent
tar -xzf sergey-tihon-clippit-bin-win32-x64-*.tgz
cp package/clippit.exe "$OUT"
echo "OK: $OUT ($(wc -c < "$OUT") bytes)"
