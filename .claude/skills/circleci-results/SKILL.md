---
name: circleci-results
description: Read CircleCI results for open-source-event-calendar with the circleci CLI - triage order for a red build, run/job/testresult/artifact commands, reading source at the run's revision, flaky-test insights, token setup/renewal and permission guardrails. Use when the user asks about a CI run, a failing build or flaky tests.
---

# Reading CI results

The `circleci` CLI is installed in the web container so pipeline results can be read directly
instead of being pasted in by hand. Set up 2026-09-24; verified working against real runs.

### The setup is NOT in version control

`/var/www/html/.ddev` sits **outside** this git repo (the repo is the plugin directory, the DDEV
config lives at the WordPress root), so none of the three files below are tracked anywhere. On a
fresh machine or a wiped DDEV config they must be recreated by hand — this section is the only
record of them.

| File | Contents |
|---|---|
| `.ddev/web-build/Dockerfile.circleci` | `RUN curl -1sLf 'https://packages.circleci.com/public/setup.deb.sh' \| bash && apt-get install -y --no-install-recommends circleci && rm -rf /var/lib/apt/lists/*` |
| `.ddev/.env.web` | `CIRCLE_NO_INTERACTIVE=1` (shared, no secret) |
| `.ddev/.env.web.local` | `CIRCLE_TOKEN=<the PAT>` — gitignored by DDEV ≥ 1.25.4 via `/.env.*.local` |
| `.ddev/config.circleci.yaml` | `post-start` hooks: `circleci setting set telemetry off \|\| true` and `… update-check off \|\| true` |

The `post-start` hooks are needed because the container home is not persisted across rebuilds, so
the CLI's own config is lost on every `ddev restart`. Both settings work without a token.

### The token

**The CLI cannot run without one** — with none set it exits 3 with `auth.token_missing`,
client-side, before any network call. The token is a **Read-scoped OAuth PAT**, the only CircleCI
credential that both drives the v2 API and is read-only at the API level:

| Token type | Works with the CLI (v2)? | Can change project settings/env vars/contexts? |
|---|---|---|
| Project token, *Read Only* scope | **No** — v1 API only | No |
| Personal API token (created manually) | Yes | **Yes** — full read *and* write |
| OAuth PAT, *Read* scope | Yes | **No** — enforced server-side |
| OAuth PAT, *Write* / *Admin* | Yes | Undocumented for Write; assume yes |

**It expires after 90 days with no refresh token** (issued 2026-09-24, so due ≈ 2026-12-23; the
symptom is 401 from every command). Renew on the **host** — the browser + `127.0.0.1` loopback +
keyring flow cannot run headless in the container:

```bash
brew install circleci
circleci auth login --insecure-storage   # choose "Read" on the consent screen
grep token ~/.config/circleci/config.yml # --insecure-storage writes it here instead of the keyring
ddev dotenv set .ddev/.env.web.local --circle-token="<PAT>"
ddev restart
```

`--insecure-storage` is documented only in `circleci auth login`'s *Details* prose, not its flag
table; if a build rejects it, the token goes to the keyring and `circleci setting list` prints it.

Check a token before wiring it in — `200` means it drives the CLI, `401` means it is a v1-only
project token:

```bash
curl -s -o /dev/null -w '%{http_code}\n' -H "Circle-Token: $TOKEN" https://circleci.com/api/v2/me
```

Prove the Read scope is real (expect **403**; a `201` means the token is not read-only — delete the
var with `DELETE …/envvar/OSEC_SCOPE_PROBE` and re-issue the token):

```bash
curl -s -o /dev/null -w '%{http_code}\n' -X POST \
  -H "Circle-Token: $CIRCLE_TOKEN" -H 'Content-Type: application/json' \
  -d '{"name":"OSEC_SCOPE_PROBE","value":"x"}' \
  "https://circleci.com/api/v2/project/gh/digitaldonkey/open-source-event-calendar/envvar"
```

### Commands

**Triage order for a red build** — `run get` (which job failed) → `testresult list` (which tests)
→ artifact screenshot if it is a frontend test → `job output list` only when the failure is
*outside* a test (apt, composer, install). Then check whether a later commit already fixed it.
Reaching for `job output list` first means grepping logs for something `testresult list` prints
as a table.

The project is auto-detected from the git remote, so `--project` is only needed for another repo.
Job arguments are **UUIDs**, not the job numbers in the web UI — passing a number fails with
`invalid UUID length: 4`. Get the UUIDs from `circleci run get`.

```bash
SLUG=gh/digitaldonkey/open-source-event-calendar

circleci run list --current-branch                  # recent runs, this branch
circleci run list --limit 60 --json                 # all branches
circleci run get <run-id>                           # workflows + job UUIDs
circleci job get <job-uuid>
circleci testresult list <job-uuid>                 # failing test names as a table — start here
circleci artifact <job-uuid>                        # screenshots etc. (NOT `artifact list`)
circleci config validate                            # before pushing a .circleci/config.yml edit

# why did it fail — output of every non-zero step
circleci job output list <job-uuid> --json \
  | jq '.steps[] | select(.exit_code != 0) | .output'

# most recent failure across all branches
circleci run list --limit 60 --json \
  | jq -r '.[] | select(.current_outcome != "succeeded")
           | "\(.created_at)  \(.current_outcome)  \(.branch)  \(.id)  \(.commit.subject)"'
```

`run list --json` returns a **top-level array** whose outcome field is `current_outcome`
(`succeeded` / `failed` / `not_run`) — not `.items[]` and not `.status`.

### Always read source at the run's revision, never the working tree

`circleci run get` prints the run's `Commit`. Read the failing file **at that revision** —
`git show <rev>:<path>` — before explaining a failure:

```bash
git show 3af4c8e:integration_tests/test/03_OsecPluginFrontend.spec.js | sed -n '305,322p'
```

A CI run is a snapshot of the past, and the fix is often already in the working tree, so quoting
`path:line` from disk can show code that **would have passed** — which is self-evidently not what
failed. This happened on 2026-09-24: the failed `release_test_job` on `fix/recurrence-dst-drift`
(`3af4c8e`) asserted `markerImgUrl.startsWith('https://unpkg.com/leaflet')`, but Leaflet had moved
to local delivery so the marker `src` was `<DOMAIN>/public/js/external_libs/leaflet/…` — a **stale
test**, not a plugin regression. The working-tree copy already carried the fix from `96a73aed`
(`startsWith(pageObject.settings.domain)`), which hid the entire cause.

Sanity check before reporting: *does the code I am quoting actually explain the failure?* If it
looks like it would pass, the revision is wrong.

**Then check whether it is already fixed before reporting anything.** A failed run is history, and
the maintainer may have fixed it hours later — reporting a fresh diagnosis of a solved problem
wastes their time. List what landed on the failing file since that revision:

```bash
P=integration_tests/test/03_OsecPluginFrontend.spec.js
git log --oneline 3af4c8e..HEAD -- "$P"     # run's commit is an ancestor of HEAD
git log --oneline --all --since='2026-09-23 08:59' -- "$P"   # fallback: unmerged branch
```

Use the second form when `git merge-base --is-ancestor <rev> HEAD` fails. Either would have
returned `96a73aed "Fix map integration test, remove unpkg.com references, as we moved to local
delivery."` in the case above, making the correct report "that build failed on a stale test,
already fixed in 96a73aed" rather than a diagnosis.

### Insights and artifacts (what the logs cannot tell you)

```bash
S=gh/digitaldonkey/open-source-event-calendar
H="Circle-Token: $CIRCLE_TOKEN"

# intermittent failures, with counts and the workflow timestamp of each flake
curl -s -H "$H" "https://circleci.com/api/v2/insights/$S/flaky-tests" \
  | jq -r '.flaky_tests[] | "\(.times_flaked)x \(.workflow_created_at) \(.job_name) \(.test_name)"'

# per-job success rate, p95 duration and credits over ~90 days
curl -s -H "$H" "https://circleci.com/api/v2/insights/$S/workflows/build_test_deploy/jobs?branch=master" \
  | jq -r '.items[] | "\(.name) \(.metrics.success_rate) \(.metrics.duration_metrics.p95)s \(.metrics.total_credits_used)"'
```

**Selenium screenshots are downloadable and can be looked at.** `circleci artifact <job-uuid> --json`
gives `{path, url}`; the URL **302s to a presigned host, so `curl` needs `-L`** — without it you get
a 0-byte file and `HTTP:302`. Reading the screenshot of a failing frontend test is often faster than
any log: on 2026-09-24 it showed the map and marker rendering correctly, proving the failure was a
stale assertion rather than a plugin regression.

Two things this surfaced on 2026-09-24, worth re-checking rather than re-deriving:

- **The `Add daily repeating event` flakes are the near-midnight bug.** All flakes (3× per theme)
  came from one workflow created `2026-09-22T22:23:06Z`. CI runs `TZ: "Europe/Berlin"`
  (`.circleci/config.yml:51`, matching `integration_tests/mocha/settings.js`), so that is **00:23 local** —
  inside the `[00:00, 01:00)` window described under *Data Model: Events & Recurrence*. One workflow
  is corroboration, not proof.
- **The Selenium matrix is ~69% of CI credits** (`release_test_job` ×4 ≈ 16,956 of 24,604 over ~42
  master runs), while the whole PHPUnit matrix is ~2,180. Test jobs sit at 90–92% success while
  `build` and `static_job` are at 100% — flakiness, not breakage.

### Guardrails: two independent gates, only one of which binds

`.claude/settings.json` gates what Claude may *type*; the **token scope** gates what CircleCI will
*do*. The token is the binding one, and it cannot be talked around.

`run trigger`, `run cancel`, `workflow rerun` and `workflow cancel` are **denied**. They were
briefly allowed on 2026-09-24 to test them, and the test showed the allow entries were inert: with
the Read-scoped PAT every one fails `403 Permission denied` at the API. They were reverted to deny,
so both gates now agree. Re-allowing them changes nothing unless the token is also re-minted with
**Write** scope — read the caveat below before doing that.

Also denied, because these change project state rather than pipeline runs: `envvar`, `context`,
`project`, `policy`, `runner`, `deploy`, `dlc`, `setting set/unset`, `orb publish`, `namespace`,
`auth login/logout`, plus `Read()` on `.env.web.local`. Keep deny patterns narrow — a blanket
`Bash(circleci setting:*)` also blocks the harmless read `circleci setting list`.

**To test what a scope really permits, hit the API endpoint directly — not the CLI.** The CLI
short-circuits client-side and never reaches the server: `circleci run cancel <finished-run>`
answers *"has no active workflows to cancel"* and exits 0, which says nothing about permissions.
The honest probes are:

```bash
# write to pipeline state (harmless on a finished workflow)
curl -s -w '%{http_code}\n' -X POST -H "Circle-Token: $CIRCLE_TOKEN" \
  https://circleci.com/api/v2/workflow/<finished-workflow-id>/cancel

# write to project settings — 403 is the answer that keeps the safety promise
curl -s -o /dev/null -w '%{http_code}\n' -X POST \
  -H "Circle-Token: $CIRCLE_TOKEN" -H 'Content-Type: application/json' \
  -d '{"name":"OSEC_SCOPE_PROBE","value":"x"}' \
  "https://circleci.com/api/v2/project/$SLUG/envvar"
```

**Before upgrading to a Write-scoped token, run the second probe.** CircleCI documents that OAuth
scopes are Read / Write / Admin but never says whether **Write** also permits changing env vars and
contexts. If it does, a Write token breaks the original requirement that CI access must not be able
to touch project settings. Mint it, probe it, and drop back to Read if the probe returns `201`
(deleting `OSEC_SCOPE_PROBE` afterwards).

### Fallback when the token is expired or revoked

The project is public, so the REST API answers these reads **unauthenticated**:
`/api/v2/project/{slug}/pipeline` → `/api/v2/pipeline/{id}/workflow` →
`/api/v2/workflow/{id}/job` covers status, and `/api/v2/project/{slug}/{job_number}/artifacts`
and `.../tests` work too. Step logs are the exception: they need the **deprecated v1.1** endpoint
`/api/v1.1/project/github/{org}/{repo}/{job_number}`, whose `output_url` values are presigned and
expire within minutes, so fetch them immediately.
