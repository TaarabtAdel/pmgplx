#!/usr/bin/env bash
# Tai qpdf Windows x64 (msvc) vao laravel/bin/ — dung tren server IIS.
set -euo pipefail
VERSION="${QPDF_VERSION:-12.4.1}"
HERE="$(cd "$(dirname "$0")" && pwd)"
ZIP="/tmp/qpdf-${VERSION}-msvc64.zip"
URL="https://github.com/qpdf/qpdf/releases/download/v${VERSION}/qpdf-${VERSION}-msvc64.zip"
echo "Downloading ${URL} ..."
curl -fsSL -o "$ZIP" "$URL"
unzip -jo "$ZIP" "qpdf-${VERSION}-msvc64/bin/*" -d "$HERE"
rm -f "$ZIP"
echo "OK: ${HERE}/qpdf.exe (+ DLL cung thu muc)"
