#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_SLUG="$(basename "$SCRIPT_DIR")"
SET_VERSION=""
BUMP="patch"
ZIP_NAME=""

while [ "$#" -gt 0 ]; do
    case "$1" in
        --set-version=*)
            SET_VERSION="${1#*=}"
            shift
            ;;
        --set-version)
            shift
            if [ "$#" -eq 0 ]; then
                echo "Missing value for --set-version" >&2
                exit 1
            fi
            SET_VERSION="$1"
            shift
            ;;
        --bump=*)
            BUMP="${1#*=}"
            shift
            ;;
        --bump)
            shift
            if [ "$#" -eq 0 ]; then
                echo "Missing value for --bump" >&2
                exit 1
            fi
            BUMP="$1"
            shift
            ;;
        --zip-name=*)
            ZIP_NAME="${1#*=}"
            shift
            ;;
        --zip-name)
            shift
            if [ "$#" -eq 0 ]; then
                echo "Missing value for --zip-name" >&2
                exit 1
            fi
            ZIP_NAME="$1"
            shift
            ;;
        -h|--help)
            cat <<USAGE
Usage: bash build.sh [options]
  --set-version=X.Y.Z   Set the version explicitly.
  --bump=patch|minor|major
                        Bump the version (default: patch).
  --zip-name=NAME       Custom name for the resulting zip (optional).
  -h, --help            Show this help text.
USAGE
            exit 0
            ;;
        *)
            echo "Unknown option: $1" >&2
            exit 1
            ;;
    esac
done

if [ -n "$SET_VERSION" ]; then
    VERSION="$(php "$SCRIPT_DIR/tools/bump-version.php" --set="$SET_VERSION")"
else
    case "$BUMP" in
        patch|minor|major)
            VERSION="$(php "$SCRIPT_DIR/tools/bump-version.php" --"$BUMP")"
            ;;
        none|"")
            VERSION="$(php "$SCRIPT_DIR/tools/bump-version.php" --patch)"
            ;;
        *)
            echo "Invalid value for --bump: $BUMP" >&2
            exit 1
            ;;
    esac
fi

cd "$SCRIPT_DIR"
rm -rf vendor
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
composer dump-autoload -o --classmap-authoritative

BUILD_ROOT="$SCRIPT_DIR/build"
TARGET_DIR="$BUILD_ROOT/$PLUGIN_SLUG"
rm -rf "$TARGET_DIR"
mkdir -p "$TARGET_DIR"

RSYNC_EXCLUDES=(
    "--exclude=.git/"
    "--exclude=.github/"
    "--exclude=tests/"
    "--exclude=docs/"
    "--exclude=node_modules/"
    "--exclude=*.md"
    "--exclude=.idea/"
    "--exclude=.vscode/"
    "--exclude=build/"
    "--exclude=.gitattributes"
    "--exclude=.gitignore"
)

rsync -a --delete "${RSYNC_EXCLUDES[@]}" "$SCRIPT_DIR/" "$TARGET_DIR/"

TIMESTAMP="$(date +%Y%m%d%H%M)"
if [ -n "$ZIP_NAME" ]; then
    case "$ZIP_NAME" in
        *.zip)
            ZIP_FILE="$BUILD_ROOT/$ZIP_NAME"
            ;;
        *)
            ZIP_FILE="$BUILD_ROOT/${ZIP_NAME}.zip"
            ;;
    esac
else
    ZIP_FILE="$BUILD_ROOT/${PLUGIN_SLUG}-${TIMESTAMP}.zip"
fi

rm -f "$ZIP_FILE"
(cd "$BUILD_ROOT" && zip -rq "${ZIP_FILE##*/}" "$PLUGIN_SLUG")

TOP_LEVEL_ENTRIES="$(zipinfo -1 "$ZIP_FILE" | awk -F'/' '{print $1}' | sort -u)"

cat <<INFO
Version: $VERSION
Zip file: $ZIP_FILE
Top-level entries:
$TOP_LEVEL_ENTRIES
INFO
