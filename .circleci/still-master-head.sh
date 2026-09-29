#!/bin/sh
#
# Checks, right before a deploy, that $CIRCLE_SHA1 is still master's HEAD on
# origin, so a slower pipeline of an older commit never rolls the SVN trunk or
# the GitHub "dev" release back.
#
# Exit 0: still HEAD. Exit 1: master has moved on. Exit 2: could not check.
# POSIX sh: also runs in the cibuilds/github image.

set -eu

sha=${CIRCLE_SHA1:?CIRCLE_SHA1 is not set}
if ! git fetch --quiet --force origin '+refs/heads/master:refs/remotes/origin/master'; then
    echo "still-master-head: could not fetch master" >&2
    exit 2
fi
head=$(git rev-parse refs/remotes/origin/master)
if [ "$head" = "$sha" ]; then
    exit 0
fi
echo "still-master-head: master has moved on to $head since this pipeline started" >&2
exit 1
