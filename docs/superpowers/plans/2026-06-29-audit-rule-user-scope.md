# Per-User Content Audit Rule Scoping Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an audit rule name the ProcessWire usernames it belongs to, and show username-scoped rules only to those users on the dashboard's renamed "My Content Audit" card.

**Architecture:** Add an optional fifth `Users` column to the pipe-delimited audit rule format. Each rule object gains a sanitized `users` string array. A visibility helper plus an opt-in `$onlyMine` flag on `getAuditSummary()` filters the dashboard summary while preserving each rule's original index (so `?rule=N` deep links stay correct). Everything outside the dashboard is untouched.

**Tech Stack:** PHP 8.0+, ProcessWire module (`Verk.module.php`), plain PHP views (`views/*.php`). No automated test harness exists in this repo.

## Global Constraints

- ProcessWire >= 3.0.200, PHP >= 8.0 (copied from README requirements).
- No test framework in the repo — verification is `php -l` syntax checks plus manual checks inside a ProcessWire install. Do NOT add a test framework.
- Backward compatibility is mandatory: existing four-column (and legacy two/three-column) rules must keep working with no `users` restriction.
- Usernames are normalized to the ProcessWire page-name charset (lowercased, `[a-z0-9-_.]`) so they compare against `$user->name`.
- Restriction applies on the dashboard ONLY. The Content Audit page (`viewAudit`), per-page gaps (`getPageAuditGaps`), and DOCX export stay unchanged.
- No `Co-Authored-By: Claude` trailer on commits (user preference).
- All user-facing strings use the existing `$this->_()` / `__()` translation wrappers.

---

### Task 1: Parse and store the `users` column

Add `users` to rule normalization and parse the fifth column on save. After this task, saving a five-column rule stores a `users` array and reading it back round-trips; four-column and legacy rules store `users => []`.

**Files:**
- Modify: `Verk.module.php` — `normalizeAuditRule()` (~lines 1445-1467)
- Modify: `Verk.module.php` — `actionSaveAuditRules()` (~lines 1215-1248)

**Interfaces:**
- Consumes: nothing new.
- Produces: every rule array returned by `getAuditRules()` / `normalizeAuditRule()` now has a `'users'` key whose value is a `string[]` of normalized usernames (empty array = global rule).

- [ ] **Step 1: Add `users` normalization to `normalizeAuditRule()`**

The current method ends with a `return [...]` of `label`/`selector`/`field`/`message`. Add a `users` key. Place this block just before the `return`:

```php
        $rawUsers = $rule['users'] ?? [];
        if (is_string($rawUsers)) $rawUsers = explode(',', $rawUsers);
        $users = [];
        foreach ((array)$rawUsers as $name) {
            $name = strtolower(trim((string)$name));
            $name = preg_replace('/[^a-z0-9\-_.]/', '', $name) ?? '';
            if ($name !== '' && !in_array($name, $users, true)) $users[] = $name;
        }
```

Then add `'users' => $users,` to the returned array, so it reads:

```php
        return [
            'label'    => trim((string)($rule['label'] ?? $this->_('Audit rule'))),
            'selector' => $selector ?: 'template!=admin',
            'field'    => preg_replace('/[^A-Za-z0-9_.*]/', '', $field) ?? '',
            'message'  => trim((string)($rule['message'] ?? $this->_('Field is empty'))),
            'users'    => $users,
        ];
```

- [ ] **Step 2: Parse the fifth column in `actionSaveAuditRules()`**

In the `if (count($parts) >= 4)` branch, pass the fifth column (when present) into the normalize call. Change that branch to:

```php
            if (count($parts) >= 4) {
                $rules[] = $this->normalizeAuditRule([
                    'label'    => $parts[0],
                    'selector' => $parts[1],
                    'field'    => $parts[2],
                    'message'  => $parts[3] ?: $this->_('Field is empty'),
                    'users'    => $parts[4] ?? '',
                ]);
```

Leave the closing of the branch and the legacy `elseif (count($parts) >= 2)` branch unchanged (legacy rules get `users => []` from `normalizeAuditRule`).

- [ ] **Step 3: Syntax-check the module**

Run: `php -l Verk.module.php`
Expected: `No syntax errors detected in Verk.module.php`

- [ ] **Step 4: Manually verify round-trip (in a PW install)**

In the Content Audit page editor, save a rule line:
`Needs clinical review | template!=admin |  | Needs clinical review | John-Smith , jane-brown`
Then reopen the editor. Expected: the rule persists; the stored JSON (module config `audit_rules`) contains `"users":["john-smith","jane-brown"]`. Save a normal four-column rule and confirm its stored `users` is `[]`.

- [ ] **Step 5: Commit**

```bash
git add Verk.module.php
git commit -m "Parse and store optional users column on audit rules (#42)"
```

---

### Task 2: Filter the dashboard summary by current user

Add the visibility helper, make `getAuditSummary()` optionally filter to the current user while preserving original rule indices, and have the dashboard use it.

**Files:**
- Modify: `Verk.module.php` — add `auditRuleVisibleToUser()` (place it next to `getAuditSummary()`, ~line 1643)
- Modify: `Verk.module.php` — `getAuditSummary()` (~lines 1643-1652)
- Modify: `Verk.module.php` — `viewDashboard()` call site (~line 435)

**Interfaces:**
- Consumes: the `'users'` key produced by Task 1.
- Produces:
  - `protected function auditRuleVisibleToUser(array $rule, string $userName): bool` — `true` when `$rule['users']` is empty, else `true` only when `$userName` (lowercased, trimmed) is in `$rule['users']`.
  - `protected function getAuditSummary(bool $onlyMine = false): array` — same `['label', 'count', 'index']` shape as before; when `$onlyMine` is true, rules not visible to the current user are skipped and surviving rules keep their original `index`.

- [ ] **Step 1: Add the `auditRuleVisibleToUser()` helper**

Add this method immediately above `getAuditSummary()`:

```php
    /**
     * A rule with no users listed is global (visible to everyone). Otherwise it
     * is visible only to the named users. No superuser override — the dashboard
     * "My Content Audit" card stays personal, consistent with "My Tasks".
     */
    protected function auditRuleVisibleToUser(array $rule, string $userName): bool {
        $users = $rule['users'] ?? [];
        if (empty($users)) return true;
        return in_array(strtolower(trim($userName)), $users, true);
    }
```

- [ ] **Step 2: Add the `$onlyMine` filter to `getAuditSummary()`**

Replace the method body so the original index is preserved via the `$i` key and hidden rules are skipped:

```php
    protected function getAuditSummary(bool $onlyMine = false): array {
        $rules    = $this->getAuditRules();
        $userName = $onlyMine ? (string)$this->wire('user')->name : '';
        $summary  = [];
        foreach ($rules as $i => $rule) {
            if ($onlyMine && !$this->auditRuleVisibleToUser($rule, $userName)) continue;
            $result = $this->runAuditRule($rule);
            $count = (int)($result['total'] ?? 0);
            $summary[] = ['label' => $rule['label'], 'count' => $count, 'index' => $i];
        }
        return $summary;
    }
```

Note: `$i` is the rule's position in `getAuditRules()`, so the `index` carried into the dashboard links remains the correct `?rule=N` target even when earlier rules are skipped.

- [ ] **Step 3: Make the dashboard use the filtered summary**

In `viewDashboard()` find the line `$auditSummary = $this->getAuditSummary();` (~line 435) and change it to:

```php
        $auditSummary = $this->getAuditSummary(true);
```

Leave `viewAudit()`'s `$this->getAuditSummary();` call (~line 808) UNCHANGED so the Content Audit page still lists every rule.

- [ ] **Step 4: Syntax-check the module**

Run: `php -l Verk.module.php`
Expected: `No syntax errors detected in Verk.module.php`

- [ ] **Step 5: Manually verify dashboard filtering (in a PW install)**

Using rules from Task 1: log in as `john-smith` → the "Needs clinical review" rule appears on the dashboard. Log in as an unlisted user (including a superuser whose name is not listed) → it does NOT appear, but the global four-column rule still does. Click a dashboard audit card and confirm it opens the correct rule on the Content Audit page (`?rule=N` resolves correctly). Confirm the Content Audit page itself still lists every rule for everyone.

- [ ] **Step 6: Commit**

```bash
git add Verk.module.php
git commit -m "Filter dashboard audit summary to the current user (#42)"
```

---

### Task 3: Editor column, format hint, card rename, and docs

Surface the fifth column in the editor textarea, update the format hint, rename the dashboard card, and document the new column.

**Files:**
- Modify: `views/audit.php` — format hint (line 304) and textarea render loop (lines 311-313)
- Modify: `views/dashboard.php` — card title (line 245)
- Modify: `README.md` — audit rules bullet (line 39)

**Interfaces:**
- Consumes: the `'users'` key on each rule (Task 1) and the unchanged `getAuditRules()` shape.
- Produces: no new code interfaces (presentation + docs only).

- [ ] **Step 1: Update the format hint in `views/audit.php`**

Change line 304 from:

```php
            <code><?= __('Label | Scope selector | Field path | Message') ?></code>
```

to:

```php
            <code><?= __('Label | Scope selector | Field path | Message | Users') ?></code>
```

- [ ] **Step 2: Render the fifth column in the editor textarea**

Change the loop body (lines 311-313) from:

```php
foreach ($rules as $r) {
    echo htmlspecialchars($r['label'] . ' | ' . $r['selector'] . ' | ' . ($r['field'] ?? '') . ' | ' . ($r['message'] ?? '')) . "\n";
}
```

to:

```php
foreach ($rules as $r) {
    $users = implode(',', $r['users'] ?? []);
    echo htmlspecialchars($r['label'] . ' | ' . $r['selector'] . ' | ' . ($r['field'] ?? '') . ' | ' . ($r['message'] ?? '') . ' | ' . $users) . "\n";
}
```

Note: rules with no users render a trailing ` | ` which re-parses cleanly to `users => []` on save.

- [ ] **Step 3: Rename the dashboard card in `views/dashboard.php`**

Change line 245 from:

```php
                <h3 class="vk-card-title"><?= __('Content Audit') ?></h3>
```

to:

```php
                <h3 class="vk-card-title"><?= __('My Content Audit') ?></h3>
```

- [ ] **Step 4: Document the new column in `README.md`**

Change line 39 from:

```markdown
- **Audit rules** — plain text config: `Label | Scope selector | Field path | Message`, with dot-notation subfields
```

to:

```markdown
- **Audit rules** — plain text config: `Label | Scope selector | Field path | Message | Users`, with dot-notation subfields; the optional `Users` column (comma-separated usernames) limits a rule to those users on the dashboard's "My Content Audit" card
```

- [ ] **Step 5: Syntax-check the views**

Run: `php -l views/audit.php && php -l views/dashboard.php`
Expected: `No syntax errors detected` for both files.

- [ ] **Step 6: Manually verify the editor and card (in a PW install)**

Open the Content Audit page editor: each rule line now ends with ` | ` followed by its usernames (or empty). Confirm the format hint shows the five-column form. On the dashboard, confirm the card title reads "My Content Audit". Edit a rule's user list in the textarea, save, reopen — the change round-trips.

- [ ] **Step 7: Commit**

```bash
git add views/audit.php views/dashboard.php README.md
git commit -m "Show users column in audit editor and rename dashboard card (#42)"
```

---

## Self-Review

**Spec coverage:**
- Format change (4→5 columns, backward compatible) → Task 1 (parse/store) + Task 3 (editor render, hint, README).
- Stored `users` array shape → Task 1.
- Visibility logic / no superuser override → Task 2 (`auditRuleVisibleToUser`).
- Dashboard-only scope, index preservation → Task 2 (`getAuditSummary($onlyMine)`, `viewDashboard`), and Task 2 Step 3 explicitly leaves `viewAudit` unchanged.
- Dashboard card rename to "My Content Audit" → Task 3 Step 3.
- README documentation → Task 3 Step 4.
- Edge cases (empty list → global, hidden-for-user, unknown usernames harmless, whitespace/case normalization) → covered by normalization in Task 1 Step 1 and helper in Task 2 Step 1.
- Testing approach (manual, no harness) → every task's manual-verify step.

**Placeholder scan:** No TBD/TODO/"handle edge cases" — all steps carry concrete code and exact commands.

**Type consistency:** `users` is a `string[]` everywhere (produced in Task 1, consumed in Tasks 2 and 3). `auditRuleVisibleToUser(array, string): bool` and `getAuditSummary(bool): array` signatures match across their definition (Task 2) and call sites (`viewDashboard` Task 2 Step 3). The `['label','count','index']` summary shape is unchanged, so `views/dashboard.php` consumers (`$a['index']`, `$a['count']`, `$a['label']`) still work.
