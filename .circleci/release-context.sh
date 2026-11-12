#!/usr/bin/env bash
#
# Decides what a pipeline releases and prints it as shell assignments for the
# deploy jobs (release_decision_job writes them to /tmp/release.env):
#
#   RELEASE_MODE     none | dev | tagged | dryrun
#   RELEASE_VERSION  X.Y.Z for tagged and dryrun, empty otherwise
#
# WordPress.org serves trunk as a version whose tags/X.Y.Z is missing, so a new
# Stable Tag must never reach trunk ahead of its tag. "Latest" is the highest
# X.Y.Z in the tags/ listing on WordPress.org.
#
# master push, Stable Tag in README.txt and plugin header, must be equal X.Y.Z:
#   equals latest                   -> dev (trunk + GitHub "dev")
#   newer, git tag X.Y.Z on this    -> none, once tags/X.Y.Z appears on
#   exact commit                       WordPress.org: the tag pipeline releases
#                                      it. Fails if it does not appear within
#                                      RELEASE_WAIT_SECONDS
#   newer, anything else            -> fails
#   older                           -> fails
#   commit is not master's HEAD     -> none: the newer pipeline deploys
# tag X.Y.Z, tags/X.Y.Z missing     -> tagged (GitHub X.Y.Z + "dev", then
#                                      tags/X.Y.Z + trunk in one SVN commit)
# tag X.Y.Z, tags/X.Y.Z exists      -> none: already released
# tag X.Y.Z-dryrun                  -> dryrun (the same, without committing or
#                                      publishing; may be on a release-* commit)
# anything else                     -> none
#
# Options (environment, for tests):
#   RELEASE_WAIT_SECONDS  how long master waits for tags/X.Y.Z (default 3600)
#   RELEASE_POLL_SECONDS  pause between two reads of the listing (default 60)
#
# release_decision_job runs on master and release tags only, after all tests.
# Any failed check, also an unreadable tags/ listing, fails it (exit 1), so
# nothing is deployed; while master waits, an unreadable listing only counts as
# "not there yet". Messages go to stderr, the assignments to stdout.

set -Eeuo pipefail

note() { echo "release-context: $*" >&2; }
fail() { note "$*"; exit 1; }
emit() { printf 'RELEASE_MODE=%s\nRELEASE_VERSION=%s\n' "$1" "$2"; }

sha=${CIRCLE_SHA1:?CIRCLE_SHA1 is not set}
RELEASE_WAIT_SECONDS=${RELEASE_WAIT_SECONDS:-3600}
RELEASE_POLL_SECONDS=${RELEASE_POLL_SECONDS:-60}
[[ "$RELEASE_WAIT_SECONDS" =~ ^[0-9]+$ && "$RELEASE_POLL_SECONDS" =~ ^[0-9]+$ ]] \
    || fail "RELEASE_WAIT_SECONDS and RELEASE_POLL_SECONDS must be whole seconds"

# The first word after the first match of a regex in a file.
field() { sed -nE "s/$2[[:space:]]*([^[:space:]]+).*/\1/p" "$1" | head -n1; }

# True when version $1 is higher than version $2.
is_newer() { [[ "$1" != "$2" && "$(printf '%s\n%s\n' "$1" "$2" | sort -V | tail -n1)" == "$1" ]]; }

# Prints the versions in tags/ on WordPress.org, lowest first. No answer is never
# "no tag": a failed request or a listing without versions fails the job.
svn_tags() {
    local url listing tags
    url=${WP_ORG_SVN_URL:-https://plugins.svn.wordpress.org/${WP_ORG_PLUGIN_NAME:?WordPress.org plugin name not set}}
    listing=$(curl -fsSL --retry 2 --max-time 30 "$url/tags/") || fail "could not read $url/tags/"
    tags=$(grep -oE 'href="[0-9]+\.[0-9]+\.[0-9]+/"' <<< "$listing" | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | sort -V || true)
    [[ -n "$tags" ]] || fail "no versions found in $url/tags/"
    printf '%s\n' "$tags"
}

# Waits until tags/$1 is in the listing, reading it every RELEASE_POLL_SECONDS,
# and fails after RELEASE_WAIT_SECONDS. Prints one line per read, so the job
# never hits CircleCI's no-output timeout.
wait_for_release() {
    local version=$1 wait=$RELEASE_WAIT_SECONDS poll=$RELEASE_POLL_SECONDS tags deadline
    deadline=$((SECONDS + wait))
    while true; do
        if tags=$(svn_tags) && grep -qxF "$version" <<< "$tags"; then
            note "tags/$version is on WordPress.org"
            return 0
        fi
        ((SECONDS < deadline)) \
            || fail "tags/$version did not appear on WordPress.org within ${wait}s: check the pipeline of tag $version"
        note "tags/$version is not on WordPress.org yet, waited $((SECONDS - deadline + wait))s of ${wait}s"
        sleep "$poll"
    done
}

git fetch --quiet --force origin '+refs/heads/master:refs/remotes/origin/master'
master=$(git rev-parse refs/remotes/origin/master)

if [[ -z "${CIRCLE_TAG:-}" ]]; then
    if [[ "${CIRCLE_BRANCH:-}" != "master" ]]; then
        emit none ""
        exit 0
    fi
    if [[ "$sha" != "$master" ]]; then
        # A rerun of an older master pipeline, or one overtaken by a newer push,
        # must not roll trunk or the "dev" release back.
        note "commit $sha is no longer master's HEAD ($master), nothing is deployed"
        emit none ""
        exit 0
    fi

    stable=$(field README.txt '^[Ss]table [Tt]ag:')
    header_stable=$(field open-source-event-calendar.php '^[[:space:]]*\*[[:space:]]*[Ss]table [Tt]ag:')
    [[ "$stable" == "$header_stable" ]] \
        || fail "README.txt Stable Tag is '$stable', the plugin header says '$header_stable'"
    [[ "$stable" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || fail "Stable Tag '$stable' is not X.Y.Z"

    tags=$(svn_tags)
    latest=$(tail -n1 <<< "$tags")
    if [[ "$stable" == "$latest" ]]; then
        emit dev ""
        exit 0
    fi
    is_newer "$latest" "$stable" && fail "Stable Tag $stable is older than the latest release $latest"

    git fetch --quiet --force --tags origin
    tag_commit=$(git rev-parse --quiet --verify "refs/tags/$stable^{commit}" || true)
    [[ -n "$tag_commit" ]] \
        || fail "Stable Tag $stable on master without a release tag. Push the tag $stable on this commit, or revert the bump"
    [[ "$tag_commit" == "$sha" ]] \
        || fail "Stable Tag $stable on master, but the tag $stable is on $tag_commit, not on this commit." \
            "A push during a release? Rerun once tags/$stable exists"
    note "release $stable is deployed by its tag pipeline, waiting for tags/$stable"
    wait_for_release "$stable"
    emit none ""
    exit 0
fi

if [[ ! "$CIRCLE_TAG" =~ ^([0-9]+\.[0-9]+\.[0-9]+)(-dryrun)?$ ]]; then
    fail "tag '$CIRCLE_TAG' is neither X.Y.Z nor X.Y.Z-dryrun"
fi
version=${BASH_REMATCH[1]}
mode=tagged
[[ -n "${BASH_REMATCH[2]}" ]] && mode=dryrun

if [[ "$mode" != "dryrun" ]]; then
    git merge-base --is-ancestor "$sha" "$master" \
        || fail "tagged commit $sha is not on master"
fi

declare -A found=(
    ["plugin header Version"]=$(field open-source-event-calendar.php '^[[:space:]]*\*[[:space:]]*Version:')
    ["plugin header Stable Tag"]=$(field open-source-event-calendar.php '^[[:space:]]*\*[[:space:]]*[Ss]table [Tt]ag:')
    ["README.txt Stable Tag"]=$(field README.txt '^[Ss]table [Tt]ag:')
    ["constants.php OSEC_VERSION"]=$(sed -nE "s/.*define\([[:space:]]*'OSEC_VERSION'[[:space:]]*,[[:space:]]*'([^']+)'.*/\1/p" constants.php | head -n1)
)
mismatch=0
for where in "${!found[@]}"; do
    if [[ "${found[$where]}" != "$version" ]]; then
        note "$where is '${found[$where]}', the tag says $version"
        mismatch=1
    fi
done
[[ $mismatch -eq 0 ]] || fail "version metadata does not match tag $CIRCLE_TAG"

git fetch --quiet --force --tags origin
highest=$(git tag -l | grep -E '^[0-9]+\.[0-9]+\.[0-9]+$' | grep -vxF "$version" | sort -V | tail -n1 || true)
if [[ -n "$highest" ]] && ! is_newer "$version" "$highest"; then
    fail "tag $version is not higher than the existing release $highest"
fi

tags=$(svn_tags)
if grep -qxF "$version" <<< "$tags"; then
    note "tags/$version already exists on WordPress.org: already released, nothing is deployed"
    emit none ""
    exit 0
fi
latest=$(tail -n1 <<< "$tags")
is_newer "$version" "$latest" || fail "tag $version is not higher than the latest release $latest on WordPress.org"

emit "$mode" "$version"
