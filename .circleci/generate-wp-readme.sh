#!/usr/bin/env bash

set -euo pipefail

src=".github/README.md"
dest="README.txt"

echo "Generating WordPress README.txt from GitHub README.md"

# WordPress Header
cat > "$dest" << 'EOF'
=== Open Source Event Calendar ===

Tags: calendar, events, ics, ical importer
Requires at least: 6.6
Requires PHP: 8.2
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: open-source-event-calendar
Domain Path: /languages
Contributors: digitaldonkey, hubrik, vtowel, yaniiliev, nicolapeluchetti, jbutkus, lpawlik, bangelov  
Donate link: https://www.paypal.com/donate/?hosted_button_id=ZNWEQRQNJBTE6
Tested up to: 6.9
Stable Tag: 1.0.11
EOF

# Add README.md content
awk '
NR == 1 { next } # Skip first line
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
