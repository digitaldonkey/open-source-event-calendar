#!/bin/sh

DEST_FILE_NAME="phpcs-wp-plugin-review.xml"
LATEST_TAG=$(curl --show-error --silent "https://api.github.com/repos/WordPress/plugin-check/tags" | jq -r '.[0].name')
OSEC_DIR=$(dirname $(dirname $(readlink -f "$0")))

echo "Downloading latest plugin-review.xml (Release $LATEST_TAG) \n to $OSEC_DIR/$DEST_FILE_NAME"

curl --show-error --silent "https://raw.githubusercontent.com/WordPress/plugin-check/refs/tags/$LATEST_TAG/phpcs-rulesets/plugin-review.xml" > "$OSEC_DIR/$DEST_FILE_NAME"


