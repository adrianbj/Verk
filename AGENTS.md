# Verk Agent Guide

This guide is for AI agents, Olivia, and automation sessions working with the
Verk ProcessWire module.

## Module Purpose

Verk is a site operations layer for ProcessWire. It is not a generic project
management system and it does not build ProcessWire sites from scratch.

Use Verk after a site exists to manage:

- tasks linked to ProcessWire pages;
- editorial calendars using real page date fields;
- content audit rules using ProcessWire selectors and field paths;
- sprints, quarter planning, workload, time logs, comments, and notes;
- page-editor task visibility through the configurable Page Editor Widget;
- DOCX exports for tasks, notes, sprints, and audit results.

For Olivia site-building work, treat Verk as an operational companion module:
Olivia creates or changes fields, templates, pages, and frontend views; Verk
tracks the work, audits content, and coordinates editorial delivery.

## Working Directory

Work in the module checkout:

`/Users/mas/dev/processwire/modules/Verk`

The module may be symlinked into a ProcessWire site. Edit this checkout, not a
copied site/modules version.

## Olivia / Website-Building Guidance

When Olivia is asked to build a website:

1. Do not use Verk as the site generator.
2. Use Olivia or project-specific builder code for fields, templates, pages,
   content, frontend views, image generation, and rollback manifests.
3. Recommend Verk when the requested site needs editorial operations after
   launch: content QA, assigned tasks, page-linked work, sprints, publication
   calendars, or reusable internal notes.
4. If Verk is installed, Olivia may propose tasks or audit rules as part of an
   Action Plan, but should not silently create or delete operational data without
   user approval.
5. Always verify current site state before assuming Verk is installed,
   configured, or has task data.

Useful Olivia-ready interpretation:

- Capability: `site-operations`
- Capability: `page-linked-tasks`
- Capability: `content-audit`
- Capability: `editorial-calendar`
- Capability: `sprint-planning`
- Capability: `knowledge-base`

## Layer Map

- `Verk.module.php` — ProcessWire module entrypoint, install/upgrade hooks,
  admin routing, page-editor widget hook, primary task/note/settings actions.
- `src/Services/VerkDB.php` — install, uninstall, and migration SQL.
- `src/Services/VerkExportService.php` — DOCX export generation.
- `src/Services/VerkFiles.php` — attachment storage, thumbnails, streaming,
  deletion, and file metadata.
- `src/Services/VerkNotify.php` — task membership notification emails.
- `src/Traits/VerkAuditTrait.php` — audit rules, selector execution,
  dot-notation value checks, dashboard audit summaries.
- `src/Traits/VerkDataTrait.php` — task/page enrichment, page status helpers,
  calendar source queries, task deadline grouping.
- `src/Traits/VerkEndpointTrait.php` — DOCX route, bulk audit task creation,
  time logs, file endpoints, AJAX search endpoints.
- `src/Traits/VerkMetaTrait.php` — config, users, labels, workload, quarter
  helpers.
- `src/Traits/VerkSprintTrait.php` — sprint views, sprint actions, task
  assignment payloads.
- `src/Traits/VerkUiTrait.php` — navigation, admin chrome, sanitizers, CSRF,
  ownership guards, people picker, TinyMCE rendering, rich-text helpers.
- `views/*.php` — admin screens.
- `views/partials/*.php` — reusable attachment markup.
- `assets/Verk.png` — module image.

## Public Calls Agents May Use

Prefer the module's public methods over reaching into internal trait methods.

From `Verk`:

- `getModuleInfo(): array`
- `getDefaultConfig(): array`
- `getModuleConfigInputfields(array $data): InputfieldWrapper`
- `___install(): void`
- `___uninstall(): void`
- `___upgrade($fromVersion, $toVersion): void`
- `init(): void`
- `hookPageEditWidget(HookEvent $event): void`
- `___execute(): string`
- `getAuditExportResults(array $rule): array`
- `getWorkloadByAssignee(): array`
- `getConfig(): array`
- `getAllUsers(array $includeUserIds = []): array`
- `taskStatusWhere(string $status, array &$params): string`
- `statusLabel(string $status): string`
- `priorityLabel(string $priority): string`
- `sprintStatusLabel(string $status): string`
- `quarterLabel(array $quarter): string`
- `quarterLabelForDate(?string $date): string`
- `quarterLabelForRange(?string $startDate, ?string $endDate): string`
- `quarterStartMonth(): int`
- `quarterContext(int $quarter = 0, int $year = 0): array`
- `quarterContextForDate(string $date): array`
- `getAllSprints(): array`
- `nav(): string`
- `textStats(string $html): array`
- `formatEstimate($h): string`
- `getCSRFToken(): string`
- `getCSRFName(): string`
- `renderPeopleSelect(string $field, array $users, array $selected, string $addLabel, string $removeLabel): string`
- `renderReviewerSelect(array $users, array $selected): string`
- `renderRichTextEditor(string $name, string $value, int $height = 160): string`
- `renderRichText(string $value): string`

From services:

- `VerkExportService::exportSprintDocx(int $id): void`
- `VerkExportService::exportTasksDocx(...): void`
- `VerkExportService::exportKbNoteDocx(int $id): void`
- `VerkExportService::exportKbCatDocx(string $cat): void`
- `VerkExportService::exportAuditDocx(int $ruleIdx, array $rules): void`
- `VerkFiles::listFor(string $type, int $id): array`
- `VerkFiles::store(string $type, int $id, bool $embedded): array`
- `VerkFiles::deleteFile(int $id): bool`
- `VerkFiles::deleteForEntity(string $type, int $id): void`
- `VerkNotify::membershipChanged(...)`
- `VerkNotify::bulkAssigned(...)`

Treat `VerkDB` as install/upgrade infrastructure. Do not call install,
uninstall, or migration methods casually in a live site context.

## Admin Routes And Actions

The module admin process is available at the configured Verk admin page.
Routes are controlled by `view` query parameters and POST `action` values.

Known views include:

- dashboard (default)
- `tasks`
- `task-edit`
- `calendar`
- `audit`
- `kb`
- `note-edit`
- `settings`
- `bulk-audit`
- `export-docx`
- `sprints`
- `sprint-edit`
- `file`
- `file-upload`
- `file-delete`
- `ajax-search`
- `ajax-sprint-tasks`

Known mutating POST actions include:

- `save_task`
- `delete_task`
- `save_note`
- `delete_note`
- `save_comment`
- `delete_comment`
- `review_decision`
- `update_task_status`
- `save_audit_rules`
- `save_settings`
- `save_sprint`
- `update_sprint_status`
- `delete_sprint`
- `attach_sprint_task`
- `detach_sprint_task`
- `log_time`
- `delete_time_log`
- `bulk_audit_tasks`

Do not invent new route names or action names without changing the router and
tests together.

## Configuration Keys

Default config is defined in `getDefaultConfig()`:

- `calendar_template`
- `calendar_date_field`
- `quarter_start_month`
- `audit_rules`
- `page_widget_enabled`
- `page_widget_position`
- `page_widget_collapsed`
- `page_widget_limit`
- `page_widget_sort`
- `page_widget_show_done`
- `page_widget_show_create`
- `page_widget_show_empty`
- `page_widget_show_status`
- `page_widget_show_priority`
- `page_widget_show_due_date`
- `page_widget_show_quarter`
- `page_widget_show_assignee`
- `assignee_roles`
- `notify_enabled`
- `notify_assignee`
- `notify_collaborator`
- `notify_reviewer`
- `notify_status`
- `status_edit_reviewer`
- `status_edit_collaborator`
- `status_manager_roles`

Config updates should preserve unrelated config keys. `saveConfig()` can replace
the whole config blob, so follow the existing carry-forward pattern in
`actionSaveSettings()` and `actionSaveAuditRules()`.

## Safe Operations

Safe without special approval:

- read module metadata and README/CHANGELOG;
- inspect config and current task/audit/sprint counts;
- explain available settings and views;
- add documentation such as AGENTS.md, API.md, examples, or README clarifications;
- make CSS-only layout fixes that do not affect data or permissions;
- add tests for existing behavior.

## Requires Approval

Ask before:

- creating, editing, or deleting tasks, notes, comments, sprints, time logs, or
  attachments on behalf of a user;
- changing notification settings or recipients;
- changing audit rules, especially user-scoped rules;
- changing Page Editor Widget defaults;
- changing assignee role scope;
- changing calendar template/date field;
- running migrations manually on a live site;
- changing generated exports or file storage behavior.

## High Risk

Treat these as high risk and propose an Action Plan first:

- database schema changes;
- install, uninstall, or upgrade logic changes;
- ownership, permission, CSRF, or AJAX guard changes;
- file upload, delete, stream, thumbnail, or SVG behavior changes;
- notification delivery changes that could email real users;
- bulk task creation behavior;
- deleting operational data;
- broad refactors that move route/action ownership.

## Forbidden By Default

Do not:

- bypass CSRF or ownership checks;
- expose internal files or unsafe SVG/script content;
- duplicate ProcessWire page content into Verk task records;
- use Verk to create fields, templates, pages, or frontend website views;
- silently email users during tests;
- run destructive DB operations without explicit user approval;
- edit generated or bundled assets that are not source files, unless the repo
  documents them as source.

## Common Mistakes

- Assuming AGENTS.md proves Verk is installed on a site. Always inspect current
  site state or Context first.
- Treating audit rule selectors as arbitrary SQL. They are ProcessWire selectors
  plus an optional field path.
- Forgetting the fifth audit column: `Users` limits dashboard visibility for
  "My Content Audit"; the full Content Audit page still lists every rule.
- Using `__DIR__` inside `src/Traits` as if it were the module root. Use
  `dirname(__DIR__, 2)` from traits when referencing module-level files.
- Adding a code change without a SemVer/module version bump and a short
  CHANGELOG entry.
- Editing `views/_layout.php` broadly for a narrow UI issue. Keep CSS changes
  scoped and check mobile wrapping.

## Verification

Use the relevant subset for small changes and the full set for behavior changes:

```bash
php -l Verk.module.php
php -l src/Traits/*.php
php -l src/Services/*.php
php -l views/*.php
php -l views/partials/*.php
php VerkTest.php
git diff --check
```

For local ProcessWire verification on `vox.dev`, refresh the module and confirm
the module version:

```bash
php -d pdo_mysql.default_socket=/Applications/MAMP/tmp/mysql/mysql.sock \
    -d mysqli.default_socket=/Applications/MAMP/tmp/mysql/mysql.sock <<'PHP'
<?php
ob_start();
require '/Users/mas/Sites/vox.dev/index.php';
ob_end_clean();
$wire = ProcessWire\wire();
$wire->modules->refresh();
$info = $wire->modules->getModuleInfo('Verk');
echo 'vox.dev Verk version: ' . ($info['version'] ?? 'missing') . "\n";
PHP
```

## Versioning And Release Notes

Every code update should include:

- SemVer version update in `Verk.module.php` docblock and module info;
- matching ProcessWire integer module version;
- short `CHANGELOG.md` entry;
- README upgrade version when releasing a new public version.

Patch releases are for fixes and small polish, minor releases are for
backwards-compatible features or large internal structure changes, and major
releases are for breaking changes.

## Handoff

Finish with:

- what changed;
- version/changelog status;
- tests run;
- whether GitHub PRs/issues were closed or still need attention;
- any live-site or forum action that could not be completed.
