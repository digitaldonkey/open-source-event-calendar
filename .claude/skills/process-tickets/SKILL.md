---
name: process-tickets
description: Processes all open GitHub tickets (issues and pull requests) of open-source-event-calendar - fetches each one, evaluates it against the current code, summarizes it, tries to reproduce it, and saves a per-ticket plan to .claude/plans/ named after the ticket ID and GitHub title. Use when the user asks to triage, process, evaluate or plan open GitHub issues/tickets. Accepts optional ticket numbers and --refresh.
---

# Process open tickets

Turns every open GitHub ticket into an evaluated, reproduced (where possible)
plan file. Output is plan files only. Never comment on GitHub, change labels,
commit, merge PRs or edit `src/` - reproduction code goes in the scratchpad.

## Arguments

- none: all open tickets
- `<number> ...`: only these tickets
- `--refresh`: overwrite existing plans (default: skip and report as skipped)

## Plan file naming

`.claude/plans/<number>-<slug>.md`; H1 is `# #<number>: <GitHub title>`
(verbatim). List tickets with their target filenames:

```
SLUG='\(.number)-\(.title | ascii_downcase | gsub("[^a-z0-9]+"; "-") | ltrimstr("-") | rtrimstr("-") | .[0:60]).md'
gh issue list --state open --limit 200 --json number,title --jq ".[] | \"$SLUG\""
gh pr list    --state open --limit 200 --json number,title --jq ".[] | \"$SLUG\""
```

Check for an existing plan with `ls .claude/plans/<number>-*.md`; if the GitHub
title changed since, rename the file to the new slug.

## Steps

1. **Check `gh`**: `gh auth status`. If not logged in, ask the user to run
   `! gh auth login` (gh needs auth even for this public repo).

2. **Read every ticket before planning any**, to catch cross-references (PRs
   fixing issues, duplicates, dependencies). Every plan names its related tickets.
   The `--json` forms give compact, parseable output (and also work around the
   `Projects (classic)` error of gh < 2.50).
   ```
   gh issue view <n> --json number,title,url,author,createdAt,labels,body,comments \
     --jq '"# #\(.number): \(.title)\n\(.url) by \(.author.login)\n\n\(.body)\n" + ([.comments[] | "--- \(.author.login) (\(.createdAt))\n\(.body)"] | join("\n"))'
   gh pr view <n> --json number,title,url,author,body,comments,reviews,headRefName,baseRefName,mergeable,additions,deletions,files
   gh pr diff <n>
   gh pr checks <n>
   ```
   Screenshots in bodies (`github.com/user-attachments/...`) can be downloaded
   with `curl -sL` into the scratchpad and viewed with Read - they often carry
   key context (e.g. which page builder a site uses).

3. **For each ticket, ascending:**

   a. **Classify**: bug / feature / task / pull request / question. Keep plans
      for non-engineering tasks short - don't invent work.

   b. **Evaluate against upstream `master`**, not the local branch. SSH to
      `origin` is usually unavailable inside DDEV, so fetch over HTTPS:
      `git fetch "$(gh repo view --json url -q .url)" +master:refs/remotes/gh/master`,
      then read with `git show gh/master:<path>`. Locate code (PhpStorm MCP if
      connected, otherwise grep), check history (`git log --oneline --all --grep '#<n>'`).
      Decide: still valid / already fixed / needs info / won't fix candidate.

   c. **Reproduce if possible**, cheapest reliable method first, recording exact
      commands and observed vs. expected output:
      1. PHPUnit against the `phpunit` test DB (resetting it is pre-approved).
         Test files can live in the scratchpad: `vendor/bin/phpunit <scratchpad>/XTest.php`.
      2. `wp` CLI / `php` against the DDEV site - **read-only**; ask before
         creating content or changing options on the dev DB.
      3. `curl https://ddev-wordpress.ddev.site/...` or the browser for frontend issues.
      4. SQLite / WordPress Playground issues:
         `npx @wp-playground/cli php --mount <plugin>:/wordpress/wp-content/plugins/open-source-event-calendar --mount <dir>:/x -- /x/script.php`.
      5. PRs: use a scratch worktree - not `gh pr checkout`, which switches the
         main tree (it may hold uncommitted work):
         `git fetch "$(gh repo view --json url -q .url)" +pull/<n>/head:refs/remotes/gh/pr-<n>`
         then `git worktree add --detach <scratchpad>/pr-<n> gh/pr-<n>`.
         Symlink `vendor/` and prepend a PSR-4 override in a wrapper bootstrap
         (`$loader->setPsr4('Osec\\', [<worktree>/src])`), otherwise tests
         silently run the main tree's code. Run phpcs + relevant tests; compare
         against master; remove the worktree afterwards.
      If reproduction isn't feasible, say why and what's needed. Clean up
      temporary files and test data.

   d. **Write the plan** using the template below.

4. **Final report**: table of ticket / type / status / reproduced / priority,
   skipped tickets, and questions the user must answer.

## Plan template

```markdown
# #<number>: <GitHub title>

- **Ticket**: <url>
- **Type**: bug | feature | task | pull request | question
- **Status**: still valid | already fixed | needs info | won't fix candidate
- **Reproduced**: yes | no | not applicable | not attempted (reason)
- **Priority (suggested)**: high | medium | low
- **Effort (estimate)**: S | M | L
- **Related**: #.. (e.g. "PR #57 proposes a fix")
- **Processed**: <YYYY-MM-DD>

## Summary
<2-5 sentences: what the reporter experiences/wants, relevant discussion.>

## Evaluation
<Code locations as `path:line`, root cause or hypothesis, accuracy of the
report, relevant git history.>

## Reproduction
<Environment, exact commands/steps, observed vs. expected - or why not
reproducible and what is missing.>

## Plan
<Numbered steps with files to touch. For PRs: review findings and
merge/request-changes recommendation. Tests to add, and whether Twig -> JS
transform, README/hooks regeneration or a CHANGELOG entry is needed.>

## Risks & open questions
<Backward compatibility, DB schema, frontend rendering, questions for
reporter/maintainer. Flag high-risk changes per CLAUDE.md.>
```
