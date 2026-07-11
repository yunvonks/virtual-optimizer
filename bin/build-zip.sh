#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SLUG="virtual-optimizer"
PLUGIN_FILE="$PLUGIN_SLUG.php"
BUILD_DIR="/tmp/$PLUGIN_SLUG-build"
ZIP_NAME="$PLUGIN_SLUG.zip"

echo "==> Cleaning build dir"
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

echo "==> Exporting from git (production files only)"
git archive HEAD --format=tar --prefix="$PLUGIN_SLUG/" | tar xf - -C "$BUILD_DIR"

echo "==> Installing composer dependencies (no dev)"
cd "$BUILD_DIR/$PLUGIN_SLUG"
composer install --no-dev --optimize-autoloader --quiet

echo "==> Building zip"
cd "$BUILD_DIR"
zip -r "$ZIP_NAME" "$PLUGIN_SLUG" -x "*/.git/*" > /dev/null 2>&1
mv "$ZIP_NAME" "$OLDPWD/"

echo "==> Cleanup"
rm -rf "$BUILD_DIR"

echo "Done: $ZIP_NAME ($(du -h "$OLDPWD/$ZIP_NAME" | cut -f1))"
