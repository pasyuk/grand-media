# Gmedia Photo Gallery (grand-media) — Agent Instructions

<!-- Capsule contract: this ONE file is the project capsule (ADR-002).
     Sections marked (required) must exist; `agentic doctor` checks them. -->

## Project (required)
- id: grand-media
- type: wordpress-plugin
- owner: simka
- memory: `~/_DEV_/agentic-vault/Projects/grand-media/` — durable notes,
  decisions, receipts live THERE; this repo holds only working files.

## Session continuity (required)
At session start, READ the vault working state and continue from its
"In progress"/"Next". State path (ADR-003, resolver `lib/task-path.sh`):
`Projects/<id>/current.md` on main/master; on any other branch
`Projects/<id>/tasks/<branch-slug>/current.md`. At checkpoints and session
end, UPDATE it and append a journal entry (handoff skill). This is how work
survives switching between Claude Code / Codex / Antigravity / Devin — and
between parallel worktrees — mid-task.

## Verification (required)
Run before claiming any change done:
```bash
for t in tests/compat/*.php; do php "$t" || exit 1; done && git diff --name-only HEAD -- "*.php" | xargs -I{} php -l {}
```

## Connectors (required)
Accounts this project may touch. Connector skills call the service CLI/API via
`agentic secrets run <profile> -- ...` (ADR-002 §3); anything not listed = out of scope.
| Service | Account/workspace | Access |
|---|---|---|
| github | pasyuk/grand-media (PUBLIC repo, `pasyuk` gh account only) | read-write; push to feature branches, PRs into this repo |
| wordpress.org | support forum for grand-media | read; drafting replies only — the owner posts |

## Workflow (required)
- **Tracking**: GitHub issues are the ticket system. Every unit of work gets an
  issue first, then a branch named after it, then a PR that closes it.
- **Intake — support forum**: check
  `https://wordpress.org/support/plugin/grand-media/` regularly for new user
  reports. Triage per `docs/kb/support-playbook.md`. Replies are DRAFTS; the
  owner posts them.
- **Intake — email**: the owner forwards problem reports as vault notes under
  `Sources/Emails/`. Read the note, turn it into an issue, then implement.
- **Implementation**: use the superpowers skills — `brainstorming` before design
  work, `systematic-debugging` for defects, `test-driven-development` for the
  fix, `verification-before-completion` before claiming anything done.
- **Release**: Freemius + wordpress.org SVN. Process not documented yet — ASK
  the owner before attempting any release step.

## Security reports (required)
This repository is PUBLIC. A vulnerability report is confidential until patched:
- NEVER put proof of concept, exploit parameters, CVSS, or reporter identity in
  an issue, PR, commit message, or committed doc.
- Track with a neutral title and the affected surface only; details stay in the
  private email thread.
- Note the wordpress.org clock in the issue: 14 days to acknowledge, 30 days to
  patch, or the plugin is closed.
- Plugin Check (`https://wordpress.org/plugins/plugin-check/`) must be clean
  before requesting re-review.

## Protected actions (required)
These ALWAYS require explicit human approval (enforced by the PreToolUse hook,
documented here for humans + non-hook harnesses):
send message · create/update external issue · merge PR · bulk delete
· rotate/modify secrets · anything billing.

## Working rules
- Evidence before verdict: a "done/passing" claim requires a citation
  (file:line, test name, command output) written BEFORE the claim. No
  citation → state UNPROVEN.
- 2-strike rule: same issue survives 2 fix attempts → STOP patching, write
  diagnostic, ask the user.
- Read-before-write; glob before creating a file (no silent shadowing);
  destructive steps need a stated rollback plan covering untracked files.

## Knowledge graph
After modifying code, run `graphify update .`. For codebase questions, query
`graphify query "<question>"` before grepping.

## Conventions
Follow these unless the owner says otherwise:
- Branches: name them after the WORK, never after the tool. `issue-<N>-<slug>`
  when a GitHub issue drives it, otherwise `fix/<slug>`. One task per branch.
  PRs target `master` (this repo has no `main`) and land squashed with `(#N)`.
  Do NOT prefix branches or PR titles with a tool name (`codex/`, `claude/`,
  `[codex]`): several harnesses work this repo, the author is already recorded
  in the commit, and a copied prefix ends up crediting the wrong tool. Branches
  and PRs predating 2026-08-23 carry `codex/` — leave that history alone.
- Commits: imperative subject, no type prefix ("Harden admin AJAX nonce and
  capability checks"). Older history used `Fix:`/`Enhance:` prefixes; recent
  work does not. Body explains why, not what.
- Code: WordPress PHP coding standards as written in the existing files — tabs,
  spaces inside parens, Yoda conditions, snake_case functions, `esc_*`/`wp_*`
  helpers for output and HTTP. Match the file you are editing.
- Licensing/test data: never commit license keys or harness backups; they hold
  serialized account state. See the vault decisions/ for licensing policy.

## Skills
Universal skills come from the agentic-os kernel (linked). Project skills live
in `.agents/skills/` (canonical; harness dirs are symlinks via `agentic link`).
Rule: a skill that names an account, channel, table, or command is a PROJECT
skill; process-only skills go to the kernel.

Optional overrides: `./AGENTS.override.md` then `~/.config/agentic/override.md`
— later wins; may narrow, must not relax protected actions.
