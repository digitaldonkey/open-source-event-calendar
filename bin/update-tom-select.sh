#!/bin/sh
#
# Downloads the bundled Tom Select (https://tom-select.js.org) from the npm
# registry into public/js/external_libs/tom-select.
#
# Usage: bin/update-tom-select.sh
#
# The version is read from constants.php (OSEC_TOM_SELECT_VERSION), so bump the
# constant and re-run this script. Same conventions as bin/update-leaflet.sh:
# files are copied verbatim - never edit them - and the directory is replaced,
# so files dropped upstream do not linger. Source maps are not shipped; a
# browser with devtools open will 404 on the .map reference, which is harmless.
#
# Shipped: the "complete" build (core plus all plugins; the filters use
# remove_button and clear_button) and the base stylesheet; the themes style
# the widget on top of it.
#
# Unlike GitHub release assets, the npm registry publishes a checksum: the
# tarball is verified against the registry's sha512 integrity value before
# anything is installed.

set -e

OSEC_DIR=$(dirname $(dirname $(readlink -f "$0")))
DEST="$OSEC_DIR/public/js/external_libs/tom-select"
TMP_DIR=$(mktemp -d)
trap 'rm -rf "$TMP_DIR"' EXIT

VERSION=$(grep -A 3 "define(" "$OSEC_DIR/constants.php" \
    | grep -A 2 "'OSEC_TOM_SELECT_VERSION'" \
    | grep -o "'[0-9][0-9.]*'" \
    | head -n 1 \
    | tr -d "'")
if [ -z "$VERSION" ]; then
    echo "Error: could not read OSEC_TOM_SELECT_VERSION from $OSEC_DIR/constants.php" >&2
    exit 1
fi

FILES="js/tom-select.complete.min.js css/tom-select.css"
URL="https://registry.npmjs.org/tom-select/-/tom-select-$VERSION.tgz"
ARCHIVE="$TMP_DIR/tom-select.tgz"

curl --show-error --silent --fail --location \
    "https://registry.npmjs.org/tom-select/$VERSION" --output "$TMP_DIR/meta.json" \
    || { echo "Error: no tom-select $VERSION on npm." >&2; exit 1; }
INTEGRITY=$(grep -o '"integrity": *"sha512-[^"]*"' "$TMP_DIR/meta.json" \
    | sed 's/.*"sha512-\([^"]*\)"/\1/' | head -n 1)
if [ -z "$INTEGRITY" ]; then
    echo "Error: the registry lists no sha512 integrity for tom-select $VERSION." >&2
    exit 1
fi

curl --show-error --silent --fail --location "$URL" --output "$ARCHIVE"
ACTUAL=$(openssl dgst -sha512 -binary "$ARCHIVE" | openssl base64 -A)
if [ "$ACTUAL" != "$INTEGRITY" ]; then
    echo "Error: checksum mismatch for $URL" >&2
    exit 1
fi
tar xzf "$ARCHIVE" -C "$TMP_DIR"
DIST="$TMP_DIR/package/dist"

rm -rf "$DEST"
mkdir -p "$DEST"
for FILE in $FILES; do
    if [ ! -f "$DIST/$FILE" ]; then
        echo "Error: dist/$FILE is missing - the layout changed upstream." >&2
        exit 1
    fi
    cp "$DIST/$FILE" "$DEST/$(basename "$FILE")"
done
cp "$TMP_DIR/package/LICENSE" "$DEST/LICENSE"

{
    echo "Third-party library - DO NOT EDIT."
    echo ""
    echo "Unmodified files as published by the project. Local changes would be"
    echo "silently discarded: bin/update-tom-select.sh replaces this directory."
    echo "Theme styling lives in the themes' LESS (taxonomy filter)."
    echo ""
    echo "release: tom-select $VERSION (npm, Apache-2.0)"
    echo "asset:   $URL"
    echo "sha512:  $INTEGRITY  (npm registry integrity, verified)"
    echo "updated: $(date -u +%Y-%m-%d)"
    echo "script:  bin/update-tom-select.sh (version from constants.php)"
    echo ""
    echo "files:"
    for FILE in $FILES; do echo "  dist/$FILE"; done
} > "$DEST/osec-vendor.txt"

echo "Installed tom-select $VERSION: $FILES"
