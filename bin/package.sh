#!/usr/bin/env bash
set -euo pipefail

SLUG="${1:-fp-privacy-cookie-policy}"
VERSION="${2:-0.1.1}"
PLUGIN_DIR_INPUT="${3:-.}"

ROOT_DIR="$(pwd)"
BUILD_DIR="${ROOT_DIR}/build"
STAGING_DIR="${BUILD_DIR}/${SLUG}"

rm -rf "${BUILD_DIR}"
mkdir -p "${STAGING_DIR}"

# Percorso assoluto della sorgente da impacchettare
SRC_DIR="$(cd "${PLUGIN_DIR_INPUT}" && pwd)"

# File/dir da includere (relativi a SRC_DIR)
INCLUDE_PATHS=(
  "fp-privacy-cookie-policy.php"
  "src"
  "assets"
  "blocks"
  "templates"
  "languages"
  "inc"
  "README.md"
  "readme.txt"
  "CHANGELOG.md"
  "LICENSE"
  ".gitattributes"
  ".gitignore"
  "bin/qa-checklist.md"
)

# Copia filtrata
for p in "${INCLUDE_PATHS[@]}"; do
  if [ -e "${SRC_DIR}/${p}" ]; then
    rsync -a --exclude ".DS_Store" "${SRC_DIR}/${p}" "${STAGING_DIR}/"
  fi
done

# Esclusioni dure (anti-binary/dev)
find "${STAGING_DIR}" -type f \
  \( -name "*.map" -o -name "*.min.*" -o -name "*.zip" -o -name "*.png" -o -name "*.jpg" -o -name "*.jpeg" -o -name "*.gif" \
  -o -name "*.svg" -o -name "*.webp" -o -name "*.ico" -o -name "*.lock" -o -name "composer.lock" \) \
  -delete
rm -rf "${STAGING_DIR}/node_modules" "${STAGING_DIR}/vendor" "${STAGING_DIR}/dist" "${STAGING_DIR}/build" 2>/dev/null || true

# Lint PHP nel pacchetto finale
find "${STAGING_DIR}" -type f -name "*.php" -print0 | xargs -0 -n1 php -l

# ZIP con cartella radice = slug
pushd "${BUILD_DIR}" >/dev/null
ZIP_NAME="${SLUG}-${VERSION}.zip"
zip -r "${ZIP_NAME}" "${SLUG}" \
  -x "*.DS_Store" -x "*node_modules/*" -x "*vendor/*" -x "*dist/*" -x "*build/*" -x "*.map" -x "*.min.*"
popd >/dev/null

echo "Creato: ${BUILD_DIR}/${ZIP_NAME}"
