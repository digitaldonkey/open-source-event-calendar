#!/usr/bin/env bash
#
# Claude Code PreToolUse hook: runs GrumPHP's pre-commit checks before Claude
# commits from inside the DDEV container.
#
# The git hooks in .git/hooks call `ddev exec`, which is a stub inside the
# container, so they do nothing there. They belong to the maintainer and stay
# as they are. This runs the same command they run (`grumphp git:pre-commit`,
# testsuite git_pre_commit: composer, phpcs on the staged files, phpunit) with
# the same staged diff as input. Exit 2 blocks the commit and hands the output
# to Claude.

input=$(cat)
# First line only (a heredoc body is the message), without quoted strings.
command=$(printf '%s' "$input" | jq -r '.tool_input.command // empty' | head -n 1 \
    | sed -E "s/'[^']*'//g; s/\"[^\"]*\"//g")

# Only `git commit`, also after `cd ... &&` or with `git -C <dir>`.
if ! printf '%s' "$command" | grep -Eq '(^|[;&|[:space:]])git([[:space:]]+-C[[:space:]]+[^[:space:]]+)?[[:space:]]+commit([[:space:]]|$)'; then
    exit 0
fi

# Outside the container the maintainer's git hook does the job.
[ "$IS_DDEV_PROJECT" = "true" ] || exit 0

# The checks read the index, which a `git add` or `commit -a` in the same
# command has not updated yet.
if printf '%s' "$command" | grep -Eq '(^|[;&|[:space:]])git[[:space:]]+add([[:space:]]|$)|git[[:space:]]+commit[^;&|]*[[:space:]](--all|-[a-zA-Z]*a[a-zA-Z]*)([[:space:]]|$)'; then
    echo "Stage the files in a separate command first (git add), then run git commit without -a, so the pre-commit checks see what gets committed." >&2
    exit 2
fi

cd "$CLAUDE_PROJECT_DIR" || exit 0

output=$(git -c diff.mnemonicprefix=false -c diff.noprefix=false --no-pager diff --raw -r -p -m -M --full-index --no-color --staged \
    | GRUMPHP_GIT_WORKING_DIR="$(git rev-parse --show-toplevel)" vendor/bin/grumphp git:pre-commit --no-ansi --no-interaction 2>&1)
status=$?

if [ $status -ne 0 ]; then
    printf 'GrumPHP pre-commit checks failed (exit %s), commit blocked:\n%s\n' "$status" "$output" >&2
    exit 2
fi
exit 0
