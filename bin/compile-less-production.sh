#!/bin/sh
#
# npm install -g less less-plugin-clean-css less-watch-compiler
#
# Development
#    less-watch-compiler  public/admin/less/admin-pages/ public/admin/css/

SCRIPT_DIR=$(cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd)
SOURCE_PATH=$(realpath "$SCRIPT_DIR/../public/admin/less/admin-pages")
DEST_PATH=$(realpath "$SCRIPT_DIR/../public/admin/css")

echo "Processing directory:\n  $SOURCE_PATH\n to\n  $DEST_PATH\n\n"
for SOURCE_FILE in $SOURCE_PATH/*.less;
  do
    file_name=`basename "$SOURCE_FILE"`
    file_dest="${file_name%.less}.css"
    lessc --clean-css="-b" --verbose $SOURCE_FILE "$DEST_PATH/$file_dest"

#    echo "$DEST_PATH/$file_dest"
done
