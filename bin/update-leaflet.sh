#!/bin/sh
#
# Downloads the bundled Leaflet and leaflet-control-geocoder assets from their
# GitHub releases.
#
# Versions are read from constants.php (OSEC_LEAFLET_VERSION,
# OSEC_LEAFLET_GEOCODER_VERSION), so bump the constant and re-run this script.
#
# Files are copied verbatim - never edit them. Source maps, development builds
# and TypeScript declarations are not shipped; a browser with devtools open will
# 404 on the .map reference, which is harmless.
#
# leaflet.css and images/ must stay siblings: Leaflet resolves its default marker
# icons from the CSS rule `.leaflet-default-icon-path`, relative to the stylesheet.
#
# Note on release assets: the two projects publish differently. Leaflet attaches
# leaflet.zip (dist only) to every release and tags with a "v" prefix.
# leaflet-control-geocoder tags without a prefix, does not commit dist/, and has
# only attached an asset since 4.0.0 - pinning an older version will fail here
# with the list of assets that release does offer. GitHub publishes no checksum
# for release assets (the API digest field is empty), so the sha256 recorded in
# osec-vendor.txt is computed locally: it documents what was installed, it does
# not authenticate the download beyond TLS.

set -e

OSEC_DIR=$(dirname $(dirname $(readlink -f "$0")))
LIB_DIR="$OSEC_DIR/public/js/external_libs"
TMP_DIR=$(mktemp -d)
trap 'rm -rf "$TMP_DIR"' EXIT

read_constant() {
    grep -A 3 "define(" "$OSEC_DIR/constants.php" \
        | grep -A 2 "'$1'" \
        | grep -o "'[0-9][0-9.]*'" \
        | head -n 1 \
        | tr -d "'"
}

# fetch_release <repo> <tag> <asset> <name>
# Downloads the named release asset and unpacks it to $TMP_DIR/<name>.
fetch_release() {
    REPO="$1"
    TAG="$2"
    ASSET="$3"
    NAME="$4"
    META="$TMP_DIR/$NAME.json"

    curl --show-error --silent --fail --location \
        "https://api.github.com/repos/$REPO/releases/tags/$TAG" --output "$META" \
        || {
            echo "Error: no GitHub release $TAG in $REPO." >&2
            exit 1
        }

    URL=$(grep -o '"browser_download_url": *"[^"]*'"$ASSET"'"' "$META" \
        | sed 's/.*"\(https[^"]*\)"/\1/' | head -n 1)

    if [ -z "$URL" ]; then
        echo "Error: release $TAG of $REPO has no asset named $ASSET." >&2
        echo "  Assets in that release:" >&2
        grep -o '"browser_download_url": *"[^"]*"' "$META" \
            | sed 's|.*/\([^/"]*\)"|    \1|' >&2 || echo "    (none)" >&2
        echo "  Releases before 4.0.0 of leaflet-control-geocoder ship no built" >&2
        echo "  files at all; dist/ is not in the repository either." >&2
        exit 1
    fi

    ARCHIVE="$TMP_DIR/$(basename "$URL")"
    curl --show-error --silent --fail --location "$URL" --output "$ARCHIVE"

    mkdir -p "$TMP_DIR/$NAME"
    case "$ARCHIVE" in
        *.zip) unzip -q "$ARCHIVE" -d "$TMP_DIR/$NAME" ;;
        *.tgz | *.tar.gz) tar xzf "$ARCHIVE" -C "$TMP_DIR/$NAME" ;;
        *) echo "Error: unsupported archive $ARCHIVE" >&2; exit 1 ;;
    esac

    # leaflet.zip has dist/ at the root, an npm tarball nests it in package/.
    if [ -d "$TMP_DIR/$NAME/dist" ]; then
        DIST="$TMP_DIR/$NAME/dist"
    elif [ -d "$TMP_DIR/$NAME/package/dist" ]; then
        DIST="$TMP_DIR/$NAME/package/dist"
    else
        echo "Error: no dist/ directory in $(basename "$URL")." >&2
        exit 1
    fi

    SHA=$(openssl dgst -sha256 "$ARCHIVE" | sed 's/.* //')
    printf '%s\n%s\n%s\n%s\n' "$TAG" "$URL" "$SHA" "$DIST" > "$TMP_DIR/$NAME.origin"
    echo "$REPO $TAG: $(basename "$URL") ($(wc -c < "$ARCHIVE") bytes)"
}

# install_files <name> <repo> <tag> <destination> <file>...
# Replaces the destination so files dropped upstream do not linger, copies the
# requested files plus LICENSE/README, and reports every other dist file so a
# version bump surfaces additions instead of silently ignoring them.
install_files() {
    NAME="$1"
    REPO="$2"
    TAG="$3"
    DEST="$4"
    shift 4
    DIST=$(sed -n 4p "$TMP_DIR/$NAME.origin")
    SRC=$(dirname "$DIST")

    rm -rf "$DEST"
    mkdir -p "$DEST"

    for FILE in "$@"; do
        if [ ! -f "$DIST/$FILE" ]; then
            echo "Error: $NAME dist/$FILE is missing - the layout changed upstream." >&2
            exit 1
        fi
        mkdir -p "$DEST/$(dirname "$FILE")"
        cp "$DIST/$FILE" "$DEST/$FILE"
    done

    # leaflet.zip carries dist/ only, so fall back to the repository at the tag.
    for FILE in LICENSE README.md; do
        if [ -f "$SRC/$FILE" ]; then
            cp "$SRC/$FILE" "$DEST/$FILE"
        else
            curl --show-error --silent --fail --location \
                "https://raw.githubusercontent.com/$REPO/$TAG/$FILE" \
                --output "$DEST/$FILE" \
                || echo "  warning: no $FILE in the archive or at $REPO@$TAG" >&2
        fi
    done

    # Marks the directory as an unmodified third-party library, so a local
    # customisation is never mistaken for vendor code (and vice versa).
    {
        echo "Third-party library - DO NOT EDIT."
        echo ""
        echo "Unmodified files as published by the project. Local changes would be"
        echo "silently discarded: bin/update-leaflet.sh replaces this directory."
        echo "Customised adaptations are marked as such and live elsewhere; see"
        echo "public/admin/less/admin-pages/control-geocoder.less for an example."
        echo ""
        echo "release: $REPO $(sed -n 1p "$TMP_DIR/$NAME.origin")"
        echo "asset:   $(sed -n 2p "$TMP_DIR/$NAME.origin")"
        echo "sha256:  $(sed -n 3p "$TMP_DIR/$NAME.origin")  (of the asset, computed locally)"
        echo "updated: $(date -u +%Y-%m-%d)"
        echo "script:  bin/update-leaflet.sh (version from constants.php)"
        echo ""
        echo "files:"
        for FILE in "$@"; do echo "  dist/$FILE"; done
    } > "$DEST/osec-vendor.txt"

    echo "  installed: $*"
    SKIPPED=$(cd "$DIST" && find . -type f | sed 's|^\./||' | sort \
        | grep -v -F -x "$(printf '%s\n' "$@")" || true)
    if [ -n "$SKIPPED" ]; then
        echo "  not shipped (review after a version bump):"
        echo "$SKIPPED" | sed 's/^/    /'
    fi
}

LEAFLET_VERSION=$(read_constant OSEC_LEAFLET_VERSION)
GEOCODER_VERSION=$(read_constant OSEC_LEAFLET_GEOCODER_VERSION)

if [ -z "$LEAFLET_VERSION" ] || [ -z "$GEOCODER_VERSION" ]; then
    echo "Error: could not read versions from $OSEC_DIR/constants.php" >&2
    exit 1
fi

echo "Target: $LIB_DIR"

fetch_release Leaflet/Leaflet "v$LEAFLET_VERSION" "leaflet.zip" leaflet
install_files leaflet Leaflet/Leaflet "v$LEAFLET_VERSION" "$LIB_DIR/leaflet" \
    leaflet.js \
    leaflet.css \
    images/marker-icon.png \
    images/marker-icon-2x.png \
    images/marker-shadow.png \
    images/layers.png \
    images/layers-2x.png

fetch_release perliedman/leaflet-control-geocoder "$GEOCODER_VERSION" ".tgz" geocoder
install_files geocoder perliedman/leaflet-control-geocoder "$GEOCODER_VERSION" \
    "$LIB_DIR/leaflet-control-geocoder" \
    Control.Geocoder.js

echo "Done."
