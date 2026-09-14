---
name: process-tickets
description: Processes all open GitHub tickets (issues and pull requests) of open-source-event-calendar - fetches each one, evaluates it against current upstream master, summarizes it, tries to reproduce it, and saves a per-ticket plan to .claude/plans/ named after the ticket ID and GitHub title. Existing plans are updated incrementally (ticket activity, master changes, closed tickets) and side findings are re-checked. Use when the user asks to triage, process, update, evaluate or plan open GitHub issues/tickets. Accepts optional ticket numbers and --refresh.
---

# Process open tickets

Turns every open GitHub ticket into an evaluated, reproduced (where possible)
plan file and keeps existing plans and side findings current with GitHub and
upstream `master`. Output is plan files only. Never comment on GitHub, change
labels, commit, merge PRs or edit `src/` - reproduction code goes in the
scratchpad.

## Arguments

- none: all open tickets, all existing ticket plans, all side findings
- `<number> ...`: only these tickets; side findings only where `Tracked in`
  names one of them
- `--refresh`: rewrite the selected plans unconditionally instead of
  updating incrementally

Existing plans are never skipped: by default each one is updated as described
in [Updating existing plans](#updating-existing-plans).

## Plan file naming

`.claude/plans/<number>-<slug>.md`; H1 is `# #<number>: <GitHub title>`
(verbatim). List tickets with their target filenames:

```
SLUG='\(.number)-\(.title | ascii_downcase | gsub("[^a-z0-9]+"; "-") | ltrimstr("-") | rtrimstr("-") | .[0:60]).md'
gh issue list --state open --limit 200 --json number,title --jq ".[] | \"$SLUG\""
gh pr list    --state open --limit 200 --json number,title --jq ".[] | \"$SLUG\""
```

Plans of closed tickets are named `.claude/plans/<number>-closed-<slug>.md`
(same slug, `closed-` inserted after the number); rename back if the ticket is
reopened.

Check for an existing plan with `ls .claude/plans/<number>-*.md` (matches both
forms); if the GitHub title changed since, rename the file to the new slug.
After any rename, update references to the old filename in other plans and in
side findings' `Tracked in`. Non-numbered files
(e.g. `prepare-release-tooling.md`) are not ticket plans; only
`side-findings-*.md` among them is processed. `process-tickets-report-*.md`
files are earlier run reports (see step 10); they are neither processed nor
edited by later runs.

## Output conventions

Apply to everything shown to the user (progress messages, `AskUserQuestion`
questions, the final response) and to the report file:

- **Ticket references** always carry the GitHub title in parentheses:
  `#59 (Agenda view shows entire homepage in expanded toggle)`,
  `PR #57 (Fix/54 shortcode term warning)`. Never a bare number.
- **Side-finding IDs** always carry a short title in parentheses, derived from
  the Finding column: `D1 (REST anonymous permissions)`. This also applies in
  plan files and in the side findings file's text (action items, `## Removed`);
  table rows that show the Finding column next to the ID don't repeat it.
- **Plan files**: whenever a plan is created, rewritten, header-updated, renamed
  or changed through an answer, name its filename (`.claude/plans/<file>.md`)
  in the output.
- **Priority order**: lists and tables of tickets, side findings and open
  questions are sorted by priority high → medium → low, then tickets before
  side findings, then ascending ticket number / ID. Removed side findings come
  last with priority `–`.
- **Maintainer priorities win**: a priority the maintainer set (recorded in a
  plan's `## Maintainer notes`, or in a side finding's Priority column as
  `<priority> (maintainer)`) is kept; don't re-suggest it unless its basis
  changed (see [Re-asking settled decisions](#re-asking-settled-decisions)).

## Steps

1. **Check `gh`**: `gh auth status`. If not logged in, ask the user to run
   `! gh auth login` (gh needs auth even for this public repo).

2. **Fetch upstream `master`** once. SSH to `origin` is usually unavailable
   inside DDEV, so fetch over HTTPS:
   ```
   git fetch "$(gh repo view --json url -q .url)" +master:refs/remotes/gh/master
   git rev-parse gh/master   # current master SHA, recorded in every plan written
   ```
   All evaluation reads `gh/master` (`git show gh/master:<path>`), never the
   local branch.

3. **Inventory**: open tickets (listing above) plus existing ticket plans
   (`ls .claude/plans/[0-9]*-*.md`). Each ticket is *new* (open, no plan),
   *existing* (plan present), possibly *closed* (plan present, not in the
   open list) or *already closed* (`<number>-closed-*` file; only check for
   reopening).

4. **Read every open ticket before planning any**, to catch cross-references
   (PRs fixing issues, duplicates, dependencies). Every plan names its related
   tickets. The `--json` forms give compact, parseable output (and also work
   around the `Projects (classic)` error of gh < 2.50).
   ```
   gh issue view <n> --json number,title,url,author,createdAt,updatedAt,labels,body,comments \
     --jq '"# #\(.number): \(.title)\n\(.url) by \(.author.login)\n\n\(.body)\n" + ([.comments[] | "--- \(.author.login) (\(.createdAt))\n\(.body)"] | join("\n"))'
   gh pr view <n> --json number,title,url,author,updatedAt,body,comments,reviews,headRefName,headRefOid,baseRefName,mergeable,additions,deletions,files
   gh pr diff <n>
   gh pr checks <n>
   ```
   For plans of tickets not in the open list, fetch state only:
   ```
   gh issue view <n> --json number,title,state,stateReason,closedAt,updatedAt
   gh pr view <n>    --json number,title,state,mergedAt,closedAt,updatedAt
   ```
   (`gh issue view` resolves PR numbers too but lacks PR fields; use
   `gh pr view` when the plan's Type is pull request.)
   Screenshots in bodies (`github.com/user-attachments/...`) can be downloaded
   with `curl -sL` into the scratchpad and viewed with Read - they often carry
   key context (e.g. which page builder a site uses).

5. **For each ticket, ascending:**
   - *new*, or any selected plan with `--refresh`: steps 6a-6d.
   - *existing* or *closed*: [Updating existing plans](#updating-existing-plans),
     which decides whether steps 6a-6d run.

6. **Evaluate and write a plan:**

   a. **Classify**: bug / feature / task / pull request / question. Keep plans
      for non-engineering tasks short - don't invent work.

   b. **Evaluate against `gh/master`**. Locate code (PhpStorm MCP if
      connected, otherwise grep), check history
      (`git log --oneline --all --grep '#<n>'`).
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

   d. **Write the plan** using the template below, restoring
      [preserved sections](#preserved-sections) when overwriting.

   Findings outside the ticket's scope go to the side findings file (see
   [Side findings](#side-findings)), not into the plan beyond a one-line
   reference.

7. **Check side findings** as described below.

Steps 8 and 9 run before the final report, so the report reflects the
answers. Start with a one-line overview (e.g. "3 re-opened decisions, 19 side
findings: 2 removed, 1 flagged, 1 new") so the user knows what is coming.

8. **Ask re-opened plan decisions** one question each via `AskUserQuestion`:
   ticket, what was decided, the assumption behind it, what changed (commit,
   comment, code location) and the options, recommended first. Record each
   answer as described in [Re-asking settled decisions](#re-asking-settled-decisions)
   and update the affected plan sections.

9. **Walk the user through the side findings**, in ID order, and apply each
   answer to the side findings file immediately:
   - For each finding show ID (with title), priority, finding, current
     decision, tracked in and the check result, then ask: confirm / change
     decision / remove (for removed rows: confirm removal / restore). New
     findings get a suggested priority the user can change.
   - Give flagged, new and removed findings their own question each; batch
     plainly still-valid rows with unchanged premises a few at a time.
   - Use `AskUserQuestion`, recommended option first.
   - Decisions given here are the maintainer's and may be written to the
     Decision column; restores move the row back from `## Removed`.
   - If a decision creates work in a plan (e.g. "part of #59"), update that
     plan's Plan section and History too.

10. **Final report**, built from the files after steps 8-9. Write it to
    `.claude/plans/process-tickets-report-<YYYY-MM-DD>.md` (a second run on the
    same day overwrites it) and show it in the response, naming the file. It
    follows the [Output conventions](#output-conventions) and contains:
    - A one-paragraph summary: counts (new / rewritten / header updates /
      unchanged / closed plans; side findings removed / flagged / new;
      decisions changed in steps 8-9) and what relevant changed on master.
    - **One combined table of tickets and side findings**, sorted by priority:
      `Item | Kind | Priority | Status / decision | Plan file / tracked in | Reproduced / check result | Action / walkthrough`
      - Tickets: Item `#<n> (<title>)`, Kind = plan Type, Status, plan
        filename, Reproduced, and Action = new / rewritten (trigger) / master
        assessed, no relevant change / unchanged / closed. Mark tickets whose
        plan changed through an answer in steps 8-9.
      - Side findings, one row each across all side findings files: Item
        `<ID> (<finding>)`, Kind `side finding`, Decision, Tracked in plus the
        tracking plan's filename (`#59 (...) → 59-....md`), check result (still
        valid / removed (reason) / flagged (why) / new) and walkthrough outcome
        (confirmed / decision changed / removed / restored). Removed rows from
        this run are included (priority `–`); rows removed in earlier runs are not.
      - Note where the side findings are stored (`side-findings-*.md`).
    - Remaining open questions, in priority order: for the maintainer (anything
      skipped or deferred in steps 8-9, open maintainer decisions from plans)
      and for reporters (not posted - text only).
    - The response also lists every plan file changed in this run, grouped by
      action.

## Updating existing plans

Every existing plan gets both checks below; results combine into one action.

1. **Closed ticket**: `state` is `CLOSED`/`MERGED`. Skip the other checks.
   Edit the header only: `Status` becomes `closed (<completed | not planned |
   merged | closed unmerged>, <closedAt date>)`, add the closing PR/commit to
   `Related` if findable (`git log gh/master --oneline --grep '#<n>'`), append
   a History entry, and rename the file to `<number>-closed-<slug>.md`. Don't
   delete it. `<number>-closed-*` plans stay untouched unless the ticket was
   reopened: then rename back, append a History entry and treat as open.

2. **Ticket activity**: compare `updatedAt` with the header's `Ticket updated`.
   Older plans lack it; then count the ticket as changed if `updatedAt` is on
   or after the `Processed` date. If changed, work out what changed: new
   comments/reviews after the baseline, title, labels, new PR commits
   (`headRefOid` differs from `Head`). Label-only or cosmetic changes need no
   rewrite, only the header field update.

3. **Master assessment** - mandatory for every open-ticket plan:
   - Baseline is the header's `Master` SHA. Older plans lack it; use
     `git rev-list -1 --before='<Processed> 00:00' gh/master` so commits from
     that day count as new.
   - Baseline equals current `gh/master`: nothing to assess.
   - Otherwise list what landed: `git log --oneline <base>..gh/master` and
     `git diff --stat <base>..gh/master`. Judge relevance for this plan, not
     just by path:
     - commits referencing `#<n>` or tickets in `Related`
     - diffs touching files cited in Evaluation/Plan
     - changes to symbols the plan names (functions, classes, hooks, options,
       templates): `git log --oneline -G '<symbol>' <base>..gh/master`
   - For relevant commits, read the diffs and re-verify the plan: does each
     cited `path:line` still show the described code, is the root cause still
     present, are plan steps already done, obsoleted or now conflicting? For
     PRs, also check `mergeable` against the new master.

4. **Resulting action**:
   - **rewrite** (steps 6a-6d) when the ticket had substantive activity or
     master contains relevant changes. Re-run reproduction only if the change
     could affect it (new code on master, new reporter info, new PR commits);
     otherwise keep the previous Reproduction section prefixed with
     `_Carried over from <date>._` If Status flips (e.g. still valid -> already
     fixed), say why in Evaluation.
   - **header update** when master moved without relevant changes, or ticket
     activity was cosmetic: update `Master` / `Ticket updated` / `Head`, fix
     shifted `path:line` references, append a History entry, e.g.
     `- 2026-09-14: assessed against master abc1234 - no relevant changes`.
   - **unchanged** when neither ticket nor master changed: leave the file
     untouched, including `Processed`.

   History entries for rewrites name the trigger, e.g.
   `- 2026-09-14: rewritten - 2 new comments by reporter; commit abc1234 changes Foo::bar() cited in Evaluation`.

## Re-asking settled decisions

Answers the maintainer already gave - in `## Maintainer notes`, side findings'
Decision column, answered items in `## Risks & open questions`, or decisions
quoted in History - are not asked again by default. Re-ask one when the checks
above show its basis no longer holds:

- the code it refers to changed on master, moved or disappeared
- an assumption it rests on is now false (e.g. "PR #57 will be merged first"
  but #57 was closed unmerged; "only used in X" but master adds a second
  caller; "reporter uses Elementor" contradicted by a new comment)
- a related ticket changed state in a way that affects it
- a reproduction result it relied on now differs

Don't re-ask because a question could be phrased better or because you would
have decided differently on unchanged facts. When unsure whether the basis
changed, re-ask and say what is unclear.

Until answered, keep the old decision in place and mark it
`_Re-opened <date>: <what changed>._` in the plan (or flag the side finding).
Record answers in `## Maintainer notes` as
`- <YYYY-MM-DD> (master <short SHA>): <question> -> <answer>; basis: <assumptions/code relied on>`,
so the next run can tell whether the basis changed again, and add a History
entry. An answer that replaces an earlier note keeps the old one struck
through (`~~...~~`) rather than deleting it.

## Preserved sections

Before overwriting an existing plan, extract `## Maintainer notes` and
`## History` and write them back verbatim, History with the new entry
appended. Everything else is regenerated. If a plan has obvious hand-edits
outside those sections (e.g. first-person decisions or notes not in the
template's style), move them into `## Maintainer notes` with a
`_Moved from <section> on <date>._` line rather than dropping them, and mention
it in the final report.

## Side findings

Files: `.claude/plans/side-findings-*.md`, each with a table
`# | Priority (suggested) | Finding | Decision | Tracked in` (rows in ID order,
since plans reference IDs) and an `## Open action items` list. Priority is
high / medium / low, suggested from impact and the tracking ticket's priority;
a maintainer-set priority is written as `<priority> (maintainer)`.
The files are untracked by git, so removals must leave a trace in the file.

1. **Re-check every row** against `gh/master`, ticket states and plans:
   - Code-level findings: read the cited code on `gh/master`; re-run a cheap
     reproduction if the plan has one.
   - `Done` rows: check whether the named commit reached master
     (`git merge-base --is-ancestor <sha> gh/master`; match by subject if the
     SHA was rebased away).
   - `Part of #<n>` rows: check the ticket's plan/state and whether the fix
     that closed it covered this finding.
   - Decision-only rows (`Leave as is`, `Keep`, `Intended`): check the premise
     still holds (e.g. label still set, refs still used).
   - Upstream reports: check the upstream issue/release if linked.

2. **Remove a row** only when its premise is verifiably gone: fixed on master,
   code removed, `Done` commit on master, tracking ticket closed with the
   finding covered, or decision premise no longer true. Then:
   - delete the table row (never renumber the remaining IDs - plans reference
     them)
   - drop or strike through `Open action items` that only concerned removed IDs
   - append to `## Removed` at the end of the file (create it if missing):
     `- <ID> (<short title>) <finding> - <reason, commit/ticket> (<YYYY-MM-DD>)`

3. **Keep and flag** (asked in step 9, shown in the final report) when unsure, when a row's decision is
   undermined (e.g. tracking ticket closed as not planned), or when `Tracked in`
   points to a renamed/closed plan. Update `Tracked in` for renames; never
   rewrite the Decision column on your own - only with the maintainer's answer
   in the walkthrough (step 9). A Decision whose basis changed is re-asked
   there as a flagged finding (see
   [Re-asking settled decisions](#re-asking-settled-decisions)).

4. **Add** new side findings from this run to the most recent file with
   Decision `open - maintainer to decide` and a suggested priority, using the
   next free ID in the matching letter group (or a new letter).

## Plan template

```markdown
# #<number>: <GitHub title>

- **Ticket**: <url>
- **Type**: bug | feature | task | pull request | question
- **Status**: still valid | already fixed | needs info | won't fix candidate | closed (<reason>, <date>)
- **Reproduced**: yes | no | not applicable | not attempted (reason)
- **Priority (suggested)**: high | medium | low (or `<priority> (maintainer decision <date>)`, see Maintainer notes)
- **Effort (estimate)**: S | M | L
- **Related**: #.. (e.g. "PR #57 proposes a fix")
- **Processed**: <YYYY-MM-DD>
- **Ticket updated**: <GitHub updatedAt, verbatim ISO timestamp>
- **Master**: <gh/master SHA the plan was last assessed against>
- **Head**: <PR headRefOid; pull requests only>

## Summary
<2-5 sentences: what the reporter experiences/wants, relevant discussion.>

## Evaluation
<Code locations as `path:line` on gh/master, root cause or hypothesis,
accuracy of the report, relevant git history.>

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

## Maintainer notes
<Hand-written by the maintainer; never generated. Omit on first creation.>

## History
- <YYYY-MM-DD>: created
```
