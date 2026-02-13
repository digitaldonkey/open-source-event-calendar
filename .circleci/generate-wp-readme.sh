#!/usr/bin/env bash

set -euo pipefail

src=".github/README.md"
dest="README.txt"
phpfile="open-source-event-calendar.php"

echo "Generating WordPress README.txt from GitHub README.md"

# Begin header
cat > "$dest" << 'EOF'
=== Open Source Event Calendar ===

EOF

# WordPress Header from PHP header
awk '
/^\/\*\*/ { inblock=1; next }
/^\s*\*\// { exit }

inblock {
    sub(/^[[:space:]]*\* ?/, "")   # remove leading " * "
    if ($0 ~ /^(Contributors:|Tags:|Requires at least:|Tested up to:|Requires PHP:|Stable Tag:|License:|License URI:|Text Domain:|Domain Path:)/)
        print
}
' "$phpfile" >> "$dest"

# End of header
cat >> "$dest" << 'EOF'
Donate link: https://www.paypal.com/donate/?hosted_button_id=ZNWEQRQNJBTE6
A fully open-source WordPress event calendar with native iCal / ICS import and export.

EOF

# Add README.md content
awk '
NR < 9 { next } # Skip until content at line 9
/^## Screenshots/ { print; exit } # Stop at the screenshots section
{ print }
' "$src" >> "$dest"

# Generate WordPress screenshots
awk '
/^## Screenshots/ { insection=1; next }
insection && /^## / { exit }

insection && /!\[.*\]\(.*screenshot-[0-9]+\.png\)/ {
    match($0, /screenshot-[0-9]+\.png/)
    num = substr($0, RSTART + 11, RLENGTH - 15)
    getline caption
    print num ". " caption
}
' "$src" >> "$dest"

echo "WordPress README.txt generated"
