#!/usr/bin/env bash
#
# Decides what a pipeline releases and prints it as shell assignments for the
# deploy jobs (the build job writes them to /tmp/release.env):
#
#   RELEASE_MODE          none | dev | tagged | dryrun
#   RELEASE_VERSION       X.Y.Z for tagged and dryrun, empty otherwise
#   RELEASE_UPDATE_TRUNK  true when the SVN trunk is to be replaced
#
# RELEASE_UPDATE_TRUNK is decided when the pipeline starts. The deploy checks
# again right before committing (still-master-head.sh) and leaves trunk alone
# if master has moved on meanwhile; release.env is not rewritten then, the
# deploy log says so.
#
# master push        -> dev (trunk + GitHub "dev"), unless master has moved on
# tag X.Y.Z          -> tagged (tags/X.Y.Z + GitHub release X.Y.Z, trunk too when
#                       the tagged commit is master's HEAD)
# tag X.Y.Z-dryrun   -> dryrun (the same, without committing or publishing)
# anything else      -> none
#
# A release tag that fails a check stops the pipeline (exit 1), before any test
# job runs. Messages go to stderr, the assignments to stdout.

set -Eeuo pipefail

note() { echo "release-context: $*" >&2; }
fail() { note "$*"; exit 1; }
emit() { printf 'RELEASE_MODE=%s\nRELEASE_VERSION=%s\nRELEASE_UPDATE_TRUNK=%s\n' "$1" "$2" "$3"; }

sha=${CIRCLE_SHA1:?CIRCLE_SHA1 is not set}

# The first word after the first match of a regex in a file.
field() { sed -nE "s/$2[[:space:]]*([^[:space:]]+).*/\1/p" "$1" | head -n1; }

git fetch --quiet --force origin '+refs/heads/master:refs/remotes/origin/master'
master=$(git rev-parse refs/remotes/origin/master)
at_head=false
[[ "$sha" == "$master" ]] && at_head=true

if [[ -z "${CIRCLE_TAG:-}" ]]; then
    if [[ "${CIRCLE_BRANCH:-}" != "master" ]]; then
        emit none "" false
        exit 0
    fi
    if ! $at_head; then
        # A rerun of an older master pipeline, or one overtaken by a newer push,
        # must not roll trunk or the "dev" release back.
        note "commit $sha is no longer master's HEAD ($master), nothing is deployed"
        emit none "" false
        exit 0
    fi
    emit dev "" true
    exit 0
fi

if [[ ! "$CIRCLE_TAG" =~ ^([0-9]+\.[0-9]+\.[0-9]+)(-dryrun)?$ ]]; then
    fail "tag '$CIRCLE_TAG' is neither X.Y.Z nor X.Y.Z-dryrun"
fi
version=${BASH_REMATCH[1]}
mode=tagged
[[ -n "${BASH_REMATCH[2]}" ]] && mode=dryrun

git merge-base --is-ancestor "$sha" "$master" \
    || fail "tagged commit $sha is not on master"

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
if [[ -n "$highest" ]] && [[ "$(printf '%s\n%s\n' "$highest" "$version" | sort -V | tail -n1)" != "$version" ]]; then
    fail "tag $version is not higher than the existing release $highest"
fi

$at_head || note "tagged commit is not master's HEAD ($master): only tags/$version is deployed, trunk stays"
emit "$mode" "$version" "$at_head"
