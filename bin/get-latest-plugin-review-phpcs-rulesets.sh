#!/bin/sh

DEST_FILE_NAME="plugin-check.ruleset.xml"

LATEST_TAG=$(curl --show-error --silent "https://api.github.com/repos/WordPress/plugin-check/tags" | jq -r '.[0].name')
OSEC_DIR=$(dirname $(dirname $(readlink -f "$0")))

echo "Downloading latest $DEST_FILE_NAME (Release $LATEST_TAG) \n to $OSEC_DIR/$DEST_FILE_NAME"

curl --show-error --silent "https://raw.githubusercontent.com/WordPress/plugin-check/refs/tags/$LATEST_TAG/phpcs-rulesets/$DEST_FILE_NAME" > "$OSEC_DIR/$DEST_FILE_NAME"
