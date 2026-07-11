#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SLUG="virtual-optimizer"
PLUGIN_FILE="$PLUGIN_SLUG.php"
BUILD_DIR="/tmp/$PLUGIN_SLUG-build"
ZIP_NAME="$PLUGIN_SLUG.zip"
ORIG_DIR="$(pwd)"

echo "==> Cleaning build dir"
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

echo "==> Exporting from git (production files only)"
git archive HEAD --format=tar --prefix="$PLUGIN_SLUG/" | tar xf - -C "$BUILD_DIR"

echo "==> Installing composer dependencies (no dev)"
cd "$BUILD_DIR/$PLUGIN_SLUG"
cp "$ORIG_DIR/composer.json" "$ORIG_DIR/composer.lock" ./ 2>/dev/null || true
composer install --no-dev --optimize-autoloader --no-interaction --quiet
rm -f composer.json composer.lock

echo "==> Building zip"
cd "$BUILD_DIR"
zip -r "$ZIP_NAME" "$PLUGIN_SLUG" -x "*/.git/*" > /dev/null 2>&1
cp "$ZIP_NAME" "$ORIG_DIR/$ZIP_NAME"

echo "==> Cleanup"
rm -rf "$BUILD_DIR"

SIZE=$(du -h "$ORIG_DIR/$ZIP_NAME" | cut -f1)
echo "Done: $ORIG_DIR/$ZIP_NAME ($SIZE)"
