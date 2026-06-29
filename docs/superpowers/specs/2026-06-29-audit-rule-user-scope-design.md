# Limit content audit rules to specific users

**Issue:** [#42](https://github.com/mxmsmnv/Verk/issues/42)
**Date:** 2026-06-29

## Problem

Sites with several content audit rules clutter the dashboard for users who are
not responsible for a given audit. A rule like "Needs clinical review" should
only appear on the dashboard of the people who own that review, not everyone.

## Solution overview

Add an optional fifth column to the audit rule format that lists the
ProcessWire usernames a rule belongs to. When usernames are present, the rule is
shown only to those users on the dashboard. When the column is empty, the rule
stays global (visible to everyone), keeping every existing rule working
unchanged.

The restriction applies on the **dashboard only**. The dashboard "Content Audit"
card is renamed **"My Content Audit"** for consistency with "My Tasks", "My
Reviews", etc. Everywhere else audit rules surface — the full Content Audit
page, the per-page audit gaps in the Page Editor widget, and DOCX export — is
unchanged and continues to show all rules.

## Format change (backward compatible)

The audit rule line goes from four to an optional five columns:

```
Label | Scope selector | Field path | Message | Users
```

- `Users` is a comma-separated list of ProcessWire usernames, e.g.
  `john-smith,jane-brown`.
- Empty or omitted → the rule is global and visible to everyone. Every existing
  four-column rule therefore keeps its current behavior.

Example:

```
Needs clinical review | template=message, completed_reviews!=1 |  | Needs clinical review | john-smith,jane-brown
```

### Stored shape

Each rule object gains a `users` key (array of sanitized username strings)
alongside the existing `label`, `selector`, `field`, `message`:

```php
[
    'label'    => 'Needs clinical review',
    'selector' => 'template=message, completed_reviews!=1',
    'field'    => '',
    'message'  => 'Needs clinical review',
    'users'    => ['john-smith', 'jane-brown'],
]
```

A global rule stores `'users' => []`.

## Visibility logic

A new helper decides whether the current user may see a rule:

```php
protected function auditRuleVisibleToUser(array $rule, User $user): bool
```

- `$rule['users']` empty → visible to all (return `true`).
- otherwise → visible only if `$user->name` matches an entry in the list,
  compared trimmed and case-insensitively.
- **No superuser override** — superusers are treated like everyone else, so the
  "My" card stays personal and consistent with "My Tasks".

## Implementation points

All in `Verk.module.php` unless noted.

1. **`actionSaveAuditRules()`** (~line 1215) — when a line has a fifth column
   (`$parts[4]`), pass it through to `normalizeAuditRule()` as the raw users
   string. Four-column and legacy two/three-column lines continue to parse as
   before (no users → global).

2. **`normalizeAuditRule()`** (~line 1445) — add a `users` key. Accept either an
   array or a comma-separated string, split on commas, trim, drop empties, and
   sanitize each name to the ProcessWire page-name charset
   (`[a-z0-9-_.]`, lowercased) so it can be compared against `$user->name`.

3. **`auditRuleVisibleToUser()`** — new helper implementing the logic above.

4. **`getAuditSummary(bool $onlyMine = false)`** (~line 1643) — when `$onlyMine`
   is `true`, skip rules the current user cannot see. The returned `index` for
   each surviving rule MUST remain the rule's original position in
   `getAuditRules()` so the existing `?view=audit&rule=N` deep links keep
   pointing at the correct rule on the full Audit page. (Iterate with the
   original index and `continue` past hidden rules rather than re-indexing.)

5. **`viewDashboard()`** (~line 435) — call `getAuditSummary(true)`. Both
   dashboard audit displays read the same `$auditSummary`, so the top stat tiles
   (lines 97–101) and the card (lines 242–259) are filtered consistently.

6. **`viewAudit()`** (~line 808) — unchanged. It calls `getAuditSummary()` with
   no argument, so the full Content Audit page still lists every rule.

7. **`views/audit.php`** —
   - Render the fifth column in the editor textarea: append
     ` | ` + the comma-joined `users` for each rule (empty string when none).
   - Update the format hint (line 304) to
     `Label | Scope selector | Field path | Message | Users`.
   - (The dashboard card title lives in `views/dashboard.php`, not here — see the
     next item.)

8. **`views/dashboard.php`** (~line 245) — change the card title from
   `Content Audit` to `My Content Audit`.

9. **`README.md`** (line 39) — document the optional `Users` column in the audit
   rules bullet.

## Edge cases

- A rule whose `users` list names only other people simply does not appear on
  the current user's dashboard; it is still fully reachable on the Content Audit
  page (and via direct `?rule=N` link).
- If a user has no visible rules, `$auditSummary` is empty and the dashboard card
  hides itself (existing `if ($auditSummary)` guard) — no empty card.
- Usernames that do not correspond to a real PW user are harmless: they simply
  never match a current user's `->name`.
- Whitespace and casing in the column are normalized on save, so
  `John-Smith , jane-brown` stores as `['john-smith', 'jane-brown']`.

## Testing

The repository has no automated test harness, so verification is manual inside a
ProcessWire install:

1. A four-column (no users) rule shows on every user's dashboard — confirms
   backward compatibility.
2. A rule scoped to `userA` shows on userA's dashboard and not on userB's.
3. A superuser whose name is not listed does **not** see the scoped rule on their
   dashboard (no override).
4. The full Content Audit page lists every rule for everyone regardless of the
   users column, and `?rule=N` deep links resolve to the correct rule.
5. Saving rules with and without the fifth column round-trips correctly through
   the editor textarea (the column reappears as typed, normalized).
