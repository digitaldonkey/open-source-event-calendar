#!/bin/bash
#
# Shared helpers of the DDEV commands osec-review and osec-screenshots (bin/ddev/commands/web/). Sourced, not run:
#
#   . /var/www/html/wp-content/plugins/open-source-event-calendar/bin/ddev/osec-lib.sh
#   osec_site "$@"; set -- "${OSEC_ARGS[@]}"
#
# Lives outside commands/web/, so it is not copied into .ddev/commands/web/ (DDEV would list it as a command).
# Runs inside the DDEV web container.

OSEC_PLUGIN_DIR=/var/www/html/wp-content/plugins/open-source-event-calendar

# Prints an error and exits.
osec_die() {
    echo "$(basename "$0"): $*" >&2
    exit 1
}

# Picks the site from a leading argument and exports WP_SANDBOX_DB for WP-CLI and the tests:
# `dev` (or none) = the DDEV dev site, `claude` or `try_<name>` = that sandbox database. The remaining arguments
# are left in OSEC_ARGS.
osec_site() {
    OSEC_ARGS=("$@")
    case "${1:-}" in
        dev)
            unset WP_SANDBOX_DB
            OSEC_ARGS=("${@:2}")
            ;;
        claude | try_*)
            export WP_SANDBOX_DB=$1
            OSEC_ARGS=("${@:2}")
            ;;
    esac
}

# Home URL of the selected site (WordPress takes it from WP_HOME, per sandbox).
osec_home() {
    /usr/local/bin/wp option get home --path=/var/www/html 2>/dev/null
}

# Installs the npm packages of a folder once (node_modules missing).
osec_npm_ready() {
    [ -d "$1/node_modules" ] || (cd "$1" && npm install --no-audit --no-fund) || osec_die "npm install failed in $1"
}

# Stops unless the DDEV playwright service answers on port 3000.
osec_playwright_ready() {
    timeout 3 bash -c 'cat < /dev/null > /dev/tcp/playwright/3000' 2>/dev/null \
        || osec_die "the DDEV playwright service (ws://playwright:3000/) does not answer - is .ddev/docker-compose.playwright.yaml in place and DDEV restarted?"
}
