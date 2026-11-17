#!/usr/bin/env bash
#
# Tests release-context.sh, still-master-head.sh and both deploy scripts against a local git
# origin, a local file:// SVN repository and a stub curl for the WordPress.org tags/ listing.
# Needs git, svn, svnadmin, zip.
#
#   bash .circleci/tests/test-release-scripts.sh

set -Eeuo pipefail

here=$(cd "$(dirname "$0")/.." && pwd)
context="$here/release-context.sh"
head_check="$here/still-master-head.sh"
deploy="$here/deploy-wp-plugin-release.sh"
deploy_assets="$here/deploy-wp-plugin-assets.sh"
T=$(mktemp -d)
trap 'rm -rf "$T"' EXIT
plugin=open-source-event-calendar
failures=0

pass() { echo "  ok   $*"; }
bad() { echo "  FAIL $*"; failures=$((failures + 1)); }
expect_eq() { if [[ "$2" == "$3" ]]; then pass "$1"; else bad "$1: expected '$3', got '$2'"; fi; }

export GIT_AUTHOR_NAME=t GIT_AUTHOR_EMAIL=t@t GIT_COMMITTER_NAME=t GIT_COMMITTER_EMAIL=t@t
# master's wait for tags/X.Y.Z: two reads, one second apart.
export RELEASE_WAIT_SECONDS=1 RELEASE_POLL_SECONDS=1

# Writes the version metadata of a plugin tree.
write_version() {
    local dir=$1 version=$2 stable=${3:-$2}
    mkdir -p "$dir"
    printf '<?php\n/**\n * Plugin Name: OSEC\n * Stable Tag: %s\n * Version: %s\n */\n' "$stable" "$version" > "$dir/$plugin.php"
    printf '=== OSEC ===\nStable Tag: %s\n' "$stable" > "$dir/README.txt"
    printf "<?php\n    if (! defined('OSEC_VERSION')) {\n        define('OSEC_VERSION', '%s');\n    }\n" "$version" > "$dir/constants.php"
}

# Stub curl: answers with the listing set by svn_tags (from the read set by svn_tags_later on, that
# one), or fails after svn_tags_fail (from the read set by svn_tags_fail_later on), and records the URL.
mkdir -p "$T/bin"
cat > "$T/bin/curl" <<STUB
#!/usr/bin/env bash
printf '%s\n' "\${@: -1}" > "$T/curl.url"
[[ ! -e "$T/curl.fail" ]] || exit 22
n=\$((\$(cat "$T/curl.count") + 1)) && echo "\$n" > "$T/curl.count"
[[ ! -e "$T/fail.at" || \$n -lt \$(cat "$T/fail.at") ]] || exit 22
if [[ -e "$T/listing.later" && \$n -ge \$(cat "$T/later.at") ]]; then cat "$T/listing.later"; else cat "$T/listing.html"; fi
STUB
chmod +x "$T/bin/curl"
# The listing as WordPress.org writes it, with the links that are not versions.
listing() {
    echo '<ul><li><a href="../">..</a></li>'
    for tag in "$@"; do echo "<li><a href=\"$tag/\">$tag/</a></li>"; done
    echo '</ul><a href="http://subversion.apache.org/">Subversion</a>'
}
svn_tags() {
    rm -f "$T/curl.fail" "$T/fail.at" "$T/listing.later" && echo 0 > "$T/curl.count"
    listing "$@" > "$T/listing.html"
}
# From read $1 on, the listing holds the other versions: a release that appears while master waits.
svn_tags_later() {
    local at=$1
    shift
    listing "$@" > "$T/listing.later" && echo "$at" > "$T/later.at"
}
svn_tags_fail() { touch "$T/curl.fail"; }
svn_tags_fail_later() { echo "$1" > "$T/fail.at"; }

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
    rm -rf "$T/ci" && git clone -q "${context_origin:-$T/origin.git}" "$T/ci" 2> /dev/null
    (cd "$T/ci" && git checkout -q "$commit" && env -u CIRCLE_TAG -u CIRCLE_BRANCH PATH="$T/bin:$PATH" WP_ORG_PLUGIN_NAME=$plugin \
        CIRCLE_SHA1="$commit" "$@" "$context" 2> "$T/ctx.err")
}

echo "release-context.sh"
# Expects a failed release-context.sh run whose message contains $2: name, reason, commit, env...
expect_fail() {
    local name=$1 reason=$2
    shift 2
    if run_context "$@" > /dev/null; then bad "$name: accepted"; else
        grep -q "$reason" "$T/ctx.err" && pass "$name" || bad "$name: wrong reason: $(cat "$T/ctx.err")"
    fi
}

svn_tags 1.1.14 1.2.0
expect_eq "master push, Stable Tag equals latest -> trunk" "$(run_context "$c2" CIRCLE_BRANCH=master | tr '\n' ' ')" \
    "RELEASE_MODE=dev RELEASE_VERSION= "
expect_eq "tags/ listing URL" "$(cat "$T/curl.url")" "https://plugins.svn.wordpress.org/$plugin/tags/"
expect_eq "master pipeline of an older commit -> none" "$(run_context "$c1" CIRCLE_BRANCH=master | head -1)" "RELEASE_MODE=none"
expect_eq "feature branch -> none" "$(run_context "$f1" CIRCLE_BRANCH=feature | head -1)" "RELEASE_MODE=none"
expect_eq "release branch with a bump -> none" "$(run_context "$f1" CIRCLE_BRANCH=release-1.3.x | head -1)" "RELEASE_MODE=none"

svn_tags 1.1.14
expect_eq "tag 1.2.0 on HEAD -> tagged" "$(run_context "$c2" CIRCLE_TAG=1.2.0 | tr '\n' ' ')" \
    "RELEASE_MODE=tagged RELEASE_VERSION=1.2.0 "
expect_eq "tag 1.2.0-dryrun -> dryrun" "$(run_context "$c2" CIRCLE_TAG=1.2.0-dryrun | head -2 | tr '\n' ' ')" \
    "RELEASE_MODE=dryrun RELEASE_VERSION=1.2.0 "
expect_eq "tag 1.3.0-dryrun on a commit off master -> dryrun" "$(run_context "$f1" CIRCLE_TAG=1.3.0-dryrun | head -1)" "RELEASE_MODE=dryrun"

for bad_case in "v1.2.0" "1.2" "1.2.0-rc1" "1.2.0.1"; do
    if run_context "$c2" CIRCLE_TAG="$bad_case" > /dev/null; then bad "tag $bad_case accepted"; else pass "tag $bad_case rejected"; fi
done
expect_fail "tag 1.3.0 on a 1.2.0 commit rejected" "does not match" "$c2" CIRCLE_TAG=1.3.0
expect_fail "tag on a commit off master rejected" "not on master" "$f1" CIRCLE_TAG=1.3.0
expect_fail "tag 1.0.0 below 1.1.14 rejected" "not higher" "$c0" CIRCLE_TAG=1.0.0

svn_tags 1.1.14 1.2.0
expect_eq "tag whose tags/X.Y.Z exists -> none" "$(run_context "$c2" CIRCLE_TAG=1.2.0 | tr '\n' ' ')" \
    "RELEASE_MODE=none RELEASE_VERSION= "
grep -q "already released" "$T/ctx.err" && pass "tag whose tags/X.Y.Z exists: says so" || bad "already released: no notice: $(cat "$T/ctx.err")"
svn_tags 1.1.14 1.2.5
expect_fail "tag below the latest on WordPress.org rejected" "not higher" "$c2" CIRCLE_TAG=1.2.0
svn_tags_fail
expect_fail "tag: unreadable tags/ listing fails" "could not read" "$c2" CIRCLE_TAG=1.2.0

# A push to master while 1.2.0 is released (its SVN step failed): the push is red, a rerun of the
# tag pipeline still releases 1.2.0, then the push's rerun deploys.
cd "$T/dev" && echo more >> README.txt && git commit -qam c3 && git push -q origin master 2> /dev/null
c3=$(git -C "$T/dev" rev-parse HEAD)
git -C "$T/dev" tag 1.2.0 "$c2" && git -C "$T/dev" push -q origin 1.2.0 2> /dev/null
svn_tags 1.1.14
expect_fail "push during a release: master fails" "not on this commit" "$c3" CIRCLE_BRANCH=master
expect_eq "push during a release: tag rerun -> tagged" "$(run_context "$c2" CIRCLE_TAG=1.2.0 | tr '\n' ' ')" \
    "RELEASE_MODE=tagged RELEASE_VERSION=1.2.0 "
svn_tags 1.1.14 1.2.0
expect_eq "push during a release: master rerun after the release -> dev" "$(run_context "$c3" CIRCLE_BRANCH=master | head -1)" "RELEASE_MODE=dev"

# The Stable Tag rules on master, on their own origin. Pushes a commit with these values, prints it.
git init -q --bare "$T/rules.git"
git clone -q "$T/rules.git" "$T/rules" 2> /dev/null
master_commit() {
    (cd "$T/rules" && git checkout -q -B master && write_version . "$1" "$1" && git add -A && git commit -qm "stable $1" --allow-empty \
        && { [[ $# -lt 2 ]] || sed -i "s/^Stable Tag:.*/Stable Tag: $2/" README.txt; } \
        && { [[ $# -lt 3 ]] || sed -i "s/^ \* Stable Tag:.*/ * Stable Tag: $3/" "$plugin.php"; } \
        && git add -A && git commit -qm "values" --allow-empty && git push -qf origin master --tags 2> /dev/null && git rev-parse HEAD)
}
context_origin="$T/rules.git"
svn_tags 1.2.9 1.2.10
m=$(master_commit 1.2.10)
expect_eq "versions compare as numbers: 1.2.10 is latest" "$(run_context "$m" CIRCLE_BRANCH=master | tr '\n' ' ')" \
    "RELEASE_MODE=dev RELEASE_VERSION= "
m=$(master_commit 1.2.9)
expect_fail "Stable Tag older than latest fails" "older than the latest" "$m" CIRCLE_BRANCH=master
m=$(master_commit 1.2.11)
expect_fail "new Stable Tag without a git tag fails" "without a release tag" "$m" CIRCLE_BRANCH=master
git -C "$T/rules" tag 1.2.11 HEAD~1 && git -C "$T/rules" push -q origin --tags 2> /dev/null
expect_fail "new Stable Tag, git tag on an ancestor fails" "not on this commit" "$m" CIRCLE_BRANCH=master
git -C "$T/rules" tag -f 1.2.11 HEAD > /dev/null && git -C "$T/rules" push -qf origin --tags 2> /dev/null
svn_tags 1.2.9 1.2.10 1.2.11
expect_eq "tag pipeline first: tags/1.2.11 exists -> dev" "$(run_context "$m" CIRCLE_BRANCH=master | head -1)" "RELEASE_MODE=dev"
svn_tags 1.2.9 1.2.10
# Read 1 is the decision's own, read 2 the first one of the wait.
svn_tags_later 3 1.2.9 1.2.10 1.2.11
expect_eq "master first: waits until tags/1.2.11 appears -> none" "$(run_context "$m" CIRCLE_BRANCH=master | tr '\n' ' ')" \
    "RELEASE_MODE=none RELEASE_VERSION= "
grep -q "not on WordPress.org yet" "$T/ctx.err" && pass "master first: says it waits" || bad "master first: no wait notice: $(cat "$T/ctx.err")"
svn_tags 1.2.9 1.2.10
expect_fail "master first: tags/1.2.11 never appears fails" "did not appear" "$m" CIRCLE_BRANCH=master
svn_tags 1.2.9 1.2.10
svn_tags_fail_later 2
expect_fail "master first: unreadable listing while waiting fails at the timeout" "did not appear" "$m" CIRCLE_BRANCH=master
grep -q "could not read" "$T/ctx.err" && pass "master first: unreadable listing is reported while waiting" \
    || bad "master first: unreadable listing not reported: $(cat "$T/ctx.err")"
svn_tags 1.2.9 1.2.10
expect_fail "wait: bad RELEASE_WAIT_SECONDS fails" "whole seconds" "$m" CIRCLE_BRANCH=master RELEASE_WAIT_SECONDS=1h
git -C "$T/rules" checkout -q --orphan other && git -C "$T/rules" commit -qm other --allow-empty
git -C "$T/rules" tag -f 1.2.12 && git -C "$T/rules" push -qf origin --tags 2> /dev/null && git -C "$T/rules" checkout -q master
svn_tags 1.2.9 1.2.10
m=$(master_commit 1.2.12)
expect_fail "new Stable Tag, git tag not on this history fails" "not on this commit" "$m" CIRCLE_BRANCH=master
m=$(master_commit 1.2.13 1.2.10)
expect_fail "README.txt and header differ fails" "plugin header says" "$m" CIRCLE_BRANCH=master
m=$(master_commit 1.2.13 "" "")
expect_fail "empty Stable Tag fails" "is not X.Y.Z" "$m" CIRCLE_BRANCH=master
m=$(master_commit 1.2.13 trunk trunk)
expect_fail "Stable Tag trunk fails" "is not X.Y.Z" "$m" CIRCLE_BRANCH=master
m=$(master_commit 1.2.10)
svn_tags_fail
expect_fail "unreadable tags/ listing fails" "could not read" "$m" CIRCLE_BRANCH=master
echo '<html><body>Service unavailable</body></html>' > "$T/listing.html"; rm -f "$T/curl.fail"
expect_fail "listing without versions fails" "no versions found" "$m" CIRCLE_BRANCH=master
unset context_origin
cd "$T/dev"

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

# Deploys with a release.env of: mode version zip [commit, default master's HEAD c3]
run_deploy() {
    printf 'RELEASE_MODE=%s\nRELEASE_VERSION=%s\n' "$1" "$2" > "$T/release.env"
    (cd "$T/dev" && RELEASE_ENV="$T/release.env" RELEASE_ZIP="$T/$3.zip" WP_ORG_SVN_URL="$url" DEPLOY_WORK_DIR="$T/work" \
        WP_ORG_PLUGIN_NAME=$plugin WP_ORG_USERNAME=u WP_ORG_SVN_PASSWORD=p CIRCLE_SHA1=${4:-$c3} PATH="${deploy_path:-$PATH}" \
        "$deploy" > "$T/deploy.out" 2>&1)
}
# svn that runs the real one, but drops "Committed revision" from a commit's output.
mkdir -p "$T/svnbin"
cat > "$T/svnbin/svn" <<STUB
#!/usr/bin/env bash
PATH=${PATH@Q}
if [[ "\$1" == commit ]]; then svn "\$@" | grep -v '^Committed revision'; exit "\${PIPESTATUS[0]}"; fi
exec svn "\$@"
STUB
chmod +x "$T/svnbin/svn"
youngest() { svnlook youngest "$T/svnrepo"; }
same_as_zip() { # svn path, version
    rm -rf "$T/exp" && svn export -q "$url/$1" "$T/exp" && diff -r "$T/exp" "$T/zip-$2/$plugin" > /dev/null
}

r=$(youngest)
run_deploy none "" 1.2.0 && expect_eq "none commits nothing" "$(youngest)" "$r"

run_deploy dryrun 1.2.0 1.2.0 || bad "dryrun failed: $(cat "$T/deploy.out")"
expect_eq "dryrun commits nothing" "$(youngest)" "$r"

r=$(youngest)
run_deploy dev "" 1.3.0 "$c2" || bad "dev of an overtaken commit failed: $(cat "$T/deploy.out")"
expect_eq "dev: master moved on during the pipeline -> nothing committed" "$(youngest)" "$r"

run_deploy dev "" 1.2.0 || bad "dev failed: $(cat "$T/deploy.out")"
same_as_zip trunk 1.2.0 && pass "dev: trunk equals the zip (add, delete, space, ignored file)" || bad "dev: trunk differs from zip"
svn ls "$url/tags/1.2.0" > /dev/null 2>&1 && bad "dev created a tag" || pass "dev creates no tag"

# A rejected SVN commit fails and leaves nothing; the rerun releases.
r=$(youngest)
printf '#!/bin/sh\nexit 1\n' > "$T/svnrepo/hooks/pre-commit" && chmod +x "$T/svnrepo/hooks/pre-commit"
if run_deploy tagged 1.2.0 1.2.0; then bad "tagged: rejected commit accepted"; else pass "tagged: rejected commit fails"; fi
expect_eq "tagged: rejected commit leaves SVN as it was" "$(youngest)" "$r"
rm "$T/svnrepo/hooks/pre-commit"
run_deploy tagged 1.2.0 1.2.0 || bad "tagged failed: $(cat "$T/deploy.out")"
expect_eq "tagged: one commit" "$(youngest)" "$((r + 1))"
same_as_zip tags/1.2.0 1.2.0 && pass "tagged: tags/1.2.0 equals the zip" || bad "tagged: tag differs from zip"
svnlook changed --copy-info -r "$(youngest)" "$T/svnrepo" | grep -A1 'tags/1.2.0/$' | grep -q '(from trunk/' && pass "tagged: tag is a copy of trunk" \
    || bad "tagged: tag not copied from trunk: $(svnlook changed -r "$(youngest)" "$T/svnrepo" | head -3)"

r=$(youngest)
run_deploy tagged 1.2.0 1.2.0 || bad "re-run failed: $(cat "$T/deploy.out")"
expect_eq "existing tag is left untouched" "$(youngest)" "$r"

run_deploy tagged 1.3.0 1.3.0 "$c2" || bad "tagged after master moved on failed: $(cat "$T/deploy.out")"
same_as_zip tags/1.3.0 1.3.0 && pass "tagged, master moved on during the pipeline: tags/1.3.0 equals the zip" || bad "tagged, master moved on: tag differs from zip"
same_as_zip trunk 1.3.0 && pass "tagged, master moved on during the pipeline: trunk replaced" || bad "tagged, master moved on: trunk not replaced"
same_as_zip tags/1.1.14 1.2.0 && bad "old tag modified" || pass "old tags untouched"

r=$(youngest)
git -C "$T/dev" remote set-url origin "$T/missing.git"
if run_deploy dev "" 1.4.0; then bad "dev deployed although master's HEAD could not be checked"; else pass "dev fails when master's HEAD cannot be checked"; fi
git -C "$T/dev" remote set-url origin "$T/origin.git"
expect_eq "failed HEAD check commits nothing" "$(youngest)" "$r"

r=$(youngest)
if deploy_path="$T/svnbin:$PATH" run_deploy tagged 1.4.0 1.4.0; then bad "tagged: commit without a revision accepted"; else
    grep -q "reported no revision" "$T/deploy.out" && pass "tagged: commit without a revision fails" || bad "no revision: wrong reason: $(cat "$T/deploy.out")"
fi
expect_eq "tagged: commit without a revision went through" "$(youngest)" "$((r + 1))"
r=$(youngest)
run_deploy tagged 1.4.0 1.4.0 || bad "rerun after a commit without a revision failed: $(cat "$T/deploy.out")"
expect_eq "rerun after a commit without a revision: tag exists, nothing committed" "$(youngest)" "$r"

echo "deploy-wp-plugin-assets.sh"
run_assets() { # runs from a directory holding ./assets
    (cd "$T/assets-src" && CIRCLECI=true CIRCLE_BRANCH=master WP_ORG_SVN_URL="$url" \
        WP_ORG_PLUGIN_NAME=$plugin WP_ORG_USERNAME=u WP_ORG_SVN_PASSWORD=p "$deploy_assets" > "$T/assets.out" 2>&1)
}
mkdir -p "$T/assets-src/assets" && echo banner > "$T/assets-src/assets/banner.png" && echo icon > "$T/assets-src/assets/icon.png"
r=$(youngest)
run_assets || bad "assets failed: $(cat "$T/assets.out")"
expect_eq "assets: one commit" "$(youngest)" "$((r + 1))"
rm -rf "$T/exp" && svn export -q "$url/assets" "$T/exp" && diff -r "$T/exp" "$T/assets-src/assets" > /dev/null \
    && pass "assets: SVN assets equals ./assets" || bad "assets: SVN assets differs"
r=$(youngest)
run_assets || bad "assets re-run failed: $(cat "$T/assets.out")"
expect_eq "assets unchanged: nothing committed" "$(youngest)" "$r"
grep -q "no changes" "$T/assets.out" && pass "assets unchanged: says so" || bad "assets unchanged: no notice: $(cat "$T/assets.out")"
rm "$T/assets-src/assets/icon.png"
run_assets || bad "assets delete failed: $(cat "$T/assets.out")"
svn ls "$url/assets/icon.png" > /dev/null 2>&1 && bad "assets: removed file still in SVN" || pass "assets: removed file deleted in SVN"
if (cd "$T/assets-src" && CIRCLECI=true CIRCLE_BRANCH=feature WP_ORG_SVN_URL="$url" WP_ORG_PLUGIN_NAME=$plugin \
    WP_ORG_USERNAME=u WP_ORG_SVN_PASSWORD=p "$deploy_assets" > /dev/null 2>&1); then bad "assets deployed from a feature branch"; else pass "assets refused outside master"; fi

echo
if [[ $failures -gt 0 ]]; then
    echo "$failures failure(s)"
    exit 1
fi
echo "all passed"
