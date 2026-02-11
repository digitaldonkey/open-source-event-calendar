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
/^## Screenshots/ { exit } # Stop at the screenshots section
{ print }
' "$src" >> "$dest"

# Add WordPress screenshots
cat >> "$dest" << 'EOF'
== Screenshots ==

1. Month view with catergory colors set
2. Week view
3. Agenda view
4. Calendar block UI
5. Manage Ical feeds
6. Reoccurring events UI (based on [iCalendar-RFC-5545](https://icalendar.org/iCalendar-RFC-5545/3-8-5-3-recurrence-rule.html))
7. Cache settings
8. Agenda view in mobile. All calendars are mobile friendly

EOF

echo "WordPress README.txt generated"
