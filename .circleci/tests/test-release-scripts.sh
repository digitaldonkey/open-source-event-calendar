#!/usr/bin/env bash
#
# Tests release-context.sh and deploy-wp-plugin-release.sh against a local git
# origin and a local file:// SVN repository. Needs git, svn, svnadmin, zip.
#
#   bash .circleci/tests/test-release-scripts.sh

set -Eeuo pipefail

here=$(cd "$(dirname "$0")/.." && pwd)
context="$here/release-context.sh"
head_check="$here/still-master-head.sh"
deploy="$here/deploy-wp-plugin-release.sh"
T=$(mktemp -d)
trap 'rm -rf "$T"' EXIT
plugin=open-source-event-calendar
failures=0

pass() { echo "  ok   $*"; }
bad() { echo "  FAIL $*"; failures=$((failures + 1)); }
expect_eq() { if [[ "$2" == "$3" ]]; then pass "$1"; else bad "$1: expected '$3', got '$2'"; fi; }

export GIT_AUTHOR_NAME=t GIT_AUTHOR_EMAIL=t@t GIT_COMMITTER_NAME=t GIT_COMMITTER_EMAIL=t@t

# Writes the version metadata of a plugin tree.
write_version() {
    local dir=$1 version=$2 stable=${3:-$2}
    mkdir -p "$dir"
    printf '<?php\n/**\n * Plugin Name: OSEC\n * Stable Tag: %s\n * Version: %s\n */\n' "$stable" "$version" > "$dir/$plugin.php"
    printf '=== OSEC ===\nStable Tag: %s\n' "$stable" > "$dir/README.txt"
    printf "<?php\n    if (! defined('OSEC_VERSION')) {\n        define('OSEC_VERSION', '%s');\n    }\n" "$version" > "$dir/constants.php"
}

# --- git fixture -------------------------------------------------------------
git init -q --bare "$T/origin.git"
git clone -q "$T/origin.git" "$T/dev" 2> /dev/null
cd "$T/dev"
git checkout -q -b master
write_version . 1.0.0 && git add -A && git commit -qm c0 && c0=$(git rev-parse HEAD)
write_version . 1.1.14 && git commit -qam c1 && git tag 1.1.14 && c1=$(git rev-parse HEAD)
write_version . 1.2.0 && git commit -qam c2 && c2=$(git rev-parse HEAD)
git push -q origin master --tags 2> /dev/null
git checkout -q -b feature && write_version . 1.3.0 && git commit -qam f1 && f1=$(git rev-parse HEAD)
git push -q origin feature 2> /dev/null
git checkout -q master

# Runs release-context.sh in a fresh checkout of $1, with the given env.
run_context() {
    local commit=$1
    shift
    rm -rf "$T/ci" && git clone -q "$T/origin.git" "$T/ci" 2> /dev/null
    (cd "$T/ci" && git checkout -q "$commit" && env -u CIRCLE_TAG -u CIRCLE_BRANCH CIRCLE_SHA1="$commit" "$@" "$context" 2> "$T/ctx.err")
}

echo "release-context.sh"
expect_eq "master push at HEAD -> dev" "$(run_context "$c2" CIRCLE_BRANCH=master | tr '\n' ' ')" \
    "RELEASE_MODE=dev RELEASE_VERSION= RELEASE_UPDATE_TRUNK=true "
expect_eq "master pipeline of an older commit -> none" "$(run_context "$c1" CIRCLE_BRANCH=master | head -1)" "RELEASE_MODE=none"
expect_eq "feature branch -> none" "$(run_context "$f1" CIRCLE_BRANCH=feature | head -1)" "RELEASE_MODE=none"
expect_eq "tag 1.2.0 on HEAD -> tagged, trunk" "$(run_context "$c2" CIRCLE_TAG=1.2.0 | tr '\n' ' ')" \
    "RELEASE_MODE=tagged RELEASE_VERSION=1.2.0 RELEASE_UPDATE_TRUNK=true "
expect_eq "tag 1.2.0-dryrun -> dryrun" "$(run_context "$c2" CIRCLE_TAG=1.2.0-dryrun | head -2 | tr '\n' ' ')" \
    "RELEASE_MODE=dryrun RELEASE_VERSION=1.2.0 "

for bad_case in "v1.2.0" "1.2" "1.2.0-rc1" "1.2.0.1"; do
    if run_context "$c2" CIRCLE_TAG="$bad_case" > /dev/null; then bad "tag $bad_case accepted"; else pass "tag $bad_case rejected"; fi
done
if run_context "$c2" CIRCLE_TAG=1.3.0 > /dev/null; then bad "metadata mismatch accepted"; else
    grep -q "does not match" "$T/ctx.err" && pass "tag 1.3.0 on a 1.2.0 commit rejected" || bad "mismatch: wrong reason: $(cat "$T/ctx.err")"
fi
if run_context "$f1" CIRCLE_TAG=1.3.0 > /dev/null; then bad "commit off master accepted"; else
    grep -q "not on master" "$T/ctx.err" && pass "tag on a commit off master rejected" || bad "off master: wrong reason: $(cat "$T/ctx.err")"
fi
if run_context "$c0" CIRCLE_TAG=1.0.0 > /dev/null; then bad "lower version accepted"; else
    grep -q "not higher" "$T/ctx.err" && pass "tag 1.0.0 below 1.1.14 rejected" || bad "lower: wrong reason: $(cat "$T/ctx.err")"
fi
expect_eq "re-run of existing tag 1.1.14 is not 'lower' than itself" "$(cd "$T/dev" && git checkout -q "$c1" && git checkout -q master; run_context "$c1" CIRCLE_TAG=1.1.14 | head -1)" "RELEASE_MODE=tagged"

# master moves on: tagging the release commit afterwards updates only the tag
cd "$T/dev" && echo more >> README.txt && git commit -qam c3 && git push -q origin master 2> /dev/null
expect_eq "tag 1.2.0 after master moved on -> tagged, no trunk" "$(run_context "$c2" CIRCLE_TAG=1.2.0 | tr '\n' ' ')" \
    "RELEASE_MODE=tagged RELEASE_VERSION=1.2.0 RELEASE_UPDATE_TRUNK=false "

c3=$(git -C "$T/dev" rev-parse HEAD)

echo "still-master-head.sh"
head_rc() { (cd "$T/dev" && CIRCLE_SHA1=$1 "$head_check" 2> /dev/null) && echo 0 || echo $?; }
expect_eq "HEAD -> 0" "$(head_rc "$c3")" 0
expect_eq "older commit -> 1" "$(head_rc "$c2")" 1
expect_eq "unreachable origin -> 2" "$( (cd "$T/dev" && git remote set-url origin "$T/missing.git" && CIRCLE_SHA1=$c3 "$head_check" 2> /dev/null; echo $?; git remote set-url origin "$T/origin.git") | tail -1)" 2

# --- svn fixture ---------------------------------------------------------------
echo "deploy-wp-plugin-release.sh"
svnadmin create "$T/svnrepo"
url="file://$T/svnrepo"
mkdir -p "$T/layout/trunk" "$T/layout/tags" "$T/layout/assets"
write_version "$T/layout/trunk" 1.1.14
mkdir -p "$T/layout/trunk/src/Old" && echo old > "$T/layout/trunk/src/Old/Gone.php" && echo keep > "$T/layout/trunk/src/Keep.php"
svn import -q "$T/layout" "$url" -m layout
svn copy -q "$url/trunk" "$url/tags/1.1.14" -m "tag 1.1.14"

# Builds a release zip for version $1 (+ a file with a space and one svn would ignore).
make_zip() {
    local version=$1 dir="$T/zip-$1/$plugin"
    rm -rf "$T/zip-$1" && write_version "$dir" "$version"
    mkdir -p "$dir/src" "$dir/vendor/x" && echo keep > "$dir/src/Keep.php" && echo "v$version" > "$dir/src/New $version.php"
    echo binary > "$dir/vendor/x/lib.so"
    (cd "$T/zip-$1" && zip -qr "$T/$version.zip" "$plugin")
}
make_zip 1.2.0
make_zip 1.3.0
make_zip 1.4.0

# Deploys with a release.env of: mode version update_trunk zip [commit, default master's HEAD c3]
run_deploy() {
    printf 'RELEASE_MODE=%s\nRELEASE_VERSION=%s\nRELEASE_UPDATE_TRUNK=%s\n' "$1" "$2" "$3" > "$T/release.env"
    (cd "$T/dev" && RELEASE_ENV="$T/release.env" RELEASE_ZIP="$T/$4.zip" WP_ORG_SVN_URL="$url" DEPLOY_WORK_DIR="$T/work" \
        WP_ORG_PLUGIN_NAME=$plugin WP_ORG_USERNAME=u WP_ORG_SVN_PASSWORD=p CIRCLE_SHA1=${5:-$c3} \
        "$deploy" > "$T/deploy.out" 2>&1)
}
youngest() { svnlook youngest "$T/svnrepo"; }
same_as_zip() { # svn path, version
    rm -rf "$T/exp" && svn export -q "$url/$1" "$T/exp" && diff -r "$T/exp" "$T/zip-$2/$plugin" > /dev/null
}

r=$(youngest)
run_deploy none "" false 1.2.0 && expect_eq "none commits nothing" "$(youngest)" "$r"

run_deploy dryrun 1.2.0 true 1.2.0 || bad "dryrun failed: $(cat "$T/deploy.out")"
expect_eq "dryrun commits nothing" "$(youngest)" "$r"

r=$(youngest)
run_deploy dev "" true 1.3.0 "$c2" || bad "dev of an overtaken commit failed: $(cat "$T/deploy.out")"
expect_eq "dev: master moved on during the pipeline -> nothing committed" "$(youngest)" "$r"

run_deploy dev "" true 1.2.0 || bad "dev failed: $(cat "$T/deploy.out")"
same_as_zip trunk 1.2.0 && pass "dev: trunk equals the zip (add, delete, space, ignored file)" || bad "dev: trunk differs from zip"
svn ls "$url/tags/1.2.0" > /dev/null 2>&1 && bad "dev created a tag" || pass "dev creates no tag"

r=$(youngest)
run_deploy tagged 1.2.0 true 1.2.0 || bad "tagged failed: $(cat "$T/deploy.out")"
expect_eq "tagged: one commit" "$(youngest)" "$((r + 1))"
same_as_zip tags/1.2.0 1.2.0 && pass "tagged: tags/1.2.0 equals the zip" || bad "tagged: tag differs from zip"
svnlook changed --copy-info -r "$(youngest)" "$T/svnrepo" | grep -A1 'tags/1.2.0/$' | grep -q '(from trunk/' && pass "tagged: tag is a copy of trunk" \
    || bad "tagged: tag not copied from trunk: $(svnlook changed -r "$(youngest)" "$T/svnrepo" | head -3)"

r=$(youngest)
run_deploy tagged 1.2.0 true 1.2.0 || bad "re-run failed: $(cat "$T/deploy.out")"
expect_eq "existing tag is left untouched" "$(youngest)" "$r"

run_deploy tagged 1.3.0 true 1.3.0 "$c2" || bad "tagged after master moved on failed: $(cat "$T/deploy.out")"
same_as_zip tags/1.3.0 1.3.0 && pass "tagged, master moved on during the pipeline: tags/1.3.0 equals the zip" || bad "tagged, master moved on: tag differs from zip"
same_as_zip trunk 1.2.0 && pass "tagged, master moved on during the pipeline: trunk unchanged" || bad "tagged, master moved on: trunk was modified"
same_as_zip tags/1.1.14 1.2.0 && bad "old tag modified" || pass "old tags untouched"

run_deploy tagged 1.4.0 false 1.4.0 || bad "tagged, no trunk failed: $(cat "$T/deploy.out")"
same_as_zip tags/1.4.0 1.4.0 && pass "tagged, RELEASE_UPDATE_TRUNK=false: tags/1.4.0 equals the zip" || bad "tagged, no trunk: tag differs from zip"
same_as_zip trunk 1.2.0 && pass "tagged, RELEASE_UPDATE_TRUNK=false: trunk unchanged" || bad "tagged, no trunk: trunk was modified"

r=$(youngest)
git -C "$T/dev" remote set-url origin "$T/missing.git"
if run_deploy dev "" true 1.4.0; then bad "dev deployed although master's HEAD could not be checked"; else pass "dev fails when master's HEAD cannot be checked"; fi
git -C "$T/dev" remote set-url origin "$T/origin.git"
expect_eq "failed HEAD check commits nothing" "$(youngest)" "$r"

echo
if [[ $failures -gt 0 ]]; then
    echo "$failures failure(s)"
    exit 1
fi
echo "all passed"
